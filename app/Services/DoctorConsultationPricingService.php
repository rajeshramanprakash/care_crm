<?php

namespace App\Services;

use App\Models\DoctorConsultationService;
use App\Models\DoctorRequest;
use App\Models\Location;
use App\Models\LocationDoctorConsultationPrice;

class DoctorConsultationPricingService
{
    /**
     * Normalize admin-entered per-doctor consultation pricing (optional overrides per sub-service × mode).
     *
     * @param  list<array{sub_service_id: int, sub_service_name: string, tags: list<string>}>  $normalizedSubServices
     * @param  array<int, mixed>  $rawPricing
     * @return array{pricing: ?array<int, array<string, mixed>>, error?: string}
     */
    public function normalizeAdminPricing(
        DoctorRequest $doctor,
        DoctorConsultationService $serviceRow,
        array $normalizedSubServices,
        array $rawPricing
    ): array {
        $city = trim((string) ($doctor->city ?? $doctor->location ?? ''));
        $location = $city !== '' ? Location::query()->where('name', $city)->first() : null;
        $locationId = $location ? (int) $location->id : 0;
        $serviceId = (int) $serviceRow->id;

        $modes = is_array($doctor->consultation_modes) ? $doctor->consultation_modes : [];
        $modes = array_values(array_filter(array_map(static fn ($m) => (string) $m, $modes), static fn ($m) => in_array($m, LocationDoctorConsultationPrice::MODES, true)));

        $selectedSubIds = [];
        $subNameById = [];
        foreach ($normalizedSubServices as $row) {
            $sid = (int) ($row['sub_service_id'] ?? 0);
            if ($sid > 0) {
                $selectedSubIds[] = $sid;
                $subNameById[$sid] = (string) ($row['sub_service_name'] ?? '');
            }
        }
        $selectedSubIds = array_values(array_unique($selectedSubIds));
        $hasSubs = count($selectedSubIds) > 0;

        $rawBySubMode = $this->buildRawPriceLookup($rawPricing, $selectedSubIds, $hasSubs);

        $out = [];
        $items = $hasSubs ? $selectedSubIds : [0];

        foreach ($items as $sid) {
            $rowModes = [];

            foreach ($modes as $modeKey) {
                if (! array_key_exists($sid, $rawBySubMode) || ! array_key_exists($modeKey, $rawBySubMode[$sid])) {
                    continue;
                }

                $entry = $rawBySubMode[$sid][$modeKey];
                $rowModes[$modeKey] = [
                    'doctor_price' => $entry['doctor_price'] ?? null,
                    'website_price' => $entry['website_price'] ?? null,
                    'locked' => false,
                ];
            }

            if ($rowModes === []) {
                continue;
            }

            $out[] = [
                'location_id' => $locationId > 0 ? $locationId : null,
                'service_id' => $serviceId,
                'sub_service_id' => $sid,
                'sub_service_name' => $sid > 0 ? ($subNameById[$sid] ?? null) : null,
                'modes' => $rowModes,
            ];
        }

        return [
            'pricing' => $out !== [] ? $out : null,
        ];
    }

    /**
     * @param  array<int, mixed>  $rawPricing
     * @param  list<int>  $selectedSubIds
     * @return array<int, array<string, array{doctor_price: ?float, website_price: ?float}>>
     */
    private function buildRawPriceLookup(array $rawPricing, array $selectedSubIds, bool $hasSubs): array
    {
        $allowedSubIds = $hasSubs ? array_fill_keys($selectedSubIds, true) : [0 => true];
        $rawBySubMode = [];

        foreach ($rawPricing as $row) {
            if (! is_array($row)) {
                continue;
            }

            $sid = (int) ($row['sub_service_id'] ?? 0);
            if ($hasSubs) {
                if ($sid <= 0 || ! isset($allowedSubIds[$sid])) {
                    continue;
                }
            } elseif ($sid !== 0) {
                continue;
            }

            $modeRows = $row['modes'] ?? [];
            if (! is_array($modeRows)) {
                continue;
            }

            foreach ($modeRows as $modeKey => $modeRow) {
                if (! is_array($modeRow) || ! in_array((string) $modeKey, LocationDoctorConsultationPrice::MODES, true)) {
                    continue;
                }

                $doctorPrice = $this->parseOptionalAmount($modeRow['doctor_price'] ?? null);
                $websitePrice = $this->parseOptionalAmount($modeRow['website_price'] ?? null);

                if ($doctorPrice === null && $websitePrice === null) {
                    continue;
                }

                $rawBySubMode[$sid] = $rawBySubMode[$sid] ?? [];
                $rawBySubMode[$sid][(string) $modeKey] = [
                    'doctor_price' => $doctorPrice,
                    'website_price' => $websitePrice,
                ];
            }
        }

        return $rawBySubMode;
    }

    private function parseOptionalAmount(mixed $raw): ?float
    {
        if ($raw === null || $raw === '') {
            return null;
        }
        if (! is_numeric($raw)) {
            return null;
        }
        $amount = (float) $raw;

        return $amount >= 0 ? $amount : null;
    }
}
