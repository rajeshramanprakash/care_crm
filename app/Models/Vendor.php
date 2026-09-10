<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vendor extends Model
{
    use HasFactory;

    protected $fillable = [
        'lead_id',
        'agreement_number',
        'name',
        'customer_name',
        'contact_no',
        'email',
        'profile_image',
        'age',
        'gender',
        'expected_salary',
        'total_experience',
        'job_title',
        'service_sub_services',
        'vendor_services',
        'location',
        'location_id',
        'account_name',
        'account_number',
        'ifsc_code',
        'upi_id',
        'service_city_shifts',
        'shift',
        'status',
        'aadhar_card',
        'pan_card',
        'qualification_certificate',
        'bank_document'
    ];

    protected $casts = [
        'service_city_shifts' => 'array',
        'service_sub_services' => 'array',
        'vendor_services' => 'array',
    ];

    // Relationships
    public function payments()
    {
        return $this->hasMany(VendorPayment::class);
    }

    public function locationModel()
    {
        return $this->belongsTo(Location::class, 'location_id');
    }

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($model) {
            if ($model->isDirty('location') && !empty($model->location)) {
                $loc = \App\Models\Location::where(\Illuminate\Support\Facades\DB::raw('LOWER(name)'), strtolower(trim($model->location)))->first();
                if ($loc) {
                    $model->location_id = $loc->id;
                }
            }
        });
    }

    public function priceChangeRequests()
    {
        return $this->hasMany(VendorServicePriceChangeRequest::class)->orderByDesc('created_at');
    }

    public function pendingPriceChangeRequests()
    {
        return $this->hasMany(VendorServicePriceChangeRequest::class)
            ->where('status', VendorServicePriceChangeRequest::STATUS_PENDING);
    }

    public function leegalitySignatures()
    {
        return $this->hasMany(VendorLeegalitySignature::class, 'vendor_id')->orderByDesc('id');
    }

    public function latestLeegalitySignature()
    {
        return $this->hasOne(VendorLeegalitySignature::class, 'vendor_id')->latestOfMany();
    }

    // Accessors
    public function getPartnerIdAttribute()
    {
        return $this->attributes['partner_id'] ?? $this->lead_id;
    }
    public function getTotalPaidAttribute()
    {
        return $this->payments()->completed()->sum('amount');
    }

    public function getLastPaymentDateAttribute()
    {
        return $this->payments()->latest('payment_date')->first()?->payment_date;
    }
}
