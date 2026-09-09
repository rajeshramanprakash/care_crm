<?php

namespace App\Http\Controllers\OperationManager;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\BreakLog;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class BreakLogController extends Controller
{
    public function getBreakLogsForApp(Request $request)
    {
        try {
            $currentUser = auth()->user();

            \Illuminate\Support\Facades\Log::info("BreakLogs API Request - Current User ID: " . $currentUser->id);

            // First, let's test if the basic model works
            $totalLogs = BreakLog::count();
            \Illuminate\Support\Facades\Log::info("Total break logs in system: " . $totalLogs);

            // Check if table exists
            $tableExists = Schema::hasTable('break_logs');
            \Illuminate\Support\Facades\Log::info("Break logs table exists: " . ($tableExists ? 'Yes' : 'No'));

            // First, let's get all logs to test if the model works
            $allLogs = BreakLog::orderBy('created_at', 'desc')->limit(10)->get();
            \Illuminate\Support\Facades\Log::info("All break logs found: " . $allLogs->count());

            // Get break logs for the operation manager's team
            $breakLogs = BreakLog::with('user')
                ->whereHas('user', function($query) use ($currentUser) {
                    $query->where('parent_id', $currentUser->id);
                })
                ->orderBy('created_at', 'desc')
                ->get();

            \Illuminate\Support\Facades\Log::info("BreakLogs Found for team: " . $breakLogs->count());

            return response()->json([
                'success' => true,
                'data' => $breakLogs,
                'debug' => [
                    'current_user_id' => $currentUser->id,
                    'total_logs' => $breakLogs->count(),
                    'user_ids' => $breakLogs->pluck('user_id')->toArray(),
                    'table_exists' => $tableExists,
                    'total_logs_in_system' => $totalLogs,
                    'all_logs_count' => $allLogs->count()
                ]
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("BreakLogs API Error: " . $e->getMessage());
            \Illuminate\Support\Facades\Log::error("BreakLogs API Error Stack: " . $e->getTraceAsString());

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch break logs',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
