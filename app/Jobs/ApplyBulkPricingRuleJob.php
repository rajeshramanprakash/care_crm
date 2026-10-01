<?php

namespace App\Jobs;

use App\Models\BulkPricingRule;
use App\Services\BulkPricing\BulkPricingRuleApplier;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ApplyBulkPricingRuleJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 900;

    public function __construct(public BulkPricingRule $rule)
    {
    }

    public function handle(): void
    {
        $rule = $this->rule->fresh();
        if (! $rule || ! in_array($rule->state, [BulkPricingRule::STATE_SCHEDULED, BulkPricingRule::STATE_ACTIVE], true) || $rule->applied_at) {
            return;
        }

        Log::info("Applying bulk pricing rule #{$rule->id}");

        try {
            $count = (new BulkPricingRuleApplier())->apply($rule);
        } catch (\Throwable $e) {
            report($e);
            $rule->update([
                'state' => BulkPricingRule::STATE_FAILED,
                'status' => 0,
                'error_message' => mb_substr($e->getMessage(), 0, 2000),
            ]);

            return;
        }

        $keepsRunning = $rule->isTemporary()
            || ($rule->apply_to === 'new' && (! $rule->apply_to_date || $rule->apply_to_date->isFuture()));

        $rule->update([
            'state' => $keepsRunning ? BulkPricingRule::STATE_ACTIVE : BulkPricingRule::STATE_COMPLETED,
            'status' => $keepsRunning ? 1 : 0,
            'applied_at' => now(),
            'affected_count' => $count,
            'error_message' => null,
        ]);

        Log::info("Bulk pricing rule #{$rule->id} applied, {$count} price(s) changed");
    }
}
