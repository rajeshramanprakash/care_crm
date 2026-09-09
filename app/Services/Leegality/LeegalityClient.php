<?php

namespace App\Services\Leegality;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class LeegalityClient
{
    public function isConfigured(): bool
    {
        return trim((string) config('leegality.auth_token')) !== ''
            && trim((string) config('leegality.profile_id')) !== '';
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function createSignRequest(array $payload): array
    {
        return $this->request('POST', '/v3.0/sign/request', $payload);
    }

    /**
     * @return array<string, mixed>
     */
    public function documentDetails(string $documentId, bool $file = false, bool $auditTrail = false): array
    {
        $query = [
            'documentId' => $documentId,
            'file' => $file ? 'true' : 'false',
            'auditTrail' => $auditTrail ? 'true' : 'false',
        ];

        return $this->request('GET', '/v3.3/document/details', null, $query);
    }

    /**
     * @return array<string, mixed>
     */
    public function fetchDocument(string $documentId, string $downloadType = 'DOCUMENT'): array
    {
        return $this->request('GET', '/v3.3/document/fetchDocument', null, [
            'documentId' => $documentId,
            'documentDownloadType' => strtoupper($downloadType),
        ]);
    }

    /**
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function addMySignature(string $documentId, array $options = []): array
    {
        $payload = array_merge([
            'documentId' => $documentId,
        ], $options);

        try {
            return $this->request('POST', '/v3.0/document/addMySignature', $payload);
        } catch (\Throwable $e) {
            try {
                return $this->request('POST', '/v2.0/document/'.$documentId.'/add-signature', $payload);
            } catch (\Throwable $e2) {
                return $this->request('POST', '/v2/documents/'.$documentId.'/add-my-signature', $payload);
            }
        }
    }

    /**
     * @param  array<string, mixed>|null  $json
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     */
    private function request(string $method, string $path, ?array $json = null, array $query = []): array
    {
        $token = trim((string) config('leegality.auth_token'));
        if ($token === '') {
            throw new RuntimeException('Leegality auth token is not configured.');
        }

        $url = rtrim((string) config('leegality.base_url'), '/').'/'.ltrim($path, '/');
        $timeout = max(10, (int) config('leegality.timeout_seconds', 60));

        $pending = Http::withHeaders([
            'X-Auth-Token' => $token,
            'Accept' => 'application/json',
        ])->timeout($timeout);

        if ($method === 'GET') {
            $response = $pending->get($url, $query);
        } else {
            $response = $pending->asJson()->post($url, $json ?? []);
        }

        $body = $response->json();
        if (! is_array($body)) {
            Log::error('Leegality API non-JSON response', [
                'url' => $url,
                'status' => $response->status(),
                'body' => substr($response->body(), 0, 500),
            ]);
            throw new RuntimeException('Leegality API returned an invalid response.');
        }

        if (! $response->successful()) {
            Log::error('Leegality API HTTP error', [
                'url' => $url,
                'status' => $response->status(),
                'body' => $body,
            ]);
            throw new RuntimeException($this->firstMessage($body) ?: 'Leegality API request failed.');
        }

        return $body;
    }

    /**
     * @param  array<string, mixed>  $body
     */
    public function firstMessage(array $body): ?string
    {
        $messages = $body['messages'] ?? null;
        if (! is_array($messages) || $messages === []) {
            return null;
        }
        $first = $messages[0] ?? null;
        if (is_array($first)) {
            return isset($first['message']) ? (string) $first['message'] : null;
        }

        return is_string($first) ? $first : null;
    }
}
