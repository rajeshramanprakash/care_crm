<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Hash;

class BrokerUser extends Model
{
    protected $table = 'broker_users';

    protected $guarded = [];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'company_documents' => 'array',
    ];

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

    public function corporateUsers(): HasMany
    {
        return $this->hasMany(CorporateUser::class, 'broker_user_id')
            ->where('owner_type', 'broker');
    }
}
