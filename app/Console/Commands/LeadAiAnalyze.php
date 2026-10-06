<?php

namespace App\Console\Commands;

use App\Models\LeadAiAnalysis;
use App\Support\LeadAi\LeadAnalyzer;
use App\Support\LeadAi\LeadContext;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Re-runs the AI lead review for leads that had new activity (call, remark, WhatsApp, status, deployment, feedback).
 */
class LeadAiAnalyze extends Command
{
    protected $signature = 'leads:ai-analyze
        {--limit=8 : Max leads to analyse in this run}
        {--hours=24 : Look for activity in the last N hours}
        {--gap=180 : Minutes to wait before re-analysing the same lead}
        {--type= : sales or operation (default both)}
        {--lead= : Analyse one lead id now (needs --type)}
        {--backfill= : Analyse leads from the last N days that have no AI review yet}';

    protected $description = 'AI review (Gemini) of sales and operation leads with new calls, chats or updates';

    public const SKIP_STATUSES = ['spam', 'duplicate'];

    public function handle(LeadAnalyzer $analyzer): int
    {
        if (! LeadAnalyzer::configured()) {
            $this->warn('GEMINI_API_KEY is not set; skipping.');

            return self::SUCCESS;
        }

        if ($this->option('lead')) {
            $type = $this->option('type') === 'operation' ? 'operation' : 'sales';
            $analysis = $analyzer->run($type, (int) $this->option('lead'));
            $this->line("$type #{$this->option('lead')}: {$analysis->status} ".($analysis->error ?: ($analysis->result['headline'] ?? '')));

            return self::SUCCESS;
        }

        $limit = max(1, (int) $this->option('limit'));
        $types = in_array($this->option('type'), ['sales', 'operation'], true) ? [$this->option('type')] : ['sales', 'operation'];

        if ($this->option('backfill')) {
            return $this->backfill($analyzer, $types, max(1, (int) $this->option('backfill')), $limit);
        }

        $since = now()->subHours(max(1, (int) $this->option('hours')));

        $candidates = collect();
        foreach ($types as $type) {
            $ids = $type === 'operation' ? $this->operationCandidates($since) : $this->salesCandidates($since);
            $candidates = $candidates->concat($ids->map(fn ($id) => [$type, (int) $id]));
        }

        $existing = LeadAiAnalysis::query()
            ->where(fn ($q) => $candidates->groupBy(0)->each(fn ($rows, $type) => $q->orWhere(fn ($w) => $w->where('lead_type', $type)->whereIn('lead_id', $rows->pluck(1)))))
            ->get()
            ->keyBy(fn ($a) => $a->lead_type.':'.$a->lead_id);

        $done = 0;
        foreach ($candidates as [$type, $id]) {
            if ($done >= $limit) {
                break;
            }
            $analysis = $existing[$type.':'.$id] ?? null;
            if ($analysis && ($analysis->isRunning() || ($analysis->analyzed_at && $analysis->analyzed_at->gt(now()->subMinutes((int) $this->option('gap')))))) {
                continue;
            }

            $context = LeadContext::for($type, $id);
            if (! $context || ! $context->hasActivity() || in_array(strtolower(trim((string) $context->lead->status)), self::SKIP_STATUSES, true)) {
                continue;
            }
            if ($analysis && $analysis->status === 'done' && $analysis->input_hash === $context->hash()) {
                continue;
            }

            $result = $analyzer->run($type, $id);
            $done++;
            $this->line("$type #$id: {$result->status} ".($result->error ?: ($result->result['headline'] ?? '')));
            if ($result->status === 'failed' && LeadAnalyzer::isConfigError($result->error)) {
                $this->error('Stopping: '.$result->error);

                break;
            }
        }

        $this->info("Analysed $done lead(s).");

        return self::SUCCESS;
    }

    /** Older leads (created/updated in the last N days) that were never reviewed, newest first, a few per run. */
    private function backfill(LeadAnalyzer $analyzer, array $types, int $days, int $limit): int
    {
        $since = now()->subDays($days);
        $done = 0;
        $checked = 0;

        foreach ($types as $type) {
            $table = $type === 'operation' ? 'operation_leads' : 'leads';
            $ids = DB::table($table.' as l')
                ->leftJoin('lead_ai_analyses as a', fn ($j) => $j->on('a.lead_id', '=', 'l.id')->where('a.lead_type', $type))
                ->whereNull('a.id')
                ->where(fn ($q) => $q->where('l.created_at', '>=', $since)->orWhere('l.updated_at', '>=', $since))
                ->whereRaw("LOWER(TRIM(COALESCE(l.status, ''))) NOT IN ('spam', 'duplicate')")
                ->when($type === 'operation', fn ($q) => $q->whereNull('l.deleted_at'))
                ->orderByDesc('l.updated_at')
                ->limit(300)
                ->pluck('l.id');

            foreach ($ids as $id) {
                if ($done >= $limit || $checked >= 60) {
                    break 2;
                }
                $skipKey = 'lead-ai-backfill-skip:'.$type.':'.$id;
                if (cache()->has($skipKey)) {
                    continue;
                }
                $checked++;

                $context = LeadContext::for($type, (int) $id);
                if (! $context || ! $context->hasActivity()) {
                    cache()->put($skipKey, 1, now()->addDays(7));

                    continue;
                }

                $result = $analyzer->run($type, (int) $id);
                $done++;
                $this->line("$type #$id: {$result->status} ".($result->error ?: ($result->result['headline'] ?? '')));
                if ($result->status === 'failed' && LeadAnalyzer::isConfigError($result->error)) {
                    $this->error('Stopping: '.$result->error);

                    break 2;
                }
            }
        }

        $this->info("Backfilled $done lead(s).");

        return self::SUCCESS;
    }

    private function salesCandidates($since): Collection
    {
        return collect()
            ->concat(DB::table('call_details')->where('created_at', '>=', $since)->whereNotNull('lead_id')->where(fn ($q) => $q->where('call_for', 'lead')->orWhereNull('call_for'))->orderByDesc('id')->pluck('lead_id'))
            ->concat(DB::table('call_logs')->where('created_at', '>=', $since)->whereNotNull('lead_id')->orderByDesc('id')->pluck('lead_id'))
            ->concat(DB::table('lead_status_remarks')->where('created_at', '>=', $since)->orderByDesc('id')->pluck('lead_id'))
            ->concat($this->idsByRecentWhatsapp('leads', $since))
            ->concat(DB::table('leads')->where('updated_at', '>=', $since)->orderByDesc('updated_at')->pluck('id'))
            ->filter()
            ->unique()
            ->values();
    }

    private function operationCandidates($since): Collection
    {
        $callNumbers = DB::table('call_details')->where('created_at', '>=', $since)->where('call_for', 'operation_lead')->pluck('caller_id_number');

        return collect()
            ->concat(DB::table('operation_lead_status_remarks')->where('created_at', '>=', $since)->orderByDesc('id')->pluck('operation_lead_id'))
            ->concat(DB::table('operation_deployment_details')->where('updated_at', '>=', $since)->orderByDesc('updated_at')->pluck('operation_lead_id'))
            ->concat(DB::table('customer_feedbacks')->where('created_at', '>=', $since)->whereNotNull('operation_lead_id')->pluck('operation_lead_id'))
            ->concat($this->idsByNumbers('operation_leads', $callNumbers))
            ->concat($this->idsByRecentWhatsapp('operation_leads', $since))
            ->concat(DB::table('operation_leads')->whereNull('deleted_at')->where('updated_at', '>=', $since)->orderByDesc('updated_at')->pluck('id'))
            ->filter()
            ->unique()
            ->values();
    }

    private function idsByRecentWhatsapp(string $table, $since): Collection
    {
        return $this->idsByNumbers($table, DB::table('whatsapp_messages')->where('created_at', '>=', $since)->whereNull('deleted_at')->distinct()->pluck('msg_from'));
    }

    private function idsByNumbers(string $table, Collection $numbers): Collection
    {
        $mobiles = $numbers->map(fn ($n) => substr(preg_replace('/\D+/', '', (string) $n), -10))->filter(fn ($n) => strlen($n) === 10)->unique()->take(300)->values();
        if ($mobiles->isEmpty()) {
            return collect();
        }

        return DB::table($table)
            ->whereRaw("RIGHT(REPLACE(REPLACE(contact_no, '+', ''), ' ', ''), 10) IN (".$mobiles->map(fn () => '?')->implode(',').')', $mobiles->all())
            ->when($table === 'operation_leads', fn ($q) => $q->whereNull('deleted_at'))
            ->orderByDesc('updated_at')
            ->pluck('id');
    }
}
