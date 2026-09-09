<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Lead;
use Illuminate\Support\Facades\DB;

class PopulateLastCallStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'leads:populate-last-call-status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Populate last_call_status for existing leads based on their call recordings';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting to populate last_call_status for existing leads...');

        $leads = Lead::whereNotNull('recording_url')
                    ->where('recording_url', '!=', '')
                    ->whereNull('last_call_status')
                    ->get();

        $this->info("Found {$leads->count()} leads with call recordings but no last_call_status");

        $updatedCount = 0;
        $errorCount = 0;

        foreach ($leads as $lead) {
            try {
                $lastCallStatus = $this->getLastCallStatusFromRecordings($lead->recording_url);

                if ($lastCallStatus) {
                    $lead->update(['last_call_status' => $lastCallStatus]);
                    $updatedCount++;
                    $this->line("Updated Lead ID {$lead->id}: {$lastCallStatus}");
                }
            } catch (\Exception $e) {
                $errorCount++;
                $this->error("Error processing Lead ID {$lead->id}: " . $e->getMessage());
            }
        }

        $this->info("Completed! Updated {$updatedCount} leads, {$errorCount} errors.");
    }

    /**
     * Extract the last call status from recordings
     */
    private function getLastCallStatusFromRecordings($recordingUrl)
    {
        if (empty($recordingUrl)) {
            return null;
        }

        try {
            $recordings = json_decode($recordingUrl, true);

            if (!is_array($recordings) || empty($recordings)) {
                return null;
            }

            // Get the last recording (most recent)
            $lastRecording = end($recordings);

            if (!isset($lastRecording['metadata'])) {
                return null;
            }

            $metadata = json_decode($lastRecording['metadata'], true);

            if (!is_array($metadata) || !isset($metadata['dialstatus'])) {
                return null;
            }

            return $metadata['dialstatus'];

        } catch (\Exception $e) {
            return null;
        }
    }
}
