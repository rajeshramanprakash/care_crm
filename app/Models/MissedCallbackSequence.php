<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MissedCallbackSequence extends Model
{
    public const STATUS_ACTIVE = 'active';

    public const STATUS_COMPLETED_ANSWERED = 'completed_answered';

    public const STATUS_COMPLETED_EXHAUSTED = 'completed_exhausted';

    public const STATUS_CANCELLED = 'cancelled';

    public const LEAD_TYPE_LEAD = 'lead';

    public const LEAD_TYPE_OPERATION_LEAD = 'operation_lead';

    protected $fillable = [
        'lead_type',
        'lead_id',
        'inbound_tata_call_id',
        'executive_id',
        'customer_phone',
        'status',
        'current_step',
        'next_attempt_at',
        'last_attempt_at',
        'awaiting_disposition_at',
        'stop_reason',
    ];

    protected $casts = [
        'next_attempt_at' => 'datetime',
        'last_attempt_at' => 'datetime',
        'awaiting_disposition_at' => 'datetime',
    ];

    public function attempts(): HasMany
    {
        return $this->hasMany(MissedCallbackAttempt::class, 'missed_callback_sequence_id');
    }

    public function executive(): BelongsTo
    {
        return $this->belongsTo(User::class, 'executive_id');
    }

    public function leadRecord(): Lead|OperationLead|null
    {
        if ($this->lead_type === self::LEAD_TYPE_OPERATION_LEAD) {
            return OperationLead::find($this->lead_id);
        }

        return Lead::find($this->lead_id);
    }
}
