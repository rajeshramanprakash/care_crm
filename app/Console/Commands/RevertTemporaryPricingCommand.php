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
            ->whereDate('time_period_end_date', '<', now())
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

            // Run the job synchronously to revert
            ApplyBulkPricingRuleJob::dispatchSync($invertedRule);

            // Mark rule as inactive
            $rule->update(['status' => 0]);
        }

        $this->info('Successfully reverted expired pricing rules.');
    }
}
