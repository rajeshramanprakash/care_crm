<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$rule = \App\Models\BulkPricingRule::create([
    'pricing_type' => 'vendor',
    'service_id' => null, // all services
    'sub_service_id' => null, // all sub-services
    'mode_type' => '24_hours', // ONLY 24 HOURS
    'change_type' => 'normal',
    'value' => 62,
    'apply_to' => 'all',
    'city_filter' => 'all',
    'selected_cities' => null,
    'time_period' => 'permanent',
    'status' => 1,
]);

\App\Jobs\ApplyBulkPricingRuleJob::dispatchSync($rule);

echo "Rule ID {$rule->id} applied successfully. All Vendor 24_hours prices should now be 62.\n";
