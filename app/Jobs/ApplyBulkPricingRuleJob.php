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
            case 'doctor':
                $this->applyToDoctors();
                break;
            case 'freelancer':
                $this->applyToFreelancers();
                break;
            // website can be handled later based on needs
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
                // New = Old * (1 + V/100) => Old = New / (1 + V/100)
                return max(0, $currentPrice / (1 + ($value / 100)));
            case 'revert_decrease_percent':
                // New = Old * (1 - V/100) => Old = New / (1 - V/100)
                // Avoid division by zero if value is 100%
                if ($value >= 100) return $currentPrice;
                return max(0, $currentPrice / (1 - ($value / 100)));
            default:
                return $currentPrice;
        }
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

        $query->chunk(200, function ($prices) {
            foreach ($prices as $priceRecord) {
                // Determine which fields to update based on mode_type
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

    private function applyToDoctors()
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

        $query->chunk(200, function ($prices) {
            foreach ($prices as $priceRecord) {
                // Update website_price and doctor_max_price
                $priceRecord->website_price = $this->calculateNewPrice($priceRecord->website_price, $this->rule->change_type, $this->rule->value);
                $priceRecord->doctor_max_price = $this->calculateNewPrice($priceRecord->doctor_max_price, $this->rule->change_type, $this->rule->value);
                
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

        $query->chunk(200, function ($prices) {
            foreach ($prices as $priceRecord) {
                $mode = $this->rule->mode_type;
                
                // Assuming JobRequestServicePrice has similar fields to VendorServicePrice
                // You can adapt this based on the exact schema of JobRequestServicePrice
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
