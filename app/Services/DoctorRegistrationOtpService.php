<?php

namespace App\Services;

/** @deprecated Use RegistrationOtpService::for('doctor') — kept for backward compatibility. */
class DoctorRegistrationOtpService extends RegistrationOtpService
{
    public const CACHE_PREFIX = 'doctor_reg_otp_';

    public const VERIFIED_PREFIX = 'doctor_reg_mobile_verified_';

    public function __construct()
    {
        parent::__construct('doctor');
    }
}
