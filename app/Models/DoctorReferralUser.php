<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DoctorReferralUser extends Model
{
    protected $table = 'doctor_referral_users';

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
        'commission_percent' => 'decimal:2',
    ];

    public function doctorRequests(): HasMany
    {
        return $this->hasMany(DoctorRequest::class, 'doctor_referral_user_id');
    }
}
