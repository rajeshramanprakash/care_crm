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
        
        $signature = DoctorLeegalitySignature::create([
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

        if ($signUrl) {
            try {
                \Illuminate\Support\Facades\Mail::to($email)->send(new \App\Mail\DoctorLeegalitySignatureMail($doctor, $signUrl));
            } catch (\Throwable $e) {
                Log::warning('Doctor Leegality invite email failed', [
                    'doctor_request_id' => $doctor->id,
                    'email' => $email,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $signature;
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

        $canAddAuth = $signature->isSignedLike()
            || strcasecmp((string) $signature->document_status, 'Completed') === 0
            || in_array((string) $signature->signer_action, ['AUTH_SIGN_SENT', 'AUTH_SIGN_PENDING', 'SIGNED', 'BOTH_SIGNED'], true)
            || ($signature->signed_document && Storage::disk('public')->exists($signature->signed_document));

        if (! $canAddAuth) {
            throw new RuntimeException('Doctor must finish signing before Add My Signature.');
        }

        $documentId = (string) $signature->leegality_document_id;
        $partiallySignedPdfBase64 = $this->resolveSignedPdfBase64($signature, $documentId);
        if (! $partiallySignedPdfBase64) {
            throw new RuntimeException('Signed PDF content missing in Leegality response. Ensure Doctor has completed signature.');
        }

        $authName = (string) config('leegality.authorised_signatory_name', 'Carelix Authorised Signatory');
        $authEmail = (string) config('leegality.authorised_signatory_email', 'legal@carelixhealthcare.com');
        $authPhone = preg_replace('/\D+/', '', (string) config('leegality.authorised_signatory_phone', '7666426664')) ?: null;
        $signerId = trim((string) config('leegality.automated_signer_id'));
        $passkey = trim((string) config('leegality.automated_signer_passkey'));

        if ($signerId === '' || $passkey === '') {
            throw new RuntimeException('Set LEEGALITY_AUTOMATED_SIGNER_ID and LEEGALITY_AUTOMATED_SIGNER_PASSKEY in .env for Virtual Signature auto-sign.');
        }

        $irn = 'AUTH-CRLX-DR-'.$doctor->id.'-'.time();
        $invitee = [
            'name' => $authName,
            'email' => $authEmail,
            'phone' => $authPhone,
            'emailNotification' => false,
            'phoneNotification' => false,
            // Documented V3/V4 automated Virtual Sign fields
            'enableAutomatedSign' => true,
            'automatedSignConfig' => [
                'automatedSignProfile' => $signerId,
                'automatedSignPassword' => $passkey,
                'profileId' => $signerId,
                'password' => $passkey,
                'id' => $signerId,
                'passkey' => $passkey,
            ],
            // Legacy AUTOMATED_SIGN shape (kept for older workflow runtimes)
            'signatures' => [[
                'type' => 'AUTOMATED_SIGN',
                'config' => [
                    'id' => $signerId,
                    'passkey' => $passkey,
                    'password' => $passkey,
                ],
            ]],
            'appearances' => [[
                'page' => 'L',
                'x1' => (int) config('leegality.appearance_x1', 40),
                'y1' => (int) config('leegality.appearance_y1', 40),
                'x2' => (int) config('leegality.appearance_x2', 200),
                'y2' => (int) config('leegality.appearance_y2', 100),
            ]],
        ];
        if (empty($invitee['phone'])) {
            unset($invitee['phone']);
        }

        $profileId = trim((string) config('leegality.auth_profile_id'))
            ?: trim((string) config('leegality.profile_id'));

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

        $response = $this->client->createSignRequest($payload);
        if ((int) ($response['status'] ?? 0) !== 1) {
            throw new RuntimeException($this->client->firstMessage($response) ?: 'Authorised signature request failed on Leegality.');
        }

        $data = is_array($response['data'] ?? null) ? $response['data'] : [];
        $finalDocId = (string) ($data['documentId'] ?? $documentId);
        $signUrl = $this->extractSignUrl($data);

        // Wait for Leegality to apply Virtual Signature (Automated Sign) before saving PDF for View.
        $completion = $this->waitForDocumentCompletion($finalDocId, 8, 4);
        $isCompleted = (bool) ($completion['completed'] ?? false);
        $docStatus = (string) ($completion['status'] ?? 'Sent');

        $dir = 'leegality/signatures/'.$doctor->id.'/'.$signature->id;
        Storage::disk('public')->makeDirectory($dir);
        $doctorOnlyPath = $dir.'/signed.pdf';
        $finalPath = $dir.'/final-executed.pdf';

        // Never replace the doctor-signed PDF with an incomplete auth document.
        $keptDoctorPdf = $signature->signed_document;
        if ($keptDoctorPdf && Storage::disk('public')->exists($keptDoctorPdf) && ! str_ends_with((string) $keptDoctorPdf, '/final-executed.pdf')) {
            // keep as-is
        } elseif (Storage::disk('public')->exists($doctorOnlyPath)) {
            $keptDoctorPdf = $doctorOnlyPath;
        }

        $finalFetched = null;
        if ($isCompleted) {
            $finalFetched = $this->storeFetchedFile($finalDocId, 'DOCUMENT', $finalPath);
            if (! $finalFetched) {
                $details = $this->client->documentDetails($finalDocId, true, false);
                $fileUrl = $details['data']['file'] ?? null;
                if (is_string($fileUrl) && $fileUrl !== '') {
                    $finalFetched = $this->storeRemoteUrl($fileUrl, $finalPath);
                }
            }
            $auditPath = $this->storeFetchedFile($finalDocId, 'AUDIT_TRAIL', $dir.'/audit-trail.pdf');
        }

        $signature->update([
            'leegality_document_id' => $finalDocId,
            'signed_document' => $finalFetched ?: $keptDoctorPdf ?: $signature->signed_document,
            'audit_trail' => ($isCompleted && ! empty($auditPath)) ? $auditPath : $signature->audit_trail,
            'sign_url' => $signUrl ?: $signature->sign_url,
            'signature_status' => $isCompleted ? DoctorLeegalitySignature::STATUS_COMPLETED : DoctorLeegalitySignature::STATUS_SIGNED,
            'signer_action' => $isCompleted ? 'BOTH_SIGNED' : 'AUTH_SIGN_PENDING',
            'document_status' => $docStatus,
            'signed_at' => $signature->signed_at ?: now(),
            'create_response' => $response,
            'error_message' => $isCompleted ? null : 'Authorised Virtual Signature not applied yet by Leegality Automated Sign.',
        ]);

        if (! $isCompleted) {
            $hint = 'Leegality did not auto-apply your Virtual Signature. '
                .'In Leegality Dashboard open workflow '.$profileId
                .' → invitee Signature Type → enable Automated Sign'
                .' → choose profile '.$signerId
                .' → enter passkey, Save & Publish. Then click Add My Signature again.';
            if ($signUrl) {
                $hint .= ' Temporary sign link: '.$signUrl;
            }
            throw new RuntimeException($hint);
        }

        $email = trim((string) ($signature->signer_email ?: $doctor->email));
        if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) && $signature->fresh()->signed_document) {
            try {
                \Illuminate\Support\Facades\Mail::to($email)->send(
                    new \App\Mail\FinalSignedAgreementMail(
                        $doctor->name ?: 'Doctor',
                        'doctor',
                        $signature->fresh()->signed_document
                    )
                );
            } catch (\Throwable $e) {
                Log::warning('Sending final dual-signed agreement email failed', ['error' => $e->getMessage()]);
            }
        }

        return $signature->fresh();
    }

    private function resolveSignedPdfBase64(DoctorLeegalitySignature $signature, string $documentId): ?string
    {
        $candidates = array_values(array_filter([
            $signature->signed_document,
            'leegality/signatures/'.$signature->doctor_request_id.'/'.$signature->id.'/signed.pdf',
        ]));

        foreach ($candidates as $path) {
            if (is_string($path) && $path !== '' && ! str_ends_with($path, '/final-executed.pdf') && Storage::disk('public')->exists($path)) {
                return base64_encode((string) Storage::disk('public')->get($path));
            }
        }

        // Fall back to whatever local PDF we have (including previous final if doctor copy missing).
        if ($signature->signed_document && Storage::disk('public')->exists($signature->signed_document)) {
            return base64_encode((string) Storage::disk('public')->get($signature->signed_document));
        }

        try {
            $fetchRes = $this->client->fetchDocument($documentId, 'DOCUMENT');
            $fileUrl = $fetchRes['data']['file'] ?? null;
            if (is_string($fileUrl) && $fileUrl !== '') {
                $bin = Http::timeout(60)->get($fileUrl);
                if ($bin->successful() && $bin->body() !== '') {
                    return base64_encode($bin->body());
                }
            }
        } catch (\Throwable $e) {
            Log::warning('Could not fetch doctor signed PDF for auth sign', ['error' => $e->getMessage()]);
        }

        try {
            $details = $this->client->documentDetails($documentId, true, false);
            $fileUrl = $details['data']['file'] ?? null;
            if (is_string($fileUrl) && $fileUrl !== '') {
                if (str_starts_with($fileUrl, 'http://') || str_starts_with($fileUrl, 'https://')) {
                    $bin = Http::timeout(60)->get($fileUrl);
                    if ($bin->successful() && $bin->body() !== '') {
                        return base64_encode($bin->body());
                    }
                } else {
                    return $fileUrl;
                }
            }
        } catch (\Throwable $e) {
            Log::warning('documentDetails fetch for auth sign failed', ['error' => $e->getMessage()]);
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function extractSignUrl(array $data): ?string
    {
        $invitations = is_array($data['invitations'] ?? null)
            ? $data['invitations']
            : (is_array($data['invitees'] ?? null) ? $data['invitees'] : []);
        if ($invitations === []) {
            return null;
        }
        $first = $invitations[0];
        if (! is_array($first)) {
            return null;
        }

        $url = $first['signUrl'] ?? $first['invitationUrl'] ?? null;

        return is_string($url) && $url !== '' ? $url : null;
    }

    /**
     * @return array{completed: bool, status: string}
     */
    private function waitForDocumentCompletion(string $documentId, int $attempts = 12, int $sleepSeconds = 5): array
    {
        $status = 'Sent';
        for ($i = 0; $i < $attempts; $i++) {
            if ($i > 0) {
                sleep($sleepSeconds);
            }
            try {
                $details = $this->client->documentDetails($documentId, false, false);
                $data = is_array($details['data'] ?? null) ? $details['data'] : [];
                $status = (string) (
                    $data['document']['status']
                    ?? $data['documentStatus']
                    ?? $status
                );

                $signed = false;
                $invitations = is_array($data['invitations'] ?? null) ? $data['invitations'] : [];
                foreach ($invitations as $inv) {
                    if (! is_array($inv)) {
                        continue;
                    }
                    $invStatus = is_array($inv['invitationStatus'] ?? null) ? $inv['invitationStatus'] : [];
                    if (! empty($invStatus['signed']) || ! empty($invStatus['approved'])) {
                        $signed = true;
                        break;
                    }
                }

                if (strcasecmp($status, 'Completed') === 0 || $signed) {
                    // Small grace so Leegality finishes writing the signed file.
                    sleep(2);

                    return ['completed' => true, 'status' => strcasecmp($status, 'Completed') === 0 ? 'Completed' : $status];
                }
            } catch (\Throwable $e) {
                Log::warning('Leegality completion poll failed', [
                    'documentId' => $documentId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return ['completed' => false, 'status' => $status];
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

        $docStatus = (string) (
            $data['document']['status']
            ?? $data['documentStatus']
            ?? $signature->document_status
        );
        $requests = is_array($data['requests'] ?? null) ? $data['requests'] : [];
        $invitations = is_array($data['invitations'] ?? null) ? $data['invitations'] : [];
        $isSigned = false;
        $allInviteesSigned = $invitations !== [];

        foreach ($requests as $req) {
            if (is_array($req) && in_array(strtoupper(trim((string) ($req['status'] ?? ''))), ['SIGNED', 'APPROVED', 'COMPLETED'], true)) {
                $isSigned = true;
                break;
            }
        }
        foreach ($invitations as $inv) {
            if (! is_array($inv)) {
                $allInviteesSigned = false;
                continue;
            }
            $invStatus = is_array($inv['invitationStatus'] ?? null) ? $inv['invitationStatus'] : [];
            $thisInviteeSigned = ! empty($invStatus['signed']) || ! empty($invStatus['approved']);
            if ($thisInviteeSigned) {
                $isSigned = true;
            } else {
                $allInviteesSigned = false;
            }
        }
        if (! $isSigned && strcasecmp($docStatus, 'Completed') === 0) {
            $isSigned = true;
            $allInviteesSigned = true;
        }

        $isAuthFlow = str_starts_with((string) $signature->irn, 'AUTH-')
            || in_array((string) $signature->signer_action, ['AUTH_SIGN_SENT', 'AUTH_SIGN_PENDING', 'BOTH_SIGNED'], true);
        $targetPdf = ($isAuthFlow && (strcasecmp($docStatus, 'Completed') === 0 || $allInviteesSigned))
            ? $dir.'/final-executed.pdf'
            : $dir.'/signed.pdf';

        $signedPath = $this->storeFetchedFile($documentId, 'DOCUMENT', $targetPdf);
        $auditPath = $this->storeFetchedFile($documentId, 'AUDIT_TRAIL', $dir.'/audit-trail.pdf');

        if (! $signedPath && ! empty($data['file'])) {
            $signedPath = $this->storeRemoteUrl((string) $data['file'], $targetPdf);
        }
        if (! $auditPath && ! empty($data['auditTrail'])) {
            $auditPath = $this->storeRemoteUrl((string) $data['auditTrail'], $dir.'/audit-trail.pdf');
        }

        $statusToSet = $signature->signature_status;
        if (strcasecmp($docStatus, 'Completed') === 0 || $allInviteesSigned) {
            $statusToSet = $isAuthFlow
                ? DoctorLeegalitySignature::STATUS_COMPLETED
                : DoctorLeegalitySignature::STATUS_SIGNED;
        } elseif ($isSigned && in_array($signature->signature_status, [DoctorLeegalitySignature::STATUS_SENT, DoctorLeegalitySignature::STATUS_FAILED], true)) {
            $statusToSet = DoctorLeegalitySignature::STATUS_SIGNED;
        }

        $signature->fill([
            'signed_document' => $signedPath ?: $signature->signed_document,
            'audit_trail' => $auditPath ?: $signature->audit_trail,
            'signed_at' => $isSigned ? ($signature->signed_at ?: now()) : $signature->signed_at,
            'document_status' => $docStatus ?: $signature->document_status,
            'signature_status' => $statusToSet,
            'signer_action' => (strcasecmp($docStatus, 'Completed') === 0 || $allInviteesSigned)
                ? ($isAuthFlow ? 'BOTH_SIGNED' : ($signature->signer_action ?: 'SIGNED'))
                : $signature->signer_action,
            'error_message' => (strcasecmp($docStatus, 'Completed') === 0 || $allInviteesSigned) ? null : $signature->error_message,
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
