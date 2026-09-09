<?php

namespace App\Support;

use App\Events\DoctorCustomerChatMessageCreated;
use App\Events\DoctorCustomerChatUnreadRefresh;
use App\Models\CustomerChatMessage;
use Illuminate\Support\Facades\Log;

/**
 * Stable public channel slug for Laravel Echo / Reverb (no private auth — portals use session, not Laravel users).
 */
final class DoctorCustomerChatRealtime
{
    public const CHANNEL_PREFIX = 'doctor-customer.';

    public static function threadSignature(int $doctorRequestId, int $operationLeadId): string
    {
        return hash_hmac('sha256', "dc-chat|{$doctorRequestId}|{$operationLeadId}", self::broadcastSalt());
    }

    public static function channelName(int $doctorRequestId, int $operationLeadId): string
    {
        return self::CHANNEL_PREFIX.self::threadSignature($doctorRequestId, $operationLeadId);
    }

    /**
     * @return array{0: int, 1: int}|null doctor_request_id, operation_lead_id message row uses for customer
     */
    public static function pairFromMessage(\App\Models\CustomerChatMessage $m): ?array
    {
        if ($m->sender_type === 'doctor' && $m->receiver_type === 'customer') {
            return [(int) $m->sender_id, (int) $m->receiver_id];
        }
        if ($m->sender_type === 'customer' && $m->receiver_type === 'doctor') {
            return [(int) $m->receiver_id, (int) $m->sender_id];
        }

        return null;
    }

    /** @deprecated Use threadSignature — alias for Blade clarity */
    public static function sidebarSignature(int $doctorRequestId, int $operationLeadId): string
    {
        return self::threadSignature($doctorRequestId, $operationLeadId);
    }

    /**
     * Never fail HTTP / saves when Reverb/Pusher returns non-JSON (wrong host/port, server down).
     */
    public static function broadcastMessageCreated(CustomerChatMessage $message): void
    {
        if (self::pairFromMessage($message) === null) {
            return;
        }
        try {
            event(new DoctorCustomerChatMessageCreated($message));
        } catch (\Throwable $e) {
            Log::warning('doctor_customer_chat.broadcast.message_failed', [
                'message_id' => $message->id,
                'exception' => $e->getMessage(),
            ]);
        }
    }

    public static function broadcastUnreadRefresh(int $doctorRequestId, int $representativeOperationLeadId, string $reason = 'mark_read'): void
    {
        try {
            event(new DoctorCustomerChatUnreadRefresh($doctorRequestId, $representativeOperationLeadId, $reason));
        } catch (\Throwable $e) {
            Log::warning('doctor_customer_chat.broadcast.unread_failed', [
                'doctor_request_id' => $doctorRequestId,
                'operation_lead_id' => $representativeOperationLeadId,
                'exception' => $e->getMessage(),
            ]);
        }
    }

    private static function broadcastSalt(): string
    {
        return (string) config('app.key');
    }
}
