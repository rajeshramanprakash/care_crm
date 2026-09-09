<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DoctorReferralUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DoctorReferralUserController extends Controller
{
    public function apiIndex(): JsonResponse
    {
        $data = DoctorReferralUser::query()
            ->withCount('doctorRequests')
            ->orderByDesc('id')
            ->get()
            ->map(static function (DoctorReferralUser $r) {
                return [
                    'id' => $r->id,
                    'name' => $r->name,
                    'mobile' => $r->mobile,
                    'doctor_requests_count' => (int) ($r->doctor_requests_count ?? 0),
                ];
            })
            ->values()
            ->all();

        return response()->json(['data' => $data]);
    }

    public function apiStore(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'mobile' => ['required', 'string', 'regex:/^[0-9]{10}$/', Rule::unique('doctor_referral_users', 'mobile')],
        ]);

        $data['is_active'] = true;
        $data['commission_percent'] = null;
        $user = DoctorReferralUser::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Doctor referral user created. They can log in with this mobile via OTP.',
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'mobile' => $user->mobile,
                'doctor_requests_count' => 0,
            ],
        ], 201);
    }

    public function apiDestroy(DoctorReferralUser $doctor_referral_user): JsonResponse
    {
        $doctor_referral_user->delete();

        return response()->json([
            'success' => true,
            'message' => 'Doctor referral user removed. Assigned doctors are no longer linked to them.',
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'mobile' => ['required', 'string', 'regex:/^[0-9]{10}$/', Rule::unique('doctor_referral_users', 'mobile')],
        ]);

        $data['is_active'] = true;
        $data['commission_percent'] = null;
        DoctorReferralUser::create($data);

        return redirect()->route('admin.doctor_requests.index')->with('status', [
            'alert_type' => 'success',
            'message' => 'Doctor referral user created. They can log in with this mobile via OTP (same home login).',
        ]);
    }

    public function destroy(DoctorReferralUser $doctorReferralUser)
    {
        $doctorReferralUser->delete();

        return redirect()->route('admin.doctor_requests.index')->with('status', [
            'alert_type' => 'success',
            'message' => 'Doctor referral user removed. Assigned doctors are no longer linked to them.',
        ]);
    }
}
