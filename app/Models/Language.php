<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Language extends Model
{
    protected $fillable = [
        'name',
        'code',
        'native_name',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function registrationTranslations(): HasMany
    {
        return $this->hasMany(RegistrationTranslation::class);
    }

    public function displayLabel(): string
    {
        if ($this->native_name && $this->native_name !== $this->name) {
            return $this->name.' ('.$this->native_name.')';
        }

        return (string) $this->name;
    }
}
