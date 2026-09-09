<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DoctorConsultationServiceSubService extends Model
{
    protected $table = 'doctor_consultation_service_sub_services';

    protected $fillable = [
        'doctor_consultation_service_id',
        'name',
        'icon_path',
        'consultation_duration_minutes',
        'sort_order',
        'specialization_options',
        'is_active',
    ];

    protected $casts = [
        'consultation_duration_minutes' => 'integer',
        'sort_order' => 'integer',
        'is_active' => 'boolean',
        'specialization_options' => 'array',
    ];

    public function consultationService(): BelongsTo
    {
        return $this->belongsTo(DoctorConsultationService::class, 'doctor_consultation_service_id');
    }
}
