<?php

namespace App\Http\Controllers\SubAdmin;

use Illuminate\Http\Request;

/** @deprecated Use RegistrationOtpLogController — kept for route compatibility. */
class SubAdminDoctorRegistrationOtpLogController extends SubAdminRegistrationOtpLogController
{
    public function index(Request $request, string $registrationType = 'doctor')
    {
        return parent::index($request, 'doctor');
    }
}
