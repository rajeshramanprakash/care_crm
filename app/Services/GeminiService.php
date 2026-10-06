<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiService
{
    protected string $apiKey;

    protected string $baseUrl = 'https://generativelanguage.googleapis.com/v1beta';

    /** Last API error message from generateContent() (shown to admins when AI fails). */
    public ?string $lastError = null;

    /** Model that answered the last successful generateContent() call. */
    public ?string $lastModel = null;

    public function __construct()
    {
        $this->apiKey = (string) config('services.gemini.api_key', env('GEMINI_API_KEY', ''));
    }

    /**
     * Translate text to target language using Google Gemini (model from config, with fallbacks).
     *
     * @return string|null Translated text or null on failure
     */
    public function translate(string $text, string $targetLanguage = 'Hindi'): ?string
    {
        $text = trim($text);
        if ($this->apiKey === '') {
            Log::warning('Gemini translate: GEMINI_API_KEY is not configured');

            return null;
        }
        if ($text === '') {
            return '';
        }

        $targetLanguage = trim($targetLanguage) !== '' ? trim($targetLanguage) : 'Hindi';
        $prompt = <<<PROMPT
Translate the following text to {$targetLanguage}.
Rules:
- Output ONLY the translation, with no title, no quotes, and no explanation.
- Preserve line breaks where reasonable.
- If the text is already mostly in the target language, return it cleaned up without adding commentary.

Text:
{$text}
PROMPT;

        foreach ($this->modelCandidates() as $model) {
            $out = $this->generateTranslatedText($model, $prompt);
            if ($out !== null && $out !== '') {
                return $out;
            }
        }

        return null;
    }

    /**
     * @return list<string>
     */
    protected function modelCandidates(): array
    {
        $primary = trim((string) config('services.gemini.model', 'gemini-3.5-flash'));
        $pool = array_merge(
            [$primary],
            [
                'gemini-3.5-flash',
                'gemini-flash-latest',
                'gemini-3.5-flash-lite',
                'gemini-2.5-flash',
            ]
        );

        return array_values(array_unique(array_filter($pool)));
    }

    protected function generateTranslatedText(string $model, string $prompt): ?string
    {
        $model = ltrim(str_replace(['models/', 'models:', 'Models/'], '', $model), '/');
        $url = "{$this->baseUrl}/models/{$model}:generateContent?key=".$this->apiKey;

        try {
            $response = Http::timeout(55)
                ->acceptJson()
                ->asJson()
                ->post($url, [
                    'contents' => [
                        ['parts' => [['text' => $prompt]]],
                    ],
                    'generationConfig' => [
                        'temperature' => 0.2,
                        'maxOutputTokens' => 4096,
                    ],
                    'safetySettings' => [
                        ['category' => 'HARM_CATEGORY_HARASSMENT', 'threshold' => 'BLOCK_NONE'],
                        ['category' => 'HARM_CATEGORY_HATE_SPEECH', 'threshold' => 'BLOCK_NONE'],
                        ['category' => 'HARM_CATEGORY_SEXUALLY_EXPLICIT', 'threshold' => 'BLOCK_NONE'],
                        ['category' => 'HARM_CATEGORY_DANGEROUS_CONTENT', 'threshold' => 'BLOCK_NONE'],
                    ],
                ]);

            $status = $response->status();
            $data = $response->json();
            $errMsg = is_array($data) ? ($data['error']['message'] ?? null) : null;

            if (! $response->successful()) {
                Log::warning('Gemini API error', [
                    'model' => $model,
                    'status' => $status,
                    'message' => $errMsg,
                    'body_snippet' => substr($response->body(), 0, 500),
                ]);

                return null;
            }

            $text = $this->extractTextFromResponse(is_array($data) ? $data : null);
            if ($text === null || $text === '') {
                Log::warning('Gemini returned no text', [
                    'model' => $model,
                    'promptFeedback' => is_array($data) ? ($data['promptFeedback'] ?? null) : null,
                ]);
            }

            return $text;
        } catch (\Throwable $e) {
            Log::error('Gemini translate exception', [
                'model' => $model,
                'exception' => $e->getMessage(),
            ]);

            return null;
        }
    }

    protected function extractTextFromResponse(?array $data): ?string
    {
        if ($data === null) {
            return null;
        }

        $candidates = $data['candidates'] ?? null;
        if (! is_array($candidates) || $candidates === []) {
            if (isset($data['promptFeedback'])) {
                Log::info('Gemini promptFeedback present', ['feedback' => $data['promptFeedback']]);
            }

            return null;
        }

        foreach ($candidates as $candidate) {
            $parts = $candidate['content']['parts'] ?? [];
            if (! is_array($parts)) {
                continue;
            }
            foreach ($parts as $part) {
                if (isset($part['text']) && is_string($part['text']) && trim($part['text']) !== '') {
                    return trim($part['text']);
                }
            }
        }

        return null;
    }

    public function translateTo(string $text, string $targetLanguage): ?string
    {
        return $this->translate($text, $targetLanguage);
    }

    /**
     * Generate text from a full prompt (doctor about, etc.).
     */
    public function generateText(string $prompt, float $temperature = 0.5, int $maxOutputTokens = 2048): ?string
    {
        $prompt = trim($prompt);
        if ($this->apiKey === '') {
            Log::warning('Gemini generateText: GEMINI_API_KEY is not configured');

            return null;
        }
        if ($prompt === '') {
            return null;
        }

        foreach ($this->modelCandidates() as $model) {
            $out = $this->generateWithPrompt($model, $prompt, $temperature, $maxOutputTokens);
            if ($out !== null && $out !== '') {
                return $out;
            }
        }

        return null;
    }

    /**
     * Multi-part request (text + inline audio etc.). With $json the model is asked for a JSON-only answer.
     *
     * @param  list<array<string, mixed>>  $parts  e.g. [['text' => '...'], ['inline_data' => ['mime_type' => 'audio/mp3', 'data' => base64]]]
     */
    public function generateContent(array $parts, float $temperature = 0.3, int $maxOutputTokens = 8192, bool $json = false, int $timeout = 120): ?string
    {
        $this->lastError = null;
        $this->lastModel = null;
        if ($this->apiKey === '') {
            $this->lastError = 'GEMINI_API_KEY is not configured.';

            return null;
        }

        $generationConfig = ['temperature' => $temperature, 'maxOutputTokens' => $maxOutputTokens];
        if ($json) {
            $generationConfig['responseMimeType'] = 'application/json';
        }

        foreach ($this->modelCandidates() as $model) {
            $model = ltrim(str_replace(['models/', 'models:', 'Models/'], '', $model), '/');
            try {
                $response = Http::timeout($timeout)
                    ->acceptJson()
                    ->asJson()
                    ->post("{$this->baseUrl}/models/{$model}:generateContent?key=".$this->apiKey, [
                        'contents' => [['role' => 'user', 'parts' => $parts]],
                        'generationConfig' => $generationConfig,
                    ]);
            } catch (\Throwable $e) {
                $this->lastError = $e->getMessage();
                Log::error('Gemini generateContent exception', ['model' => $model, 'exception' => $e->getMessage()]);

                continue;
            }

            $data = $response->json();
            if (! $response->successful()) {
                $this->lastError = is_array($data) ? ($data['error']['message'] ?? null) : null;
                $this->lastError ??= 'HTTP '.$response->status();
                Log::warning('Gemini API error', ['model' => $model, 'status' => $response->status(), 'message' => $this->lastError]);
                if (in_array($response->status(), [400, 401, 403], true) && str_contains(strtolower($this->lastError), 'api key')) {
                    return null;
                }

                continue;
            }

            $text = $this->extractTextFromResponse(is_array($data) ? $data : null);
            if ($text !== null && $text !== '') {
                $this->lastModel = $model;

                return $text;
            }
            $this->lastError = 'The AI returned an empty answer.';
        }

        return null;
    }

    protected function generateWithPrompt(string $model, string $prompt, float $temperature, int $maxOutputTokens): ?string
    {
        $model = ltrim(str_replace(['models/', 'models:', 'Models/'], '', $model), '/');
        $url = "{$this->baseUrl}/models/{$model}:generateContent?key=".$this->apiKey;

        try {
            $response = Http::timeout(55)
                ->acceptJson()
                ->asJson()
                ->post($url, [
                    'contents' => [
                        ['parts' => [['text' => $prompt]]],
                    ],
                    'generationConfig' => [
                        'temperature' => $temperature,
                        'maxOutputTokens' => $maxOutputTokens,
                    ],
                    'safetySettings' => [
                        ['category' => 'HARM_CATEGORY_HARASSMENT', 'threshold' => 'BLOCK_NONE'],
                        ['category' => 'HARM_CATEGORY_HATE_SPEECH', 'threshold' => 'BLOCK_NONE'],
                        ['category' => 'HARM_CATEGORY_SEXUALLY_EXPLICIT', 'threshold' => 'BLOCK_NONE'],
                        ['category' => 'HARM_CATEGORY_DANGEROUS_CONTENT', 'threshold' => 'BLOCK_NONE'],
                    ],
                ]);

            if (! $response->successful()) {
                $data = $response->json();
                Log::warning('Gemini API error', [
                    'model' => $model,
                    'status' => $response->status(),
                    'message' => is_array($data) ? ($data['error']['message'] ?? null) : null,
                    'body_snippet' => substr($response->body(), 0, 500),
                ]);

                return null;
            }

            return $this->extractTextFromResponse($response->json());
        } catch (\Throwable $e) {
            Log::error('Gemini generateText exception', [
                'model' => $model,
                'exception' => $e->getMessage(),
            ]);

            return null;
        }
    }
}
