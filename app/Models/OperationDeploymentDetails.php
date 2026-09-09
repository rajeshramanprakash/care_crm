<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OperationDeploymentDetails extends Model
{
    use HasFactory;

    protected $guarded = [];
    protected $table = 'operation_deployment_details';

    protected $fillable = [
        'operation_lead_id',
        'deployment_date',
        'deployment_from_date',
        'deployment_to_date',
        'deployment_status',
        'duty_hours',
        'vendor_id',
        'staff_name',
        'staff_number',
        'payment_term',
        'vendor_rate_per_day',
        'vendor_payment',
        'absent_dates',
        'verify_payment',
        'payment_verified_at',
        'remark',
        'freelance_staff_id'
    ];

    protected $casts = [
        'vendor_rate_per_day' => 'decimal:2',
        'vendor_payment' => 'decimal:2',
        'verify_payment' => 'boolean',
        'deployment_date' => 'datetime',
        'deployment_from_date' => 'datetime',
        'deployment_to_date' => 'datetime',
        'absent_dates' => 'array',
        'payment_verified_at' => 'datetime',
    ];

    public function absentAdjustments()
    {
        return $this->hasMany(DeploymentAbsentAdjustment::class, 'operation_deployment_detail_id');
    }

    /**
     * The accessors to append to the model's array form.
     * This ensures calculated_vendor_payment is always included in JSON responses.
     */
    protected $appends = ['calculated_vendor_payment'];

    public function operationLead()
    {
        return $this->belongsTo(OperationLead::class, 'operation_lead_id');
    }

    public function vendor()
    {
        return $this->belongsTo(Vendor::class, 'vendor_id');
    }

    public function freelanceStaff()
    {
        return $this->belongsTo(JobRequest::class, 'freelance_staff_id');
    }

    /**
     * Calculate vendor payment based on payment term and absent days.
     *
     * Note: This accessor is mainly used where we don't manually override
     * vendor_payment. In views where we actively maintain vendor_payment
     * (e.g. deployment details with absent dates), prefer using vendor_payment.
     */
    public function getCalculatedVendorPaymentAttribute()
    {
        // If an explicit vendor_payment is stored, start from there
        if ($this->vendor_payment !== null) {
            return (float) $this->vendor_payment;
        }

        if (!$this->vendor_rate_per_day || !$this->payment_term) {
            return 0;
        }

        $ratePerDay = floatval($this->vendor_rate_per_day);
        $days = $this->getPaymentTermDays();

        // Adjust by absent days if present
        $absentCount = is_array($this->absent_dates) ? count($this->absent_dates) : 0;
        $effectiveDays = max(0, $days - $absentCount);

        return $ratePerDay * $effectiveDays;
    }

    /**
     * Get number of days from payment term
     */
    public function getPaymentTermDays()
    {
        if (!$this->payment_term) {
            return 0;
        }

        $term = strtolower($this->payment_term);
        
        switch ($term) {
            case '15 day advance':
            case 'post 15 days':
                return 15;
            
            case '1 week advance':
            case 'post 1 week':
                return 7;
            
            case 'per day advance':
                return 1;
            
            case 'post one month':
                return 30;
            
            default:
                return 0;
        }
    }
}
