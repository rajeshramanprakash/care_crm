<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Vendor;
use App\Models\VendorLeegalitySignature;
use App\Services\Leegality\VendorLeegalitySignatureService;
use App\Services\Leegality\VendorServiceAgreementDocument;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class VendorLeegalitySignatureController extends Controller
{
    public function previewAgreement(Request $request, Vendor $vendor, VendorServiceAgreementDocument $document)
    {
        $format = strtolower((string) $request->query('format', 'pdf'));
        $irn = 'VEN-'.$vendor->id.'-PREVIEW';

        if ($format === 'html') {
            return response($document->renderHtml($vendor, $irn))
                ->header('Content-Type', 'text/html; charset=UTF-8');
        }

        $filename = 'vendor-'.$vendor->id.'-service-agreement.pdf';

        return $document->makePdf($vendor, $irn)->stream($filename);
    }

    public function send(Vendor $vendor, VendorLeegalitySignatureService $service): JsonResponse
    {
        try {
            $signature = $service->sendForSignature($vendor);
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
            'html' => view('admin.vendors.partials.leegality_agreement_panel', [
                'vendor' => $vendor->fresh(),
                'leegalitySignature' => $signature,
            ])->render(),
        ]);
    }

    public function refresh(Vendor $vendor, VendorLeegalitySignatureService $service): JsonResponse
    {
        $signature = $service->latestForVendor($vendor);
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
            'html' => view('admin.vendors.partials.leegality_agreement_panel', [
                'vendor' => $vendor->fresh(),
                'leegalitySignature' => $signature,
            ])->render(),
        ]);
    }

    public function addMySignature(Vendor $vendor, VendorLeegalitySignatureService $service): JsonResponse
    {
        try {
            $signature = $service->addAuthorisedSignature($vendor);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Authorised Signatory signature added & final executed PDF emailed to vendor.',
            'signature' => $this->formatSignature($signature),
            'html' => view('admin.vendors.partials.leegality_agreement_panel', [
                'vendor' => $vendor->fresh(),
                'leegalitySignature' => $signature,
            ])->render(),
        ]);
    }

    public function testAddMySignature(Vendor $vendor, VendorLeegalitySignatureService $service)
    {
        try {
            $signature = $service->addAuthorisedSignature($vendor);
            $path = $signature->signed_document;
            if ($path && Storage::disk('public')->exists($path)) {
                return response()->file(Storage::disk('public')->path($path), [
                    'Content-Type' => 'application/pdf',
                    'Content-Disposition' => 'inline; filename="vendor-'.$vendor->id.'-final-dual-signed.pdf"',
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

    public function viewSigned(Vendor $vendor, VendorLeegalitySignature $signature)
    {
        $this->assertOwns($vendor, $signature);
        $path = $signature->signed_document;
        if (! $path || ! Storage::disk('public')->exists($path)) {
            abort(404, 'Signed document not available yet.');
        }

        return response()->file(Storage::disk('public')->path($path), [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="vendor-'.$vendor->id.'-signed-agreement.pdf"',
        ]);
    }

    public function downloadSigned(Vendor $vendor, VendorLeegalitySignature $signature): StreamedResponse
    {
        $this->assertOwns($vendor, $signature);
        $path = $signature->signed_document;
        if (! $path || ! Storage::disk('public')->exists($path)) {
            abort(404, 'Signed document not available yet.');
        }

        return Storage::disk('public')->download($path, 'vendor-'.$vendor->id.'-signed-agreement.pdf');
    }

    public function downloadAudit(Vendor $vendor, VendorLeegalitySignature $signature): StreamedResponse
    {
        $this->assertOwns($vendor, $signature);
        $path = $signature->audit_trail;
        if (! $path || ! Storage::disk('public')->exists($path)) {
            abort(404, 'Audit trail not available yet.');
        }

        return Storage::disk('public')->download($path, 'vendor-'.$vendor->id.'-audit-trail.pdf');
    }

    private function assertOwns(Vendor $vendor, VendorLeegalitySignature $signature): void
    {
        if ((int) $signature->vendor_id !== (int) $vendor->id) {
            abort(404);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function formatSignature(VendorLeegalitySignature $signature): array
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
