<?php

namespace App\Http\Controllers;

use App\Services\TataService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TataController extends Controller
{
    protected $tataService;

    public function __construct(TataService $tataService)
    {
        $this->tataService = $tataService;
    }

    public function handleCallWebhook(Request $request)
    {
        Log::info('Tata webhook received', $request->all());
        
        try {
            // Core fields only — do not use $request->validate() as the sole payload:
            // Laravel would drop Tata extras (call_flow, answered_agent, etc.) needed in raw_data.
            $request->validate([
                'uuid' => 'required|string',
                'call_to_number' => 'required|string',
                'start_stamp' => 'required|string',
                'call_status' => 'required|string',
                'call_id' => 'required|string',
                'caller_id_number' => 'nullable|string',
                'answer_stamp' => 'nullable|string',
                'end_stamp' => 'nullable|string',
                'hangup_cause' => 'nullable|string',
                'billsec' => 'nullable|string',
                'direction' => 'nullable|string',
                'duration' => 'nullable|string',
                'recording_url' => 'nullable|string',
                'missed_agent' => 'nullable|array',
                'call_flow' => 'nullable|array',
            ]);

            $data = $request->except(['_token']);

            if (empty($data['caller_id_number']) && ! empty($data['call_to_number'])) {
                $data['caller_id_number'] = (string) $data['call_to_number'];
            }
            if (empty($data['caller_id_number'])) {
                throw new \InvalidArgumentException('Missing caller_id_number (and no call_to_number fallback)');
            }

            $data['end_stamp'] = $data['end_stamp'] ?? $data['start_stamp'];
            $data['hangup_cause'] = $data['hangup_cause'] ?? '';
            $data['billsec'] = (string) ($data['billsec'] ?? '0');
            $data['duration'] = (string) ($data['duration'] ?? $data['billsec']);

            $dir = strtolower(trim((string) ($data['direction'] ?? 'inbound')));
            if (in_array($dir, ['click-to-call', 'click_to_call'], true)) {
                $dir = 'clicktocall';
            }
            if ($dir === '') {
                $dir = 'inbound';
            }
            $data['direction'] = $dir;

            $lead = $this->tataService->handleCallData($data);

            return response()->json([
                'status' => 'success',
                'message' => 'Call data processed successfully',
                'lead_id' => $lead ? $lead->id : null
            ]);

        } catch (\Exception $e) {
            Log::error('Tata webhook error: ' . $e->getMessage(), [
                'call_id' => $request->input('call_id'),
                'uuid' => $request->input('uuid'),
            ]);
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to process call data'
            ], 500);
        }
    }
}
