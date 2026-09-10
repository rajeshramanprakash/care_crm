<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

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
        'profile_image',
        'profile_image_upload',
        'profile_image_pending',
        'profile_image_status',
        'profile_image_reviewed_at',
        'profile_image_reviewed_by',
        'age',
        'gender',
        'expected_salary',
        'shift',
        'total_experience',
        'job_title',
        'service_sub_services',
        'other_remark',
        'city',
        'location',
        'location_id',
        'full_address',
        'full_address_lat',
        'full_address_lng',
        'radius_12hr_km',
        'radius_24hr_km',
        'radius_onetime_km',
        'remark',
        'status',
        'lead_id',
        'recording_url',
        'last_call_status',
        'mobile',
        'aadhar_card',
        'pan_card',
        'qualification_certificate',
        'account_name',
        'account_number',
        'ifsc_code',
        'upi_id',
        'bank_document',
    ];

    protected $casts = [
        'service_sub_services' => 'array',
    ];

    public function getPartnerIdAttribute()
    {
        return $this->attributes['partner_id'] ?? $this->lead_id;
    }

    public function servicePrices()
    {
        return $this->hasMany(JobRequestServicePrice::class);
    }

    public function priceChangeRequests()
    {
        return $this->hasMany(FreelancerServicePriceChangeRequest::class)->orderByDesc('created_at');
    }

    public function pendingPriceChangeRequests()
    {
        return $this->hasMany(FreelancerServicePriceChangeRequest::class)
            ->where('status', FreelancerServicePriceChangeRequest::STATUS_PENDING);
    }

    public function executive()
    {
        return $this->belongsTo(User::class, 'executive_id');
    }

    public static function matchesServiceJobTitle(?string $jobTitle, string $configKey): bool
    {
        $title = strtolower(trim((string) $jobTitle));
        if ($title === '') {
            return false;
        }
        foreach ((array) config('freelancer_profile.'.$configKey, []) as $needle) {
            $needle = strtolower(trim((string) $needle));
            if ($needle !== '' && ($title === $needle || str_contains($title, $needle))) {
                return true;
            }
        }

        return false;
    }

    public static function isAttendantJobTitle(?string $jobTitle): bool
    {
        return self::matchesServiceJobTitle($jobTitle, 'attendant_service_match');
    }

    public static function isNurseJobTitle(?string $jobTitle): bool
    {
        return self::matchesServiceJobTitle($jobTitle, 'nurse_service_match');
    }

    public function requiresAttendantUniform(): bool
    {
        return self::isAttendantJobTitle($this->job_title);
    }

    public function requiresNurseUniform(): bool
    {
        return self::isNurseJobTitle($this->job_title);
    }

    /** @return 'attendant'|'nurse'|null */
    public function getUniformDressType(): ?string
    {
        if ($this->requiresNurseUniform()) {
            return 'nurse';
        }
        if ($this->requiresAttendantUniform()) {
            return 'attendant';
        }

        return null;
    }

    public function requiresUniformApproval(): bool
    {
        return $this->getUniformDressType() !== null;
    }

    public function getDisplayProfileImagePath(): ?string
    {
        if ($this->requiresUniformApproval()) {
            if ($this->profile_image_status === 'approved' && $this->profile_image) {
                return $this->profile_image;
            }
            if ($this->profile_image_upload) {
                return $this->profile_image_upload;
            }

            return null;
        }

        return $this->profile_image ?: null;
    }

    public function getPublicProfileImagePath(): ?string
    {
        if ($this->requiresUniformApproval()) {
            return ($this->profile_image_status === 'approved' && $this->profile_image)
                ? $this->profile_image
                : null;
        }

        return $this->profile_image ?: null;
    }

    /**
     * @return array{success: bool, message: string, profile_image_url: string, pending_approval: bool}
     */
    public function applyProfileImageUpload(UploadedFile $file): array
    {
        $dressType = $this->getUniformDressType();
        if ($dressType !== null) {
            $path = $file->store('documents/freelancers/selfies', 'public');

            if ($this->profile_image_upload && $this->profile_image_upload !== $this->profile_image) {
                Storage::disk('public')->delete($this->profile_image_upload);
            }
            if ($this->profile_image_pending) {
                Storage::disk('public')->delete($this->profile_image_pending);
            }

            $this->profile_image_upload = $path;
            $this->profile_image_pending = null;
            $this->profile_image_status = 'pending_review';
            $this->save();

            $uniformLabel = $dressType === 'nurse' ? 'nurse uniform' : 'attendant uniform';

            return [
                'success' => true,
                'message' => 'Photo uploaded. Please wait for admin approval — your profile will update after the Carelix '.$uniformLabel.' preview is approved.',
                'profile_image_url' => asset('storage/'.$path),
                'pending_approval' => true,
            ];
        }

        $filename = time().'_'.uniqid().'.'.$file->getClientOriginalExtension();
        $file->storeAs('public/profile_images', $filename);
        $path = 'profile_images/'.$filename;

        if ($this->profile_image) {
            Storage::disk('public')->delete($this->profile_image);
        }

        $this->profile_image = $path;
        $this->profile_image_upload = null;
        $this->profile_image_pending = null;
        $this->profile_image_status = 'none';
        $this->save();

        return [
            'success' => true,
            'message' => 'Profile image updated successfully',
            'profile_image_url' => Storage::url($path),
            'pending_approval' => false,
        ];
    }

    public function leegalitySignatures(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(FreelancerLeegalitySignature::class, 'job_request_id');
    }

    public function latestLeegalitySignature(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(FreelancerLeegalitySignature::class, 'job_request_id')->latestOfMany('id');
    }

    public function locationModel()
    {
        return $this->belongsTo(Location::class, 'location_id');
    }

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($model) {
            if ($model->isDirty('city') && !empty($model->city)) {
                $loc = \App\Models\Location::where(\Illuminate\Support\Facades\DB::raw('LOWER(name)'), strtolower(trim($model->city)))->first();
                if ($loc) {
                    $model->location_id = $loc->id;
                }
            }
        });
    }
}
