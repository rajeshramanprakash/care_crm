<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Service extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'icon_path',
        'consultation_duration_minutes',
        'sort_order',
        'specialization_options',
        'is_active',
    ];

    protected $casts = [
        'specialization_options' => 'array',
        'consultation_duration_minutes' => 'integer',
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    public function locations()
    {
        return $this->belongsToMany(Location::class, 'location_services')
            ->withPivot(['price_12hr', 'price_24hr', 'price_onetime', 'provider_type', 'service_sub_service_id'])
            ->withTimestamps();
    }

    public function subServices(): HasMany
    {
        return $this->hasMany(ServiceSubService::class)->orderBy('sort_order')->orderBy('name');
    }
}
