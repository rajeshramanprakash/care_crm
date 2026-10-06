<?php

namespace App\Support\LeadAi;

use App\Models\LeadAiCallNote;
use App\Services\GeminiService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Sends answered call recordings to Gemini (audio) once and caches the transcript + summary per recording.
 */
class CallTranscriber
{
    /** Inline audio limit: Gemini rejects requests over ~20 MB and base64 adds a third. */
    public const MAX_BYTES = 14 * 1024 * 1024;

    public const MIN_SECONDS = 10;

    public const MAX_ATTEMPTS = 3;

    public function __construct(protected GeminiService $gemini) {}

    /**
     * Transcribes up to $limit new recordings (newest first) and returns notes for all answered calls that have one.
     *
     * @return array<string, LeadAiCallNote> keyed by call key
     */
    public function notesFor(LeadContext $context, int $limit): array
    {
        $candidates = collect($context->calls)
            ->filter(fn ($c) => $c['status'] === 'answered' && $c['recording_url'] && ($c['duration'] === null || $c['duration'] >= self::MIN_SECONDS))
            ->sortByDesc(fn ($c) => $c['at']?->timestamp ?? 0)
            ->values();

        $existing = LeadAiCallNote::whereIn('recording_hash', $candidates->map(fn ($c) => self::hashFor($c))->all())->get()->keyBy('recording_hash');

        $notes = [];
        $done = 0;
        foreach ($candidates as $call) {
            $hash = self::hashFor($call);
            $note = $existing[$hash] ?? null;
            if ((! $note || ($note->status === 'failed' && $note->attempts < self::MAX_ATTEMPTS)) && $done < $limit) {
                $note = $this->transcribe($context, $call, $note);
                $done++;
            }
            if ($note && $note->status === 'done') {
                $notes[$call['key']] = $note;
            }
        }

        return $notes;
    }

    public static function hashFor(array $call): string
    {
        $id = LeadContext::callIdFromUrl($call['recording_url']) ?? strtok((string) $call['recording_url'], '?');

        return hash('sha256', $id);
    }

    private function transcribe(LeadContext $context, array $call, ?LeadAiCallNote $note): LeadAiCallNote
    {
        $note ??= new LeadAiCallNote(['recording_hash' => self::hashFor($call)]);
        $note->fill([
            'lead_type' => $context->type,
            'lead_id' => $context->lead->id,
            'call_at' => $call['at'],
            'call_status' => $call['raw_status'],
            'agent_name' => $call['agent'],
            'duration' => $call['duration'],
        ]);
        $note->attempts++;

        try {
            $response = Http::timeout(40)->withOptions(['stream' => false])->get($call['recording_url']);
            $body = $response->body();
            if (! $response->successful() || strlen($body) < 2000) {
                return $this->fail($note, 'Recording could not be downloaded (HTTP '.$response->status().').');
            }
            if (strlen($body) > self::MAX_BYTES) {
                return $this->fail($note, 'Recording is too large for AI ('.round(strlen($body) / 1048576, 1).' MB).');
            }

            $text = $this->gemini->generateContent([
                ['text' => $this->prompt($context, $call)],
                ['inline_data' => ['mime_type' => self::mimeType((string) $response->header('Content-Type'), $call['recording_url']), 'data' => base64_encode($body)]],
            ], 0.2, 8192, true, 150);
        } catch (\Throwable $e) {
            return $this->fail($note, Str::limit($e->getMessage(), 480));
        }

        $data = LeadAnalyzer::decodeJson($text);
        if (! $data && LeadAnalyzer::isConfigError($this->gemini->lastError)) {
            throw new LeadAiException($this->gemini->lastError);
        }
        if (! $data) {
            return $this->fail($note, $this->gemini->lastError ?: 'AI could not read this recording.');
        }

        $transcript = (string) ($data['transcript'] ?? '');
        unset($data['transcript']);
        $note->fill(['status' => 'done', 'summary' => $data, 'transcript' => Str::limit($transcript, 15000), 'error' => null])->save();

        return $note;
    }

    private function fail(LeadAiCallNote $note, string $error): LeadAiCallNote
    {
        $note->fill(['status' => 'failed', 'error' => Str::limit($error, 480)])->save();

        return $note;
    }

    private function prompt(LeadContext $context, array $call): string
    {
        $who = $context->type === 'operation' ? 'an operation (service delivery) executive' : 'a sales executive';

        return <<<PROMPT
This audio is a phone call recording between {$who} of Carelix Home Healthcare (home nursing, attendants, physiotherapy, doctor visits in India) and a customer. Language may be Hindi, Hinglish or English.
Agent: {$call['agent']}. Call time: {$call['at']}.

Listen carefully and return ONLY this JSON:
{
  "transcript": "Speaker-labelled transcript in Roman script (Agent: ... / Customer: ...). Keep it faithful; shorten small talk. Max ~1200 words.",
  "summary": "3-5 sentence English summary of what was discussed and agreed",
  "customer_need": "what the customer wants (service, patient, duration, budget) or empty",
  "price_discussed": "rates/quotes mentioned or empty",
  "objections": ["customer concerns / objections"],
  "agent_tone": "how the agent spoke: polite/rushed/confident/unclear etc. with one example",
  "agent_mistakes": ["specific mistakes by the agent, if any"],
  "outcome": "how the call ended / next step agreed",
  "customer_sentiment": "positive | neutral | negative"
}
If the audio is silent, a voicemail or unclear, say so in summary and keep other fields empty.
PROMPT;
    }

    private static function mimeType(string $contentType, string $url): string
    {
        $ct = strtolower($contentType);
        $path = strtolower((string) parse_url($url, PHP_URL_PATH));

        return match (true) {
            str_contains($ct, 'wav') || str_ends_with($path, '.wav') => 'audio/wav',
            str_contains($ct, 'ogg') || str_ends_with($path, '.ogg') => 'audio/ogg',
            str_contains($ct, 'aac') || str_ends_with($path, '.aac') => 'audio/aac',
            str_contains($ct, 'flac') || str_ends_with($path, '.flac') => 'audio/flac',
            default => 'audio/mp3',
        };
    }
}
