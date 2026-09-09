<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DoctorConsultationService extends Model
{
    protected $table = 'doctor_consultation_services';

    protected $fillable = [
        'name',
        'category',
        'icon_path',
        'specialization_options',
        'consultation_duration_minutes',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'consultation_duration_minutes' => 'integer',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
        'specialization_options' => 'array',
    ];

    public function subServices(): HasMany
    {
        return $this->hasMany(DoctorConsultationServiceSubService::class, 'doctor_consultation_service_id')
            ->orderBy('sort_order')
            ->orderBy('name');
    }
}
