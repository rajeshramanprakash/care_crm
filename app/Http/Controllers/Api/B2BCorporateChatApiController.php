<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\B2BUser;
use App\Services\B2BCorporateChatService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class B2BCorporateChatApiController extends Controller
{
    public function __construct(
        protected B2BCorporateChatService $chatService
    ) {}

    private function b2bUserOrAbort(Request $request): B2BUser
    {
        $user = $request->user();
        $info = Cache::get('b2b_info_'.$user->id);
        abort_if(! $info || empty($info['b2b_user_id']), 403, 'B2B access only.');

        $b2bUser = B2BUser::find((int) $info['b2b_user_id']);
        abort_if(! $b2bUser, 403, 'B2B access only.');
        abort_if(! $b2bUser->isCorporate(), 403, 'Corporate B2B access only.');

        return $b2bUser;
    }

    public function access(Request $request)
    {
        $b2bUser = $this->b2bUserOrAbort($request);

        return response()->json([
            'success' => true,
            'has_access' => $this->chatService->corporateUserCanChat($b2bUser),
        ]);
    }

    public function index(Request $request)
    {
        $b2bUser = $this->b2bUserOrAbort($request);
        $canChat = $this->chatService->corporateUserCanChat($b2bUser);

        return response()->json([
            'success' => true,
            'has_access' => $canChat,
            'contacts' => $canChat
                ? $this->chatService->sidebarContactsForCorporate($b2bUser)->values()
                : [],
            'group_name' => $b2bUser->chat_group_name,
            'sidebar_title' => 'Chats',
            'sidebar_hint' => 'Team group + private chats with members.',
        ]);
    }

    public function getGroupMessages(Request $request)
    {
        $b2bUser = $this->b2bUserOrAbort($request);
        $this->chatService->assertGroupMemberCorporate($b2bUser);
        $this->chatService->markGroupAsRead($b2bUser->id, 'b2b');

        return response()->json(
            $this->mapMessages($this->chatService->getGroupMessages($b2bUser->id))
        );
    }

    public function getDirectMessages(Request $request, int $userId)
    {
        $b2bUser = $this->b2bUserOrAbort($request);
        $this->chatService->assertGroupMemberCorporate($b2bUser);
        $this->chatService->assertCorporatePeer($b2bUser, $userId);
        $this->chatService->markDirectAsRead($b2bUser->id, $userId, 'b2b');

        return response()->json(
            $this->mapMessages($this->chatService->getDirectMessages($b2bUser->id, $userId))
        );
    }

    public function sendMessage(Request $request)
    {
        $b2bUser = $this->b2bUserOrAbort($request);
        $this->chatService->assertGroupMemberCorporate($b2bUser);

        $data = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
            'user_id' => ['nullable', 'integer'],
        ]);

        if (! empty($data['user_id'])) {
            $staffId = (int) $data['user_id'];
            $this->chatService->assertCorporatePeer($b2bUser, $staffId);
            $message = $this->chatService->sendDirectMessage($b2bUser->id, $staffId, 'b2b', $data['body']);

            return response()->json([
                'success' => true,
                'message' => $this->mapMessage($this->chatService->messagePayload($message)),
            ]);
        }

        $message = $this->chatService->sendGroupMessage($b2bUser->id, 'b2b', null, $data['body']);

        return response()->json([
            'success' => true,
            'message' => $this->mapMessage($this->chatService->messagePayload($message)),
        ]);
    }

    public function sendAttachment(Request $request)
    {
        $b2bUser = $this->b2bUserOrAbort($request);
        $this->chatService->assertGroupMemberCorporate($b2bUser);

        $data = $request->validate([
            'attachment' => ['required', 'file', 'max:20480'],
            'user_id' => ['nullable', 'integer'],
        ]);

        if (! empty($data['user_id'])) {
            $staffId = (int) $data['user_id'];
            $this->chatService->assertCorporatePeer($b2bUser, $staffId);
            $message = $this->chatService->sendDirectAttachment(
                $b2bUser->id,
                $staffId,
                'b2b',
                $request->file('attachment')
            );

            return response()->json([
                'success' => true,
                'message' => $this->mapMessage($this->chatService->messagePayload($message)),
            ]);
        }

        $message = $this->chatService->sendGroupAttachment(
            $b2bUser->id,
            'b2b',
            null,
            $request->file('attachment')
        );

        return response()->json([
            'success' => true,
            'message' => $this->mapMessage($this->chatService->messagePayload($message)),
        ]);
    }

    public function markGroupAsRead(Request $request)
    {
        $b2bUser = $this->b2bUserOrAbort($request);
        $this->chatService->assertGroupMemberCorporate($b2bUser);
        $this->chatService->markGroupAsRead($b2bUser->id, 'b2b');

        return response()->json(['success' => true]);
    }

    public function markDirectAsRead(Request $request, int $userId)
    {
        $b2bUser = $this->b2bUserOrAbort($request);
        $this->chatService->assertGroupMemberCorporate($b2bUser);
        $this->chatService->assertCorporatePeer($b2bUser, $userId);
        $this->chatService->markDirectAsRead($b2bUser->id, $userId, 'b2b');

        return response()->json(['success' => true]);
    }

    public function initiateCall(Request $request, int $userId)
    {
        $b2bUser = $this->b2bUserOrAbort($request);
        $this->chatService->assertGroupMemberCorporate($b2bUser);
        $result = $this->chatService->initiateCallFromCorporate($b2bUser, $userId);

        return response()->json($result, ($result['success'] ?? false) ? 200 : 422);
    }

    public function unreadCounts(Request $request)
    {
        $b2bUser = $this->b2bUserOrAbort($request);
        if (! $this->chatService->corporateUserCanChat($b2bUser)) {
            return response()->json([]);
        }

        return response()->json($this->chatService->corporateUnreadCounts($b2bUser));
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
