<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\ResolvesDoctorForApiUser;
use App\Http\Controllers\Controller;
use App\Models\ConsultationWebsiteBooking;
use App\Models\DoctorRequest;
use App\Services\DoctorConsultationPriceChangeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DoctorPortalApiController extends Controller
{
    use ResolvesDoctorForApiUser;

    private function absolutePublicFileUrl(?string $path): ?string
    {
        if ($path === null || $path === '') {
            return null;
        }
        $path = str_replace('\\', '/', trim((string) $path));
        if (preg_match('#^https?://#i', $path)) {
            return $path;
        }
        $path = ltrim($path, '/');
        $relative = str_starts_with($path, 'storage/') ? '/'.$path : '/storage/'.$path;
        $host = rtrim((string) request()->getSchemeAndHttpHost(), '/');
        if ($host === '') {
            $host = rtrim((string) config('app.url', ''), '/');
        }

        return $host.$relative;
    }

    private function doctorPayload(DoctorRequest $doctor): array
    {
        $doctor->load([
            'reviewer:id,f_name,l_name',
            'priceLogs' => function ($q) {
                $q->with('updatedByUser:id,f_name,l_name')->orderByDesc('created_at')->limit(50);
            },
            'priceChangeRequests' => function ($q) {
                $q->orderByDesc('created_at')->limit(200);
            },
        ]);
        $arr = $doctor->toArray();
        $arr['profile_image_url'] = $this->absolutePublicFileUrl($doctor->profile_image);
        foreach (['aadhar_card', 'pan_card', 'qualification_certificate', 'bank_document'] as $field) {
            $arr[$field.'_url'] = $this->absolutePublicFileUrl($doctor->{$field});
        }

        return $arr;
    }

    public function dashboard(Request $request)
    {
        $doctor = $this->doctorForUser($request->user());
        if (!$doctor) {
            return response()->json(['success' => false, 'message' => 'Doctor profile not found'], 404);
        }

        return response()->json([
            'success' => true,
            'doctor_name' => $doctor->name ?? 'Doctor',
            'doctor' => [
                'id' => $doctor->id,
                'lead_id' => $doctor->lead_id,
                'mobile' => $doctor->mobile ?? $doctor->contact_no,
                'approval_status' => $doctor->approval_status,
                'profile_image' => $doctor->profile_image,
                'profile_image_url' => $this->absolutePublicFileUrl($doctor->profile_image),
                'job_title' => $doctor->job_title,
            ],
        ]);
    }

    public function profile(Request $request)
    {
        $doctor = $this->doctorForUser($request->user());
        if (!$doctor) {
            return response()->json(['success' => false, 'message' => 'Doctor profile not found'], 404);
        }

        return response()->json([
            'success' => true,
            'doctor' => $this->doctorPayload($doctor),
        ]);
    }

    public function bankDetails(Request $request)
    {
        $doctor = $this->doctorForUser($request->user());
        if (!$doctor) {
            return response()->json(['success' => false, 'message' => 'Doctor profile not found'], 404);
        }

        return response()->json([
            'success' => true,
            'doctor' => [
                'account_name' => $doctor->account_name,
                'bank_name' => $doctor->bank_name,
                'account_number' => $doctor->account_number,
                'ifsc_code' => $doctor->ifsc_code,
                'upi_id' => $doctor->upi_id,
                'bank_document' => $doctor->bank_document,
                'bank_document_url' => $this->absolutePublicFileUrl($doctor->bank_document),
            ],
        ]);
    }

    public function updateProfileImage(Request $request)
    {
        $doctor = $this->doctorForUser($request->user());
        if (!$doctor) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }
        if (!$request->hasFile('profile_image')) {
            return response()->json(['success' => false, 'message' => 'No image file found']);
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
            'message' => 'Photo uploaded. Please wait for admin approval.',
            'profile_image_url' => $this->absolutePublicFileUrl($displayPath ?: $path),
            'pending_approval' => true,
        ]);
    }

    public function uploadDocument(Request $request)
    {
        $doctor = $this->doctorForUser($request->user());
        if (!$doctor) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }
        $request->validate([
            'document' => 'required|file|mimes:jpeg,jpg,png,pdf|max:10240',
            'document_type' => 'required|in:aadhar_card,pan_card,qualification_certificate',
        ]);

        $file = $request->file('document');
        $documentType = $request->document_type;
        $filename = time().'_'.uniqid().'.'.$file->getClientOriginalExtension();
        $file->storeAs('public/documents/doctors', $filename);
        if ($doctor->$documentType) {
            Storage::disk('public')->delete($doctor->$documentType);
        }
        $doctor->$documentType = 'documents/doctors/'.$filename;
        $doctor->save();

        return response()->json([
            'success' => true,
            'message' => 'Document uploaded successfully',
            'document_url' => $this->absolutePublicFileUrl($doctor->$documentType),
        ]);
    }

    public function uploadBankDocument(Request $request)
    {
        $doctor = $this->doctorForUser($request->user());
        if (!$doctor) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }
        $request->validate([
            'document' => 'required|file|mimes:jpeg,jpg,png,pdf|max:10240',
        ]);

        $file = $request->file('document');
        $filename = time().'_'.uniqid().'.'.$file->getClientOriginalExtension();
        $file->storeAs('public/documents/doctors', $filename);
        if ($doctor->bank_document) {
            Storage::disk('public')->delete($doctor->bank_document);
        }
        $doctor->bank_document = 'documents/doctors/'.$filename;
        $doctor->save();

        return response()->json([
            'success' => true,
            'message' => 'Bank document uploaded successfully',
            'document_url' => $this->absolutePublicFileUrl($doctor->bank_document),
        ]);
    }

    public function updatePersonalDetails(Request $request)
    {
        $doctor = $this->doctorForUser($request->user());
        if (!$doctor) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
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
                $fieldValue = $fieldValue.'|'.($parts[1] ?? '');
            } elseif ($request->filled('gender')) {
                $fieldValue = $fieldValue.'|'.$request->gender;
            }
        }
        $doctor->$fieldName = $fieldValue;
        $doctor->save();

        return response()->json([
            'success' => true,
            'message' => ucfirst(str_replace('_', ' ', $fieldName)).' updated successfully',
            'field_value' => $fieldValue,
        ]);
    }

    public function updateBankDetails(Request $request)
    {
        $doctor = $this->doctorForUser($request->user());
        if (!$doctor) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
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
            'message' => ucfirst(str_replace('_', ' ', $fieldName)).' updated successfully',
            'field_value' => $fieldValue,
        ]);
    }

    public function bookings(Request $request)
    {
        $doctor = $this->doctorForUser($request->user());
        if (!$doctor) {
            return response()->json(['success' => false, 'message' => 'Doctor profile not found'], 404);
        }

        $rows = ConsultationWebsiteBooking::query()
            ->where('doctor_request_id', $doctor->id)
            ->paidForDoctorPortal()
            ->with('consultationService:id,name,consultation_duration_minutes')
            ->orderByDesc('created_at')
            ->limit(200)
            ->get();

        $bookings = $rows->map(function (ConsultationWebsiteBooking $b) {
            return [
                'id' => $b->id,
                'customer_name' => $b->customer_name,
                'customer_address' => $b->customer_address,
                'customer_city' => $b->customer_city,
                'consultation_mode' => $b->consultation_mode,
                'consultation_mode_label' => ConsultationWebsiteBooking::modeLabel($b->consultation_mode),
                'consultation_duration_minutes' => $b->consultation_duration_minutes
                    ?? $b->consultationService?->consultation_duration_minutes,
                'consultation_duration_label' => ConsultationWebsiteBooking::durationLabel(
                    $b->consultation_duration_minutes ?? $b->consultationService?->consultation_duration_minutes
                ),
                'appointment_date' => optional($b->appointment_date)->format('Y-m-d'),
                'appointment_date_display' => optional($b->appointment_date)->format('d M Y'),
                'appointment_start_time' => $b->appointment_start_time,
                'appointment_end_time' => $b->appointment_end_time,
                'appointment_time_range' => ConsultationWebsiteBooking::timeRangeLabel(
                    $b->appointment_start_time,
                    $b->appointment_end_time
                ),
                'online_meeting_link' => $b->online_meeting_link,
                'online_meeting_provider' => $b->online_meeting_provider,
                'online_meeting_starts_at' => optional($b->online_meeting_starts_at)->toIso8601String(),
                'online_meeting_ends_at' => optional($b->online_meeting_ends_at)->toIso8601String(),
                'consultation_service_name' => $b->consultationService?->name,
                'submitted_at' => optional($b->created_at)->toIso8601String(),
                'submitted_at_display' => optional($b->created_at)->format('d M Y, H:i'),
            ];
        });

        return response()->json([
            'success' => true,
            'bookings' => $bookings,
        ]);
    }

    public function submitPriceChangeRequest(Request $request)
    {
        $doctor = $this->doctorForUser($request->user());
        if (!$doctor) {
            return response()->json(['success' => false, 'message' => 'Doctor profile not found'], 404);
        }

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
}
