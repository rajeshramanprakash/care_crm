<?php

namespace App\Services;

class ApiRelayUrlResolver
{
    public static function resolve(string $targetUrl): string
    {
        $enabled = (bool) config('services.api_relay.enabled', false);
        $relayBaseUrl = trim((string) config('services.api_relay.url', ''));
        $targetParam = trim((string) config('services.api_relay.target_param', 'target_url'));

        if (! $enabled || $relayBaseUrl === '') {
            return $targetUrl;
        }

        $separator = str_contains($relayBaseUrl, '?') ? '&' : '?';

        return $relayBaseUrl.$separator.rawurlencode($targetParam).'='.rawurlencode($targetUrl);
    }
}
