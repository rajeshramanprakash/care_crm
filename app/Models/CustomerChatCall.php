<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerChatCall extends Model
{
    use HasFactory;

    protected $fillable = [
        'caller_type',
        'caller_id',
        'receiver_type',
        'receiver_id',
        'call_status',
        'call_started_at',
        'call_ended_at',
        'call_duration'
    ];

    protected $casts = [
        'call_started_at' => 'datetime',
        'call_ended_at' => 'datetime',
    ];

    // Get caller based on type
    public function caller()
    {
        if ($this->caller_type === 'vendor') {
            return $this->belongsTo(Vendor::class, 'caller_id');
        } elseif ($this->caller_type === 'freelancer') {
            return $this->belongsTo(JobRequest::class, 'caller_id');
        } elseif ($this->caller_type === 'doctor') {
            return $this->belongsTo(DoctorRequest::class, 'caller_id');
        } elseif ($this->caller_type === 'customer') {
            return $this->belongsTo(OperationLead::class, 'caller_id');
        }
        return null;
    }

    // Get receiver based on type
    public function receiver()
    {
        if ($this->receiver_type === 'vendor') {
            return $this->belongsTo(Vendor::class, 'receiver_id');
        } elseif ($this->receiver_type === 'freelancer') {
            return $this->belongsTo(JobRequest::class, 'receiver_id');
        } elseif ($this->receiver_type === 'doctor') {
            return $this->belongsTo(DoctorRequest::class, 'receiver_id');
        } elseif ($this->receiver_type === 'customer') {
            return $this->belongsTo(OperationLead::class, 'receiver_id');
        }
        return null;
    }
}
