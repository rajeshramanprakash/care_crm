<?php
require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\Leegality\LeegalityClient;
use Illuminate\Support\Facades\Log;

$client = new LeegalityClient();

$signerId = config('leegality.automated_signer_id');
$passkey = config('leegality.automated_signer_passkey');

echo "Signer ID: $signerId\n";
echo "Passkey: $passkey\n";

$dummyPdfBase64 = base64_encode(file_get_contents('/Applications/XAMPP/xamppfiles/htdocs/Office/carecrm/storage/app/leegality/preview-test.pdf'));

// Payload 1: type => AUTOMATED_SIGN (Current)
$payload1 = [
    'file' => [
        'name' => 'Test1.pdf',
        'file' => $dummyPdfBase64,
    ],
    'profileId' => config('leegality.auth_profile_id'),
    'invitees' => [
        [
            'name' => 'Ashish Ramniwas',
            'email' => 'it1.carelix@gmail.com',
            'signatures' => [
                [
                    'type' => 'AUTOMATED_SIGN',
                    'config' => [
                        'id' => $signerId,
                        'passkey' => $passkey,
                    ],
                ]
            ]
        ]
    ]
];

// Payload 2: signatureType => VIRTUAL_SIGN, automatedSign object (Alternative)
$payload2 = [
    'file' => [
        'name' => 'Test2.pdf',
        'file' => $dummyPdfBase64,
    ],
    'profileId' => config('leegality.auth_profile_id'),
    'invitees' => [
        [
            'name' => 'Ashish Ramniwas',
            'email' => 'it1.carelix@gmail.com',
            'signatureType' => 'VIRTUAL_SIGN',
            'automatedSign' => [
                'enabled' => true,
                'esignId' => $signerId,
                'password' => $passkey,
            ]
        ]
    ]
];

// Payload 3: signatures[0] => signatureType => VIRTUAL_SIGN, automatedSign object (Alternative 2)
$payload3 = [
    'file' => [
        'name' => 'Test3.pdf',
        'file' => $dummyPdfBase64,
    ],
    'profileId' => config('leegality.auth_profile_id'),
    'invitees' => [
        [
            'name' => 'Ashish Ramniwas',
            'email' => 'it1.carelix@gmail.com',
            'signatures' => [
                [
                    'signatureType' => 'VIRTUAL_SIGN',
                    'automatedSign' => [
                        'enabled' => true,
                        'esignId' => $signerId,
                        'password' => $passkey,
                    ]
                ]
            ]
        ]
    ]
];

foreach(['payload2', 'payload3'] as $idx => $pName) {
    echo "Testing $pName...\n";
    try {
        $res = $client->createSignRequest($$pName);
        echo "SUCCESS: " . json_encode($res) . "\n";
        $docId = $res['data']['documentId'] ?? null;
        if ($docId) {
            echo "Waiting 5 seconds...\n";
            sleep(5);
            $details = $client->documentDetails($docId);
            echo "Status after 5s: " . ($details['data']['documentStatus'] ?? 'Unknown') . "\n\n";
        }
    } catch (\Exception $e) {
        echo "FAILED: " . $e->getMessage() . "\n\n";
    }
}
