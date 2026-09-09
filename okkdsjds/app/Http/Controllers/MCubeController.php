<?php

namespace App\Http\Controllers;

use App\Services\MCubeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MCubeController extends Controller
{
    protected $mCubeService;

    public function __construct(MCubeService $mCubeService)
    {
        $this->mCubeService = $mCubeService;
    }

    public function handleCallWebhook(Request $request)
    {
        Log::info($request->all());
        try {
            // Validate the incoming data
            $validated = $request->validate([
                'starttime' => 'required|date',
                'callid' => 'required|string',
                'emp_phone' => 'required|string',
                'clicktocalldid' => 'required|string',
                'callto' => 'required|string',
                'dialstatus' => 'required|string',
                'filename' => 'required|url',
                'direction' => 'required|in:inbound,outbound',
                'endtime' => 'required|date',
                'disconnectedby' => 'required|string',
                'answeredtime' => 'required|string',
                'groupname' => 'required|string',
                'agentname' => 'required|string'
            ]);

            // Process the call data
            $lead = $this->mCubeService->handleCallData($validated);

            return response()->json([
                'status' => 'success',
                'message' => 'Call data processed successfully',
                'lead_id' => $lead->id
            ]);

        } catch (\Exception $e) {
            Log::error('MCube webhook error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to process call data'
            ], 500);
        }
    }
}
