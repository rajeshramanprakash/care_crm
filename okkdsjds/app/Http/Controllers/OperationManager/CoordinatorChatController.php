<?php

namespace App\Http\Controllers\OperationManager;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Message;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CoordinatorChatController extends Controller
{
    public function index()
    {
        // Get all coordinator users
        $coordinators = User::with('role')
                         ->whereHas('role', function($query) {
                             $query->whereIn('name', ['Coordinator', 'Operation']);
                         })
                         ->get();

        // For each coordinator, get their chat participants
        foreach($coordinators as $coordinator) {
            // Get all users who have exchanged messages with this coordinator
            $coordinator->chatParticipants = User::select('users.*')
                ->join('messages', function($join) use ($coordinator) {
                    $join->on('users.id', '=', 'messages.sender_id')
                         ->where('messages.receiver_id', '=', $coordinator->id)
                         ->orWhere(function($query) use ($coordinator) {
                             $query->on('users.id', '=', 'messages.receiver_id')
                                  ->where('messages.sender_id', '=', $coordinator->id);
                         });
                })
                ->where('users.id', '!=', $coordinator->id)
                ->distinct()
                ->get();

            // Get last message and unread count for each participant
            foreach($coordinator->chatParticipants as $participant) {
                // Get the last message between coordinator and participant
                $participant->lastMessage = Message::where(function($query) use ($coordinator, $participant) {
                    $query->where('sender_id', $coordinator->id)
                          ->where('receiver_id', $participant->id);
                })->orWhere(function($query) use ($coordinator, $participant) {
                    $query->where('sender_id', $participant->id)
                          ->where('receiver_id', $coordinator->id);
                })
                ->latest()
                ->first();

                // Get unread message count
                $participant->unreadCount = Message::where('sender_id', $participant->id)
                                                 ->where('receiver_id', $coordinator->id)
                                                 ->where('is_read', false)
                                                 ->count();
            }

            // Sort participants by last message time
            $coordinator->chatParticipants = $coordinator->chatParticipants->sortByDesc(function($participant) {
                return $participant->lastMessage ? $participant->lastMessage->created_at : null;
            })->values();
        }

        return view('operation_manager.coordinator_chats.index', compact('coordinators'));
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

    public function getCoordinatorsForApp()
    {
        // Get all coordinator users
        $coordinators = User::with('role')
                         ->whereHas('role', function($query) {
                             $query->whereIn('name', ['Coordinator', 'Operation']);
                         })
                         ->get();

        // For each coordinator, get their chat participants
        foreach($coordinators as $coordinator) {
            // Get all users who have exchanged messages with this coordinator
            $coordinator->chatParticipants = User::select('users.*')
                ->join('messages', function($join) use ($coordinator) {
                    $join->on('users.id', '=', 'messages.sender_id')
                         ->where('messages.receiver_id', '=', $coordinator->id)
                         ->orWhere(function($query) use ($coordinator) {
                             $query->on('users.id', '=', 'messages.receiver_id')
                                  ->where('messages.sender_id', '=', $coordinator->id);
                         });
                })
                ->where('users.id', '!=', $coordinator->id)
                ->distinct()
                ->get();

            // Get last message and unread count for each participant
            foreach($coordinator->chatParticipants as $participant) {
                // Get the last message between coordinator and participant
                $participant->lastMessage = Message::where(function($query) use ($coordinator, $participant) {
                    $query->where('sender_id', $coordinator->id)
                          ->where('receiver_id', $participant->id);
                })->orWhere(function($query) use ($coordinator, $participant) {
                    $query->where('sender_id', $participant->id)
                          ->where('receiver_id', $coordinator->id);
                })
                ->latest()
                ->first();

                // Get unread message count
                $participant->unreadCount = Message::where('sender_id', $participant->id)
                                                 ->where('receiver_id', $coordinator->id)
                                                 ->where('is_read', false)
                                                 ->count();
            }

            // Sort participants by last message time
            $coordinator->chatParticipants = $coordinator->chatParticipants->sortByDesc(function($participant) {
                return $participant->lastMessage ? $participant->lastMessage->created_at : null;
            })->values();
        }

        return response()->json($coordinators);
    }
}
