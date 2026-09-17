<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'state', 'tier'];

    protected $appends = ['display_label'];

    public static function formatDisplayLabel(?string $name, ?string $state = null): string
    {
        $name = trim((string) $name);
        $state = trim((string) ($state ?? ''));

        if ($name === '') {
            return '';
        }

        return $state !== '' ? "{$name}, {$state}" : $name;
    }

    public function getDisplayLabelAttribute(): string
    {
        return static::formatDisplayLabel($this->name, $this->state);
    }

    public function services()
    {
        return $this->belongsToMany(Service::class, 'location_services')
            ->withPivot([
                'price_12hr', 'price_24hr', 'price_onetime',
                'original_price_12hr', 'original_price_24hr', 'original_price_onetime',
                'provider_type', 'service_sub_service_id'
            ])
            ->withTimestamps();
    }

    public function vendorServices()
    {
        return $this->services()->wherePivot('provider_type', 'vendor');
    }

    public function freelancerServices()
    {
        return $this->services()->wherePivot('provider_type', 'freelancer');
    }

    public function doctorConsultationPrices()
    {
        return $this->hasMany(LocationDoctorConsultationPrice::class);
    }
}
