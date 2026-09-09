<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OperationLeadStatusRemark extends Model
{
    protected $guarded = [];

    protected $casts = [
        'is_ai_polished' => 'boolean',
        'ai_generated_at' => 'datetime',
    ];

    public function operationLead(): BelongsTo
    {
        return $this->belongsTo(OperationLead::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
