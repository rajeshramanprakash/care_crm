<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Task extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'status',
        'priority',
        'due_date',
        'assigned_to',
        'assigned_by',
        'completed_at',
        'notes'
    ];

    protected $casts = [
        'due_date' => 'date',
        'completed_at' => 'datetime',
    ];

    // Relationship with the user who is assigned the task
    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    // Relationship with the user who assigned the task
    public function assignedBy()
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    // Scope for tasks assigned to a specific user
    public function scopeAssignedTo($query, $userId)
    {
        return $query->where('assigned_to', $userId);
    }

    // Scope for tasks assigned by a specific user
    public function scopeAssignedBy($query, $userId)
    {
        return $query->where('assigned_by', $userId);
    }

    // Scope for tasks by status
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    // Scope for overdue tasks
    public function scopeOverdue($query)
    {
        return $query->where('due_date', '<', Carbon::today())
                    ->where('status', '!=', 'completed');
    }

    // Scope for today's tasks
    public function scopeToday($query)
    {
        return $query->where('due_date', Carbon::today());
    }

    // Accessor for formatted due date
    public function getFormattedDueDateAttribute()
    {
        return $this->due_date->format('d M Y');
    }

    // Accessor for status badge class
    public function getStatusBadgeClassAttribute()
    {
        return match($this->status) {
            'pending' => 'badge-secondary',
            'in_progress' => 'badge-warning',
            'completed' => 'badge-success',
            'cancelled' => 'badge-danger',
            default => 'badge-secondary'
        };
    }

    // Accessor for priority badge class
    public function getPriorityBadgeClassAttribute()
    {
        return match($this->priority) {
            'low' => 'badge-info',
            'medium' => 'badge-primary',
            'high' => 'badge-warning',
            'urgent' => 'badge-danger',
            default => 'badge-primary'
        };
    }

    // Check if task is overdue
    public function isOverdue()
    {
        return $this->due_date < Carbon::today() && $this->status !== 'completed';
    }

    // Check if task is due today
    public function isDueToday()
    {
        return $this->due_date->isToday();
    }
}
