<?php

namespace App\Services;

use App\Models\DoctorRequest;
use App\Models\Location;
use App\Models\LocationDoctorConsultationPrice;

class ConsultationLocationPricingService
{
    /**
     * Website prices per mode for a location + service (+ optional sub-service).
     *
     * @return array{online: ?float, home_visit: ?float, clinic_visit: ?float}
     */
    public function websitePricesFor(string $locationName, int $serviceId, ?int $subServiceId = null): array
    {
        $out = [
            'online' => null,
            'home_visit' => null,
            'clinic_visit' => null,
        ];

        if ($locationName === '' || $serviceId <= 0) {
            return $out;
        }

        $location = Location::query()->where('name', $locationName)->first();
        if (! $location) {
            return $out;
        }

        $subId = $subServiceId > 0 ? $subServiceId : null;

        $rows = LocationDoctorConsultationPrice::query()
            ->where('location_id', $location->id)
            ->where('doctor_consultation_service_id', $serviceId)
            ->where('is_active', true)
            ->when($subId !== null, function ($q) use ($subId) {
                $q->where('doctor_consultation_service_sub_service_id', $subId);
            }, function ($q) {
                $q->whereNull('doctor_consultation_service_sub_service_id');
            })
            ->get(['consultation_mode', 'website_price']);

        foreach ($rows as $row) {
            $mode = (string) $row->consultation_mode;
            if (! array_key_exists($mode, $out)) {
                continue;
            }
            if ($row->website_price !== null && $row->website_price !== '' && (float) $row->website_price > 0) {
                $out[$mode] = (float) $row->website_price;
            }
        }

        return $out;
    }

    public function websitePriceForMode(string $locationName, int $serviceId, ?int $subServiceId, string $mode): ?float
    {
        $mode = strtolower(trim($mode));
        $all = $this->websitePricesFor($locationName, $serviceId, $subServiceId);

        return $all[$mode] ?? null;
    }

    /**
     * Location admin prices keyed by sub-service id (0 = service-level) and mode.
     *
     * @return array<int, array<string, array{website_price: ?float, doctor_max_price: ?float}>>
     */
    public function locationPricesMatrix(string $locationName, int $serviceId): array
    {
        $out = [];

        if ($locationName === '' || $serviceId <= 0) {
            return $out;
        }

        $location = Location::query()->where('name', $locationName)->first();
        if (! $location) {
            return $out;
        }

        $rows = LocationDoctorConsultationPrice::query()
            ->where('location_id', $location->id)
            ->where('doctor_consultation_service_id', $serviceId)
            ->where('is_active', true)
            ->get(['doctor_consultation_service_sub_service_id', 'consultation_mode', 'website_price', 'doctor_max_price']);

        foreach ($rows as $row) {
            $subId = $row->doctor_consultation_service_sub_service_id
                ? (int) $row->doctor_consultation_service_sub_service_id
                : 0;
            $mode = (string) $row->consultation_mode;
            if (! in_array($mode, LocationDoctorConsultationPrice::MODES, true)) {
                continue;
            }

            $out[$subId] = $out[$subId] ?? [];
            $out[$subId][$mode] = [
                'website_price' => $row->website_price !== null && $row->website_price !== ''
                    ? (float) $row->website_price
                    : null,
                'doctor_max_price' => $row->doctor_max_price !== null && $row->doctor_max_price !== ''
                    ? (float) $row->doctor_max_price
                    : null,
            ];
        }

        return $out;
    }

    /**
     * Resolve doctor-portal display prices: doctor override first, then location defaults.
     *
     * @param  array<int, array<string, array{website_price: ?float, doctor_max_price: ?float}>>  $locationMatrix
     * @return array{doctor: mixed, website: mixed, source: string}
     */
    public function portalDisplayPricesForSubMode(
        DoctorRequest $doctor,
        array $locationMatrix,
        int $subServiceId,
        string $mode
    ): array {
        $mode = strtolower(trim($mode));
        $subId = $subServiceId > 0 ? $subServiceId : 0;

        $doctorPrice = $doctor->consultationChargeForSubServiceAndMode($subId > 0 ? $subId : null, $mode);
        $websitePrice = $doctor->consultationWebsiteFeeForSubServiceAndMode($subId > 0 ? $subId : null, $mode);
        $source = 'doctor';

        $locRow = $locationMatrix[$subId][$mode] ?? null;
        if ($locRow === null && $subId > 0) {
            $locRow = $locationMatrix[0][$mode] ?? null;
        }

        if ($doctorPrice === null && $locRow !== null) {
            $doctorPrice = $locRow['doctor_max_price'] ?? null;
            if ($doctorPrice !== null) {
                $source = 'location';
            }
        }

        if ($websitePrice === null && $locRow !== null) {
            $websitePrice = $locRow['website_price'] ?? null;
            if ($source !== 'doctor' && $websitePrice !== null) {
                $source = 'location';
            }
        }

        if ($subId === 0) {
            if ($doctorPrice === null) {
                $doctorPrice = $doctor->adminConsultationChargeForMode($mode);
            }
            if ($websitePrice === null) {
                $websitePrice = $doctor->websiteCustomerFeeForMode($mode);
            }
        }

        if ($doctorPrice !== null && $websitePrice === null) {
            $source = $source === 'location' ? 'location' : 'doctor';
        } elseif ($doctorPrice === null && $websitePrice !== null) {
            $source = 'location';
        }

        return [
            'doctor' => $doctorPrice,
            'website' => $websitePrice,
            'source' => $source,
        ];
    }

    public function patientBookingFeeForMode(
        string $locationName,
        int $serviceId,
        ?int $subServiceId,
        DoctorRequest $doctor,
        string $mode
    ): ?float {
        $doctorSpecific = $this->doctorSpecificBookingFee($doctor, $subServiceId, $mode);
        if ($doctorSpecific !== null) {
            return $doctorSpecific;
        }

        $locFee = $this->websitePriceForMode($locationName, $serviceId, $subServiceId, $mode);
        if ($locFee !== null && $locFee > 0) {
            return $locFee;
        }

        $flatWebFee = $doctor->websiteCustomerFeeForMode($mode);
        if ($flatWebFee !== null) {
            return $flatWebFee;
        }

        return $doctor->adminConsultationChargeForMode($mode);
    }

    /**
     * Resolve using pre-fetched location fees (same for all doctors in a list response).
     *
     * @param  array{online: ?float, home_visit: ?float, clinic_visit: ?float}  $locationFees
     */
    public function patientBookingFeeFromLocationFees(array $locationFees, DoctorRequest $doctor, string $mode, ?int $subServiceId = null): ?float
    {
        $mode = strtolower(trim($mode));

        $doctorSpecific = $this->doctorSpecificBookingFee($doctor, $subServiceId, $mode);
        if ($doctorSpecific !== null) {
            return $doctorSpecific;
        }

        $locFee = $locationFees[$mode] ?? null;
        if ($locFee !== null && $locFee > 0) {
            return $locFee;
        }

        $flatWebFee = $doctor->websiteCustomerFeeForMode($mode);
        if ($flatWebFee !== null) {
            return $flatWebFee;
        }

        return $doctor->adminConsultationChargeForMode($mode);
    }

    /**
     * Admin-set per-doctor price from consultation_pricing (CareWeb card price, else doctor charge).
     */
    public function doctorSpecificBookingFee(DoctorRequest $doctor, ?int $subServiceId, string $mode): ?float
    {
        $doctorWebFee = $doctor->consultationWebsiteFeeForSubServiceAndMode($subServiceId, $mode);
        if ($doctorWebFee !== null) {
            return $doctorWebFee;
        }

        return $doctor->consultationChargeForSubServiceAndMode($subServiceId, $mode);
    }
}
