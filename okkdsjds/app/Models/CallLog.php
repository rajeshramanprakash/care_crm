<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CallLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'lead_id',
        'executive_id',
        'caller_id_number',
        'agent_number',
        'agent_name',
        'call_status',
        'recording_url',
        'call_received_datetime',
        'customer_name',
        'lead_code',
        'is_processed'
    ];

    protected $casts = [
        'call_received_datetime' => 'datetime',
        'is_processed' => 'boolean'
    ];

    public function lead()
    {
        return $this->belongsTo(Lead::class);
    }

    public function executive()
    {
        return $this->belongsTo(User::class, 'executive_id');
    }
}
