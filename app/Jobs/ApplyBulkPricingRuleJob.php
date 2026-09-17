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
            case 'normal':
                return max(0, $value);
            case 'increase_fixed':
                return $currentPrice + $value;
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
            if (!empty($this->rule->selected_cities)) {
                return $this->rule->selected_cities;
            }
            $tierName = ucfirst(str_replace('_', ' ', $filter));
            return \App\Models\Location::where('tier', $tierName)->pluck('id')->toArray();
        }
        return null;
    }

    private function getCityFilterLocationNames()
    {
        $filter = $this->rule->city_filter;
        if (in_array($filter, ['tier_1', 'tier_2', 'tier_3'])) {
            if (!empty($this->rule->selected_cities)) {
                return \App\Models\Location::whereIn('id', $this->rule->selected_cities)->pluck('name')->toArray();
            }
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
                $mode = $this->rule->mode_type;
                if (empty($mode) || $mode === '12_hours' || $mode === 'both') {
                    if (isset($priceRecord->price_12hr)) {
                        $updateData['price_12hr'] = $this->calculateNewPrice($priceRecord->price_12hr, $this->rule->change_type, $this->rule->value);
                        if ($this->rule->time_period === 'temporary' && $updateData['price_12hr'] != $priceRecord->price_12hr) {
                            $updateData['original_price_12hr'] = $priceRecord->price_12hr;
                        }
                    }
                }
                
                if (empty($mode) || $mode === '24_hours' || $mode === 'both') {
                    if (isset($priceRecord->price_24hr)) {
                        $updateData['price_24hr'] = $this->calculateNewPrice($priceRecord->price_24hr, $this->rule->change_type, $this->rule->value);
                        if ($this->rule->time_period === 'temporary' && $updateData['price_24hr'] != $priceRecord->price_24hr) {
                            $updateData['original_price_24hr'] = $priceRecord->price_24hr;
                        }
                    }
                }

                if (empty($mode) || $mode === 'one_time') {
                    if (isset($priceRecord->price_onetime)) {
                        $updateData['price_onetime'] = $this->calculateNewPrice($priceRecord->price_onetime, $this->rule->change_type, $this->rule->value);
                        if ($this->rule->time_period === 'temporary' && $updateData['price_onetime'] != $priceRecord->price_onetime) {
                            $updateData['original_price_onetime'] = $priceRecord->price_onetime;
                        }
                    }
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
        $vendors = \App\Models\Vendor::where('status', 'active')->get();
        foreach ($vendors as $vendor) {
            $blocks = \App\Services\VendorServiceSync::blocksForVendor($vendor);
            foreach ($blocks as $block) {
                $serviceId = (int) ($block['service_id'] ?? 0);
                if ($this->rule->service_id && $serviceId !== (int)$this->rule->service_id) continue;
                
                $subServices = $block['sub_services'] ?? [];
                $subIds = [0];
                foreach ($subServices as $s) {
                    if (isset($s['sub_service_id'])) { $subIds[] = (int)$s['sub_service_id']; } elseif (isset($s['id'])) { $subIds[] = (int)$s['id']; }
                }

                foreach ($subIds as $subId) {
                    if ($this->rule->sub_service_id && $subId !== (int)$this->rule->sub_service_id) continue;
                    
                    $cityMatch = false;
                    $filter = $this->rule->city_filter;
                    $locationIds = $this->getCityFilterLocationIds();
                    
                    if ($filter === 'all' || empty($filter) || $filter === 'current') {
                        $cityMatch = true;
                    } else if ($locationIds !== null) {
                        if ($vendor->location_id && in_array($vendor->location_id, $locationIds)) {
                            $cityMatch = true;
                        } else {
                            $shifts = is_string($vendor->service_city_shifts) ? json_decode($vendor->service_city_shifts, true) : $vendor->service_city_shifts;
                            if (is_array($shifts)) {
                                foreach ($shifts as $shiftData) {
                                    if (isset($shiftData['cities']) && is_array($shiftData['cities'])) {
                                        foreach ($shiftData['cities'] as $cityInfo) {
                                            if (in_array((int)($cityInfo['city_id'] ?? 0), $locationIds)) {
                                                $cityMatch = true;
                                                break 3;
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }

                    if (!$cityMatch) continue;

                    $current = \App\Services\VendorServiceSync::resolvePrice($vendor, $serviceId, $subId);
                    
                    $price12 = $current['price_12hr'];
                    $price24 = $current['price_24hr'];
                    $priceOne = $current['price_onetime'];
                    $mode = $this->rule->mode_type;
                    $updated = false;

                    if (empty($mode) || $mode === '12_hours' || $mode === 'both') {
                        if ($price12 !== null) {
                            $newPrice = $this->calculateNewPrice($price12, $this->rule->change_type, $this->rule->value);
                            if ($newPrice != $price12) { $price12 = $newPrice; $updated = true; }
                        } else if ($this->rule->change_type === 'normal') {
                            $price12 = $this->rule->value; $updated = true;
                        }
                    }
                    if (empty($mode) || $mode === '24_hours' || $mode === 'both') {
                        if ($price24 !== null) {
                            $newPrice = $this->calculateNewPrice($price24, $this->rule->change_type, $this->rule->value);
                            if ($newPrice != $price24) { $price24 = $newPrice; $updated = true; }
                        } else if ($this->rule->change_type === 'normal') {
                            $price24 = $this->rule->value; $updated = true;
                        }
                    }
                    if (empty($mode) || $mode === 'one_time') {
                        if ($priceOne !== null) {
                            $newPrice = $this->calculateNewPrice($priceOne, $this->rule->change_type, $this->rule->value);
                            if ($newPrice != $priceOne) { $priceOne = $newPrice; $updated = true; }
                        } else if ($this->rule->change_type === 'normal') {
                            $priceOne = $this->rule->value; $updated = true;
                        }
                    }

                    if ($updated) {
                        $vsp = \App\Models\VendorServicePrice::firstOrNew(
                            ['vendor_id' => $vendor->id, 'service_id' => $serviceId, 'service_sub_service_id' => $subId]
                        );

                        if ($this->rule->time_period === 'temporary') {
                            if (!$vsp->exists || $vsp->original_price_12hr === null) $vsp->original_price_12hr = $vsp->price_12hr;
                            if (!$vsp->exists || $vsp->original_price_24hr === null) $vsp->original_price_24hr = $vsp->price_24hr;
                            if (!$vsp->exists || $vsp->original_price_onetime === null) $vsp->original_price_onetime = $vsp->price_onetime;
                        } else {
                            $vsp->original_price_12hr = null;
                            $vsp->original_price_24hr = null;
                            $vsp->original_price_onetime = null;
                        }

                        $vsp->price_12hr = $price12;
                        $vsp->price_24hr = $price24;
                        $vsp->price_onetime = $priceOne;
                        $vsp->save();
                    }
                }
            }
        }
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
            $normalizedMode = match (strtolower(trim($this->rule->mode_type))) {
                'online' => 'online',
                'home visit' => 'home_visit',
                'clinic' => 'clinic_visit',
                default => strtolower(str_replace(' ', '_', trim($this->rule->mode_type)))
            };
            $query->where('consultation_mode', $normalizedMode);
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

        // Update Individual Doctor Profiles (Admin-set consultation charges)
        $doctorQuery = \App\Models\DoctorRequest::query()->where('approval_status', 'Approved');

        if ($this->rule->service_id) {
            $doctorService = \App\Models\DoctorConsultationService::find($this->rule->service_id);
            if ($doctorService) {
                $doctorQuery->where('job_title', $doctorService->name);
            }
        }
        
        $cityNames = $this->getCityFilterLocationNames();
        if ($cityNames !== null) {
            $doctorQuery->where(function($q) use ($cityNames) {
                $q->whereIn('city', $cityNames)->orWhereIn('location', $cityNames);
            });
        }

        $doctorQuery->chunk(100, function ($doctors) {
            foreach ($doctors as $doctor) {
                $updated = false;
                
                $normalizedMode = null;
                if ($this->rule->mode_type) {
                    $normalizedMode = match (strtolower(trim($this->rule->mode_type))) {
                        'online' => 'online',
                        'home visit' => 'home_visit',
                        'clinic' => 'clinic_visit',
                        default => strtolower(str_replace(' ', '_', trim($this->rule->mode_type)))
                    };
                }
                $modesToUpdate = $normalizedMode ? [$normalizedMode] : ['online', 'home_visit', 'clinic_visit'];


                foreach ($modesToUpdate as $mode) {
                    $columnName = match ($mode) {
                        'online' => 'online_charges',
                        'home_visit' => 'home_visit_charges',
                        'clinic_visit' => 'clinic_consultation_charges',
                        default => null
                    };

                    if ($columnName && $doctor->{$columnName} !== null) {
                        $oldPrice = $doctor->{$columnName};
                        $newPrice = $this->calculateNewPrice($oldPrice, $this->rule->change_type, $this->rule->value);

                        if ($oldPrice != $newPrice) {
                            if ($this->rule->time_period === 'temporary') {
                                $originalCol = 'original_' . $columnName;
                                $doctor->{$originalCol} = $oldPrice;
                            }
                            $doctor->{$columnName} = $newPrice;
                            $updated = true;

                            \App\Models\DoctorRequestPriceLog::create([
                                'doctor_request_id' => $doctor->id,
                                'mode' => $mode,
                                'old_amount' => $oldPrice,
                                'new_amount' => $newPrice,
                                'note' => 'Updated via Bulk Pricing Rule #' . $this->rule->id,
                                'updated_by' => 1,
                            ]);
                        }
                    }
                }

                // Sub-service JSON override logic
                $pricingArr = $doctor->consultation_pricing;
                if (is_array($pricingArr)) {
                    $jsonUpdated = false;
                    foreach ($pricingArr as &$pricingRow) {
                        if (!is_array($pricingRow)) continue;

                        if ($this->rule->service_id && (int)($pricingRow['service_id'] ?? 0) !== (int)$this->rule->service_id) {
                            continue;
                        }
                        
                        if ($this->rule->sub_service_id && (int)($pricingRow['sub_service_id'] ?? 0) !== (int)$this->rule->sub_service_id) {
                            continue;
                        }

                        if (isset($pricingRow['modes']) && is_array($pricingRow['modes'])) {
                            foreach ($modesToUpdate as $mode) {
                                if (isset($pricingRow['modes'][$mode]['doctor_price'])) {
                                    $oldPrice = $pricingRow['modes'][$mode]['doctor_price'];
                                    $newPrice = $this->calculateNewPrice($oldPrice, $this->rule->change_type, $this->rule->value);

                                    if ($oldPrice != $newPrice) {
                                        if ($this->rule->time_period === 'temporary') {
                                            $pricingRow['modes'][$mode]['original_doctor_price'] = $oldPrice;
                                        }
                                        $pricingRow['modes'][$mode]['doctor_price'] = $newPrice;
                                        $jsonUpdated = true;

                                        \App\Models\DoctorRequestPriceLog::create([
                                            'doctor_request_id' => $doctor->id,
                                            'mode' => $mode . ' (' . ($pricingRow['sub_service_name'] ?? 'General') . ')',
                                            'old_amount' => $oldPrice,
                                            'new_amount' => $newPrice,
                                            'note' => 'Sub-service updated via Bulk Rule #' . $this->rule->id,
                                            'updated_by' => 1,
                                        ]);
                                    }
                                }
                            }
                        }
                    }
                    if ($jsonUpdated) {
                        $doctor->consultation_pricing = $pricingArr;
                        $updated = true;
                    }
                }

                if ($updated) {
                    $doctor->save();
                }
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
            $normalizedMode = match (strtolower(trim($this->rule->mode_type))) {
                'online' => 'online',
                'home visit' => 'home_visit',
                'clinic' => 'clinic_visit',
                default => strtolower(str_replace(' ', '_', trim($this->rule->mode_type)))
            };
            $query->where('consultation_mode', $normalizedMode);
        }

        $locationIds = $this->getCityFilterLocationIds();
        if ($locationIds !== null) {
            $query->whereIn('location_id', $locationIds);
        }

        $query->chunk(200, function ($prices) {
            foreach ($prices as $priceRecord) {
                if ($this->rule->time_period === 'temporary') {
                    $priceRecord->original_website_price = $priceRecord->website_price;
                    $priceRecord->original_doctor_max_price = $priceRecord->doctor_max_price;
                }
                $priceRecord->website_price = $this->calculateNewPrice($priceRecord->website_price, $this->rule->change_type, $this->rule->value);
                $priceRecord->save();
            }
        });

        // Update Individual Doctor Profiles (Website Customer Fees)
        $doctorQuery = \App\Models\DoctorRequest::query()->where('approval_status', 'Approved');

        if ($this->rule->service_id) {
            $doctorService = \App\Models\DoctorConsultationService::find($this->rule->service_id);
            if ($doctorService) {
                $doctorQuery->where('job_title', $doctorService->name);
            }
        }
        
        $cityNames = $this->getCityFilterLocationNames();
        if ($cityNames !== null) {
            $doctorQuery->where(function($q) use ($cityNames) {
                $q->whereIn('city', $cityNames)->orWhereIn('location', $cityNames);
            });
        }

        $doctorQuery->chunk(100, function ($doctors) {
            foreach ($doctors as $doctor) {
                $updated = false;
                
                $normalizedMode = null;
                if ($this->rule->mode_type) {
                    $normalizedMode = match (strtolower(trim($this->rule->mode_type))) {
                        'online' => 'online',
                        'home visit' => 'home_visit',
                        'clinic' => 'clinic_visit',
                        default => strtolower(str_replace(' ', '_', trim($this->rule->mode_type)))
                    };
                }
                $modesToUpdate = $normalizedMode ? [$normalizedMode] : ['online', 'home_visit', 'clinic_visit'];


                foreach ($modesToUpdate as $mode) {
                    $columnName = match ($mode) {
                        'online' => 'website_customer_fee_online',
                        'home_visit' => 'website_customer_fee_home_visit',
                        'clinic_visit' => 'website_customer_fee_clinic',
                        default => null
                    };

                    if ($columnName && $doctor->{$columnName} !== null) {
                        $oldPrice = $doctor->{$columnName};
                        $newPrice = $this->calculateNewPrice($oldPrice, $this->rule->change_type, $this->rule->value);

                        if ($oldPrice != $newPrice) {
                            if ($this->rule->time_period === 'temporary') {
                                $originalCol = 'original_' . $columnName;
                                $doctor->{$originalCol} = $oldPrice;
                            }
                            $doctor->{$columnName} = $newPrice;
                            $updated = true;

                            \App\Models\DoctorRequestPriceLog::create([
                                'doctor_request_id' => $doctor->id,
                                'mode' => $mode,
                                'old_amount' => $oldPrice,
                                'new_amount' => $newPrice,
                                'note' => 'Website fee updated via Bulk Pricing Rule #' . $this->rule->id,
                                'updated_by' => 1,
                            ]);
                        }
                    }
                }

                // Sub-service JSON override logic
                $pricingArr = $doctor->consultation_pricing;
                if (is_array($pricingArr)) {
                    $jsonUpdated = false;
                    foreach ($pricingArr as &$pricingRow) {
                        if (!is_array($pricingRow)) continue;

                        if ($this->rule->service_id && (int)($pricingRow['service_id'] ?? 0) !== (int)$this->rule->service_id) {
                            continue;
                        }
                        
                        if ($this->rule->sub_service_id && (int)($pricingRow['sub_service_id'] ?? 0) !== (int)$this->rule->sub_service_id) {
                            continue;
                        }

                        if (isset($pricingRow['modes']) && is_array($pricingRow['modes'])) {
                            foreach ($modesToUpdate as $mode) {
                                if (isset($pricingRow['modes'][$mode]['website_price'])) {
                                    $oldPrice = $pricingRow['modes'][$mode]['website_price'];
                                    $newPrice = $this->calculateNewPrice($oldPrice, $this->rule->change_type, $this->rule->value);

                                    if ($oldPrice != $newPrice) {
                                        if ($this->rule->time_period === 'temporary') {
                                            $pricingRow['modes'][$mode]['original_website_price'] = $oldPrice;
                                        }
                                        $pricingRow['modes'][$mode]['website_price'] = $newPrice;
                                        $jsonUpdated = true;

                                        \App\Models\DoctorRequestPriceLog::create([
                                            'doctor_request_id' => $doctor->id,
                                            'mode' => $mode . ' (' . ($pricingRow['sub_service_name'] ?? 'General') . ')',
                                            'old_amount' => $oldPrice,
                                            'new_amount' => $newPrice,
                                            'note' => 'Website sub-service fee updated via Bulk Rule #' . $this->rule->id,
                                            'updated_by' => 1,
                                        ]);
                                    }
                                }
                            }
                        }
                    }
                    if ($jsonUpdated) {
                        $doctor->consultation_pricing = $pricingArr;
                        $updated = true;
                    }
                }

                if ($updated) {
                    $doctor->save();
                }
            }
        });
    }

        private function applyToFreelancers()
    {
        $jobRequests = \App\Models\JobRequest::where('status', 'active')->get();
        foreach ($jobRequests as $jobReq) {
            // Simplified freelancer logic since JobRequest structure might vary.
            // Using existing JobRequestServicePrice logic but fallback to default if missing.
            
            $blocks = \App\Services\FreelancerServiceSync::blocksForFreelancer($jobReq);
            foreach ($blocks as $block) {
                $serviceId = (int) ($block['service_id'] ?? 0);
                if ($this->rule->service_id && $serviceId !== (int)$this->rule->service_id) continue;
                
                $subServices = $block['sub_services'] ?? [];
                $subIds = [0];
                foreach ($subServices as $s) {
                    if (isset($s['sub_service_id'])) { $subIds[] = (int)$s['sub_service_id']; } elseif (isset($s['id'])) { $subIds[] = (int)$s['id']; }
                }

                foreach ($subIds as $subId) {
                    if ($this->rule->sub_service_id && $subId !== (int)$this->rule->sub_service_id) continue;
                    
                    $cityMatch = false;
                    $filter = $this->rule->city_filter;
                    $locationIds = $this->getCityFilterLocationIds();
                    
                    if ($filter === 'all' || empty($filter) || $filter === 'current') {
                        $cityMatch = true;
                    } else if ($locationIds !== null) {
                        if ($jobReq->location_id && in_array($jobReq->location_id, $locationIds)) {
                            $cityMatch = true;
                        }
                    }

                    if (!$cityMatch) continue;

                    $current = \App\Services\FreelancerServiceSync::resolvePrice($jobReq, $serviceId, $subId);
                    
                    $price12 = $current['price_12hr'];
                    $price24 = $current['price_24hr'];
                    $priceOne = $current['price_onetime'];
                    $mode = $this->rule->mode_type;
                    $updated = false;

                    if (empty($mode) || $mode === '12_hours' || $mode === 'both') {
                        if ($price12 !== null) {
                            $newPrice = $this->calculateNewPrice($price12, $this->rule->change_type, $this->rule->value);
                            if ($newPrice != $price12) { $price12 = $newPrice; $updated = true; }
                        } else if ($this->rule->change_type === 'normal') {
                            $price12 = $this->rule->value; $updated = true;
                        }
                    }
                    if (empty($mode) || $mode === '24_hours' || $mode === 'both') {
                        if ($price24 !== null) {
                            $newPrice = $this->calculateNewPrice($price24, $this->rule->change_type, $this->rule->value);
                            if ($newPrice != $price24) { $price24 = $newPrice; $updated = true; }
                        } else if ($this->rule->change_type === 'normal') {
                            $price24 = $this->rule->value; $updated = true;
                        }
                    }
                    if (empty($mode) || $mode === 'one_time') {
                        if ($priceOne !== null) {
                            $newPrice = $this->calculateNewPrice($priceOne, $this->rule->change_type, $this->rule->value);
                            if ($newPrice != $priceOne) { $priceOne = $newPrice; $updated = true; }
                        } else if ($this->rule->change_type === 'normal') {
                            $priceOne = $this->rule->value; $updated = true;
                        }
                    }

                    if ($updated) {
                        $jsp = \App\Models\JobRequestServicePrice::firstOrNew(
                            ['job_request_id' => $jobReq->id, 'service_id' => $serviceId, 'service_sub_service_id' => $subId]
                        );

                        if ($this->rule->time_period === 'temporary') {
                            if (!$jsp->exists || $jsp->original_price_12hr === null) $jsp->original_price_12hr = $jsp->price_12hr;
                            if (!$jsp->exists || $jsp->original_price_24hr === null) $jsp->original_price_24hr = $jsp->price_24hr;
                            if (!$jsp->exists || $jsp->original_price_onetime === null) $jsp->original_price_onetime = $jsp->price_onetime;
                        } else {
                            $jsp->original_price_12hr = null;
                            $jsp->original_price_24hr = null;
                            $jsp->original_price_onetime = null;
                        }

                        $jsp->price_12hr = $price12;
                        $jsp->price_24hr = $price24;
                        $jsp->price_onetime = $priceOne;
                        $jsp->save();
                    }
                }
            }
        }
    }
}