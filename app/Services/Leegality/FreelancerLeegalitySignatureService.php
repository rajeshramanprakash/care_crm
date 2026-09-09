<?php

namespace App\Services\Leegality;

use App\Models\FreelancerLeegalitySignature;
use App\Models\JobRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class FreelancerLeegalitySignatureService
{
    public function __construct(
        public readonly LeegalityClient $client,
        private readonly FreelancerServiceAgreementDocument $agreementDocument
    ) {}

    public function latestForFreelancer(JobRequest $freelancer): ?FreelancerLeegalitySignature
    {
        return FreelancerLeegalitySignature::query()
            ->where('job_request_id', $freelancer->id)
            ->orderByDesc('id')
            ->first();
    }

    public function sendForSignature(JobRequest $freelancer): FreelancerLeegalitySignature
    {
        if (! $this->client->isConfigured()) {
            throw new RuntimeException('Leegality is not configured. Set LEEGALITY_AUTH_TOKEN and LEEGALITY_PROFILE_ID in .env.');
        }

        $email = trim((string) $freelancer->email);
        $name = trim((string) $freelancer->name);
        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('Freelancer email is missing or invalid. Update registration details first.');
        }
        if ($name === '') {
            throw new RuntimeException('Freelancer name is required before sending for signature.');
        }

        $active = FreelancerLeegalitySignature::query()
            ->where('job_request_id', $freelancer->id)
            ->whereIn('signature_status', [
                FreelancerLeegalitySignature::STATUS_SENT,
                FreelancerLeegalitySignature::STATUS_SIGNED,
                FreelancerLeegalitySignature::STATUS_APPROVED,
                FreelancerLeegalitySignature::STATUS_COMPLETED,
            ])
            ->whereNotNull('leegality_document_id')
            ->orderByDesc('id')
            ->first();

        if ($active && in_array($active->signature_status, [
            FreelancerLeegalitySignature::STATUS_SIGNED,
            FreelancerLeegalitySignature::STATUS_APPROVED,
            FreelancerLeegalitySignature::STATUS_COMPLETED,
        ], true)) {
            throw new RuntimeException('Agreement is already signed for this freelancer.');
        }

        if ($active && $active->signature_status === FreelancerLeegalitySignature::STATUS_SENT) {
            $active->update([
                'signature_status' => FreelancerLeegalitySignature::STATUS_FAILED,
                'error_message' => 'Superseded by a new Send for Signature request.',
            ]);
        }

        $irn = 'FRL-'.$freelancer->id.'-'.now()->format('YmdHis').'-'.rand(100, 999);
        $filePayload = $this->buildFilePayload($freelancer, $irn);

        $payload = [
            'profileId' => (string) config('leegality.profile_id'),
            'file' => $filePayload,
            'invitees' => [[
                'name' => $name,
                'email' => $email,
                'phone' => preg_replace('/\D+/', '', (string) ($freelancer->contact_no ?: $freelancer->mobile)) ?: null,
                'emailNotification' => false,
            ]],
            'irn' => $irn,
        ];

        // Drop null phone so Leegality does not reject empty values.
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

        $signUrl = null;
        $invitees = is_array($data['invitees'] ?? null) ? $data['invitees'] : [];
        if ($invitees !== []) {
            $first = $invitees[0];
            if (is_array($first)) {
                $signUrl = $first['signUrl'] ?? $first['invitationUrl'] ?? null;
            }
        }

        if ($signUrl) {
            \Illuminate\Support\Facades\Mail::to($email)->send(new \App\Mail\FreelancerLeegalitySignatureMail($freelancer, $signUrl));
        }

        return FreelancerLeegalitySignature::create([
            'job_request_id' => $freelancer->id,
            'leegality_document_id' => $documentId,
            'irn' => (string) ($data['irn'] ?? $irn),
            'signature_status' => FreelancerLeegalitySignature::STATUS_SENT,
            'document_status' => 'Sent',
            'signer_email' => $email,
            'signer_name' => $name,
            'sign_url' => $signUrl ? (string) $signUrl : null,
            'sent_at' => now(),
            'create_response' => $response,
            'sent_by' => Auth::id(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function handleWebhook(array $payload): ?FreelancerLeegalitySignature
    {
        if (config('leegality.verify_mac')) {
            $this->assertValidMac($payload);
        }

        $documentId = (string) ($payload['documentId'] ?? '');
        if ($documentId === '') {
            Log::warning('Leegality webhook missing documentId', ['payload' => $payload]);

            return null;
        }

        $signature = FreelancerLeegalitySignature::query()
            ->where('leegality_document_id', $documentId)
            ->orderByDesc('id')
            ->first();

        if (! $signature) {
            Log::warning('Leegality webhook for unknown document', ['documentId' => $documentId]);

            return null;
        }

        $request = is_array($payload['request'] ?? null) ? $payload['request'] : [];
        $action = strtoupper(trim((string) ($request['action'] ?? '')));
        $documentStatus = (string) ($payload['documentStatus'] ?? $signature->document_status);

        $mapped = match ($action) {
            'SIGNED' => FreelancerLeegalitySignature::STATUS_SIGNED,
            'APPROVED' => FreelancerLeegalitySignature::STATUS_APPROVED,
            'REJECTED' => FreelancerLeegalitySignature::STATUS_REJECTED,
            default => null,
        };

        if ($mapped === null && strcasecmp($documentStatus, 'Completed') === 0) {
            $mapped = FreelancerLeegalitySignature::STATUS_COMPLETED;
        }

        $updates = [
            'document_status' => $documentStatus ?: $signature->document_status,
            'signer_action' => $action !== '' ? $action : $signature->signer_action,
            'last_webhook_payload' => $payload,
            'last_webhook_at' => now(),
        ];

        if (! empty($request['email'])) {
            $updates['signer_email'] = (string) $request['email'];
        }
        if (! empty($request['name'])) {
            $updates['signer_name'] = (string) $request['name'];
        }
        if (! empty($request['invitationUrl']) || ! empty($request['signUrl'])) {
            $updates['sign_url'] = (string) ($request['invitationUrl'] ?? $request['signUrl']);
        }

        if ($mapped !== null) {
            $updates['signature_status'] = $mapped;
            if (in_array($mapped, [
                FreelancerLeegalitySignature::STATUS_SIGNED,
                FreelancerLeegalitySignature::STATUS_APPROVED,
                FreelancerLeegalitySignature::STATUS_COMPLETED,
            ], true) && ! $signature->signed_at) {
                $updates['signed_at'] = now();
            }
            if ($mapped === FreelancerLeegalitySignature::STATUS_REJECTED) {
                $updates['error_message'] = (string) ($request['rejectionMessage'] ?? $request['error'] ?? 'Rejected by signer');
            }
        }

        $signature->fill($updates);
        $signature->save();

        $shouldFetchFiles = in_array($signature->signature_status, [
            FreelancerLeegalitySignature::STATUS_SIGNED,
            FreelancerLeegalitySignature::STATUS_APPROVED,
            FreelancerLeegalitySignature::STATUS_COMPLETED,
        ], true) || strcasecmp((string) $signature->document_status, 'Completed') === 0;

        if ($shouldFetchFiles) {
            try {
                $this->assignPartnerAndAgreementIds($signature);
            } catch (\Throwable $e) {
                Log::error('Failed to assign Agreement ID', ['error' => $e->getMessage()]);
            }

            try {
                $this->downloadAndStoreArtifacts($signature);
            } catch (\Throwable $e) {
                Log::error('Leegality artifact download failed', [
                    'documentId' => $documentId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $signature->fresh();
    }

    public function assignPartnerAndAgreementIds(FreelancerLeegalitySignature $signature): void
    {
        $freelancer = $signature->jobRequest;
        if (! $freelancer) {
            return;
        }

        if ($freelancer->agreement_number) {
            return;
        }

        $year = now()->format('Y');

        $count = \App\Models\JobRequest::whereNotNull('agreement_number')
            ->where('agreement_number', 'like', "%-{$year}-%")
            ->count();
        
        $nextId = str_pad((string)($count + 1), 6, '0', STR_PAD_LEFT);
        
        $freelancer->agreement_number = "CLX-AGR-FRL-{$year}-{$nextId}";
        $freelancer->save();
    }

    public function addAuthorisedSignature(JobRequest $freelancer): FreelancerLeegalitySignature
    {
        $signature = $this->latestForFreelancer($freelancer);
        if (! $signature || ! $signature->leegality_document_id) {
            throw new RuntimeException('Freelancer signature not found. Freelancer must sign first.');
        }

        $documentId = (string) $signature->leegality_document_id;

        // 1. Fetch Freelancer's partially signed PDF Base64
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
            throw new RuntimeException('Signed PDF content missing in Leegality response. Ensure Freelancer has completed signature.');
        }

        // 2. Build Authorised Signatory Invitee Payload with appearances and optional Automated Signer
        $authName = (string) config('leegality.authorised_signatory_name', 'Carelix Authorised Signatory');
        $authEmail = (string) config('leegality.authorised_signatory_email', 'legal@carelixhealthcare.com');
        $authPhone = preg_replace('/\D+/', '', (string) config('leegality.authorised_signatory_phone', '7666426664')) ?: null;

        $irn = 'AUTH-CRLX-FRL-'.$freelancer->id.'-'.time();

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
                'name' => 'Freelancer_Agreement_Final_'.$freelancer->id.'.pdf',
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
        $dir = 'leegality/freelancer_signatures/'.$freelancer->id.'/'.$signature->id;
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
            'signature_status' => FreelancerLeegalitySignature::STATUS_COMPLETED,
            'signer_action' => 'BOTH_SIGNED',
            'document_status' => 'Completed',
            'signed_at' => now(),
        ]);

        // 6. Send final executed agreement PDF with both signatures to freelancer via email
        $email = trim((string) ($signature->signer_email ?: $freelancer->email));
        if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) && $signature->signed_document) {
            try {
                \Illuminate\Support\Facades\Mail::to($email)->send(
                    new \App\Mail\FinalSignedAgreementMail(
                        $freelancer->name ?: 'Freelancer',
                        'freelancer',
                        $signature->signed_document
                    )
                );
            } catch (\Throwable $e) {
                Log::warning('Sending final dual-signed agreement email failed', ['error' => $e->getMessage()]);
            }
        }

        return $signature;
    }

    public function downloadAndStoreArtifacts(FreelancerLeegalitySignature $signature): FreelancerLeegalitySignature
    {
        $documentId = (string) $signature->leegality_document_id;
        if ($documentId === '') {
            return $signature;
        }

        $dir = 'leegality/freelancer_signatures/'.$signature->job_request_id.'/'.$signature->id;
        Storage::disk('public')->makeDirectory($dir);

        $signedPath = $this->storeFetchedFile($documentId, 'DOCUMENT', $dir.'/signed.pdf');
        $auditPath = $this->storeFetchedFile($documentId, 'AUDIT_TRAIL', $dir.'/audit-trail.pdf');

        if (! $signedPath || ! $auditPath) {
            $details = $this->client->documentDetails($documentId, true, true);
            $data = is_array($details['data'] ?? null) ? $details['data'] : [];
            if (! $signedPath && ! empty($data['file'])) {
                $signedPath = $this->storeRemoteUrl((string) $data['file'], $dir.'/signed.pdf');
            }
            if (! $auditPath && ! empty($data['auditTrail'])) {
                $auditPath = $this->storeRemoteUrl((string) $data['auditTrail'], $dir.'/audit-trail.pdf');
            }
        }

        $signature->fill([
            'signed_document' => $signedPath ?: $signature->signed_document,
            'audit_trail' => $auditPath ?: $signature->audit_trail,
            'signed_at' => $signature->signed_at ?: now(),
            'signature_status' => $signature->isSignedLike()
                ? $signature->signature_status
                : FreelancerLeegalitySignature::STATUS_COMPLETED,
        ]);
        $signature->save();

        return $signature;
    }

    private function storeFetchedFile(string $documentId, string $type, string $relativePath): ?string
    {
        try {
            $res = $this->client->fetchDocument($documentId, $type);
            if ((int) ($res['status'] ?? 0) !== 1) {
                return null;
            }
            $url = $res['data']['file'] ?? null;
            if (! is_string($url) || $url === '') {
                return null;
            }

            return $this->storeRemoteUrl($url, $relativePath);
        } catch (\Throwable $e) {
            Log::warning('Leegality fetchDocument failed for freelancer', [
                'documentId' => $documentId,
                'type' => $type,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    private function storeRemoteUrl(string $url, string $relativePath): ?string
    {
        $bin = Http::timeout(60)->get($url);
        if (! $bin->successful() || $bin->body() === '') {
            return null;
        }
        Storage::disk('public')->put($relativePath, $bin->body());

        return $relativePath;
    }

    private function buildFilePayload(JobRequest $freelancer, string $irn): array
    {
        $name = 'Carelix_Freelancer_Agreement_'.$freelancer->id;
        $payload = [
            'name' => $name,
            'file' => base64_encode($this->agreementDocument->pdfBinary($freelancer, $irn)),
        ];

        return $payload;
    }

    private function assertValidMac(array $payload): void
    {
        $salt = trim((string) config('leegality.private_salt'));
        $mac = (string) ($payload['mac'] ?? '');
        $documentId = (string) ($payload['documentId'] ?? '');
        if ($salt === '' || $mac === '' || $documentId === '') {
            throw new RuntimeException('Leegality webhook MAC verification failed.');
        }

        $expected = hash_hmac('sha1', $documentId, $salt);
        if (! hash_equals(strtolower($expected), strtolower($mac))) {
            throw new RuntimeException('Leegality webhook MAC verification failed.');
        }
    }
}
