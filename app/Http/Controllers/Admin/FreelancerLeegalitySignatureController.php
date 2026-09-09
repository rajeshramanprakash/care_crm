<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FreelancerLeegalitySignature;
use App\Models\JobRequest;
use App\Services\Leegality\FreelancerLeegalitySignatureService;
use App\Services\Leegality\FreelancerServiceAgreementDocument;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FreelancerLeegalitySignatureController extends Controller
{
    public function previewAgreement(Request $request, JobRequest $job_request, FreelancerServiceAgreementDocument $document)
    {
        $format = strtolower((string) $request->query('format', 'pdf'));
        $irn = 'FRL-'.$job_request->id.'-PREVIEW';

        if ($format === 'html') {
            return response($document->renderHtml($job_request, $irn))
                ->header('Content-Type', 'text/html; charset=UTF-8');
        }

        $filename = 'freelancer-'.$job_request->id.'-service-agreement.pdf';

        return $document->makePdf($job_request, $irn)->stream($filename);
    }

    public function send(JobRequest $job_request, FreelancerLeegalitySignatureService $service): JsonResponse
    {
        try {
            $signature = $service->sendForSignature($job_request);
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
            'html' => view('admin.jobproc.partials.leegality_agreement_panel', [
                'jobRequest' => $job_request->fresh(),
                'leegalitySignature' => $signature,
            ])->render(),
        ]);
    }

    public function refresh(JobRequest $job_request, FreelancerLeegalitySignatureService $service): JsonResponse
    {
        $signature = $service->latestForFreelancer($job_request);
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
            'html' => view('admin.jobproc.partials.leegality_agreement_panel', [
                'jobRequest' => $job_request->fresh(),
                'leegalitySignature' => $signature,
            ])->render(),
        ]);
    }

    public function addMySignature(JobRequest $job_request, FreelancerLeegalitySignatureService $service): JsonResponse
    {
        try {
            $signature = $service->addAuthorisedSignature($job_request);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Authorised Signatory signature added & final executed PDF emailed to freelancer.',
            'signature' => $this->formatSignature($signature),
            'html' => view('admin.jobproc.partials.leegality_agreement_panel', [
                'jobRequest' => $job_request->fresh(),
                'leegalitySignature' => $signature,
            ])->render(),
        ]);
    }

    public function testAddMySignature(JobRequest $job_request, FreelancerLeegalitySignatureService $service)
    {
        try {
            $signature = $service->addAuthorisedSignature($job_request);
            $path = $signature->signed_document;
            if ($path && Storage::disk('public')->exists($path)) {
                return response()->file(Storage::disk('public')->path($path), [
                    'Content-Type' => 'application/pdf',
                    'Content-Disposition' => 'inline; filename="freelancer-'.$job_request->id.'-final-dual-signed.pdf"',
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Authorised signature processed.',
                'signature' => $signature,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ], 422);
        }
    }

    public function viewSigned(JobRequest $job_request, FreelancerLeegalitySignature $signature)
    {
        $this->assertOwns($job_request, $signature);
        $path = $signature->signed_document;
        if (! $path || ! Storage::disk('public')->exists($path)) {
            abort(404, 'Signed document not available yet.');
        }

        return response()->file(Storage::disk('public')->path($path), [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="freelancer-'.$job_request->id.'-signed-agreement.pdf"',
        ]);
    }

    public function downloadSigned(JobRequest $job_request, FreelancerLeegalitySignature $signature): StreamedResponse
    {
        $this->assertOwns($job_request, $signature);
        $path = $signature->signed_document;
        if (! $path || ! Storage::disk('public')->exists($path)) {
            abort(404, 'Signed document not available yet.');
        }

        return Storage::disk('public')->download($path, 'freelancer-'.$job_request->id.'-signed-agreement.pdf');
    }

    public function downloadAudit(JobRequest $job_request, FreelancerLeegalitySignature $signature): StreamedResponse
    {
        $this->assertOwns($job_request, $signature);
        $path = $signature->audit_trail;
        if (! $path || ! Storage::disk('public')->exists($path)) {
            abort(404, 'Audit trail not available yet.');
        }

        return Storage::disk('public')->download($path, 'freelancer-'.$job_request->id.'-audit-trail.pdf');
    }

    private function assertOwns(JobRequest $freelancer, FreelancerLeegalitySignature $signature): void
    {
        if ((int) $signature->job_request_id !== (int) $freelancer->id) {
            abort(404);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function formatSignature(FreelancerLeegalitySignature $signature): array
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
