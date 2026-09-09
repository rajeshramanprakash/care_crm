<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class GooglePlacesService
{
    private const CITY_CACHE_TTL_SECONDS = 86400;

    private const DEFAULT_CITY_RADIUS_METERS = 30000;

    private ?string $lastError = null;

    public function isConfigured(): bool
    {
        return $this->apiKey() !== '';
    }

    public function lastError(): ?string
    {
        return $this->lastError;
    }

    /**
     * Quick check whether the key can call Geocoding (used for CareWeb status).
     *
     * @return array{ok: bool, message: string}
     */
    public function healthCheck(): array
    {
        $this->lastError = null;
        if (! $this->isConfigured()) {
            return [
                'ok' => false,
                'message' => 'GOOGLE_MAPS_API_KEY is empty in carecrm/.env',
            ];
        }

        $result = $this->geocode('New Delhi, India');
        if ($result !== null) {
            return ['ok' => true, 'message' => ''];
        }

        $hint = 'In Google Cloud Console → APIs & Services: enable Places API (New) and Geocoding API. '
            .'For this server key use Application restrictions: None (or IP addresses), not HTTP referrers.';

        return [
            'ok' => false,
            'message' => $this->lastError ?: $hint,
        ];
    }

    private function apiKey(): string
    {
        return trim((string) config('google.maps_api_key', ''));
    }

    private function noteGoogleFailure(?string $status, ?string $errorMessage = null): void
    {
        if ($errorMessage !== null && $errorMessage !== '') {
            $this->lastError = $errorMessage;

            return;
        }
        if ($status !== null && $status !== '' && $status !== 'OK' && $status !== 'ZERO_RESULTS') {
            $this->lastError = 'Google API: '.$status;
        }
    }

    /**
     * @return array{city: string, lat: float, lng: float, radius: float, viewport: array{low: array{latitude: float, longitude: float}, high: array{latitude: float, longitude: float}}|null}|null
     */
    public function resolveCityBounds(string $city): ?array
    {
        $city = trim($city);
        if ($city === '') {
            return null;
        }

        $cacheKey = 'google_city_bounds:'.Str::slug($city);

        return Cache::remember($cacheKey, self::CITY_CACHE_TTL_SECONDS, function () use ($city) {
            return $this->fetchCityBounds($city);
        });
    }

    /**
     * @return array{city: string, lat: float, lng: float, radius: float, viewport: array{low: array{latitude: float, longitude: float}, high: array{latitude: float, longitude: float}}|null}|null
     */
    private function fetchCityBounds(string $city): ?array
    {
        $key = $this->apiKey();
        if ($key === '') {
            return null;
        }

        try {
            $response = Http::timeout(10)->get('https://maps.googleapis.com/maps/api/geocode/json', [
                'address' => $city.', India',
                'components' => 'country:IN',
                'key' => $key,
            ]);
        } catch (\Throwable $e) {
            return null;
        }

        $status = (string) ($response->json('status') ?? '');
        if (! $response->successful() || $status !== 'OK') {
            return null;
        }

        $result = $response->json('results.0');
        if (! is_array($result)) {
            return null;
        }

        $lat = $result['geometry']['location']['lat'] ?? null;
        $lng = $result['geometry']['location']['lng'] ?? null;
        if (! is_numeric($lat) || ! is_numeric($lng)) {
            return null;
        }

        $radius = self::DEFAULT_CITY_RADIUS_METERS;
        $bounds = $result['geometry']['bounds'] ?? $result['geometry']['viewport'] ?? null;
        $viewport = null;
        if (is_array($bounds)) {
            $ne = $bounds['northeast'] ?? null;
            $sw = $bounds['southwest'] ?? null;
            if (is_array($ne) && is_array($sw)
                && is_numeric($ne['lat'] ?? null) && is_numeric($ne['lng'] ?? null)
                && is_numeric($sw['lat'] ?? null) && is_numeric($sw['lng'] ?? null)) {
                $viewport = [
                    'low' => [
                        'latitude' => (float) $sw['lat'],
                        'longitude' => (float) $sw['lng'],
                    ],
                    'high' => [
                        'latitude' => (float) $ne['lat'],
                        'longitude' => (float) $ne['lng'],
                    ],
                ];
                $radius = max(
                    self::DEFAULT_CITY_RADIUS_METERS,
                    $this->haversineMeters((float) $lat, (float) $lng, (float) $ne['lat'], (float) $ne['lng']),
                    $this->haversineMeters((float) $lat, (float) $lng, (float) $sw['lat'], (float) $sw['lng'])
                );
            }
        }

        return [
            'city' => $city,
            'lat' => (float) $lat,
            'lng' => (float) $lng,
            'radius' => (float) $radius,
            'viewport' => $viewport,
        ];
    }

    private function haversineMeters(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371000;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return $earthRadius * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    /**
     * @param  array{address: string, lat: float, lng: float}  $resolved
     * @param  array{city: string, lat: float, lng: float, radius: float, viewport: array|null}|null  $bounds
     */
    private function isWithinCity(array $resolved, ?string $city, ?array $bounds): bool
    {
        if ($city === null || $city === '' || $bounds === null) {
            return true;
        }

        $address = (string) ($resolved['address'] ?? '');
        if ($address !== '' && Str::contains(Str::lower($address), Str::lower($city))) {
            return true;
        }

        $lat = $resolved['lat'] ?? null;
        $lng = $resolved['lng'] ?? null;
        if (! is_numeric($lat) || ! is_numeric($lng)) {
            return false;
        }

        $distance = $this->haversineMeters(
            (float) $bounds['lat'],
            (float) $bounds['lng'],
            (float) $lat,
            (float) $lng
        );

        return $distance <= (float) $bounds['radius'];
    }

    /**
     * @param  list<array{place_id: string, description: string}>  $predictions
     * @return list<array{place_id: string, description: string}>
     */
    private function filterPredictionsByCity(array $predictions, string $city): array
    {
        $cityLower = Str::lower($city);
        $filtered = array_values(array_filter($predictions, static function (array $row) use ($cityLower): bool {
            $desc = Str::lower((string) ($row['description'] ?? ''));

            return $desc !== '' && str_contains($desc, $cityLower);
        }));

        return $filtered;
    }

    /**
     * @return list<array{place_id: string, description: string}>
     */
    public function autocomplete(string $input, ?string $city = null): array
    {
        $this->lastError = null;
        $input = trim($input);
        if (strlen($input) < 3) {
            return [];
        }

        $city = $city !== null ? trim($city) : '';
        $bounds = $city !== '' ? $this->resolveCityBounds($city) : null;

        $query = $input;
        if ($city !== '' && ! Str::contains(Str::lower($input), Str::lower($city))) {
            $query = $city.', '.$input;
        }

        $fromNew = $this->autocompleteNew($query, $bounds);
        if ($fromNew !== null) {
            if ($city !== '') {
                return $this->filterPredictionsByCity($fromNew, $city);
            }

            return $fromNew;
        }

        $legacy = $this->autocompleteLegacy($query, $bounds);
        if ($city !== '') {
            return $this->filterPredictionsByCity($legacy, $city);
        }

        return $legacy;
    }

    /**
     * @param  array{city: string, lat: float, lng: float, radius: float, viewport: array|null}|null  $bounds
     * @return list<array{place_id: string, description: string}>|null
     */
    private function autocompleteNew(string $input, ?array $bounds): ?array
    {
        $key = $this->apiKey();
        if ($key === '') {
            return null;
        }

        $body = [
            'input' => $input,
            'includedRegionCodes' => ['in'],
        ];

        if ($bounds !== null) {
            if ($bounds['viewport'] !== null) {
                $body['locationRestriction'] = [
                    'rectangle' => $bounds['viewport'],
                ];
            } else {
                $body['locationRestriction'] = [
                    'circle' => [
                        'center' => [
                            'latitude' => $bounds['lat'],
                            'longitude' => $bounds['lng'],
                        ],
                        'radius' => (float) $bounds['radius'],
                    ],
                ];
            }
        }

        try {
            $response = Http::timeout(4)
                ->connectTimeout(2)
                ->withHeaders([
                    'X-Goog-Api-Key' => $key,
                    'Content-Type' => 'application/json',
                ])
                ->post('https://places.googleapis.com/v1/places:autocomplete', $body);
        } catch (\Throwable $e) {
            return null;
        }

        if (! $response->successful()) {
            $this->noteGoogleFailure(null, $response->json('error.message'));

            return null;
        }

        $predictions = [];
        foreach ($response->json('suggestions') ?? [] as $item) {
            if (! is_array($item)) {
                continue;
            }
            $pred = $item['placePrediction'] ?? null;
            if (! is_array($pred)) {
                continue;
            }
            $placeId = (string) ($pred['placeId'] ?? '');
            $text = (string) ($pred['text']['text'] ?? '');
            if ($text === '' && isset($pred['structuredFormat']['mainText']['text'])) {
                $text = (string) $pred['structuredFormat']['mainText']['text'];
            }
            if ($placeId !== '' && $text !== '') {
                $predictions[] = [
                    'place_id' => $placeId,
                    'description' => $text,
                ];
            }
        }

        return array_slice($predictions, 0, 6);
    }

    /**
     * @param  array{city: string, lat: float, lng: float, radius: float, viewport: array|null}|null  $bounds
     * @return list<array{place_id: string, description: string}>
     */
    private function autocompleteLegacy(string $input, ?array $bounds): array
    {
        $key = $this->apiKey();
        if ($key === '') {
            return [];
        }

        $params = [
            'input' => $input,
            'key' => $key,
            'components' => 'country:in',
        ];

        if ($bounds !== null) {
            $params['location'] = $bounds['lat'].','.$bounds['lng'];
            $params['radius'] = (int) min(50000, max(1000, (int) $bounds['radius']));
            $params['strictbounds'] = 'true';
        }

        try {
            $response = Http::timeout(4)
                ->connectTimeout(2)
                ->get('https://maps.googleapis.com/maps/api/place/autocomplete/json', $params);
        } catch (\Throwable $e) {
            return [];
        }

        $status = (string) ($response->json('status') ?? '');
        if (! $response->successful() || $status !== 'OK') {
            $this->noteGoogleFailure($status, $response->json('error_message'));

            return [];
        }

        $predictions = [];
        foreach ($response->json('predictions') ?? [] as $row) {
            if (! is_array($row)) {
                continue;
            }
            $placeId = (string) ($row['place_id'] ?? '');
            $description = (string) ($row['description'] ?? '');
            if ($placeId !== '' && $description !== '') {
                $predictions[] = [
                    'place_id' => $placeId,
                    'description' => $description,
                ];
            }
        }

        return array_slice($predictions, 0, 6);
    }

    /**
     * @return array{address: string, lat: float, lng: float}|null
     */
    public function resolvePlace(string $placeId, ?string $city = null): ?array
    {
        $this->lastError = null;
        $placeId = trim($placeId);
        if ($placeId === '') {
            return null;
        }

        $city = $city !== null ? trim($city) : '';
        $bounds = $city !== '' ? $this->resolveCityBounds($city) : null;

        $fromNew = $this->resolvePlaceNew($placeId);
        if ($fromNew === null) {
            $fromLegacy = $this->resolvePlaceLegacy($placeId);
            $fromNew = $fromLegacy;
        }

        if ($fromNew === null) {
            return null;
        }

        if (! $this->isWithinCity($fromNew, $city !== '' ? $city : null, $bounds)) {
            $this->lastError = $city !== ''
                ? 'Address must be in '.$city.'.'
                : 'Address is outside the selected city.';

            return null;
        }

        return $fromNew;
    }

    /**
     * @return array{address: string, lat: float, lng: float}|null
     */
    private function resolvePlaceNew(string $placeId): ?array
    {
        $key = $this->apiKey();
        if ($key === '') {
            return null;
        }

        try {
            $response = Http::timeout(4)
                ->connectTimeout(2)
                ->withHeaders([
                    'X-Goog-Api-Key' => $key,
                    'X-Goog-FieldMask' => 'location,formattedAddress',
                ])
                ->get('https://places.googleapis.com/v1/places/'.rawurlencode($placeId));
        } catch (\Throwable $e) {
            return null;
        }

        if (! $response->successful()) {
            return null;
        }

        $data = $response->json();
        $lat = $data['location']['latitude'] ?? null;
        $lng = $data['location']['longitude'] ?? null;
        if (! is_numeric($lat) || ! is_numeric($lng)) {
            return null;
        }

        return [
            'address' => (string) ($data['formattedAddress'] ?? ''),
            'lat' => (float) $lat,
            'lng' => (float) $lng,
        ];
    }

    /**
     * @return array{address: string, lat: float, lng: float}|null
     */
    private function resolvePlaceLegacy(string $placeId): ?array
    {
        $key = $this->apiKey();
        if ($key === '') {
            return null;
        }

        try {
            $response = Http::timeout(10)->get('https://maps.googleapis.com/maps/api/place/details/json', [
                'place_id' => $placeId,
                'fields' => 'formatted_address,geometry',
                'key' => $key,
            ]);
        } catch (\Throwable $e) {
            return null;
        }

        $status = (string) ($response->json('status') ?? '');
        if (! $response->successful() || $status !== 'OK') {
            $this->noteGoogleFailure($status, $response->json('error_message'));

            return null;
        }

        $result = $response->json('result');
        if (! is_array($result)) {
            return null;
        }

        $lat = $result['geometry']['location']['lat'] ?? null;
        $lng = $result['geometry']['location']['lng'] ?? null;
        if (! is_numeric($lat) || ! is_numeric($lng)) {
            return null;
        }

        return [
            'address' => (string) ($result['formatted_address'] ?? ''),
            'lat' => (float) $lat,
            'lng' => (float) $lng,
        ];
    }

    /**
     * @return array{address: string, lat: float, lng: float}|null
     */
    public function geocode(string $address, ?string $city = null): ?array
    {
        $this->lastError = null;
        $address = trim($address);
        if ($address === '') {
            return null;
        }

        $city = $city !== null ? trim($city) : '';
        $bounds = $city !== '' ? $this->resolveCityBounds($city) : null;

        $query = $address;
        if ($city !== '' && ! Str::contains(Str::lower($address), Str::lower($city))) {
            $query = $city.', '.$address;
        }

        $key = $this->apiKey();
        if ($key === '') {
            return null;
        }

        try {
            $response = Http::timeout(10)->get('https://maps.googleapis.com/maps/api/geocode/json', [
                'address' => $query,
                'components' => 'country:IN',
                'key' => $key,
            ]);
        } catch (\Throwable $e) {
            return null;
        }

        $status = (string) ($response->json('status') ?? '');
        if (! $response->successful() || $status !== 'OK') {
            $this->noteGoogleFailure($status, $response->json('error_message'));

            return null;
        }

        $result = $response->json('results.0');
        if (! is_array($result)) {
            return null;
        }

        $lat = $result['geometry']['location']['lat'] ?? null;
        $lng = $result['geometry']['location']['lng'] ?? null;
        if (! is_numeric($lat) || ! is_numeric($lng)) {
            return null;
        }

        $resolved = [
            'address' => (string) ($result['formatted_address'] ?? $address),
            'lat' => (float) $lat,
            'lng' => (float) $lng,
        ];

        if (! $this->isWithinCity($resolved, $city !== '' ? $city : null, $bounds)) {
            $this->lastError = $city !== ''
                ? 'Address must be in '.$city.'.'
                : 'Address is outside the selected city.';

            return null;
        }

        return $resolved;
    }

    /**
     * Reverse geocode lat/lng → address (optionally enforce selected city).
     *
     * @return array{address: string, lat: float, lng: float}|null
     */
    public function reverseGeocode(float $lat, float $lng, ?string $city = null): ?array
    {
        $this->lastError = null;
        if (! is_finite($lat) || ! is_finite($lng)) {
            return null;
        }

        $key = $this->apiKey();
        if ($key === '') {
            return null;
        }

        $city = $city !== null ? trim($city) : '';
        $bounds = $city !== '' ? $this->resolveCityBounds($city) : null;

        try {
            $response = Http::timeout(10)->get('https://maps.googleapis.com/maps/api/geocode/json', [
                'latlng' => $lat.','.$lng,
                'key' => $key,
                'result_type' => 'street_address|route|premise|sublocality|locality',
            ]);
        } catch (\Throwable $e) {
            return null;
        }

        $status = (string) ($response->json('status') ?? '');
        if (! $response->successful() || ($status !== 'OK' && $status !== 'ZERO_RESULTS')) {
            $this->noteGoogleFailure($status, $response->json('error_message'));

            return null;
        }

        $results = $response->json('results');
        if (! is_array($results) || $results === []) {
            $this->lastError = 'Could not resolve address for this GPS location.';

            return null;
        }

        $picked = null;
        foreach ($results as $result) {
            if (! is_array($result)) {
                continue;
            }
            $formatted = (string) ($result['formatted_address'] ?? '');
            if ($formatted === '') {
                continue;
            }
            $candidate = [
                'address' => $formatted,
                'lat' => $lat,
                'lng' => $lng,
            ];
            if ($city === '' || $this->isWithinCity($candidate, $city, $bounds)) {
                $picked = $candidate;
                break;
            }
        }

        if ($picked === null) {
            $this->lastError = $city !== ''
                ? 'Your GPS location is outside '.$city.'. Move into '.$city.' or type an address in '.$city.'.'
                : 'Could not resolve address for this GPS location.';

            return null;
        }

        return $picked;
    }
}
