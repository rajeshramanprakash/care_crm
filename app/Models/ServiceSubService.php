<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceSubService extends Model
{
    protected $fillable = [
        'service_id',
        'name',
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

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }
}
