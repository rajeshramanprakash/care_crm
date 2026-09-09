<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Hash;

class CorporateUser extends Model
{
    protected $table = 'corporate_users';

    protected $guarded = [];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function insurer(): BelongsTo
    {
        return $this->belongsTo(InsurerUser::class, 'insurer_user_id');
    }

    public function broker(): BelongsTo
    {
        return $this->belongsTo(BrokerUser::class, 'broker_user_id');
    }

    public function employees(): HasMany
    {
        return $this->hasMany(CorporateEmployee::class, 'corporate_user_id');
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

    public function loginUrl(): string
    {
        return url('/corporate/login');
    }

    public function createdByLabel(): string
    {
        if ($this->owner_type === 'insurer' && $this->insurer) {
            return 'Insurer: '.($this->insurer->company_name ?: $this->insurer->name);
        }
        if ($this->owner_type === 'broker' && $this->broker) {
            return 'Broker: '.($this->broker->company_name ?: $this->broker->name);
        }

        return ucfirst((string) $this->owner_type);
    }
}
