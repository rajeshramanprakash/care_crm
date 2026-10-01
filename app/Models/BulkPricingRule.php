<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BulkPricingRule extends Model
{
    use HasFactory;

    public const STATE_SCHEDULED = 'scheduled';
    public const STATE_ACTIVE = 'active';
    public const STATE_COMPLETED = 'completed';
    public const STATE_REVERTED = 'reverted';
    public const STATE_FAILED = 'failed';

    public const PRICING_TYPE_LABELS = [
        'doctor_payout' => 'Doctor Payout Only',
        'vendor' => 'Vendor',
        'freelancer' => 'Freelancer',
        'website_general' => 'Website - General Services',
        'website_doctor' => 'Website - Doctor Consultations',
    ];

    public const CHANGE_TYPE_LABELS = [
        'increase_fixed' => 'Increase by Fixed Amount',
        'increase_percent' => 'Increase by Percentage',
        'decrease_fixed' => 'Decrease by Fixed Amount',
        'decrease_percent' => 'Decrease by Percentage',
    ];

    public const APPLY_TO_LABELS = [
        'all' => 'All Existing + New',
        'new' => 'New Registrations Only',
        'old' => 'Old Registrations Only',
    ];

    public const MODE_LABELS = [
        'online' => 'Online',
        'home_visit' => 'Home Visit',
        'clinic_visit' => 'Clinic',
        '12_hours' => '12 Hours',
        '24_hours' => '24 Hours',
        'both' => '12 + 24 Hours',
        'one_time' => 'One-time',
    ];

    protected $fillable = [
        'pricing_type',
        'service_id',
        'sub_service_id',
        'mode_type',
        'change_type',
        'value',
        'apply_to',
        'apply_from_date',
        'apply_to_date',
        'city_filter',
        'selected_cities',
        'time_period',
        'time_period_start_date',
        'time_period_end_date',
        'status',
        'state',
        'created_by',
        'created_by_name',
        'applied_at',
        'reverted_at',
        'affected_count',
        'error_message',
    ];

    protected $casts = [
        'selected_cities' => 'array',
        'apply_from_date' => 'datetime',
        'apply_to_date' => 'datetime',
        'time_period_start_date' => 'datetime',
        'time_period_end_date' => 'datetime',
        'applied_at' => 'datetime',
        'reverted_at' => 'datetime',
        'value' => 'decimal:2',
    ];

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function subService()
    {
        return $this->belongsTo(ServiceSubService::class);
    }

    public function doctorService()
    {
        return $this->belongsTo(DoctorConsultationService::class, 'service_id');
    }

    public function doctorSubService()
    {
        return $this->belongsTo(DoctorConsultationServiceSubService::class, 'sub_service_id');
    }

    public function changes(): HasMany
    {
        return $this->hasMany(BulkPricingRuleChange::class);
    }

    public function isDoctorType(): bool
    {
        return in_array($this->pricing_type, ['doctor_payout', 'website_doctor'], true);
    }

    public function isTemporary(): bool
    {
        return $this->time_period === 'temporary';
    }

    public function isIncrease(): bool
    {
        return str_starts_with((string) $this->change_type, 'increase');
    }

    /**
     * Rule mode normalised to the keys used in price tables (old rows stored "Online", "Home Visit", "Clinic").
     */
    public function normalizedMode(): ?string
    {
        $mode = strtolower(trim((string) $this->mode_type));
        if ($mode === '') {
            return null;
        }

        return match ($mode) {
            'home visit' => 'home_visit',
            'clinic', 'clinic visit' => 'clinic_visit',
            default => str_replace(' ', '_', $mode),
        };
    }

    public function serviceLabel(): string
    {
        $service = $this->isDoctorType() ? $this->doctorService : $this->service;
        $sub = $this->isDoctorType() ? $this->doctorSubService : $this->subService;
        if (! $service) {
            return 'All Services';
        }

        return $service->name.($sub ? ' ('.$sub->name.')' : '');
    }

    public function modeLabel(): string
    {
        $mode = $this->normalizedMode();

        return $mode ? (self::MODE_LABELS[$mode] ?? ucfirst(str_replace('_', ' ', $mode))) : 'All';
    }

    public function changeLabel(): string
    {
        $value = rtrim(rtrim(number_format((float) $this->value, 2, '.', ''), '0'), '.');
        $amount = str_contains((string) $this->change_type, 'percent') ? $value.'%' : '₹'.$value;

        return ($this->isIncrease() ? '+ ' : '− ').$amount;
    }

    public function cityLabel(): string
    {
        $filter = (string) $this->city_filter;
        if ($filter === '' || $filter === 'all' || $filter === 'current') {
            return 'All Cities';
        }
        $label = ucfirst(str_replace('_', ' ', $filter));
        $count = is_array($this->selected_cities) ? count($this->selected_cities) : 0;

        return $count > 0 ? "{$label} ({$count} selected)" : "{$label} (all)";
    }
}
