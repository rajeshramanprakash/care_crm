<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CallDetails extends Model
{
    use HasFactory;

    protected $fillable = [
        'lead_id',
        'call_for', // lead, operation_lead, job_request
        'executive_id',
        'caller_id_number',
        'agent_number',
        'agent_name',
        'call_status',
        'recording_url',
        'call_received_datetime',
        'customer_name',
        'lead_code',
        'is_processed',
        'call_duration',
        'call_notes',
        'call_type',
        'call_source',
        'raw_data'
    ];

    protected $casts = [
        'call_received_datetime' => 'datetime',
        'is_processed' => 'boolean',
        'raw_data' => 'array'
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
