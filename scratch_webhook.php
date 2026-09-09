<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$service = app(\App\Services\Leegality\DoctorLeegalitySignatureService::class);

$payload = [
    'documentId' => '01M1ZMQMCF07AFR05EPPQ8TZ47',
    'documentStatus' => 'Completed',
    'request' => [
        'action' => 'SIGNED',
        'email' => 'test@example.com',
        'name' => 'Dr. Test',
    ],
];

echo "Simulating Webhook...\n";
try {
    $result = $service->handleWebhook($payload);
    if ($result) {
        echo "Success!\n";
        echo "Agreement Number: {$result->doctorRequest->agreement_number}\n";
        echo "Partner ID: {$result->doctorRequest->partner_id}\n";
    } else {
        echo "Failed.\n";
    }
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
