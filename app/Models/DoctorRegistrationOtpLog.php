<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DoctorRegistrationOtpLog extends Model
{
    public const TYPE_DOCTOR = 'doctor';

    public const TYPE_VENDOR = 'vendor';

    public const TYPE_FREELANCER = 'freelancer';

    protected $fillable = [
        'registration_type',
        'mobile',
        'otp',
        'sent_at',
        'sent_ip',
        'verified_at',
        'verified_ip',
        'verify_attempts',
        'last_verify_attempt_at',
        'last_verify_attempt_ip',
        'sms_provider',
        'msg91_request_id',
        'msg91_delivery_status',
        'msg91_last_error',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'verified_at' => 'datetime',
        'last_verify_attempt_at' => 'datetime',
        'verify_attempts' => 'integer',
    ];

    public function isVerified(): bool
    {
        return $this->verified_at !== null;
    }
}
