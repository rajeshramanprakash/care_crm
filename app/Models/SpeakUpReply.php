<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SpeakUpReply extends Model
{
    protected $guarded = [];

    protected $casts = [
        'is_internal' => 'boolean',
    ];

    public function submission()
    {
        return $this->belongsTo(SpeakUpSubmission::class, 'speak_up_submission_id');
    }
}
