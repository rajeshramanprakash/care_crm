<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class SpeakUpSubmission extends Model
{
    protected $guarded = [];

    protected $hidden = ['follow_up_key_hash'];

    protected $casts = [
        'is_anonymous' => 'boolean',
        'has_unread_reply' => 'boolean',
    ];

    public const CATEGORIES = [
        'manager' => 'Manager / Reporting Manager',
        'colleague' => 'Colleague',
        'workplace' => 'Workplace',
        'hr' => 'HR',
        'company_policy' => 'Company Policy',
        'management' => 'Management',
        'harassment' => 'Harassment / Misconduct',
        'suggestion' => 'Suggestion',
        'other' => 'Other',
    ];

    public const STATUSES = [
        'new' => 'New',
        'under_review' => 'Under Review',
        'resolved' => 'Resolved',
    ];

    public function replies()
    {
        return $this->hasMany(SpeakUpReply::class)->orderBy('id');
    }

    public function publicReplies()
    {
        return $this->hasMany(SpeakUpReply::class)->where('is_internal', false)->orderBy('id');
    }

    public function getCategoryLabelAttribute(): string
    {
        return self::CATEGORIES[$this->category] ?? ucfirst((string) $this->category);
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? ucfirst((string) $this->status);
    }

    public static function nextReferenceNo(): string
    {
        $prefix = 'SPK-' . now()->format('Y') . '-';
        $last = DB::table('speak_up_submissions')->where('reference_no', 'like', $prefix . '%')->lockForUpdate()->max('reference_no');
        $next = $last ? ((int) substr($last, strlen($prefix))) + 1 : 1;

        return $prefix . str_pad((string) $next, 6, '0', STR_PAD_LEFT);
    }
}
