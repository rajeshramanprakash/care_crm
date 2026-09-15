<?php
$vendorFile = 'app/Services/VendorServiceSync.php';
$vendorContent = file_get_contents($vendorFile);

$vendorContent = str_replace(
    'return self::mergeServicePricesIntoBlocks($vendor, $blocks);
    }',
    'return $blocks;
    }',
    $vendorContent
);

file_put_contents($vendorFile, $vendorContent);
echo "Fixed infinite recursion.\n";
