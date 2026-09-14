<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BulkPricingRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'pricing_type',
        'service_id',
        'sub_service_id',
        'mode_type',
        'change_type',
        'value',
        'apply_to',
        'apply_from_date',
        'apply_to_date',
        'city_filter',
        'selected_cities',
        'time_period',
        'time_period_start_date',
        'time_period_end_date',
        'status',
    ];

    protected $casts = [
        'selected_cities' => 'array',
    ];

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function subService()
    {
        return $this->belongsTo(ServiceSubService::class);
    }

    public function doctorService()
    {
        return $this->belongsTo(DoctorConsultationService::class, 'service_id');
    }

    public function doctorSubService()
    {
        return $this->belongsTo(DoctorConsultationServiceSubService::class, 'sub_service_id');
    }
}
