<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Leegality\DoctorLeegalitySignatureService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LeegalityWebhookController extends Controller
{
    public function __invoke(
        Request $request,
        DoctorLeegalitySignatureService $doctorService,
        \App\Services\Leegality\FreelancerLeegalitySignatureService $freelancerService,
        \App\Services\Leegality\VendorLeegalitySignatureService $vendorService
    ): JsonResponse {
        $payload = $request->all();
        Log::info('Leegality webhook received', [
            'documentId' => $payload['documentId'] ?? null,
            'documentStatus' => $payload['documentStatus'] ?? null,
            'action' => data_get($payload, 'request.action'),
        ]);

        try {
            $signature = $doctorService->handleWebhook($payload);
            if (! $signature) {
                $signature = $freelancerService->handleWebhook($payload);
            }
            if (! $signature) {
                $signature = $vendorService->handleWebhook($payload);
            }
        } catch (\Throwable $e) {
            Log::error('Leegality webhook processing failed', [
                'error' => $e->getMessage(),
                'payload' => $payload,
            ]);

            // Still ACK with 200 when MAC fails only if verify is off; otherwise 400.
            if (str_contains(strtolower($e->getMessage()), 'mac')) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
            }

            // Avoid endless retries for unknown processing bugs: acknowledge receipt.
            return response()->json(['success' => true, 'message' => 'Received with processing error logged'], 200);
        }

        return response()->json([
            'success' => true,
            'message' => $signature ? 'Webhook processed' : 'Webhook accepted (no matching signature row)',
        ], 200);
    }
}
