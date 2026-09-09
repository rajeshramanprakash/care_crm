<?php

namespace App\Services;

use App\Models\Language;
use App\Models\RegistrationTranslation;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class RegistrationI18nService
{
    public function defaultStrings(): array
    {
        $strings = [];
        foreach ($this->keyDefinitions() as $key => $meta) {
            $strings[$key] = (string) ($meta['default'] ?? '');
        }

        return $strings;
    }

    /** @return array<string, array{group: string, label: string, default: string}> */
    public function keyDefinitions(): array
    {
        return config('registration_i18n.keys', []);
    }

    /** @return array<string, string> */
    public function groupedLabels(): array
    {
        return config('registration_i18n.groups', []);
    }

    public function activeLanguages(): Collection
    {
        return Language::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'native_name']);
    }

    public function findByCode(string $code): ?Language
    {
        return Language::query()
            ->where('code', strtolower(trim($code)))
            ->where('is_active', true)
            ->first();
    }

    /** @return array<string, string> */
    public function translationsForLanguage(Language $language): array
    {
        $defaults = $this->defaultStrings();
        $overrides = RegistrationTranslation::query()
            ->where('language_id', $language->id)
            ->pluck('value', 'translation_key')
            ->all();

        $merged = $defaults;
        foreach ($overrides as $key => $value) {
            if ($value !== null && trim((string) $value) !== '') {
                $merged[$key] = trim((string) $value);
            }
        }

        return $merged;
    }

    public function defaultStringsForLanguage(?string $code = null): array
    {
        $strings = $this->defaultStrings();
        $code = strtolower(trim((string) $code));
        $preset = config('registration_i18n.presets.'.$code);

        if (is_array($preset)) {
            foreach ($preset as $key => $value) {
                if (array_key_exists($key, $strings) && trim((string) $value) !== '') {
                    $strings[$key] = trim((string) $value);
                }
            }
        }

        return $strings;
    }

    public function seedTranslationsFromDefaults(Language $language): void
    {
        foreach ($this->defaultStringsForLanguage($language->code) as $key => $value) {
            RegistrationTranslation::query()->updateOrCreate(
                [
                    'language_id' => $language->id,
                    'translation_key' => $key,
                ],
                ['value' => $value]
            );
        }
    }

    public function saveTranslations(Language $language, array $payload): void
    {
        $allowed = array_keys($this->keyDefinitions());

        foreach ($allowed as $key) {
            if (! array_key_exists($key, $payload)) {
                continue;
            }
            $value = trim((string) $payload[$key]);
            RegistrationTranslation::query()->updateOrCreate(
                [
                    'language_id' => $language->id,
                    'translation_key' => $key,
                ],
                ['value' => $value]
            );
        }
    }

    /**
     * Resolve a unique language code from display name (e.g. "Hindi" -> hi).
     */
    public function resolveUniqueCode(string $name, ?int $exceptLanguageId = null): string
    {
        $base = $this->guessCodeFromName($name);
        $code = $base;
        $suffix = 1;

        while ($this->codeExists($code, $exceptLanguageId)) {
            $code = substr($base, 0, 8).$suffix;
            $suffix++;
        }

        return $code;
    }

    /**
     * Auto-fill all registration form strings for a language (AI + presets).
     *
     * @return array{success: bool, message: string, method: string}
     */
    public function autoTranslateLanguage(Language $language): array
    {
        if ($this->isEnglishLanguage($language)) {
            $this->seedTranslationsFromDefaults($language);

            return [
                'success' => true,
                'message' => 'English registration labels saved.',
                'method' => 'english',
            ];
        }

        $preset = config('registration_i18n.presets.'.$language->code);
        if (is_array($preset) && count($preset) >= 10) {
            $this->persistTranslationMap($language, $this->defaultStringsForLanguage($language->code));

            return [
                'success' => true,
                'message' => 'Registration form labels loaded (built-in preset for '.$language->name.').',
                'method' => 'preset',
            ];
        }

        $english = $this->defaultStrings();
        $translated = $this->translateMapViaGemini($english, $language->name);

        if ($translated !== null && count($translated) > 0) {
            $this->persistTranslationMap($language, $translated);

            return [
                'success' => true,
                'message' => 'Registration form auto-translated to '.$language->name.' using AI.',
                'method' => 'gemini',
            ];
        }

        $this->seedTranslationsFromDefaults($language);

        return [
            'success' => false,
            'message' => 'AI auto-translate unavailable (check GEMINI_API_KEY in .env). English/preset fallback saved — you can use Translations to edit.',
            'method' => 'fallback',
        ];
    }

    public function isEnglishLanguage(Language $language): bool
    {
        $code = strtolower(trim((string) $language->code));
        $name = strtolower(trim((string) $language->name));

        return in_array($code, ['en', 'eng'], true)
            || in_array($name, ['english', 'en'], true);
    }

    public function guessCodeFromName(string $name): string
    {
        $known = [
            'hindi' => 'hi',
            'english' => 'en',
            'tamil' => 'ta',
            'telugu' => 'te',
            'marathi' => 'mr',
            'bengali' => 'bn',
            'gujarati' => 'gu',
            'punjabi' => 'pa',
            'kannada' => 'kn',
            'malayalam' => 'ml',
            'urdu' => 'ur',
            'arabic' => 'ar',
            'french' => 'fr',
            'spanish' => 'es',
            'german' => 'de',
            'odia' => 'or',
            'assamese' => 'as',
        ];

        $lower = strtolower(trim($name));
        if (isset($known[$lower])) {
            return $known[$lower];
        }

        $slug = preg_replace('/[^a-z0-9]+/', '', Str::slug($lower, ''));

        return substr($slug, 0, 10) ?: 'lang';
    }

    /** @param array<string, string> $map */
    protected function persistTranslationMap(Language $language, array $map): void
    {
        foreach ($this->keyDefinitions() as $key => $meta) {
            $value = trim((string) ($map[$key] ?? $meta['default'] ?? ''));
            RegistrationTranslation::query()->updateOrCreate(
                [
                    'language_id' => $language->id,
                    'translation_key' => $key,
                ],
                ['value' => $value]
            );
        }
    }

    /**
     * @param array<string, string> $strings
     * @return array<string, string>|null
     */
    protected function translateMapViaGemini(array $strings, string $targetLanguageName): ?array
    {
        $targetLanguageName = trim($targetLanguageName);
        if ($targetLanguageName === '') {
            return null;
        }

        $gemini = app(GeminiService::class);
        $payload = json_encode($strings, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($payload === false) {
            return null;
        }

        $prompt = <<<PROMPT
You are a professional UI translator for a healthcare registration website in India.
Translate every string VALUE in the JSON below from English to "{$targetLanguageName}".

Rules:
- Return ONLY one valid JSON object with the EXACT same keys as the input.
- Keep the token :type unchanged inside values (example: "Create :type Account" must still contain :type).
- Do not translate brand name "Carelix".
- Keep ₹ symbol if present.
- Natural, clear wording for Indian users.
- No markdown, no code fences, no explanation.

Input JSON:
{$payload}
PROMPT;

        $raw = $gemini->generateText($prompt, 0.15, 8192);
        if ($raw === null || trim($raw) === '') {
            Log::warning('Registration i18n: Gemini returned empty', ['language' => $targetLanguageName]);

            return null;
        }

        return $this->parseTranslationJsonResponse($raw, array_keys($strings));
    }

    /**
     * @param list<string> $expectedKeys
     * @return array<string, string>|null
     */
    protected function parseTranslationJsonResponse(string $raw, array $expectedKeys): ?array
    {
        $raw = trim($raw);
        $raw = preg_replace('/^```(?:json)?\s*/i', '', $raw) ?? $raw;
        $raw = preg_replace('/\s*```\s*$/', '', $raw) ?? $raw;

        $decoded = json_decode($raw, true);
        if (! is_array($decoded)) {
            if (preg_match('/\{[\s\S]*\}/', $raw, $m)) {
                $decoded = json_decode($m[0], true);
            }
        }

        if (! is_array($decoded)) {
            Log::warning('Registration i18n: could not parse Gemini JSON', ['snippet' => substr($raw, 0, 400)]);

            return null;
        }

        $result = [];
        foreach ($expectedKeys as $key) {
            if (isset($decoded[$key]) && is_string($decoded[$key])) {
                $result[$key] = trim($decoded[$key]);
            }
        }

        return count($result) >= (int) (count($expectedKeys) * 0.5) ? $result : null;
    }

    protected function codeExists(string $code, ?int $exceptLanguageId): bool
    {
        $query = Language::query()->where('code', $code);
        if ($exceptLanguageId) {
            $query->where('id', '!=', $exceptLanguageId);
        }

        return $query->exists();
    }
}
