<?php

namespace App\Http\Controllers\B2B;

use App\Http\Controllers\B2B\Concerns\ResolvesB2BPartnerPortal;
use App\Http\Controllers\Controller;
use App\Services\B2BCorporateChatService;
use Illuminate\Http\Request;

class B2BCorporateChatController extends Controller
{
    use ResolvesB2BPartnerPortal;

    public function __construct(
        protected B2BCorporateChatService $chatService
    ) {}

    public function index(Request $request)
    {
        $b2bUser = $this->currentB2BUser();
        if (! $this->chatService->corporateUserCanChat($b2bUser)) {
            return redirect()->route('b2b.corporate.dashboard')->with('error', 'Chat is not enabled for your account.');
        }

        $contacts = $this->chatService->sidebarContactsForCorporate($b2bUser);
        $group = $contacts->firstWhere('id', 'group') ?? $this->chatService->formatGroupForCorporate($b2bUser);

        return view('b2b_corporate.chat.index', array_merge($this->portalContext($b2bUser), [
            'b2bUser' => $b2bUser,
            'contacts' => $contacts,
            'chatMode' => 'corporate',
            'chatLayout' => 'group',
            'groupName' => $b2bUser->chat_group_name,
            'groupMembers' => $group['members'] ?? $this->chatService->groupMembers($b2bUser)->all(),
            'sidebarTitle' => 'Chats',
            'sidebarHint' => 'Team group + private chats with members.',
            'themePrimary' => '#F7941D',
            'themePrimaryDark' => '#cf6413',
            'currentStaffUserId' => null,
            'routes' => $this->corporateRoutes(),
        ]));
    }

    public function getGroupMessages()
    {
        $b2bUser = $this->currentB2BUser();
        $this->chatService->assertGroupMemberCorporate($b2bUser);
        $this->chatService->markGroupAsRead($b2bUser->id, 'b2b');

        return response()->json($this->chatService->getGroupMessages($b2bUser->id));
    }

    public function getDirectMessages(int $userId)
    {
        $b2bUser = $this->currentB2BUser();
        $this->chatService->assertGroupMemberCorporate($b2bUser);
        $this->chatService->assertCorporatePeer($b2bUser, $userId);
        $this->chatService->markDirectAsRead($b2bUser->id, $userId, 'b2b');

        return response()->json($this->chatService->getDirectMessages($b2bUser->id, $userId));
    }

    public function sendMessage(Request $request)
    {
        $b2bUser = $this->currentB2BUser();
        $this->chatService->assertGroupMemberCorporate($b2bUser);

        $data = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
            'user_id' => ['nullable', 'integer'],
        ]);

        if (! empty($data['user_id'])) {
            $staffId = (int) $data['user_id'];
            $this->chatService->assertCorporatePeer($b2bUser, $staffId);
            $message = $this->chatService->sendDirectMessage($b2bUser->id, $staffId, 'b2b', $data['body']);

            return response()->json(['success' => true, 'message' => $this->chatService->getDirectMessages($b2bUser->id, $staffId)->last()]);
        }

        $message = $this->chatService->sendGroupMessage($b2bUser->id, 'b2b', null, $data['body']);

        return response()->json(['success' => true, 'message' => $this->chatService->getGroupMessages($b2bUser->id)->last()]);
    }

    public function sendAttachment(Request $request)
    {
        $b2bUser = $this->currentB2BUser();
        $this->chatService->assertGroupMemberCorporate($b2bUser);

        $data = $request->validate([
            'attachment' => ['required', 'file', 'max:20480'],
            'user_id' => ['nullable', 'integer'],
        ]);

        if (! empty($data['user_id'])) {
            $staffId = (int) $data['user_id'];
            $this->chatService->assertCorporatePeer($b2bUser, $staffId);
            $message = $this->chatService->sendDirectAttachment($b2bUser->id, $staffId, 'b2b', $request->file('attachment'));

            return response()->json([
                'success' => true,
                'message' => $this->chatService->messagePayload($message),
            ]);
        }

        $message = $this->chatService->sendGroupAttachment($b2bUser->id, 'b2b', null, $request->file('attachment'));

        return response()->json([
            'success' => true,
            'message' => $this->chatService->messagePayload($message),
        ]);
    }

    public function markGroupAsRead()
    {
        $b2bUser = $this->currentB2BUser();
        $this->chatService->assertGroupMemberCorporate($b2bUser);
        $this->chatService->markGroupAsRead($b2bUser->id, 'b2b');

        return response()->json(['success' => true]);
    }

    public function markDirectAsRead(int $userId)
    {
        $b2bUser = $this->currentB2BUser();
        $this->chatService->assertGroupMemberCorporate($b2bUser);
        $this->chatService->assertCorporatePeer($b2bUser, $userId);
        $this->chatService->markDirectAsRead($b2bUser->id, $userId, 'b2b');

        return response()->json(['success' => true]);
    }

    public function initiateCall(int $userId)
    {
        $b2bUser = $this->currentB2BUser();
        $this->chatService->assertGroupMemberCorporate($b2bUser);
        $result = $this->chatService->initiateCallFromCorporate($b2bUser, $userId);

        return response()->json($result, ($result['success'] ?? false) ? 200 : 422);
    }

    public function unreadCounts()
    {
        $b2bUser = $this->currentB2BUser();
        if (! $this->chatService->corporateUserCanChat($b2bUser)) {
            return response()->json([]);
        }

        return response()->json($this->chatService->corporateUnreadCounts($b2bUser));
    }

    protected function corporateRoutes(): array
    {
        return [
            'messages_group' => route('b2b.corporate.chat.messages.group'),
            'messages_direct' => url('/b2b-corporate/chat/messages'),
            'send' => route('b2b.corporate.chat.send'),
            'send_attachment' => route('b2b.corporate.chat.send_attachment'),
            'mark_read_group' => route('b2b.corporate.chat.mark_read.group'),
            'mark_read_direct' => url('/b2b-corporate/chat/mark-read'),
            'call' => url('/b2b-corporate/chat/call'),
            'unread_counts' => route('b2b.corporate.chat.unread_counts'),
            'chat_layout' => 'group',
        ];
    }
}
