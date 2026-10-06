<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeadAiCallNote extends Model
{
    protected $guarded = [];

    protected $casts = [
        'summary' => 'array',
        'call_at' => 'datetime',
    ];
}
