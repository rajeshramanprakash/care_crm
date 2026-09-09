<?php

namespace App\Services;

use App\Models\DoctorRequest;

class ConsultationDoctorProximityService
{
    public const DEFAULT_RADIUS_KM = 5.0;

    public static function haversineKm(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadiusKm = 6371.0;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadiusKm * $c;
    }

    /**
     * @return array{lat: float, lng: float}|null
     */
    public static function coordinatesForMode(DoctorRequest $doctor, string $mode): ?array
    {
        if ($mode === 'home_visit') {
            if ($doctor->base_location_lat !== null && $doctor->base_location_lng !== null) {
                return [
                    'lat' => (float) $doctor->base_location_lat,
                    'lng' => (float) $doctor->base_location_lng,
                ];
            }

            return null;
        }

        if ($mode === 'clinic_visit') {
            if ($doctor->clinic_lat !== null && $doctor->clinic_lng !== null) {
                return [
                    'lat' => (float) $doctor->clinic_lat,
                    'lng' => (float) $doctor->clinic_lng,
                ];
            }

            return null;
        }

        return null;
    }

    /**
     * @param  iterable<DoctorRequest>  $doctors
     * @return array<int, array{doctor: DoctorRequest, distance_km: float}>
     */
    public static function filterWithinRadius(
        iterable $doctors,
        string $mode,
        float $patientLat,
        float $patientLng,
        float $radiusKm = self::DEFAULT_RADIUS_KM
    ): array {
        $matches = [];

        foreach ($doctors as $doctor) {
            if (! $doctor instanceof DoctorRequest) {
                continue;
            }
            $coords = self::coordinatesForMode($doctor, $mode);
            if ($coords === null) {
                continue;
            }
            $distanceKm = self::haversineKm($patientLat, $patientLng, $coords['lat'], $coords['lng']);
            if ($distanceKm <= $radiusKm) {
                $matches[] = [
                    'doctor' => $doctor,
                    'distance_km' => round($distanceKm, 2),
                ];
            }
        }

        usort($matches, static fn (array $a, array $b) => $a['distance_km'] <=> $b['distance_km']);

        return $matches;
    }
}
