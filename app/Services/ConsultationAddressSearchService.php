<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Address autocomplete + geocode for CareWeb (Google when allowed, else OpenStreetMap).
 */
class ConsultationAddressSearchService
{
    private const AUTOCOMPLETE_CACHE_SECONDS = 120;

    private const RESOLVE_CACHE_SECONDS = 3600;
    public function __construct(
        private readonly GooglePlacesService $google,
        private readonly NominatimAddressService $nominatim,
    ) {}

    public function lastError(): ?string
    {
        return $this->google->lastError();
    }

    public function isConfigured(): bool
    {
        return $this->healthCheck()['ok'];
    }

    /**
     * @return array{ok: bool, provider: string|null, message: string}
     */
    public function healthCheck(): array
    {
        if ($this->google->isConfigured()) {
            $googleHealth = $this->google->healthCheck();
            if ($googleHealth['ok']) {
                return [
                    'ok' => true,
                    'provider' => 'google',
                    'message' => '',
                ];
            }
        }

        $osmHealth = $this->nominatim->healthCheck();
        if ($osmHealth['ok']) {
            $msg = $this->google->isConfigured() && $this->google->lastError()
                ? 'Google Maps key is blocked; using OpenStreetMap for address search. Fix the key in Google Cloud to use Google Places.'
                : '';

            return [
                'ok' => true,
                'provider' => 'nominatim',
                'message' => $msg,
            ];
        }

        return [
            'ok' => false,
            'provider' => null,
            'message' => $this->google->lastError() ?: $osmHealth['message'],
        ];
    }

    /**
     * @return list<array{place_id: string, description: string}>
     */
    public function autocomplete(string $input, ?string $city = null): array
    {
        $input = trim($input);
        if (strlen($input) < 3) {
            return [];
        }

        $cityKey = $city !== null ? trim($city) : '';
        $cacheKey = 'consult_addr_ac:'.md5(Str::lower($input).'|'.Str::lower($cityKey));

        return Cache::remember($cacheKey, self::AUTOCOMPLETE_CACHE_SECONDS, function () use ($input, $city) {
            if ($this->google->isConfigured()) {
                $fromGoogle = $this->google->autocomplete($input, $city);
                if ($fromGoogle !== []) {
                    return $fromGoogle;
                }
            }

            return $this->nominatim->autocomplete($input, $city);
        });
    }

    /**
     * @return array{address: string, lat: float, lng: float}|null
     */
    public function resolvePlace(string $placeId, ?string $city = null): ?array
    {
        $placeId = trim($placeId);
        if ($placeId === '') {
            return null;
        }

        $city = $city !== null ? trim($city) : '';

        if (str_starts_with($placeId, 'osm:')) {
            $resolved = $this->nominatim->resolvePlace($placeId);
            if ($resolved === null || $city === '') {
                return $resolved;
            }
            if ($this->addressMatchesCity($resolved, $city)) {
                return $resolved;
            }

            return null;
        }

        if ($this->google->isConfigured()) {
            $cacheKey = 'consult_addr_resolve:'.md5($placeId.'|'.Str::lower($city));

            return Cache::remember($cacheKey, self::RESOLVE_CACHE_SECONDS, function () use ($placeId, $city) {
                return $this->google->resolvePlace($placeId, $city !== '' ? $city : null);
            });
        }

        return null;
    }

    /**
     * @param  array{address: string, lat: float, lng: float}  $resolved
     */
    private function addressMatchesCity(array $resolved, string $city): bool
    {
        $address = strtolower((string) ($resolved['address'] ?? ''));
        $cityLower = strtolower($city);

        return $address !== '' && str_contains($address, $cityLower);
    }

    /**
     * @return array{address: string, lat: float, lng: float}|null
     */
    public function geocode(string $address, ?string $city = null): ?array
    {
        if ($this->google->isConfigured()) {
            $fromGoogle = $this->google->geocode($address, $city);
            if ($fromGoogle !== null) {
                return $fromGoogle;
            }
        }

        return $this->nominatim->geocode($address, $city);
    }

    /**
     * @return array{address: string, lat: float, lng: float}|null
     */
    public function reverseGeocode(float $lat, float $lng, ?string $city = null): ?array
    {
        $city = $city !== null ? trim($city) : '';

        if ($this->google->isConfigured()) {
            $fromGoogle = $this->google->reverseGeocode($lat, $lng, $city !== '' ? $city : null);
            if ($fromGoogle !== null) {
                return $fromGoogle;
            }
        }

        return $this->nominatim->reverseGeocode($lat, $lng, $city !== '' ? $city : null);
    }
}
