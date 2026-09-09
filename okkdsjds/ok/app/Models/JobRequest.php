<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobRequest extends Model
{
    use HasFactory;
    protected $guarded = [];

    protected $fillable = [
        'date_time',
        'executive_id',
        'customer_name',
        'contact_no',
        'name',
        'age',
        'expected_salary',
        'shift',
        'total_experience',
        'job_title',
        'other_remark',
        'city',
        'remark',
        'status',
        'lead_id',
        'recording_url',
        'last_call_status',
    ];

    public function executive()
    {
        return $this->belongsTo(User::class, 'executive_id');
    }
}
