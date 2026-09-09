<?php

namespace App\Http\Controllers\PostSales;

use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;
use App\Models\FavoriteChat;
use App\Models\FavoriteGroup;
use Illuminate\Support\Facades\Auth;
use App\Services\ExpoNotificationService;
class ChatController extends Controller
{
    public function index()
    {
        $users = User::where('id', '!=', Auth::id())
            ->whereRaw("FIND_IN_SET(?, role_id)", [1])
            ->get();
        foreach ($users as $user) {
            $user->unread_count = Message::where('sender_id', $user->id)
                ->where('receiver_id', Auth::id())
                ->where('is_read', false)
                ->count();
            $user->last_message = Message::where(function($query) use ($user) {
                $query->where('sender_id', Auth::id())
                    ->where('receiver_id', $user->id);
            })->orWhere(function($query) use ($user) {
                $query->where('sender_id', $user->id)
                    ->where('receiver_id', Auth::id());
            })
            ->orderBy('created_at', 'desc')
            ->first();
        }
        // Sort users by last message datetime descending (most recent first)
        $users = $users->sortByDesc(function($user) {
            return $user->last_message ? $user->last_message->created_at : $user->created_at;
        })->values();
        return view('postsales.chat.index', compact('users'));
    }

    public function getMessages($userId)
    {
        $messages = Message::where(function($query) use ($userId) {
            $query->where('sender_id', Auth::id())
                  ->where('receiver_id', $userId);
        })->orWhere(function($query) use ($userId) {
            $query->where('sender_id', $userId)
                  ->where('receiver_id', Auth::id());
        })
        ->with(['repliedTo.sender', 'repliedTo.receiver'])
        ->orderBy('created_at', 'asc')
        ->get();

        return response()->json($messages);
    }

    public function sendMessage(Request $request)
    {
        $request->validate([
            'receiver_id' => 'required|exists:users,id',
            'message' => 'required|string',
            'reply_to_id' => 'nullable|exists:messages,id'
        ]);

        $message = Message::create([
            'sender_id' => Auth::id(),
            'receiver_id' => $request->receiver_id,
            'message' => $request->message,
            'reply_to_id' => $request->reply_to_id
        ]);

        ExpoNotificationService::send([
            $request->receiver_id
        ], 'New Message', $request->message);

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
        $type = $file->getMimeType();
        
        // Get original filename and extension
        $originalName = $file->getClientOriginalName();
        $extension = $file->getClientOriginalExtension();
        
        // Decode URL encoding if present, then get filename without extension
        $decodedName = urldecode($originalName);
        $nameWithoutExt = pathinfo($decodedName, PATHINFO_FILENAME);
        
        // Replace spaces and special characters with underscores
        $safeNameWithoutExt = preg_replace('/[^A-Za-z0-9_\-]/', '_', $nameWithoutExt);
        
        // Create unique filename: originalname_timestamp_uniqueid.ext
        $uniqueFileName = $safeNameWithoutExt . '_' . time() . '_' . uniqid() . '.' . $extension;
        
        // Store file with unique name
        $path = $file->storeAs('chat_attachments', $uniqueFileName, 'public');

        // Save original filename with underscores (no spaces, no %20)
        $cleanOriginalName = $safeNameWithoutExt . '.' . $extension;

        $message = \App\Models\Message::create([
            'sender_id' => \Auth::id(),
            'receiver_id' => $request->receiver_id,
            'message' => null,
            'attachment' => $path,
            'attachment_type' => $type,
            'original_filename' => $cleanOriginalName,
        ]);

        ExpoNotificationService::send([
            $request->receiver_id
        ], 'New Message', 'You have a new Attachment' );

        return response()->json(['success' => true, 'message' => $message]);
    }

    public function toggleGroupFavorite($groupId)
    {
        $user = Auth::user();
        $favorite = FavoriteGroup::where('user_id', $user->id)->where('group_id', $groupId)->first();
        if ($favorite) {
            $favorite->delete();
            return response()->json(['status' => 'removed']);
        } else {
            FavoriteGroup::create(['user_id' => $user->id, 'group_id' => $groupId]);
            return response()->json(['status' => 'added']);
        }
    }

    public function getGroupFavorites()
    {
        $user = Auth::user();
        $favorites = FavoriteGroup::where('user_id', $user->id)->pluck('group_id');
        return response()->json($favorites);
    }
}
