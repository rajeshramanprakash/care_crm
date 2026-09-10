<?php

namespace App\Jobs;

use App\Models\BulkPricingRule;
use App\Models\VendorServicePrice;
use App\Models\LocationDoctorConsultationPrice;
use App\Models\JobRequestServicePrice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ApplyBulkPricingRuleJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $rule;

    /**
     * Create a new job instance.
     */
    public function __construct(BulkPricingRule $rule)
    {
        $this->rule = $rule;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info("Starting ApplyBulkPricingRuleJob for rule ID: {$this->rule->id}");

        switch ($this->rule->pricing_type) {
            case 'vendor':
                $this->applyToVendors();
                break;
            case 'doctor_payout':
                $this->applyToDoctorsPayout();
                break;
            case 'freelancer':
                $this->applyToFreelancers();
                break;
            case 'website_general':
                $this->applyToWebsiteGeneral();
                break;
            case 'website_doctor':
                $this->applyToWebsiteDoctor();
                break;
        }

        Log::info("Completed ApplyBulkPricingRuleJob for rule ID: {$this->rule->id}");
    }

    private function calculateNewPrice($currentPrice, $changeType, $value)
    {
        if ($currentPrice === null) return null;

        $currentPrice = (float) $currentPrice;
        $value = (float) $value;

        switch ($changeType) {
            case 'increase_fixed':
                return max(0, $currentPrice + $value);
            case 'increase_percent':
                return max(0, $currentPrice + ($currentPrice * ($value / 100)));
            case 'decrease_fixed':
                return max(0, $currentPrice - $value);
            case 'decrease_percent':
                return max(0, $currentPrice - ($currentPrice * ($value / 100)));
            case 'revert_increase_percent':
                return max(0, $currentPrice / (1 + ($value / 100)));
            case 'revert_decrease_percent':
                if ($value >= 100) return $currentPrice;
                return max(0, $currentPrice / (1 - ($value / 100)));
            default:
                return $currentPrice;
        }
    }

    private function getCityFilterLocationIds()
    {
        $filter = $this->rule->city_filter;
        if (in_array($filter, ['tier_1', 'tier_2', 'tier_3'])) {
            $tierName = ucfirst(str_replace('_', ' ', $filter));
            return \App\Models\Location::where('tier', $tierName)->pluck('id')->toArray();
        }
        return null;
    }

    private function getCityFilterLocationNames()
    {
        $filter = $this->rule->city_filter;
        if (in_array($filter, ['tier_1', 'tier_2', 'tier_3'])) {
            $tierName = ucfirst(str_replace('_', ' ', $filter));
            return \App\Models\Location::where('tier', $tierName)->pluck('name')->toArray();
        }
        return null;
    }

    private function applyToWebsiteGeneral()
    {
        $query = \Illuminate\Support\Facades\DB::table('location_services');
        
        if ($this->rule->service_id) {
            $query->where('service_id', $this->rule->service_id);
        }
        if ($this->rule->sub_service_id) {
            $query->where('service_sub_service_id', $this->rule->sub_service_id);
        }
        
        $locationIds = $this->getCityFilterLocationIds();
        if ($locationIds !== null) {
            $query->whereIn('location_id', $locationIds);
        }

        $query->orderBy('id')->chunk(200, function ($prices) {
            foreach ($prices as $priceRecord) {
                $updateData = [];
                
                if (isset($priceRecord->price_12hr)) {
                    $updateData['price_12hr'] = $this->calculateNewPrice($priceRecord->price_12hr, $this->rule->change_type, $this->rule->value);
                }
                
                if (isset($priceRecord->price_24hr)) {
                    $updateData['price_24hr'] = $this->calculateNewPrice($priceRecord->price_24hr, $this->rule->change_type, $this->rule->value);
                }

                if (isset($priceRecord->price_onetime)) {
                    $updateData['price_onetime'] = $this->calculateNewPrice($priceRecord->price_onetime, $this->rule->change_type, $this->rule->value);
                }
                
                if (!empty($updateData)) {
                    \Illuminate\Support\Facades\DB::table('location_services')
                        ->where('id', $priceRecord->id)
                        ->update($updateData);
                }
            }
        });
    }

    private function applyToVendors()
    {
        $query = VendorServicePrice::query();
        
        if ($this->rule->service_id) {
            $query->where('service_id', $this->rule->service_id);
        }
        if ($this->rule->sub_service_id) {
            $query->where('service_sub_service_id', $this->rule->sub_service_id);
        }

        $locationIds = $this->getCityFilterLocationIds();
        if ($locationIds !== null) {
            $query->whereHas('vendor', function($q) use ($locationIds) {
                $q->whereIn('location_id', $locationIds);
            });
        }

        $query->chunk(200, function ($prices) {
            foreach ($prices as $priceRecord) {
                $mode = $this->rule->mode_type;
                
                if (empty($mode) || $mode === '12_hours' || $mode === 'both') {
                    $priceRecord->price_12hr = $this->calculateNewPrice($priceRecord->price_12hr, $this->rule->change_type, $this->rule->value);
                }
                
                if (empty($mode) || $mode === '24_hours' || $mode === 'both') {
                    $priceRecord->price_24hr = $this->calculateNewPrice($priceRecord->price_24hr, $this->rule->change_type, $this->rule->value);
                }

                if (empty($mode) || $mode === 'one_time') {
                    $priceRecord->price_onetime = $this->calculateNewPrice($priceRecord->price_onetime, $this->rule->change_type, $this->rule->value);
                }
                
                $priceRecord->save();
            }
        });
    }

    private function applyToDoctorsPayout()
    {
        $query = LocationDoctorConsultationPrice::query();
        
        if ($this->rule->service_id) {
            $query->where('doctor_consultation_service_id', $this->rule->service_id);
        }
        if ($this->rule->sub_service_id) {
            $query->where('doctor_consultation_service_sub_service_id', $this->rule->sub_service_id);
        }
        
        if ($this->rule->mode_type) {
            $query->where('consultation_mode', $this->rule->mode_type);
        }

        $locationIds = $this->getCityFilterLocationIds();
        if ($locationIds !== null) {
            $query->whereIn('location_id', $locationIds);
        }

        $query->chunk(200, function ($prices) {
            foreach ($prices as $priceRecord) {
                $priceRecord->doctor_max_price = $this->calculateNewPrice($priceRecord->doctor_max_price, $this->rule->change_type, $this->rule->value);
                $priceRecord->save();
            }
        });
    }

    private function applyToWebsiteDoctor()
    {
        $query = LocationDoctorConsultationPrice::query();
        
        if ($this->rule->service_id) {
            $query->where('doctor_consultation_service_id', $this->rule->service_id);
        }
        if ($this->rule->sub_service_id) {
            $query->where('doctor_consultation_service_sub_service_id', $this->rule->sub_service_id);
        }
        
        if ($this->rule->mode_type) {
            $query->where('consultation_mode', $this->rule->mode_type);
        }

        $locationIds = $this->getCityFilterLocationIds();
        if ($locationIds !== null) {
            $query->whereIn('location_id', $locationIds);
        }

        $query->chunk(200, function ($prices) {
            foreach ($prices as $priceRecord) {
                $priceRecord->website_price = $this->calculateNewPrice($priceRecord->website_price, $this->rule->change_type, $this->rule->value);
                $priceRecord->save();
            }
        });
    }

    private function applyToFreelancers()
    {
        $query = JobRequestServicePrice::query();
        
        if ($this->rule->service_id) {
            $query->where('service_id', $this->rule->service_id);
        }
        if ($this->rule->sub_service_id) {
            $query->where('service_sub_service_id', $this->rule->sub_service_id);
        }

        $locationIds = $this->getCityFilterLocationIds();
        if ($locationIds !== null) {
            $query->whereHas('jobRequest', function($q) use ($locationIds) {
                $q->whereIn('location_id', $locationIds);
            });
        }

        $query->chunk(200, function ($prices) {
            foreach ($prices as $priceRecord) {
                $mode = $this->rule->mode_type;
                
                if (empty($mode) || $mode === '12_hours' || $mode === 'both') {
                    if (isset($priceRecord->price_12hr)) {
                        $priceRecord->price_12hr = $this->calculateNewPrice($priceRecord->price_12hr, $this->rule->change_type, $this->rule->value);
                    }
                }
                
                if (empty($mode) || $mode === '24_hours' || $mode === 'both') {
                    if (isset($priceRecord->price_24hr)) {
                        $priceRecord->price_24hr = $this->calculateNewPrice($priceRecord->price_24hr, $this->rule->change_type, $this->rule->value);
                    }
                }

                if (empty($mode) || $mode === 'one_time') {
                    if (isset($priceRecord->price_onetime)) {
                        $priceRecord->price_onetime = $this->calculateNewPrice($priceRecord->price_onetime, $this->rule->change_type, $this->rule->value);
                    }
                }
                
                $priceRecord->save();
            }
        });
    }
}
