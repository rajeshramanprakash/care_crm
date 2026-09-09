<?php

namespace App\Services;

use App\Models\PaymentInvoice;
use App\Models\OperationLead;
use App\Models\OperationDeploymentDetails;
use Carbon\Carbon;

class PaymentInvoiceService
{
    /**
     * Generate payment invoices for a deployment
     * This is called when a deployment is created with status "In Progress" or "Pending"
     */
    public static function generateInvoicesForDeployment(OperationDeploymentDetails $deployment)
    {
        $lead = $deployment->operationLead;

        // Generate invoices if deployment is In Progress or Pending, and lead is ongoing
        $allowedStatuses = ['In Progress', 'Pending'];
        if (!in_array($deployment->deployment_status, $allowedStatuses)) {
            return;
        }

        if ($lead->ongoing_stopped !== 'ongoing') {
            return;
        }

        // Get payment plan days
        $paymentPlanDays = $lead->getPaymentPlanDays();
        
        if ($paymentPlanDays <= 0) {
            return; // No payment plan set
        }

        // Get the from_date from deployment
        $fromDate = Carbon::parse($deployment->deployment_from_date);
        
        // Generate invoices automatically based on payment plan
        self::generateNextInvoice($lead, $fromDate, $paymentPlanDays);
    }

    /**
     * Generate next invoice for the lead
     */
    public static function generateNextInvoice(OperationLead $lead, Carbon $startDate, int $paymentPlanDays)
    {
        // Check if an invoice already exists for this date range
        $existingInvoice = PaymentInvoice::where('operation_lead_id', $lead->id)
            ->where('from_date', '<=', $startDate)
            ->where('to_date', '>=', $startDate)
            ->first();

        if ($existingInvoice) {
            return; // Invoice already exists for this period
        }

        // Calculate to_date based on payment plan
        $toDate = $startDate->copy()->addDays($paymentPlanDays - 1); // Subtract 1 because we include the start date

        // Calculate work days (including both start and end dates)
        $workDays = $paymentPlanDays;

        // Calculate payment amount based on closed_rate per day
        $closedRate = floatval($lead->closed_rate ?? 0);
        $paymentAmount = $workDays * $closedRate;

        // Generate invoice
        $invoice = PaymentInvoice::create([
            'operation_lead_id' => $lead->id,
            'invoice_id' => PaymentInvoice::generateInvoiceId(),
            'from_date' => $startDate,
            'to_date' => $toDate,
            'payment_amount' => $paymentAmount,
            'is_received' => false,
            'work_days' => $workDays,
            'remark' => 'Auto-generated invoice based on payment plan: ' . ($lead->payment_plan ?: 'from deployment payment term'),
        ]);

        return $invoice;
    }

    /**
     * Check and generate next invoices if needed
     * This should be called periodically (e.g., daily cron job) or when checking deployment status
     */
    public static function checkAndGenerateNextInvoices(OperationLead $lead)
    {
        // Check if there are any In Progress or Pending deployments
        $activeDeployments = OperationDeploymentDetails::where('operation_lead_id', $lead->id)
            ->whereIn('deployment_status', ['In Progress', 'Pending'])
            ->get();

        if ($activeDeployments->isEmpty()) {
            return;
        }

        // Get payment plan days from lead's payment_plan, or fallback to earliest deployment's payment_term
        $paymentPlanDays = $lead->getPaymentPlanDays();
        if ($paymentPlanDays <= 0) {
            $earliest = $activeDeployments->sortBy('deployment_from_date')->first();
            if ($earliest && !empty($earliest->payment_term)) {
                $paymentPlanDays = \App\Models\OperationLead::parsePaymentTermToDays($earliest->payment_term);
            }
        }
        if ($paymentPlanDays <= 0) {
            return;
        }

        // Ensure each deployment has an invoice covering its deployment_from_date
        // (Do this even when lead is not "ongoing" or already has invoices - so new deployments still get invoices)
        // (fixes: multiple deployments on same lead - only earliest was getting an invoice)
        $deploymentsWithFromDate = OperationDeploymentDetails::where('operation_lead_id', $lead->id)
            ->whereIn('deployment_status', ['In Progress', 'Pending'])
            ->whereNotNull('deployment_from_date')
            ->orderBy('deployment_from_date', 'asc')
            ->get();

        foreach ($deploymentsWithFromDate as $deployment) {
            $fromDate = Carbon::parse($deployment->deployment_from_date);
            $covered = PaymentInvoice::where('operation_lead_id', $lead->id)
                ->where('from_date', '<=', $fromDate)
                ->where('to_date', '>=', $fromDate)
                ->exists();
            if (!$covered) {
                self::generateNextInvoice($lead, $fromDate, $paymentPlanDays);
            }
        }

        // Also generate the "next" period after the latest invoice if due (only for ongoing leads)
        if ($lead->ongoing_stopped === 'ongoing') {
            $latestInvoice = PaymentInvoice::where('operation_lead_id', $lead->id)
                ->orderBy('to_date', 'desc')
                ->first();

            if ($latestInvoice) {
                $nextFromDate = Carbon::parse($latestInvoice->to_date)->addDay();
                $today = Carbon::today();

                if ($nextFromDate->lte($today->addDays(2))) {
                    self::generateNextInvoice($lead, $nextFromDate, $paymentPlanDays);
                }
            }
        }
    }

    /**
     * Update invoice status when payment is received
     */
    public static function updateInvoiceStatus(PaymentInvoice $invoice)
    {
        $totalReceived = $invoice->receivedPayments()->sum('amount');
        
        // Mark as received if total received >= payment amount
        if ($totalReceived >= $invoice->payment_amount) {
            $invoice->update(['is_received' => true]);
        } else {
            $invoice->update(['is_received' => false]);
        }
    }
}

