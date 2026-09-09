<?php

namespace App\Services;

use App\Models\DoctorConsultationService;
use App\Models\DoctorConsultationPriceChangeRequest;
use App\Models\DoctorRequest;
use App\Models\DoctorRequestPriceLog;
use App\Models\LocationDoctorConsultationPrice;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DoctorConsultationPriceChangeService
{
    public function __construct(
        private readonly ConsultationLocationPricingService $locationPricingService
    ) {}

    public function currentDoctorCharge(DoctorRequest $doctor, int $subServiceId, string $mode): ?float
    {
        $resolved = $this->locationPricingService->portalDisplayPricesForSubMode(
            $doctor,
            $this->locationMatrixForDoctor($doctor),
            $subServiceId,
            $mode
        );

        $price = $resolved['doctor'] ?? null;
        if ($price === null || $price === '') {
            return null;
        }

        return (float) $price;
    }

    /**
     * @return array<int, array<string, array{website_price: ?float, doctor_max_price: ?float}>>
     */
    public function locationMatrixForDoctor(DoctorRequest $doctor): array
    {
        $serviceName = trim((string) ($doctor->job_title ?? ''));
        if ($serviceName === '') {
            return [];
        }

        $serviceRow = DoctorConsultationService::query()->where('name', $serviceName)->first();
        if (! $serviceRow) {
            return [];
        }

        $locationName = trim((string) ($doctor->city ?? $doctor->location ?? ''));

        return $this->locationPricingService->locationPricesMatrix($locationName, (int) $serviceRow->id);
    }

    public function submit(DoctorRequest $doctor, array $payload): DoctorConsultationPriceChangeRequest
    {
        $subServiceId = (int) ($payload['sub_service_id'] ?? 0);
        $mode = strtolower(trim((string) ($payload['consultation_mode'] ?? '')));
        $requested = (float) ($payload['requested_price'] ?? 0);

        if (! in_array($mode, LocationDoctorConsultationPrice::MODES, true)) {
            throw ValidationException::withMessages([
                'consultation_mode' => ['Invalid consultation mode.'],
            ]);
        }

        if ($requested <= 0) {
            throw ValidationException::withMessages([
                'requested_price' => ['Please enter a valid price greater than zero.'],
            ]);
        }

        $enabledModes = is_array($doctor->consultation_modes ?? null) ? $doctor->consultation_modes : [];
        if (! in_array($mode, $enabledModes, true)) {
            throw ValidationException::withMessages([
                'consultation_mode' => ['This consultation mode is not enabled on your profile.'],
            ]);
        }

        $serviceName = trim((string) ($doctor->job_title ?? ''));
        $serviceRow = $serviceName !== ''
            ? DoctorConsultationService::query()->where('is_active', true)->where('name', $serviceName)->first()
            : null;

        if (! $serviceRow) {
            throw ValidationException::withMessages([
                'service' => ['Consultation service is not set on your profile.'],
            ]);
        }

        if ($subServiceId > 0) {
            $allowedSubIds = collect(is_array($doctor->consultation_sub_services ?? null) ? $doctor->consultation_sub_services : [])
                ->map(fn ($row) => (int) ($row['sub_service_id'] ?? 0))
                ->filter(fn ($id) => $id > 0)
                ->all();
            if ($allowedSubIds !== [] && ! in_array($subServiceId, $allowedSubIds, true)) {
                throw ValidationException::withMessages([
                    'sub_service_id' => ['This sub-service is not linked to your profile.'],
                ]);
            }
        }

        $pendingExists = DoctorConsultationPriceChangeRequest::query()
            ->where('doctor_request_id', $doctor->id)
            ->where('sub_service_id', $subServiceId)
            ->where('consultation_mode', $mode)
            ->where('status', DoctorConsultationPriceChangeRequest::STATUS_PENDING)
            ->exists();

        if ($pendingExists) {
            throw ValidationException::withMessages([
                'requested_price' => ['A price change request for this sub-service and mode is already pending admin review.'],
            ]);
        }

        $current = $this->currentDoctorCharge($doctor, $subServiceId, $mode);
        if ($current !== null && round($current, 2) === round($requested, 2)) {
            throw ValidationException::withMessages([
                'requested_price' => ['Requested price is the same as your current charge.'],
            ]);
        }

        $subName = null;
        if ($subServiceId > 0) {
            foreach (is_array($doctor->consultation_sub_services ?? null) ? $doctor->consultation_sub_services : [] as $row) {
                if ((int) ($row['sub_service_id'] ?? 0) === $subServiceId) {
                    $subName = (string) ($row['sub_service_name'] ?? 'Sub-service');
                    break;
                }
            }
            if ($subName === null) {
                $subName = (string) ($serviceRow->subServices->firstWhere('id', $subServiceId)?->name ?? 'Sub-service');
            }
        }

        return DoctorConsultationPriceChangeRequest::create([
            'doctor_request_id' => $doctor->id,
            'service_id' => (int) $serviceRow->id,
            'service_name' => (string) $serviceRow->name,
            'sub_service_id' => $subServiceId,
            'sub_service_name' => $subName,
            'consultation_mode' => $mode,
            'current_price' => $current,
            'requested_price' => $requested,
            'status' => DoctorConsultationPriceChangeRequest::STATUS_PENDING,
        ]);
    }

    public function approve(DoctorConsultationPriceChangeRequest $changeRequest, ?int $adminUserId = null): void
    {
        if ($changeRequest->status !== DoctorConsultationPriceChangeRequest::STATUS_PENDING) {
            throw ValidationException::withMessages([
                'status' => ['This request has already been reviewed.'],
            ]);
        }

        DB::transaction(function () use ($changeRequest, $adminUserId) {
            $doctor = $changeRequest->doctorRequest()->lockForUpdate()->first();
            if (! $doctor) {
                throw ValidationException::withMessages(['doctor' => ['Doctor not found.']]);
            }

            $this->applyPriceToDoctor($doctor, $changeRequest);

            DoctorRequestPriceLog::create([
                'doctor_request_id' => $doctor->id,
                'mode' => $changeRequest->consultation_mode,
                'old_amount' => $changeRequest->current_price,
                'new_amount' => $changeRequest->requested_price,
                'note' => 'Approved doctor price change request #'.$changeRequest->id
                    .($changeRequest->sub_service_name ? ' ('.$changeRequest->sub_service_name.')' : ''),
                'updated_by' => $adminUserId,
            ]);

            $changeRequest->status = DoctorConsultationPriceChangeRequest::STATUS_APPROVED;
            $changeRequest->admin_note = null;
            $changeRequest->reviewed_by = $adminUserId;
            $changeRequest->reviewed_at = now();
            $changeRequest->save();

            DoctorConsultationPriceChangeRequest::query()
                ->where('doctor_request_id', $doctor->id)
                ->where('sub_service_id', (int) $changeRequest->sub_service_id)
                ->where('consultation_mode', $changeRequest->consultation_mode)
                ->where('status', DoctorConsultationPriceChangeRequest::STATUS_PENDING)
                ->where('id', '!=', $changeRequest->id)
                ->update([
                    'status' => DoctorConsultationPriceChangeRequest::STATUS_REJECTED,
                    'admin_note' => 'Superseded by approved request #'.$changeRequest->id.'.',
                    'reviewed_by' => $adminUserId,
                    'reviewed_at' => now(),
                ]);
        });
    }

    public function reject(DoctorConsultationPriceChangeRequest $changeRequest, string $adminNote, ?int $adminUserId = null): void
    {
        if ($changeRequest->status !== DoctorConsultationPriceChangeRequest::STATUS_PENDING) {
            throw ValidationException::withMessages([
                'status' => ['This request has already been reviewed.'],
            ]);
        }

        $adminNote = trim($adminNote);
        if ($adminNote === '') {
            throw ValidationException::withMessages([
                'admin_note' => ['Please enter a reason for rejection.'],
            ]);
        }

        $changeRequest->status = DoctorConsultationPriceChangeRequest::STATUS_REJECTED;
        $changeRequest->admin_note = $adminNote;
        $changeRequest->reviewed_by = $adminUserId;
        $changeRequest->reviewed_at = now();
        $changeRequest->save();
    }

    private function applyPriceToDoctor(DoctorRequest $doctor, DoctorConsultationPriceChangeRequest $changeRequest): void
    {
        $subId = (int) $changeRequest->sub_service_id;
        $mode = (string) $changeRequest->consultation_mode;
        $newPrice = (float) $changeRequest->requested_price;

        $pricing = is_array($doctor->consultation_pricing ?? null) ? $doctor->consultation_pricing : [];
        $updated = false;

        foreach ($pricing as &$row) {
            if (! is_array($row)) {
                continue;
            }
            if ((int) ($row['sub_service_id'] ?? 0) !== $subId) {
                continue;
            }
            $modes = is_array($row['modes'] ?? null) ? $row['modes'] : [];
            $existing = is_array($modes[$mode] ?? null) ? $modes[$mode] : [];
            $modes[$mode] = array_merge($existing, [
                'doctor_price' => $newPrice,
                'locked' => false,
            ]);
            $row['modes'] = $modes;
            if (empty($row['service_id']) && $changeRequest->service_id) {
                $row['service_id'] = (int) $changeRequest->service_id;
            }
            if ($subId > 0 && empty($row['sub_service_name']) && $changeRequest->sub_service_name) {
                $row['sub_service_name'] = $changeRequest->sub_service_name;
            }
            $updated = true;
            break;
        }
        unset($row);

        if (! $updated) {
            $city = trim((string) ($doctor->city ?? $doctor->location ?? ''));
            $locationId = $city !== '' ? (int) (\App\Models\Location::query()->where('name', $city)->value('id') ?? 0) : 0;

            $pricing[] = [
                'location_id' => $locationId > 0 ? $locationId : null,
                'service_id' => (int) ($changeRequest->service_id ?? 0),
                'sub_service_id' => $subId,
                'sub_service_name' => $subId > 0 ? $changeRequest->sub_service_name : null,
                'modes' => [
                    $mode => [
                        'doctor_price' => $newPrice,
                        'website_price' => null,
                        'locked' => false,
                    ],
                ],
            ];
        }

        $doctor->consultation_pricing = $pricing;

        if ($subId === 0) {
            $legacyMap = [
                'online' => 'online_charges',
                'home_visit' => 'home_visit_charges',
                'clinic_visit' => 'clinic_consultation_charges',
            ];
            if (isset($legacyMap[$mode])) {
                $doctor->{$legacyMap[$mode]} = $newPrice;
            }
        }

        $doctor->save();
    }
}
