<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class CustomerFeedback extends Model
{
    protected $table = 'customer_feedbacks';

    protected $guarded = [];

    protected $casts = [
        'reviewed_at' => 'datetime',
        'operation_seen_at' => 'datetime',
        'manager_seen_at' => 'datetime',
    ];

    public const RATING_FIELDS = [
        'overall_rating' => 'Overall rating',
        'service_quality' => 'Service quality',
        'staff_behaviour' => 'Staff behaviour',
        'response_time' => 'Response time',
        'overall_experience' => 'Overall experience',
    ];

    public const STATUSES = [
        'new' => 'New',
        'reviewed' => 'Reviewed',
        'resolved' => 'Resolved',
    ];

    public function operationLead()
    {
        return $this->belongsTo(OperationLead::class, 'operation_lead_id');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function operationUser()
    {
        return $this->belongsTo(User::class, 'operation_user_id')->withTrashed();
    }

    public function operationManager()
    {
        return $this->belongsTo(User::class, 'operation_manager_id')->withTrashed();
    }

    /**
     * Operation (role 4) sees feedback on the leads they handled; Operation Manager (role 5) also sees
     * feedback for everyone in their team (users.parent_id), including team members who joined later.
     */
    public function scopeVisibleToOperation(Builder $query, User $user, bool $isManager): Builder
    {
        if (! $isManager) {
            return $query->where('operation_user_id', $user->id);
        }

        $team = User::withTrashed()->where('parent_id', $user->id)->pluck('id');

        return $query->where(fn ($q) => $q->where('operation_manager_id', $user->id)
            ->orWhere('operation_user_id', $user->id)
            ->orWhereIn('operation_user_id', $team));
    }
}
