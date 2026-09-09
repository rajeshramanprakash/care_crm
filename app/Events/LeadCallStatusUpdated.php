<?php

namespace App\Events;

use App\Models\Lead;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LeadCallStatusUpdated implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    public function __construct(public Lead $lead)
    {
    }

    public function broadcastOn(): array
    {
        $execId = (int) $this->lead->executive;
        if ($execId < 1) {
            return [];
        }

        return [
            new PrivateChannel('App.Models.User.'.$execId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'lead.call.status';
    }

    public function broadcastWith(): array
    {
        $l = $this->lead;

        return [
            'lead_id' => $l->id,
            'executive_id' => (int) $l->executive,
            'last_call_status' => $l->last_call_status,
            'updated_at' => $l->updated_at?->toIso8601String(),
        ];
    }
}
