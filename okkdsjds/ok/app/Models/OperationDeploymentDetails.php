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
        'verify_payment',
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
    ];

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
     * Calculate vendor payment based on payment term
     */
    public function getCalculatedVendorPaymentAttribute()
    {
        if (!$this->vendor_rate_per_day || !$this->payment_term) {
            return $this->vendor_payment ?? 0;
        }

        $ratePerDay = floatval($this->vendor_rate_per_day);
        $days = $this->getPaymentTermDays();
        
        return $ratePerDay * $days;
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
