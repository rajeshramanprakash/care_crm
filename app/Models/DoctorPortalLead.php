<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DoctorPortalLead extends Model
{
    protected $table = 'doctor_portal_leads';

    protected $guarded = [];

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(DoctorRequest::class, 'doctor_request_id');
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class, 'lead_id');
    }
}
