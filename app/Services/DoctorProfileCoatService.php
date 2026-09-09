<?php

namespace App\Services;

use App\Models\DoctorRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DoctorProfileCoatService
{
    /**
     * Generate branded coat overlay preview; saves to profile_image_pending.
     *
     * @return array{success: bool, message?: string, preview_url?: string, path?: string}
     */
    public function generateCoatPreview(DoctorRequest $doctor): array
    {
        $uploadPath = $this->resolveUploadPath($doctor);
        if ($uploadPath === null) {
            return ['success' => false, 'message' => 'Doctor has not uploaded a profile photo yet.'];
        }

        $coatPath = (string) config('doctor_profile.coat_template_path', 'assets/carelix-doctor-coat-template.png');
        if (! Storage::disk('public')->exists($coatPath)) {
            return ['success' => false, 'message' => 'CareLix coat template image is missing on the server.'];
        }

        $apiKey = (string) config('services.gemini.api_key', '');
        if ($apiKey === '') {
            return ['success' => false, 'message' => 'GEMINI_API_KEY is not configured in .env'];
        }

        $doctorBytes = Storage::disk('public')->get($uploadPath);
        $coatBytes = Storage::disk('public')->get($coatPath);
        $doctorMime = $this->guessMime($uploadPath);
        $coatMime = $this->guessMime($coatPath);

        $prompt = <<<'PROMPT'
You are editing a professional doctor profile photo for Carelix Healthcare.

Image 1: A photo of a doctor/person (keep their face, skin tone, hair, and pose natural and recognizable).
Image 2: A reference white medical lab coat with green trim and the Carelix Healthcare logo — use this exact coat design.

Task: Create a realistic professional portrait where the person from image 1 is wearing the Carelix lab coat from image 2. The coat should fit naturally on their body. Keep the background clean and professional (neutral/light). Output ONLY the final portrait image — photorealistic, suitable for a hospital website profile.
PROMPT;

        $imageBase64 = null;
        $lastError = null;
        foreach ($this->imageModelCandidates() as $model) {
            $result = $this->requestGeneratedImage($apiKey, $model, $prompt, $doctorBytes, $doctorMime, $coatBytes, $coatMime);
            if ($result['image'] !== null) {
                $imageBase64 = $result['image'];
                break;
            }
            $lastError = $result['error'] ?? $lastError;
        }

        if ($imageBase64 === null) {
            $hint = $lastError
                ? ' Gemini: '.$lastError
                : ' Set GEMINI_IMAGE_MODEL=gemini-2.5-flash-image in .env (see config/doctor_profile.php).';

            return ['success' => false, 'message' => 'Could not generate coat preview.'.$hint];
        }

        $binary = base64_decode($imageBase64, true);
        if ($binary === false || $binary === '') {
            return ['success' => false, 'message' => 'Invalid image data returned from Gemini.'];
        }

        $ext = 'png';
        $relative = 'documents/doctors/processed/'.(int) $doctor->id.'_'.Str::uuid().'.'.$ext;

        if ($doctor->profile_image_pending && $doctor->profile_image_pending !== $doctor->profile_image) {
            Storage::disk('public')->delete($doctor->profile_image_pending);
        }

        Storage::disk('public')->put($relative, $binary);
        $doctor->profile_image_pending = $relative;
        $doctor->profile_image_status = 'pending_review';
        $doctor->save();

        return [
            'success' => true,
            'message' => 'Preview generated. Review and approve below.',
            'path' => $relative,
            'preview_url' => Storage::disk('public')->url($relative),
        ];
    }

    public function approvePending(DoctorRequest $doctor, ?int $reviewerId): void
    {
        if (! $doctor->profile_image_pending) {
            throw new \InvalidArgumentException('No generated preview to approve.');
        }

        if ($doctor->profile_image && $doctor->profile_image !== $doctor->profile_image_upload && $doctor->profile_image !== $doctor->profile_image_pending) {
            Storage::disk('public')->delete($doctor->profile_image);
        }

        $doctor->profile_image = $doctor->profile_image_pending;
        $doctor->profile_image_status = 'approved';
        $doctor->profile_image_reviewed_at = now();
        $doctor->profile_image_reviewed_by = $reviewerId;
        $doctor->save();
    }

    public function publicProfileImagePath(DoctorRequest $doctor): ?string
    {
        if ($doctor->profile_image_status === 'approved' && $doctor->profile_image) {
            return $doctor->profile_image;
        }

        return null;
    }

    protected function resolveUploadPath(DoctorRequest $doctor): ?string
    {
        $upload = trim((string) ($doctor->profile_image_upload ?? ''));
        if ($upload !== '' && Storage::disk('public')->exists($upload)) {
            return $upload;
        }

        $legacy = trim((string) ($doctor->profile_image ?? ''));
        if ($legacy !== '' && Storage::disk('public')->exists($legacy)) {
            return $legacy;
        }

        return null;
    }

    /**
     * @return list<string>
     */
    protected function imageModelCandidates(): array
    {
        $primary = trim((string) config('doctor_profile.image_model', ''));
        $pool = array_merge(
            [$primary],
            (array) config('doctor_profile.image_model_fallbacks', [])
        );

        return array_values(array_unique(array_filter($pool)));
    }

    /**
     * @return array{image: ?string, error: ?string}
     */
    protected function requestGeneratedImage(
        string $apiKey,
        string $model,
        string $prompt,
        string $doctorBytes,
        string $doctorMime,
        string $coatBytes,
        string $coatMime
    ): array {
        $model = ltrim(str_replace(['models/', 'models:'], '', $model), '/');
        $url = 'https://generativelanguage.googleapis.com/v1beta/models/'.$model.':generateContent?key='.$apiKey;

        try {
            $response = Http::timeout(180)
                ->acceptJson()
                ->asJson()
                ->post($url, [
                    'contents' => [[
                        'parts' => [
                            ['inline_data' => ['mime_type' => $doctorMime, 'data' => base64_encode($doctorBytes)]],
                            ['inline_data' => ['mime_type' => $coatMime, 'data' => base64_encode($coatBytes)]],
                            ['text' => $prompt],
                        ],
                    ]],
                    'generationConfig' => [
                        'responseModalities' => ['IMAGE'],
                        'temperature' => 0.35,
                    ],
                    'safetySettings' => [
                        ['category' => 'HARM_CATEGORY_HARASSMENT', 'threshold' => 'BLOCK_NONE'],
                        ['category' => 'HARM_CATEGORY_HATE_SPEECH', 'threshold' => 'BLOCK_NONE'],
                        ['category' => 'HARM_CATEGORY_SEXUALLY_EXPLICIT', 'threshold' => 'BLOCK_NONE'],
                        ['category' => 'HARM_CATEGORY_DANGEROUS_CONTENT', 'threshold' => 'BLOCK_NONE'],
                    ],
                ]);

            if (! $response->successful()) {
                $errMsg = $this->parseApiErrorMessage($response->json(), $response->status());
                Log::warning('Gemini coat generation failed', [
                    'model' => $model,
                    'status' => $response->status(),
                    'message' => $errMsg,
                    'body' => substr($response->body(), 0, 600),
                ]);

                return ['image' => null, 'error' => '['.$model.'] '.$errMsg];
            }

            $image = $this->extractImageBase64($response->json());
            if ($image === null) {
                $errMsg = 'No image in response (model may not support image output).';

                return ['image' => null, 'error' => '['.$model.'] '.$errMsg];
            }

            return ['image' => $image, 'error' => null];
        } catch (\Throwable $e) {
            Log::error('Gemini coat generation exception', ['model' => $model, 'error' => $e->getMessage()]);

            return ['image' => null, 'error' => '['.$model.'] '.$e->getMessage()];
        }
    }

    protected function parseApiErrorMessage(?array $data, int $status): string
    {
        if (is_array($data) && isset($data['error']['message'])) {
            return (string) $data['error']['message'];
        }

        return 'HTTP '.$status;
    }

    protected function extractImageBase64(?array $data): ?string
    {
        if (! is_array($data)) {
            return null;
        }
        foreach ($data['candidates'] ?? [] as $candidate) {
            foreach ($candidate['content']['parts'] ?? [] as $part) {
                $inline = $part['inlineData'] ?? $part['inline_data'] ?? null;
                if (is_array($inline) && ! empty($inline['data'])) {
                    return (string) $inline['data'];
                }
            }
        }

        return null;
    }

    protected function guessMime(string $path): string
    {
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        return match ($ext) {
            'jpg', 'jpeg' => 'image/jpeg',
            'webp' => 'image/webp',
            'gif' => 'image/gif',
            default => 'image/png',
        };
    }
}
