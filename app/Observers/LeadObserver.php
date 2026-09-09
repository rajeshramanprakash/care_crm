<?php

namespace App\Observers;

use App\Events\LeadCallStatusUpdated;
use App\Jobs\BroadcastFutureProspectReminderJob;
use App\Models\B2BLead;
use App\Models\DoctorPortalLead;
use App\Models\Lead;
use App\Models\SalesReferralLead;
use App\Support\SafeBroadcast;

class LeadObserver
{
    public function deleting(Lead $lead): void
    {
        DoctorPortalLead::query()
            ->where('lead_id', $lead->id)
            ->delete();

        B2BLead::query()
            ->where('lead_id', $lead->id)
            ->delete();

        SalesReferralLead::query()
            ->where('lead_id', $lead->id)
            ->delete();
    }

    public function updating(Lead $lead): void
    {
        if ($lead->isDirty('status') && ! in_array($lead->status, ['future prospect', 'follow-up'], true)) {
            $lead->future_prospect_reminder_at = null;
        }

        if ($lead->isDirty('status') && in_array($lead->status, ['future prospect', 'follow-up'], true)) {
            $lead->future_prospect_reminder_at = null;
        }

        if ($lead->status === 'future prospect' && $lead->isDirty('future_prospect_date')) {
            $lead->future_prospect_reminder_at = null;
        }
        if ($lead->status === 'follow-up' && $lead->isDirty('follow_up_date')) {
            $lead->future_prospect_reminder_at = null;
        }

        if ($lead->isDirty('executive')) {
            $lead->future_prospect_reminder_at = null;
        }

        if ($lead->isDirty('stage') && in_array($lead->stage, ['inactive', 'closed'], true)) {
            $lead->future_prospect_reminder_at = null;
        }
    }

    public function created(Lead $lead): void
    {
        BroadcastFutureProspectReminderJob::scheduleForLeadIfNeeded($lead, true);
    }

    public function updated(Lead $lead): void
    {
        if ($lead->wasChanged('last_call_status') && $lead->executive) {
            // Realtime should never break persistence. If websocket/pusher is down,
            // swallow and log instead of failing the request with 500.
            SafeBroadcast::toOthers(new LeadCallStatusUpdated($lead));
        }

        if ($lead->wasChanged(['status', 'future_prospect_date', 'follow_up_date', 'executive'])) {
            $fromScheduledFieldWrite = $lead->wasChanged(['future_prospect_date', 'follow_up_date']);
            BroadcastFutureProspectReminderJob::scheduleForLeadIfNeeded($lead, $fromScheduledFieldWrite);
        }
    }
}
