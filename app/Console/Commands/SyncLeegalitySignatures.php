<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\DoctorLeegalitySignature;
use App\Services\Leegality\DoctorLeegalitySignatureService;

class SyncLeegalitySignatures extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'leegality:sync-status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Pulls the latest status for pending Leegality signatures and processes them like a webhook';

    /**
     * Execute the console command.
     */
    public function handle(DoctorLeegalitySignatureService $service)
    {
        $pendingSignatures = DoctorLeegalitySignature::whereIn('signature_status', ['SENT', 'VIEWED'])
            ->whereNotNull('leegality_document_id')
            ->get();

        if ($pendingSignatures->isEmpty()) {
            $this->info("No pending signatures found.");
            return;
        }

        $this->info("Found {$pendingSignatures->count()} pending signatures. Syncing from Leegality API...");

        foreach ($pendingSignatures as $signature) {
            try {
                $docId = $signature->leegality_document_id;
                $this->line("Checking Document ID: {$docId} ...");

                // Fetch real details from Leegality
                $details = $service->client->documentDetails($docId);
                
                $data = $details['data'] ?? [];
                $status = $data['document']['status'] ?? null;
                
                if (!$status) {
                    $this->error("  Failed to get status for {$docId}");
                    continue;
                }

                $this->info("  Status from Leegality is: {$status}");

                // If it's Completed, let's simulate a webhook payload to process it fully
                if (strcasecmp($status, 'Completed') === 0 || strcasecmp($status, 'Signed') === 0) {
                    $payload = [
                        'documentId' => $docId,
                        'documentStatus' => 'Completed',
                        'request' => [
                            'action' => 'SIGNED',
                            'email' => $data['invitations'][0]['email'] ?? '',
                            'name' => $data['invitations'][0]['name'] ?? 'Signer',
                        ],
                    ];

                    $result = $service->handleWebhook($payload);
                    if ($result) {
                        $this->info("  -> Successfully processed and assigned Agreement: " . ($result->doctorRequest->agreement_number ?? 'N/A'));
                    }
                }

            } catch (\Exception $e) {
                $this->error("  Error checking {$docId}: " . $e->getMessage());
            }
        }

        $this->info("Sync complete!");
    }
}
