<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LocationDoctorConsultationPrice extends Model
{
    public const MODES = ['online', 'home_visit', 'clinic_visit'];

    public const MODE_LABELS = [
        'online' => 'Online',
        'home_visit' => 'Home Visit',
        'clinic_visit' => 'Clinic',
    ];

    protected $fillable = [
        'location_id',
        'doctor_consultation_service_id',
        'doctor_consultation_service_sub_service_id',
        'consultation_mode',
        'website_price',
        'doctor_max_price',
        'is_active',
    ];

    protected $casts = [
        'website_price' => 'decimal:2',
        'doctor_max_price' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function consultationService(): BelongsTo
    {
        return $this->belongsTo(DoctorConsultationService::class, 'doctor_consultation_service_id');
    }

    public function subService(): BelongsTo
    {
        return $this->belongsTo(DoctorConsultationServiceSubService::class, 'doctor_consultation_service_sub_service_id');
    }

    public static function modeLabel(string $mode): string
    {
        return self::MODE_LABELS[$mode] ?? ucfirst(str_replace('_', ' ', $mode));
    }
}
