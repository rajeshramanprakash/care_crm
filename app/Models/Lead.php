<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Expression;
use Illuminate\Support\Facades\DB;

class Lead extends Model
{
    protected $guarded = [];

    protected $fillable = [
        'date',
        'executive',
        'customer_name',
        'profile_image',
        'patient_name',
        'patient_gender',
        'age',
        'contact_type',
        'contact_no',
        'location',
        'lead_source',
        'query',
        'query_remarks',
        'status',
        'follow_up_date',
        'status_remarks',
        'stage',
        'inactive_stage_remark',
        'shift_type',
        'future_prospect_date',
        'future_prospect_reminder_at',
        'prospect_rate',
        'last_call_status',
        'recording_url'
    ];

    protected $casts = [
        'future_prospect_date' => 'datetime',
        'follow_up_date' => 'datetime',
        'future_prospect_reminder_at' => 'datetime',
    ];

    public function statusRemarks()
    {
        return $this->hasMany(LeadStatusRemark::class)->orderBy('id');
    }

    /**
     * Normalize status for PHP comparisons. MySQL may match WHERE clauses case-insensitively while
     * returning the row's stored casing, which breaks strict === checks in reminder logic.
     */
    public static function normalizedLeadStatus(?string $status): string
    {
        return strtolower(trim((string) $status));
    }

    public static function isSalesFollowUpStatus(?string $status): bool
    {
        return self::normalizedLeadStatus($status) === 'follow-up';
    }

    public static function isSalesFutureProspectStatus(?string $status): bool
    {
        return self::normalizedLeadStatus($status) === 'future prospect';
    }

    /**
     * Parse future contact datetime from forms (datetime-local sends "Y-m-d\TH:i") in app timezone.
     */
    public static function parseFutureProspectDateInput(mixed $value): ?Carbon
    {
        if ($value === null || $value === '') {
            return null;
        }
        $s = trim((string) $value);
        if ($s === '') {
            return null;
        }
        $raw = str_replace('T', ' ', $s);

        // If the input already carries an explicit timezone/offset (ISO-8601),
        // respect it. Otherwise interpret as app "wall time" (Asia/Kolkata).
        if (preg_match('/(Z|[+-]\d{2}:\d{2})$/', $raw)) {
            return Carbon::parse($raw);
        }

        return Carbon::parse($raw, config('app.timezone'));
    }

    protected $appends = ['last_call_status_display', 'formatted_id'];

    /**
     * Get formatted ID for this lead
     * Can be used as attribute: $lead->formatted_id
     * Or as method: $lead->formatted_id()
     */
    public function formatted_id()
    {
        if (!$this->id) {
            return null;
        }
        return 'CH' . str_pad($this->id, 8, '0', STR_PAD_LEFT);
    }

    /**
     * Accessor for formatted_id (for attribute access)
     */
    public function getFormattedIdAttribute()
    {
        return $this->formatted_id();
    }

    /**
     * Extract numeric ID from formatted ID (e.g., "CH00000724" -> 724)
     * Static method for use with OperationLead and other models
     */
    public static function extractIdFromFormattedId($formattedId)
    {
        if (empty($formattedId)) {
            return null;
        }
        
        // Remove CH prefix and leading zeros
        $numericId = (int) ltrim(str_replace('CH', '', $formattedId), '0');
        
        return $numericId ?: null;
    }

    /**
     * Get the executive user for this lead
     */
    public function executiveUser()
    {
        return $this->belongsTo(User::class, 'executive', 'id');
    }

    /**
     * Get the last call status, either from the database or from recordings
     */
    public function getLastCallStatusDisplayAttribute()
    {
        // If last_call_status is already set, return it
        if (!empty($this->last_call_status)) {
            return $this->last_call_status;
        }

        // Otherwise, try to get it from recordings
        return $this->getLastCallStatusFromRecordings();
    }

    /**
     * Extract the last call status from recordings
     */
    public function getLastCallStatusFromRecordings()
    {
        if (empty($this->recording_url)) {
            return null;
        }

        try {
            $recordings = json_decode($this->recording_url, true);

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

    /**
     * Wall time for sales future-contact / follow-up reminder.
     */
    public function scheduledSalesContactReminderDueAt(): ?Carbon
    {
        if (self::isSalesFutureProspectStatus($this->status) && $this->future_prospect_date) {
            return $this->future_prospect_date;
        }

        if (self::isSalesFollowUpStatus($this->status) && $this->follow_up_date) {
            return $this->follow_up_date;
        }

        return null;
    }

    /**
     * Reminder (CRM web + app poll): only from the scheduled instant until end of that calendar day (app timezone).
     * Matches operation leads behaviour — avoids the same slot resurfacing days later on login.
     */
    public function isScheduledSalesContactReminderDueThisAppDay(?Carbon $now = null): bool
    {
        $now ??= Carbon::now();
        $due = $this->scheduledSalesContactReminderDueAt();

        if (! $due || $now->lt($due)) {
            return false;
        }

        $tz = config('app.timezone');
        $dueDay = $due->copy()->timezone($tz)->toDateString();
        $nowDay = $now->copy()->timezone($tz)->toDateString();

        return $dueDay === $nowDay;
    }

    /**
     * SQL expression aligned with CRM list views: derive B2b display source when leads.lead_source is empty.
     * Requires aliases bl (b2b_leads) and bu (b2b_users) joined like other lead queries.
     */
    public static function computedLeadSourceSelect(string $alias = 'computed_lead_source'): Expression
    {
        $sql = <<<SQL
COALESCE(NULLIF(TRIM(leads.lead_source), ''), CASE
    WHEN bl.id IS NOT NULL AND TRIM(COALESCE(bu.company_name, '')) <> '' THEN CONCAT('b2b (', TRIM(bu.company_name), ')')
    WHEN bl.id IS NOT NULL AND TRIM(COALESCE(bu.name, '')) <> '' THEN CONCAT('b2b (', TRIM(bu.name), ')')
    WHEN bl.id IS NOT NULL THEN 'b2b'
    ELSE NULL
END) as {$alias}
SQL;

        return DB::raw($sql);
    }
}
