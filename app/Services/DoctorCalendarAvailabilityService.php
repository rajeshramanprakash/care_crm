<?php

namespace App\Services;

use App\Models\ConsultationWebsiteBooking;
use App\Models\DoctorCalendarAvailabilitySlot;
use App\Models\DoctorRequest;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DoctorCalendarAvailabilityService
{
    public function pendingPaymentHoldMinutes(): int
    {
        return max(1, (int) config('services.consultation.pending_payment_hold_minutes', 15));
    }

    public function pendingPaymentHoldCutoff(): Carbon
    {
        return now()->subMinutes($this->pendingPaymentHoldMinutes());
    }

    /**
     * Bookings that block calendar slots: paid (permanent) or pending_payment within checkout hold.
     */
    public function blockingBookingsQuery(int $doctorRequestId, string $date, ?int $excludeBookingId = null)
    {
        $holdCutoff = $this->pendingPaymentHoldCutoff();

        $query = ConsultationWebsiteBooking::query()
            ->where('doctor_request_id', $doctorRequestId)
            ->whereDate('appointment_date', $date)
            ->where(function ($q) use ($holdCutoff) {
                $q->where('payment_status', 'paid')
                    ->orWhere(function ($q2) use ($holdCutoff) {
                        $q2->where('payment_status', 'pending_payment')
                            ->where('created_at', '>=', $holdCutoff);
                    });
            });

        if ($excludeBookingId !== null && $excludeBookingId > 0) {
            $query->where('id', '!=', $excludeBookingId);
        }

        return $query;
    }

    /**
     * Remove checkout holds that expired without payment.
     */
    public function cleanupStalePendingPaymentBookings(): int
    {
        return ConsultationWebsiteBooking::query()
            ->where('payment_status', 'pending_payment')
            ->where('created_at', '<', $this->pendingPaymentHoldCutoff())
            ->delete();
    }

    public function validateTimes(string $start, string $end, ?string $breakStart = null, ?string $breakEnd = null): ?string
    {
        if (!preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $start) || !preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $end)) {
            return 'Use 24-hour times as HH:MM.';
        }
        $sm = $this->timeToMinutes($start);
        $em = $this->timeToMinutes($end);
        if ($sm >= $em) {
            return 'End time must be after start time.';
        }
        if (($breakStart === null || $breakStart === '') xor ($breakEnd === null || $breakEnd === '')) {
            return 'Set both break start and break end, or leave both empty.';
        }
        if ($breakStart !== null && $breakStart !== '' && $breakEnd !== null && $breakEnd !== '') {
            if (!preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $breakStart) || !preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $breakEnd)) {
                return 'Break times must be HH:MM.';
            }
            $bm = $this->timeToMinutes($breakStart);
            $be = $this->timeToMinutes($breakEnd);
            if ($bm >= $be) {
                return 'Break end must be after break start.';
            }
            if ($bm <= $sm || $be >= $em) {
                return 'Break must be within slot start and end.';
            }
        }

        return null;
    }

    public function timeToMinutes(string $hhmm): int
    {
        [$h, $m] = array_map('intval', explode(':', $hhmm));

        return $h * 60 + $m;
    }

    public function minutesToTime(int $mins): string
    {
        $h = intdiv($mins, 60);
        $m = $mins % 60;

        return sprintf('%02d:%02d', $h, $m);
    }

    /**
     * @return Collection<int, DoctorCalendarAvailabilitySlot>
     */
    public function listForDoctor(DoctorRequest $doctor, string $from, string $to): Collection
    {
        return DoctorCalendarAvailabilitySlot::query()
            ->where('doctor_request_id', $doctor->id)
            ->whereBetween('slot_date', [$from, $to])
            ->orderBy('slot_date')
            ->orderBy('time_start')
            ->get();
    }

    /**
     * @param list<array{time_start:string,time_end:string,break_start:?string,break_end:?string}> $slotDefs
     */
    public function createSlotsForDates(DoctorRequest $doctor, array $dates, array $slotDefs, bool $replaceExisting): int
    {
        $count = 0;
        DB::transaction(function () use ($doctor, $dates, $slotDefs, $replaceExisting, &$count) {
            foreach ($dates as $ds) {
                if ($replaceExisting) {
                    DoctorCalendarAvailabilitySlot::query()
                        ->where('doctor_request_id', $doctor->id)
                        ->whereDate('slot_date', $ds)
                        ->delete();
                }
                foreach ($slotDefs as $slot) {
                    DoctorCalendarAvailabilitySlot::updateOrCreate(
                        [
                            'doctor_request_id' => $doctor->id,
                            'slot_date' => $ds,
                            'time_start' => $slot['time_start'],
                            'time_end' => $slot['time_end'],
                        ],
                        [
                            'break_start' => $slot['break_start'] ?: null,
                            'break_end' => $slot['break_end'] ?: null,
                        ]
                    );
                    $count++;
                }
            }
        });

        return $count;
    }

    /**
     * @return list<string> Y-m-d
     */
    public function datesForMonth(int $year, int $month, bool $weekdaysOnly): array
    {
        $start = Carbon::create($year, $month, 1)->startOfMonth();
        $end = (clone $start)->endOfMonth();
        $dates = [];
        foreach (CarbonPeriod::create($start, $end) as $date) {
            /** @var Carbon $date */
            if ($weekdaysOnly && $date->isWeekend()) {
                continue;
            }
            $dates[] = $date->format('Y-m-d');
        }

        return $dates;
    }

    /**
     * @return list<string> Y-m-d
     */
    public function datesForYear(int $year, bool $weekdaysOnly): array
    {
        $start = Carbon::create($year, 1, 1)->startOfYear();
        $end = Carbon::create($year, 12, 31)->endOfYear();
        $dates = [];
        foreach (CarbonPeriod::create($start, $end) as $date) {
            /** @var Carbon $date */
            if ($weekdaysOnly && $date->isWeekend()) {
                continue;
            }
            $dates[] = $date->format('Y-m-d');
        }

        return $dates;
    }

    public function deleteSlot(DoctorRequest $doctor, int $slotId): bool
    {
        $row = DoctorCalendarAvailabilitySlot::query()
            ->where('doctor_request_id', $doctor->id)
            ->whereKey($slotId)
            ->first();
        if (!$row) {
            return false;
        }
        $row->delete();

        return true;
    }

    public function deleteByDate(DoctorRequest $doctor, string $slotDate): void
    {
        DoctorCalendarAvailabilitySlot::query()
            ->where('doctor_request_id', $doctor->id)
            ->whereDate('slot_date', $slotDate)
            ->delete();
    }

    /**
     * @return list<array{start:int,end:int}>
     */
    public function availableSegmentsForDate(int $doctorRequestId, string $date): array
    {
        $rows = DoctorCalendarAvailabilitySlot::query()
            ->where('doctor_request_id', $doctorRequestId)
            ->whereDate('slot_date', $date)
            ->orderBy('time_start')
            ->get();
        $segments = [];
        foreach ($rows as $row) {
            $start = $this->timeToMinutes((string) $row->time_start);
            $end = $this->timeToMinutes((string) $row->time_end);
            if ($start >= $end) {
                continue;
            }
            if (!empty($row->break_start) && !empty($row->break_end)) {
                $bs = $this->timeToMinutes((string) $row->break_start);
                $be = $this->timeToMinutes((string) $row->break_end);
                if ($start < $bs) {
                    $segments[] = ['start' => $start, 'end' => min($bs, $end)];
                }
                if ($be < $end) {
                    $segments[] = ['start' => max($be, $start), 'end' => $end];
                }
            } else {
                $segments[] = ['start' => $start, 'end' => $end];
            }
        }

        return array_values(array_filter($segments, fn ($s) => $s['start'] < $s['end']));
    }

    /**
     * @return list<array{start:int,end:int}>
     */
    public function bookedIntervalsForDate(int $doctorRequestId, string $date, ?int $excludeBookingId = null): array
    {
        $rows = $this->blockingBookingsQuery($doctorRequestId, $date, $excludeBookingId)
            ->get(['appointment_start_time', 'appointment_end_time']);
        $out = [];
        foreach ($rows as $r) {
            $st = trim((string) ($r->appointment_start_time ?? ''));
            $en = trim((string) ($r->appointment_end_time ?? ''));
            if ($st === '' || $en === '') {
                // Legacy row without times: treat full day blocked.
                $out[] = ['start' => 0, 'end' => 24 * 60];
                continue;
            }
            $sm = $this->timeToMinutes($st);
            $em = $this->timeToMinutes($en);
            if ($sm < $em) {
                $out[] = ['start' => $sm, 'end' => $em];
            }
        }

        return $out;
    }

    public function intervalsOverlap(int $aStart, int $aEnd, int $bStart, int $bEnd): bool
    {
        return $aStart < $bEnd && $bStart < $aEnd;
    }

    /**
     * @return list<array{start_time:string,end_time:string,is_booked:bool}>
     */
    public function timeOptionsForDate(int $doctorRequestId, string $date, int $durationMinutes, int $gapMinutes = 5): array
    {
        $duration = max(1, $durationMinutes);
        $step = max(1, $duration + max(0, $gapMinutes));
        $segments = $this->availableSegmentsForDate($doctorRequestId, $date);
        $booked = $this->bookedIntervalsForDate($doctorRequestId, $date);
        $seen = [];
        $out = [];
        foreach ($segments as $seg) {
            for ($start = $seg['start']; $start + $duration <= $seg['end']; $start += $step) {
                $end = $start + $duration;
                $key = $start.'-'.$end;
                if (isset($seen[$key])) {
                    continue;
                }
                $seen[$key] = true;
                $isBooked = false;
                foreach ($booked as $bi) {
                    if ($this->intervalsOverlap($start, $end, $bi['start'], $bi['end'])) {
                        $isBooked = true;
                        break;
                    }
                }
                $out[] = [
                    'start_time' => $this->minutesToTime($start),
                    'end_time' => $this->minutesToTime($end),
                    'is_booked' => $isBooked,
                ];
            }
        }
        usort($out, fn ($a, $b) => strcmp($a['start_time'], $b['start_time']));

        return $out;
    }

    public function isAppointmentWindowAvailable(
        int $doctorRequestId,
        string $date,
        string $startTime,
        int $durationMinutes,
        ?int $excludeBookingId = null
    ): bool {
        $duration = max(1, $durationMinutes);
        $start = $this->timeToMinutes($startTime);
        $end = $start + $duration;
        $segments = $this->availableSegmentsForDate($doctorRequestId, $date);
        $fitsSegment = false;
        foreach ($segments as $seg) {
            if ($start >= $seg['start'] && $end <= $seg['end']) {
                $fitsSegment = true;
                break;
            }
        }
        if (!$fitsSegment) {
            return false;
        }
        $booked = $this->bookedIntervalsForDate($doctorRequestId, $date, $excludeBookingId);
        foreach ($booked as $bi) {
            if ($this->intervalsOverlap($start, $end, $bi['start'], $bi['end'])) {
                return false;
            }
        }

        return true;
    }
}
