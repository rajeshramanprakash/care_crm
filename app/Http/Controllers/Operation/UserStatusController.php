<?php

namespace App\Http\Controllers\Operation;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class UserStatusController extends Controller
{
    public function updateDutyStatus($userId, $status, $reason = null)
    {
        try {
            $user = User::findOrFail($userId);

            // Update duty status
            $user->is_active = $status;

            // If going offline and reason provided, you might want to log this
            if ($status == 0 && $reason) {
                Log::info("User {$user->name} went off duty with reason: " . urldecode($reason));
                // You can store the reason in a separate table if needed
            }

            $user->save();

            return response()->json([
                'success' => true,
                'message' => $status ? 'Set to On Duty' : 'Set to Off Duty',
                'user' => [
                    'id' => $user->id,
                    'is_active' => $user->is_active,
                    'is_break' => $user->is_break
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error updating duty status: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update duty status'
            ], 500);
        }
    }

    public function updateBreakStatus($userId, $status, $reason = null)
    {
        try {
            $user = User::findOrFail($userId);

            // Update break status
            $user->is_break = $status;

            // If going on break and reason provided, you might want to log this
            if ($status == 1 && $reason) {
                Log::info("User {$user->name} went on break with reason: " . urldecode($reason));
                // You can store the reason in a separate table if needed
            }

            $user->save();

            return response()->json([
                'success' => true,
                'message' => $status ? 'Set to Break' : 'Set to Active',
                'user' => [
                    'id' => $user->id,
                    'is_active' => $user->is_active,
                    'is_break' => $user->is_break
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error updating break status: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update break status'
            ], 500);
        }
    }
}
