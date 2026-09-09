<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DailyCaseCount extends Model
{
    use HasFactory;

    protected $table = 'daily_case_counts';

    protected $fillable = [
        'department',
        'role_id',
        'date',
        'main_claim_cases',
        'post_claim_cases',
        'post_two_claim_cases',
    ];
}
