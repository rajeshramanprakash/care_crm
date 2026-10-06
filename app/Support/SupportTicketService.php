<?php

namespace App\Support;

use App\Models\SupportTicket;
use App\Models\SupportTicketMessage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SupportTicketService
{
    public const ATTACHMENT_RULE = 'nullable|file|max:5120|mimes:jpg,jpeg,png,webp,pdf,doc,docx,txt';

    public const ATTACHMENT_DIR = 'support_attachments';

    public static function open(array $customer, array $data, ?UploadedFile $file): SupportTicket
    {
        $ticket = DB::transaction(function () use ($customer, $data, $file) {
            $ticket = SupportTicket::create([
                'ticket_no' => SupportTicket::nextTicketNo(),
                'customer_contact_no' => $customer['contact_no'],
                'customer_type' => $customer['type'],
                'customer_ref_id' => (int) $customer['id'] > 0 ? (int) $customer['id'] : null,
                'customer_name' => $customer['name'],
                'type' => $data['type'],
                'category' => $data['category'],
                'subject' => $data['subject'],
                'priority' => $data['priority'] ?? 'normal',
                'status' => 'open',
                'last_reply_by' => 'customer',
                'last_reply_at' => now(),
                'unread_for_staff' => true,
                'unread_for_customer' => false,
            ]);

            static::addMessage($ticket, 'customer', null, $customer['name'], $data['message'], $file);

            return $ticket;
        });

        if ($ticket->isBug()) {
            DeskClient::push($ticket);
        }

        return $ticket;
    }

    public static function addMessage(SupportTicket $ticket, string $senderType, ?int $userId, ?string $name, string $message, ?UploadedFile $file): SupportTicketMessage
    {
        $path = null;
        $originalName = null;
        if ($file) {
            $originalName = Str::limit($file->getClientOriginalName(), 200, '');
            $path = $file->storeAs(
                self::ATTACHMENT_DIR . '/' . $ticket->id,
                Str::random(32) . '.' . strtolower($file->getClientOriginalExtension()),
                'local'
            );
        }

        return $ticket->messages()->create([
            'sender_type' => $senderType,
            'sender_user_id' => $userId,
            'sender_name' => $name,
            'message' => $message,
            'attachment_path' => $path,
            'attachment_name' => $originalName,
        ]);
    }

    public static function download(SupportTicketMessage $message)
    {
        abort_unless($message->attachment_path && Storage::disk('local')->exists($message->attachment_path), 404);

        return Storage::disk('local')->download($message->attachment_path, $message->attachment_name ?: basename($message->attachment_path));
    }

    /** Staff side reply / status update. */
    public static function staffReply(SupportTicket $ticket, $user, string $message, ?UploadedFile $file, ?string $status): void
    {
        DB::transaction(function () use ($ticket, $user, $message, $file, $status) {
            static::addMessage($ticket, 'staff', (int) $user->id, trim($user->f_name . ' ' . $user->l_name), $message, $file);

            $ticket->last_reply_by = 'staff';
            $ticket->last_reply_at = now();
            $ticket->unread_for_customer = true;
            $ticket->unread_for_staff = false;
            static::applyStatus($ticket, $status ?: ($ticket->status === 'open' ? 'in_progress' : $ticket->status));
            $ticket->save();
        });
    }

    public static function applyStatus(SupportTicket $ticket, string $status): void
    {
        if (! array_key_exists($status, SupportTicket::STATUSES)) {
            return;
        }
        $ticket->status = $status;
        $ticket->resolved_at = $status === 'resolved' ? ($ticket->resolved_at ?? now()) : ($status === 'closed' ? $ticket->resolved_at : null);
        $ticket->closed_at = $status === 'closed' ? now() : null;
    }
}
