<?php

namespace App\Http\Controllers\Concerns;

use App\Models\ConsultationWebsiteBooking;
use App\Models\DoctorReferralCommission;
use App\Models\DoctorReferralUser;
use App\Models\DoctorRequest;
use App\Models\LocationDoctorConsultationPrice;

trait BuildsDoctorReferralPortalData
{
    /** @return array<string, mixed> */
    protected function doctorReferralDashboardPayload(DoctorReferralUser $refUser): array
    {
        $assignedDoctors = DoctorRequest::query()
            ->where('doctor_referral_user_id', $refUser->id)
            ->orderBy('name')
            ->get();

        $commissionRows = DoctorReferralCommission::query()
            ->where('doctor_referral_user_id', $refUser->id)
            ->with('doctorRequest')
            ->orderByDesc('service_date')
            ->orderByDesc('id')
            ->get();

        return [
            'profile' => [
                'id' => $refUser->id,
                'name' => $refUser->name,
                'mobile' => $refUser->mobile,
                'portal_label' => 'Doctor Referral Portal',
            ],
            'assigned_doctor_count' => $assignedDoctors->count(),
            'paid_bookings_count' => $commissionRows->count(),
            'total_commission' => round($commissionRows->sum(fn (DoctorReferralCommission $row): float => (float) $row->commission_amount), 2),
            'assigned_doctor_cards' => $assignedDoctors
                ->map(fn (DoctorRequest $doctor): array => $this->buildAssignedDoctorCard($doctor))
                ->values()
                ->all(),
            'earning_rows' => $commissionRows
                ->map(fn (DoctorReferralCommission $row): array => $this->buildEarningRow($row))
                ->values()
                ->all(),
        ];
    }

    /** @return array<string, mixed> */
    protected function doctorReferralLeadsPayload(DoctorReferralUser $refUser): array
    {
        $doctorIds = DoctorRequest::query()
            ->where('doctor_referral_user_id', $refUser->id)
            ->pluck('id');

        if ($doctorIds->isEmpty()) {
            return [
                'assigned_doctor_count' => 0,
                'paid_leads_count' => 0,
                'pending_leads_count' => 0,
                'total_commission' => 0.0,
                'lead_rows' => [],
            ];
        }

        $bookings = ConsultationWebsiteBooking::query()
            ->whereIn('doctor_request_id', $doctorIds)
            ->with([
                'doctorRequest',
                'consultationService',
                'consultationSubService',
                'operationLead.crmLead',
            ])
            ->orderByDesc('appointment_date')
            ->orderByDesc('id')
            ->get();

        $commissionsByBooking = DoctorReferralCommission::query()
            ->where('doctor_referral_user_id', $refUser->id)
            ->whereIn('consultation_website_booking_id', $bookings->pluck('id'))
            ->get()
            ->keyBy('consultation_website_booking_id');

        $leadRows = $bookings
            ->map(fn (ConsultationWebsiteBooking $booking): array => $this->buildLeadDetailRow(
                $booking,
                $commissionsByBooking->get($booking->id),
            ))
            ->values()
            ->all();

        return [
            'assigned_doctor_count' => $doctorIds->count(),
            'paid_leads_count' => collect($leadRows)->where('payment_status', 'paid')->count(),
            'pending_leads_count' => collect($leadRows)->where('payment_status', '!=', 'paid')->count(),
            'total_commission' => round(collect($leadRows)->sum(fn (array $row): float => (float) ($row['commission_earned'] ?? 0)), 2),
            'lead_rows' => $leadRows,
        ];
    }

    /** @return array<string, mixed> */
    protected function buildLeadDetailRow(
        ConsultationWebsiteBooking $booking,
        ?DoctorReferralCommission $commission = null,
    ): array {
        $doctor = $booking->doctorRequest;
        $mode = (string) $booking->consultation_mode;
        $bookingAmount = $booking->booking_fee_amount !== null
            ? (float) $booking->booking_fee_amount
            : ($doctor?->effectiveWebsiteBookingFeeForMode($mode));

        $commissionMeta = $this->commissionMetaForMode($doctor, $mode, $bookingAmount);
        $commissionEarned = $commission !== null
            ? (float) $commission->commission_amount
            : (((string) ($booking->payment_status ?? '')) === 'paid' ? ($commissionMeta['estimated_commission'] ?? 0.0) : null);

        $serviceName = $booking->consultationService?->name
            ?? $doctor?->job_title
            ?? '—';
        $subServiceName = $booking->sub_service_name
            ?: $booking->consultationSubService?->name
            ?: null;
        $tags = is_array($booking->selected_tags) ? array_values(array_filter($booking->selected_tags)) : [];

        $opLead = $booking->operationLead;
        $leadReference = $commission?->lead_reference
            ?? ($opLead?->crmLead?->formatted_id
                ?? ($opLead?->lead_id ? (string) $opLead->lead_id : null)
                ?? ($doctor?->lead_id ? (string) $doctor->lead_id : null)
                ?? ($doctor ? 'CLX-DOC-' . str_pad((string) $doctor->id, 6, '0', STR_PAD_LEFT) : '—'));

        $city = trim((string) ($booking->customer_city ?? ''));
        if ($city === '') {
            $city = trim((string) ($doctor?->city ?? $doctor?->location ?? ''));
        }

        return [
            'id' => $booking->id,
            'lead_no' => $leadReference ?: '—',
            'doctor_name' => (string) ($doctor?->name ?? '—'),
            'doctor_lead_id' => $doctor?->lead_id ? (string) $doctor->lead_id : ($doctor ? 'CLX-DOC-' . str_pad((string) $doctor->id, 6, '0', STR_PAD_LEFT) : '—'),
            'doctor_city' => trim((string) ($doctor?->city ?? $doctor?->location ?? '')),
            'customer_name' => (string) ($booking->customer_name ?? '—'),
            'customer_mobile' => (string) ($booking->contact_no ?? '—'),
            'customer_city' => $city !== '' ? $city : '—',
            'customer_address' => trim((string) ($booking->customer_address ?? '')),
            'service' => $serviceName,
            'sub_service' => $subServiceName,
            'tags' => $tags,
            'tags_label' => $tags !== [] ? implode(', ', $tags) : '—',
            'mode' => $mode,
            'mode_label' => ConsultationWebsiteBooking::modeLabel($mode),
            'mode_short' => DoctorReferralCommission::visitTypeLabel($mode),
            'appointment_date' => $booking->appointment_date?->format('d M Y') ?? '—',
            'appointment_time' => ConsultationWebsiteBooking::timeRangeLabel(
                $booking->appointment_start_time,
                $booking->appointment_end_time,
            ),
            'duration' => ConsultationWebsiteBooking::durationLabel($booking->consultation_duration_minutes),
            'payment_status' => (string) ($booking->payment_status ?? ''),
            'payment_status_label' => ConsultationWebsiteBooking::paymentStatusLabel($booking->payment_status),
            'booking_amount' => $bookingAmount,
            'booking_amount_label' => $bookingAmount !== null ? '₹'.number_format($bookingAmount, 2) : '—',
            'paid_at' => $booking->paid_at?->format('d M Y, h:i A') ?? '—',
            'booked_at' => $booking->created_at?->format('d M Y, h:i A') ?? '—',
            'commission_type' => $commissionMeta['commission_type'],
            'commission_rate' => $commissionMeta['commission_rate'],
            'commission_calc' => $commissionMeta['commission_calc'],
            'commission_earned' => $commissionEarned,
            'commission_earned_label' => $commissionEarned !== null
                ? '₹'.number_format((float) $commissionEarned, 2)
                : ($commissionMeta['commission_rate'] !== '—' && ((string) ($booking->payment_status ?? '')) !== 'paid'
                    ? 'After payment'
                    : '—'),
            'search_blob' => strtolower(implode(' ', array_filter([
                $leadReference,
                $doctor?->name,
                $booking->customer_name,
                $booking->contact_no,
                $serviceName,
                $subServiceName,
                $city,
                $booking->payment_status,
                $commissionMeta['commission_rate'],
            ]))),
        ];
    }

    /**
     * @return array{commission_type: string, commission_rate: string, commission_calc: string, estimated_commission: ?float}
     */
    protected function commissionMetaForMode(?DoctorRequest $doctor, string $mode, ?float $bookingAmount): array
    {
        $commissionType = 'fixed';
        $commissionLabel = '—';
        $commissionCalc = '—';
        $estimated = null;

        if ($doctor instanceof DoctorRequest) {
            $commissionType = $doctor->referralCommissionTypeForMode($mode);
            $configured = $doctor->referralCommissionConfiguredValueForMode($mode);
            $commissionLabel = $doctor->referralCommissionAdminLabelForMode($mode);
            $estimated = $doctor->referralCommissionForMode($mode, $bookingAmount);

            if ($configured !== null) {
                if ($commissionType === 'percent') {
                    $commissionCalc = $bookingAmount !== null && $bookingAmount > 0
                        ? $commissionLabel.' × ₹'.number_format($bookingAmount, 2)
                        : $commissionLabel.' of paid booking amount';
                } else {
                    $commissionCalc = $commissionLabel.' per paid booking';
                }
            }
        }

        return [
            'commission_type' => $commissionType,
            'commission_rate' => $commissionLabel,
            'commission_calc' => $commissionCalc,
            'estimated_commission' => $estimated,
        ];
    }

    /** @return array<string, mixed> */
    protected function buildAssignedDoctorCard(DoctorRequest $doctor): array
    {
        $modeLabels = LocationDoctorConsultationPrice::MODE_LABELS;
        $enabledModes = is_array($doctor->consultation_modes)
            ? array_values(array_filter(
                $doctor->consultation_modes,
                static fn ($mode): bool => array_key_exists((string) $mode, $modeLabels),
            ))
            : [];

        $subServices = is_array($doctor->consultation_sub_services) ? $doctor->consultation_sub_services : [];
        $pricingRows = is_array($doctor->consultation_pricing) ? $doctor->consultation_pricing : [];
        $pricingBySub = [];
        foreach ($pricingRows as $row) {
            if (! is_array($row)) {
                continue;
            }
            $pricingBySub[(int) ($row['sub_service_id'] ?? 0)] = $row;
        }

        $serviceItems = [];
        $seenSubIds = [];
        foreach ($subServices as $subRow) {
            if (! is_array($subRow)) {
                continue;
            }
            $subId = (int) ($subRow['sub_service_id'] ?? 0);
            $seenSubIds[$subId] = true;
            $serviceItems[] = [
                'sub_service_id' => $subId,
                'name' => (string) ($subRow['sub_service_name'] ?? 'Sub-service'),
                'tags' => is_array($subRow['tags'] ?? null) ? $subRow['tags'] : [],
            ];
        }
        foreach ($pricingBySub as $subId => $row) {
            $subId = (int) $subId;
            if ($subId > 0 && ! isset($seenSubIds[$subId])) {
                $seenSubIds[$subId] = true;
                $serviceItems[] = [
                    'sub_service_id' => $subId,
                    'name' => (string) ($row['sub_service_name'] ?? 'Sub-service'),
                    'tags' => [],
                ];
            }
        }
        if ($serviceItems === []) {
            $serviceItems[] = [
                'sub_service_id' => 0,
                'name' => trim((string) ($doctor->job_title ?? '')) !== '' ? (string) $doctor->job_title : 'Consultation service',
                'tags' => is_array($doctor->specializations) ? $doctor->specializations : [],
            ];
        }

        $serviceRows = [];
        foreach ($serviceItems as $item) {
            $subId = (int) ($item['sub_service_id'] ?? 0);
            foreach ($enabledModes as $modeKey) {
                $mode = (string) $modeKey;
                $patientFee = $doctor->consultationWebsiteFeeForSubServiceAndMode($subId, $mode)
                    ?? $doctor->effectiveWebsiteBookingFeeForMode($mode);
                $commissionType = $doctor->referralCommissionTypeForMode($mode);
                $commissionLabel = $doctor->referralCommissionAdminLabelForMode($mode);
                $estimatedCommission = $doctor->referralCommissionForMode($mode, $patientFee);

                $serviceRows[] = [
                    'service_name' => (string) $item['name'],
                    'tags' => $item['tags'],
                    'mode' => $mode,
                    'mode_label' => $modeLabels[$mode] ?? DoctorReferralCommission::visitTypeLabel($mode),
                    'patient_fee' => $patientFee,
                    'commission_type' => $commissionType,
                    'commission_label' => $commissionLabel,
                    'estimated_commission' => $estimatedCommission,
                ];
            }
        }

        if ($serviceRows === [] && $enabledModes === []) {
            foreach (LocationDoctorConsultationPrice::MODES as $mode) {
                $patientFee = $doctor->effectiveWebsiteBookingFeeForMode($mode);
                $serviceRows[] = [
                    'service_name' => trim((string) ($doctor->job_title ?? '')) !== '' ? (string) $doctor->job_title : 'Consultation',
                    'tags' => [],
                    'mode' => $mode,
                    'mode_label' => $modeLabels[$mode] ?? $mode,
                    'patient_fee' => $patientFee,
                    'commission_type' => $doctor->referralCommissionTypeForMode($mode),
                    'commission_label' => $doctor->referralCommissionAdminLabelForMode($mode),
                    'estimated_commission' => $doctor->referralCommissionForMode($mode, $patientFee),
                ];
            }
        }

        return [
            'id' => $doctor->id,
            'name' => (string) ($doctor->name ?? 'Doctor'),
            'lead_id' => $doctor->lead_id ? (string) $doctor->lead_id : ('CLX-DOC-' . str_pad((string) $doctor->id, 6, '0', STR_PAD_LEFT)),
            'main_service' => trim((string) ($doctor->job_title ?? '')),
            'city' => trim((string) ($doctor->city ?? $doctor->location ?? '')),
            'enabled_modes' => array_map(
                static fn (string $mode): string => $modeLabels[$mode] ?? $mode,
                array_map('strval', $enabledModes),
            ),
            'service_rows' => $serviceRows,
            'has_commission_config' => collect($serviceRows)->contains(
                static fn (array $row): bool => ($row['commission_label'] ?? '—') !== '—',
            ),
        ];
    }

    /** @return array<string, mixed> */
    protected function buildEarningRow(DoctorReferralCommission $row): array
    {
        $doctor = $row->doctorRequest;
        $mode = (string) $row->consultation_mode;
        $bookingAmount = $row->booking_amount !== null ? (float) $row->booking_amount : null;
        $earned = (float) $row->commission_amount;

        $commissionMeta = $this->commissionMetaForMode($doctor, $mode, $bookingAmount);
        $commissionType = $commissionMeta['commission_type'];
        $commissionLabel = $commissionMeta['commission_rate'];
        $commissionCalc = $commissionMeta['commission_calc'];

        if ($commissionLabel === '—' && $bookingAmount !== null && $bookingAmount > 0 && $earned > 0) {
            $pct = round(($earned / $bookingAmount) * 100, 2);
            if (abs($earned - ($bookingAmount * $pct / 100)) < 0.02) {
                $commissionType = 'percent';
                $commissionLabel = number_format($pct, 2).'%';
                $commissionCalc = $commissionLabel.' × ₹'.number_format($bookingAmount, 2);
            } else {
                $commissionLabel = '₹'.number_format($earned, 2).' fix';
                $commissionCalc = $commissionLabel.' per paid booking';
            }
        }

        return [
            'lead_no' => $row->lead_reference ?? '—',
            'dr_name' => $row->doctor_name,
            'service' => $row->consultation_service_name ?? '—',
            'service_date' => $row->service_date ? $row->service_date->format('d M Y') : '—',
            'city' => $row->city ?? '—',
            'visit_type' => DoctorReferralCommission::visitTypeLabel($mode),
            'mode' => $mode,
            'amt' => $bookingAmount,
            'commission_type' => $commissionType,
            'commission_rate' => $commissionLabel,
            'commission_calc' => $commissionCalc,
            'commission' => $earned,
        ];
    }
}
