<?php

namespace App\Models;

use App\Models\JobRequest;
use App\Models\OperationLead;
use App\Models\Vendor;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeploymentLocationAttendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'operation_lead_id',
        'vendor_id',
        'freelancer_id',
        'attendance_date',
        'customer_latitude',
        'customer_longitude',
        'customer_location_captured_at',
        'vendor_latitude',
        'vendor_longitude',
        'vendor_location_captured_at',
        'freelancer_latitude',
        'freelancer_longitude',
        'freelancer_location_captured_at',
        'freelancer_selfie_path',
        'freelancer_selfie_captured_at',
        'attendance_marked_at',
        'attendance_status',
        'distance_meters',
        'is_location_matched',
    ];

    protected $casts = [
        'customer_latitude' => 'float',
        'customer_longitude' => 'float',
        'vendor_latitude' => 'float',
        'vendor_longitude' => 'float',
        'freelancer_latitude' => 'float',
        'freelancer_longitude' => 'float',
        'customer_location_captured_at' => 'datetime',
        'vendor_location_captured_at' => 'datetime',
        'freelancer_location_captured_at' => 'datetime',
        'freelancer_selfie_captured_at' => 'datetime',
        'attendance_marked_at' => 'datetime',
        'attendance_date' => 'date',
        'distance_meters' => 'float',
        'is_location_matched' => 'boolean',
    ];

    public function operationLead(): BelongsTo
    {
        return $this->belongsTo(OperationLead::class, 'operation_lead_id');
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class, 'vendor_id');
    }

    public function freelancer(): BelongsTo
    {
        return $this->belongsTo(JobRequest::class, 'freelancer_id');
    }
}
