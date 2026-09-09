<?php

namespace App\Services\Leegality;

use App\Models\Vendor;
use App\Models\VendorLeegalitySignature;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class VendorLeegalitySignatureService
{
    public function __construct(
        public readonly LeegalityClient $client,
        private readonly VendorServiceAgreementDocument $agreementDocument
    ) {}

    public function latestForVendor(Vendor $vendor): ?VendorLeegalitySignature
    {
        return VendorLeegalitySignature::query()
            ->where('vendor_id', $vendor->id)
            ->orderByDesc('id')
            ->first();
    }

    public function sendForSignature(Vendor $vendor): VendorLeegalitySignature
    {
        if (! $this->client->isConfigured()) {
            throw new RuntimeException('Leegality is not configured. Set LEEGALITY_AUTH_TOKEN and LEEGALITY_PROFILE_ID in .env.');
        }

        $email = trim((string) $vendor->email);
        $name = trim((string) ($vendor->customer_name ?: $vendor->name));
        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('Vendor email is missing or invalid. Update registration details first.');
        }
        if ($name === '') {
            throw new RuntimeException('Vendor contact person name is required before sending for signature.');
        }

        $active = VendorLeegalitySignature::query()
            ->where('vendor_id', $vendor->id)
            ->whereIn('signature_status', [
                VendorLeegalitySignature::STATUS_SENT,
                VendorLeegalitySignature::STATUS_SIGNED,
                VendorLeegalitySignature::STATUS_APPROVED,
                VendorLeegalitySignature::STATUS_COMPLETED,
            ])
            ->whereNotNull('leegality_document_id')
            ->orderByDesc('id')
            ->first();

        if ($active && in_array($active->signature_status, [
            VendorLeegalitySignature::STATUS_SIGNED,
            VendorLeegalitySignature::STATUS_APPROVED,
            VendorLeegalitySignature::STATUS_COMPLETED,
        ], true)) {
            throw new RuntimeException('Agreement is already signed for this vendor.');
        }

        if ($active && $active->signature_status === VendorLeegalitySignature::STATUS_SENT) {
            $active->update([
                'signature_status' => VendorLeegalitySignature::STATUS_FAILED,
                'error_message' => 'Superseded by a new Send for Signature request.',
            ]);
        }

        $irn = 'VEN-'.$vendor->id.'-'.now()->format('YmdHis');
        $filePayload = $this->buildFilePayload($vendor, $irn);

        $payload = [
            'profileId' => (string) config('leegality.profile_id'),
            'file' => $filePayload,
            'invitees' => [[
                'name' => $name,
                'email' => $email,
                'phone' => preg_replace('/\D+/', '', (string) $vendor->contact_no) ?: null,
                'emailNotification' => false,
            ]],
            'irn' => $irn,
        ];

        if (empty($payload['invitees'][0]['phone'])) {
            unset($payload['invitees'][0]['phone']);
        }

        $response = $this->client->createSignRequest($payload);
        $status = (int) ($response['status'] ?? 0);
        if ($status !== 1) {
            throw new RuntimeException($this->client->firstMessage($response) ?: 'Leegality could not create the signing request.');
        }

        $data = is_array($response['data'] ?? null) ? $response['data'] : [];
        $documentId = (string) ($data['documentId'] ?? '');
        if ($documentId === '') {
            throw new RuntimeException('Leegality response did not include documentId.');
        }

        $invitation = null;
        if (! empty($data['invitationUrls']) && is_array($data['invitationUrls'])) {
            $invitation = $data['invitationUrls'][0] ?? null;
        }

        $signUrl = null;
        if (is_array($invitation)) {
            $signUrl = (string) ($invitation['signUrl'] ?? '');
        }

        $signature = VendorLeegalitySignature::create([
            'vendor_id' => $vendor->id,
            'leegality_document_id' => $documentId,
            'irn' => $irn,
            'signature_status' => VendorLeegalitySignature::STATUS_SENT,
            'signer_email' => $email,
            'signer_name' => $name,
            'sign_url' => $signUrl ?: null,
            'sent_at' => now(),
            'create_response' => $response,
            'sent_by' => Auth::id(),
        ]);

        $this->syncVendorAgreementNumber($vendor, $signature);

        return $signature;
    }

    public function handleWebhook(array $payload): ?VendorLeegalitySignature
    {
        $docId = (string) ($payload['documentId'] ?? $payload['document_id'] ?? '');
        $irn = (string) ($payload['irn'] ?? '');

        $query = VendorLeegalitySignature::query();
        if ($docId !== '') {
            $query->where('leegality_document_id', $docId);
        } elseif ($irn !== '') {
            $query->where('irn', $irn);
        } else {
            return null;
        }

        $signature = $query->orderByDesc('id')->first();
        if (! $signature) {
            return null;
        }

        $docStatus = (string) ($payload['documentStatus'] ?? $payload['document_status'] ?? $payload['status'] ?? '');
        $invStatus = (string) ($payload['invitationStatus'] ?? $payload['inviteeStatus'] ?? '');

        $updates = [
            'last_webhook_payload' => $payload,
            'last_webhook_at' => now(),
            'document_status' => $docStatus ?: $signature->document_status,
            'signer_action' => $invStatus ?: $signature->signer_action,
        ];

        $signedLike = false;
        foreach ([$docStatus, $invStatus] as $st) {
            $stU = strtoupper((string) $st);
            if (in_array($stU, ['SIGNED', 'COMPLETED', 'APPROVED'], true)) {
                $signedLike = true;
                break;
            }
        }

        if ($signedLike) {
            $updates['signature_status'] = VendorLeegalitySignature::STATUS_SIGNED;
            if (! $signature->signed_at) {
                $updates['signed_at'] = now();
            }
        } elseif (in_array(strtoupper($docStatus), ['REJECTED', 'CANCELLED', 'EXPIRED'], true)) {
            $updates['signature_status'] = VendorLeegalitySignature::STATUS_REJECTED;
        }

        $signature->update($updates);

        if ($signature->isSignedLike() || strcasecmp((string) $docStatus, 'Completed') === 0) {
            try {
                $this->downloadAndStoreArtifacts($signature);
            } catch (\Throwable $e) {
                Log::warning('Leegality vendor artifact download failed during webhook', [
                    'signature_id' => $signature->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $signature->fresh();
    }

    public function downloadAndStoreArtifacts(VendorLeegalitySignature $signature): VendorLeegalitySignature
    {
        if (! $signature->leegality_document_id) {
            return $signature;
        }

        $details = $this->client->getDocumentDetails($signature->leegality_document_id);
        $documentData = is_array($details['data'] ?? null) ? $details['data'] : [];

        $signedDocUrl = (string) ($documentData['fileUrl'] ?? $documentData['signedUrl'] ?? $documentData['documentUrl'] ?? '');
        $auditUrl = (string) ($documentData['auditTrailUrl'] ?? $documentData['auditUrl'] ?? '');

        $dir = 'leegality/vendor_signatures/'.$signature->vendor_id.'/'.$signature->id;
        $updates = [];

        if ($signedDocUrl !== '') {
            $resp = Http::get($signedDocUrl);
            if ($resp->successful() && strlen($resp->body()) > 100) {
                $path = $dir.'/signed_agreement.pdf';
                Storage::disk('public')->put($path, $resp->body());
                $updates['signed_document'] = $path;
                $updates['signature_status'] = VendorLeegalitySignature::STATUS_SIGNED;
                if (! $signature->signed_at) {
                    $updates['signed_at'] = now();
                }
            }
        }

        if ($auditUrl !== '') {
            $resp = Http::get($auditUrl);
            if ($resp->successful() && strlen($resp->body()) > 100) {
                $path = $dir.'/audit_trail.pdf';
                Storage::disk('public')->put($path, $resp->body());
                $updates['audit_trail'] = $path;
            }
        }

        if (! empty($updates)) {
            $signature->update($updates);
        }

        $signature = $signature->fresh();
        if ($signature->vendor) {
            $this->syncVendorAgreementNumber($signature->vendor, $signature);
        }

        return $signature;
    }

    private function buildFilePayload(Vendor $vendor, string $irn): array
    {
        $pdfBinary = $this->agreementDocument->pdfBinary($vendor, $irn);

        return [
            'name' => 'Carelix-Vendor-Service-Agreement-'.$vendor->id.'.pdf',
            'file' => base64_encode($pdfBinary),
        ];
    }

    private function syncVendorAgreementNumber(Vendor $vendor, VendorLeegalitySignature $signature): void
    {
        if (! $vendor->agreement_number) {
            $number = 'CLX-AGR-VEN-'.now()->format('Y').'-'.str_pad((string) $vendor->id, 6, '0', STR_PAD_LEFT);
            $vendor->update(['agreement_number' => $number]);
        }
    }

    public function addAuthorisedSignature(Vendor $vendor): VendorLeegalitySignature
    {
        $signature = $this->latestForVendor($vendor);
        if (! $signature || ! $signature->leegality_document_id) {
            throw new RuntimeException('Vendor signature not found. Vendor must sign first.');
        }

        $documentId = (string) $signature->leegality_document_id;

        // 1. Fetch Vendor's partially signed PDF Base64
        $partiallySignedPdfBase64 = null;

        if ($signature->signed_document && Storage::disk('public')->exists($signature->signed_document)) {
            $partiallySignedPdfBase64 = base64_encode((string) Storage::disk('public')->get($signature->signed_document));
        }

        if (! $partiallySignedPdfBase64) {
            $fetchRes = $this->client->fetchDocument($documentId, 'DOCUMENT');
            if (isset($fetchRes['data']['file']) && is_string($fetchRes['data']['file'])) {
                $bin = Http::timeout(60)->get($fetchRes['data']['file']);
                if ($bin->successful() && $bin->body() !== '') {
                    $partiallySignedPdfBase64 = base64_encode($bin->body());
                }
            }
        }

        if (! $partiallySignedPdfBase64) {
            $details = $this->client->documentDetails($documentId, true, false);
            $fileUrl = $details['data']['file'] ?? ($details['data']['files'][0] ?? null);
            if ($fileUrl && is_string($fileUrl)) {
                if (str_starts_with($fileUrl, 'http://') || str_starts_with($fileUrl, 'https://')) {
                    $bin = Http::timeout(60)->get($fileUrl);
                    if ($bin->successful() && $bin->body() !== '') {
                        $partiallySignedPdfBase64 = base64_encode($bin->body());
                    }
                } else {
                    $partiallySignedPdfBase64 = $fileUrl;
                }
            }
        }

        if (! $partiallySignedPdfBase64) {
            throw new RuntimeException('Signed PDF content missing in Leegality response. Ensure Vendor has completed signature.');
        }

        // 2. Build Authorised Signatory Invitee Payload with appearances and optional Automated Signer
        $authName = (string) config('leegality.authorised_signatory_name', 'Carelix Authorised Signatory');
        $authEmail = (string) config('leegality.authorised_signatory_email', 'legal@carelixhealthcare.com');
        $authPhone = preg_replace('/\D+/', '', (string) config('leegality.authorised_signatory_phone', '7666426664')) ?: null;

        $irn = 'AUTH-CRLX-VEN-'.$vendor->id.'-'.time();

        $invitee = [
            'name' => $authName,
            'email' => $authEmail,
            'phone' => $authPhone,
            'emailNotification' => false,
            'phoneNotification' => false,
            'appearances' => [
                [
                    'page' => 'L',
                    'x1' => (int) config('leegality.appearance_x1', 40),
                    'y1' => (int) config('leegality.appearance_y1', 40),
                    'x2' => (int) config('leegality.appearance_x2', 200),
                    'y2' => (int) config('leegality.appearance_y2', 100),
                ],
            ],
        ];

        if (empty($invitee['phone'])) {
            unset($invitee['phone']);
        }

        $signerId = trim((string) config('leegality.automated_signer_id'));
        $passkey = trim((string) config('leegality.automated_signer_passkey'));

        if ($signerId !== '' && $passkey !== '') {
            $invitee['signatures'] = [
                [
                    'type' => 'AUTOMATED_SIGN',
                    'config' => [
                        'id' => $signerId,
                        'passkey' => $passkey,
                    ],
                ],
            ];
        }

        $profileId = trim((string) config('leegality.profile_id'));

        $payload = [
            'file' => [
                'name' => 'Vendor_Agreement_Final_'.$vendor->id.'.pdf',
                'file' => $partiallySignedPdfBase64,
            ],
            'irn' => $irn,
            'invitees' => [$invitee],
        ];

        if ($profileId !== '') {
            $payload['profileId'] = $profileId;
        }

        // 3. Send signing request to Leegality for Authorised Signatory
        $response = $this->client->createSignRequest($payload);
        $status = (int) ($response['status'] ?? 0);
        if ($status !== 1) {
            $errMsg = $this->client->firstMessage($response) ?: 'Authorised signature request failed on Leegality.';
            throw new RuntimeException($errMsg);
        }

        $data = is_array($response['data'] ?? null) ? $response['data'] : [];
        $finalDocId = (string) ($data['documentId'] ?? $documentId);

        // 4. Download final executed PDF & store locally
        $dir = 'leegality/vendor_signatures/'.$vendor->id.'/'.$signature->id;
        Storage::disk('public')->makeDirectory($dir);
        $signedPath = $dir.'/final-executed.pdf';

        $finalFetched = $this->storeFetchedFile($finalDocId, 'DOCUMENT', $signedPath);
        if (! $finalFetched && ! empty($data['file'])) {
            $finalFetched = $this->storeRemoteUrl((string) $data['file'], $signedPath);
        }

        // 5. Update database record
        $signature->update([
            'leegality_document_id' => $finalDocId,
            'signed_document' => $finalFetched ?: $signature->signed_document,
            'signature_status' => VendorLeegalitySignature::STATUS_COMPLETED,
            'signer_action' => 'BOTH_SIGNED',
            'document_status' => 'Completed',
            'signed_at' => now(),
        ]);

        // 6. Send final executed agreement PDF with both signatures to vendor via email
        $email = trim((string) ($signature->signer_email ?: $vendor->email));
        if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) && $signature->signed_document) {
            try {
                \Illuminate\Support\Facades\Mail::to($email)->send(
                    new \App\Mail\FinalSignedAgreementMail(
                        $vendor->customer_name ?: $vendor->name ?: 'Vendor',
                        'vendor',
                        $signature->signed_document
                    )
                );
            } catch (\Throwable $e) {
                Log::warning('Sending final dual-signed agreement email failed', ['error' => $e->getMessage()]);
            }
        }

        return $signature;
    }
}
