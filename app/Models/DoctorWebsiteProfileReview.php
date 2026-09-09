<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class DoctorWebsiteProfileReview extends Model
{
    protected $table = 'doctor_website_profile_reviews';

    protected $fillable = [
        'doctor_request_id',
        'reviewer_display_name',
        'reviewer_photo_path',
        'rating',
        'body',
        'consultation_mode',
        'reviewed_on',
        'sort_order',
    ];

    protected $casts = [
        'reviewed_on' => 'date',
        'rating' => 'decimal:1',
        'sort_order' => 'integer',
    ];

    public function doctorRequest(): BelongsTo
    {
        return $this->belongsTo(DoctorRequest::class, 'doctor_request_id');
    }

    public function reviewerPhotoPublicUrl(): ?string
    {
        $path = trim((string) ($this->reviewer_photo_path ?? ''));
        if ($path === '') {
            return null;
        }
        if (filter_var($path, FILTER_VALIDATE_URL)) {
            return $path;
        }

        return asset('storage/'.$path);
    }

    public function deleteReviewerPhotoIfPresent(): void
    {
        $path = trim((string) ($this->reviewer_photo_path ?? ''));
        if ($path === '' || filter_var($path, FILTER_VALIDATE_URL)) {
            return;
        }
        Storage::disk('public')->delete($path);
    }
}
