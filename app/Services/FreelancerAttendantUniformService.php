<?php

namespace App\Services;

use App\Models\JobRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FreelancerAttendantUniformService
{
    public function getUniformDressType(JobRequest $freelancer): ?string
    {
        return $freelancer->getUniformDressType();
    }

    /**
     * @return array{success: bool, message?: string, preview_url?: string, path?: string}
     */
    public function generateUniformPreview(JobRequest $freelancer): array
    {
        $dressType = $this->getUniformDressType($freelancer);
        if ($dressType === null) {
            return ['success' => false, 'message' => 'AI uniform dress is only for freelancers with Attendant or Nurse service.'];
        }

        $uploadPath = $this->resolveUploadPath($freelancer);
        if ($uploadPath === null) {
            return ['success' => false, 'message' => 'Freelancer has not uploaded a profile photo yet.'];
        }

        $templatePath = $this->templatePathForDressType($dressType);
        if ($templatePath === null || ! Storage::disk('public')->exists($templatePath)) {
            return ['success' => false, 'message' => ucfirst($dressType).' uniform template image is missing on the server.'];
        }

        $apiKey = (string) config('services.gemini.api_key', '');
        if ($apiKey === '') {
            return ['success' => false, 'message' => 'GEMINI_API_KEY is not configured in .env'];
        }

        $personBytes = Storage::disk('public')->get($uploadPath);
        $uniformBytes = Storage::disk('public')->get($templatePath);
        $personMime = $this->guessMime($uploadPath);
        $uniformMime = $this->guessMime($templatePath);
        $prompt = $this->promptForDressType($dressType);

        $imageBase64 = null;
        $lastError = null;
        foreach ($this->imageModelCandidates() as $model) {
            $result = $this->requestGeneratedImage($apiKey, $model, $prompt, $personBytes, $personMime, $uniformBytes, $uniformMime, $dressType);
            if ($result['image'] !== null) {
                $imageBase64 = $result['image'];
                break;
            }
            $lastError = $result['error'] ?? $lastError;
        }

        if ($imageBase64 === null) {
            $hint = $lastError ? ' Gemini: '.$lastError : '';

            return ['success' => false, 'message' => 'Could not generate uniform preview.'.$hint];
        }

        $binary = base64_decode($imageBase64, true);
        if ($binary === false || $binary === '') {
            return ['success' => false, 'message' => 'Invalid image data returned from Gemini.'];
        }

        $relative = 'documents/freelancers/processed/'.(int) $freelancer->id.'_'.$dressType.'_'.Str::uuid().'.png';

        if ($freelancer->profile_image_pending && $freelancer->profile_image_pending !== $freelancer->profile_image) {
            Storage::disk('public')->delete($freelancer->profile_image_pending);
        }

        Storage::disk('public')->put($relative, $binary);
        $freelancer->profile_image_pending = $relative;
        $freelancer->profile_image_status = 'pending_review';
        $freelancer->save();

        return [
            'success' => true,
            'message' => 'Preview generated. Review and approve below.',
            'path' => $relative,
            'preview_url' => Storage::disk('public')->url($relative),
        ];
    }

    public function approvePending(JobRequest $freelancer, ?int $reviewerId): void
    {
        if (! $freelancer->profile_image_pending) {
            throw new \InvalidArgumentException('No generated preview to approve.');
        }

        if ($freelancer->profile_image && $freelancer->profile_image !== $freelancer->profile_image_upload && $freelancer->profile_image !== $freelancer->profile_image_pending) {
            Storage::disk('public')->delete($freelancer->profile_image);
        }

        $freelancer->profile_image = $freelancer->profile_image_pending;
        $freelancer->profile_image_status = 'approved';
        $freelancer->profile_image_reviewed_at = now();
        $freelancer->profile_image_reviewed_by = $reviewerId;
        $freelancer->save();
    }

    protected function templatePathForDressType(string $dressType): ?string
    {
        return match ($dressType) {
            'attendant' => (string) config('freelancer_profile.attendant_uniform_template_path', 'assets/carelix-attendant-uniform-template.png'),
            'nurse' => (string) config('freelancer_profile.nurse_uniform_template_path', 'assets/carelix-nurse-uniform-template.png'),
            default => null,
        };
    }

    protected function promptForDressType(string $dressType): string
    {
        return match ($dressType) {
            'nurse' => <<<'PROMPT'
You are editing a professional profile photo for Carelix Healthcare nursing staff.

Image 1: A photo of a person (keep their face, skin tone, hair, and pose natural and recognizable).
Image 2: A reference light sky-blue medical nurse scrub top with dark navy blue V-neck trim, sleeve cuffs, pocket trim, and Carelix Healthcare logo — use this exact uniform design.

Task: Create a realistic professional portrait where the person from image 1 is wearing the nurse uniform from image 2. The uniform should fit naturally. Keep the background clean and professional (neutral/light). Output ONLY the final portrait image — photorealistic, suitable for a healthcare staffing profile.
PROMPT,
            default => <<<'PROMPT'
You are editing a professional profile photo for Carelix Healthcare attendant staff.

Image 1: A photo of a person (keep their face, skin tone, hair, and pose natural and recognizable).
Image 2: A reference mint-green medical attendant uniform top with dark green collar, cuffs, pocket trim, and Carelix Healthcare logo — use this exact uniform design.

Task: Create a realistic professional portrait where the person from image 1 is wearing the attendant uniform from image 2. The uniform should fit naturally. Keep the background clean and professional (neutral/light). Output ONLY the final portrait image — photorealistic, suitable for a healthcare staffing profile.
PROMPT,
        };
    }

    protected function resolveUploadPath(JobRequest $freelancer): ?string
    {
        $upload = trim((string) ($freelancer->profile_image_upload ?? ''));
        if ($upload !== '' && Storage::disk('public')->exists($upload)) {
            return $upload;
        }

        if ($freelancer->requiresUniformApproval() && $freelancer->profile_image_status !== 'approved') {
            $legacy = trim((string) ($freelancer->profile_image ?? ''));
            if ($legacy !== '' && Storage::disk('public')->exists($legacy)) {
                return $legacy;
            }
        }

        return null;
    }

    /**
     * @return list<string>
     */
    protected function imageModelCandidates(): array
    {
        $primary = trim((string) config('freelancer_profile.image_model', ''));
        $pool = array_merge(
            [$primary],
            (array) config('freelancer_profile.image_model_fallbacks', [])
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
        string $personBytes,
        string $personMime,
        string $uniformBytes,
        string $uniformMime,
        string $dressType
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
                            ['inline_data' => ['mime_type' => $personMime, 'data' => base64_encode($personBytes)]],
                            ['inline_data' => ['mime_type' => $uniformMime, 'data' => base64_encode($uniformBytes)]],
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
                Log::warning('Gemini freelancer uniform generation failed', [
                    'dress_type' => $dressType,
                    'model' => $model,
                    'status' => $response->status(),
                    'message' => $errMsg,
                ]);

                return ['image' => null, 'error' => '['.$model.'] '.$errMsg];
            }

            $image = $this->extractImageBase64($response->json());
            if ($image === null) {
                return ['image' => null, 'error' => '['.$model.'] No image in response.'];
            }

            return ['image' => $image, 'error' => null];
        } catch (\Throwable $e) {
            Log::error('Gemini freelancer uniform exception', ['dress_type' => $dressType, 'model' => $model, 'error' => $e->getMessage()]);

            return ['image' => null, 'error' => '['.$model.'] '.$e->getMessage()];
        }
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

    protected function parseApiErrorMessage(?array $data, int $status): string
    {
        if (is_array($data) && isset($data['error']['message'])) {
            return (string) $data['error']['message'];
        }

        return 'HTTP '.$status;
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
