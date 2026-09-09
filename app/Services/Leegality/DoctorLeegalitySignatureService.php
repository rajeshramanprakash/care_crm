<?php

namespace App\Services\Leegality;

use App\Models\DoctorLeegalitySignature;
use App\Models\DoctorRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class DoctorLeegalitySignatureService
{
    public function __construct(
        public readonly LeegalityClient $client,
        private readonly DoctorServiceAgreementDocument $agreementDocument
    ) {}

    public function latestForDoctor(DoctorRequest $doctor): ?DoctorLeegalitySignature
    {
        return DoctorLeegalitySignature::query()
            ->where('doctor_request_id', $doctor->id)
            ->orderByDesc('id')
            ->first();
    }

    public function sendForSignature(DoctorRequest $doctor): DoctorLeegalitySignature
    {
        if (! $this->client->isConfigured()) {
            throw new RuntimeException('Leegality is not configured. Set LEEGALITY_AUTH_TOKEN, LEEGALITY_PROFILE_ID and LEEGALITY_AUTH_PROFILE_ID in .env.');
        }

        $email = trim((string) $doctor->email);
        $name = trim((string) $doctor->name);
        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('Doctor email is missing or invalid. Update registration details first.');
        }
        if ($name === '') {
            throw new RuntimeException('Doctor name is required before sending for signature.');
        }

        $active = DoctorLeegalitySignature::query()
            ->where('doctor_request_id', $doctor->id)
            ->whereIn('signature_status', [
                DoctorLeegalitySignature::STATUS_SENT,
                DoctorLeegalitySignature::STATUS_SIGNED,
                DoctorLeegalitySignature::STATUS_APPROVED,
                DoctorLeegalitySignature::STATUS_COMPLETED,
            ])
            ->whereNotNull('leegality_document_id')
            ->orderByDesc('id')
            ->first();

        if ($active && in_array($active->signature_status, [
            DoctorLeegalitySignature::STATUS_SIGNED,
            DoctorLeegalitySignature::STATUS_APPROVED,
            DoctorLeegalitySignature::STATUS_COMPLETED,
        ], true)) {
            throw new RuntimeException('Agreement is already signed for this doctor.');
        }

        if ($active && $active->signature_status === DoctorLeegalitySignature::STATUS_SENT) {
            $active->update([
                'signature_status' => DoctorLeegalitySignature::STATUS_FAILED,
                'error_message' => 'Superseded by a new Send for Signature request.',
            ]);
        }

        $irn = 'DR-'.$doctor->id.'-'.now()->format('YmdHis').'-'.rand(100, 999);
        $filePayload = $this->buildFilePayload($doctor, $irn);

        $payload = [
            'profileId' => (string) config('leegality.profile_id'),
            'file' => $filePayload,
            'invitees' => [[
                'name' => $name,
                'email' => $email,
                'phone' => preg_replace('/\D+/', '', (string) ($doctor->mobile ?: $doctor->contact_no)) ?: null,
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
            \Illuminate\Support\Facades\Mail::to($email)->send(new \App\Mail\DoctorLeegalitySignatureMail($doctor, $signUrl));
        }

        return DoctorLeegalitySignature::create([
            'doctor_request_id' => $doctor->id,
            'leegality_document_id' => $documentId,
            'irn' => (string) ($data['irn'] ?? $irn),
            'signature_status' => DoctorLeegalitySignature::STATUS_SENT,
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
    public function handleWebhook(array $payload): ?DoctorLeegalitySignature
    {
        if (config('leegality.verify_mac')) {
            $this->assertValidMac($payload);
        }

        $documentId = (string) ($payload['documentId'] ?? '');
        if ($documentId === '') {
            Log::warning('Leegality webhook missing documentId', ['payload' => $payload]);

            return null;
        }

        $signature = DoctorLeegalitySignature::query()
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
            'SIGNED' => DoctorLeegalitySignature::STATUS_SIGNED,
            'APPROVED' => DoctorLeegalitySignature::STATUS_APPROVED,
            'REJECTED' => DoctorLeegalitySignature::STATUS_REJECTED,
            default => null,
        };

        if ($mapped === null && strcasecmp($documentStatus, 'Completed') === 0) {
            $mapped = DoctorLeegalitySignature::STATUS_COMPLETED;
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
                DoctorLeegalitySignature::STATUS_SIGNED,
                DoctorLeegalitySignature::STATUS_APPROVED,
                DoctorLeegalitySignature::STATUS_COMPLETED,
            ], true) && ! $signature->signed_at) {
                $updates['signed_at'] = now();
            }
            if ($mapped === DoctorLeegalitySignature::STATUS_REJECTED) {
                $updates['error_message'] = (string) ($request['rejectionMessage'] ?? $request['error'] ?? 'Rejected by signer');
            }
        }

        $signature->fill($updates);
        $signature->save();

        $shouldFetchFiles = in_array($signature->signature_status, [
            DoctorLeegalitySignature::STATUS_SIGNED,
            DoctorLeegalitySignature::STATUS_APPROVED,
            DoctorLeegalitySignature::STATUS_COMPLETED,
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

    public function assignPartnerAndAgreementIds(DoctorLeegalitySignature $signature): void
    {
        $request = $signature->doctorRequest;
        if (! $request) {
            return;
        }

        // Only assign if they are not already assigned
        if ($request->agreement_number) {
            return;
        }

        $year = now()->format('Y');

        // Logic to generate sequential number for current year
        $count = \App\Models\DoctorRequest::whereNotNull('agreement_number')
            ->where('agreement_number', 'like', "%-{$year}-%")
            ->count();
        
        $nextId = str_pad((string)($count + 1), 6, '0', STR_PAD_LEFT);
        
        $request->agreement_number = "CLX-AGR-DOC-{$year}-{$nextId}";
        $request->save();
    }

    public function addAuthorisedSignature(DoctorRequest $doctor): DoctorLeegalitySignature
    {
        $signature = $this->latestForDoctor($doctor);
        if (! $signature || ! $signature->leegality_document_id) {
            throw new RuntimeException('Doctor signature not found. Doctor must sign first.');
        }

        $documentId = (string) $signature->leegality_document_id;

        // 1. Fetch Doctor's partially signed PDF Base64
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
            throw new RuntimeException('Signed PDF content missing in Leegality response. Ensure Doctor has completed signature.');
        }

        // 2. Build Authorised Signatory Invitee Payload with appearances and optional Automated Signer
        $authName = (string) config('leegality.authorised_signatory_name', 'Carelix Authorised Signatory');
        $authEmail = (string) config('leegality.authorised_signatory_email', 'legal@carelixhealthcare.com');
        $authPhone = preg_replace('/\D+/', '', (string) config('leegality.authorised_signatory_phone', '7666426664')) ?: null;

        $irn = 'AUTH-CRLX-DR-'.$doctor->id.'-'.time();

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

        $profileId = trim((string) config('leegality.auth_profile_id'));
        if ($profileId === '') {
            $profileId = trim((string) config('leegality.profile_id'));
        }

        $payload = [
            'file' => [
                'name' => 'Doctor_Agreement_Final_'.$doctor->id.'.pdf',
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

        $signUrl = null;
        $invitations = is_array($data['invitations'] ?? null) ? $data['invitations'] : (is_array($data['invitees'] ?? null) ? $data['invitees'] : []);
        if ($invitations !== []) {
            $first = $invitations[0];
            if (is_array($first)) {
                $signUrl = $first['signUrl'] ?? $first['invitationUrl'] ?? null;
            }
        }

        // 4. Download final executed PDF & store locally
        $dir = 'leegality/signatures/'.$doctor->id.'/'.$signature->id;
        Storage::disk('public')->makeDirectory($dir);
        $signedPath = $dir.'/final-executed.pdf';

        $finalFetched = $this->storeFetchedFile($finalDocId, 'DOCUMENT', $signedPath);
        if (! $finalFetched && ! empty($data['file'])) {
            $finalFetched = $this->storeRemoteUrl((string) $data['file'], $signedPath);
        }

        // Check if both signers have completed
        $details = $this->client->documentDetails($finalDocId, true, false);
        $docData = is_array($details['data'] ?? null) ? $details['data'] : [];
        $docStatus = (string) ($docData['documentStatus'] ?? ($data['documentStatus'] ?? 'Sent'));
        $isCompleted = strcasecmp($docStatus, 'Completed') === 0;

        // 5. Update database record
        $signature->update([
            'leegality_document_id' => $finalDocId,
            'signed_document' => $finalFetched ?: $signature->signed_document,
            'sign_url' => $signUrl ?: $signature->sign_url,
            'signature_status' => $isCompleted ? DoctorLeegalitySignature::STATUS_COMPLETED : DoctorLeegalitySignature::STATUS_SENT,
            'signer_action' => $isCompleted ? 'BOTH_SIGNED' : 'AUTH_SIGN_SENT',
            'document_status' => $docStatus,
            'signed_at' => $isCompleted ? ($signature->signed_at ?: now()) : $signature->signed_at,
        ]);

        // 6. If completed, send final executed agreement PDF with both signatures to doctor via email
        if ($isCompleted && $email = trim((string) ($signature->signer_email ?: $doctor->email))) {
            if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) && $signature->signed_document) {
                try {
                    \Illuminate\Support\Facades\Mail::to($email)->send(
                        new \App\Mail\FinalSignedAgreementMail(
                            $doctor->name ?: 'Doctor',
                            'doctor',
                            $signature->signed_document
                        )
                    );
                } catch (\Throwable $e) {
                    Log::warning('Sending final dual-signed agreement email failed', ['error' => $e->getMessage()]);
                }
            }
        }

        return $signature;
    }



    public function downloadAndStoreArtifacts(DoctorLeegalitySignature $signature): DoctorLeegalitySignature
    {
        $documentId = (string) $signature->leegality_document_id;
        if ($documentId === '') {
            return $signature;
        }

        $dir = 'leegality/signatures/'.$signature->doctor_request_id.'/'.$signature->id;
        Storage::disk('public')->makeDirectory($dir);

        // Fetch document details from Leegality to check signer status
        $details = $this->client->documentDetails($documentId, true, true);
        $data = is_array($details['data'] ?? null) ? $details['data'] : [];

        $docStatus = (string) ($data['documentStatus'] ?? $signature->document_status);
        $requests = is_array($data['requests'] ?? null) ? $data['requests'] : [];
        $isSigned = false;

        foreach ($requests as $req) {
            if (is_array($req) && in_array(strtoupper(trim((string) ($req['status'] ?? ''))), ['SIGNED', 'APPROVED', 'COMPLETED'], true)) {
                $isSigned = true;
                break;
            }
        }
        if (! $isSigned && strcasecmp($docStatus, 'Completed') === 0) {
            $isSigned = true;
        }

        $signedPath = $this->storeFetchedFile($documentId, 'DOCUMENT', $dir.'/signed.pdf');
        $auditPath = $this->storeFetchedFile($documentId, 'AUDIT_TRAIL', $dir.'/audit-trail.pdf');

        if (! $signedPath && ! empty($data['file'])) {
            $signedPath = $this->storeRemoteUrl((string) $data['file'], $dir.'/signed.pdf');
        }
        if (! $auditPath && ! empty($data['auditTrail'])) {
            $auditPath = $this->storeRemoteUrl((string) $data['auditTrail'], $dir.'/audit-trail.pdf');
        }

        $statusToSet = $signature->signature_status;
        if ($isSigned && in_array($signature->signature_status, [DoctorLeegalitySignature::STATUS_SENT, DoctorLeegalitySignature::STATUS_FAILED], true)) {
            $statusToSet = DoctorLeegalitySignature::STATUS_SIGNED;
        }

        $signature->fill([
            'signed_document' => $signedPath ?: $signature->signed_document,
            'audit_trail' => $auditPath ?: $signature->audit_trail,
            'signed_at' => $isSigned ? ($signature->signed_at ?: now()) : $signature->signed_at,
            'document_status' => $docStatus ?: $signature->document_status,
            'signature_status' => $statusToSet,
        ]);
        $signature->save();

        if ($isSigned) {
            $this->assignPartnerAndAgreementIds($signature);
        }

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
            Log::warning('Leegality fetchDocument failed', [
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

    /**
     * @return array<string, mixed>
     */
    private function buildFilePayload(DoctorRequest $doctor, string $irn): array
    {
        $name = preg_replace('/[^A-Za-z0-9_\-\.]+/', '_', (string) config('leegality.document_name_prefix')).'_'.$doctor->id;
        $payload = ['name' => $name];

        if (config('leegality.template_only')) {
            return $payload;
        }

        // Prefer configured static PDF if set; otherwise generate pre-filled agreement from HTML template.
        $configured = trim((string) config('leegality.agreement_pdf_path'));
        $absolute = null;
        if ($configured !== '') {
            if (is_file($configured)) {
                $absolute = $configured;
            } elseif (Storage::disk('local')->exists($configured)) {
                $absolute = Storage::disk('local')->path($configured);
            } elseif (is_file(storage_path('app/'.$configured))) {
                $absolute = storage_path('app/'.$configured);
            }
        }

        if ($absolute && is_readable($absolute)) {
            $payload['file'] = base64_encode((string) file_get_contents($absolute));

            return $payload;
        }

        $payload['file'] = base64_encode($this->agreementDocument->pdfBinary($doctor, $irn));

        return $payload;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function assertValidMac(array $payload): void
    {
        $salt = trim((string) config('leegality.private_salt'));
        $mac = (string) ($payload['mac'] ?? '');
        $documentId = (string) ($payload['documentId'] ?? '');
        if ($salt === '' || $mac === '' || $documentId === '') {
            throw new RuntimeException('Leegality webhook MAC verification failed (missing salt/mac/documentId).');
        }

        $expected = hash_hmac('sha1', $documentId, $salt);
        if (! hash_equals(strtolower($expected), strtolower($mac))) {
            throw new RuntimeException('Leegality webhook MAC verification failed.');
        }
    }
}
