<?php
$file = 'app/Jobs/ApplyBulkPricingRuleJob.php';
$content = file_get_contents($file);

$content = str_replace(
    'if (isset($s[\'id\'])) $subIds[] = (int)$s[\'id\'];',
    'if (isset($s[\'sub_service_id\'])) { $subIds[] = (int)$s[\'sub_service_id\']; } elseif (isset($s[\'id\'])) { $subIds[] = (int)$s[\'id\']; }',
    $content
);

file_put_contents($file, $content);
echo "Fixed sub_service_id key extraction\n";
