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
            // Validate the incoming data based on Tata's response format
            $validated = $request->validate([
                'uuid' => 'required|string',
                'call_to_number' => 'required|string',
                'caller_id_number' => 'required|string',
                'start_stamp' => 'required|string',
                'answer_stamp' => 'nullable|string',
                'end_stamp' => 'required|string',
                'hangup_cause' => 'required|string',
                'billsec' => 'required|string',
                'direction' => 'required|in:inbound,outbound',
                'duration' => 'required|string',
                'call_status' => 'required|string',
                'call_id' => 'required|string',
                'recording_url' => 'nullable|url',
                'missed_agent' => 'nullable|array',
                'call_flow' => 'nullable|array'
            ]);

            // Process the call data
            $lead = $this->tataService->handleCallData($validated);

            return response()->json([
                'status' => 'success',
                'message' => 'Call data processed successfully',
                'lead_id' => $lead ? $lead->id : null
            ]);

        } catch (\Exception $e) {
            Log::error('Tata webhook error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to process call data'
            ], 500);
        }
    }
}
