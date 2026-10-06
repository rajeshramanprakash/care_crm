<?php

namespace App\Http\Controllers\SubAdmin;

use App\Http\Controllers\Controller;
use App\Models\DoctorReferralUser;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SubAdminDoctorReferralUserController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'mobile' => ['required', 'string', 'regex:/^[0-9]{10}$/', Rule::unique('doctor_referral_users', 'mobile')],
        ]);

        $data['is_active'] = true;
        $data['commission_percent'] = null;
        DoctorReferralUser::create($data);

        return redirect()->route('subadmin.doctor_requests.index')->with('status', [
            'alert_type' => 'success',
            'message' => 'Doctor referral user created. They can log in with this mobile via OTP (same home login).',
        ]);
    }

    public function destroy(DoctorReferralUser $doctorReferralUser)
    {
        $doctorReferralUser->delete();

        return redirect()->route('subadmin.doctor_requests.index')->with('status', [
            'alert_type' => 'success',
            'message' => 'Doctor referral user removed. Assigned doctors are no longer linked to them.',
        ]);
    }
}
