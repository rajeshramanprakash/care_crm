<?php

namespace App\Services;

use App\Models\DoctorConsultationService;

class DoctorConsultationRegistrationTagsService
{
    /**
     * @param  array<int, mixed>  $specializations
     * @param  array<int, mixed>  $consultationSubServices
     * @return array{success: false, message: string, errors: array<string, array<int, string>>}|null
     */
    public function validate(
        ?DoctorConsultationService $serviceRow,
        array $specializations,
        array $consultationSubServices,
        bool $requireTags = true
    ): ?array {
        if ($serviceRow === null) {
            return null;
        }

        $activeSubs = $serviceRow->subServices->filter(fn ($s) => (bool) $s->is_active);
        if ($activeSubs->isNotEmpty()) {
            $normalized = $this->normalizeSubServices($consultationSubServices);
            if ($normalized === []) {
                return [
                    'success' => false,
                    'message' => 'Select at least one sub-service.',
                    'errors' => ['consultation_sub_services' => ['Choose one or more sub-services.']],
                ];
            }

            $subById = $activeSubs->keyBy('id');
            foreach ($normalized as $entry) {
                $subId = (int) ($entry['sub_service_id'] ?? 0);
                $sub = $subById->get($subId);
                if ($sub === null) {
                    return [
                        'success' => false,
                        'message' => 'Invalid sub-service for the selected consultation service.',
                        'errors' => ['consultation_sub_services' => ['One or more sub-services are not valid.']],
                    ];
                }
                $allowed = is_array($sub->specialization_options)
                    ? array_values(array_filter(array_map('strval', $sub->specialization_options)))
                    : [];
                $tags = $entry['tags'] ?? [];
                if ($requireTags && $allowed !== [] && count($tags) === 0) {
                    return [
                        'success' => false,
                        'message' => 'Select at least one tag for each sub-service that has tags configured.',
                        'errors' => ['consultation_sub_services' => ['Tags required for: '.$sub->name]],
                    ];
                }
                foreach ($tags as $tag) {
                    if ($allowed !== [] && ! in_array((string) $tag, $allowed, true)) {
                        return [
                            'success' => false,
                            'message' => 'Invalid tag for sub-service '.$sub->name.'.',
                            'errors' => ['consultation_sub_services' => ['Tag not allowed: '.$tag]],
                        ];
                    }
                }
            }

            return null;
        }

        $allowedSpecs = is_array($serviceRow->specialization_options)
            ? array_values(array_filter(array_map('strval', $serviceRow->specialization_options)))
            : [];
        if ($allowedSpecs === []) {
            return null;
        }
        if ($requireTags && count($specializations) === 0) {
            return [
                'success' => false,
                'message' => 'Select at least one tag for this consultation service.',
                'errors' => ['specializations' => ['Choose one or more tags listed for this service.']],
            ];
        }
        foreach ($specializations as $spec) {
            if (! in_array((string) $spec, $allowedSpecs, true)) {
                return [
                    'success' => false,
                    'message' => 'Invalid tag for the selected consultation service.',
                    'errors' => ['specializations' => ['One or more tags are not allowed for this service.']],
                ];
            }
        }

        return null;
    }

    /**
     * @param  array<int, mixed>  $raw
     * @return list<array{sub_service_id: int, sub_service_name: string, tags: list<string>}>
     */
    public function normalizeSubServices(array $raw): array
    {
        $out = [];
        $seen = [];
        foreach ($raw as $row) {
            if (! is_array($row)) {
                continue;
            }
            $id = (int) ($row['sub_service_id'] ?? 0);
            if ($id <= 0 || isset($seen[$id])) {
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
            $tags = array_values(array_unique($tags));
            $seen[$id] = true;
            $out[] = [
                'sub_service_id' => $id,
                'sub_service_name' => $name,
                'tags' => $tags,
            ];
        }

        return $out;
    }

    /**
     * @param  list<array{sub_service_id: int, sub_service_name: string, tags: list<string>}>  $normalizedSubServices
     * @param  array<int, mixed>  $fallbackSpecs
     * @return list<string>
     */
    public function flattenTags(array $normalizedSubServices, array $fallbackSpecs): array
    {
        if ($normalizedSubServices !== []) {
            $flat = [];
            foreach ($normalizedSubServices as $entry) {
                foreach ($entry['tags'] as $tag) {
                    $flat[] = $tag;
                }
            }

            return array_values(array_unique($flat));
        }

        return array_values(array_unique(array_filter(
            array_map(static fn ($s) => trim((string) $s), $fallbackSpecs),
            static fn ($s) => $s !== ''
        )));
    }
}
