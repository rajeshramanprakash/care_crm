<?php

namespace App\Jobs;

use App\Models\LeadAiAnalysis;
use App\Support\LeadAi\LeadAiAutoTrigger;
use App\Support\LeadAi\LeadAnalyzer;
use App\Support\LeadAi\LeadContext;
use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Cache;

/** Run with dispatchAfterResponse() so the page returns at once and the AI works in the same request. */
class AnalyzeLeadWithAi
{
    use Dispatchable, Queueable;

    public const SKIP_STATUSES = ['spam', 'duplicate'];

    public function __construct(
        public string $type,
        public int $leadId,
        public ?int $requestedBy = null,
        public bool $auto = false,
    ) {}

    public function handle(LeadAnalyzer $analyzer): void
    {
        if ($this->auto && ! self::claimAutoRun($this->type, $this->leadId)) {
            return;
        }

        $analyzer->run($this->type, $this->leadId, $this->requestedBy);
    }

    public static function lockKey(string $type, int $leadId): string
    {
        return 'lead-ai-auto:'.$type.':'.$leadId;
    }

    /**
     * Automatic runs: only when the lead's data changed since the last review, at most once per gap per lead.
     * Activity inside the gap or during a running review is remembered and re-run once the gap is over.
     * Returns true (and takes the gap lock) when a run should start now.
     */
    public static function claimAutoRun(string $type, int $leadId, bool $rememberIfBusy = true): bool
    {
        $lockKey = self::lockKey($type, $leadId);
        $analysis = LeadAiAnalysis::where('lead_type', $type)->where('lead_id', $leadId)->first();
        if ($analysis?->status === 'failed' && LeadAnalyzer::isConfigError($analysis->error)) {
            return false;
        }
        if (Cache::has($lockKey) || $analysis?->isRunning()) {
            if ($rememberIfBusy) {
                LeadAiAutoTrigger::markPending($type, $leadId);
            }

            return false;
        }

        $context = LeadContext::for($type, $leadId);
        if (! $context || ! $context->hasActivity()
            || in_array(strtolower(trim((string) $context->lead->status)), self::SKIP_STATUSES, true)) {
            return false;
        }
        if ($analysis && $analysis->status === 'done' && $analysis->input_hash === $context->hash()) {
            return false;
        }

        return Cache::add($lockKey, 1, now()->addMinutes(max(1, (int) config('services.gemini.lead_ai_auto_gap', 2))));
    }
}
