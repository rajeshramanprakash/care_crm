<?php

namespace App\Support;

use App\Models\SupportTicket;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * Sends customer bug / technical tickets to Ashniva Desk (POST /support/tickets) and reads back status and
 * public replies (GET /support/tickets/:id). The ticket always stays in Carelix too; a Desk failure never
 * blocks the customer — it is recorded in desk_error and retried by `desk:sync` or the Admin button.
 */
class DeskClient
{
    public static function configured(): bool
    {
        return filled(config('services.desk.key')) && filled(config('services.desk.url'));
    }

    public static function push(SupportTicket $ticket): bool
    {
        if (! $ticket->isBug() || $ticket->desk_ticket_id) {
            return (bool) $ticket->desk_ticket_id;
        }
        if (! static::configured()) {
            static::fail($ticket, 'Desk is not connected (DESK_API_KEY missing in .env).');

            return false;
        }

        $first = $ticket->messages()->orderBy('id')->first();
        $description = trim((string) optional($first)->message) . "\n\n"
            . "— Carelix ticket {$ticket->ticket_no}\n"
            . "Customer: {$ticket->customer_name} ({$ticket->customer_contact_no})\n"
            . 'Priority: ' . $ticket->priority_label
            . (optional($first)->attachment_path ? "\nAttachment: {$first->attachment_name} (open the ticket in Carelix Admin)" : '');

        $payload = array_filter([
            'title' => $ticket->subject,
            'description' => $description,
            'module' => config('services.desk.module'),
            'externalUserId' => 'carelix-customer-' . $ticket->customer_contact_no,
            'externalReference' => $ticket->ticket_no,
        ], fn ($v) => $v !== null && $v !== '');

        try {
            $response = static::http()
                ->withHeaders(['Idempotency-Key' => 'carelix-' . $ticket->ticket_no])
                ->post('/support/tickets', $payload);
        } catch (Throwable $e) {
            static::fail($ticket, 'Could not reach Desk: ' . $e->getMessage());

            return false;
        }

        if (! $response->successful()) {
            static::fail($ticket, 'Desk HTTP ' . $response->status() . ': ' . static::errorMessage($response->json(), $response->body()));

            return false;
        }

        $data = static::ticketData($response->json());
        $deskId = static::first($data, ['id', 'ticketId', 'uuid']);
        if (! $deskId) {
            static::fail($ticket, 'Desk accepted the request but returned no ticket id.');

            return false;
        }

        $ticket->forceFill([
            'desk_ticket_id' => (string) $deskId,
            'desk_reference' => static::first($data, ['number', 'key', 'reference', 'ticketNumber', 'code', 'displayId']),
            'desk_status' => static::first($data, ['status', 'state']),
            'desk_error' => null,
            'desk_sent_at' => now(),
            'desk_synced_at' => now(),
        ])->saveQuietly();

        return true;
    }

    /** Pull status and public replies from Desk. Desk replies show to the customer as "Carelix Support". */
    public static function refresh(SupportTicket $ticket): bool
    {
        if (! $ticket->desk_ticket_id || ! static::configured()) {
            return false;
        }

        try {
            $response = static::http()->timeout(5)->get('/support/tickets/' . rawurlencode($ticket->desk_ticket_id));
        } catch (Throwable $e) {
            Log::warning('Desk refresh failed', ['ticket' => $ticket->ticket_no, 'error' => $e->getMessage()]);
            $ticket->forceFill(['desk_synced_at' => now()])->saveQuietly();

            return false;
        }
        if (! $response->successful()) {
            Log::warning('Desk refresh failed', ['ticket' => $ticket->ticket_no, 'status' => $response->status()]);

            return false;
        }

        $json = $response->json();
        $data = static::ticketData($json);
        $replies = static::replies($json, $data);

        DB::transaction(function () use ($ticket, $data, $replies) {
            $known = $ticket->messages()->whereNotNull('desk_reply_id')->pluck('desk_reply_id')->all();
            $added = 0;
            foreach ($replies as $reply) {
                $replyId = static::first($reply, ['id', 'uuid']);
                $body = static::first($reply, ['body', 'message', 'content', 'text', 'bodyText']);
                if (! $replyId || ! is_string($body) || trim($body) === '' || in_array((string) $replyId, $known, true)) {
                    continue;
                }
                $ticket->messages()->create([
                    'sender_type' => 'desk',
                    'sender_name' => 'Ashniva Desk',
                    'message' => Str::limit(trim(strip_tags($body)), 5000, ''),
                    'desk_reply_id' => (string) $replyId,
                ]);
                $added++;
            }

            $status = static::first($data, ['status', 'state']);
            $ticket->desk_status = $status ?: $ticket->desk_status;
            $ticket->desk_synced_at = now();
            if ($added) {
                $ticket->last_reply_by = 'staff';
                $ticket->last_reply_at = now();
                $ticket->unread_for_customer = true;
                if ($ticket->status === 'open') {
                    $ticket->status = 'in_progress';
                }
            }
            if ($status && in_array(strtoupper((string) $status), ['RESOLVED', 'CLOSED', 'DONE'], true) && ! in_array($ticket->status, ['resolved', 'closed'], true)) {
                SupportTicketService::applyStatus($ticket, 'resolved');
            }
            $ticket->saveQuietly();
        });

        return true;
    }

    private static function http()
    {
        return Http::baseUrl((string) config('services.desk.url'))
            ->withToken((string) config('services.desk.key'))
            ->acceptJson()
            ->asJson()
            ->timeout(max(3, (int) config('services.desk.timeout', 10)));
    }

    private static function fail(SupportTicket $ticket, string $error): void
    {
        $ticket->forceFill(['desk_error' => Str::limit($error, 490)])->saveQuietly();
        Log::warning('Desk push failed', ['ticket' => $ticket->ticket_no, 'error' => $error]);
    }

    private static function ticketData($json): array
    {
        if (! is_array($json)) {
            return [];
        }
        foreach (['ticket', 'data.ticket', 'data'] as $path) {
            $candidate = Arr::get($json, $path);
            if (is_array($candidate) && Arr::isAssoc($candidate)) {
                return $candidate;
            }
        }

        return $json;
    }

    private static function replies($json, array $data): array
    {
        foreach ([$data, is_array($json) ? $json : []] as $source) {
            foreach (['publicReplies', 'replies', 'comments', 'messages'] as $key) {
                if (isset($source[$key]) && is_array($source[$key]) && array_is_list($source[$key])) {
                    return array_filter($source[$key], 'is_array');
                }
            }
        }

        return [];
    }

    private static function first(array $data, array $keys)
    {
        foreach ($keys as $key) {
            if (isset($data[$key]) && $data[$key] !== '' && ! is_array($data[$key])) {
                return $data[$key];
            }
        }

        return null;
    }

    private static function errorMessage($json, string $body): string
    {
        $message = is_array($json) ? ($json['message'] ?? $json['error'] ?? null) : null;
        if (is_array($message)) {
            $message = implode('; ', array_map(fn ($m) => is_scalar($m) ? (string) $m : json_encode($m), $message));
        }

        return Str::limit((string) ($message ?: $body), 300);
    }
}
