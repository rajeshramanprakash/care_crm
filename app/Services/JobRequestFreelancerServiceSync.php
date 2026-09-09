<?php

namespace App\Services;

use App\Models\JobRequest;
use App\Models\JobRequestServicePrice;
use App\Models\Location;
use App\Models\Service;
use Illuminate\Support\Facades\DB;

class JobRequestFreelancerServiceSync
{
    /**
     * @param  array<int, mixed>  $raw
     * @return list<array{sub_service_id: int, sub_service_name: string, tags: list<string>}>
     */
    public static function normalizeSubServices(array $raw): array
    {
        $out = [];
        $seen = [];
        foreach ($raw as $row) {
            if (! is_array($row)) {
                continue;
            }
            $id = (int) ($row['sub_service_id'] ?? 0);
            if ($id < 0 || isset($seen[$id])) {
                continue;
            }
            if ($id === 0 && $seen !== []) {
                continue;
            }
            $name = trim((string) ($row['sub_service_name'] ?? ''));
            $tags = [];
            foreach ($row['tags'] ?? [] as $tag) {
                $t = trim((string) $tag);
                if ($t !== '') {
                    $tags[] = $t;
                }
            }
            $seen[$id] = true;
            $out[] = [
                'sub_service_id' => $id,
                'sub_service_name' => $name,
                'tags' => array_values(array_unique($tags)),
            ];
        }

        return $out;
    }

    /**
     * @param  array<int, mixed>  $raw
     * @return list<array{service_id: int, service_sub_service_id: int, price_12hr: ?string, price_24hr: ?string, price_onetime: ?string}>
     */
    public static function normalizePriceOverrides(array $raw): array
    {
        $out = [];
        $seen = [];
        foreach ($raw as $row) {
            if (! is_array($row)) {
                continue;
            }
            $serviceId = (int) ($row['service_id'] ?? 0);
            if ($serviceId <= 0) {
                continue;
            }
            $subId = (int) ($row['service_sub_service_id'] ?? 0);
            $key = $serviceId.'|'.$subId;
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $out[] = [
                'service_id' => $serviceId,
                'service_sub_service_id' => $subId,
                'price_12hr' => self::nullablePrice($row['price_12hr'] ?? null),
                'price_24hr' => self::nullablePrice($row['price_24hr'] ?? null),
                'price_onetime' => self::nullablePrice($row['price_onetime'] ?? null),
            ];
        }

        return $out;
    }

    /**
     * @param  list<array{sub_service_id: int, sub_service_name: string, tags: list<string>}>  $subServices
     * @return array{success: false, message: string, errors: array<string, array<int, string>>}|null
     */
    public static function validateSubServices(?Service $serviceRow, array $subServices): ?array
    {
        if ($serviceRow === null) {
            return [
                'success' => false,
                'message' => 'Selected service is not valid.',
                'errors' => ['job_title' => ['Invalid job title.']],
            ];
        }

        $activeSubs = $serviceRow->subServices->filter(fn ($s) => (bool) $s->is_active);

        if ($activeSubs->isNotEmpty()) {
            if ($subServices === []) {
                return [
                    'success' => false,
                    'message' => 'Select at least one sub-service.',
                    'errors' => ['service_sub_services' => ['Choose one or more sub-services.']],
                ];
            }

            $subById = $activeSubs->keyBy('id');
            foreach ($subServices as $entry) {
                $subId = (int) ($entry['sub_service_id'] ?? 0);
                $sub = $subById->get($subId);
                if ($sub === null) {
                    return [
                        'success' => false,
                        'message' => 'Invalid sub-service for the selected service.',
                        'errors' => ['service_sub_services' => ['One or more sub-services are not valid.']],
                    ];
                }
                $allowed = is_array($sub->specialization_options)
                    ? array_values(array_filter(array_map('strval', $sub->specialization_options)))
                    : [];
                $tags = $entry['tags'] ?? [];
                if ($allowed !== [] && count($tags) === 0) {
                    return [
                        'success' => false,
                        'message' => 'Har sub-service ke liye kam se kam ek tag select karein.',
                        'errors' => ['service_sub_services' => ['Tags required for: '.$sub->name]],
                    ];
                }
                foreach ($tags as $tag) {
                    if ($allowed !== [] && ! in_array((string) $tag, $allowed, true)) {
                        return [
                            'success' => false,
                            'message' => 'Invalid tag for sub-service '.$sub->name.'.',
                            'errors' => ['service_sub_services' => ['Tag not allowed: '.$tag]],
                        ];
                    }
                }
            }

            return null;
        }

        $allowedSpecs = is_array($serviceRow->specialization_options)
            ? array_values(array_filter(array_map('strval', $serviceRow->specialization_options)))
            : [];

        if ($allowedSpecs !== []) {
            $tags = [];
            foreach ($subServices as $entry) {
                if ((int) ($entry['sub_service_id'] ?? -1) === 0) {
                    $tags = $entry['tags'] ?? [];
                    break;
                }
            }
            if (count($tags) === 0) {
                return [
                    'success' => false,
                    'message' => 'Is service ke liye kam se kam ek tag select karein.',
                    'errors' => ['service_sub_services' => ['Service tags required.']],
                ];
            }
            foreach ($tags as $tag) {
                if (! in_array((string) $tag, $allowedSpecs, true)) {
                    return [
                        'success' => false,
                        'message' => 'Invalid tag for this service.',
                        'errors' => ['service_sub_services' => ['Tag not allowed: '.$tag]],
                    ];
                }
            }
        }

        return null;
    }

    /**
     * @param  list<array{sub_service_id: int, sub_service_name: string, tags: list<string>}>  $subServices
     * @param  list<array{service_id: int, service_sub_service_id: int, price_12hr: ?string, price_24hr: ?string, price_onetime: ?string}>  $priceOverrides
     */
    public static function sync(JobRequest $jobRequest, string $jobTitle, ?string $locationName, array $subServices, array $priceOverrides): void
    {
        $serviceRow = Service::query()
            ->where('name', $jobTitle)
            ->first();

        $jobRequest->service_sub_services = $subServices !== [] ? $subServices : null;
        if ($locationName) {
            $jobRequest->location = $locationName;
        }
        $jobRequest->save();

        JobRequestServicePrice::query()
            ->where('job_request_id', $jobRequest->id)
            ->delete();

        if ($serviceRow === null || $priceOverrides === []) {
            return;
        }

        $locationDefaults = self::locationDefaults($locationName, (int) $serviceRow->id);

        foreach ($priceOverrides as $row) {
            if ((int) $row['service_id'] !== (int) $serviceRow->id) {
                continue;
            }
            $subId = (int) $row['service_sub_service_id'];
            $default = $locationDefaults[(string) $subId] ?? $locationDefaults['0'] ?? null;

            $p12 = $row['price_12hr'];
            $p24 = $row['price_24hr'];
            $pOnce = $row['price_onetime'];

            $hasOverride = self::differsFromDefault($p12, $default['price_12hr'] ?? null)
                || self::differsFromDefault($p24, $default['price_24hr'] ?? null)
                || self::differsFromDefault($pOnce, $default['price_onetime'] ?? null);

            if (! $hasOverride) {
                continue;
            }

            JobRequestServicePrice::create([
                'job_request_id' => $jobRequest->id,
                'service_id' => (int) $serviceRow->id,
                'service_sub_service_id' => $subId,
                'price_12hr' => $p12,
                'price_24hr' => $p24,
                'price_onetime' => $pOnce,
            ]);
        }
    }

    /**
     * @return list<array{service_id: int, service_sub_service_id: int, price_12hr: mixed, price_24hr: mixed, price_onetime: mixed}>
     */
    public static function overridesForResponse(JobRequest $jobRequest): array
    {
        return $jobRequest->servicePrices()
            ->get()
            ->map(fn (JobRequestServicePrice $p) => [
                'service_id' => (int) $p->service_id,
                'service_sub_service_id' => (int) $p->service_sub_service_id,
                'price_12hr' => $p->price_12hr,
                'price_24hr' => $p->price_24hr,
                'price_onetime' => $p->price_onetime,
            ])
            ->values()
            ->all();
    }

    /**
     * Resolve effective price for a freelancer service/sub-service row.
     *
     * @return array{price_12hr: mixed, price_24hr: mixed, price_onetime: mixed, is_override: bool}
     */
    public static function resolvePrice(JobRequest $jobRequest, int $serviceId, int $subServiceId = 0): array
    {
        $override = JobRequestServicePrice::query()
            ->where('job_request_id', $jobRequest->id)
            ->where('service_id', $serviceId)
            ->where('service_sub_service_id', $subServiceId)
            ->first();

        $locationName = $jobRequest->location ?: $jobRequest->city;
        $defaults = self::locationDefaults($locationName, $serviceId);
        $default = $defaults[(string) $subServiceId] ?? $defaults['0'] ?? [
            'price_12hr' => null,
            'price_24hr' => null,
            'price_onetime' => null,
        ];

        if ($override === null) {
            return [
                'price_12hr' => $default['price_12hr'],
                'price_24hr' => $default['price_24hr'],
                'price_onetime' => $default['price_onetime'],
                'is_override' => false,
            ];
        }

        return [
            'price_12hr' => $override->price_12hr ?? $default['price_12hr'],
            'price_24hr' => $override->price_24hr ?? $default['price_24hr'],
            'price_onetime' => $override->price_onetime ?? $default['price_onetime'],
            'is_override' => true,
        ];
    }

    /**
     * @return array<string, array{price_12hr: mixed, price_24hr: mixed, price_onetime: mixed}>
     */
    public static function locationDefaults(?string $locationName, int $serviceId): array
    {
        if (! $locationName) {
            return [];
        }

        $location = Location::query()->where('name', $locationName)->first();
        if (! $location) {
            return [];
        }

        $rows = DB::table('location_services')
            ->where('location_id', $location->id)
            ->where('service_id', $serviceId)
            ->where('provider_type', 'freelancer')
            ->get();

        $out = [];
        foreach ($rows as $row) {
            $key = (string) (int) ($row->service_sub_service_id ?? 0);
            $out[$key] = [
                'price_12hr' => $row->price_12hr,
                'price_24hr' => $row->price_24hr,
                'price_onetime' => $row->price_onetime,
            ];
        }

        return $out;
    }

    private static function nullablePrice(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return number_format((float) $value, 2, '.', '');
    }

    private static function differsFromDefault(?string $override, mixed $default): bool
    {
        if ($override === null || $override === '') {
            return false;
        }
        $d = $default === null || $default === '' ? null : number_format((float) $default, 2, '.', '');

        return $override !== $d;
    }

    /**
     * @param  array<int, mixed>|null  $raw
     */
    public static function decodeJsonArray(mixed $raw): array
    {
        if (is_array($raw)) {
            return $raw;
        }
        if (! is_string($raw) || trim($raw) === '') {
            return [];
        }
        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : [];
    }
}
