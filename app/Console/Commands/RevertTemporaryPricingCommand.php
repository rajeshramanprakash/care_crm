<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\BulkPricingRule;
use App\Jobs\ApplyBulkPricingRuleJob;
use Illuminate\Support\Facades\Log;

class RevertTemporaryPricingCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pricing:revert-temporary';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reverts any temporary bulk pricing rules that have passed their end date';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $expiredRules = BulkPricingRule::where('time_period', 'temporary')
            ->where('status', 1)
            ->where('time_period_end_date', '<=', now())
            ->get();

        if ($expiredRules->isEmpty()) {
            $this->info('No expired temporary pricing rules found.');
            return;
        }

        foreach ($expiredRules as $rule) {
            $this->info("Reverting rule ID: {$rule->id}");
            Log::info("RevertTemporaryPricingCommand is reverting rule ID: {$rule->id}");

            // Create a fake inverted rule to apply the reverse math
            $invertedRule = $rule->replicate();
            
            // Invert the change type
            switch ($rule->change_type) {
                case 'increase_fixed':
                    $invertedRule->change_type = 'decrease_fixed';
                    break;
                case 'decrease_fixed':
                    $invertedRule->change_type = 'increase_fixed';
                    break;
                case 'increase_percent':
                    // If original price was 100, increase by 10% makes it 110. 
                    // To go back to 100, we need to decrease 110 by roughly 9.09% (10/110)
                    // For simplicity if we can't reliably trace back percentage, this might be lossy.
                    // But we can approximate or use a custom "revert_percent" logic inside Job.
                    // For now, we will just use decrease_percent and assume the value is relative to the new price,
                    // or better, we should probably just recalculate. 
                    // To be mathematically exact: New = Old * (1 + V) => Old = New / (1 + V).
                    $invertedRule->change_type = 'revert_increase_percent';
                    break;
                case 'decrease_percent':
                    $invertedRule->change_type = 'revert_decrease_percent';
                    break;
            }

            if (in_array($rule->pricing_type, ['vendor', 'freelancer'])) {
                if ($rule->pricing_type === 'vendor') {
                    $q = \App\Models\VendorServicePrice::query();
                    if ($rule->service_id) $q->where('service_id', $rule->service_id);
                    if ($rule->sub_service_id) $q->where('service_sub_service_id', $rule->sub_service_id);
                    
                    $q->chunkById(100, function ($prices) {
                        foreach ($prices as $p) {
                            $updated = false;
                            if ($p->original_price_12hr !== null) { $p->price_12hr = $p->original_price_12hr; $p->original_price_12hr = null; $updated = true; }
                            if ($p->original_price_24hr !== null) { $p->price_24hr = $p->original_price_24hr; $p->original_price_24hr = null; $updated = true; }
                            if ($p->original_price_onetime !== null) { $p->price_onetime = $p->original_price_onetime; $p->original_price_onetime = null; $updated = true; }
                            if ($updated) $p->save();
                        }
                    });
                } else if ($rule->pricing_type === 'freelancer') {
                    $q = \App\Models\JobRequestServicePrice::query();
                    if ($rule->service_id) $q->where('service_id', $rule->service_id);
                    if ($rule->sub_service_id) $q->where('service_sub_service_id', $rule->sub_service_id);
                    
                    $q->chunkById(100, function ($prices) {
                        foreach ($prices as $p) {
                            $updated = false;
                            if ($p->original_price_12hr !== null) { $p->price_12hr = $p->original_price_12hr; $p->original_price_12hr = null; $updated = true; }
                            if ($p->original_price_24hr !== null) { $p->price_24hr = $p->original_price_24hr; $p->original_price_24hr = null; $updated = true; }
                            if ($p->original_price_onetime !== null) { $p->price_onetime = $p->original_price_onetime; $p->original_price_onetime = null; $updated = true; }
                            if ($updated) $p->save();
                        }
                    });
                }
            } else if ($rule->pricing_type === 'website_doctor') {
                $q = \App\Models\LocationDoctorConsultationPrice::query();
                if ($rule->service_id) $q->where('doctor_consultation_service_id', $rule->service_id);
                if ($rule->sub_service_id) $q->where('doctor_consultation_service_sub_service_id', $rule->sub_service_id);
                
                $q->chunkById(100, function ($prices) {
                    foreach ($prices as $p) {
                        $updated = false;
                        if ($p->original_website_price !== null) { $p->website_price = $p->original_website_price; $p->original_website_price = null; $updated = true; }
                        if ($p->original_doctor_max_price !== null) { $p->doctor_max_price = $p->original_doctor_max_price; $p->original_doctor_max_price = null; $updated = true; }
                        if ($updated) $p->save();
                    }
                });

                $docQ = \App\Models\DoctorRequest::query()->where('approval_status', 'Approved');
                if ($rule->service_id) {
                    $docSvc = \App\Models\DoctorConsultationService::find($rule->service_id);
                    if ($docSvc) $docQ->where('job_title', $docSvc->name);
                }
                $docQ->chunkById(100, function ($doctors) {
                    foreach ($doctors as $d) {
                        $updated = false;
                        $cols = [
                            'website_customer_fee_online',
                            'website_customer_fee_home_visit',
                            'website_customer_fee_clinic'
                        ];
                        foreach ($cols as $c) {
                            $origCol = 'original_' . $c;
                            if ($d->{$origCol} !== null) {
                                $d->{$c} = $d->{$origCol};
                                $d->{$origCol} = null;
                                $updated = true;
                            }
                        }
                        
                        $pricingArr = $d->consultation_pricing;
                        if (is_array($pricingArr)) {
                            $jsonUpdated = false;
                            foreach ($pricingArr as &$row) {
                                if (isset($row['modes']) && is_array($row['modes'])) {
                                    foreach (['online', 'home_visit', 'clinic_visit'] as $m) {
                                        if (isset($row['modes'][$m]['original_website_price'])) {
                                            $row['modes'][$m]['website_price'] = $row['modes'][$m]['original_website_price'];
                                            unset($row['modes'][$m]['original_website_price']);
                                            $jsonUpdated = true;
                                        }
                                    }
                                }
                            }
                            if ($jsonUpdated) {
                                $d->consultation_pricing = $pricingArr;
                                $updated = true;
                            }
                        }
                        if ($updated) $d->save();
                    }
                });
            } else if ($rule->pricing_type === 'doctor_payout') {
                $docQ = \App\Models\DoctorRequest::query()->where('approval_status', 'Approved');
                if ($rule->service_id) {
                    $docSvc = \App\Models\DoctorConsultationService::find($rule->service_id);
                    if ($docSvc) $docQ->where('job_title', $docSvc->name);
                }
                $docQ->chunkById(100, function ($doctors) {
                    foreach ($doctors as $d) {
                        $updated = false;
                        $cols = [
                            'online_charges',
                            'home_visit_charges',
                            'clinic_consultation_charges'
                        ];
                        foreach ($cols as $c) {
                            $origCol = 'original_' . $c;
                            if ($d->{$origCol} !== null) {
                                $d->{$c} = $d->{$origCol};
                                $d->{$origCol} = null;
                                $updated = true;
                            }
                        }
                        
                        $pricingArr = $d->consultation_pricing;
                        if (is_array($pricingArr)) {
                            $jsonUpdated = false;
                            foreach ($pricingArr as &$row) {
                                if (isset($row['modes']) && is_array($row['modes'])) {
                                    foreach (['online', 'home_visit', 'clinic_visit'] as $m) {
                                        if (isset($row['modes'][$m]['original_doctor_price'])) {
                                            $row['modes'][$m]['doctor_price'] = $row['modes'][$m]['original_doctor_price'];
                                            unset($row['modes'][$m]['original_doctor_price']);
                                            $jsonUpdated = true;
                                        }
                                    }
                                }
                            }
                            if ($jsonUpdated) {
                                $d->consultation_pricing = $pricingArr;
                                $updated = true;
                            }
                        }
                        if ($updated) $d->save();
                    }
                });
            } else if ($rule->pricing_type === 'website_general') {
                $q = \Illuminate\Support\Facades\DB::table('location_services');
                if ($rule->service_id) $q->where('service_id', $rule->service_id);
                if ($rule->sub_service_id) $q->where('service_sub_service_id', $rule->sub_service_id);
                
                $q->orderBy('id')->chunk(200, function ($prices) {
                    foreach ($prices as $p) {
                        $updateData = [];
                        if (isset($p->original_price_12hr)) {
                            $updateData['price_12hr'] = $p->original_price_12hr;
                            $updateData['original_price_12hr'] = null;
                        }
                        if (isset($p->original_price_24hr)) {
                            $updateData['price_24hr'] = $p->original_price_24hr;
                            $updateData['original_price_24hr'] = null;
                        }
                        if (isset($p->original_price_onetime)) {
                            $updateData['price_onetime'] = $p->original_price_onetime;
                            $updateData['original_price_onetime'] = null;
                        }
                        if (!empty($updateData)) {
                            \Illuminate\Support\Facades\DB::table('location_services')->where('id', $p->id)->update($updateData);
                        }
                    }
                });
            }

            // Mark rule as inactive
            $rule->update(['status' => 0]);
        }

        $this->info('Successfully reverted expired pricing rules.');
    }
}
