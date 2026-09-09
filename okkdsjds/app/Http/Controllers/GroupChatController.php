<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\GroupMessage;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\ExpoNotificationService;

class GroupChatController extends Controller
{
    // List all groups the user is a member of
    public function listGroups()
    {
        $user = Auth::user();
        $groups = $user->groups()->with('users')->get();

        $groupsWithUnread = $groups->map(function ($group) use ($user) {
            // Get IDs of messages the user has read in this group
            $readMessageIds = \App\Models\GroupMessageRead::where('user_id', $user->id)
                ->where('group_id', $group->id)
                ->pluck('group_message_id')
                ->toArray();

            // Count unread messages (not sent by the user)
            $unreadCount = $group->groupMessages()
                ->whereNotIn('id', $readMessageIds)
                ->where('sender_id', '!=', $user->id)
                ->count();

            $group->unread_count = $unreadCount;
            return $group;
        });

        return response()->json($groupsWithUnread);
    }

    // Create a new group
    public function createGroup(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'user_ids' => 'required|array|min:3',
            'user_ids.*' => 'exists:users,id',
        ]);

        $group = Group::create([
            'name' => $request->name,
            'created_by' => Auth::id(),
        ]);
        $group->users()->attach(array_unique(array_merge($request->user_ids, [Auth::id()])));
        return response()->json($group->load('users'));
    }

    // Add a user to a group
    public function addUser(Request $request, $groupId)
    {
        $group = Group::findOrFail($groupId);
        if ($group->created_by !== Auth::id()) {
            return response()->json(['error' => 'Only the group creator can add users.'], 403);
        }
        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);
        $group->users()->syncWithoutDetaching([$request->user_id]);
        return response()->json(['success' => true]);
    }

    // Remove a user from a group
    public function removeUser(Request $request, $groupId)
    {
        $group = Group::findOrFail($groupId);
        if ($group->created_by !== Auth::id()) {
            return response()->json(['error' => 'Only the group creator can remove users.'], 403);
        }
        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);
        $group->users()->detach($request->user_id);
        return response()->json(['success' => true]);
    }

    // Send a message to a group
    public function sendMessage(Request $request, $groupId)
    {
        $request->validate([
            'message' => 'required|string',
        ]);
        $group = Group::findOrFail($groupId);
        $msg = $group->groupMessages()->create([
            'sender_id' => Auth::id(),
            'message' => $request->message,
        ]);
        // Send Expo notification to all group members except sender
        $memberIds = $group->users()->pluck('users.id')->toArray();
        $notifyIds = array_diff($memberIds, [Auth::id()]);
        if (!empty($notifyIds)) {
            ExpoNotificationService::send(
                $notifyIds,
                'New Group Message',
                $request->message
            );
        }
        return response()->json($msg->load('sender'));
    }

    // Send an attachment to a group
    public function sendAttachment(Request $request, $group_id)
    {
        try {
            \Log::info('Attachment request:', $request->all());
            if ($request->hasFile('attachment')) {
                $file = $request->file('attachment');
                $path = $file->store('public/group_attachments');
                $relativePath = str_replace('public/', '', $path);

                \Log::info('File uploaded:', ['path' => $relativePath]);

                $group = Group::findOrFail($group_id);
                $msg = $group->groupMessages()->create([
                    'sender_id' => Auth::id(),
                    'message' => $request->filled('message') ? $request->input('message') : '',
                    'attachment' => $relativePath,
                ]);

                \Log::info('DB record:', $msg->toArray());

                return response()->json(['success' => true, 'message' => $msg]);
            } else {
                \Log::error('No file found in request');
                return response()->json(['success' => false, 'message' => 'No file found in request.']);
            }
        } catch (\Exception $e) {
            \Log::error('Group attachment error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // Get all messages for a group
    public function getMessages($groupId)
    {
        $group = Group::findOrFail($groupId);
        $messages = $group->groupMessages()->with('sender')->orderBy('created_at')->get();
        return response()->json($messages);
    }

    // Delete a group
    public function deleteGroup($groupId)
    {
        $group = Group::findOrFail($groupId);
        if ($group->created_by !== Auth::id()) {
            return response()->json(['error' => 'Only the group creator can delete this group.'], 403);
        }
        $group->delete();
        return response()->json(['success' => true]);
    }

    // Mark all messages in a group as read for the authenticated user
    public function markGroupMessagesAsRead($groupId)
    {
        $user = Auth::user();
        $group = \App\Models\Group::findOrFail($groupId);
        $messageIds = $group->groupMessages()
            ->where('sender_id', '!=', $user->id)
            ->pluck('id')
            ->toArray();

        if (empty($messageIds)) {
            return response()->json(['success' => true, 'marked' => 0]);
        }

        $alreadyRead = \App\Models\GroupMessageRead::where('user_id', $user->id)
            ->where('group_id', $groupId)
            ->whereIn('group_message_id', $messageIds)
            ->pluck('group_message_id')
            ->toArray();

        $toInsert = array_diff($messageIds, $alreadyRead);
        $now = now();
        $insertData = array_map(function($msgId) use ($user, $groupId, $now) {
            return [
                'user_id' => $user->id,
                'group_id' => $groupId,
                'group_message_id' => $msgId,
                'read_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }, $toInsert);
        if (!empty($insertData)) {
            \App\Models\GroupMessageRead::insert($insertData);
        }
        return response()->json(['success' => true, 'marked' => count($insertData)]);
    }
}
