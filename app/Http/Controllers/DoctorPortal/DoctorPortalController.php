<?php

namespace App\Http\Controllers\DoctorPortal;

use App\Http\Controllers\Controller;
use App\Models\DoctorCalendarAvailabilitySlot;
use App\Models\ConsultationWebsiteBooking;
use App\Models\DoctorConsultationService;
use App\Models\DoctorPortalLead;
use App\Models\DoctorRequest;
use App\Models\Service;
use App\Services\DoctorCalendarAvailabilityService;
use App\Services\DoctorConsultationPriceChangeService;
use App\Services\DoctorPortalLeadService;
use Illuminate\Http\Request;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;

class DoctorPortalController extends Controller
{
    private function currentDoctor(): ?DoctorRequest
    {
        $id = Session::get('doctor_reg_id');
        if (!$id) {
            return null;
        }
        return DoctorRequest::find($id);
    }

    private function requireDoctor()
    {
        $doctor = $this->currentDoctor();
        if (!$doctor) {
            Session::forget(['doctor_reg_id', 'doctor_reg_name', 'login_type']);
            return redirect()->route('home')->with('error', 'Please login to access your doctor account.');
        }
        if ($doctor->approval_status !== 'approved') {
            return redirect()->route('home')->with('error', 'Your doctor account is not active.');
        }
        return null;
    }

    public function dashboard()
    {
        if ($r = $this->requireDoctor()) {
            return $r;
        }
        $doctor = $this->currentDoctor();
        $page_heading = 'Doctor Dashboard';
        $doctorName = $doctor->name ?? 'N/A';
        $portalLeadCommissionPercent = $doctor->portalLeadCommissionPercent();

        return view('doctor_carelix.dashboard', compact(
            'page_heading',
            'doctor',
            'doctorName',
            'portalLeadCommissionPercent'
        ));
    }

    public function personalDetails()
    {
        if ($r = $this->requireDoctor()) {
            return $r;
        }
        $doctor = $this->currentDoctor();
        $doctor->load([
            'reviewer:id,f_name,l_name',
            'priceLogs' => function ($q) {
                $q->with('updatedByUser:id,f_name,l_name')->orderByDesc('created_at')->limit(50);
            },
            'priceChangeRequests' => function ($q) {
                $q->orderByDesc('created_at')->limit(200);
            },
        ]);
        $page_heading = 'Personal Details';
        $doctorName = $doctor->name ?? 'N/A';

        return view('doctor_carelix.personal-details', compact('page_heading', 'doctor', 'doctorName'));
    }

    public function bankDetails()
    {
        if ($r = $this->requireDoctor()) {
            return $r;
        }
        $doctor = $this->currentDoctor();
        $page_heading = 'Bank Account Details';
        $doctorName = $doctor->name ?? 'N/A';

        return view('doctor_carelix.bank-details', compact('page_heading', 'doctor', 'doctorName'));
    }

    public function calendarAvailability()
    {
        if ($r = $this->requireDoctor()) {
            return $r;
        }
        $doctor = $this->currentDoctor();
        $page_heading = 'Calendar availability';
        $doctorName = $doctor->name ?? 'N/A';

        return view('doctor_carelix.calendar-availability', compact('page_heading', 'doctor', 'doctorName'));
    }

    public function calendarAvailabilityData(Request $request)
    {
        if (!Session::get('doctor_reg_id')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }
        $doctor = DoctorRequest::find(Session::get('doctor_reg_id'));
        if (!$doctor || $doctor->approval_status !== 'approved') {
            return response()->json(['success' => false, 'message' => 'Doctor not found'], 404);
        }
        $request->validate([
            'from' => ['required', 'date'],
            'to' => ['required', 'date', 'after_or_equal:from'],
        ]);
        /** @var DoctorCalendarAvailabilityService $svc */
        $svc = app(DoctorCalendarAvailabilityService::class);
        $slots = $svc->listForDoctor($doctor, $request->input('from'), $request->input('to'));

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

    public function calendarAvailabilitySave(Request $request)
    {
        if (!Session::get('doctor_reg_id')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }
        $doctor = DoctorRequest::find(Session::get('doctor_reg_id'));
        if (!$doctor || $doctor->approval_status !== 'approved') {
            return response()->json(['success' => false, 'message' => 'Doctor not found'], 404);
        }
        /** @var DoctorCalendarAvailabilityService $svc */
        $svc = app(DoctorCalendarAvailabilityService::class);

        $slotDefsInput = $request->input('slots');
        if (is_array($slotDefsInput) && count($slotDefsInput) > 0) {
            $validatedSlotData = $request->validate([
                'slots' => ['required', 'array', 'min:1', 'max:20'],
                'slots.*.time_start' => ['required', 'string', 'size:5'],
                'slots.*.time_end' => ['required', 'string', 'size:5'],
                'slots.*.break_start' => ['nullable', 'string', 'size:5'],
                'slots.*.break_end' => ['nullable', 'string', 'size:5'],
            ]);
            $slotDefs = [];
            foreach ($validatedSlotData['slots'] as $slotDef) {
                $err = $svc->validateTimes(
                    $slotDef['time_start'],
                    $slotDef['time_end'],
                    $slotDef['break_start'] ?? null,
                    $slotDef['break_end'] ?? null
                );
                if ($err) {
                    throw new HttpResponseException(response()->json(['success' => false, 'message' => $err], 422));
                }
                $slotDefs[] = [
                    'time_start' => $slotDef['time_start'],
                    'time_end' => $slotDef['time_end'],
                    'break_start' => $slotDef['break_start'] ?? null,
                    'break_end' => $slotDef['break_end'] ?? null,
                ];
            }
        } else {
            $slotData = $request->validate([
                'time_start' => ['required', 'string', 'size:5'],
                'time_end' => ['required', 'string', 'size:5'],
                'break_start' => ['nullable', 'string', 'size:5'],
                'break_end' => ['nullable', 'string', 'size:5'],
            ]);
            $err = $svc->validateTimes(
                $slotData['time_start'],
                $slotData['time_end'],
                $slotData['break_start'] ?? null,
                $slotData['break_end'] ?? null
            );
            if ($err) {
                return response()->json(['success' => false, 'message' => $err], 422);
            }
            $slotDefs = [[
                'time_start' => $slotData['time_start'],
                'time_end' => $slotData['time_end'],
                'break_start' => $slotData['break_start'] ?? null,
                'break_end' => $slotData['break_end'] ?? null,
            ]];
        }
        $replaceExisting = (bool) $request->boolean('replace_existing', false);

        $dates = $request->input('dates');
        if (is_array($dates) && count($dates) > 0) {
            $validatedDates = $request->validate([
                'dates' => ['required', 'array', 'min:1', 'max:180'],
                'dates.*' => ['required', 'date', 'after_or_equal:today'],
            ]);
            $count = $svc->createSlotsForDates($doctor, $validatedDates['dates'], $slotDefs, $replaceExisting);

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
                $dates = $svc->datesForMonth((int) $data['year'], (int) $data['month'], $weekdaysOnly);
            } else {
                $dates = $svc->datesForYear((int) $data['year'], $weekdaysOnly);
            }
            $count = $svc->createSlotsForDates($doctor, $dates, $slotDefs, $replaceExisting);

            return response()->json([
                'success' => true,
                'message' => 'Saved '.$count.' slot(s) for bulk selection.',
                'slots_saved' => $count,
            ]);
        }

        $data = $request->validate([
            'slot_date' => ['required', 'date', 'after_or_equal:today'],
        ]);
        $count = $svc->createSlotsForDates($doctor, [$data['slot_date']], $slotDefs, $replaceExisting);

        return response()->json([
            'success' => true,
            'message' => 'Saved '.$count.' slot(s) for '.$data['slot_date'].'.',
            'slots_saved' => $count,
        ]);
    }

    public function calendarAvailabilityDelete(Request $request, int $slot)
    {
        if (!Session::get('doctor_reg_id')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }
        $doctor = DoctorRequest::find(Session::get('doctor_reg_id'));
        if (!$doctor || $doctor->approval_status !== 'approved') {
            return response()->json(['success' => false, 'message' => 'Doctor not found'], 404);
        }
        $svc = app(DoctorCalendarAvailabilityService::class);
        if (!$svc->deleteSlot($doctor, $slot)) {
            return response()->json(['success' => false, 'message' => 'Slot not found'], 404);
        }

        return response()->json(['success' => true, 'message' => 'Removed.']);
    }

    public function calendarAvailabilityDeleteByDate(Request $request)
    {
        if (!Session::get('doctor_reg_id')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }
        $doctor = DoctorRequest::find(Session::get('doctor_reg_id'));
        if (!$doctor || $doctor->approval_status !== 'approved') {
            return response()->json(['success' => false, 'message' => 'Doctor not found'], 404);
        }
        $data = $request->validate([
            'slot_date' => ['required', 'date'],
        ]);
        app(DoctorCalendarAvailabilityService::class)->deleteByDate($doctor, $data['slot_date']);

        return response()->json(['success' => true, 'message' => 'Removed.']);
    }

    public function bookings()
    {
        if ($r = $this->requireDoctor()) {
            return $r;
        }
        $doctor = $this->currentDoctor();
        $doctor->load([
            'consultationWebsiteBookings' => function ($q) {
                $q->paidForDoctorPortal()
                    ->with('consultationService:id,name,consultation_duration_minutes')
                    ->orderByDesc('created_at')
                    ->limit(200);
            },
        ]);
        $page_heading = 'Website bookings';
        $doctorName = $doctor->name ?? 'N/A';

        return view('doctor_carelix.bookings', compact('page_heading', 'doctor', 'doctorName'));
    }

    public function joinBookingMeeting(int $bookingId)
    {
        if ($r = $this->requireDoctor()) {
            return $r;
        }
        $doctor = $this->currentDoctor();

        $booking = ConsultationWebsiteBooking::query()
            ->whereKey($bookingId)
            ->where('doctor_request_id', (int) $doctor->id)
            ->paidForDoctorPortal()
            ->first();

        if (! $booking) {
            return back()->with('error', 'Booking not found for this doctor.');
        }
        if (($booking->consultation_mode ?? '') !== 'online' || empty($booking->online_meeting_link)) {
            return back()->with('error', 'Online meeting link is not available for this booking yet.');
        }

        $join = ConsultationWebsiteBooking::meetingJoinStatus($booking);
        if (! $join['allowed']) {
            return back()->with('error', $join['reason'] ?: 'Meeting is not open right now.');
        }

        return redirect()->away($booking->online_meeting_link);
    }

    public function updateProfileImage(Request $request)
    {
        if (!Session::get('doctor_reg_id')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }
        if (!$request->hasFile('profile_image')) {
            return response()->json(['success' => false, 'message' => 'No image file found']);
        }
        $doctor = DoctorRequest::find(Session::get('doctor_reg_id'));
        if (!$doctor || $doctor->approval_status !== 'approved') {
            return response()->json(['success' => false, 'message' => 'Doctor not found'], 404);
        }

        $path = $request->file('profile_image')->store('documents/doctors/selfies', 'public');

        if ($doctor->profile_image_upload && $doctor->profile_image_upload !== $doctor->profile_image) {
            Storage::disk('public')->delete($doctor->profile_image_upload);
        }
        if ($doctor->profile_image_pending) {
            Storage::disk('public')->delete($doctor->profile_image_pending);
        }

        $doctor->profile_image_upload = $path;
        $doctor->profile_image_pending = null;
        $doctor->profile_image_status = 'pending_review';
        $doctor->save();

        $displayPath = $doctor->profile_image_status === 'approved' ? $doctor->profile_image : null;

        return response()->json([
            'success' => true,
            'message' => 'Photo uploaded. Please wait for admin approval — your profile will update after the Carelix coat preview is approved.',
            'profile_image_url' => $displayPath ? asset('storage/'.$displayPath) : asset('storage/'.$path),
            'pending_approval' => true,
        ]);
    }

    public function uploadDocument(Request $request)
    {
        if (!Session::get('doctor_reg_id')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }
        $request->validate([
            'document' => 'required|file|mimes:jpeg,jpg,png,pdf|max:10240',
            'document_type' => 'required|in:aadhar_card,pan_card,qualification_certificate',
        ]);
        $doctor = DoctorRequest::find(Session::get('doctor_reg_id'));
        if (!$doctor || $doctor->approval_status !== 'approved') {
            return response()->json(['success' => false, 'message' => 'Doctor not found']);
        }
        $file = $request->file('document');
        $documentType = $request->document_type;
        $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
        $file->storeAs('public/documents/doctors', $filename);
        if ($doctor->$documentType) {
            Storage::disk('public')->delete($doctor->$documentType);
        }
        $doctor->$documentType = 'documents/doctors/' . $filename;
        $doctor->save();

        return response()->json([
            'success' => true,
            'message' => 'Document uploaded successfully',
            'document_url' => asset('storage/documents/doctors/' . $filename),
            'document_name' => ucfirst(str_replace('_', ' ', $documentType)),
        ]);
    }

    public function uploadBankDocument(Request $request)
    {
        if (!Session::get('doctor_reg_id')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }
        $request->validate([
            'document' => 'required|file|mimes:jpeg,jpg,png,pdf|max:10240',
        ]);
        $doctor = DoctorRequest::find(Session::get('doctor_reg_id'));
        if (!$doctor || $doctor->approval_status !== 'approved') {
            return response()->json(['success' => false, 'message' => 'Doctor not found']);
        }
        $file = $request->file('document');
        $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
        $file->storeAs('public/documents/doctors', $filename);
        if ($doctor->bank_document) {
            Storage::disk('public')->delete($doctor->bank_document);
        }
        $doctor->bank_document = 'documents/doctors/' . $filename;
        $doctor->save();

        return response()->json([
            'success' => true,
            'message' => 'Bank document uploaded successfully',
            'document_url' => asset('storage/documents/doctors/' . $filename),
        ]);
    }

    public function updatePersonalDetails(Request $request)
    {
        if (!Session::get('doctor_reg_id')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }
        $doctor = DoctorRequest::find(Session::get('doctor_reg_id'));
        if (!$doctor || $doctor->approval_status !== 'approved') {
            return response()->json(['success' => false, 'message' => 'Doctor not found']);
        }
        $request->validate([
            'field_name' => 'required|string',
            'field_value' => 'nullable|string|max:255',
        ]);
        $fieldName = $request->field_name;
        $fieldValue = $request->field_value;

        if ($fieldName === 'email') {
            $request->validate([
                'field_value' => 'required|email|max:255',
            ]);
            $doctor->email = strtolower(trim((string) $request->field_value));
            $doctor->save();

            return response()->json([
                'success' => true,
                'message' => 'Email updated successfully',
                'field_value' => $doctor->email,
            ]);
        }

        $allowedFields = ['name', 'customer_name', 'age', 'gender', 'contact_no', 'mobile', 'job_title', 'location', 'total_experience', 'expected_salary', 'shift'];
        if (!in_array($fieldName, $allowedFields, true)) {
            return response()->json(['success' => false, 'message' => 'Invalid field name']);
        }
        $currentValue = $doctor->$fieldName;
        if ($currentValue) {
            if ($fieldName === 'age') {
                if (strpos((string) $currentValue, '|') !== false) {
                    $agePart = explode('|', (string) $currentValue)[0];
                    if ($agePart) {
                        return response()->json(['success' => false, 'message' => 'This field is already filled and cannot be edited']);
                    }
                } elseif ($currentValue) {
                    return response()->json(['success' => false, 'message' => 'This field is already filled and cannot be edited']);
                }
            } else {
                return response()->json(['success' => false, 'message' => 'This field is already filled and cannot be edited']);
            }
        }
        if ($fieldName === 'age') {
            if ($currentValue && strpos((string) $currentValue, '|') !== false) {
                $parts = explode('|', (string) $currentValue);
                $fieldValue = $fieldValue . '|' . ($parts[1] ?? '');
            } elseif ($request->filled('gender')) {
                $fieldValue = $fieldValue . '|' . $request->gender;
            }
        }
        $doctor->$fieldName = $fieldValue;
        $doctor->save();

        return response()->json([
            'success' => true,
            'message' => ucfirst(str_replace('_', ' ', $fieldName)) . ' updated successfully',
            'field_value' => $fieldValue,
        ]);
    }

    public function updateBankDetails(Request $request)
    {
        if (!Session::get('doctor_reg_id')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }
        $doctor = DoctorRequest::find(Session::get('doctor_reg_id'));
        if (!$doctor || $doctor->approval_status !== 'approved') {
            return response()->json(['success' => false, 'message' => 'Doctor not found']);
        }
        $request->validate([
            'field_name' => 'required|string',
            'field_value' => 'nullable|string|max:255',
        ]);
        $fieldName = $request->field_name;
        $fieldValue = $request->field_value;
        $allowedFields = ['account_name', 'account_number', 'ifsc_code', 'upi_id'];
        if (!in_array($fieldName, $allowedFields, true)) {
            return response()->json(['success' => false, 'message' => 'Invalid field name']);
        }
        if ($doctor->$fieldName) {
            return response()->json(['success' => false, 'message' => 'This field is already filled and cannot be edited']);
        }
        $doctor->$fieldName = $fieldValue;
        $doctor->save();

        return response()->json([
            'success' => true,
            'message' => ucfirst(str_replace('_', ' ', $fieldName)) . ' updated successfully',
            'field_value' => $fieldValue,
        ]);
    }

    public function submitPriceChangeRequest(Request $request)
    {
        if ($r = $this->requireDoctor()) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Please login to your doctor account.'], 401);
            }

            return $r;
        }

        $doctor = $this->currentDoctor();
        $request->validate([
            'sub_service_id' => ['nullable', 'integer', 'min:0'],
            'consultation_mode' => ['required', 'string', 'in:online,home_visit,clinic_visit'],
            'requested_price' => ['required', 'numeric', 'min:0.01'],
        ]);

        try {
            $changeRequest = app(DoctorConsultationPriceChangeService::class)->submit($doctor, [
                'sub_service_id' => (int) $request->input('sub_service_id', 0),
                'consultation_mode' => (string) $request->input('consultation_mode'),
                'requested_price' => (float) $request->input('requested_price'),
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            $msg = collect($e->errors())->flatten()->first() ?: 'Could not submit price change request.';

            return response()->json(['success' => false, 'message' => $msg], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Price change request sent to admin for verification.',
            'request' => [
                'id' => $changeRequest->id,
                'status' => $changeRequest->status,
            ],
        ]);
    }

    public function referLeads()
    {
        if ($r = $this->requireDoctor()) {
            return $r;
        }

        $doctor = $this->currentDoctor();
        $page_heading = 'Refer Lead';
        $doctorName = $doctor->name ?? 'N/A';
        $items = DoctorPortalLead::query()
            ->where('doctor_request_id', $doctor->id)
            ->orderByDesc('id')
            ->get();
        $serviceOptions = $this->referLeadServiceOptions();

        return view('doctor_carelix.refer-leads', compact(
            'page_heading',
            'doctor',
            'doctorName',
            'items',
            'serviceOptions'
        ));
    }

    public function storeReferLead(Request $request, DoctorPortalLeadService $leadService)
    {
        if ($r = $this->requireDoctor()) {
            return $r;
        }

        $doctor = $this->currentDoctor();

        try {
            $leadService->createLead($doctor, $request->all(), 'manual');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()
                ->route('doctor_portal.refer-leads')
                ->withErrors($e->errors())
                ->withInput();
        }

        return redirect()
            ->route('doctor_portal.refer-leads')
            ->with('status', [
                'alert_type' => 'success',
                'message' => 'Lead submitted to sales successfully.',
            ]);
    }

    private function referLeadServiceOptions(): array
    {
        $regular = Service::query()->select('name')->orderBy('name')->pluck('name')->toArray();
        $doctor = DoctorConsultationService::query()->select('name')->orderBy('name')->pluck('name')->toArray();
        $all = array_values(array_unique(array_filter(array_merge($regular, $doctor))));
        natcasesort($all);

        return array_values($all);
    }
}
