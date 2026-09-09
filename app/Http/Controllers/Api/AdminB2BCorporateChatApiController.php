<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\B2BCorporateChatService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminB2BCorporateChatApiController extends Controller
{
    public function __construct(
        protected B2BCorporateChatService $chatService
    ) {}

    public function access()
    {
        $staff = Auth::user();

        return response()->json([
            'success' => true,
            'has_access' => $this->chatService->staffHasCorporatePartners($staff),
        ]);
    }

    public function index()
    {
        $staff = Auth::user();
        $hasAccess = $this->chatService->staffHasCorporatePartners($staff);

        return response()->json([
            'success' => true,
            'has_access' => $hasAccess,
            'contacts' => $hasAccess
                ? $this->chatService->sidebarContactsForStaff($staff)->values()
                : [],
            'current_staff_user_id' => $staff->id,
            'sidebar_title' => 'B2B Corporate chats',
            'sidebar_hint' => 'Group chat and private 1-to-1 with each partner.',
        ]);
    }

    public function getGroupMessages(int $b2bUserId)
    {
        $staff = Auth::user();
        $partner = $this->chatService->assertGroupMemberStaff($staff, $b2bUserId);
        $this->chatService->markGroupAsRead($partner->id, 'staff', $staff->id);

        return response()->json(
            $this->mapMessages($this->chatService->getGroupMessages($partner->id))
        );
    }

    public function getDirectMessages(int $b2bUserId)
    {
        $staff = Auth::user();
        $partner = $this->chatService->assertGroupMemberStaff($staff, $b2bUserId);
        $this->chatService->markDirectAsRead($partner->id, $staff->id, 'staff');

        return response()->json(
            $this->mapMessages($this->chatService->getDirectMessages($partner->id, $staff->id))
        );
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

            return response()->json([
                'success' => true,
                'message' => $this->mapMessage($this->chatService->messagePayload($message)),
            ]);
        }

        $message = $this->chatService->sendGroupMessage($partner->id, 'staff', $staff->id, $data['body']);

        return response()->json([
            'success' => true,
            'message' => $this->mapMessage($this->chatService->messagePayload($message)),
        ]);
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
                'message' => $this->mapMessage($this->chatService->messagePayload($message)),
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
            'message' => $this->mapMessage($this->chatService->messagePayload($message)),
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

    /** @param  iterable<int, array<string, mixed>>  $messages */
    protected function mapMessages(iterable $messages): array
    {
        return collect($messages)->map(fn (array $msg) => $this->mapMessage($msg))->values()->all();
    }

    /** @param  array<string, mixed>  $msg */
    protected function mapMessage(array $msg): array
    {
        if (! empty($msg['attachment'])) {
            $path = ltrim((string) $msg['attachment'], '/');
            $msg['attachment_url'] = url('/storage/'.$path);
        }

        return $msg;
    }
}
