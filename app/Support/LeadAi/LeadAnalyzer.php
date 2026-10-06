<?php

namespace App\Support\LeadAi;

use App\Models\LeadAiAnalysis;
use App\Models\LeadAiCallNote;
use App\Services\GeminiService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Builds the full lead story (fields, remarks, calls + recording transcripts, WhatsApp, operations data)
 * and asks Gemini for a coaching-style review for managers and Admin.
 */
class LeadAnalyzer
{
    /** New recordings sent to the AI per analysis (older ones are cached). */
    public const NEW_RECORDINGS_PER_RUN = 6;

    public const TRANSCRIPTS_IN_PROMPT = 10;

    public const LIST_FIELDS = ['journey', 'what_went_well', 'mistakes', 'how_to_improve', 'communication_tips', 'next_actions', 'missing_data'];

    public function __construct(
        protected GeminiService $gemini,
        protected CallTranscriber $transcriber,
    ) {}

    public static function configured(): bool
    {
        return (string) config('services.gemini.api_key') !== '';
    }

    public static function isConfigError(?string $error): bool
    {
        $e = strtolower((string) $error);

        return str_contains($e, 'api key') || str_contains($e, 'not configured') || str_contains($e, 'permission denied');
    }

    public function run(string $type, int $leadId, ?int $requestedBy = null): LeadAiAnalysis
    {
        @set_time_limit(600);
        $analysis = LeadAiAnalysis::firstOrNew(['lead_type' => $type, 'lead_id' => $leadId]);
        $analysis->fill(['status' => 'processing', 'started_at' => now(), 'error' => null, 'requested_by' => $requestedBy ?? $analysis->requested_by])->save();

        try {
            if (! self::configured()) {
                throw new LeadAiException('AI is not configured on this server (GEMINI_API_KEY missing).');
            }
            $context = LeadContext::for($type, $leadId);
            if (! $context) {
                throw new LeadAiException('Lead not found.');
            }

            $notes = $this->transcriber->notesFor($context, self::NEW_RECORDINGS_PER_RUN);
            $text = $this->gemini->generateContent([['text' => $this->prompt($context, $notes)]], 0.35, 8192, true, 180);
            $result = self::decodeJson($text);
            if (! $result) {
                throw new LeadAiException($this->gemini->lastError ? 'AI error: '.$this->gemini->lastError : 'AI did not return a usable answer. Please try again.');
            }
            $result = $this->normalize($result);

            $analysis->fill([
                'status' => 'done',
                'health' => $result['health'],
                'score' => $result['score'],
                'result' => $result,
                'sources' => $context->counts() + ['transcribed' => count($notes)],
                'input_hash' => $context->hash(),
                'model' => $this->gemini->lastModel,
                'analyzed_at' => now(),
                'error' => null,
            ])->save();
        } catch (LeadAiException $e) {
            $analysis->fill(['status' => 'failed', 'error' => Str::limit($e->getMessage(), 990)])->save();
        } catch (\Throwable $e) {
            Log::error('Lead AI analysis failed', ['type' => $type, 'lead_id' => $leadId, 'exception' => $e->getMessage()]);
            $analysis->fill(['status' => 'failed', 'error' => 'Analysis failed: '.Str::limit($e->getMessage(), 900)])->save();
        }

        return $analysis;
    }

    public static function decodeJson(?string $text): ?array
    {
        if (! is_string($text) || trim($text) === '') {
            return null;
        }
        $text = trim(preg_replace('/^```(?:json)?\s*|\s*```$/i', '', trim($text)));
        $data = json_decode($text, true);
        if (! is_array($data) && preg_match('/\{.*\}/s', $text, $m)) {
            $data = json_decode($m[0], true);
        }

        return is_array($data) ? $data : null;
    }

    /** @param  array<string, LeadAiCallNote>  $notes */
    private function prompt(LeadContext $context, array $notes): string
    {
        $isOperation = $context->type === 'operation';
        $language = (string) config('services.gemini.lead_ai_language', 'simple English');

        $header = collect($context->header)->map(fn ($v, $k) => "- $k: ".Str::limit((string) $v, 500))->implode("\n");
        $counts = $context->counts();
        $calls = collect($context->calls)->map(function ($c) use ($notes) {
            $line = '- '.($c['at']?->format('d M Y, h:i A') ?? 'unknown time').' | '.strtoupper($c['status'])
                .($c['direction'] ? ' | '.$c['direction'] : '')
                .($c['agent'] ? ' | agent: '.$c['agent'] : '')
                .($c['duration'] !== null ? ' | '.$c['duration'].'s' : '')
                .($c['recording_url'] ? ' | recording' : '');
            if (isset($notes[$c['key']])) {
                $line .= ' | AI-heard: '.Str::limit((string) ($notes[$c['key']]->summary['summary'] ?? ''), 300);
            }

            return $line;
        })->implode("\n") ?: '- No calls found.';

        $transcripts = collect($notes)->sortByDesc(fn ($n) => $n->call_at?->timestamp ?? 0)->take(self::TRANSCRIPTS_IN_PROMPT)->map(function (LeadAiCallNote $n) {
            $s = $n->summary ?? [];

            return '### Call '.($n->call_at?->format('d M Y, h:i A') ?? '').' — agent '.($n->agent_name ?: '-').' — '.($n->duration ?? '?').'s'
                ."\nSummary: ".($s['summary'] ?? '')
                ."\nCustomer need: ".($s['customer_need'] ?? '')
                ."\nPrice discussed: ".($s['price_discussed'] ?? '')
                ."\nObjections: ".implode('; ', (array) ($s['objections'] ?? []))
                ."\nAgent tone: ".($s['agent_tone'] ?? '')
                ."\nAgent mistakes: ".implode('; ', (array) ($s['agent_mistakes'] ?? []))
                ."\nOutcome: ".($s['outcome'] ?? '')
                ."\nTranscript:\n".Str::limit((string) $n->transcript, 4000);
        })->implode("\n\n") ?: 'No call recordings could be analysed.';

        $remarks = $context->remarks ? implode("\n", array_map(fn ($r) => '- '.$r, $context->remarks)) : '- No remarks.';
        $whatsapp = $context->whatsapp ? implode("\n", $context->whatsapp) : 'No WhatsApp chat found for this number.';
        $extra = $context->extra ? implode("\n", array_map(fn ($r) => '- '.$r, $context->extra)) : '- None.';

        $role = $isOperation
            ? 'You are a senior operations quality coach for Carelix Home Healthcare (home nurses, attendants, physiotherapy, doctor visits in India). You review how the OPERATION team handled a customer after the sale: staff deployment, replacements, attendance, payments, complaints, communication and retention.'
            : 'You are a senior sales coach for Carelix Home Healthcare (home nurses, attendants, physiotherapy, doctor visits in India). You review how the SALES executive handled a lead from first contact to close/loss: response speed, call handling, follow-ups, pricing talk, objection handling and WhatsApp communication.';

        $focus = $isOperation
            ? '- If the service is running well, say what keeps it good and what would make it even better (retention, upsell, fewer complaints).
- If the service stopped / customer left / inactive / complaints, find the ROOT CAUSE (staff issue, late replacement, price, payment, communication, attendance, delays) and what operation should have done.
- Check deployment gaps, staff changes, absent days, pending payments, low feedback ratings, support tickets.
- "mistakes" = gaps or mistakes in the operation team\'s handling. "how_to_improve" = changes for the operation department.'
            : '- If the lead is CLOSED/WON, say what worked and how it could have closed faster or at a better rate.
- If the lead is NOT closed (price issue, no response, inactive, prospect, future prospect, follow-up etc.), find the REAL reason the deal did not close and where the executive fell short (late call-back, missed calls not returned, weak follow-up, no value explained before price, wrong tone, no WhatsApp follow-up, etc.).
- If the lead is still active, say exactly what to do next to close it.
- "mistakes" = specific mistakes by the sales executive. "how_to_improve" = how this deal could be handled better.';

        return <<<PROMPT
{$role}
Your report is read ONLY by managers and Admin, so be honest, specific and practical. Base every point on the data below (quote dates/calls/messages when useful). Never invent facts; if data is missing, list it in "missing_data".

## Lead
{$header}

## Activity counts
Calls: {$counts['calls']} (answered {$counts['answered']}, missed/busy/unanswered {$counts['missed']}), recordings analysed: {$this->countOf($notes)}, WhatsApp messages: {$counts['whatsapp']}, remarks: {$counts['remarks']}

## Remark history (oldest first)
{$remarks}

## Other facts
{$extra}

## Call log (oldest first)
{$calls}

## Call recordings heard by AI (newest first)
{$transcripts}

## WhatsApp chat (oldest first)
{$whatsapp}

## What to analyse
- Timeline: when the customer first contacted, response time, calls answered vs missed (and whether missed calls were called back), how the talk went on calls, WhatsApp replies.
{$focus}
- Comment on the way of talking (tone, clarity, empathy, confidence, listening, pitch) from recordings and chats.
- Give concrete next actions with timing, and one ready-to-use message/script the staff can send or say next.

## Output
Write in {$language}. Return ONLY this JSON (no markdown):
{
  "health": "won | good | average | at_risk | lost",
  "score": 0-100 (how well this lead/service is handled and its chance of success),
  "headline": "one line verdict",
  "summary": "4-6 sentence story of this lead in short",
  "outcome_reason": "why it closed / why it did not close / what is blocking right now",
  "journey": ["key moments in order, each 'date – what happened'"],
  "call_review": "what the calls (answered, missed, recordings) tell us",
  "whatsapp_review": "what the WhatsApp chat tells us (or 'No chat')",
  "what_went_well": ["..."],
  "mistakes": ["..."],
  "how_to_improve": ["..."],
  "communication_tips": ["how the staff should talk / message differently"],
  "next_actions": ["concrete next step with timing"],
  "suggested_message": "ready-to-send WhatsApp message or call script for the next contact (Hinglish is fine)",
  "customer_sentiment": "positive | neutral | negative | unknown",
  "missing_data": ["data that was missing and would help"]
}
PROMPT;
    }

    private function countOf(array $notes): int
    {
        return count($notes);
    }

    private function normalize(array $r): array
    {
        $health = strtolower(str_replace([' ', '-'], '_', trim((string) ($r['health'] ?? ''))));
        $r['health'] = array_key_exists($health, LeadAiAnalysis::HEALTH) ? $health : 'average';
        $r['score'] = max(0, min(100, (int) ($r['score'] ?? 50)));
        foreach (self::LIST_FIELDS as $field) {
            $value = $r[$field] ?? [];
            $r[$field] = array_values(array_filter(array_map(fn ($v) => is_scalar($v) ? trim((string) $v) : trim(implode(' – ', array_map('strval', array_filter((array) $v, 'is_scalar')))), is_array($value) ? $value : [$value])));
        }
        foreach (['headline', 'summary', 'outcome_reason', 'call_review', 'whatsapp_review', 'suggested_message', 'customer_sentiment'] as $field) {
            $r[$field] = is_scalar($r[$field] ?? null) ? trim((string) $r[$field]) : '';
        }

        return $r;
    }
}
