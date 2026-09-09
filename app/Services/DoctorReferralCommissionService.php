<?php

namespace App\Services;

use App\Models\ConsultationWebsiteBooking;
use App\Models\DoctorReferralCommission;
use App\Models\DoctorRequest;

class DoctorReferralCommissionService
{
    public function recordForPaidBooking(ConsultationWebsiteBooking $booking): ?DoctorReferralCommission
    {
        if ((string) ($booking->payment_status ?? '') !== 'paid') {
            return null;
        }

        $booking->loadMissing([
            'doctorRequest',
            'consultationService',
            'operationLead.crmLead',
        ]);

        $doctor = $booking->doctorRequest;
        if (! $doctor instanceof DoctorRequest || ! $doctor->doctor_referral_user_id) {
            return null;
        }

        $mode = (string) $booking->consultation_mode;

        $bookingAmount = $booking->booking_fee_amount !== null
            ? (float) $booking->booking_fee_amount
            : $doctor->effectiveWebsiteBookingFeeForMode($mode);

        $commissionConfigured = $doctor->referralCommissionForMode($mode, $bookingAmount);
        $commissionAmount = $commissionConfigured !== null ? round($commissionConfigured, 2) : 0.0;

        $serviceName = $booking->consultationService?->name ?? $doctor->job_title;
        $cityRaw = trim((string) ($booking->customer_city ?? ''));
        $city = $cityRaw !== '' ? $cityRaw : trim((string) ($doctor->city ?? $doctor->location ?? ''));

        return DoctorReferralCommission::query()->updateOrCreate(
            ['consultation_website_booking_id' => $booking->id],
            [
                'doctor_referral_user_id' => (int) $doctor->doctor_referral_user_id,
                'doctor_request_id' => (int) $doctor->id,
                'operation_lead_id' => $booking->operation_lead_id,
                'lead_reference' => $this->leadReferenceForBooking($booking, $doctor),
                'doctor_name' => (string) ($doctor->name ?? ''),
                'consultation_service_name' => $serviceName,
                'consultation_mode' => $mode,
                'service_date' => $booking->appointment_date,
                'city' => $city !== '' ? $city : null,
                'booking_amount' => $bookingAmount,
                'commission_amount' => $commissionAmount,
            ]
        );
    }

    private function leadReferenceForBooking(ConsultationWebsiteBooking $booking, DoctorRequest $doctor): ?string
    {
        if ($booking->operationLead) {
            $op = $booking->operationLead;
            $crm = $op->crmLead;
            $ref = $crm?->formatted_id ?? ($op->lead_id ? (string) $op->lead_id : 'OL-'.$op->id);

            return (string) $ref;
        }

        if ($doctor->lead_id) {
            return (string) $doctor->lead_id;
        }

        return 'CLX-DOC-' . str_pad((string) $doctor->id, 6, '0', STR_PAD_LEFT);
    }
}
