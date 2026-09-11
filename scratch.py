import re

file_path = "app/Jobs/ApplyBulkPricingRuleJob.php"
with open(file_path, "r") as f:
    content = f.read()

# Replace block in applyToDoctorsPayout
payout_find = """
                if ($updated) {
                    $doctor->save();
                }
            }
        });
    }

    private function applyToWebsiteDoctor()
"""
payout_replace = """
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
                                        $pricingRow['modes'][$mode]['doctor_price'] = $newPrice;
                                        $jsonUpdated = true;

                                        \\App\\Models\\DoctorRequestPriceLog::create([
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
"""

# Replace block in applyToWebsiteDoctor
website_find = """
                if ($updated) {
                    $doctor->save();
                }
            }
        });
    }

    private function applyToFreelancers()
"""
website_replace = """
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
                                        $pricingRow['modes'][$mode]['website_price'] = $newPrice;
                                        $jsonUpdated = true;

                                        \\App\\Models\\DoctorRequestPriceLog::create([
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
"""

content = content.replace(payout_find, payout_replace)
content = content.replace(website_find, website_replace)

with open(file_path, "w") as f:
    f.write(content)

print("Done")
