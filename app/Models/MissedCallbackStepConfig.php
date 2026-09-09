<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MissedCallbackStepConfig extends Model
{
    protected $fillable = [
        'step_index',
        'kind',
        'delay_minutes',
        'window_start',
        'window_end',
        'day_offset',
        'is_active',
    ];

    protected $casts = [
        'delay_minutes' => 'integer',
        'day_offset' => 'integer',
        'is_active' => 'boolean',
    ];

    public const KIND_IMMEDIATE = 'immediate';

    public const KIND_DELAY_MINUTES = 'delay_minutes';

    public const KIND_CALENDAR_WINDOW = 'calendar_window';
}
