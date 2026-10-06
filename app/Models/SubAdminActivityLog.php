<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubAdminActivityLog extends Model
{
    protected $guarded = [];

    protected $casts = [
        'changes' => 'array',
        'request_data' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class)->withTrashed();
    }
}
