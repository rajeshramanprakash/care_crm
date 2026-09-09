<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class CorporateEmployee extends Model
{
    protected $table = 'corporate_employees';

    protected $guarded = [];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'issuance_date' => 'date',
        'last_working_date' => 'date',
        'active_from' => 'date',
        'active_to' => 'date',
        'is_active' => 'boolean',
    ];

    public function corporate(): BelongsTo
    {
        return $this->belongsTo(CorporateUser::class, 'corporate_user_id');
    }

    public function setPasswordAttribute(?string $value): void
    {
        if ($value !== null && $value !== '') {
            $this->attributes['password'] = Hash::make($value);
        }
    }

    public function checkPassword(string $plain): bool
    {
        return Hash::check($plain, (string) $this->password);
    }

    public function policyTermsUrl(): ?string
    {
        if (! $this->policy_terms_file) {
            return null;
        }

        return Storage::disk('public')->url($this->policy_terms_file);
    }

    public function employeeLoginHint(): string
    {
        $corporate = $this->relationLoaded('corporate') ? $this->corporate : $this->corporate()->first();

        return 'Corporate username: '.($corporate->username ?? '—').' | Employee ID: '.$this->employee_id;
    }
}
