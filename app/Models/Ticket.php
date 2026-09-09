<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Ticket extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'submission_time' => 'datetime',
        'is_active' => 'boolean',
    ];

    // Relationships
    public function case()
    {
        return $this->belongsTo(Cases::class, 'case_id');
    }

    public function queryData()
    {
        return $this->belongsTo(Query::class, 'query_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function messages()
    {
        return $this->hasMany(TicketMessage::class, 'ticket_id');
    }

    public function vendor()
    {
        return $this->belongsTo(User::class, 'vendor_id');
    }

    public function tpa()
    {
        return $this->belongsTo(User::class, 'tpa_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeClosed($query)
    {
        return $query->where('is_active', false);
    }

    // Helper methods
    public function getStatusTextAttribute()
    {
        return $this->is_active ? 'Active' : 'Closed';
    }

    public function getCaseTypeTextAttribute()
    {
        switch ($this->case_type) {
            case 'normal':
                return 'Normal Case';
            case 'post_1':
                return 'Post 1';
            case 'post_2':
                return 'Post 2';
            default:
                return 'Unknown';
        }
    }
}
