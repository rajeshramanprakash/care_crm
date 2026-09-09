<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DoctorRequest extends Model
{
    protected $guarded = [];

    protected $fillable = [
        'date_time',
        'lead_id',
        'customer_name',
        'contact_no',
        'email',
        'mobile',
        'name',
        'profile_image',
        'profile_image_upload',
        'profile_image_pending',
        'profile_image_status',
        'profile_image_reviewed_at',
        'profile_image_reviewed_by',
        'age',
        'gender',
        'expected_salary',
        'shift',
        'total_experience',
        'job_title',
        'fluent_languages',
        'about_text',
        'education_history',
        'experience_history',
        'specializations',
        'consultation_sub_services',
        'consultation_pricing',
        'consultation_modes',
        'online_charges',
        'home_visit_charges',
        'coverage_radius_km',
        'base_location_address',
        'base_location_lat',
        'base_location_lng',
        'clinic_consultation_charges',
        'website_customer_fee_online',
        'website_customer_fee_home_visit',
        'website_customer_fee_clinic',
        'clinic_name',
        'clinic_address',
        'clinic_lat',
        'clinic_lng',
        'permanent_address',
        'current_address',
        'current_same_as_permanent',
        'city',
        'location',
        'aadhar_card',
        'pan_card',
        'qualification_certificate',
        'account_name',
        'bank_name',
        'account_number',
        'ifsc_code',
        'upi_id',
        'bank_document',
        'approval_status',
        'admin_remark',
        'reviewed_at',
        'reviewed_by',
        'user_id',
        'website_card_rating',
        'website_card_attend',
        'website_card_qualification',
        'website_card_experience',
        'website_card_modal_description',
        'website_card_book_url',
        'weekly_availability',
        'doctor_referral_user_id',
        'referral_commission_online',
        'referral_commission_online_type',
        'referral_commission_home_visit',
        'referral_commission_home_visit_type',
        'referral_commission_clinic',
        'referral_commission_clinic_type',
        'portal_lead_commission_percent',
    ];

    protected $casts = [
        'consultation_modes' => 'array',
        'fluent_languages' => 'array',
        'education_history' => 'array',
        'experience_history' => 'array',
        'specializations' => 'array',
        'consultation_sub_services' => 'array',
        'consultation_pricing' => 'array',
        'current_same_as_permanent' => 'boolean',
        'online_charges' => 'decimal:2',
        'home_visit_charges' => 'decimal:2',
        'clinic_consultation_charges' => 'decimal:2',
        'website_customer_fee_online' => 'decimal:2',
        'website_customer_fee_home_visit' => 'decimal:2',
        'website_customer_fee_clinic' => 'decimal:2',
        'referral_commission_online' => 'decimal:2',
        'referral_commission_home_visit' => 'decimal:2',
        'referral_commission_clinic' => 'decimal:2',
        'portal_lead_commission_percent' => 'decimal:2',
        'coverage_radius_km' => 'decimal:2',
        'base_location_lat' => 'float',
        'base_location_lng' => 'float',
        'clinic_lat' => 'float',
        'clinic_lng' => 'float',
        'reviewed_at' => 'datetime',
        'profile_image_reviewed_at' => 'datetime',
        'weekly_availability' => 'array',
    ];

    /** @return list<string> */
    public static function weeklyDayKeys(): array
    {
        return ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'];
    }

    /** Default slots (all days off; times are placeholders when enabled). */
    public static function defaultWeeklyAvailability(): array
    {
        $out = [];
        foreach (self::weeklyDayKeys() as $day) {
            $out[$day] = [
                'enabled' => false,
                'start' => '09:00',
                'end' => '18:00',
            ];
        }

        return $out;
    }

    public function mergedWeeklyAvailability(): array
    {
        $merged = self::defaultWeeklyAvailability();
        $stored = $this->weekly_availability;
        if (!is_array($stored)) {
            return $merged;
        }
        foreach (self::weeklyDayKeys() as $day) {
            if (!isset($stored[$day]) || !is_array($stored[$day])) {
                continue;
            }
            $row = $stored[$day];
            $merged[$day] = [
                'enabled' => filter_var($row['enabled'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'start' => is_string($row['start'] ?? null) ? (string) $row['start'] : $merged[$day]['start'],
                'end' => is_string($row['end'] ?? null) ? (string) $row['end'] : $merged[$day]['end'],
            ];
        }

        return $merged;
    }

    private static function timeToMinutes(string $hhmm): int
    {
        if (!preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $hhmm)) {
            return -1;
        }
        [$h, $m] = array_map('intval', explode(':', $hhmm));

        return $h * 60 + $m;
    }

    /**
     * Merges incoming keys onto existing doctor availability (or defaults), validates times and enabled-day rules.
     *
     * @return array{ok:true, data: array<string, array{enabled:bool, start:string, end:string}>}|array{ok:false, message:string}
     */
    public static function mergeAndValidateWeeklyAvailability(?self $existing, ?array $raw): array
    {
        if ($raw === null || !is_array($raw)) {
            return ['ok' => false, 'message' => 'weekly_availability must be an object'];
        }
        $base = $existing ? $existing->mergedWeeklyAvailability() : self::defaultWeeklyAvailability();
        foreach (self::weeklyDayKeys() as $day) {
            if (!array_key_exists($day, $raw)) {
                continue;
            }
            $row = $raw[$day];
            if (!is_array($row)) {
                return ['ok' => false, 'message' => 'Invalid data for day: '.$day];
            }
            $enabled = filter_var($row['enabled'] ?? false, FILTER_VALIDATE_BOOLEAN);
            $start = isset($row['start']) ? (string) $row['start'] : $base[$day]['start'];
            $end = isset($row['end']) ? (string) $row['end'] : $base[$day]['end'];
            if (!preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $start) || !preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $end)) {
                return ['ok' => false, 'message' => 'Use 24h times as HH:MM for '.$day];
            }
            $base[$day] = [
                'enabled' => $enabled,
                'start' => $start,
                'end' => $end,
            ];
        }
        foreach (self::weeklyDayKeys() as $day) {
            $row = $base[$day];
            if (!$row['enabled']) {
                continue;
            }
            $sm = self::timeToMinutes($row['start']);
            $em = self::timeToMinutes($row['end']);
            if ($sm < 0 || $em < 0) {
                return ['ok' => false, 'message' => 'Invalid time for '.$day];
            }
            if ($sm >= $em) {
                return ['ok' => false, 'message' => 'End time must be after start time ('.$day.')'];
            }
        }

        return ['ok' => true, 'data' => $base];
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function priceLogs(): HasMany
    {
        return $this->hasMany(DoctorRequestPriceLog::class, 'doctor_request_id')->orderByDesc('created_at');
    }

    public function priceChangeRequests(): HasMany
    {
        return $this->hasMany(DoctorConsultationPriceChangeRequest::class, 'doctor_request_id')->orderByDesc('created_at');
    }

    public function pendingPriceChangeRequests(): HasMany
    {
        return $this->hasMany(DoctorConsultationPriceChangeRequest::class, 'doctor_request_id')
            ->where('status', DoctorConsultationPriceChangeRequest::STATUS_PENDING)
            ->orderByDesc('created_at');
    }

    public function consultationWebsiteBookings(): HasMany
    {
        return $this->hasMany(ConsultationWebsiteBooking::class, 'doctor_request_id')->orderByDesc('created_at');
    }

    public function portalLeads(): HasMany
    {
        return $this->hasMany(DoctorPortalLead::class, 'doctor_request_id')->orderByDesc('created_at');
    }

    public function calendarAvailabilitySlots(): HasMany
    {
        return $this->hasMany(DoctorCalendarAvailabilitySlot::class, 'doctor_request_id')->orderBy('slot_date');
    }

    public function websiteProfileReviews(): HasMany
    {
        return $this->hasMany(DoctorWebsiteProfileReview::class, 'doctor_request_id')
            ->orderBy('sort_order')
            ->orderByDesc('id');
    }

    public function leegalitySignatures(): HasMany
    {
        return $this->hasMany(DoctorLeegalitySignature::class, 'doctor_request_id');
    }

    public function latestLeegalitySignature()
    {
        return $this->hasOne(DoctorLeegalitySignature::class, 'doctor_request_id')->latestOfMany();
    }

    public function doctorReferralUser(): BelongsTo
    {
        return $this->belongsTo(DoctorReferralUser::class, 'doctor_referral_user_id');
    }

    /**
     * Per-doctor override from consultation_pricing (service / sub-service × mode).
     */
    public function consultationChargeForSubServiceAndMode(?int $subServiceId, string $mode): ?float
    {
        $mode = strtolower(trim($mode));
        $pricing = $this->consultation_pricing;
        if (! is_array($pricing) || $pricing === []) {
            return null;
        }

        $subId = ($subServiceId !== null && $subServiceId > 0) ? $subServiceId : 0;

        foreach ($pricing as $row) {
            if (! is_array($row)) {
                continue;
            }
            if ((int) ($row['sub_service_id'] ?? 0) !== $subId) {
                continue;
            }
            $modes = $row['modes'] ?? [];
            if (! is_array($modes)) {
                continue;
            }
            $modeRow = $modes[$mode] ?? null;
            if (! is_array($modeRow)) {
                continue;
            }
            $price = $modeRow['doctor_price'] ?? null;
            if ($price === null || $price === '') {
                continue;
            }
            if (! is_numeric($price)) {
                continue;
            }
            $amount = (float) $price;

            return $amount > 0 ? $amount : null;
        }

        return null;
    }

    /**
     * Per-doctor CareWeb card price from consultation_pricing (sub-service × mode).
     */
    public function consultationWebsiteFeeForSubServiceAndMode(?int $subServiceId, string $mode): ?float
    {
        $mode = strtolower(trim($mode));
        $pricing = $this->consultation_pricing;
        if (! is_array($pricing) || $pricing === []) {
            return null;
        }

        $subId = ($subServiceId !== null && $subServiceId > 0) ? $subServiceId : 0;

        foreach ($pricing as $row) {
            if (! is_array($row)) {
                continue;
            }
            if ((int) ($row['sub_service_id'] ?? 0) !== $subId) {
                continue;
            }
            $modes = $row['modes'] ?? [];
            if (! is_array($modes)) {
                continue;
            }
            $modeRow = $modes[$mode] ?? null;
            if (! is_array($modeRow)) {
                continue;
            }
            $price = $modeRow['website_price'] ?? null;
            if ($price === null || $price === '') {
                continue;
            }
            if (! is_numeric($price)) {
                continue;
            }
            $amount = (float) $price;

            return $amount > 0 ? $amount : null;
        }

        return null;
    }

    /**
     * Flat admin CareWeb patient fee (Update consultation charges).
     */
    public function websiteCustomerFeeForMode(string $mode): ?float
    {
        $mode = strtolower(trim($mode));
        $customer = match ($mode) {
            'online' => $this->website_customer_fee_online,
            'home_visit' => $this->website_customer_fee_home_visit,
            'clinic_visit' => $this->website_customer_fee_clinic,
            default => null,
        };
        if ($customer === null || $customer === '') {
            return null;
        }
        $amount = (float) $customer;

        return $amount > 0 ? $amount : null;
    }

    /**
     * Admin-set consultation charges (Update consultation charges in doctor requests).
     */
    public function adminConsultationChargeForMode(string $mode): ?float
    {
        $mode = strtolower(trim($mode));
        $raw = match ($mode) {
            'online' => $this->online_charges,
            'home_visit' => $this->home_visit_charges,
            'clinic_visit' => $this->clinic_consultation_charges,
            default => null,
        };
        if ($raw === null || $raw === '') {
            return null;
        }
        $amount = (float) $raw;

        return $amount > 0 ? $amount : null;
    }

    /**
     * Amount the patient pays on the website for this consultation mode:
     * admin-set website_customer_fee_* if present, otherwise doctor-entered consultation charge.
     */
    public function effectiveWebsiteBookingFeeForMode(string $mode): ?float
    {
        $mode = strtolower(trim($mode));
        $customer = match ($mode) {
            'online' => $this->website_customer_fee_online,
            'home_visit' => $this->website_customer_fee_home_visit,
            'clinic_visit' => $this->website_customer_fee_clinic,
            default => null,
        };
        if ($customer !== null && $customer !== '' && (float) $customer > 0) {
            return (float) $customer;
        }

        return $this->adminConsultationChargeForMode($mode);
    }

    public function referralCommissionTypeForMode(string $mode): string
    {
        $mode = strtolower(trim($mode));
        $type = match ($mode) {
            'online' => $this->referral_commission_online_type,
            'home_visit' => $this->referral_commission_home_visit_type,
            'clinic_visit' => $this->referral_commission_clinic_type,
            default => null,
        };

        return strtolower(trim((string) $type)) === 'percent' ? 'percent' : 'fixed';
    }

    /** Admin-configured commission value (% or fixed ₹ depending on type). */
    public function referralCommissionConfiguredValueForMode(string $mode): ?float
    {
        $mode = strtolower(trim($mode));
        $val = match ($mode) {
            'online' => $this->referral_commission_online,
            'home_visit' => $this->referral_commission_home_visit,
            'clinic_visit' => $this->referral_commission_clinic,
            default => null,
        };
        if ($val === null || $val === '') {
            return null;
        }

        return (float) $val;
    }

    /** Calculated referral commission for a paid booking in this mode. */
    public function referralCommissionForMode(string $mode, ?float $bookingAmount = null): ?float
    {
        $configured = $this->referralCommissionConfiguredValueForMode($mode);
        if ($configured === null) {
            return null;
        }

        if ($this->referralCommissionTypeForMode($mode) === 'percent') {
            $base = $bookingAmount !== null ? (float) $bookingAmount : 0.0;
            if ($base <= 0) {
                return 0.0;
            }

            return round($base * ($configured / 100), 2);
        }

        return round($configured, 2);
    }

    public function referralCommissionAdminLabelForMode(string $mode): string
    {
        $configured = $this->referralCommissionConfiguredValueForMode($mode);
        if ($configured === null) {
            return '—';
        }

        if ($this->referralCommissionTypeForMode($mode) === 'percent') {
            return number_format($configured, 2).'%';
        }

        return '₹'.number_format($configured, 2);
    }

    /**
     * Commission % for doctor-portal "Refer Lead" submissions (default 10%).
     */
    public function portalLeadCommissionPercent(): float
    {
        $value = $this->portal_lead_commission_percent;
        if ($value === null || $value === '') {
            return 10.0;
        }

        return max(0, min(100, (float) $value));
    }
}
