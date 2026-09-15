<?php

namespace App\Services;

use App\Models\Location;
use App\Models\Service;
use App\Models\Vendor;
use App\Models\VendorServicePrice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VendorServiceSync
{
    /**
     * @param  array<int, mixed>  $raw
     * @return list<array<string, mixed>>
     */
    public static function decodePayload($raw): array
    {
        if (is_string($raw)) {
            $decoded = json_decode($raw, true);

            return is_array($decoded) ? $decoded : [];
        }

        return is_array($raw) ? $raw : [];
    }

    /**
     * @param  list<array<string, mixed>>  $blocks
     * @return list<array<string, mixed>>
     */
    public static function normalizeBlocks(array $blocks): array
    {
        $out = [];
        $seenServiceIds = [];

        foreach ($blocks as $block) {
            if (! is_array($block)) {
                continue;
            }

            $serviceId = (int) ($block['service_id'] ?? 0);
            if ($serviceId <= 0 || isset($seenServiceIds[$serviceId])) {
                continue;
            }

            $seenServiceIds[$serviceId] = true;
            $subServices = JobRequestFreelancerServiceSync::normalizeSubServices(
                is_array($block['sub_services'] ?? null) ? $block['sub_services'] : []
            );
            $priceOverrides = [];
            foreach (is_array($block['price_overrides'] ?? null) ? $block['price_overrides'] : [] as $row) {
                if (! is_array($row)) {
                    continue;
                }
                $subId = (int) ($row['service_sub_service_id'] ?? 0);
                $priceOverrides[] = [
                    'service_sub_service_id' => $subId,
                    'price_12hr' => self::nullablePrice($row['price_12hr'] ?? null),
                    'price_24hr' => self::nullablePrice($row['price_24hr'] ?? null),
                    'price_onetime' => self::nullablePrice($row['price_onetime'] ?? null),
                ];
            }

            $out[] = [
                'service_id' => $serviceId,
                'service_name' => trim((string) ($block['service_name'] ?? '')),
                'sub_services' => $subServices,
                'price_overrides' => $priceOverrides,
            ];
        }

        return $out;
    }

    /**
     * @param  list<array<string, mixed>>  $blocks
     * @return array{success: false, message: string, errors: array<string, array<int, string>>}|null
     */
    public static function validateBlocks(array $blocks, ?string $locationName): ?array
    {
        if ($locationName === null || trim($locationName) === '') {
            return [
                'success' => false,
                'message' => 'Location is required.',
                'errors' => ['location' => ['Please select a location.']],
            ];
        }

        if ($blocks === []) {
            return [
                'success' => false,
                'message' => 'Add at least one service.',
                'errors' => ['vendor_services' => ['Add at least one service for this vendor.']],
            ];
        }

        if (! Location::query()->where('name', $locationName)->exists()) {
            return [
                'success' => false,
                'message' => 'Selected location is not valid.',
                'errors' => ['location' => ['Invalid location.']],
            ];
        }

        foreach ($blocks as $index => $block) {
            $serviceId = (int) ($block['service_id'] ?? 0);
            $serviceRow = Service::query()
                ->where('id', $serviceId)
                ->with(['subServices' => fn ($q) => $q->where('is_active', true)])
                ->first();

            if ($serviceRow === null) {
                return [
                    'success' => false,
                    'message' => 'One or more selected services are invalid.',
                    'errors' => ['vendor_services' => ['Invalid service in block '.($index + 1).'.']],
                ];
            }

            $block['service_name'] = $serviceRow->name;
            $err = JobRequestFreelancerServiceSync::validateSubServices(
                $serviceRow,
                is_array($block['sub_services'] ?? null) ? $block['sub_services'] : []
            );
            if ($err !== null) {
                $err['errors']['vendor_services'] = $err['errors']['service_sub_services'] ?? ['Service '.($index + 1).': validation failed.'];
                unset($err['errors']['service_sub_services']);

                return $err;
            }
        }

        return null;
    }

    /**
     * @return array{success: false, message: string, errors: array<string, array<int, string>>}|null
     */
    public static function validateRequest(Request $request): ?array
    {
        $blocks = self::normalizeBlocks(self::decodePayload($request->input('vendor_services')));
        $location = trim((string) $request->input('location', ''));

        return self::validateBlocks($blocks, $location !== '' ? $location : null);
    }

    public static function applyToVendor(Vendor $vendor, Request $request): void
    {
        $blocks = self::normalizeBlocks(self::decodePayload($request->input('vendor_services')));
        $locationName = trim((string) $request->input('location', ''));

        foreach ($blocks as $i => $block) {
            $serviceRow = Service::find((int) $block['service_id']);
            if ($serviceRow) {
                $blocks[$i]['service_name'] = $serviceRow->name;
            }
        }

        $vendor->location = $locationName !== '' ? $locationName : $vendor->location;
        $vendor->vendor_services = $blocks !== [] ? $blocks : null;

        if ($blocks !== []) {
            $first = $blocks[0];
            $vendor->job_title = (string) ($first['service_name'] ?? '');
            $vendor->service_sub_services = ($first['sub_services'] ?? []) !== []
                ? $first['sub_services']
                : null;
            $vendor->service_city_shifts = self::buildLegacyServiceCityShifts($blocks, $locationName);
        }

        $vendor->save();

        if ($blocks !== []) {
            self::syncServicePricesFromBlocks($vendor, $blocks);
        }
    }

    /**
     * @return array{price_12hr: mixed, price_24hr: mixed, price_onetime: mixed, is_override: bool}
     */
    public static function resolvePrice(Vendor $vendor, int $serviceId, int $subServiceId = 0): array
    {
        $override = VendorServicePrice::query()
            ->where('vendor_id', $vendor->id)
            ->where('service_id', $serviceId)
            ->where('service_sub_service_id', $subServiceId)
            ->first();

        $defaults = self::locationDefaults($vendor->location, $serviceId);
        $default = $defaults[(string) $subServiceId] ?? $defaults['0'] ?? [
            'price_12hr' => null,
            'price_24hr' => null,
            'price_onetime' => null,
        ];

        if ($override === null) {
            $jsonOverride = self::jsonBlockOverride($vendor, $serviceId, $subServiceId);
            if ($jsonOverride !== null) {
                return [
                    'price_12hr' => $jsonOverride['price_12hr'] ?? $default['price_12hr'],
                    'price_24hr' => $jsonOverride['price_24hr'] ?? $default['price_24hr'],
                    'price_onetime' => $jsonOverride['price_onetime'] ?? $default['price_onetime'],
                    'is_override' => true,
                ];
            }

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
            ->where('provider_type', 'vendor')
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

    /**
     * @param  list<array<string, mixed>>  $blocks
     */
    public static function syncServicePricesFromBlocks(Vendor $vendor, array $blocks): void
    {
        $keepKeys = [];
        foreach ($blocks as $block) {
            $serviceId = (int) ($block['service_id'] ?? 0);
            if ($serviceId <= 0) {
                continue;
            }
            foreach ($block['price_overrides'] ?? [] as $row) {
                if (! is_array($row)) {
                    continue;
                }
                $subId = (int) ($row['service_sub_service_id'] ?? 0);
                $p12 = self::nullablePrice($row['price_12hr'] ?? null);
                $p24 = self::nullablePrice($row['price_24hr'] ?? null);
                $po = self::nullablePrice($row['price_onetime'] ?? null);
                if ($p12 === null && $p24 === null && $po === null) {
                    continue;
                }
                $keepKeys[] = $serviceId.'|'.$subId;
                VendorServicePrice::query()->updateOrCreate(
                    [
                        'vendor_id' => $vendor->id,
                        'service_id' => $serviceId,
                        'service_sub_service_id' => $subId,
                    ],
                    [
                        'price_12hr' => $p12,
                        'price_24hr' => $p24,
                        'price_onetime' => $po,
                    ]
                );
            }
        }

        if ($keepKeys === []) {
            return;
        }

        VendorServicePrice::query()
            ->where('vendor_id', $vendor->id)
            ->get()
            ->each(function (VendorServicePrice $row) use ($keepKeys) {
                $key = (int) $row->service_id.'|'.(int) $row->service_sub_service_id;
                if (! in_array($key, $keepKeys, true)) {
                    // keep DB overrides from approved requests even if not in admin form row
                }
            });
    }

    /**
     * @return array{price_12hr: mixed, price_24hr: mixed, price_onetime: mixed}|null
     */
    private static function jsonBlockOverride(Vendor $vendor, int $serviceId, int $subServiceId): ?array
    {
        foreach (self::blocksForVendor($vendor) as $block) {
            if ((int) ($block['service_id'] ?? 0) !== $serviceId) {
                continue;
            }
            foreach ($block['price_overrides'] ?? [] as $row) {
                if ((int) ($row['service_sub_service_id'] ?? 0) === $subServiceId) {
                    return [
                        'price_12hr' => $row['price_12hr'] ?? null,
                        'price_24hr' => $row['price_24hr'] ?? null,
                        'price_onetime' => $row['price_onetime'] ?? null,
                    ];
                }
            }
        }

        return null;
    }

    /**
     * @param  list<array<string, mixed>>  $blocks
     * @return list<array<string, mixed>>
     */
    public static function buildLegacyServiceCityShifts(array $blocks, string $locationName): array
    {
        $location = Location::query()->where('name', $locationName)->first();
        if (! $location) {
            return [];
        }

        $legacy = [];
        foreach ($blocks as $block) {
            $legacy[] = [
                'service_id' => (int) $block['service_id'],
                'sub_services' => $block['sub_services'] ?? [],
                'price_overrides' => $block['price_overrides'] ?? [],
                'cities' => [
                    [
                        'city_id' => $location->id,
                        'shift' => 'both',
                    ],
                ],
            ];
        }

        return $legacy;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function blocksForVendor(Vendor $vendor): array
    {
        $stored = $vendor->vendor_services;
        if (is_array($stored) && $stored !== []) {
            $blocks = self::normalizeBlocks($stored); return self::mergeServicePricesIntoBlocks($vendor, $blocks);
        }

        $legacy = $vendor->service_city_shifts;
        if (is_string($legacy)) {
            $legacy = json_decode($legacy, true);
        }
        if (! is_array($legacy) || $legacy === []) {
            if ($vendor->job_title) {
                $service = Service::query()->where('name', $vendor->job_title)->first();
                if ($service) {
                    return [[
                        'service_id' => (int) $service->id,
                        'service_name' => (string) $service->name,
                        'sub_services' => is_array($vendor->service_sub_services) ? $vendor->service_sub_services : [],
                        'price_overrides' => [],
                    ]];
                }
            }

            return [];
        }

        $blocks = [];
        foreach ($legacy as $row) {
            if (! is_array($row)) {
                continue;
            }
            $serviceId = (int) ($row['service_id'] ?? 0);
            if ($serviceId <= 0) {
                continue;
            }
            $service = Service::find($serviceId);
            $blocks[] = [
                'service_id' => $serviceId,
                'service_name' => $service ? (string) $service->name : '',
                'sub_services' => is_array($row['sub_services'] ?? null) ? $row['sub_services'] : [],
                'price_overrides' => is_array($row['price_overrides'] ?? null) ? $row['price_overrides'] : [],
            ];
        }

        return self::normalizeBlocks($blocks);
    }

    private static function nullablePrice(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (string) $value;
    }

    /**
     * @param list<array<string, mixed>> $blocks
     * @return list<array<string, mixed>>
     */
    private static function mergeServicePricesIntoBlocks(Vendor $vendor, array $blocks): array
    {
        $overrides = \App\Models\VendorServicePrice::query()
            ->where('vendor_id', $vendor->id)
            ->get()
            ->groupBy('service_id');

        foreach ($blocks as &$block) {
            $serviceId = (int)($block['service_id'] ?? 0);
            if (!isset($overrides[$serviceId])) continue;

            $serviceOverrides = $overrides[$serviceId]->keyBy('service_sub_service_id');
            $mergedOverrides = [];
            
            // Collect all sub-services from the block
            $subIds = [0]; // Main service
            foreach ($block['sub_services'] ?? [] as $sub) {
                if (isset($sub['sub_service_id'])) {
                    $subIds[] = (int)$sub['sub_service_id'];
                }
            }

            foreach ($subIds as $subId) {
                if (isset($serviceOverrides[$subId])) {
                    $row = $serviceOverrides[$subId];
                    $mergedOverrides[] = [
                        'service_sub_service_id' => $subId,
                        'price_12hr' => $row->price_12hr,
                        'price_24hr' => $row->price_24hr,
                        'price_onetime' => $row->price_onetime,
                    ];
                }
            }

            $block['price_overrides'] = $mergedOverrides;
        }

        return $blocks;
    }
}
