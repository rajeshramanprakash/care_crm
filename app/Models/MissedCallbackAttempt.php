<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MissedCallbackAttempt extends Model
{
    public const RESULT_PLACED = 'placed';

    public const RESULT_SKIPPED_BUSY = 'skipped_busy';

    public const RESULT_FAILED_API = 'failed_api';

    public const RESULT_DRY_RUN = 'dry_run';

    public const RESULT_STOPPED_ANSWERED = 'stopped_answered';

    public const RESULT_NO_ANSWER = 'no_answer';

    protected $fillable = [
        'missed_callback_sequence_id',
        'step_index',
        'scheduled_at',
        'started_at',
        'finished_at',
        'result',
        'meta',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
        'meta' => 'array',
    ];

    public function sequence(): BelongsTo
    {
        return $this->belongsTo(MissedCallbackSequence::class, 'missed_callback_sequence_id');
    }
}
