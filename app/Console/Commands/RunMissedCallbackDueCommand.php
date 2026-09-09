<?php

namespace App\Console\Commands;

use App\Jobs\RunMissedCallbackAttemptJob;
use App\Models\MissedCallbackSequence;
use App\Services\MissedCallbackSequenceService;
use Illuminate\Console\Command;

class RunMissedCallbackDueCommand extends Command
{
    protected $signature = 'missed-callback:run-due';

    protected $description = 'Dispatch missed-callback click-to-call jobs and resolve stale Tata dispositions';

    public function handle(MissedCallbackSequenceService $sequenceService): int
    {
        if (! $sequenceService->isEnabled()) {
            $this->info('Missed callback is disabled.');

            return self::SUCCESS;
        }

        $stale = $sequenceService->processStaleAwaitingDispositions();
        if ($stale > 0) {
            $this->info("Resolved {$stale} stale awaiting-disposition sequence(s).");
        }

        $ids = MissedCallbackSequence::query()
            ->where('status', MissedCallbackSequence::STATUS_ACTIVE)
            ->whereNull('awaiting_disposition_at')
            ->whereNotNull('next_attempt_at')
            ->where('next_attempt_at', '<=', now())
            ->orderBy('id')
            ->pluck('id');

        foreach ($ids as $id) {
            RunMissedCallbackAttemptJob::dispatch((int) $id);
        }

        if ($ids->isNotEmpty()) {
            $this->info('Dispatched '.$ids->count().' missed callback job(s).');
        }

        return self::SUCCESS;
    }
}
