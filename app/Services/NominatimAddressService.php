<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * OpenStreetMap Nominatim fallback (no API key). Respect usage policy: max ~1 req/s.
 */
class NominatimAddressService
{
    private const USER_AGENT = 'CarelixHealthcare/1.0 (consultation address; contact: grievance@carelixhealthcare.com)';

    /**
     * @return array{ok: bool, message: string}
     */
    public function healthCheck(): array
    {
        $result = $this->geocode('New Delhi, India');
        if ($result !== null) {
            return ['ok' => true, 'message' => ''];
        }

        return [
            'ok' => false,
            'message' => 'OpenStreetMap address lookup is temporarily unavailable.',
        ];
    }

    /**
     * @return list<array{place_id: string, description: string}>
     */
    public function autocomplete(string $input, ?string $city = null): array
    {
        $city = $city !== null ? trim($city) : '';
        $viewbox = $city !== '' ? $this->cityViewbox($city) : null;
        $rows = $this->search($this->buildQuery($input, $city !== '' ? $city : null), 6, $viewbox);
        if ($city !== '') {
            $rows = $this->filterRowsByCity($rows, $city);
        }

        return array_map(static function (array $row): array {
            return [
                'place_id' => self::placeIdFromRow($row),
                'description' => (string) ($row['display_name'] ?? ''),
            ];
        }, $rows);
    }

    /**
     * @return array{address: string, lat: float, lng: float}|null
     */
    public function resolvePlace(string $placeId): ?array
    {
        if (! str_starts_with($placeId, 'osm:')) {
            return null;
        }

        $parts = explode(':', $placeId, 4);
        if (count($parts) < 4) {
            return null;
        }

        $lat = (float) $parts[1];
        $lng = (float) $parts[2];
        $address = rawurldecode($parts[3]);
        if (! is_finite($lat) || ! is_finite($lng) || $address === '') {
            return null;
        }

        return [
            'address' => $address,
            'lat' => $lat,
            'lng' => $lng,
        ];
    }

    /**
     * @return array{address: string, lat: float, lng: float}|null
     */
    public function geocode(string $address, ?string $city = null): ?array
    {
        $city = $city !== null ? trim($city) : '';
        $viewbox = $city !== '' ? $this->cityViewbox($city) : null;
        $rows = $this->search($this->buildQuery($address, $city !== '' ? $city : null), 6, $viewbox);
        if ($city !== '') {
            $rows = $this->filterRowsByCity($rows, $city);
        }
        if ($rows === []) {
            return null;
        }

        $row = $rows[0];
        $lat = $row['lat'] ?? null;
        $lng = $row['lon'] ?? null;
        if (! is_numeric($lat) || ! is_numeric($lng)) {
            return null;
        }

        return [
            'address' => (string) ($row['display_name'] ?? $address),
            'lat' => (float) $lat,
            'lng' => (float) $lng,
        ];
    }

    /**
     * @return array{address: string, lat: float, lng: float}|null
     */
    public function reverseGeocode(float $lat, float $lng, ?string $city = null): ?array
    {
        if (! is_finite($lat) || ! is_finite($lng)) {
            return null;
        }

        $city = $city !== null ? trim($city) : '';

        try {
            $response = Http::timeout(12)
                ->withHeaders(['User-Agent' => self::USER_AGENT])
                ->get('https://nominatim.openstreetmap.org/reverse', [
                    'lat' => $lat,
                    'lon' => $lng,
                    'format' => 'json',
                    'addressdetails' => 0,
                    'zoom' => 18,
                ]);
        } catch (\Throwable $e) {
            return null;
        }

        if (! $response->successful()) {
            return null;
        }

        $data = $response->json();
        if (! is_array($data)) {
            return null;
        }

        $address = trim((string) ($data['display_name'] ?? ''));
        if ($address === '') {
            return null;
        }

        if ($city !== '' && ! Str::contains(Str::lower($address), Str::lower($city))) {
            return null;
        }

        return [
            'address' => $address,
            'lat' => $lat,
            'lng' => $lng,
        ];
    }

    private function buildQuery(string $input, ?string $city): string
    {
        $input = trim($input);
        if ($city !== null && $city !== '' && ! Str::contains(Str::lower($input), Str::lower($city))) {
            return $city.', '.$input.', India';
        }

        return $input;
    }

    private function cityViewbox(string $city): ?string
    {
        $cacheKey = 'nominatim_city_viewbox:'.Str::slug($city);

        return Cache::remember($cacheKey, 86400, function () use ($city) {
            $rows = $this->search($city.', India', 1);
            $row = $rows[0] ?? null;
            if (! is_array($row)) {
                return null;
            }
            $bbox = $row['boundingbox'] ?? null;
            if (! is_array($bbox) || count($bbox) < 4) {
                return null;
            }
            $south = (float) $bbox[0];
            $north = (float) $bbox[1];
            $west = (float) $bbox[2];
            $east = (float) $bbox[3];
            if (! is_finite($south) || ! is_finite($north) || ! is_finite($west) || ! is_finite($east)) {
                return null;
            }

            return $west.','.$north.','.$east.','.$south;
        });
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<array<string, mixed>>
     */
    private function filterRowsByCity(array $rows, string $city): array
    {
        $cityLower = Str::lower($city);
        $pattern = '/,\s*'.preg_quote($cityLower, '/').'\s*,/u';

        return array_values(array_filter($rows, static function (array $row) use ($pattern): bool {
            $name = Str::lower((string) ($row['display_name'] ?? ''));

            return $name !== '' && preg_match($pattern, $name) === 1;
        }));
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function search(string $query, int $limit, ?string $viewbox = null): array
    {
        $query = trim($query);
        if ($query === '') {
            return [];
        }

        $params = [
            'q' => $query,
            'format' => 'json',
            'limit' => max(1, min($limit, 10)),
            'countrycodes' => 'in',
            'addressdetails' => 0,
        ];
        if ($viewbox !== null && $viewbox !== '') {
            $params['viewbox'] = $viewbox;
            $params['bounded'] = 1;
        }

        try {
            $response = Http::timeout(12)
                ->withHeaders(['User-Agent' => self::USER_AGENT])
                ->get('https://nominatim.openstreetmap.org/search', $params);
        } catch (\Throwable $e) {
            return [];
        }

        if (! $response->successful()) {
            return [];
        }

        $data = $response->json();
        if (! is_array($data)) {
            return [];
        }

        $rows = [];
        foreach ($data as $item) {
            if (is_array($item) && isset($item['lat'], $item['lon'])) {
                $rows[] = $item;
            }
        }

        return $rows;
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private static function placeIdFromRow(array $row): string
    {
        $lat = (string) ($row['lat'] ?? '0');
        $lng = (string) ($row['lon'] ?? '0');
        $name = (string) ($row['display_name'] ?? '');

        return 'osm:'.$lat.':'.$lng.':'.rawurlencode($name);
    }
}
