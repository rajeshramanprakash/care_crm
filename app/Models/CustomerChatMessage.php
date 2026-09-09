<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerChatMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'sender_type',
        'sender_id',
        'receiver_type',
        'receiver_id',
        'message',
        'attachment',
        'attachment_type',
        'is_read',
        'reply_to_id'
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Get sender based on type
    public function sender()
    {
        if ($this->sender_type === 'vendor') {
            return $this->belongsTo(Vendor::class, 'sender_id');
        } elseif ($this->sender_type === 'freelancer') {
            return $this->belongsTo(JobRequest::class, 'sender_id');
        } elseif ($this->sender_type === 'doctor') {
            return $this->belongsTo(DoctorRequest::class, 'sender_id');
        } elseif ($this->sender_type === 'customer') {
            return $this->belongsTo(OperationLead::class, 'sender_id');
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

    // Get sender name
    public function getSenderNameAttribute()
    {
        if ($this->sender_type === 'vendor') {
            return $this->sender->name ?? 'Vendor';
        } elseif ($this->sender_type === 'freelancer') {
            return $this->sender->name ?? 'Freelancer';
        } elseif ($this->sender_type === 'doctor') {
            return $this->sender->name ?? 'Doctor';
        } elseif ($this->sender_type === 'customer') {
            return $this->sender->customer_name ?? 'Customer';
        }
        return 'Unknown';
    }

    // Get receiver name
    public function getReceiverNameAttribute()
    {
        if ($this->receiver_type === 'vendor') {
            return $this->receiver->name ?? 'Vendor';
        } elseif ($this->receiver_type === 'freelancer') {
            return $this->receiver->name ?? 'Freelancer';
        } elseif ($this->receiver_type === 'doctor') {
            return $this->receiver->name ?? 'Doctor';
        } elseif ($this->receiver_type === 'customer') {
            return $this->receiver->customer_name ?? 'Customer';
        }
        return 'Unknown';
    }

    // Get the message this is replying to
    public function repliedTo()
    {
        return $this->belongsTo(CustomerChatMessage::class, 'reply_to_id');
    }
}
