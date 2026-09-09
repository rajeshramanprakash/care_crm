<?php

namespace App\Services;

use App\Models\B2BChatMessage;
use App\Models\B2BChatReadCursor;
use App\Models\B2BUser;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;

class B2BCorporateChatService
{
    public const THREAD_GROUP = 'group';

    public const THREAD_DIRECT = 'direct';

    public function corporateUserCanChat(?B2BUser $user): bool
    {
        return $user
            && $user->isCorporate()
            && $user->chat_enabled
            && $user->chatPeers()->exists();
    }

    public function staffHasCorporatePartners(User $staff): bool
    {
        return B2BUser::corporate()
            ->where('chat_enabled', true)
            ->whereHas('chatPeers', fn ($q) => $q->where('users.id', $staff->id))
            ->exists();
    }

    public function assertGroupMemberCorporate(B2BUser $b2bUser): void
    {
        if (! $this->corporateUserCanChat($b2bUser)) {
            abort(403, 'Chat is not available.');
        }
    }

    public function assertGroupMemberStaff(User $staff, int $b2bUserId): B2BUser
    {
        return $this->assertStaffPartner($staff, $b2bUserId);
    }

    public function assertStaffPartner(User $staff, int $b2bUserId): B2BUser
    {
        $partner = B2BUser::corporate()
            ->where('id', $b2bUserId)
            ->where('chat_enabled', true)
            ->whereHas('chatPeers', fn ($q) => $q->where('users.id', $staff->id))
            ->first();

        if (! $partner) {
            abort(403, 'Invalid corporate partner.');
        }

        return $partner;
    }

    /** @return Collection<int, array<string, mixed>> */
    public function groupMembers(B2BUser $b2bUser): Collection
    {
        $staff = $b2bUser->chatPeers()
            ->orderBy('f_name')
            ->orderBy('l_name')
            ->get()
            ->map(fn (User $peer) => [
                'id' => $peer->id,
                'name' => $peer->name,
                'role' => $peer->role?->name ?? 'CRM User',
                'type' => 'staff',
            ]);

        return collect([
            [
                'id' => $b2bUser->id,
                'name' => $b2bUser->name,
                'role' => 'Corporate partner',
                'type' => 'b2b',
            ],
        ])->merge($staff)->values();
    }

    /** @return array<string, mixed> */
    public function formatGroupForCorporate(B2BUser $b2bUser): array
    {
        $last = $this->lastGroupMessage($b2bUser->id);
        $unread = $this->groupUnreadCountForCorporate($b2bUser->id);

        return [
            'id' => 'group',
            'name' => $b2bUser->chat_group_name ?: 'Team group',
            'subtitle' => $this->groupMembers($b2bUser)->count().' members',
            'initial' => strtoupper(substr($b2bUser->chat_group_name ?: 'G', 0, 1)),
            'color' => '#F7941D',
            'avatar' => null,
            'unread_count' => $unread,
            'last_message' => $this->previewText($last),
            'last_message_time' => $last?->created_at?->format('h:i A'),
            'last_message_at' => $last?->created_at?->timestamp ?? 0,
            'members' => $this->groupMembers($b2bUser)->all(),
            'is_group' => true,
            'thread_kind' => self::THREAD_GROUP,
            'sidebar_key' => 'group',
        ];
    }

    /** @return array<string, mixed> */
    public function formatGroupForStaff(User $staff, B2BUser $partner): array
    {
        $last = $this->lastGroupMessage($partner->id);
        $unread = $this->groupUnreadCountForStaff($partner->id, $staff->id);

        return [
            'id' => $partner->id,
            'name' => $partner->chat_group_name ?: $partner->name,
            'mobile' => $this->normalizeMobile($partner->mobile),
            'subtitle' => $partner->company_name,
            'initial' => strtoupper(substr($partner->name, 0, 1)),
            'color' => '#F7941D',
            'avatar' => null,
            'unread_count' => $unread,
            'last_message' => $this->previewText($last),
            'last_message_time' => $last?->created_at?->format('h:i A'),
            'last_message_at' => $last?->created_at?->timestamp ?? 0,
            'group_name' => $partner->chat_group_name,
            'members' => $this->groupMembers($partner)->all(),
            'is_group' => true,
        ];
    }

    public function assertCorporatePeer(B2BUser $b2bUser, int $staffUserId): User
    {
        $peer = $b2bUser->chatPeers()->where('users.id', $staffUserId)->first();
        if (! $peer) {
            abort(403, 'Invalid chat contact.');
        }

        return $peer;
    }

    /** @return array<string, mixed> */
    public function formatPeerForCorporate(B2BUser $b2bUser, User $peer): array
    {
        $last = $this->lastDirectMessage($b2bUser->id, $peer->id);
        $unread = $this->directUnreadCountForCorporate($b2bUser->id, $peer->id);

        return [
            'id' => $peer->id,
            'name' => $peer->name,
            'mobile' => $this->normalizeMobile($peer->mobile),
            'subtitle' => ($peer->role?->name ?? 'CRM User').' · Private chat',
            'initial' => strtoupper(substr($peer->f_name ?? $peer->name, 0, 1)),
            'color' => $peer->color ?? '#F7941D',
            'avatar' => $peer->profile_image ? asset('storage/'.$peer->profile_image) : null,
            'unread_count' => $unread,
            'last_message' => $this->previewText($last),
            'last_message_time' => $last?->created_at?->format('h:i A'),
            'last_message_at' => $last?->created_at?->timestamp ?? 0,
            'thread_kind' => self::THREAD_DIRECT,
            'is_group' => false,
            'sidebar_key' => (string) $peer->id,
        ];
    }

    /** @return array<string, mixed> */
    public function formatDirectForStaff(User $staff, B2BUser $partner): array
    {
        $last = $this->lastDirectMessage($partner->id, $staff->id);
        $unread = $this->directUnreadCountForStaff($partner->id, $staff->id);

        return [
            'id' => $partner->id,
            'name' => $partner->name,
            'mobile' => $this->normalizeMobile($partner->mobile),
            'subtitle' => $partner->company_name.' · 1-to-1',
            'initial' => strtoupper(substr($partner->name, 0, 1)),
            'color' => '#F7941D',
            'avatar' => null,
            'unread_count' => $unread,
            'last_message' => $this->previewText($last),
            'last_message_time' => $last?->created_at?->format('h:i A'),
            'last_message_at' => $last?->created_at?->timestamp ?? 0,
            'thread_kind' => self::THREAD_DIRECT,
            'is_group' => false,
            'b2b_user_id' => $partner->id,
        ];
    }

    /** @return Collection<int, array<string, mixed>> */
    public function sidebarContactsForCorporate(B2BUser $b2bUser): Collection
    {
        $group = $this->formatGroupForCorporate($b2bUser);

        $directs = $b2bUser->chatPeers()
            ->orderBy('f_name')
            ->orderBy('l_name')
            ->get()
            ->map(fn (User $peer) => $this->formatPeerForCorporate($b2bUser, $peer));

        return collect([$group])->merge($directs)->values();
    }

    /** @return Collection<int, array<string, mixed>> */
    public function sidebarContactsForStaff(User $staff): Collection
    {
        $items = collect();

        B2BUser::corporate()
            ->where('chat_enabled', true)
            ->whereHas('chatPeers', fn ($q) => $q->where('users.id', $staff->id))
            ->orderBy('name')
            ->get()
            ->each(function (B2BUser $partner) use ($staff, $items) {
                $group = $this->formatGroupForStaff($staff, $partner);
                $group['thread_kind'] = self::THREAD_GROUP;
                $group['sidebar_key'] = 'g-'.$partner->id;
                $group['name'] = $partner->chat_group_name ?: $partner->name;
                $items->push($group);

                $direct = $this->formatDirectForStaff($staff, $partner);
                $direct['sidebar_key'] = 'd-'.$partner->id;
                $items->push($direct);
            });

        return $items->sortByDesc('last_message_at')->values();
    }

    /** @return Collection<int, array<string, mixed>> */
    public function sidebarGroupsForCorporate(B2BUser $b2bUser): Collection
    {
        return $this->sidebarContactsForCorporate($b2bUser);
    }

    /** @return Collection<int, array<string, mixed>> */
    public function sidebarGroupsForStaff(User $staff): Collection
    {
        return $this->sidebarContactsForStaff($staff);
    }

    public function getGroupMessages(int $b2bUserId): Collection
    {
        return B2BChatMessage::query()
            ->with('b2bUser')
            ->where('b2b_user_id', $b2bUserId)
            ->where('thread_type', self::THREAD_GROUP)
            ->orderBy('id')
            ->limit(500)
            ->get()
            ->map(fn (B2BChatMessage $m) => $this->formatMessage($m));
    }

    public function sendGroupMessage(
        int $b2bUserId,
        string $senderType,
        ?int $senderStaffUserId,
        ?string $body = null
    ): B2BChatMessage {
        return B2BChatMessage::create([
            'b2b_user_id' => $b2bUserId,
            'thread_type' => self::THREAD_GROUP,
            'user_id' => null,
            'sender_type' => $senderType,
            'sender_staff_user_id' => $senderStaffUserId,
            'body' => $body,
            'is_read' => false,
        ]);
    }

    public function sendGroupAttachment(
        int $b2bUserId,
        string $senderType,
        ?int $senderStaffUserId,
        UploadedFile $file
    ): B2BChatMessage {
        $path = $file->store('b2b_chat_attachments', 'public');

        return B2BChatMessage::create([
            'b2b_user_id' => $b2bUserId,
            'thread_type' => self::THREAD_GROUP,
            'user_id' => null,
            'sender_type' => $senderType,
            'sender_staff_user_id' => $senderStaffUserId,
            'body' => null,
            'attachment' => $path,
            'attachment_type' => $file->getMimeType(),
            'is_read' => false,
        ]);
    }

    public function markGroupAsRead(int $b2bUserId, string $readerType, ?int $staffUserId = null): void
    {
        $lastId = B2BChatMessage::query()
            ->where('b2b_user_id', $b2bUserId)
            ->where('thread_type', self::THREAD_GROUP)
            ->max('id');

        B2BChatReadCursor::query()->updateOrCreate(
            [
                'b2b_user_id' => $b2bUserId,
                'reader_type' => $readerType,
                'staff_user_id' => $readerType === 'staff' ? $staffUserId : null,
            ],
            ['last_read_message_id' => $lastId]
        );
    }

    public function groupUnreadCountForCorporate(int $b2bUserId): int
    {
        return $this->groupUnreadCount($b2bUserId, 'b2b', null);
    }

    public function groupUnreadCountForStaff(int $b2bUserId, int $staffUserId): int
    {
        return $this->groupUnreadCount($b2bUserId, 'staff', $staffUserId);
    }

    protected function groupUnreadCount(int $b2bUserId, string $readerType, ?int $staffUserId): int
    {
        $cursor = B2BChatReadCursor::query()
            ->where('b2b_user_id', $b2bUserId)
            ->where('reader_type', $readerType)
            ->when($readerType === 'staff', fn ($q) => $q->where('staff_user_id', $staffUserId))
            ->when($readerType === 'b2b', fn ($q) => $q->whereNull('staff_user_id'))
            ->first();

        $afterId = (int) ($cursor?->last_read_message_id ?? 0);

        $query = B2BChatMessage::query()
            ->where('b2b_user_id', $b2bUserId)
            ->where('thread_type', self::THREAD_GROUP)
            ->where('id', '>', $afterId);

        if ($readerType === 'b2b') {
            $query->where('sender_type', '!=', 'b2b');
        } else {
            $query->where(function ($q) use ($staffUserId) {
                $q->where('sender_type', 'b2b')
                    ->orWhere(function ($q2) use ($staffUserId) {
                        $q2->where('sender_type', 'staff')
                            ->where('sender_staff_user_id', '!=', $staffUserId);
                    });
            });
        }

        return (int) $query->count();
    }

    public function getDirectMessages(int $b2bUserId, int $staffUserId): Collection
    {
        return B2BChatMessage::query()
            ->where('b2b_user_id', $b2bUserId)
            ->where('user_id', $staffUserId)
            ->where('thread_type', self::THREAD_DIRECT)
            ->orderBy('id')
            ->limit(300)
            ->get()
            ->map(fn (B2BChatMessage $m) => $this->formatMessage($m));
    }

    public function sendDirectMessage(
        int $b2bUserId,
        int $staffUserId,
        string $senderType,
        ?string $body = null
    ): B2BChatMessage {
        return B2BChatMessage::create([
            'b2b_user_id' => $b2bUserId,
            'thread_type' => self::THREAD_DIRECT,
            'user_id' => $staffUserId,
            'sender_type' => $senderType,
            'sender_staff_user_id' => $senderType === 'staff' ? $staffUserId : null,
            'body' => $body,
            'is_read' => false,
        ]);
    }

    public function sendDirectAttachment(
        int $b2bUserId,
        int $staffUserId,
        string $senderType,
        UploadedFile $file
    ): B2BChatMessage {
        $path = $file->store('b2b_chat_attachments', 'public');

        return B2BChatMessage::create([
            'b2b_user_id' => $b2bUserId,
            'thread_type' => self::THREAD_DIRECT,
            'user_id' => $staffUserId,
            'sender_type' => $senderType,
            'sender_staff_user_id' => $senderType === 'staff' ? $staffUserId : null,
            'body' => null,
            'attachment' => $path,
            'attachment_type' => $file->getMimeType(),
            'is_read' => false,
        ]);
    }

    public function markDirectAsRead(int $b2bUserId, int $staffUserId, string $readerType): void
    {
        $senderToMark = $readerType === 'b2b' ? 'staff' : 'b2b';

        B2BChatMessage::query()
            ->where('b2b_user_id', $b2bUserId)
            ->where('user_id', $staffUserId)
            ->where('thread_type', self::THREAD_DIRECT)
            ->where('sender_type', $senderToMark)
            ->where('is_read', false)
            ->update(['is_read' => true]);
    }

    public function directUnreadCountForCorporate(int $b2bUserId, int $staffUserId): int
    {
        return (int) B2BChatMessage::query()
            ->where('b2b_user_id', $b2bUserId)
            ->where('user_id', $staffUserId)
            ->where('thread_type', self::THREAD_DIRECT)
            ->where('sender_type', 'staff')
            ->where('is_read', false)
            ->count();
    }

    public function directUnreadCountForStaff(int $b2bUserId, int $staffUserId): int
    {
        return (int) B2BChatMessage::query()
            ->where('b2b_user_id', $b2bUserId)
            ->where('user_id', $staffUserId)
            ->where('thread_type', self::THREAD_DIRECT)
            ->where('sender_type', 'b2b')
            ->where('is_read', false)
            ->count();
    }

    public function corporateUnreadCounts(B2BUser $b2bUser): array
    {
        $counts = ['group' => $this->groupUnreadCountForCorporate($b2bUser->id)];
        foreach ($b2bUser->chatPeers as $peer) {
            $counts[$peer->id] = $this->directUnreadCountForCorporate($b2bUser->id, $peer->id);
        }

        return $counts;
    }

    public function staffUnreadCounts(User $staff): array
    {
        $counts = [];
        foreach ($this->sidebarContactsForStaff($staff) as $row) {
            $key = $row['sidebar_key'] ?? (string) $row['id'];
            $counts[$key] = $row['unread_count'];
        }

        return $counts;
    }

    public function initiateCallFromCorporate(B2BUser $b2bUser, int $staffUserId): array
    {
        $staff = $this->assertCorporatePeer($b2bUser, $staffUserId);

        $staffMobile = $this->normalizeMobile($staff->mobile);
        if (! $staffMobile) {
            return ['success' => false, 'message' => 'This CRM user has no mobile number on file.'];
        }

        $corporateMobile = $this->normalizeMobile($b2bUser->mobile);
        if (! $corporateMobile) {
            return ['success' => false, 'message' => 'Your account has no mobile number. Ask admin to add your mobile for IVR calls.'];
        }

        return OutboundCall::call($staffMobile, 'b2b_corp_'.$b2bUser->id, $staff, $corporateMobile);
    }

    public function initiateCallFromStaff(User $staff, int $b2bUserId): array
    {
        $partner = $this->assertStaffPartner($staff, $b2bUserId);
        $partnerMobile = $this->normalizeMobile($partner->mobile);
        if (! $partnerMobile) {
            return ['success' => false, 'message' => 'This corporate partner has no mobile number on file.'];
        }

        return OutboundCall::call($partnerMobile, 'b2b_corp_'.$partner->id, $staff);
    }

    public function messagePayload(B2BChatMessage $m): array
    {
        $m->loadMissing('b2bUser');

        return $this->formatMessage($m);
    }

    protected function formatMessage(B2BChatMessage $m): array
    {
        $senderName = null;
        if ($m->thread_type === self::THREAD_GROUP) {
            if ($m->sender_type === 'b2b') {
                $senderName = $m->b2bUser?->name ?? 'Corporate';
            } elseif ($m->sender_staff_user_id) {
                $staff = User::query()->find($m->sender_staff_user_id);
                $senderName = $staff?->name ?? 'CRM User';
            }
        }

        $attachmentPath = $m->attachment ? ltrim((string) $m->attachment, '/') : null;
        $attachmentUrl = $attachmentPath ? asset('storage/'.$attachmentPath) : null;

        return [
            'id' => $m->id,
            'body' => $m->body,
            'sender_type' => $m->sender_type,
            'sender_staff_user_id' => $m->sender_staff_user_id,
            'sender_name' => $senderName,
            'is_read' => (bool) $m->is_read,
            'attachment' => $attachmentPath,
            'attachment_url' => $attachmentUrl,
            'attachment_name' => $attachmentPath ? basename($attachmentPath) : null,
            'attachment_type' => $m->attachment_type,
            'created_at' => $m->created_at?->toIso8601String(),
            'time_label' => $m->created_at?->format('h:i A'),
        ];
    }

    protected function previewText(?B2BChatMessage $message): ?string
    {
        if (! $message) {
            return null;
        }
        if ($message->attachment) {
            $label = $this->attachmentLabel($message->attachment_type);
            if ($message->thread_type === self::THREAD_GROUP && $message->sender_type === 'staff') {
                $staff = User::query()->find($message->sender_staff_user_id);

                return ($staff?->name ? $staff->name.': ' : '').$label;
            }

            return $label;
        }

        if ($message->thread_type === self::THREAD_GROUP && $message->sender_type === 'staff') {
            $staff = User::query()->find($message->sender_staff_user_id);

            return ($staff?->name ? $staff->name.': ' : '').$message->body;
        }

        return $message->body;
    }

    protected function attachmentLabel(?string $mime): string
    {
        if (! $mime) {
            return '📎 Attachment';
        }
        if (str_starts_with($mime, 'image/')) {
            return '📷 Photo';
        }
        if (str_starts_with($mime, 'video/')) {
            return '🎬 Video';
        }

        return '📎 File';
    }

    protected function normalizeMobile(?string $mobile): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $mobile);

        return strlen($digits) === 10 ? $digits : (strlen($digits) > 10 ? substr($digits, -10) : null);
    }

    protected function lastGroupMessage(int $b2bUserId): ?B2BChatMessage
    {
        return B2BChatMessage::query()
            ->where('b2b_user_id', $b2bUserId)
            ->where('thread_type', self::THREAD_GROUP)
            ->orderByDesc('id')
            ->first();
    }

    protected function lastDirectMessage(int $b2bUserId, int $staffUserId): ?B2BChatMessage
    {
        return B2BChatMessage::query()
            ->where('b2b_user_id', $b2bUserId)
            ->where('user_id', $staffUserId)
            ->where('thread_type', self::THREAD_DIRECT)
            ->orderByDesc('id')
            ->first();
    }
}
