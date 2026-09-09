<?php

namespace App\Http\Controllers\OperationManager;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\DutyLogs;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class DutyLogController extends Controller
{
    public function getDutyLogsForApp(Request $request)
    {
        try {
            $currentUser = auth()->user();

            \Illuminate\Support\Facades\Log::info("DutyLogs API Request - Current User ID: " . $currentUser->id);

            // First, let's test if the basic model works
            $totalLogs = DutyLogs::count();
            \Illuminate\Support\Facades\Log::info("Total duty logs in system: " . $totalLogs);

            // Check if table exists
            $tableExists = Schema::hasTable('duty_logs');
            \Illuminate\Support\Facades\Log::info("Duty logs table exists: " . ($tableExists ? 'Yes' : 'No'));

            // First, let's get all logs to test if the model works
            $allLogs = DutyLogs::orderBy('created_at', 'desc')->limit(10)->get();
            \Illuminate\Support\Facades\Log::info("All duty logs found: " . $allLogs->count());

            // Get duty logs for the operation manager's team
            $dutyLogs = DutyLogs::with('user')
                ->whereHas('user', function($query) use ($currentUser) {
                    $query->where('parent_id', $currentUser->id);
                })
                ->orderBy('created_at', 'desc')
                ->get();

            \Illuminate\Support\Facades\Log::info("DutyLogs Found for team: " . $dutyLogs->count());

            return response()->json([
                'success' => true,
                'data' => $dutyLogs,
                'debug' => [
                    'current_user_id' => $currentUser->id,
                    'total_logs' => $dutyLogs->count(),
                    'user_ids' => $dutyLogs->pluck('user_id')->toArray(),
                    'table_exists' => $tableExists,
                    'total_logs_in_system' => $totalLogs,
                    'all_logs_count' => $allLogs->count()
                ]
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("DutyLogs API Error: " . $e->getMessage());
            \Illuminate\Support\Facades\Log::error("DutyLogs API Error Stack: " . $e->getTraceAsString());

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch duty logs',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function testBasicModel()
    {
        try {
            // Test basic model functionality
            $count = DutyLogs::count();
            $tableExists = Schema::hasTable('duty_logs');

            return response()->json([
                'success' => true,
                'count' => $count,
                'table_exists' => $tableExists,
                'model_class' => get_class(new DutyLogs())
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    }
}
