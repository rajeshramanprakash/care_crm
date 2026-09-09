<?php

namespace App\Services;

use App\Jobs\RunMissedCallbackAttemptJob;
use App\Models\Lead;
use App\Models\MissedCallbackAttempt;
use App\Models\MissedCallbackSequence;
use App\Models\OperationLead;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MissedCallbackSequenceService
{
    public function __construct(
        private MissedCallbackTimeCalculator $calculator
    ) {}

    public function isEnabled(): bool
    {
        return (bool) config('services.missed_callback.enabled', true);
    }

    public function isInboundMissedTrigger(array $data): bool
    {
        $dir = strtolower(trim((string) ($data['direction'] ?? 'inbound')));
        if (in_array($dir, ['outbound', 'clicktocall', 'click_to_call', 'click-to-call'], true)) {
            return false;
        }

        $status = strtolower(trim((string) ($data['call_status'] ?? '')));

        return in_array($status, ['missed', 'no-answer', 'no_answer'], true);
    }

    public function normalizePhone(string $phone): string
    {
        return preg_replace('/\D+/', '', $phone) ?? $phone;
    }

    /**
     * @param  Lead|OperationLead  $lead
     */
    public function startOrIgnore(
        $lead,
        string $leadType,
        array $data,
        User $assignedExecutive,
        ?string $inboundTataCallId
    ): ?MissedCallbackSequence {
        if (! $this->isEnabled()) {
            return null;
        }

        if (! $this->isInboundMissedTrigger($data)) {
            return null;
        }

        $execId = (int) ($lead->executive ?? 0);
        if ($execId < 1) {
            $execId = (int) $assignedExecutive->id;
        }

        $exec = User::find($execId);
        if (! $exec || ! $exec->tata_agent_id) {
            Log::info('Missed callback: skip — no executive with Tata agent', [
                'lead_type' => $leadType,
                'lead_id' => $lead->id,
            ]);

            return null;
        }

        $customerPhone = $this->normalizePhone(
            (string) ($lead->contact_no ?? $data['caller_id_number'] ?? '')
        );

        if ($customerPhone === '') {
            return null;
        }

        $sequence = null;
        $createdNew = false;

        DB::transaction(function () use ($lead, $leadType, $inboundTataCallId, $exec, $customerPhone, &$sequence, &$createdNew) {
            $q = MissedCallbackSequence::query()
                ->where('lead_type', $leadType)
                ->where('lead_id', $lead->id)
                ->where('status', MissedCallbackSequence::STATUS_ACTIVE);

            if ($inboundTataCallId) {
                $existing = (clone $q)->where('inbound_tata_call_id', $inboundTataCallId)->first();
                if ($existing) {
                    $sequence = $existing;

                    return;
                }
            }

            if ($q->exists()) {
                Log::info('Missed callback: active sequence already exists', [
                    'lead_type' => $leadType,
                    'lead_id' => $lead->id,
                ]);

                return;
            }

            $now = now();
            $nextAt = $this->calculator->nextRunForStepIndex(
                new MissedCallbackSequence(['created_at' => $now]),
                1,
                $now
            ) ?? $now;

            $sequence = MissedCallbackSequence::create([
                'lead_type' => $leadType,
                'lead_id' => $lead->id,
                'inbound_tata_call_id' => $inboundTataCallId,
                'executive_id' => $exec->id,
                'customer_phone' => $customerPhone,
                'status' => MissedCallbackSequence::STATUS_ACTIVE,
                'current_step' => 1,
                'next_attempt_at' => $nextAt,
            ]);
            $createdNew = true;

            Log::info('Missed callback sequence created', [
                'sequence_id' => $sequence->id,
                'lead_type' => $leadType,
                'lead_id' => $lead->id,
                'dry_run' => (bool) config('services.missed_callback.dry_run', false),
            ]);
        });

        if ($sequence && $createdNew) {
            RunMissedCallbackAttemptJob::dispatch($sequence->id);
        }

        return $sequence;
    }

    public static function buildRefId(int $sequenceId, int $step): string
    {
        return 'MCB-'.$sequenceId.'-'.$step;
    }

    public static function parseRefId(?string $ref): ?array
    {
        if (! $ref || ! preg_match('/^MCB-(\d+)-(\d+)$/i', trim($ref), $m)) {
            return null;
        }

        return [(int) $m[1], (int) $m[2]];
    }

    public static function extractRefFromTataPayload(array $data): ?array
    {
        foreach (['custom_identifier', 'customIdentifier', 'reference', 'ref'] as $k) {
            if (! empty($data[$k])) {
                $p = self::parseRefId((string) $data[$k]);
                if ($p) {
                    return $p;
                }
            }
        }

        $found = null;
        array_walk_recursive($data, function ($v) use (&$found) {
            if ($found !== null || ! is_string($v)) {
                return;
            }
            $p = self::parseRefId($v);
            if ($p) {
                $found = $p;
            }
        });

        return $found;
    }

    /**
     * Outbound click-to-call completed: advance or complete sequence.
     */
    public function handleOutboundDisposition(MissedCallbackSequence $sequence, int $refStep, array $data): void
    {
        if ($sequence->status !== MissedCallbackSequence::STATUS_ACTIVE) {
            return;
        }

        if ((int) $sequence->current_step !== $refStep) {
            Log::info('Missed callback webhook: step mismatch', [
                'sequence_id' => $sequence->id,
                'expected_step' => $sequence->current_step,
                'ref_step' => $refStep,
            ]);

            return;
        }

        $status = strtolower(trim((string) ($data['call_status'] ?? '')));
        $duration = (int) ($data['duration'] ?? 0);
        if (isset($data['duration']) === false && ! empty($data['end_stamp']) && ! empty($data['start_stamp'])) {
            $duration = Carbon::parse($data['end_stamp'])->diffInSeconds(Carbon::parse($data['start_stamp']));
        }

        $flowMinSec = max(0, (int) config('services.missed_callback.flow_min_answer_seconds', 10));
        $answered = in_array($status, ['answered', 'completed'], true)
            && ($duration >= $flowMinSec);

        if ($answered) {
            $sequence->update([
                'status' => MissedCallbackSequence::STATUS_COMPLETED_ANSWERED,
                'stop_reason' => 'customer_answered',
                'next_attempt_at' => null,
                'awaiting_disposition_at' => null,
            ]);

            MissedCallbackAttempt::query()
                ->where('missed_callback_sequence_id', $sequence->id)
                ->where('step_index', $refStep)
                ->where('result', MissedCallbackAttempt::RESULT_PLACED)
                ->whereNull('finished_at')
                ->update([
                    'finished_at' => now(),
                    'result' => MissedCallbackAttempt::RESULT_STOPPED_ANSWERED,
                ]);

            if ($sequence->lead_type === MissedCallbackSequence::LEAD_TYPE_LEAD) {
                Lead::query()
                    ->where('id', $sequence->lead_id)
                    ->where('status', 'no response')
                    ->update(['status' => 'follow-up']);
            }

            return;
        }

        MissedCallbackAttempt::query()
            ->where('missed_callback_sequence_id', $sequence->id)
            ->where('step_index', $refStep)
            ->where('result', MissedCallbackAttempt::RESULT_PLACED)
            ->whereNull('finished_at')
            ->update([
                'finished_at' => now(),
                'result' => MissedCallbackAttempt::RESULT_NO_ANSWER,
            ]);

        if ($refStep >= 10) {
            $sequence->update([
                'status' => MissedCallbackSequence::STATUS_COMPLETED_EXHAUSTED,
                'stop_reason' => 'max_attempts',
                'next_attempt_at' => null,
                'awaiting_disposition_at' => null,
            ]);

            return;
        }

        $nextAt = $this->calculator->nextRunAfterCompletedStep($sequence, $refStep, now());
        if (! $nextAt) {
            $sequence->update([
                'status' => MissedCallbackSequence::STATUS_COMPLETED_EXHAUSTED,
                'stop_reason' => 'no_next_slot',
                'next_attempt_at' => null,
                'awaiting_disposition_at' => null,
            ]);

            return;
        }

        $sequence->update([
            'current_step' => $refStep + 1,
            'next_attempt_at' => $nextAt,
            'last_attempt_at' => now(),
            'awaiting_disposition_at' => null,
        ]);
    }

    /**
     * If Tata webhook never arrives after click-to-call, assume no-answer after stale minutes.
     */
    public function processStaleAwaitingDispositions(): int
    {
        if (! $this->isEnabled()) {
            return 0;
        }

        $mins = max(1, (int) config('services.missed_callback.stale_disposition_minutes', 25));
        $cutoff = now()->subMinutes($mins);

        $count = 0;
        MissedCallbackSequence::query()
            ->where('status', MissedCallbackSequence::STATUS_ACTIVE)
            ->whereNotNull('awaiting_disposition_at')
            ->where('awaiting_disposition_at', '<', $cutoff)
            ->orderBy('id')
            ->each(function (MissedCallbackSequence $sequence) use (&$count) {
                $this->handleOutboundDisposition($sequence, (int) $sequence->current_step, [
                    'call_status' => 'no-answer',
                    'duration' => 0,
                ]);
                $count++;
            });

        return $count;
    }
}
