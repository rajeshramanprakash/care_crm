<?php

namespace App\Jobs;

use App\Models\MissedCallbackAttempt;
use App\Models\MissedCallbackSequence;
use App\Models\User;
use App\Services\MissedCallbackAgentPresence;
use App\Services\MissedCallbackSequenceService;
use App\Services\MissedCallbackTimeCalculator;
use App\Events\MissedCallbackProgress;
use App\Services\OutboundCall;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RunMissedCallbackAttemptJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 60;

    public function __construct(public int $sequenceId)
    {
        $q = config('services.missed_callback.queue');
        if (is_string($q) && $q !== '') {
            $this->onQueue($q);
        }
    }

    public function uniqueId(): string
    {
        return 'mcb-run-'.$this->sequenceId;
    }

    public function handle(
        MissedCallbackAgentPresence $presence,
        MissedCallbackSequenceService $sequenceService,
        MissedCallbackTimeCalculator $calculator
    ): void {
        if (! $sequenceService->isEnabled()) {
            return;
        }

        DB::transaction(function () use ($presence, $sequenceService, $calculator) {
            $sequence = MissedCallbackSequence::query()->lockForUpdate()->find($this->sequenceId);
            if (! $sequence || $sequence->status !== MissedCallbackSequence::STATUS_ACTIVE) {
                return;
            }

            if ($sequence->awaiting_disposition_at !== null) {
                return;
            }

            if ($sequence->next_attempt_at && $sequence->next_attempt_at->isFuture()) {
                return;
            }

            $step = (int) $sequence->current_step;
            if ($step < 1 || $step > 10) {
                $sequence->update([
                    'status' => MissedCallbackSequence::STATUS_COMPLETED_EXHAUSTED,
                    'stop_reason' => 'invalid_step',
                    'next_attempt_at' => null,
                ]);

                return;
            }

            $exec = User::find($sequence->executive_id);
            if (! $exec || ! $exec->tata_agent_id) {
                Log::warning('Missed callback job: executive missing or no Tata agent', [
                    'sequence_id' => $sequence->id,
                ]);
                $sequence->update([
                    'status' => MissedCallbackSequence::STATUS_CANCELLED,
                    'stop_reason' => 'no_tata_agent',
                    'next_attempt_at' => null,
                ]);

                return;
            }

            $lead = $sequence->leadRecord();
            if (! $lead) {
                $sequence->update([
                    'status' => MissedCallbackSequence::STATUS_CANCELLED,
                    'stop_reason' => 'lead_missing',
                    'next_attempt_at' => null,
                ]);

                return;
            }

            $freshExecId = (int) ($lead->executive ?? 0);
            if ($freshExecId > 0 && $freshExecId !== (int) $exec->id) {
                $exec = User::find($freshExecId) ?? $exec;
                $sequence->executive_id = $exec->id;
                $sequence->save();
            }

            if ($presence->isAgentBusy($exec)) {
                MissedCallbackAttempt::create([
                    'missed_callback_sequence_id' => $sequence->id,
                    'step_index' => $step,
                    'scheduled_at' => $sequence->next_attempt_at,
                    'started_at' => now(),
                    'finished_at' => now(),
                    'result' => MissedCallbackAttempt::RESULT_SKIPPED_BUSY,
                    'meta' => ['reason' => 'agent_ringing_or_on_call'],
                ]);

                $sequence->update([
                    'next_attempt_at' => now()->addMinutes(2),
                ]);

                if (in_array(config('broadcasting.default'), ['reverb', 'pusher'], true)) {
                    broadcast(new MissedCallbackProgress($exec->id, 'skipped_busy', [
                        'sequence_id' => $sequence->id,
                        'step' => $step,
                        'retry_at' => $sequence->fresh()->next_attempt_at?->toIso8601String(),
                    ]));
                }

                return;
            }

            $refId = MissedCallbackSequenceService::buildRefId($sequence->id, $step);
            $phone = $sequence->customer_phone;

            if (config('services.missed_callback.dry_run', false)) {
                Log::info('[MissedCallback] Step '.$step.' — starting outbound attempt (agent → customer)', [
                    'sequence_id' => $sequence->id,
                    'lead_type' => $sequence->lead_type,
                    'lead_id' => $sequence->lead_id,
                    'customer_phone' => $phone,
                    'executive_id' => $exec->id,
                    'executive_mobile' => $exec->mobile,
                    'tata_agent_id' => $exec->tata_agent_id,
                ]);

                Log::info('[MissedCallback DRY_RUN] Would call Tata click-to-call API — suppressed (no HTTP)', [
                    'sequence_id' => $sequence->id,
                    'step' => $step,
                    'custom_identifier' => $refId,
                    'destination_number' => $phone,
                    'agent_number' => $exec->mobile,
                    'note' => 'Production: POST api-smartflo…/click_to_call with Bearer token',
                ]);

                $attempt = MissedCallbackAttempt::create([
                    'missed_callback_sequence_id' => $sequence->id,
                    'step_index' => $step,
                    'scheduled_at' => $sequence->next_attempt_at,
                    'started_at' => now(),
                    'finished_at' => now(),
                    'result' => MissedCallbackAttempt::RESULT_DRY_RUN,
                    'meta' => [
                        'dry_run' => true,
                        'message' => 'MISSED_CALLBACK_DRY_RUN=true — OutboundCall not invoked',
                    ],
                ]);

                $sequence->update([
                    'status' => MissedCallbackSequence::STATUS_CANCELLED,
                    'stop_reason' => 'dry_run',
                    'next_attempt_at' => null,
                    'awaiting_disposition_at' => null,
                    'last_attempt_at' => now(),
                ]);

                Log::info('[MissedCallback DRY_RUN] Attempt finished — no webhook expected (sequence closed for local test)', [
                    'sequence_id' => $sequence->id,
                    'missed_callback_attempt_id' => $attempt->id,
                    'result' => MissedCallbackAttempt::RESULT_DRY_RUN,
                    'sequence_status' => MissedCallbackSequence::STATUS_CANCELLED,
                    'stop_reason' => 'dry_run',
                ]);

                return;
            }

            $result = OutboundCall::call($phone, $refId, $exec);

            MissedCallbackAttempt::create([
                'missed_callback_sequence_id' => $sequence->id,
                'step_index' => $step,
                'scheduled_at' => $sequence->next_attempt_at,
                'started_at' => now(),
                'finished_at' => null,
                'result' => ($result['success'] ?? false) ? MissedCallbackAttempt::RESULT_PLACED : MissedCallbackAttempt::RESULT_FAILED_API,
                'meta' => $result,
            ]);

            $sequence->update([
                'last_attempt_at' => now(),
            ]);

            if (! ($result['success'] ?? false)) {
                if ($step >= 10) {
                    $sequence->update([
                        'status' => MissedCallbackSequence::STATUS_COMPLETED_EXHAUSTED,
                        'stop_reason' => 'api_fail_final',
                        'next_attempt_at' => null,
                    ]);

                    return;
                }

                $nextAt = $calculator->nextRunAfterCompletedStep($sequence, $step, now());
                if ($nextAt) {
                    $sequence->update([
                        'current_step' => $step + 1,
                        'next_attempt_at' => $nextAt,
                    ]);
                } else {
                    $sequence->update([
                        'status' => MissedCallbackSequence::STATUS_COMPLETED_EXHAUSTED,
                        'stop_reason' => 'api_fail_no_slot',
                        'next_attempt_at' => null,
                    ]);
                }

                return;
            }

            $sequence->update([
                'next_attempt_at' => null,
                'awaiting_disposition_at' => now(),
            ]);
        });
    }
}
