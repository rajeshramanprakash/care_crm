<?php

namespace App\Console\Commands;

use App\Jobs\ApplyBulkPricingRuleJob;
use App\Models\BulkPricingRule;
use App\Services\BulkPricing\BulkPricingRuleApplier;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class RevertTemporaryPricingCommand extends Command
{
    protected $signature = 'pricing:revert-temporary';

    protected $description = 'Starts scheduled bulk pricing rules, reverts expired temporary rules and closes finished "new registrations" rules';

    public function handle(): int
    {
        // Runs from both the scheduler and the page-load fallback in AppServiceProvider.
        $lock = Cache::lock('bulk-pricing-revert-temporary', 300);
        if (! $lock->get()) {
            return self::SUCCESS;
        }

        try {
            return $this->process();
        } finally {
            $lock->release();
        }
    }

    private function process(): int
    {
        $now = now();

        $scheduled = BulkPricingRule::query()
            ->where('state', BulkPricingRule::STATE_SCHEDULED)
            ->where('time_period_start_date', '<=', $now)
            ->get();
        foreach ($scheduled as $rule) {
            if ($rule->time_period_end_date && $rule->time_period_end_date->lte($now)) {
                $rule->update([
                    'state' => BulkPricingRule::STATE_COMPLETED,
                    'status' => 0,
                    'error_message' => 'Temporary period ended before the rule could start.',
                ]);
                continue;
            }
            $this->info("Starting rule #{$rule->id}");
            ApplyBulkPricingRuleJob::dispatchSync($rule);
        }

        $expired = BulkPricingRule::query()
            ->where('state', BulkPricingRule::STATE_ACTIVE)
            ->where('time_period', 'temporary')
            ->where('time_period_end_date', '<=', $now)
            ->get();
        foreach ($expired as $rule) {
            $this->info("Reverting rule #{$rule->id}");
            try {
                $count = (new BulkPricingRuleApplier())->revert($rule);
                $rule->update([
                    'state' => BulkPricingRule::STATE_REVERTED,
                    'status' => 0,
                    'reverted_at' => now(),
                ]);
                Log::info("Bulk pricing rule #{$rule->id} reverted, {$count} price(s) restored");
            } catch (\Throwable $e) {
                report($e);
                $this->error("Rule #{$rule->id} revert failed: ".$e->getMessage());
            }
        }

        BulkPricingRule::query()
            ->where('state', BulkPricingRule::STATE_ACTIVE)
            ->where('time_period', 'permanent')
            ->where('apply_to', 'new')
            ->whereNotNull('apply_to_date')
            ->where('apply_to_date', '<=', $now)
            ->update(['state' => BulkPricingRule::STATE_COMPLETED, 'status' => 0]);

        return self::SUCCESS;
    }
}
