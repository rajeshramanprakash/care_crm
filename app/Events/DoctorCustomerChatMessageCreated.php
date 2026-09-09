<?php

namespace App\Events;

use App\Models\CustomerChatMessage;
use App\Models\OperationLead;
use App\Support\DoctorCustomerChatRealtime;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DoctorCustomerChatMessageCreated implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    public function __construct(public CustomerChatMessage $message)
    {
    }

    public function broadcastWhen(): bool
    {
        return DoctorCustomerChatRealtime::pairFromMessage($this->message) !== null;
    }

    /**
     * One thread per (doctor, operation_lead); same contact may map to several lead rows — fan out so all listeners receive.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        $pair = DoctorCustomerChatRealtime::pairFromMessage($this->message);
        if ($pair === null) {
            return [];
        }
        [$doctorId, $leadId] = $pair;

        $leadIds = [(int) $leadId];
        $ol = OperationLead::find($leadId);
        if ($ol && $ol->contact_no) {
            $leadIds = array_values(array_unique(array_merge(
                $leadIds,
                OperationLead::where('contact_no', $ol->contact_no)->pluck('id')->map(fn ($id) => (int) $id)->all()
            )));
        }

        $channels = [];
        foreach ($leadIds as $id) {
            if ($id <= 0) {
                continue;
            }
            $channels[] = new Channel(DoctorCustomerChatRealtime::channelName($doctorId, $id));
        }

        return $channels;
    }

    public function broadcastAs(): string
    {
        return 'doctor.customer.message';
    }

    public function broadcastWith(): array
    {
        $m = $this->message->fresh(['repliedTo']);
        if (! $m) {
            return ['type' => 'message'];
        }

        return [
            'type' => 'message',
            'doctor_request_id' => $m->sender_type === 'doctor' ? (int) $m->sender_id : (int) $m->receiver_id,
            'operation_lead_id_sender' => $m->sender_type === 'customer' ? (int) $m->sender_id : null,
            'operation_lead_id_receiver' => $m->receiver_type === 'customer' ? (int) $m->receiver_id : null,
            'message' => [
                'id' => $m->id,
                'message' => $m->message,
                'sender_type' => $m->sender_type,
                'sender_id' => (int) $m->sender_id,
                'receiver_type' => $m->receiver_type,
                'receiver_id' => (int) $m->receiver_id,
                'attachment' => $m->attachment,
                'attachment_type' => $m->attachment_type,
                'is_read' => (bool) $m->is_read,
                'reply_to_id' => $m->reply_to_id ? (int) $m->reply_to_id : null,
                'created_at' => $m->created_at->toISOString(),
                'replied_to' => $m->relationLoaded('repliedTo') && $m->repliedTo ? [
                    'id' => $m->repliedTo->id,
                    'message' => $m->repliedTo->message,
                    'attachment' => $m->repliedTo->attachment,
                    'sender_type' => $m->repliedTo->sender_type,
                ] : null,
            ],
        ];
    }
}
