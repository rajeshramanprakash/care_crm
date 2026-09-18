<?php

namespace App\Http\Controllers\SubAdmin;

use App\Http\Controllers\Controller;
use App\Models\DoctorRegistrationOtpLog;
use App\Services\RegistrationOtpService;
use Illuminate\Http\Request;

class SubAdminRegistrationOtpLogController extends Controller
{
    public function index(Request $request, string $registrationType)
    {
        if (! in_array($registrationType, RegistrationOtpService::TYPES, true)) {
            abort(404);
        }

        $query = DoctorRegistrationOtpLog::query()
            ->where('registration_type', $registrationType)
            ->orderByDesc('sent_at');

        $mobile = preg_replace('/\D+/', '', (string) $request->query('mobile', ''));
        if (strlen($mobile) >= 10) {
            $query->where('mobile', substr($mobile, -10));
        }

        if ($request->query('status') === 'verified') {
            $query->whereNotNull('verified_at');
        } elseif ($request->query('status') === 'pending') {
            $query->whereNull('verified_at');
        }

        $logs = $query->paginate(50)->withQueryString();

        $labels = [
            'doctor' => 'Doctor',
            'vendor' => 'Vendor',
            'freelancer' => 'Freelancer',
        ];

        return view('subadmin.registration_otp_logs.index', [
            'logs' => $logs,
            'registrationType' => $registrationType,
            'registrationLabel' => $labels[$registrationType],
            'filterMobile' => $mobile,
            'filterStatus' => (string) $request->query('status', ''),
            'indexRoute' => 'admin.'.$registrationType.'_registration_otp_logs.index',
        ]);
    }
}
