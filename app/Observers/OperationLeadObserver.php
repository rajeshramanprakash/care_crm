<?php

namespace App\Observers;

use App\Events\OperationLeadCallStatusUpdated;
use App\Jobs\BroadcastOperationFutureProspectReminderJob;
use App\Models\OperationLead;

class OperationLeadObserver
{
    public function updating(OperationLead $operationLead): void
    {
        if ($operationLead->isDirty('status') && ! in_array($operationLead->status, ['future prospect', 'follow up'], true)) {
            $operationLead->future_prospect_reminder_at = null;
        }

        if ($operationLead->isDirty('status') && in_array($operationLead->status, ['future prospect', 'follow up'], true)) {
            $operationLead->future_prospect_reminder_at = null;
        }

        if ($operationLead->status === 'future prospect' && $operationLead->isDirty('future_prospect_date')) {
            $operationLead->future_prospect_reminder_at = null;
        }
        if ($operationLead->status === 'follow up' && $operationLead->isDirty('follow_up_date')) {
            $operationLead->future_prospect_reminder_at = null;
        }

        if ($operationLead->isDirty('executive')) {
            $operationLead->future_prospect_reminder_at = null;
        }
    }

    public function created(OperationLead $operationLead): void
    {
        BroadcastOperationFutureProspectReminderJob::scheduleForOperationLeadIfNeeded($operationLead, true);
    }

    public function updated(OperationLead $operationLead): void
    {
        if ($operationLead->wasChanged('last_call_status') && $operationLead->executive) {
            broadcast(new OperationLeadCallStatusUpdated($operationLead));
        }

        if ($operationLead->wasChanged(['status', 'future_prospect_date', 'follow_up_date', 'executive'])) {
            $fromScheduledFieldWrite = $operationLead->wasChanged(['future_prospect_date', 'follow_up_date']);
            BroadcastOperationFutureProspectReminderJob::scheduleForOperationLeadIfNeeded($operationLead, $fromScheduledFieldWrite);
        }
    }
}
