<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BreakLog extends Model
{
    use HasFactory;

    protected $table = 'break_logs';

    protected $fillable = [
        'user_id',
        'break_status',
        'break_reason',
        'break_start_time',
        'break_end_time',
        'api_response'
    ];

    protected $casts = [
        'break_start_time' => 'datetime',
        'break_end_time' => 'datetime',
        'api_response' => 'array'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeOnline($query)
    {
        return $query->where('break_status', 'online');
    }

    public function scopeOffline($query)
    {
        return $query->where('break_status', 'offline');
    }
}
