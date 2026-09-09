<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OperationLead extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    protected $fillable = [
        'lead_id',
        'date_time',
        'executive',
        'customer_name',
        'contact_no',
        'address',
        'location',
        'query',
        'query_remark',
        'status',
        'shift_type',
        'last_call_status',
        'recording_url',
        'active_deployment_recording_url',
        'status_remark',
        'price_issue_remark',
        'inactive_remark',
        'closed_remark',
        'patient_name',
        'patient_gender',
        'age',
        'closed_rate',
        'vendor_id',
        'staff_name',
        'vendor_closed_rate',
        'payment_plan',
        'ongoing_stopped',
        'stopped_date_time',
        'stopped_remark'
    ];

    protected $appends = ['last_call_status_display'];

    /**
     * Get the related CRM lead
     */
    public function crmLead()
    {
        return $this->belongsTo(Lead::class, 'lead_id');
    }

    /**
     * Get the last call status, either from the database or from related CRM lead recordings
     */
    public function getLastCallStatusDisplayAttribute()
    {
        // If last_call_status is already set, return it
        if (!empty($this->last_call_status)) {
            return $this->last_call_status;
        }

        // Otherwise, try to get it from related CRM lead recordings
        return $this->getLastCallStatusFromCRMLead();
    }

    /**
     * Extract the last call status from related CRM lead recordings
     */
    public function getLastCallStatusFromCRMLead()
    {
        // Find related CRM lead by contact number
        $crmLead = \App\Models\Lead::where('contact_no', $this->contact_no)->first();

        if (!$crmLead || empty($crmLead->recording_url)) {
            return null;
        }

        try {
            $recordings = json_decode($crmLead->recording_url, true);

            if (!is_array($recordings) || empty($recordings)) {
                return null;
            }

            // Get the last recording (most recent)
            $lastRecording = end($recordings);

            if (!isset($lastRecording['metadata'])) {
                return null;
            }

            $metadata = json_decode($lastRecording['metadata'], true);

            if (!is_array($metadata) || !isset($metadata['dialstatus'])) {
                return null;
            }

            return $metadata['dialstatus'];

        } catch (\Exception $e) {
            return null;
        }
    }

    public function details()
    {
        return $this->hasMany(OperationLeadDetail::class);
    }

    public function executive()
    {
        return $this->belongsTo(User::class, 'executive');
    }

    /**
     * Relationship to Payment Invoices
     */
    public function paymentInvoices()
    {
        return $this->hasMany(PaymentInvoice::class);
    }

    /**
     * Relationship to Received Payments
     */
    public function receivedPayments()
    {
        return $this->hasMany(ReceivedPayment::class);
    }

    /**
     * Get payment plan days
     */
    public function getPaymentPlanDays()
    {
        if (empty($this->payment_plan)) {
            return 0;
        }

        $plan = strtolower($this->payment_plan);
        
        if (strpos($plan, '3 days') !== false || strpos($plan, '3days') !== false) {
            return 3;
        } elseif (strpos($plan, '1 week') !== false || strpos($plan, 'week') !== false) {
            return 7;
        } elseif (strpos($plan, '15 days') !== false || strpos($plan, '15days') !== false) {
            return 15;
        } elseif (strpos($plan, '1 month') !== false || strpos($plan, 'month') !== false) {
            return 30;
        }

        return 0;
    }
}
