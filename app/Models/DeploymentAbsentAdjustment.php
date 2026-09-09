<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeploymentAbsentAdjustment extends Model
{
    protected $table = 'deployment_absent_adjustments';

    protected $fillable = [
        'operation_deployment_detail_id',
        'adjusted_at',
        'amount_deducted',
        'absent_dates',
    ];

    protected $casts = [
        'adjusted_at' => 'datetime',
        'amount_deducted' => 'decimal:2',
        'absent_dates' => 'array',
    ];

    public function operationDeploymentDetail(): BelongsTo
    {
        return $this->belongsTo(OperationDeploymentDetails::class, 'operation_deployment_detail_id');
    }
}
