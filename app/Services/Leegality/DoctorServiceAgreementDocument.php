<?php

namespace App\Services\Leegality;

use App\Models\DoctorRequest;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class DoctorServiceAgreementDocument
{
    /**
     * @return array{
     *   doctor: DoctorRequest,
     *   irn: string,
     *   generatedAt: Carbon,
     *   agreementDate: string,
     *   specialisation: string,
     *   medicalRegNo: string,
     *   scheduleRows: list<array<string, mixed>>,
     *   homeVisitRows: list<array<string, mixed>>,
     *   clinicRows: list<array<string, mixed>>
     * }
     */
    public function viewData(DoctorRequest $doctor, ?string $irn = null): array
    {
        $irn = $irn ?: ('DR-'.$doctor->id.'-'.now()->format('YmdHis'));
        $scheduleRows = $this->buildScheduleRows($doctor);

        return [
            'doctor' => $doctor,
            'irn' => $irn,
            'generatedAt' => now(),
            'agreementDate' => now()->format('d M Y'),
            'specialisation' => $this->specialisationLabel($doctor),
            'medicalRegNo' => $this->medicalRegNo($doctor),
            'scheduleRows' => $scheduleRows,
            'homeVisitRows' => $this->buildHomeVisitRows($doctor, $scheduleRows),
            'clinicRows' => $this->buildClinicRows($doctor, $scheduleRows),
        ];
    }

    public function renderHtml(DoctorRequest $doctor, ?string $irn = null): string
    {
        return view('pdf.doctor-service-agreement', $this->viewData($doctor, $irn))->render();
    }

    public function makePdf(DoctorRequest $doctor, ?string $irn = null)
    {
        $html = $this->renderHtml($doctor, $irn);

        return Pdf::loadHTML($html)->setPaper('a4');
    }

    public function pdfBinary(DoctorRequest $doctor, ?string $irn = null): string
    {
        return $this->makePdf($doctor, $irn)->output();
    }

    private function specialisationLabel(DoctorRequest $doctor): string
    {
        return trim((string) $doctor->job_title);
    }

    private function medicalRegNo(DoctorRequest $doctor): string
    {
        foreach (['medical_registration_no', 'medical_reg_no', 'registration_number', 'council_registration_no'] as $field) {
            $val = trim((string) ($doctor->{$field} ?? ''));
            if ($val !== '') {
                return $val;
            }
        }

        return '';
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function buildScheduleRows(DoctorRequest $doctor): array
    {
        $serviceName = trim((string) $doctor->job_title) ?: '—';
        $city = trim((string) ($doctor->location ?: $doctor->city)) ?: '—';
        $pricing = is_array($doctor->consultation_pricing) ? $doctor->consultation_pricing : [];
        $modeLabels = [
            'online' => 'Online',
            'home_visit' => 'Home Visit',
            'clinic_visit' => 'Clinic Visit',
        ];
        $unitLabels = [
            'online' => 'Per consultation',
            'home_visit' => 'Per visit',
            'clinic_visit' => 'Per consultation',
        ];

        $rows = [];
        foreach ($pricing as $row) {
            if (! is_array($row)) {
                continue;
            }
            $subName = trim((string) ($row['sub_service_name'] ?? ''));
            if ($subName === '') {
                $subName = '—';
            }
            $modes = is_array($row['modes'] ?? null) ? $row['modes'] : [];
            foreach ($modeLabels as $modeKey => $modeLabel) {
                $modeRow = is_array($modes[$modeKey] ?? null) ? $modes[$modeKey] : null;
                if ($modeRow === null) {
                    continue;
                }
                $price = $modeRow['doctor_price'] ?? null;
                if ($price === null || $price === '' || ! is_numeric($price)) {
                    continue;
                }
                $rows[] = [
                    'service' => $serviceName,
                    'sub_service' => $subName,
                    'city' => $city,
                    'mode' => $modeLabel,
                    'mode_key' => $modeKey,
                    'rate' => number_format((float) $price, 0, '.', ','),
                    'unit' => $unitLabels[$modeKey] ?? 'Per booking',
                ];
            }
        }

        return $rows;
    }

    /**
     * @param  list<array<string, mixed>>  $scheduleRows
     * @return list<array<string, mixed>>
     */
    private function buildHomeVisitRows(DoctorRequest $doctor, array $scheduleRows): array
    {
        $hasHome = collect($scheduleRows)->contains(fn ($r) => ($r['mode_key'] ?? '') === 'home_visit');
        $modes = is_array($doctor->consultation_modes) ? $doctor->consultation_modes : [];
        if (! $hasHome && ! in_array('home_visit', $modes, true)) {
            return [];
        }

        return [[
            'service' => trim((string) $doctor->job_title) ?: '—',
            'sub_service' => $this->firstSubServiceName($doctor),
            'city' => trim((string) ($doctor->location ?: $doctor->city)) ?: '—',
            'radius' => $doctor->coverage_radius_km !== null && $doctor->coverage_radius_km !== ''
                ? (string) $doctor->coverage_radius_km
                : '—',
            'base_location' => trim((string) $doctor->base_location_address) ?: '—',
        ]];
    }

    /**
     * @param  list<array<string, mixed>>  $scheduleRows
     * @return list<array<string, mixed>>
     */
    private function buildClinicRows(DoctorRequest $doctor, array $scheduleRows): array
    {
        $hasClinic = collect($scheduleRows)->contains(fn ($r) => ($r['mode_key'] ?? '') === 'clinic_visit');
        $modes = is_array($doctor->consultation_modes) ? $doctor->consultation_modes : [];
        if (! $hasClinic && ! in_array('clinic_visit', $modes, true)) {
            return [];
        }

        return [[
            'service' => trim((string) $doctor->job_title) ?: '—',
            'sub_service' => $this->firstSubServiceName($doctor),
            'city' => trim((string) ($doctor->location ?: $doctor->city)) ?: '—',
            'clinic_name' => trim((string) $doctor->clinic_name) ?: '—',
            'clinic_address' => trim((string) $doctor->clinic_address) ?: '—',
        ]];
    }

    private function firstSubServiceName(DoctorRequest $doctor): string
    {
        $subs = is_array($doctor->consultation_sub_services) ? $doctor->consultation_sub_services : [];
        foreach ($subs as $row) {
            if (! is_array($row)) {
                continue;
            }
            $name = trim((string) ($row['sub_service_name'] ?? ''));
            if ($name !== '') {
                return $name;
            }
        }

        return '—';
    }
}
