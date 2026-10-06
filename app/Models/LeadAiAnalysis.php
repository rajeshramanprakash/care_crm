<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeadAiAnalysis extends Model
{
    protected $guarded = [];

    protected $casts = [
        'result' => 'array',
        'sources' => 'array',
        'score' => 'integer',
        'started_at' => 'datetime',
        'analyzed_at' => 'datetime',
    ];

    public const TYPES = ['sales' => 'Sales', 'operation' => 'Operation'];

    public const HEALTH = [
        'won' => 'Won / Closed',
        'good' => 'Going well',
        'average' => 'Needs attention',
        'at_risk' => 'At risk',
        'lost' => 'Lost',
    ];

    public const HEALTH_COLORS = [
        'won' => 'success',
        'good' => 'success',
        'average' => 'warning',
        'at_risk' => 'danger',
        'lost' => 'secondary',
    ];

    public function isRunning(): bool
    {
        return in_array($this->status, ['pending', 'processing'], true)
            && $this->started_at && $this->started_at->gt(now()->subMinutes(10));
    }

    public function getHealthLabelAttribute(): string
    {
        return self::HEALTH[$this->health] ?? '—';
    }

    public function getHealthColorAttribute(): string
    {
        return self::HEALTH_COLORS[$this->health] ?? 'secondary';
    }
}
