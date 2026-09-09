<?php

namespace App\Events;

use App\Models\OperationLead;
use App\Support\DoctorCustomerChatRealtime;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

class DoctorCustomerChatUnreadRefresh implements ShouldBroadcastNow
{
    use Dispatchable;

    public function __construct(
        public int $doctorRequestId,
        public int $representativeOperationLeadId,
        public string $reason = 'mark_read'
    ) {
    }

    /**
     * Fan out like DoctorCustomerChatMessageCreated so sibling operation_leads (same phone) receive unread refresh.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        $leadIds = [(int) $this->representativeOperationLeadId];
        $ol = OperationLead::find($this->representativeOperationLeadId);
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
            $channels[] = new Channel(DoctorCustomerChatRealtime::channelName(
                $this->doctorRequestId,
                $id
            ));
        }

        return $channels;
    }

    public function broadcastAs(): string
    {
        return 'doctor.customer.unread';
    }

    public function broadcastWith(): array
    {
        return [
            'type' => 'unread_refresh',
            'reason' => $this->reason,
            'doctor_request_id' => $this->doctorRequestId,
            'representative_operation_lead_id' => $this->representativeOperationLeadId,
        ];
    }
}
