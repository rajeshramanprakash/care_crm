<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConsultationWebsiteBooking extends Model
{
    /** Normalize stored or input phone to last 10 digits (India-centric). */
    public static function normalizeContactToTenDigits(?string $contact): ?string
    {
        $digits = preg_replace('/\D/', '', (string) $contact);
        if ($digits === '') {
            return null;
        }
        return strlen($digits) >= 10 ? substr($digits, -10) : $digits;
    }

    /**
     * Match rows where normalized contact digits end with this 10-digit mobile.
     * Works across MySQL and SQLite (digits-only tail match).
     */
    public function scopeWhereTenDigitContact($query, string $mobileRaw)
    {
        $n = self::normalizeContactToTenDigits($mobileRaw);
        if ($n === null || strlen($n) !== 10) {
            return $query->whereRaw('1 = 0');
        }
        $expr = "REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(TRIM(contact_no),' ',''),'-',''),'(',''),')',''),'+','')";

        return $query->whereRaw(
            'LENGTH('.$expr.') >= 10 AND SUBSTR('.$expr.', LENGTH('.$expr.') - 9, 10) = ?',
            [$n]
        );
    }

    /** Fields for CRM / CareApp customer portal. */
    public function toCustomerPortalArray(): array
    {
        $doctor = $this->relationLoaded('doctorRequest') ? $this->doctorRequest : $this->doctorRequest()->first();
        $svc = $this->relationLoaded('consultationService') ? $this->consultationService : $this->consultationService()->first();

        return [
            'id' => $this->id,
            'customer_name' => $this->customer_name,
            'consultation_mode' => $this->consultation_mode,
            'consultation_mode_label' => self::modeLabel((string) $this->consultation_mode),
            'appointment_date' => optional($this->appointment_date)->format('Y-m-d'),
            'appointment_start_time' => $this->appointment_start_time,
            'appointment_end_time' => $this->appointment_end_time,
            'time_range_label' => self::timeRangeLabel($this->appointment_start_time, $this->appointment_end_time),
            'duration_label' => self::durationLabel($this->consultation_duration_minutes),
            'booking_fee_amount' => $this->booking_fee_amount !== null ? (float) $this->booking_fee_amount : null,
            'doctor_name' => $doctor?->name,
            'service_name' => $svc?->name,
            'sub_service_name' => $this->sub_service_name ?: $this->consultationSubService?->name,
            'selected_tags' => is_array($this->selected_tags) ? array_values($this->selected_tags) : [],
            'online_meeting_link' => $this->online_meeting_link,
            'online_meeting_provider' => $this->online_meeting_provider,
            'online_meeting_starts_at' => optional($this->online_meeting_starts_at)?->toIso8601String(),
            'online_meeting_ends_at' => optional($this->online_meeting_ends_at)?->toIso8601String(),
            'customer_address' => $this->customer_address,
            'customer_city' => $this->customer_city,
            'customer_address_lat' => $this->customer_address_lat !== null ? (float) $this->customer_address_lat : null,
            'customer_address_lng' => $this->customer_address_lng !== null ? (float) $this->customer_address_lng : null,
        ];
    }

    protected $guarded = [];

    protected $casts = [
        'appointment_date' => 'date',
        'consultation_duration_minutes' => 'integer',
        'booking_fee_amount' => 'decimal:2',
        'customer_address_lat' => 'float',
        'customer_address_lng' => 'float',
        'online_meeting_starts_at' => 'datetime',
        'online_meeting_ends_at' => 'datetime',
        'paid_at' => 'datetime',
        'selected_tags' => 'array',
    ];

    public function doctorRequest(): BelongsTo
    {
        return $this->belongsTo(DoctorRequest::class, 'doctor_request_id');
    }

    public function consultationService(): BelongsTo
    {
        return $this->belongsTo(DoctorConsultationService::class, 'doctor_consultation_service_id');
    }

    public function consultationSubService(): BelongsTo
    {
        return $this->belongsTo(DoctorConsultationServiceSubService::class, 'doctor_consultation_service_sub_service_id');
    }

    public function operationLead(): BelongsTo
    {
        return $this->belongsTo(OperationLead::class, 'operation_lead_id');
    }

    public static function modeLabel(string $mode): string
    {
        return match ($mode) {
            'online' => 'Online (Video / phone consultation)',
            'home_visit' => 'Home visit',
            'clinic_visit' => 'Clinic visit',
            default => $mode,
        };
    }

    /** Human label for website / Easebuzz payment state (admin & reports). */
    public static function paymentStatusLabel(?string $status): string
    {
        return match ((string) $status) {
            'paid' => 'Paid',
            'pending_payment' => 'Pending payment',
            '' => '—',
            default => $status,
        };
    }

    /** Bookings visible in doctor portal (payment completed). */
    public function scopePaidForDoctorPortal($query)
    {
        return $query->where('payment_status', 'paid');
    }

    public static function durationLabel(?int $minutes): string
    {
        $mins = (int) ($minutes ?? 0);
        if ($mins <= 0) {
            return '—';
        }
        if ($mins % 60 === 0) {
            $hours = (int) ($mins / 60);
            return $hours.' hour'.($hours > 1 ? 's' : '');
        }
        if ($mins > 60) {
            $hours = intdiv($mins, 60);
            $rem = $mins % 60;
            return $hours.'h '.$rem.'m';
        }

        return $mins.' min';
    }

    public static function timeRangeLabel(?string $start, ?string $end): string
    {
        $s = trim((string) ($start ?? ''));
        $e = trim((string) ($end ?? ''));
        if ($s === '' || $e === '') {
            return '—';
        }

        return $s.' - '.$e;
    }

    /** Open in Google Maps (query lat,lng). */
    public static function googleMapsOpenUrl(?float $lat, ?float $lng): ?string
    {
        if ($lat === null || $lng === null || ! is_finite($lat) || ! is_finite($lng)) {
            return null;
        }

        return 'https://www.google.com/maps/search/?api=1&query='.rawurlencode($lat.','.$lng);
    }

    /**
     * True if this doctor has a website consultation booking for the given operation lead
     * (by operation_lead_id on the booking or matching contact on the lead).
     */
    public static function doctorHasBookedCustomer(int $doctorRequestId, int $operationLeadId): bool
    {
        $lead = OperationLead::find($operationLeadId);
        if (!$lead) {
            return false;
        }

        return static::query()
            ->where('doctor_request_id', $doctorRequestId)
            ->where(function ($q) use ($operationLeadId, $lead) {
                $q->where('operation_lead_id', $operationLeadId);
                if (!empty($lead->contact_no)) {
                    $q->orWhere(function ($s) use ($lead) {
                        $s->whereTenDigitContact($lead->contact_no);
                    });
                }
            })
            ->exists();
    }

    /**
     * Operation lead ids for this row plus any other leads sharing the same contact number.
     *
     * @return array<int>
     */
    public static function operationLeadIdsForSameContact(int $routeOperationLeadId): array
    {
        $customer = OperationLead::find($routeOperationLeadId);
        $ids = [(int) $routeOperationLeadId];
        if ($customer && $customer->contact_no) {
            $ids = array_unique(array_merge(
                $ids,
                OperationLead::where('contact_no', $customer->contact_no)->pluck('id')->toArray()
            ));
        }

        return array_values(array_map('intval', $ids));
    }

    /**
     * True if the doctor has a booking with this customer on any linked operation lead.
     */
    public static function doctorHasBookedAnyLead(int $doctorRequestId, int $anyOperationLeadId): bool
    {
        foreach (self::operationLeadIdsForSameContact($anyOperationLeadId) as $oid) {
            if (self::doctorHasBookedCustomer($doctorRequestId, $oid)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Allowed when there is a matching booking or an existing chat thread for any linked lead.
     *
     * @return array<int>|null operation lead ids for message queries; null if access denied
     */
    public static function operationLeadIdsForDoctorCustomerChatOrDeny(int $doctorRequestId, int $routeOperationLeadId): ?array
    {
        $operationLeadIds = self::operationLeadIdsForSameContact($routeOperationLeadId);
        if ($operationLeadIds === []) {
            return null;
        }

        foreach ($operationLeadIds as $oid) {
            if (self::doctorHasBookedCustomer($doctorRequestId, $oid)) {
                return $operationLeadIds;
            }
        }

        $hasMessages = CustomerChatMessage::where(function ($q) use ($doctorRequestId, $operationLeadIds) {
            $q->where(function ($q2) use ($doctorRequestId, $operationLeadIds) {
                $q2->where('sender_type', 'doctor')
                    ->where('sender_id', $doctorRequestId)
                    ->where('receiver_type', 'customer')
                    ->whereIn('receiver_id', $operationLeadIds);
            })->orWhere(function ($q2) use ($doctorRequestId, $operationLeadIds) {
                $q2->where('sender_type', 'customer')
                    ->whereIn('sender_id', $operationLeadIds)
                    ->where('receiver_type', 'doctor')
                    ->where('receiver_id', $doctorRequestId);
            });
        })->exists();

        return $hasMessages ? $operationLeadIds : null;
    }

    /**
     * Start/end of this booking as wall clock in app timezone (consultation window for chat).
     *
     * @return array{start: \Carbon\Carbon, end: \Carbon\Carbon}|null
     */
    public static function bookingChatWindowBoundaries(self $booking, ?string $timezone = null): ?array
    {
        $timezone = $timezone ?: (string) config('app.timezone', 'UTC');

        if ($booking->online_meeting_starts_at && $booking->online_meeting_ends_at) {
            $start = $booking->online_meeting_starts_at->copy()->timezone($timezone);
            $end = $booking->online_meeting_ends_at->copy()->timezone($timezone);

            return ['start' => $start, 'end' => $end];
        }

        if (! $booking->appointment_date || ! $booking->appointment_start_time) {
            return null;
        }

        $dateStr = $booking->appointment_date instanceof \DateTimeInterface
            ? $booking->appointment_date->format('Y-m-d')
            : (string) $booking->appointment_date;

        $startRaw = trim((string) $booking->appointment_start_time);
        if (! preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $startRaw)) {
            return null;
        }

        $start = Carbon::createFromFormat('Y-m-d H:i', $dateStr.' '.$startRaw, $timezone);
        $endRaw = trim((string) ($booking->appointment_end_time ?? ''));
        $end = null;
        if ($endRaw !== '' && preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $endRaw)) {
            $end = Carbon::createFromFormat('Y-m-d H:i', $dateStr.' '.$endRaw, $timezone);
        }
        if ($end === null) {
            $mins = max(1, (int) ($booking->consultation_duration_minutes ?? 30));
            $end = (clone $start)->addMinutes($mins);
        }
        if ($end->lessThanOrEqualTo($start)) {
            $end = (clone $start)->addMinutes(max(1, (int) ($booking->consultation_duration_minutes ?? 30)));
        }

        return ['start' => $start, 'end' => $end];
    }

    /**
     * Doctor–customer portal chat may only be used inside a booked consultation window.
     *
     * @return array{
     *   allowed: bool,
     *   user_message: ?string,
     *   window_start: ?string,
     *   window_end: ?string,
     *   next_window_start: ?string
     * }
     */
    public static function doctorCustomerChatScheduleStatus(int $doctorRequestId, int $anyOperationLeadId): array
    {
        $timezone = (string) config('app.timezone', 'UTC');
        $now = Carbon::now($timezone);
        $lead = OperationLead::find($anyOperationLeadId);
        $leadIds = self::operationLeadIdsForSameContact($anyOperationLeadId);

        $bookings = self::query()
            ->where('doctor_request_id', $doctorRequestId)
            ->where(function ($q) use ($leadIds, $lead) {
                $q->whereIn('operation_lead_id', $leadIds);
                if ($lead && $lead->contact_no) {
                    $q->orWhere(function ($sub) use ($lead) {
                        $sub->whereTenDigitContact($lead->contact_no);
                    });
                }
            })
            ->orderBy('appointment_date')
            ->orderBy('appointment_start_time')
            ->get();

        $empty = [
            'allowed' => false,
            'user_message' => 'You can chat only after your appointment date and time are booked. Please complete booking on the consultation form.',
            'window_start' => null,
            'window_end' => null,
            'next_window_start' => null,
        ];

        if ($bookings->isEmpty()) {
            return $empty;
        }

        $windows = [];
        foreach ($bookings as $booking) {
            $w = self::bookingChatWindowBoundaries($booking, $timezone);
            if ($w !== null) {
                $windows[] = $w;
            }
        }

        if ($windows === []) {
            return [
                'allowed' => false,
                'user_message' => 'Your appointment schedule is incomplete. Please set date and time on the consultation booking form, then chat will open during that slot.',
                'window_start' => null,
                'window_end' => null,
                'next_window_start' => null,
            ];
        }

        $isoUtc = fn (Carbon $dt) => $dt->copy()->utc()->toIso8601String();

        foreach ($windows as $w) {
            if ($now->greaterThanOrEqualTo($w['start']) && $now->lessThanOrEqualTo($w['end'])) {
                return [
                    'allowed' => true,
                    'user_message' => null,
                    'window_start' => $isoUtc($w['start']),
                    'window_end' => $isoUtc($w['end']),
                    'next_window_start' => null,
                ];
            }
        }

        $nextStart = null;
        foreach ($windows as $w) {
            if ($w['start']->greaterThan($now)) {
                if ($nextStart === null || $w['start']->lessThan($nextStart)) {
                    $nextStart = $w['start']->copy();
                }
            }
        }

        $fmt = fn (Carbon $dt) => $dt->timezone($timezone)->format('d M Y, h:i A');

        if ($nextStart !== null) {
            return [
                'allowed' => false,
                'user_message' => 'You can chat only during your scheduled appointment window. Messaging opens at '.$fmt($nextStart).' (your booked time).',
                'window_start' => null,
                'window_end' => null,
                'next_window_start' => $isoUtc($nextStart),
            ];
        }

        return [
            'allowed' => false,
            'user_message' => 'You can chat only during your booked appointment time. Your scheduled consultation window has ended. Book another appointment when you want to chat again.',
            'window_start' => null,
            'window_end' => null,
            'next_window_start' => null,
        ];
    }

    /**
     * Meeting join is allowed only inside this booking's scheduled window.
     *
     * @return array{allowed: bool, reason: string, window_start: ?string, window_end: ?string}
     */
    public static function meetingJoinStatus(self $booking): array
    {
        $timezone = (string) config('app.timezone', 'UTC');
        $window = self::bookingChatWindowBoundaries($booking, $timezone);
        if (! $window) {
            return [
                'allowed' => false,
                'reason' => 'This meeting schedule is incomplete. Please contact support.',
                'window_start' => null,
                'window_end' => null,
            ];
        }

        $now = Carbon::now($timezone);
        $start = $window['start'];
        $end = $window['end'];
        $fmt = fn (Carbon $dt) => $dt->copy()->timezone($timezone)->format('d M Y, h:i A');
        $isoUtc = fn (Carbon $dt) => $dt->copy()->utc()->toIso8601String();

        if ($now->lessThan($start)) {
            return [
                'allowed' => false,
                'reason' => 'Meeting link will open at '.$fmt($start).'.',
                'window_start' => $isoUtc($start),
                'window_end' => $isoUtc($end),
            ];
        }
        if ($now->greaterThan($end)) {
            return [
                'allowed' => false,
                'reason' => 'This meeting window ended at '.$fmt($end).'.',
                'window_start' => $isoUtc($start),
                'window_end' => $isoUtc($end),
            ];
        }

        return [
            'allowed' => true,
            'reason' => '',
            'window_start' => $isoUtc($start),
            'window_end' => $isoUtc($end),
        ];
    }
}
