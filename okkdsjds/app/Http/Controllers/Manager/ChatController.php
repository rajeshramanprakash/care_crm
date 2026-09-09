<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;
use App\Models\FavoriteChat;
use Illuminate\Support\Facades\Auth;
use App\Services\ExpoNotificationService;

class ChatController extends Controller
{
    public function index()
    {
        $currentUserId = Auth::id();
        $users = User::where('id', '!=', $currentUserId)->get();
        $enrichedUsers = $users->map(function($user) use ($currentUserId) {
            // Unread count
            $unread = \App\Models\Message::where('sender_id', $user->id)
                ->where('receiver_id', $currentUserId)
                ->where('is_read', false)
                ->count();
            // Last message (send or receive)
            $lastMsg = \App\Models\Message::where(function($q) use ($user, $currentUserId) {
                $q->where('sender_id', $user->id)->where('receiver_id', $currentUserId);
            })->orWhere(function($q) use ($user, $currentUserId) {
                $q->where('sender_id', $currentUserId)->where('receiver_id', $user->id);
            })->orderByDesc('created_at')->first();
            $user->unread_count = $unread;
            $user->last_message = $lastMsg;
            return $user;
        });
        // Sort: unread first, then by last message time desc
        $sortedUsers = $enrichedUsers->sort(function($a, $b) {
            if ($a->unread_count > 0 && $b->unread_count == 0) return -1;
            if ($a->unread_count == 0 && $b->unread_count > 0) return 1;
            $aTime = $a->last_message ? strtotime($a->last_message->created_at) : 0;
            $bTime = $b->last_message ? strtotime($b->last_message->created_at) : 0;
            return $bTime <=> $aTime;
        })->values();
        return view('manager.chat.index', ['users' => $sortedUsers]);
    }

    public function getMessages($userId)
    {
        $messages = Message::where(function($query) use ($userId) {
            $query->where('sender_id', Auth::id())
                  ->where('receiver_id', $userId);
        })->orWhere(function($query) use ($userId) {
            $query->where('sender_id', $userId)
                  ->where('receiver_id', Auth::id());
        })->orderBy('created_at', 'asc')->get();

        return response()->json($messages);
    }

    public function sendMessage(Request $request)
    {
        $request->validate([
            'receiver_id' => 'required|exists:users,id',
            'message' => 'required|string'
        ]);

        $message = Message::create([
            'sender_id' => Auth::id(),
            'receiver_id' => $request->receiver_id,
            'message' => $request->message
        ]);
        // Send Expo notification to receiver
        ExpoNotificationService::send([
            $request->receiver_id
        ], 'New Direct Message', $request->message);
        return response()->json($message);
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
        $users = User::where('id', '!=', Auth::id())->get();
        $counts = [];

        foreach ($users as $user) {
            $counts[$user->id] = Message::where('sender_id', $user->id)
                                      ->where('receiver_id', Auth::id())
                                      ->where('is_read', false)
                                      ->count();
        }

        return response()->json($counts);
    }


    public function toggleFavorite($userId)
    {
        $user = Auth::user();
        $favorite = FavoriteChat::where('user_id', $user->id)->where('favorite_user_id', $userId)->first();
        if ($favorite) {
            $favorite->delete();
            return response()->json(['status' => 'removed']);
        } else {
            FavoriteChat::create(['user_id' => $user->id, 'favorite_user_id' => $userId]);
            return response()->json(['status' => 'added']);
        }
    }

    public function getFavorites()
    {
        $user = Auth::user();
        $favorites = FavoriteChat::where('user_id', $user->id)->pluck('favorite_user_id');
        return response()->json($favorites);
    }

    public function sendAttachment(Request $request)
    {
        \Auth::user()->update(['last_online' => now()]);
        $request->validate([
            'receiver_id' => 'required|exists:users,id',
            'attachment' => 'required|file|max:10240', // 10MB max
        ]);

        $file = $request->file('attachment');
        $path = $file->store('chat_attachments', 'public');
        $type = $file->getMimeType();

        $message = \App\Models\Message::create([
            'sender_id' => \Auth::id(),
            'receiver_id' => $request->receiver_id,
            'message' => null,
            'attachment' => $path,
            'attachment_type' => $type,
        ]);

        return response()->json(['success' => true, 'message' => $message]);
    }
}
