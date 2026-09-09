<?php

namespace App\Jobs;

use App\Events\OperationFutureProspectReminderDue;
use App\Models\OperationLead;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class BroadcastOperationFutureProspectReminderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public OperationLead $operationLead,
        public int $targetFutureProspectTimestamp
    ) {
    }

    /**
     * @param  bool  $fromScheduledFieldWrite  True when triggered by create or by future_prospect_date / follow_up_date change.
     */
    public static function scheduleForOperationLeadIfNeeded(OperationLead $operationLead, bool $fromScheduledFieldWrite = false): void
    {
        $dueDate = static::resolveReminderDueDate($operationLead);
        if (! $dueDate || ! $operationLead->executive) {
            return;
        }

        if ($operationLead->future_prospect_reminder_at
            && $operationLead->future_prospect_reminder_at->timestamp === $dueDate->timestamp) {
            return;
        }

        $ts = $dueDate->timestamp;
        $queue = config('queue.default');

        if ($dueDate->isFuture()) {
            if (in_array($queue, ['sync', 'null'], true)) {
                return;
            }

            static::dispatch($operationLead, $ts)->delay($dueDate)->afterCommit();

            return;
        }

        if ($fromScheduledFieldWrite && $dueDate->lt(Carbon::now())) {
            return;
        }

        static::dispatch($operationLead, $ts)->afterCommit();
    }

    public function handle(): void
    {
        $operationLead = $this->operationLead->fresh();
        $dueDate = static::resolveReminderDueDate($operationLead);
        if (! $operationLead || ! $dueDate || ! $operationLead->executive) {
            return;
        }

        if ($dueDate->isFuture()) {
            return;
        }

        if (! $operationLead->isScheduledContactReminderDueThisAppDay()) {
            return;
        }

        if (abs($dueDate->timestamp - $this->targetFutureProspectTimestamp) > 120) {
            return;
        }

        if ($operationLead->future_prospect_reminder_at
            && abs($operationLead->future_prospect_reminder_at->timestamp - $dueDate->timestamp) <= 120) {
            return;
        }

        try {
            broadcast(new OperationFutureProspectReminderDue($operationLead));
        } catch (Throwable $e) {
            Log::warning('Operation future prospect reminder broadcast failed', [
                'operation_lead_id' => $operationLead->id,
                'executive' => $operationLead->executive,
                'error' => $e->getMessage(),
            ]);
        }

        $operationLead->forceFill([
            'future_prospect_reminder_at' => $dueDate->copy(),
        ])->saveQuietly();
    }

    private static function resolveReminderDueDate(?OperationLead $operationLead)
    {
        if (! $operationLead) {
            return null;
        }

        if ($operationLead->status === 'future prospect' && $operationLead->future_prospect_date) {
            return $operationLead->future_prospect_date;
        }

        if ($operationLead->status === 'follow up' && $operationLead->follow_up_date) {
            return $operationLead->follow_up_date;
        }

        return null;
    }
}
