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
     * This is called when a deployment is created with status "In Progress"
     */
    public static function generateInvoicesForDeployment(OperationDeploymentDetails $deployment)
    {
        $lead = $deployment->operationLead;

        // Only generate invoices if deployment is In Progress and lead is ongoing
        if ($deployment->deployment_status !== 'In Progress') {
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
            'remark' => 'Auto-generated invoice based on payment plan: ' . $lead->payment_plan,
        ]);

        return $invoice;
    }

    /**
     * Check and generate next invoices if needed
     * This should be called periodically (e.g., daily cron job) or when checking deployment status
     */
    public static function checkAndGenerateNextInvoices(OperationLead $lead)
    {
        // Only generate if lead is ongoing
        if ($lead->ongoing_stopped !== 'ongoing') {
            return;
        }

        // Get payment plan days
        $paymentPlanDays = $lead->getPaymentPlanDays();
        
        if ($paymentPlanDays <= 0) {
            return;
        }

        // Check if there are any In Progress deployments
        $activeDeployments = OperationDeploymentDetails::where('operation_lead_id', $lead->id)
            ->where('deployment_status', 'In Progress')
            ->get();

        if ($activeDeployments->isEmpty()) {
            return;
        }

        // Get the latest invoice
        $latestInvoice = PaymentInvoice::where('operation_lead_id', $lead->id)
            ->orderBy('to_date', 'desc')
            ->first();

        if (!$latestInvoice) {
            // No invoices yet, generate first one based on earliest deployment
            $earliestDeployment = OperationDeploymentDetails::where('operation_lead_id', $lead->id)
                ->where('deployment_status', 'In Progress')
                ->orderBy('deployment_from_date', 'asc')
                ->first();

            if ($earliestDeployment) {
                $fromDate = Carbon::parse($earliestDeployment->deployment_from_date);
                self::generateNextInvoice($lead, $fromDate, $paymentPlanDays);
            }
        } else {
            // Check if we need to generate next invoice
            $nextFromDate = Carbon::parse($latestInvoice->to_date)->addDay();
            $today = Carbon::today();

            // If the next period has already started or is about to start, generate next invoice
            if ($nextFromDate->lte($today->addDays(2))) {
                self::generateNextInvoice($lead, $nextFromDate, $paymentPlanDays);
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

