<?php

namespace App\Events;

use App\Models\Lead;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Support\Str;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class FutureProspectReminderDue implements ShouldBroadcastNow
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
        return 'future.prospect.reminder';
    }

    public function broadcastWith(): array
    {
        $l = $this->lead;
        $isFollowUpReminder = Lead::isSalesFollowUpStatus($l->status) && ! is_null($l->follow_up_date);
        $dueAt = $isFollowUpReminder ? $l->follow_up_date : $l->future_prospect_date;
        $reminderType = $isFollowUpReminder ? 'follow_up' : 'future_prospect';

        return [
            'lead_id' => $l->id,
            'executive_id' => (int) $l->executive,
            'lead_code' => $l->formatted_id,
            'customer_name' => $l->customer_name,
            'patient_name' => $l->patient_name,
            'patient_gender' => $l->patient_gender,
            'age' => $l->age,
            'contact_no' => $l->contact_no,
            'contact_type' => $l->contact_type,
            'query' => $l->query,
            'query_remarks' => $l->query_remarks ? Str::limit((string) $l->query_remarks, 400) : null,
            'location' => $l->location,
            'lead_source' => $l->lead_source,
            'stage' => $l->stage,
            'status' => $l->status,
            'reminder_type' => $reminderType,
            'reminder_due_at' => $dueAt?->toIso8601String(),
            'reminder_due_display' => $dueAt
                ? $dueAt->copy()->timezone('Asia/Kolkata')->format('d-m-Y H:i')
                : null,
            'follow_up_date' => $l->follow_up_date?->toIso8601String(),
            'follow_up_date_display' => $l->follow_up_date
                ? $l->follow_up_date->copy()->timezone('Asia/Kolkata')->format('d-m-Y H:i')
                : null,
            'future_prospect_date' => $l->future_prospect_date?->toIso8601String(),
            'future_prospect_date_display' => $l->future_prospect_date
                ? $l->future_prospect_date->copy()->timezone('Asia/Kolkata')->format('d-m-Y H:i')
                : null,
            'last_call_status' => $l->last_call_status,
        ];
    }
}
