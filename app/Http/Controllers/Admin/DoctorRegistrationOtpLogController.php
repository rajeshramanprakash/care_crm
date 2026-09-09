<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;

/** @deprecated Use RegistrationOtpLogController — kept for route compatibility. */
class DoctorRegistrationOtpLogController extends RegistrationOtpLogController
{
    public function index(Request $request)
    {
        return parent::index($request, 'doctor');
    }
}
