<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesReferralLead extends Model
{
    protected $table = 'sales_referral_leads';

    protected $guarded = [];

    protected $casts = [
        'reviewed_at' => 'datetime',
        'commission_percent' => 'decimal:2',
    ];

    public function generatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by_user_id');
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_user_id');
    }

    public function assignedExecutive(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_executive_id');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_user_id');
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class, 'lead_id');
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }
}
