<?php

namespace App\Console\Commands;

use App\Events\FutureProspectReminderDue;
use App\Events\OperationFutureProspectReminderDue;
use App\Models\Lead;
use App\Models\OperationLead;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

class DispatchFutureProspectReminders extends Command
{
    protected $signature = 'leads:dispatch-future-prospect-reminders';

    protected $description = 'Fallback: broadcast missed future-prospect reminders for CRM leads and operation leads (queue/Reverb)';

    public function handle(): int
    {
        $count = 0;

        Lead::query()
            ->whereRaw("LOWER(TRIM(status)) IN ('future prospect', 'follow-up')")
            ->where(function ($q) {
                $q->where(function ($q1) {
                    $q1->whereRaw("LOWER(TRIM(status)) = 'future prospect'")->whereNotNull('future_prospect_date');
                })->orWhere(function ($q2) {
                    $q2->whereRaw("LOWER(TRIM(status)) = ?", ['follow-up'])->whereNotNull('follow_up_date');
                });
            })
            ->whereNotNull('executive')
            ->whereRaw("CASE WHEN LOWER(TRIM(status)) = ? THEN follow_up_date ELSE future_prospect_date END <= ?", ['follow-up', now()])
            ->where(function ($q) {
                $q->whereNull('future_prospect_reminder_at')
                    ->orWhereRaw("future_prospect_reminder_at <> CASE WHEN LOWER(TRIM(status)) = ? THEN follow_up_date ELSE future_prospect_date END", ['follow-up']);
            })
            ->orderBy('id')
            ->chunkById(100, function ($leads) use (&$count) {
                foreach ($leads as $lead) {
                    $lead = $lead->fresh();
                    if (! $lead) {
                        continue;
                    }
                    $dueDate = Lead::isSalesFollowUpStatus($lead->status)
                        ? $lead->follow_up_date
                        : $lead->future_prospect_date;
                    if (! $dueDate) {
                        continue;
                    }
                    if ($dueDate->isFuture()) {
                        continue;
                    }
                    if (! $lead->isScheduledSalesContactReminderDueThisAppDay()) {
                        continue;
                    }
                    try {
                        broadcast(new FutureProspectReminderDue($lead));
                    } catch (Throwable $e) {
                        Log::warning('Future prospect reminder broadcast failed (scheduler)', [
                            'lead_id' => $lead->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                    $lead->forceFill([
                        'future_prospect_reminder_at' => $dueDate->copy(),
                    ])->saveQuietly();
                    $count++;
                }
            });

        OperationLead::query()
            ->whereRaw("LOWER(TRIM(status)) IN ('future prospect', 'follow up')")
            ->where(function ($q) {
                $q->where(function ($q1) {
                    $q1->whereRaw("LOWER(TRIM(status)) = 'future prospect'")->whereNotNull('future_prospect_date');
                })->orWhere(function ($q2) {
                    $q2->whereRaw("LOWER(TRIM(status)) = ?", ['follow up'])->whereNotNull('follow_up_date');
                });
            })
            ->whereNotNull('executive')
            ->whereRaw("CASE WHEN LOWER(TRIM(status)) = ? THEN follow_up_date ELSE future_prospect_date END <= ?", ['follow up', now()])
            ->where(function ($q) {
                $q->whereNull('future_prospect_reminder_at')
                    ->orWhereRaw("future_prospect_reminder_at <> CASE WHEN LOWER(TRIM(status)) = ? THEN follow_up_date ELSE future_prospect_date END", ['follow up']);
            })
            ->orderBy('id')
            ->chunkById(100, function ($leads) use (&$count) {
                foreach ($leads as $operationLead) {
                    $operationLead = $operationLead->fresh();
                    if (! $operationLead) {
                        continue;
                    }
                    $dueDate = $operationLead->status === 'follow up'
                        ? $operationLead->follow_up_date
                        : $operationLead->future_prospect_date;
                    if (! $dueDate) {
                        continue;
                    }
                    if ($dueDate->gt(now())) {
                        continue;
                    }
                    if (! $operationLead->isScheduledContactReminderDueThisAppDay()) {
                        continue;
                    }
                    try {
                        broadcast(new OperationFutureProspectReminderDue($operationLead));
                    } catch (Throwable $e) {
                        Log::warning('Operation future prospect reminder broadcast failed (scheduler)', [
                            'operation_lead_id' => $operationLead->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                    $operationLead->forceFill([
                        'future_prospect_reminder_at' => $dueDate->copy(),
                    ])->saveQuietly();
                    $count++;
                }
            });

        if ($count > 0) {
            $this->info("Dispatched {$count} future prospect reminder(s).");
        }

        return self::SUCCESS;
    }
}
