<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Cases extends Model
{
    use HasFactory, SoftDeletes;
    protected $table = 'cases';
    protected $guarded = [];
    protected $casts = [
        'lab_files' => 'array',
    ];



    public function get_tpa()
    {
        return $this->belongsTo(User::class, 'tpa_allot_after_claim_no_received');
    }


    public function user()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function users()
    {
        return $this->belongsTo(User::class, 'assign_member_id');
    }


    public function get_assign_member()
    {
        return $this->belongsTo(User::class, 'assign_member_id');
    }

    public function get_guery()
    {
        return $this->hasMany(Query::class, 'case_id', 'id');
    }
}
