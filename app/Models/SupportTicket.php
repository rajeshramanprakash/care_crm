<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class SupportTicket extends Model
{
    protected $guarded = [];

    protected $casts = [
        'last_reply_at' => 'datetime',
        'resolved_at' => 'datetime',
        'closed_at' => 'datetime',
        'unread_for_staff' => 'boolean',
        'unread_for_customer' => 'boolean',
        'desk_sent_at' => 'datetime',
        'desk_synced_at' => 'datetime',
    ];

    /** bug = Report bugs / technical problems (also sent to Ashniva Desk); support = account issues & support requests. */
    public const TYPES = [
        'bug' => 'Bug / Technical problem',
        'support' => 'Account / Support request',
    ];

    public const SUPPORT_CATEGORIES = [
        'service_issue' => 'Service issue',
        'staff_behaviour' => 'Staff behaviour',
        'billing' => 'Billing',
        'app_technical' => 'App / technical',
        'scheduling' => 'Scheduling',
        'other' => 'Other',
    ];

    public const CATEGORIES = ['bug' => 'Bug / Technical problem'] + self::SUPPORT_CATEGORIES;

    public const CUSTOMER_PRIORITIES = [
        'low' => 'Low',
        'normal' => 'Medium',
        'high' => 'High',
    ];

    public const PRIORITIES = self::CUSTOMER_PRIORITIES + ['urgent' => 'Urgent'];

    public const STATUSES = [
        'open' => 'Open',
        'in_progress' => 'In Progress',
        'waiting_customer' => 'Waiting for Customer',
        'resolved' => 'Resolved',
        'closed' => 'Closed',
    ];

    public function messages()
    {
        return $this->hasMany(SupportTicketMessage::class)->orderBy('id');
    }

    public function assignee()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function getCategoryLabelAttribute(): string
    {
        return self::CATEGORIES[$this->category] ?? ucfirst((string) $this->category);
    }

    public function getPriorityLabelAttribute(): string
    {
        return self::PRIORITIES[$this->priority] ?? ucfirst((string) $this->priority);
    }

    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->type] ?? ucfirst((string) $this->type);
    }

    public function isBug(): bool
    {
        return $this->type === 'bug';
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? ucfirst((string) $this->status);
    }

    public function isClosed(): bool
    {
        return $this->status === 'closed';
    }

    public static function nextTicketNo(): string
    {
        $prefix = 'SUP-' . now()->format('Y') . '-';
        $last = DB::table('support_tickets')->where('ticket_no', 'like', $prefix . '%')->lockForUpdate()->max('ticket_no');
        $next = $last ? ((int) substr($last, strlen($prefix))) + 1 : 1;

        return $prefix . str_pad((string) $next, 6, '0', STR_PAD_LEFT);
    }
}
