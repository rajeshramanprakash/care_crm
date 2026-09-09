<?php

namespace App\Models;

use Carbon\Carbon;
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
        'profile_image',
        'contact_no',
        'address',
        'location',
        'query',
        'query_remark',
        'status',
        'follow_up_date',
        'future_prospect_date',
        'future_prospect_reminder_at',
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
        'stopped_remark',
        'customer_location_attendance_enabled',
    ];

    protected $casts = [
        'follow_up_date' => 'datetime',
        'future_prospect_date' => 'datetime',
        'future_prospect_reminder_at' => 'datetime',
        'customer_location_attendance_enabled' => 'boolean',
    ];

    protected $appends = ['last_call_status_display'];

    /**
     * Get the related CRM lead
     */
    public function crmLead()
    {
        return $this->belongsTo(Lead::class, 'lead_id');
    }

    public function statusRemarks()
    {
        return $this->hasMany(OperationLeadStatusRemark::class)->orderBy('id');
    }

    public function b2bLead()
    {
        return $this->hasOne(B2BLead::class, 'operation_lead_id');
    }

    /**
     * Resolve linked sales CRM lead (formatted CH id, numeric id, or contact match).
     */
    public function resolveCrmLead(): ?Lead
    {
        if (! empty($this->lead_id)) {
            $numericId = Lead::extractIdFromFormattedId($this->lead_id);
            if ($numericId) {
                $lead = Lead::find($numericId);
                if ($lead) {
                    return $lead;
                }
            }
            if (is_numeric($this->lead_id)) {
                $lead = Lead::find((int) $this->lead_id);
                if ($lead) {
                    return $lead;
                }
            }
        }

        if (! empty($this->contact_no)) {
            return Lead::where('contact_no', $this->contact_no)->orderByDesc('id')->first();
        }

        return null;
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
     * Get payment plan days from lead's payment_plan (or from given string e.g. deployment payment_term).
     * Supports: 15 day advance, 1 week advance, per day advance, post 1 week, post 15 days, post one month,
     * and legacy: 3 days, 1 week, 15 days, 1 month, weekly, monthly.
     */
    public function getPaymentPlanDays()
    {
        $plan = !empty($this->payment_plan) ? trim($this->payment_plan) : '';
        return self::parsePaymentTermToDays($plan);
    }

    /**
     * Parse payment term / payment plan string to number of days (for invoice interval).
     * Use for both lead payment_plan and deployment payment_term.
     */
    public static function parsePaymentTermToDays($term)
    {
        if (empty($term) || !is_string($term)) {
            return 0;
        }
        $plan = strtolower(trim($term));

        // 15 day advance, post 15 days, 15 days, 15 day
        if (strpos($plan, '15 day') !== false || strpos($plan, '15days') !== false) {
            return 15;
        }
        // 1 week advance, post 1 week, 1 week, week, weekly
        if (strpos($plan, '1 week') !== false || strpos($plan, 'week') !== false || strpos($plan, 'weekly') !== false) {
            return 7;
        }
        // post one month, 1 month, month, monthly
        if (strpos($plan, 'one month') !== false || strpos($plan, '1 month') !== false || strpos($plan, 'month') !== false || strpos($plan, 'monthly') !== false) {
            return 30;
        }
        // 3 days, 3 day
        if (strpos($plan, '3 day') !== false || strpos($plan, '3days') !== false) {
            return 3;
        }
        // per day advance
        if (strpos($plan, 'per day') !== false) {
            return 1;
        }

        return 0;
    }

    /**
     * Wall time for future-contact / follow-up reminder (operation leads).
     */
    public function scheduledContactReminderDueAt(): ?Carbon
    {
        $st = strtolower(trim((string) $this->status));

        if ($st === 'future prospect' && $this->future_prospect_date) {
            return $this->future_prospect_date;
        }

        if (($st === 'follow up' || $st === 'follow-up') && $this->follow_up_date) {
            return $this->follow_up_date;
        }

        return null;
    }

    /**
     * Reminder modal: only from the scheduled instant until end of that calendar day (app timezone).
     * Prevents the same slot from re-surfacing days or weeks later unless the datetime is updated.
     */
    public function isScheduledContactReminderDueThisAppDay(?Carbon $now = null): bool
    {
        $now ??= Carbon::now();
        $due = $this->scheduledContactReminderDueAt();

        if (! $due || $now->lt($due)) {
            return false;
        }

        $tz = config('app.timezone');
        $dueDay = $due->copy()->timezone($tz)->toDateString();
        $nowDay = $now->copy()->timezone($tz)->toDateString();

        return $dueDay === $nowDay;
    }
}
