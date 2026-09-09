<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\BreakLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BreakLogController extends Controller
{
    public function index()
    {
        // Get the current manager
        $manager = Auth::user();

        \Illuminate\Support\Facades\Log::info("Manager Break Logs - Manager ID: " . $manager->id);

        // For now, let's get all break logs to see if there are any
        $allBreakLogs = BreakLog::with('user:id,f_name,l_name,email')
            ->orderBy('created_at', 'desc')
            ->get();

        \Illuminate\Support\Facades\Log::info("Total break logs in system: " . $allBreakLogs->count());

        // Get all users under this manager (child users)
        $childUserIds = User::where('parent_id', $manager->id)->pluck('id')->toArray();

        \Illuminate\Support\Facades\Log::info("Child User IDs: " . implode(', ', $childUserIds));

        // If no child users, show all break logs for now
        if (empty($childUserIds)) {
            \Illuminate\Support\Facades\Log::info("No child users found for manager, showing all break logs");
            if (request()->expectsJson()) {
                return response()->json($allBreakLogs);
            }
            return view('manager.break_logs.index', compact('breakLogs'));
        }

        // Get break logs for all child users
        $breakLogs = BreakLog::with('user:id,f_name,l_name,email')
            ->whereIn('user_id', $childUserIds)
            ->orderBy('created_at', 'desc')
            ->get();

        \Illuminate\Support\Facades\Log::info("Found " . $breakLogs->count() . " break logs for child users");

        // Debug: Check if there are any break logs at all
        if ($allBreakLogs->count() > 0) {
            $sampleLog = $allBreakLogs->first();
            \Illuminate\Support\Facades\Log::info("Sample break log - ID: " . $sampleLog->id . ", User ID: " . $sampleLog->user_id . ", Status: " . $sampleLog->break_status);
        }

        // If API request, return JSON data
        if (request()->expectsJson()) {
            \Illuminate\Support\Facades\Log::info("Returning JSON response with " . $breakLogs->count() . " break logs");
            return response()->json($breakLogs);
        }

        return view('manager.break_logs.index', compact('breakLogs'));
    }

    public function userLogs($id)
    {
        // Get the current manager
        $manager = Auth::user();

        // Check if the user is under this manager's supervision
        $user = User::where('id', $id)
            ->where('parent_id', $manager->id)
            ->first();

        if (!$user) {
            if (request()->expectsJson()) {
                return response()->json(['error' => 'User not found or not under your supervision'], 404);
            }
            abort(404, 'User not found or not under your supervision');
        }

        // Get break logs for this specific user
        $breakLogs = BreakLog::where('user_id', $id)
            ->orderBy('created_at', 'desc')
            ->get();

        // If API request, return JSON data
        if (request()->expectsJson()) {
            return response()->json($breakLogs);
        }

        return view('manager.break_logs.user_logs', compact('user', 'breakLogs'));
    }

    public function test()
    {
        $manager = Auth::user();
        $totalBreakLogs = BreakLog::count();
        $managerChildUsers = User::where('parent_id', $manager->id)->count();
        $managerBreakLogs = BreakLog::whereIn('user_id', User::where('parent_id', $manager->id)->pluck('id'))->count();

        // If no break logs exist, create some sample data
        if ($totalBreakLogs == 0) {
            \Illuminate\Support\Facades\Log::info("No break logs found, creating sample data");

            // Get some users to create break logs for
            $users = User::limit(3)->get();

            foreach ($users as $user) {
                BreakLog::create([
                    'user_id' => $user->id,
                    'break_status' => 'offline',
                    'break_reason' => 'Lunch break',
                    'break_start_time' => now()->subHours(1),
                    'break_end_time' => null,
                    'created_at' => now()->subHours(1)
                ]);

                BreakLog::create([
                    'user_id' => $user->id,
                    'break_status' => 'online',
                    'break_reason' => 'Coffee break',
                    'break_start_time' => now()->subHours(3),
                    'break_end_time' => now()->subHours(2),
                    'created_at' => now()->subHours(3)
                ]);
            }

            $totalBreakLogs = BreakLog::count();
            \Illuminate\Support\Facades\Log::info("Created sample break logs. Total now: " . $totalBreakLogs);
        }

        return response()->json([
            'manager_id' => $manager->id,
            'total_break_logs' => $totalBreakLogs,
            'manager_child_users' => $managerChildUsers,
            'manager_break_logs' => $managerBreakLogs,
            'all_break_logs' => BreakLog::with('user')->get()
        ]);
    }

    public function createSampleData()
    {
        try {
            // Get some users to create break logs for
            $users = User::limit(5)->get();

            $createdCount = 0;
            foreach ($users as $user) {
                // Create offline break log
                BreakLog::create([
                    'user_id' => $user->id,
                    'break_status' => 'offline',
                    'break_reason' => 'Lunch break',
                    'break_start_time' => now()->subHours(1),
                    'break_end_time' => null,
                    'created_at' => now()->subHours(1)
                ]);
                $createdCount++;

                // Create online break log
                BreakLog::create([
                    'user_id' => $user->id,
                    'break_status' => 'online',
                    'break_reason' => 'Coffee break',
                    'break_start_time' => now()->subHours(3),
                    'break_end_time' => now()->subHours(2),
                    'created_at' => now()->subHours(3)
                ]);
                $createdCount++;

                // Create another online break log
                BreakLog::create([
                    'user_id' => $user->id,
                    'break_status' => 'online',
                    'break_reason' => 'Meeting',
                    'break_start_time' => now()->subHours(5),
                    'break_end_time' => now()->subHours(4),
                    'created_at' => now()->subHours(5)
                ]);
                $createdCount++;
            }

            return response()->json([
                'success' => true,
                'message' => "Created $createdCount sample break logs",
                'total_break_logs' => BreakLog::count()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error creating sample data: ' . $e->getMessage()
            ], 500);
        }
    }
}
