<?php
$file = 'app/Jobs/ApplyBulkPricingRuleJob.php';
$content = file_get_contents($file);

$vendorsStart = strpos($content, 'private function applyToVendors()');
$doctorsPayoutStart = strpos($content, 'private function applyToDoctorsPayout()');

$vendorsCode = <<<'CODE'
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
                    if (isset($s['id'])) $subIds[] = (int)$s['id'];
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
                        \App\Models\VendorServicePrice::updateOrCreate(
                            ['vendor_id' => $vendor->id, 'service_id' => $serviceId, 'service_sub_service_id' => $subId],
                            ['price_12hr' => $price12, 'price_24hr' => $price24, 'price_onetime' => $priceOne]
                        );
                    }
                }
            }
        }
    }

CODE;

$content = substr_replace($content, $vendorsCode, $vendorsStart, $doctorsPayoutStart - $vendorsStart);

$freelancersStart = strpos($content, 'private function applyToFreelancers()');
$endOfFile = strpos($content, '}', $freelancersStart + 50);

$freelancersCode = <<<'CODE'
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
                    if (isset($s['id'])) $subIds[] = (int)$s['id'];
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
                        \App\Models\JobRequestServicePrice::updateOrCreate(
                            ['job_request_id' => $jobReq->id, 'service_id' => $serviceId, 'service_sub_service_id' => $subId],
                            ['price_12hr' => $price12, 'price_24hr' => $price24, 'price_onetime' => $priceOne]
                        );
                    }
                }
            }
        }
    }
}
CODE;

$content = substr_replace($content, $freelancersCode, $freelancersStart);
file_put_contents($file, $content);
echo "Replaced logic\n";
