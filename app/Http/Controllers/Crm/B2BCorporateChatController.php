<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Services\B2BCorporateChatService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class B2BCorporateChatController extends Controller
{
    public function __construct(
        protected B2BCorporateChatService $chatService
    ) {}

    public function index()
    {
        $staff = Auth::user();
        if (! $this->chatService->staffHasCorporatePartners($staff)) {
            return redirect()->back()->with('error', 'No B2B corporate partners assigned to you for chat.');
        }

        $contacts = $this->chatService->sidebarContactsForStaff($staff);

        return view('crm.b2b_corporate_chat.index', [
            'layout' => $this->resolveStaffLayout(),
            'contacts' => $contacts,
            'chatMode' => 'staff',
            'chatLayout' => 'group',
            'groupName' => null,
            'groupMembers' => [],
            'sidebarTitle' => 'B2B Corporate chats',
            'sidebarHint' => 'Group chat and private 1-to-1 with each partner.',
            'themePrimary' => '#F7941D',
            'themePrimaryDark' => '#cf6413',
            'currentStaffUserId' => $staff->id,
            'routes' => $this->staffRoutes(),
        ]);
    }

    public function getGroupMessages(int $b2bUserId)
    {
        $staff = Auth::user();
        $partner = $this->chatService->assertGroupMemberStaff($staff, $b2bUserId);
        $this->chatService->markGroupAsRead($partner->id, 'staff', $staff->id);

        return response()->json($this->chatService->getGroupMessages($partner->id));
    }

    public function getDirectMessages(int $b2bUserId)
    {
        $staff = Auth::user();
        $partner = $this->chatService->assertGroupMemberStaff($staff, $b2bUserId);
        $this->chatService->markDirectAsRead($partner->id, $staff->id, 'staff');

        return response()->json($this->chatService->getDirectMessages($partner->id, $staff->id));
    }

    public function sendMessage(Request $request)
    {
        $staff = Auth::user();
        $data = $request->validate([
            'b2b_user_id' => ['required', 'integer'],
            'body' => ['required', 'string', 'max:5000'],
            'thread' => ['nullable', 'in:group,direct'],
        ]);

        $partner = $this->chatService->assertGroupMemberStaff($staff, (int) $data['b2b_user_id']);
        $thread = $data['thread'] ?? 'group';

        if ($thread === 'direct') {
            $message = $this->chatService->sendDirectMessage($partner->id, $staff->id, 'staff', $data['body']);

            return response()->json(['success' => true, 'message' => [
                'id' => $message->id,
                'body' => $message->body,
                'sender_type' => $message->sender_type,
                'time_label' => $message->created_at?->format('h:i A'),
            ]]);
        }

        $message = $this->chatService->sendGroupMessage($partner->id, 'staff', $staff->id, $data['body']);

        return response()->json(['success' => true, 'message' => [
            'id' => $message->id,
            'body' => $message->body,
            'sender_type' => $message->sender_type,
            'sender_staff_user_id' => $staff->id,
            'sender_name' => $staff->name,
            'time_label' => $message->created_at?->format('h:i A'),
        ]]);
    }

    public function sendAttachment(Request $request)
    {
        $staff = Auth::user();
        $data = $request->validate([
            'b2b_user_id' => ['required', 'integer'],
            'attachment' => ['required', 'file', 'max:20480'],
            'thread' => ['nullable', 'in:group,direct'],
        ]);

        $partner = $this->chatService->assertGroupMemberStaff($staff, (int) $data['b2b_user_id']);
        $thread = $data['thread'] ?? 'group';

        if ($thread === 'direct') {
            $message = $this->chatService->sendDirectAttachment(
                $partner->id,
                $staff->id,
                'staff',
                $request->file('attachment')
            );

            return response()->json([
                'success' => true,
                'message' => $this->chatService->messagePayload($message),
            ]);
        }

        $message = $this->chatService->sendGroupAttachment(
            $partner->id,
            'staff',
            $staff->id,
            $request->file('attachment')
        );

        return response()->json([
            'success' => true,
            'message' => $this->chatService->messagePayload($message),
        ]);
    }

    public function markGroupAsRead(int $b2bUserId)
    {
        $staff = Auth::user();
        $partner = $this->chatService->assertGroupMemberStaff($staff, $b2bUserId);
        $this->chatService->markGroupAsRead($partner->id, 'staff', $staff->id);

        return response()->json(['success' => true]);
    }

    public function markDirectAsRead(int $b2bUserId)
    {
        $staff = Auth::user();
        $partner = $this->chatService->assertGroupMemberStaff($staff, $b2bUserId);
        $this->chatService->markDirectAsRead($partner->id, $staff->id, 'staff');

        return response()->json(['success' => true]);
    }

    public function initiateCall(int $b2bUserId)
    {
        $staff = Auth::user();
        $result = $this->chatService->initiateCallFromStaff($staff, $b2bUserId);

        return response()->json($result, ($result['success'] ?? false) ? 200 : 422);
    }

    public function unreadCounts()
    {
        $staff = Auth::user();

        return response()->json($this->chatService->staffUnreadCounts($staff));
    }

    protected function staffRoutes(): array
    {
        return [
            'messages_group' => url('/crm-b2b-corporate-chat/messages'),
            'messages_direct' => url('/crm-b2b-corporate-chat/messages'),
            'send' => route('crm.b2b_corporate_chat.send'),
            'send_attachment' => route('crm.b2b_corporate_chat.send_attachment'),
            'mark_read_group' => url('/crm-b2b-corporate-chat/mark-read'),
            'mark_read_direct' => url('/crm-b2b-corporate-chat/mark-read'),
            'call' => url('/crm-b2b-corporate-chat/call'),
            'unread_counts' => route('crm.b2b_corporate_chat.unread_counts'),
            'chat_layout' => 'group',
        ];
    }

    protected function resolveStaffLayout(): string
    {
        $role = Auth::user()->role->name ?? '';

        return match ($role) {
            'Sales' => 'sales.layouts.app',
            'Sales Manager', 'Manager' => 'manager.layouts.app',
            'Operation' => 'operation.layouts.app',
            'Operation Manager' => 'operation_manager.layouts.app',
            default => 'admin.layouts.app',
        };
    }
}
