<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TicketMessage extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    // Relationships
    public function ticket()
    {
        return $this->belongsTo(Ticket::class, 'ticket_id');
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function recipient()
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }

    // Message types
    const TYPE_VENDOR_NOTIFICATION = 'vendor_notification';
    const TYPE_TPA_NOTIFICATION = 'tpa_notification';
    const TYPE_TPA_APPROVAL = 'tpa_approval';
    const TYPE_VENDOR_SUBMISSION = 'vendor_submission';
    const TYPE_SYSTEM = 'system';

    // Helper methods
    public function getMessageTypeTextAttribute()
    {
        switch ($this->message_type) {
            case self::TYPE_VENDOR_NOTIFICATION:
                return 'Vendor Notification';
            case self::TYPE_TPA_NOTIFICATION:
                return 'TPA Notification';
            case self::TYPE_TPA_APPROVAL:
                return 'TPA Approval';
            case self::TYPE_VENDOR_SUBMISSION:
                return 'Vendor Submission';
            case self::TYPE_SYSTEM:
                return 'System Message';
            default:
                return 'Unknown';
        }
    }
}
