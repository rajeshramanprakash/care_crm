<?php

namespace App\Services;

class LeadStatusRemarkAiService
{
    public function __construct(
        protected GeminiService $gemini
    ) {}

    /**
     * @return array{success: bool, remark?: string, original_remark?: string, ai_token?: string, message: string, ai_disabled?: bool}
     */
    public function polish(string $draft, ?string $leadStatus = null, int $entityId = 0, string $scope = 'lead'): array
    {
        $apiKey = (string) config('services.gemini.api_key', '');
        if ($apiKey === '') {
            return [
                'success' => false,
                'message' => 'AI is not configured on this server (GEMINI_API_KEY missing).',
                'ai_disabled' => true,
            ];
        }

        $draft = trim($draft);
        if ($draft === '') {
            return [
                'success' => false,
                'message' => 'Please type your status remark before using Generate with AI.',
            ];
        }

        if (mb_strlen($draft) < 3) {
            return [
                'success' => false,
                'message' => 'Please enter at least a few words describing the status update.',
            ];
        }

        $prompt = "You translate sales status notes into clear English. You must NOT rewrite, expand, or add context.\n\n"
            ."TASK:\n"
            ."Convert the staff draft below into English only. Say exactly what they wrote — nothing more.\n\n"
            ."STRICT RULES:\n"
            ."- Literal translation / light grammar fix only. Same meaning, same brevity.\n"
            ."- NEVER add actions or facts not in the draft (e.g. do NOT say \"contacted the lead\", \"inquired about well-being\", \"follow-up scheduled\" unless the draft says that).\n"
            ."- NEVER use CRM jargon or assume a phone call happened unless the draft mentions it.\n"
            ."- If the draft is a short greeting or one line, the output must stay a short greeting or one line in English.\n"
            ."- Example: draft \"sir kese ho ap?\" → output \"Sir, how are you?\" (NOT \"Contacted the lead to inquire about their well-being.\")\n"
            ."- Example: draft \"kal call karna hai\" → output \"Need to call tomorrow.\" (do not add extra detail)\n"
            ."- Fix spelling/grammar only where needed. Professional but faithful tone.\n"
            ."- One short paragraph max; no bullets, headings, markdown, or quotes.\n"
            ."- Output ONLY the English text, nothing else.\n\n"
            ."STAFF DRAFT (translate this exactly):\n---\n"
            .$draft
            ."\n---";

        $text = $this->gemini->generateText($prompt, 0.15, 512);
        $text = is_string($text) ? trim($text) : '';

        if ($text === '') {
            return [
                'success' => false,
                'message' => 'Could not generate text right now. Please try again.',
            ];
        }

        if (mb_strlen($text) > 5000) {
            $text = mb_substr($text, 0, 5000);
        }

        $token = $this->makeAiToken($scope, $entityId, $draft, $text);

        return [
            'success' => true,
            'remark' => $text,
            'original_remark' => $draft,
            'ai_token' => $token,
            'message' => 'English translation ready. Check it matches your notes, then save.',
        ];
    }

    public function makeAiToken(string $scope, int $entityId, string $original, string $remark): string
    {
        return hash_hmac(
            'sha256',
            $scope.'|'.$entityId.'|'.trim($original).'|'.trim($remark),
            (string) config('app.key')
        );
    }

    public function verifyAiToken(string $scope, int $entityId, string $original, string $remark, ?string $token): bool
    {
        if ($token === null || $token === '') {
            return false;
        }

        return hash_equals($this->makeAiToken($scope, $entityId, $original, $remark), $token);
    }
}
