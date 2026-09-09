<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DoctorCalendarAvailabilitySlot extends Model
{
    protected $guarded = [];

    protected $casts = [
        'slot_date' => 'date',
    ];

    public function doctorRequest(): BelongsTo
    {
        return $this->belongsTo(DoctorRequest::class, 'doctor_request_id');
    }

    public function fullLabel(): string
    {
        $base = ($this->time_start ?? '—').' - '.($this->time_end ?? '—');
        if (!empty($this->break_start) && !empty($this->break_end)) {
            return $base.' (break '.$this->break_start.'-'.$this->break_end.')';
        }

        return $base;
    }
}
