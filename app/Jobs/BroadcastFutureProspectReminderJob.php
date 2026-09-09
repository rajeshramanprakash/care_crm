<?php

namespace App\Jobs;

use App\Events\FutureProspectReminderDue;
use App\Models\Lead;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class BroadcastFutureProspectReminderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public Lead $lead,
        public int $targetFutureProspectTimestamp
    ) {
    }

    /**
     * Queue a reminder at the lead's future_prospect_date (exact wall time), or run immediately if already due.
     */
    /**
     * @param  bool  $fromScheduledFieldWrite  True when this call is triggered by a create or by a change to
     *                                         future_prospect_date / follow_up_date (user wrote the slot now).
     */
    public static function scheduleForLeadIfNeeded(Lead $lead, bool $fromScheduledFieldWrite = false): void
    {
        $dueDate = static::resolveReminderDueDate($lead);
        if (! $dueDate || ! $lead->executive) {
            return;
        }

        if ($lead->future_prospect_reminder_at
            && $lead->future_prospect_reminder_at->timestamp === $dueDate->timestamp) {
            return;
        }

        $ts = $dueDate->timestamp;
        $queue = config('queue.default');

        if ($dueDate->isFuture()) {
            // sync (and null) drivers ignore delay — job would run immediately and cannot fire at the chosen time.
            // Those setups rely on `leads:dispatch-future-prospect-reminders` (scheduler) or the sales UI tick (see LeadController).
            if (in_array($queue, ['sync', 'null'], true)) {
                return;
            }

            static::dispatch($lead, $ts)->delay($dueDate)->afterCommit();

            return;
        }

        // Do not fire an immediate realtime reminder when the user just saved a wall time that is
        // already behind "now" (e.g. set 10:09 while server clock is 10:10). Poll/scheduler can still surface it if desired.
        if ($fromScheduledFieldWrite && $dueDate->lt(Carbon::now())) {
            return;
        }

        static::dispatch($lead, $ts)->afterCommit();
    }

    public function handle(): void
    {
        $lead = $this->lead->fresh();
        $dueDate = static::resolveReminderDueDate($lead);
        if (! $lead || ! $dueDate || ! $lead->executive) {
            return;
        }

        if ($dueDate->isFuture()) {
            return;
        }

        if (! $lead->isScheduledSalesContactReminderDueThisAppDay()) {
            return;
        }

        // Allow small drift (DB rounding / timezone) so the job does not silently no-op.
        if (abs($dueDate->timestamp - $this->targetFutureProspectTimestamp) > 120) {
            return;
        }

        if ($lead->future_prospect_reminder_at
            && abs($lead->future_prospect_reminder_at->timestamp - $dueDate->timestamp) <= 120) {
            return;
        }

        // Realtime broadcast should never break persistence.
        // If Pusher/Reverb is down, swallow errors and continue.
        try {
            broadcast(new FutureProspectReminderDue($lead));
        } catch (Throwable $e) {
            Log::warning('Future prospect reminder broadcast failed', [
                'lead_id' => $lead->id,
                'error' => $e->getMessage(),
            ]);
        }

        $lead->forceFill([
            'future_prospect_reminder_at' => $dueDate->copy(),
        ])->saveQuietly();
    }

    private static function resolveReminderDueDate(?Lead $lead)
    {
        if (! $lead) {
            return null;
        }

        if (Lead::isSalesFutureProspectStatus($lead->status) && $lead->future_prospect_date) {
            return $lead->future_prospect_date;
        }

        if (Lead::isSalesFollowUpStatus($lead->status) && $lead->follow_up_date) {
            return $lead->follow_up_date;
        }

        return null;
    }
}
