<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BreakLog;
use App\Models\DutyLogs;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class UserStatusController extends Controller
{
    /**
     * Get the current duty and break status of the authenticated user.
     */
    public function getStatus(Request $request)
    {
        $user = Auth::user();

        return response()->json([
            'success' => true,
            'is_active' => (bool) $user->is_active,
            'is_break' => (bool) $user->is_break,
        ]);
    }

    /**
     * Toggle the duty status (is_active).
     */
    public function toggleDuty(Request $request)
    {
        try {
            $user = Auth::user();
            $newStatus = !$user->is_active;
            
            // If going off duty (is_active = false), ensure break is also off
            if (!$newStatus && $user->is_break) {
                $this->handleBreakToggle($user, false, 'System: Off Duty');
                $user->is_break = false;
            }

            $user->is_active = $newStatus;
            $user->save();

            // Log the duty status change
            if ($newStatus) {
                // User went on duty
                DutyLogs::create([
                    'user_id' => $user->id,
                    'break_status' => 'active',
                    'break_reason' => 'Started duty',
                    'break_start_time' => now(),
                    'break_end_time' => null,
                ]);
            } else {
                // User went off duty
                $reason = $request->input('reason', 'Off duty');

                // End the current active duty log
                $activeLog = DutyLogs::where('user_id', $user->id)
                    ->where('break_status', 'active')
                    ->whereNull('break_end_time')
                    ->latest()
                    ->first();

                if ($activeLog) {
                    $activeLog->update([
                        'break_end_time' => now(),
                    ]);
                }

                // Create new inactive log
                DutyLogs::create([
                    'user_id' => $user->id,
                    'break_status' => 'inactive',
                    'break_reason' => $reason,
                    'break_start_time' => now(),
                    'break_end_time' => now(),
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => $newStatus ? 'You are now On Duty' : 'You are now Off Duty',
                'is_active' => $newStatus,
                'is_break' => (bool) $user->is_break
            ]);

        } catch (\Exception $e) {
            Log::error('Error toggling duty status: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error updating duty status'
            ], 500);
        }
    }

    /**
     * Toggle the break status (is_break).
     */
    public function toggleBreak(Request $request)
    {
        try {
            $user = Auth::user();

            // Cannot toggle break if not on duty
            if (!$user->is_active) {
                return response()->json([
                    'success' => false,
                    'message' => 'You must be On Duty to take a break'
                ], 400);
            }

            $newStatus = !$user->is_break;
            $this->handleBreakToggle($user, $newStatus, $request->input('reason'));
            
            $user->is_break = $newStatus;
            $user->save();

            return response()->json([
                'success' => true,
                'message' => $newStatus ? 'You are now on Break' : 'You are back from Break',
                'is_break' => $newStatus,
                'is_active' => (bool) $user->is_active
            ]);

        } catch (\Exception $e) {
            Log::error('Error toggling break status: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error updating break status'
            ], 500);
        }
    }

    /**
     * Helper to handle break logs
     */
    private function handleBreakToggle($user, $newStatus, $reason = null)
    {
        if ($newStatus) {
            // User went on break
            $reason = $reason ?: 'Break';

            BreakLog::create([
                'user_id' => $user->id,
                'break_status' => 'offline', // Using 'offline' for break as per existing conventions seems to be the pattern, or 'break'? Checked other files, uses 'offline' for break start often or just status tracking. Using 'offline' based on UserController logic.
                'break_reason' => $reason,
                'break_start_time' => now(),
                'break_end_time' => null,
            ]);
        } else {
            // User went active (break ended)
            $reason = $reason ?: 'Break ended';

            // End the current active break log
            $activeLog = BreakLog::where('user_id', $user->id)
                ->where('break_status', 'offline')
                ->whereNull('break_end_time')
                ->latest()
                ->first();

            if ($activeLog) {
                $activeLog->update([
                    'break_end_time' => now(),
                ]);
            }

            // Create new online log (optional, based on UserController logic)
            BreakLog::create([
                'user_id' => $user->id,
                'break_status' => 'online',
                'break_reason' => $reason,
                'break_start_time' => now(),
                'break_end_time' => now(),
            ]);
        }
    }
}
