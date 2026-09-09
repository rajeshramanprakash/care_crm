<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Message;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StaffChatController extends Controller
{
    public function index()
    {
        // Get all sales staff users with their chat participants
        $salesStaff = User::with('role')
                         ->whereHas('role', function($query) {
                             $query->where('name', 'Sales');
                         })
                         ->get();

        // For each sales staff, get their chat participants
        foreach($salesStaff as $staff) {
            $staff->chatParticipants = User::select('users.*')
                ->join('messages', function($join) use ($staff) {
                    $join->on('users.id', '=', 'messages.sender_id')
                         ->where('messages.receiver_id', '=', $staff->id)
                         ->orWhere(function($query) use ($staff) {
                             $query->on('users.id', '=', 'messages.receiver_id')
                                  ->where('messages.sender_id', '=', $staff->id);
                         });
                })
                ->where('users.id', '!=', $staff->id)
                ->distinct()
                ->get();

            // Add last message and unread count for each participant
            foreach($staff->chatParticipants as $participant) {
                // Get last message
                $participant->lastMessage = Message::where(function($query) use ($staff, $participant) {
                    $query->where('sender_id', $staff->id)
                          ->where('receiver_id', $participant->id);
                })->orWhere(function($query) use ($staff, $participant) {
                    $query->where('sender_id', $participant->id)
                          ->where('receiver_id', $staff->id);
                })
                ->orderBy('created_at', 'desc')
                ->first();

                // Get unread count
                $participant->unreadCount = Message::where('sender_id', $participant->id)
                    ->where('receiver_id', $staff->id)
                    ->where('is_read', false)
                    ->count();
            }
        }

        // If API request, return JSON data
        if (request()->expectsJson()) {
            return response()->json($salesStaff);
        }

        return view('manager.staff_chats.index', compact('salesStaff'));
    }

    public function getMessages($userId, $participantId)
    {
        $messages = Message::where(function($query) use ($userId, $participantId) {
            $query->where('sender_id', $userId)
                  ->where('receiver_id', $participantId);
        })->orWhere(function($query) use ($userId, $participantId) {
            $query->where('sender_id', $participantId)
                  ->where('receiver_id', $userId);
        })
        ->with(['sender', 'receiver'])
        ->orderBy('created_at', 'asc')
        ->get();

        return response()->json($messages);
    }

    public function sendMessage(Request $request)
    {
        $request->validate([
            'to_user_id' => 'required|exists:users,id',
            'message' => 'required|string'
        ]);

        $message = Message::create([
            'sender_id' => Auth::id(),
            'receiver_id' => $request->to_user_id,
            'message' => $request->message,
            'is_read' => false
        ]);

        return response()->json($message->load(['sender', 'receiver']));
    }

    public function markAsRead($userId)
    {
        Message::where('sender_id', $userId)
              ->where('receiver_id', Auth::id())
              ->where('is_read', false)
              ->update(['is_read' => true]);

        return response()->json(['success' => true]);
    }

    public function getUnreadCounts()
    {
        $unreadCounts = Message::where('receiver_id', Auth::id())
                             ->where('is_read', false)
                             ->selectRaw('sender_id, COUNT(*) as count')
                             ->groupBy('sender_id')
                             ->get()
                             ->pluck('count', 'sender_id');

        return response()->json($unreadCounts);
    }
}
