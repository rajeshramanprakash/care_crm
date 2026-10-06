<?php

namespace App\Support\LeadAi;

use App\Jobs\AnalyzeLeadWithAi;
use App\Models\LeadAiAnalysis;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Starts the AI lead review in the background (after the HTTP response is sent) whenever a lead gets
 * new activity — no cron or queue worker needed.
 */
class LeadAiAutoTrigger
{
    /** Leads already queued in this request. */
    private static array $queued = [];

    public const MAX_PER_REQUEST = 3;

    private const PENDING_KEY = 'lead-ai-pending';

    public static function enabled(): bool
    {
        if (! config('services.gemini.lead_ai_auto', true) || ! LeadAnalyzer::configured()) {
            return false;
        }

        return ! app()->runningInConsole() || app()->runningUnitTests();
    }

    public static function lead(string $type, $leadId): void
    {
        $leadId = (int) $leadId;
        $key = $type.':'.$leadId;
        if ($leadId <= 0 || isset(self::$queued[$key]) || ! self::enabled()) {
            return;
        }
        if (count(self::$queued) >= self::MAX_PER_REQUEST) {
            self::markPending($type, $leadId);

            return;
        }
        self::$queued[$key] = true;
        AnalyzeLeadWithAi::dispatchAfterResponse($type, $leadId, null, true);
    }

    /** Activity known only by phone number (WhatsApp, calls not linked to a lead): latest sales + operation lead. */
    public static function number(?string $phone): void
    {
        $mobile = substr(preg_replace('/\D+/', '', (string) $phone), -10);
        if (strlen($mobile) !== 10 || ! self::enabled()) {
            return;
        }

        $match = fn ($q) => $q->whereIn('contact_no', [$mobile, '91'.$mobile, '+91'.$mobile, '0'.$mobile])
            ->orWhereRaw("RIGHT(REPLACE(REPLACE(contact_no, '+', ''), ' ', ''), 10) = ?", [$mobile]);

        self::lead('operation', DB::table('operation_leads')->whereNull('deleted_at')->where($match)->orderByDesc('updated_at')->value('id'));
        self::lead('sales', DB::table('leads')->where($match)->orderByDesc('updated_at')->value('id'));
    }

    /** Activity that arrived while a review was running or inside the gap; picked up by runPending() later. */
    public static function markPending(string $type, int $leadId): void
    {
        $pending = Cache::get(self::PENDING_KEY, []);
        $pending[$type.':'.$leadId] = time();
        Cache::put(self::PENDING_KEY, array_slice($pending, -300, null, true), now()->addDay());
    }

    public static function hasPending(): bool
    {
        return Cache::get(self::PENDING_KEY, []) !== [];
    }

    /** Runs waiting leads whose gap is over (called after the response of a normal page request). */
    public static function runPending(int $max = 2): void
    {
        if (! self::enabled()) {
            return;
        }

        $ran = 0;
        foreach (Cache::get(self::PENDING_KEY, []) as $key => $at) {
            if ($ran >= $max) {
                break;
            }
            [$type, $id] = explode(':', $key) + [null, 0];
            $id = (int) $id;
            if ($at < time() - 86400 || ! isset(LeadAiAnalysis::TYPES[$type]) || $id <= 0) {
                self::forgetPending($key);

                continue;
            }
            $running = LeadAiAnalysis::where('lead_type', $type)->where('lead_id', $id)->first()?->isRunning();
            if ($running || Cache::has(AnalyzeLeadWithAi::lockKey($type, $id))) {
                continue;
            }

            self::forgetPending($key);
            if (AnalyzeLeadWithAi::claimAutoRun($type, $id)) {
                app(LeadAnalyzer::class)->run($type, $id);
                $ran++;
            }
        }
    }

    private static function forgetPending(string $key): void
    {
        $pending = Cache::get(self::PENDING_KEY, []);
        unset($pending[$key]);
        Cache::put(self::PENDING_KEY, $pending, now()->addDay());
    }

    public static function reset(): void
    {
        self::$queued = [];
    }
}
