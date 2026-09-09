<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Lead extends Model
{
    protected $guarded = [];

    protected $fillable = [
        'date',
        'executive',
        'customer_name',
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
        'status_remarks',
        'stage',
        'shift_type',
        'future_prospect_date',
        'prospect_rate',
        'last_call_status',
        'recording_url'
    ];

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
}
