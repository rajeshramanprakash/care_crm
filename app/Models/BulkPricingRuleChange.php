<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BulkPricingRuleChange extends Model
{
    protected $fillable = [
        'bulk_pricing_rule_id',
        'target_type',
        'target_id',
        'target_key',
        'field',
        'label',
        'old_value',
        'new_value',
        'reverted_at',
    ];

    protected $casts = [
        'target_key' => 'array',
        'old_value' => 'decimal:2',
        'new_value' => 'decimal:2',
        'reverted_at' => 'datetime',
    ];

    public function rule(): BelongsTo
    {
        return $this->belongsTo(BulkPricingRule::class, 'bulk_pricing_rule_id');
    }
}
