<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DoctorRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminWebsiteVerifiedDoctorController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $limit = (int) $request->query('limit', 2000);
        $limit = max(1, min(5000, $limit));

        $doctors = DoctorRequest::query()
            ->whereRaw('LOWER(TRIM(approval_status)) = ?', ['approved'])
            ->orderByDesc('reviewed_at')
            ->orderByDesc('id')
            ->limit($limit)
            ->get([
                'id',
                'lead_id',
                'name',
                'mobile',
                'contact_no',
                'job_title',
                'city',
                'profile_image',
                'approval_status',
                'reviewed_at',
                'created_at',
            ]);

        return response()->json([
            'success' => true,
            'doctors' => $doctors->map(function (DoctorRequest $d) {
                return [
                    'id' => $d->id,
                    'lead_id' => $d->lead_id,
                    'name' => $d->name,
                    'mobile' => $d->mobile,
                    'contact_no' => $d->contact_no,
                    'job_title' => $d->job_title,
                    'city' => $d->city,
                    'profile_image' => $d->profile_image,
                    'approval_status' => $d->approval_status,
                    'reviewed_at' => $d->reviewed_at?->toIso8601String(),
                    'created_at' => $d->created_at?->toIso8601String(),
                ];
            })->values(),
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $doctor = DoctorRequest::query()
            ->with(['reviewer:id,f_name,l_name'])
            ->where('id', $id)
            ->whereRaw('LOWER(TRIM(approval_status)) = ?', ['approved'])
            ->first();

        if (! $doctor) {
            return response()->json([
                'success' => false,
                'message' => 'Doctor not found or not approved.',
            ], 404);
        }

        $reviewerName = '—';
        if ($doctor->relationLoaded('reviewer') && $doctor->reviewer) {
            $reviewerName = trim((string) (($doctor->reviewer->f_name ?? '').' '.($doctor->reviewer->l_name ?? ''))) ?: '—';
        }

        return response()->json([
            'success' => true,
            'doctor' => $doctor,
            'reviewed_by_name' => $reviewerName,
        ]);
    }

    public function updateWebsiteCard(Request $request, int $id): JsonResponse
    {
        $doctor = DoctorRequest::query()
            ->where('id', $id)
            ->whereRaw('LOWER(TRIM(approval_status)) = ?', ['approved'])
            ->first();

        if (! $doctor) {
            return response()->json([
                'success' => false,
                'message' => 'Doctor not found or not approved.',
            ], 404);
        }

        $data = $request->validate([
            'website_card_rating' => 'nullable|string|max:50',
            'website_card_attend' => 'nullable|string|max:120',
            'website_card_qualification' => 'nullable|string|max:160',
            'website_card_experience' => 'nullable|string|max:600',
            'website_card_modal_description' => 'nullable|string|max:20000',
            'website_card_book_url' => 'nullable|string|max:255',
        ]);

        $doctor->website_card_rating = trim((string) ($data['website_card_rating'] ?? ''));
        $doctor->website_card_attend = trim((string) ($data['website_card_attend'] ?? ''));
        $doctor->website_card_qualification = trim((string) ($data['website_card_qualification'] ?? ''));
        $doctor->website_card_experience = trim((string) ($data['website_card_experience'] ?? ''));
        if (array_key_exists('website_card_modal_description', $data)) {
            $md = trim((string) ($data['website_card_modal_description'] ?? ''));
            $doctor->website_card_modal_description = $md === '' ? null : $md;
        }
        $doctor->website_card_book_url = trim((string) ($data['website_card_book_url'] ?? ''));

        $doctor->save();

        return response()->json([
            'success' => true,
            'message' => 'Website card details saved.',
        ]);
    }
}

