<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DoctorCalendarAvailabilitySlot;
use App\Models\DoctorConsultationService;
use App\Models\DoctorRequest;
use App\Services\DoctorCalendarAvailabilityService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class PublicDoctorBookingCalendarController extends Controller
{
    public function __construct(
        private DoctorCalendarAvailabilityService $calendarService
    ) {}

    public function show(Request $request, int $doctorRequestId)
    {
        $request->validate([
            'from' => ['required', 'date'],
            'to' => ['required', 'date', 'after_or_equal:from'],
        ]);

        $doctor = DoctorRequest::query()
            ->whereKey($doctorRequestId)
            ->whereRaw('LOWER(TRIM(approval_status)) = ?', ['approved'])
            ->first();

        if (!$doctor) {
            return response()->json(['success' => false, 'message' => 'Doctor not found'], 404);
        }

        $from = Carbon::parse($request->input('from'))->startOfDay();
        $to = Carbon::parse($request->input('to'))->endOfDay();

        $slotColumns = ['slot_date', 'time_start', 'time_end'];
        $hasBreakStart = Schema::hasColumn('doctor_calendar_availability_slots', 'break_start');
        $hasBreakEnd = Schema::hasColumn('doctor_calendar_availability_slots', 'break_end');
        if ($hasBreakStart) {
            $slotColumns[] = 'break_start';
        }
        if ($hasBreakEnd) {
            $slotColumns[] = 'break_end';
        }

        $slots = DoctorCalendarAvailabilitySlot::query()
            ->where('doctor_request_id', $doctor->id)
            ->whereBetween('slot_date', [$from->toDateString(), $to->toDateString()])
            ->orderBy('slot_date')
            ->orderBy('time_start')
            ->get($slotColumns);

        $slotsPayload = $slots->map(function (DoctorCalendarAvailabilitySlot $s) use ($hasBreakStart, $hasBreakEnd) {
            return [
                'date' => $s->slot_date->format('Y-m-d'),
                'time_start' => $s->time_start,
                'time_end' => $s->time_end,
                'break_start' => $hasBreakStart ? $s->break_start : null,
                'break_end' => $hasBreakEnd ? $s->break_end : null,
            ];
        })->values();

        $availableDates = $slots->map(fn (DoctorCalendarAvailabilitySlot $s) => $s->slot_date->format('Y-m-d'))->unique()->values();

        return response()->json([
            'success' => true,
            'slots' => $slotsPayload,
            'available_dates' => $availableDates,
            'booked_dates' => [],
        ]);
    }

    public function times(Request $request, int $doctorRequestId)
    {
        $data = $request->validate([
            'date' => ['required', 'date', 'after_or_equal:today'],
            'doctor_consultation_service_id' => ['required', 'integer', 'exists:doctor_consultation_services,id'],
        ]);

        $doctor = DoctorRequest::query()
            ->whereKey($doctorRequestId)
            ->whereRaw('LOWER(TRIM(approval_status)) = ?', ['approved'])
            ->first();
        if (!$doctor) {
            return response()->json(['success' => false, 'message' => 'Doctor not found'], 404);
        }

        $this->calendarService->cleanupStalePendingPaymentBookings();

        $service = DoctorConsultationService::query()
            ->whereKey((int) $data['doctor_consultation_service_id'])
            ->first();
        $durationMinutes = max(1, (int) ($service?->consultation_duration_minutes ?? 30));
        $options = $this->calendarService->timeOptionsForDate($doctor->id, $data['date'], $durationMinutes);

        return response()->json([
            'success' => true,
            'duration_minutes' => $durationMinutes,
            'date' => Carbon::parse($data['date'])->format('Y-m-d'),
            'times' => $options,
        ]);
    }
}
