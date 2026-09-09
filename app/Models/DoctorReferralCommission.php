<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DoctorReferralCommission extends Model
{
    protected $guarded = [];

    protected $casts = [
        'service_date' => 'date',
        'booking_amount' => 'decimal:2',
        'commission_amount' => 'decimal:2',
    ];

    public function doctorReferralUser(): BelongsTo
    {
        return $this->belongsTo(DoctorReferralUser::class, 'doctor_referral_user_id');
    }

    public function doctorRequest(): BelongsTo
    {
        return $this->belongsTo(DoctorRequest::class, 'doctor_request_id');
    }

    public function consultationWebsiteBooking(): BelongsTo
    {
        return $this->belongsTo(ConsultationWebsiteBooking::class, 'consultation_website_booking_id');
    }

    public static function visitTypeLabel(string $mode): string
    {
        return match ($mode) {
            'online' => 'Online',
            'home_visit' => 'Home visit',
            'clinic_visit' => 'Clinic',
            default => $mode !== '' ? $mode : '—',
        };
    }
}
