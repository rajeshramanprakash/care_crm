<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FreelancerPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'job_request_id',
        'amount',
        'payment_method',
        'transaction_id',
        'reference_number',
        'description',
        'deployment_detail_ids',
        'screenshot',
        'payment_date',
        'status',
        'created_by'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_date' => 'date',
        'deployment_detail_ids' => 'array',
    ];

    /**
     * Get deployment details (line items) for this payment invoice.
     */
    public function getInvoiceLineItems()
    {
        $ids = $this->deployment_detail_ids;
        if (empty($ids) || !is_array($ids)) {
            return collect();
        }
        return OperationDeploymentDetails::with(['operationLead', 'freelanceStaff'])
            ->whereIn('id', $ids)
            ->orderBy('deployment_from_date')
            ->get();
    }

    /**
     * Check if this payment has invoice line items (linked to leads).
     */
    public function hasInvoice(): bool
    {
        return !empty($this->deployment_detail_ids) && is_array($this->deployment_detail_ids);
    }

    // Relationships
    public function jobRequest()
    {
        return $this->belongsTo(JobRequest::class, 'job_request_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Scopes
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeByFreelancer($query, $jobRequestId)
    {
        return $query->where('job_request_id', $jobRequestId);
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('payment_date', [$startDate, $endDate]);
    }

    // Accessors
    public function getFormattedAmountAttribute()
    {
        return '₹' . number_format($this->amount, 2);
    }

    public function getFormattedPaymentDateAttribute()
    {
        return $this->payment_date->format('d-M-Y');
    }

    public function getPaymentMethodTextAttribute()
    {
        return ucwords(str_replace('_', ' ', $this->payment_method));
    }

    public function getStatusBadgeAttribute()
    {
        $badges = [
            'pending' => 'badge bg-warning',
            'completed' => 'badge bg-success',
            'failed' => 'badge bg-danger'
        ];

        return $badges[$this->status] ?? 'badge bg-secondary';
    }
}

