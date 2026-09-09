<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WabaLoginOtpService
{
    /**
     * @return array{success: bool, message: string}
     */
    public function send(string $mobile, string $otp): array
    {
        $apiKey = trim((string) config('services.waba.api_key', ''));
        if ($apiKey === '') {
            return [
                'success' => false,
                'message' => 'WhatsApp OTP is not configured. Please contact support.',
            ];
        }

        $apiUrl = ApiRelayUrlResolver::resolve(
            trim((string) config('services.waba.api_url', 'https://api.waba.anohim.in/api/v1/send-template'))
        );

        $payload = [
            'phone' => $mobile,
            'country_code' => (string) config('services.waba.country_code', '91'),
            'template_name' => (string) config('services.waba.login_template', 'login_otpp'),
            'template_language' => (string) config('services.waba.login_template_language', 'en_US'),
            'components' => [
                [
                    'type' => 'body',
                    'parameters' => [
                        ['type' => 'text', 'text' => $otp],
                    ],
                ],
                [
                    'type' => 'button',
                    'sub_type' => 'url',
                    'index' => '0',
                    'parameters' => [
                        ['type' => 'text', 'text' => $otp],
                    ],
                ],
            ],
        ];

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'x-api-key' => $apiKey,
            ])->timeout(15)->post($apiUrl, $payload);

            if ($response->successful() && ! $this->responseIndicatesFailure($response->json())) {
                return [
                    'success' => true,
                    'message' => 'OTP sent to your WhatsApp number',
                ];
            }

            $apiMessage = $this->extractErrorMessage($response->json(), $response->body());

            Log::error('WABA login OTP send failed', [
                'mobile' => $mobile,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return [
                'success' => false,
                'message' => $apiMessage !== ''
                    ? 'Could not send OTP: '.$apiMessage
                    : 'Could not send OTP. Please try again.',
            ];
        } catch (\Throwable $e) {
            Log::error('WABA login OTP send exception', [
                'mobile' => $mobile,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Could not send OTP. Please check your connection and try again.',
            ];
        }
    }

    /**
     * @param  array<string, mixed>|null  $data
     */
    private function responseIndicatesFailure(?array $data): bool
    {
        if ($data === null) {
            return false;
        }

        if (array_key_exists('success', $data) && $data['success'] === false) {
            return true;
        }

        if (array_key_exists('error', $data) && $data['error'] !== null && $data['error'] !== '') {
            return true;
        }

        $status = strtolower((string) ($data['status'] ?? ''));
        if (in_array($status, ['error', 'failed', 'failure'], true)) {
            return true;
        }

        return false;
    }

    /**
     * @param  array<string, mixed>|null  $data
     */
    private function extractErrorMessage(?array $data, string $body): string
    {
        if ($data !== null) {
            foreach (['message', 'error', 'detail', 'description'] as $key) {
                if (! empty($data[$key]) && is_string($data[$key])) {
                    return $data[$key];
                }
            }

            if (isset($data['errors']) && is_array($data['errors'])) {
                $first = reset($data['errors']);
                if (is_string($first)) {
                    return $first;
                }
                if (is_array($first) && ! empty($first['message']) && is_string($first['message'])) {
                    return $first['message'];
                }
            }
        }

        return strlen($body) > 200 ? substr($body, 0, 200).'…' : $body;
    }
}
