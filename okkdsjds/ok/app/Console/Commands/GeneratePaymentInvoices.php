<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\OperationLead;
use App\Services\PaymentInvoiceService;

class GeneratePaymentInvoices extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'invoices:generate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically generate payment invoices for ongoing operation leads based on payment plan';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting automatic payment invoice generation...');

        // Get all ongoing leads that have a payment plan
        $ongoingLeads = OperationLead::where('ongoing_stopped', 'ongoing')
            ->whereNotNull('payment_plan')
            ->where('payment_plan', '!=', '')
            ->get();

        $this->info("Found {$ongoingLeads->count()} ongoing leads with payment plans.");

        $generatedCount = 0;

        foreach ($ongoingLeads as $lead) {
            try {
                // Check and generate next invoices if needed
                PaymentInvoiceService::checkAndGenerateNextInvoices($lead);
                $generatedCount++;
                
                $this->line("✓ Processed lead #{$lead->id} - {$lead->customer_name}");
            } catch (\Exception $e) {
                $this->error("✗ Error processing lead #{$lead->id}: " . $e->getMessage());
                \Log::error("Invoice generation error for lead #{$lead->id}: " . $e->getMessage());
            }
        }

        $this->info("Completed! Processed {$generatedCount} leads.");

        return Command::SUCCESS;
    }
}
