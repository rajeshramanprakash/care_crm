<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JobRequestServicePrice extends Model
{
    protected $fillable = [
        'job_request_id',
        'service_id',
        'service_sub_service_id',
        'price_12hr',
        'price_24hr',
        'price_onetime',
    ];

    protected $casts = [
        'service_sub_service_id' => 'integer',
        'price_12hr' => 'decimal:2',
        'price_24hr' => 'decimal:2',
        'price_onetime' => 'decimal:2',
    ];

    public function jobRequest(): BelongsTo
    {
        return $this->belongsTo(JobRequest::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }
}
