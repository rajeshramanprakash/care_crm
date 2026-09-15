<?php
// Fix VendorServiceSync
$vendorFile = 'app/Services/VendorServiceSync.php';
$vendorContent = file_get_contents($vendorFile);

$mergeMethod = <<<'METHOD'
    /**
     * @param list<array<string, mixed>> $blocks
     * @return list<array<string, mixed>>
     */
    private static function mergeServicePricesIntoBlocks(Vendor $vendor, array $blocks): array
    {
        $overrides = \App\Models\VendorServicePrice::query()
            ->where('vendor_id', $vendor->id)
            ->get()
            ->groupBy('service_id');

        foreach ($blocks as &$block) {
            $serviceId = (int)($block['service_id'] ?? 0);
            if (!isset($overrides[$serviceId])) continue;

            $serviceOverrides = $overrides[$serviceId]->keyBy('service_sub_service_id');
            $mergedOverrides = [];
            
            // Collect all sub-services from the block
            $subIds = [0]; // Main service
            foreach ($block['sub_services'] ?? [] as $sub) {
                if (isset($sub['sub_service_id'])) {
                    $subIds[] = (int)$sub['sub_service_id'];
                }
            }

            foreach ($subIds as $subId) {
                if (isset($serviceOverrides[$subId])) {
                    $row = $serviceOverrides[$subId];
                    $mergedOverrides[] = [
                        'service_sub_service_id' => $subId,
                        'price_12hr' => $row->price_12hr,
                        'price_24hr' => $row->price_24hr,
                        'price_onetime' => $row->price_onetime,
                    ];
                }
            }

            $block['price_overrides'] = $mergedOverrides;
        }

        return $blocks;
    }
METHOD;

if (strpos($vendorContent, 'mergeServicePricesIntoBlocks') === false) {
    // Insert before the last closing brace
    $vendorContent = preg_replace('/}\s*$/', "\n" . $mergeMethod . "\n}\n", $vendorContent);
}

// Now replace returns in blocksForVendor
// Replace `return self::normalizeBlocks($stored);` with `$blocks = self::normalizeBlocks($stored); return self::mergeServicePricesIntoBlocks($vendor, $blocks);`
$vendorContent = str_replace(
    'return self::normalizeBlocks($stored);',
    '$blocks = self::normalizeBlocks($stored); return self::mergeServicePricesIntoBlocks($vendor, $blocks);',
    $vendorContent
);

// Replace `return [[...]];` with `$blocks = [[...]]; return self::mergeServicePricesIntoBlocks($vendor, $blocks);`
$vendorContent = preg_replace(
    "/(return\s*\[\s*\[\s*'service_id'[^\]]+\]\s*\];)/s",
    "\$blocks = $1\n                    return self::mergeServicePricesIntoBlocks(\$vendor, \$blocks);",
    $vendorContent
);
$vendorContent = str_replace("\$blocks = return", "\$blocks =", $vendorContent);

// Replace final `return $blocks;` with `return self::mergeServicePricesIntoBlocks($vendor, $blocks);`
$vendorContent = preg_replace(
    "/(public static function blocksForVendor\(Vendor \\\$vendor\): array\s*{.*?)(return \\\$blocks;)(\s*})/s",
    "$1return self::mergeServicePricesIntoBlocks(\$vendor, \$blocks);$3",
    $vendorContent
);

file_put_contents($vendorFile, $vendorContent);
echo "VendorServiceSync updated.\n";
