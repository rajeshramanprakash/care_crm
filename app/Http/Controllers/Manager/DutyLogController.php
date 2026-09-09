<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\DutyLogs;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DutyLogController extends Controller
{
    public function index()
    {
        // Get the current manager
        $manager = Auth::user();

        \Illuminate\Support\Facades\Log::info("Manager Duty Logs - Manager ID: " . $manager->id);

        // For now, let's get all duty logs to see if there are any
        $allDutyLogs = DutyLogs::with('user:id,f_name,l_name,email')
            ->orderBy('created_at', 'desc')
            ->get();

        \Illuminate\Support\Facades\Log::info("Total duty logs in system: " . $allDutyLogs->count());

        // Get all users under this manager (child users)
        $childUserIds = User::where('parent_id', $manager->id)->pluck('id')->toArray();

        \Illuminate\Support\Facades\Log::info("Child User IDs: " . implode(', ', $childUserIds));

        // If no child users, show all duty logs for now
        if (empty($childUserIds)) {
            \Illuminate\Support\Facades\Log::info("No child users found for manager, showing all duty logs");
            if (request()->expectsJson()) {
                return response()->json($allDutyLogs);
            }
            return view('manager.duty_logs.index', compact('dutyLogs'));
        }

        // Get duty logs for all child users
        $dutyLogs = DutyLogs::with('user:id,f_name,l_name,email')
            ->whereIn('user_id', $childUserIds)
            ->orderBy('created_at', 'desc')
            ->get();

        \Illuminate\Support\Facades\Log::info("Found " . $dutyLogs->count() . " duty logs for child users");

        // Debug: Check if there are any duty logs at all
        if ($allDutyLogs->count() > 0) {
            $sampleLog = $allDutyLogs->first();
            \Illuminate\Support\Facades\Log::info("Sample duty log - ID: " . $sampleLog->id . ", User ID: " . $sampleLog->user_id . ", Status: " . $sampleLog->break_status);
        }

        // If API request, return JSON data
        if (request()->expectsJson()) {
            \Illuminate\Support\Facades\Log::info("Returning JSON response with " . $dutyLogs->count() . " duty logs");
            return response()->json($dutyLogs);
        }

        return view('manager.duty_logs.index', compact('dutyLogs'));
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

        // Get duty logs for this specific user
        $dutyLogs = DutyLogs::where('user_id', $id)
            ->orderBy('created_at', 'desc')
            ->get();

        // If API request, return JSON data
        if (request()->expectsJson()) {
            return response()->json($dutyLogs);
        }

        return view('manager.duty_logs.user_logs', compact('user', 'dutyLogs'));
    }

    public function test()
    {
        $manager = Auth::user();
        $totalDutyLogs = DutyLogs::count();
        $managerChildUsers = User::where('parent_id', $manager->id)->count();
        $managerDutyLogs = DutyLogs::whereIn('user_id', User::where('parent_id', $manager->id)->pluck('id'))->count();

        // If no duty logs exist, create some sample data
        if ($totalDutyLogs == 0) {
            \Illuminate\Support\Facades\Log::info("No duty logs found, creating sample data");

            // Get some users to create duty logs for
            $users = User::limit(3)->get();

            foreach ($users as $user) {
                DutyLogs::create([
                    'user_id' => $user->id,
                    'break_status' => 'active',
                    'break_reason' => 'Lunch break',
                    'break_start_time' => now()->subHours(1),
                    'break_end_time' => null,
                    'created_at' => now()->subHours(1)
                ]);

                DutyLogs::create([
                    'user_id' => $user->id,
                    'break_status' => 'inactive',
                    'break_reason' => 'Coffee break',
                    'break_start_time' => now()->subHours(3),
                    'break_end_time' => now()->subHours(2),
                    'created_at' => now()->subHours(3)
                ]);
            }

            $totalDutyLogs = DutyLogs::count();
            \Illuminate\Support\Facades\Log::info("Created sample duty logs. Total now: " . $totalDutyLogs);
        }

        return response()->json([
            'manager_id' => $manager->id,
            'total_duty_logs' => $totalDutyLogs,
            'manager_child_users' => $managerChildUsers,
            'manager_duty_logs' => $managerDutyLogs,
            'all_duty_logs' => DutyLogs::with('user')->get()
        ]);
    }

    public function createSampleData()
    {
        try {
            // Get some users to create duty logs for
            $users = User::limit(5)->get();

            $createdCount = 0;
            foreach ($users as $user) {
                // Create active duty log
                DutyLogs::create([
                    'user_id' => $user->id,
                    'break_status' => 'active',
                    'break_reason' => 'Lunch break',
                    'break_start_time' => now()->subHours(1),
                    'break_end_time' => null,
                    'created_at' => now()->subHours(1)
                ]);
                $createdCount++;

                // Create inactive duty log
                DutyLogs::create([
                    'user_id' => $user->id,
                    'break_status' => 'inactive',
                    'break_reason' => 'Coffee break',
                    'break_start_time' => now()->subHours(3),
                    'break_end_time' => now()->subHours(2),
                    'created_at' => now()->subHours(3)
                ]);
                $createdCount++;

                // Create another inactive duty log
                DutyLogs::create([
                    'user_id' => $user->id,
                    'break_status' => 'inactive',
                    'break_reason' => 'Meeting',
                    'break_start_time' => now()->subHours(5),
                    'break_end_time' => now()->subHours(4),
                    'created_at' => now()->subHours(5)
                ]);
                $createdCount++;
            }

            return response()->json([
                'success' => true,
                'message' => "Created $createdCount sample duty logs",
                'total_duty_logs' => DutyLogs::count()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error creating sample data: ' . $e->getMessage()
            ], 500);
        }
    }
}
