<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DoctorCalendarAvailabilitySlot;
use App\Models\DoctorRequest;
use App\Services\DoctorCalendarAvailabilityService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class DoctorCalendarAvailabilityApiController extends Controller
{
    public function __construct(
        private DoctorCalendarAvailabilityService $calendarService
    ) {}

    private function doctorForUser($user): ?DoctorRequest
    {
        if (!$user) {
            return null;
        }
        $cached = Cache::get('doctor_reg_info_'.$user->id);
        if ($cached && !empty($cached['doctor_request_id'])) {
            $d = DoctorRequest::find($cached['doctor_request_id']);
            if ($d && $d->approval_status === 'approved') {
                return $d;
            }
        }
        $mobile = $user->mobile;

        return DoctorRequest::query()
            ->where(function ($q) use ($mobile) {
                $q->where('mobile', $mobile)->orWhere('contact_no', $mobile);
            })
            ->where('approval_status', 'approved')
            ->first();
    }

    public function index(Request $request)
    {
        $doctor = $this->doctorForUser($request->user());
        if (!$doctor) {
            return response()->json(['success' => false, 'message' => 'Doctor profile not found'], 404);
        }
        $request->validate([
            'from' => ['required', 'date'],
            'to' => ['required', 'date', 'after_or_equal:from'],
        ]);

        $slots = $this->calendarService->listForDoctor($doctor, $request->input('from'), $request->input('to'));

        return response()->json([
            'success' => true,
            'slots' => $slots->map(fn (DoctorCalendarAvailabilitySlot $s) => [
                'id' => $s->id,
                'slot_date' => $s->slot_date->format('Y-m-d'),
                'time_start' => $s->time_start,
                'time_end' => $s->time_end,
                'break_start' => $s->break_start,
                'break_end' => $s->break_end,
                'label' => $s->fullLabel(),
            ]),
        ]);
    }

    /**
     * @return list<array{time_start:string,time_end:string,break_start:?string,break_end:?string}>
     */
    private function validateAndParseSlots(Request $request): array
    {
        $slotsInput = $request->input('slots');
        if (is_array($slotsInput) && count($slotsInput) > 0) {
            $validated = $request->validate([
                'slots' => ['required', 'array', 'min:1', 'max:20'],
                'slots.*.time_start' => ['required', 'string', 'size:5'],
                'slots.*.time_end' => ['required', 'string', 'size:5'],
                'slots.*.break_start' => ['nullable', 'string', 'size:5'],
                'slots.*.break_end' => ['nullable', 'string', 'size:5'],
            ]);
            $defs = [];
            foreach ($validated['slots'] as $slot) {
                $err = $this->calendarService->validateTimes(
                    $slot['time_start'],
                    $slot['time_end'],
                    $slot['break_start'] ?? null,
                    $slot['break_end'] ?? null
                );
                if ($err) {
                    throw new HttpResponseException(response()->json(['success' => false, 'message' => $err], 422));
                }
                $defs[] = [
                    'time_start' => $slot['time_start'],
                    'time_end' => $slot['time_end'],
                    'break_start' => $slot['break_start'] ?? null,
                    'break_end' => $slot['break_end'] ?? null,
                ];
            }

            return $defs;
        }

        $single = $request->validate([
            'time_start' => ['required', 'string', 'size:5'],
            'time_end' => ['required', 'string', 'size:5'],
            'break_start' => ['nullable', 'string', 'size:5'],
            'break_end' => ['nullable', 'string', 'size:5'],
        ]);
        $err = $this->calendarService->validateTimes(
            $single['time_start'],
            $single['time_end'],
            $single['break_start'] ?? null,
            $single['break_end'] ?? null
        );
        if ($err) {
            throw new HttpResponseException(response()->json(['success' => false, 'message' => $err], 422));
        }

        return [[
            'time_start' => $single['time_start'],
            'time_end' => $single['time_end'],
            'break_start' => $single['break_start'] ?? null,
            'break_end' => $single['break_end'] ?? null,
        ]];
    }

    public function store(Request $request)
    {
        $doctor = $this->doctorForUser($request->user());
        if (!$doctor) {
            return response()->json(['success' => false, 'message' => 'Doctor profile not found'], 404);
        }

        $slotDefs = $this->validateAndParseSlots($request);
        $replaceExisting = (bool) $request->boolean('replace_existing', false);

        $dates = $request->input('dates');
        if (is_array($dates) && count($dates) > 0) {
            $validatedDates = $request->validate([
                'dates' => ['required', 'array', 'min:1', 'max:180'],
                'dates.*' => ['required', 'date', 'after_or_equal:today'],
            ]);
            $count = $this->calendarService->createSlotsForDates(
                $doctor,
                $validatedDates['dates'],
                $slotDefs,
                $replaceExisting
            );

            return response()->json([
                'success' => true,
                'message' => 'Saved '.$count.' slot(s) on selected date(s).',
                'slots_saved' => $count,
            ]);
        }

        $bulk = $request->input('bulk');
        if (in_array($bulk, ['month', 'year'], true)) {
            $data = $request->validate([
                'bulk' => ['required', Rule::in(['month', 'year'])],
                'year' => ['required', 'integer', 'min:2020', 'max:2100'],
                'month' => ['required_if:bulk,month', 'nullable', 'integer', 'min:1', 'max:12'],
                'weekdays_only' => ['sometimes', 'boolean'],
            ]);
            $weekdaysOnly = (bool) ($data['weekdays_only'] ?? false);
            if ($data['bulk'] === 'month') {
                $dates = $this->calendarService->datesForMonth((int) $data['year'], (int) $data['month'], $weekdaysOnly);
            } else {
                $dates = $this->calendarService->datesForYear((int) $data['year'], $weekdaysOnly);
            }
            $count = $this->calendarService->createSlotsForDates(
                $doctor,
                $dates,
                $slotDefs,
                $replaceExisting
            );

            return response()->json([
                'success' => true,
                'message' => 'Saved '.$count.' slot(s) for bulk selection.',
                'slots_saved' => $count,
            ]);
        }

        $data = $request->validate([
            'slot_date' => ['required', 'date', 'after_or_equal:today'],
        ]);
        $count = $this->calendarService->createSlotsForDates(
            $doctor,
            [$data['slot_date']],
            $slotDefs,
            $replaceExisting
        );

        return response()->json([
            'success' => true,
            'message' => 'Saved '.$count.' slot(s) for '.$data['slot_date'].'.',
            'slots_saved' => $count,
        ]);
    }

    public function destroy(Request $request, int $slot)
    {
        $doctor = $this->doctorForUser($request->user());
        if (!$doctor) {
            return response()->json(['success' => false, 'message' => 'Doctor profile not found'], 404);
        }
        if (!$this->calendarService->deleteSlot($doctor, $slot)) {
            return response()->json(['success' => false, 'message' => 'Slot not found'], 404);
        }

        return response()->json(['success' => true, 'message' => 'Removed.']);
    }

    public function destroyByDate(Request $request)
    {
        $doctor = $this->doctorForUser($request->user());
        if (!$doctor) {
            return response()->json(['success' => false, 'message' => 'Doctor profile not found'], 404);
        }
        $data = $request->validate([
            'slot_date' => ['required', 'date'],
        ]);
        $this->calendarService->deleteByDate($doctor, $data['slot_date']);

        return response()->json(['success' => true, 'message' => 'Removed.']);
    }
}
