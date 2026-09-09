<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DoctorLeegalitySignature;
use App\Models\DoctorRequest;
use App\Services\Leegality\DoctorLeegalitySignatureService;
use App\Services\Leegality\DoctorServiceAgreementDocument;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DoctorLeegalitySignatureController extends Controller
{
    public function previewAgreement(Request $request, DoctorRequest $doctor_request, DoctorServiceAgreementDocument $document)
    {
        $format = strtolower((string) $request->query('format', 'pdf'));
        $irn = 'DR-'.$doctor_request->id.'-PREVIEW';

        if ($format === 'html') {
            return response($document->renderHtml($doctor_request, $irn))
                ->header('Content-Type', 'text/html; charset=UTF-8');
        }

        $filename = 'doctor-'.$doctor_request->id.'-service-agreement.pdf';

        return $document->makePdf($doctor_request, $irn)->stream($filename);
    }

    public function send(DoctorRequest $doctor_request, DoctorLeegalitySignatureService $service): JsonResponse
    {
        try {
            $signature = $service->sendForSignature($doctor_request);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Signature request sent to '.$signature->signer_email.'.',
            'signature' => $this->formatSignature($signature),
            'html' => view('admin.doctor_requests.partials.leegality_agreement_panel', [
                'doctor' => $doctor_request->fresh(),
                'leegalitySignature' => $signature,
            ])->render(),
        ]);
    }

    public function refresh(DoctorRequest $doctor_request, DoctorLeegalitySignatureService $service): JsonResponse
    {
        $signature = $service->latestForDoctor($doctor_request);
        if (! $signature || ! $signature->leegality_document_id) {
            return response()->json(['success' => false, 'message' => 'No signature request found.'], 404);
        }

        try {
            $signature = $service->downloadAndStoreArtifacts($signature);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Agreement files refreshed from Leegality.',
            'signature' => $this->formatSignature($signature),
            'html' => view('admin.doctor_requests.partials.leegality_agreement_panel', [
                'doctor' => $doctor_request->fresh(),
                'leegalitySignature' => $signature,
            ])->render(),
        ]);
    }

    public function addMySignature(DoctorRequest $doctor_request, DoctorLeegalitySignatureService $service): JsonResponse
    {
        try {
            $signature = $service->addAuthorisedSignature($doctor_request);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Authorised Signatory signature added & final executed PDF emailed to doctor.',
            'signature' => $this->formatSignature($signature),
            'html' => view('admin.doctor_requests.partials.leegality_agreement_panel', [
                'doctor' => $doctor_request->fresh(),
                'leegalitySignature' => $signature,
            ])->render(),
        ]);
    }



    public function viewSigned(DoctorRequest $doctor_request, DoctorLeegalitySignature $signature)
    {
        $this->assertOwns($doctor_request, $signature);
        $path = $signature->signed_document;
        if (! $path || ! Storage::disk('public')->exists($path)) {
            abort(404, 'Signed document not available yet.');
        }

        return response()->file(Storage::disk('public')->path($path), [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="doctor-'.$doctor_request->id.'-signed-agreement.pdf"',
        ]);
    }

    public function downloadSigned(DoctorRequest $doctor_request, DoctorLeegalitySignature $signature): StreamedResponse
    {
        $this->assertOwns($doctor_request, $signature);
        $path = $signature->signed_document;
        if (! $path || ! Storage::disk('public')->exists($path)) {
            abort(404, 'Signed document not available yet.');
        }

        return Storage::disk('public')->download($path, 'doctor-'.$doctor_request->id.'-signed-agreement.pdf');
    }

    public function downloadAudit(DoctorRequest $doctor_request, DoctorLeegalitySignature $signature): StreamedResponse
    {
        $this->assertOwns($doctor_request, $signature);
        $path = $signature->audit_trail;
        if (! $path || ! Storage::disk('public')->exists($path)) {
            abort(404, 'Audit trail not available yet.');
        }

        return Storage::disk('public')->download($path, 'doctor-'.$doctor_request->id.'-audit-trail.pdf');
    }

    private function assertOwns(DoctorRequest $doctor, DoctorLeegalitySignature $signature): void
    {
        if ((int) $signature->doctor_request_id !== (int) $doctor->id) {
            abort(404);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function formatSignature(DoctorLeegalitySignature $signature): array
    {
        return [
            'id' => $signature->id,
            'leegality_document_id' => $signature->leegality_document_id,
            'signature_status' => $signature->signature_status,
            'status_label' => $signature->statusLabel(),
            'sent_at' => optional($signature->sent_at)->format('d M Y'),
            'signed_at' => optional($signature->signed_at)->format('d M Y'),
            'has_signed_document' => (bool) $signature->signed_document,
            'has_audit_trail' => (bool) $signature->audit_trail,
        ];
    }
}
