<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class B2BLead extends Model
{
    protected $table = 'b2b_leads';

    protected $guarded = [];

    public function b2bUser()
    {
        return $this->belongsTo(B2BUser::class, 'b2b_user_id');
    }

    public function operationLead()
    {
        return $this->belongsTo(OperationLead::class, 'operation_lead_id');
    }

    public function lead()
    {
        return $this->belongsTo(Lead::class, 'lead_id');
    }

    /**
     * B2B corporate portal lead linked to this operation lead (if any).
     */
    public static function corporateLeadForOperationLead(int $operationLeadId): ?self
    {
        if ($operationLeadId <= 0) {
            return null;
        }

        return static::query()
            ->where('operation_lead_id', $operationLeadId)
            ->whereHas('b2bUser', fn ($q) => $q->corporate())
            ->first();
    }
}

