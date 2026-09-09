<?php

namespace App\Http\Controllers\DoctorReferral;

use App\Http\Controllers\Concerns\BuildsDoctorReferralPortalData;
use App\Http\Controllers\Controller;
use App\Models\DoctorReferralUser;
use Illuminate\Http\RedirectResponse;

class DoctorReferralPortalController extends Controller
{
    use BuildsDoctorReferralPortalData;

    public function showLogin()
    {
        if (session()->has('doctor_referral_user_id')) {
            return redirect()->route('doctor_referral.dashboard');
        }

        return view('doctor_referral.login');
    }

    public function dashboard()
    {
        $refUser = $this->resolveRefUser();
        if ($refUser instanceof RedirectResponse) {
            return $refUser;
        }

        $payload = $this->doctorReferralDashboardPayload($refUser);

        return view('doctor_referral.dashboard', [
            'refUser' => $refUser,
            'referredRows' => $payload['earning_rows'],
            'assignedDoctorCount' => $payload['assigned_doctor_count'],
            'assignedDoctorCards' => $payload['assigned_doctor_cards'],
            'totalCommission' => $payload['total_commission'],
            'paidBookingsCount' => $payload['paid_bookings_count'],
        ]);
    }

    public function leads()
    {
        $refUser = $this->resolveRefUser();
        if ($refUser instanceof RedirectResponse) {
            return $refUser;
        }

        $payload = $this->doctorReferralLeadsPayload($refUser);

        return view('doctor_referral.leads', [
            'refUser' => $refUser,
            'leadRows' => $payload['lead_rows'],
            'assignedDoctorCount' => $payload['assigned_doctor_count'],
            'paidLeadsCount' => $payload['paid_leads_count'],
            'pendingLeadsCount' => $payload['pending_leads_count'],
            'totalCommission' => $payload['total_commission'],
        ]);
    }

    private function resolveRefUser(): DoctorReferralUser|RedirectResponse
    {
        $refUser = DoctorReferralUser::find(session('doctor_referral_user_id'));
        if (! $refUser || ! $refUser->is_active) {
            return redirect()->route('doctor_referral.login')->with('error', 'Session expired. Please login again.');
        }

        return $refUser;
    }
}
