<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DoctorConsultationPriceChangeRequest extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'doctor_request_id',
        'service_id',
        'service_name',
        'sub_service_id',
        'sub_service_name',
        'consultation_mode',
        'current_price',
        'requested_price',
        'status',
        'admin_note',
        'reviewed_by',
        'reviewed_at',
    ];

    protected $casts = [
        'current_price' => 'decimal:2',
        'requested_price' => 'decimal:2',
        'reviewed_at' => 'datetime',
    ];

    public function doctorRequest(): BelongsTo
    {
        return $this->belongsTo(DoctorRequest::class, 'doctor_request_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function modeLabel(): string
    {
        return LocationDoctorConsultationPrice::modeLabel((string) $this->consultation_mode);
    }
}
