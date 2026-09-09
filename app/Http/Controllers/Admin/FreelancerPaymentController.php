<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\JobRequest;
use App\Models\FreelancerPayment;
use App\Models\OperationDeploymentDetails;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class FreelancerPaymentController extends Controller
{
    public function index()
    {
        // Get all active job requests (freelancers) that have verified payments
        $jobRequests = JobRequest::where('status', 'active')->get();
        
        // Filter to only show freelancers with verified payments from deployments
        $freelancerIds = OperationDeploymentDetails::whereNotNull('freelance_staff_id')
            ->where('verify_payment', true)
            ->distinct()
            ->pluck('freelance_staff_id')
            ->toArray();
        
        $freelancers = $jobRequests->whereIn('id', $freelancerIds);
        
        // Load payment data for each freelancer
        $freelancerIds = $freelancers->pluck('id');
        $allPayments = FreelancerPayment::whereIn('job_request_id', $freelancerIds)
            ->where('status', 'completed')
            ->get()
            ->groupBy('job_request_id');
        
        $freelancers = $freelancers->map(function($freelancer) use ($allPayments) {
            $payments = $allPayments->get($freelancer->id, collect());
            $freelancer->total_paid = $payments->sum('amount');
            $freelancer->payments = $payments;
            $lastPayment = $payments->sortByDesc('payment_date')->first();
            $freelancer->last_payment_date = $lastPayment ? $lastPayment->payment_date : null;
            return $freelancer;
        });

        return view('admin.freelancer_payments.index', compact('freelancers'));
    }

    public function indexApi()
    {
        // Get all active job requests (freelancers)
        $jobRequests = JobRequest::where('status', 'active')->get();
        
        // Get all freelancer IDs that have payments (regardless of deployment verification)
        $freelancerIdsWithPayments = FreelancerPayment::distinct()
            ->pluck('job_request_id')
            ->toArray();
        
        // Also include freelancers with verified payments from deployments
        $freelancerIdsFromDeployments = OperationDeploymentDetails::whereNotNull('freelance_staff_id')
            ->where('verify_payment', true)
            ->distinct()
            ->pluck('freelance_staff_id')
            ->toArray();
        
        // Combine both lists - show freelancers who have payments OR verified deployments
        $allFreelancerIds = array_unique(array_merge($freelancerIdsWithPayments, $freelancerIdsFromDeployments));
        
        // If no freelancers found, return empty array
        if (empty($allFreelancerIds)) {
            return response()->json([
                'freelancers' => []
            ]);
        }
        
        // Filter freelancers to only those with payments or verified deployments
        $freelancers = $jobRequests->whereIn('id', $allFreelancerIds);
        $freelancerIds = $freelancers->pluck('id')->toArray();
        
        // Get all payments for these freelancers in one query
        $allPayments = FreelancerPayment::whereIn('job_request_id', $freelancerIds)
            ->get()
            ->groupBy('job_request_id');
        
        $freelancersData = $freelancers->map(function($freelancer) use ($allPayments) {
            $payments = $allPayments->get($freelancer->id, collect());
            
            // Calculate total paid from completed payments
            $totalPaid = $payments->where('status', 'completed')->sum('amount');
            $lastPayment = $payments->sortByDesc('payment_date')->first();
            
            // Format last payment date
            $lastPaymentDate = null;
            if ($lastPayment && $lastPayment->payment_date) {
                $lastPaymentDate = is_string($lastPayment->payment_date) 
                    ? $lastPayment->payment_date 
                    : $lastPayment->payment_date->format('Y-m-d');
            }
            
            return [
                'id' => $freelancer->id,
                'name' => $freelancer->name,
                'contact_no' => $freelancer->contact_no,
                'email' => $freelancer->email ?? null,
                'total_paid' => $totalPaid,
                'payments' => $payments->toArray(),
                'last_payment_date' => $lastPaymentDate,
            ];
        })->values(); // Use values() to reset array keys

        return response()->json([
            'freelancers' => $freelancersData
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'job_request_id' => 'required|exists:job_requests,id',
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|in:bank_transfer,upi,cash,cheque',
            'transaction_id' => 'nullable|string|max:100',
            'reference_number' => 'nullable|string|max:100',
            'description' => 'nullable|string|max:500',
            'deployment_detail_ids' => 'nullable|array',
            'deployment_detail_ids.*' => 'integer|exists:operation_deployment_details,id',
            'payment_date' => 'required|date|before_or_equal:today',
            'screenshot' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $data = $request->only([
                'job_request_id', 'amount', 'payment_method', 'transaction_id',
                'reference_number', 'description', 'payment_date', 'deployment_detail_ids'
            ]);
            $data['created_by'] = auth()->id();
            $data['status'] = 'completed';
            if (isset($data['deployment_detail_ids']) && is_array($data['deployment_detail_ids'])) {
                $data['deployment_detail_ids'] = array_values(array_map('intval', $data['deployment_detail_ids']));
            } else {
                $data['deployment_detail_ids'] = null;
            }

            // Handle screenshot upload
            if ($request->hasFile('screenshot')) {
                $screenshot = $request->file('screenshot');
                $filename = 'freelancer_payment_' . time() . '_' . $screenshot->getClientOriginalName();
                $path = $screenshot->storeAs('freelancer_payments', $filename, 'public');
                $data['screenshot'] = $path;
            }

            FreelancerPayment::create($data);

            return response()->json([
                'status' => 'success',
                'message' => 'Payment recorded successfully!'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error recording payment: ' . $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        $payment = FreelancerPayment::with(['jobRequest', 'createdBy'])->findOrFail($id);
        
        return response()->json([
            'id' => $payment->id,
            'job_request_id' => $payment->job_request_id,
            'amount' => $payment->amount,
            'payment_method' => $payment->payment_method,
            'payment_date' => $payment->payment_date,
            'transaction_id' => $payment->transaction_id,
            'reference_number' => $payment->reference_number,
            'description' => $payment->description,
            'status' => $payment->status,
            'screenshot' => $payment->screenshot,
            'formatted_payment_date' => $payment->formatted_payment_date,
            'payment_method_text' => $payment->payment_method_text,
            'status_badge' => $payment->status_badge,
            'jobRequest' => $payment->jobRequest,
            'created_by' => $payment->createdBy,
        ]);
    }

    public function edit($id)
    {
        $payment = FreelancerPayment::with('jobRequest')->findOrFail($id);

        // Format the payment date for the date input field
        $payment->payment_date = $payment->payment_date->format('Y-m-d');

        return response()->json([
            'payment' => $payment
        ]);
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'job_request_id' => 'required|exists:job_requests,id',
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|in:bank_transfer,upi,cash,cheque',
            'transaction_id' => 'nullable|string|max:100',
            'reference_number' => 'nullable|string|max:100',
            'description' => 'nullable|string|max:500',
            'payment_date' => 'required|date',
            'status' => 'required|in:pending,completed,failed',
            'screenshot' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $payment = FreelancerPayment::findOrFail($id);
            $data = $request->all();

            // Handle screenshot upload
            if ($request->hasFile('screenshot')) {
                // Delete old screenshot if exists
                if ($payment->screenshot) {
                    Storage::disk('public')->delete($payment->screenshot);
                }

                $screenshot = $request->file('screenshot');
                $filename = 'freelancer_payment_' . time() . '_' . $screenshot->getClientOriginalName();
                $path = $screenshot->storeAs('freelancer_payments', $filename, 'public');
                $data['screenshot'] = $path;
            }

            $payment->update($data);

            return response()->json([
                'status' => 'success',
                'message' => 'Payment updated successfully!'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error updating payment: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $payment = FreelancerPayment::findOrFail($id);

            // Delete screenshot if exists
            if ($payment->screenshot) {
                Storage::disk('public')->delete($payment->screenshot);
            }

            $payment->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Payment deleted successfully!'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error deleting payment: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show full statement for a freelancer: debit rows (verified deployments), absent adjustments, credit rows (payments).
     */
    public function statement($freelancerId)
    {
        $freelancer = JobRequest::findOrFail($freelancerId);

        // 1) Debit rows: one per verified deployment where this freelancer is staff
        $allVerified = OperationDeploymentDetails::with(['operationLead', 'freelanceStaff'])
            ->where('freelance_staff_id', $freelancerId)
            ->where('verify_payment', true)
            ->orderBy('deployment_from_date')
            ->orderBy('id')
            ->get();

        $statementRows = [];
        foreach ($allVerified as $item) {
            $debitAmount = (float) ($item->vendor_payment ?? 0);
            $rowDate = $item->payment_verified_at ?? $item->updated_at ?? $item->deployment_to_date ?? $item->created_at;
            $rowTime = $item->payment_verified_at ?? $item->updated_at ?? $item->created_at;
            $statementRows[] = (object) [
                'type' => 'debit',
                'payment' => null,
                'item' => $item,
                'adjustment' => null,
                'debit_amount' => $debitAmount,
                'credit_amount' => 0,
                'row_date' => $rowDate,
                'row_time' => $rowTime,
            ];
        }

        // 2) Absent-adjustment rows
        $adjustments = \App\Models\DeploymentAbsentAdjustment::with(['operationDeploymentDetail.operationLead'])
            ->whereHas('operationDeploymentDetail', function ($q) use ($freelancerId) {
                $q->where('freelance_staff_id', $freelancerId);
            })
            ->orderBy('adjusted_at')
            ->orderBy('id')
            ->get();

        foreach ($adjustments as $adj) {
            $detail = $adj->operationDeploymentDetail;
            if (!$detail) {
                continue;
            }
            $statementRows[] = (object) [
                'type' => 'absent_credit',
                'payment' => null,
                'item' => $detail,
                'adjustment' => $adj,
                'debit_amount' => 0,
                'credit_amount' => (float) $adj->amount_deducted,
                'row_date' => $adj->adjusted_at,
                'row_time' => $adj->adjusted_at,
            ];
        }

        // 3) Credit rows: one per payment
        $paidPayments = FreelancerPayment::with(['jobRequest', 'createdBy'])
            ->where('job_request_id', $freelancerId)
            ->where('status', 'completed')
            ->orderBy('payment_date')
            ->orderBy('id')
            ->get();

        foreach ($paidPayments as $payment) {
            $ids = $payment->deployment_detail_ids;
            $invoiceNos = is_array($ids) ? array_values(array_unique(array_map('intval', $ids))) : [];
            $statementRows[] = (object) [
                'type' => 'credit',
                'payment' => $payment,
                'item' => null,
                'adjustment' => null,
                'invoice_nos' => $invoiceNos,
                'debit_amount' => 0,
                'credit_amount' => (float) $payment->amount,
                'row_date' => $payment->payment_date,
                'row_time' => $payment->created_at,
            ];
        }

        $totalEarned = (float) $allVerified->sum('vendor_payment');
        $totalPaid = (float) $paidPayments->sum('amount');
        $totalAbsentDeducted = (float) $adjustments->sum('amount_deducted');
        $balanceAmount = round($totalEarned - $totalPaid - $totalAbsentDeducted, 2);

        usort($statementRows, function ($a, $b) {
            $t1 = $a->row_date ? \Carbon\Carbon::parse($a->row_date)->timestamp : 0;
            $t2 = $b->row_date ? \Carbon\Carbon::parse($b->row_date)->timestamp : 0;
            if ($t1 !== $t2) {
                return $t1 <=> $t2;
            }
            $time1 = $a->row_time ? \Carbon\Carbon::parse($a->row_time)->format('His') : '0';
            $time2 = $b->row_time ? \Carbon\Carbon::parse($b->row_time)->format('His') : '0';
            if ($time1 !== $time2) {
                return $time1 <=> $time2;
            }
            $order = ['debit' => 0, 'absent_credit' => 1, 'credit' => 2];
            return ($order[$a->type] ?? 1) <=> ($order[$b->type] ?? 1);
        });

        return view('admin.freelancer_payments.statement', compact(
            'freelancer',
            'statementRows',
            'totalEarned',
            'totalPaid',
            'balanceAmount'
        ));
    }

    /**
     * API: Return statement HTML for app (WebView / in-app display, optional PDF download).
     */
    public function statementApi($freelancerId)
    {
        $freelancer = JobRequest::findOrFail($freelancerId);

        $allVerified = OperationDeploymentDetails::with(['operationLead', 'freelanceStaff'])
            ->where('freelance_staff_id', $freelancerId)
            ->where('verify_payment', true)
            ->orderBy('deployment_from_date')
            ->orderBy('id')
            ->get();

        $statementRows = [];
        foreach ($allVerified as $item) {
            $debitAmount = (float) ($item->vendor_payment ?? 0);
            $rowDate = $item->payment_verified_at ?? $item->updated_at ?? $item->deployment_to_date ?? $item->created_at;
            $rowTime = $item->payment_verified_at ?? $item->updated_at ?? $item->created_at;
            $statementRows[] = (object) [
                'type' => 'debit',
                'payment' => null,
                'item' => $item,
                'adjustment' => null,
                'debit_amount' => $debitAmount,
                'credit_amount' => 0,
                'row_date' => $rowDate,
                'row_time' => $rowTime,
            ];
        }

        $adjustments = \App\Models\DeploymentAbsentAdjustment::with(['operationDeploymentDetail.operationLead'])
            ->whereHas('operationDeploymentDetail', function ($q) use ($freelancerId) {
                $q->where('freelance_staff_id', $freelancerId);
            })
            ->orderBy('adjusted_at')
            ->orderBy('id')
            ->get();

        foreach ($adjustments as $adj) {
            $detail = $adj->operationDeploymentDetail;
            if (!$detail) {
                continue;
            }
            $statementRows[] = (object) [
                'type' => 'absent_credit',
                'payment' => null,
                'item' => $detail,
                'adjustment' => $adj,
                'debit_amount' => 0,
                'credit_amount' => (float) $adj->amount_deducted,
                'row_date' => $adj->adjusted_at,
                'row_time' => $adj->adjusted_at,
            ];
        }

        $paidPayments = FreelancerPayment::with(['jobRequest', 'createdBy'])
            ->where('job_request_id', $freelancerId)
            ->where('status', 'completed')
            ->orderBy('payment_date')
            ->orderBy('id')
            ->get();

        foreach ($paidPayments as $payment) {
            $ids = $payment->deployment_detail_ids;
            $invoiceNos = is_array($ids) ? array_values(array_unique(array_map('intval', $ids))) : [];
            $statementRows[] = (object) [
                'type' => 'credit',
                'payment' => $payment,
                'item' => null,
                'adjustment' => null,
                'invoice_nos' => $invoiceNos,
                'debit_amount' => 0,
                'credit_amount' => (float) $payment->amount,
                'row_date' => $payment->payment_date,
                'row_time' => $payment->created_at,
            ];
        }

        $totalEarned = (float) $allVerified->sum('vendor_payment');
        $totalPaid = (float) $paidPayments->sum('amount');
        $totalAbsentDeducted = (float) $adjustments->sum('amount_deducted');
        $balanceAmount = round($totalEarned - $totalPaid - $totalAbsentDeducted, 2);

        usort($statementRows, function ($a, $b) {
            $t1 = $a->row_date ? \Carbon\Carbon::parse($a->row_date)->timestamp : 0;
            $t2 = $b->row_date ? \Carbon\Carbon::parse($b->row_date)->timestamp : 0;
            if ($t1 !== $t2) {
                return $t1 <=> $t2;
            }
            $time1 = $a->row_time ? \Carbon\Carbon::parse($a->row_time)->format('His') : '0';
            $time2 = $b->row_time ? \Carbon\Carbon::parse($b->row_time)->format('His') : '0';
            $order = ['debit' => 0, 'absent_credit' => 1, 'credit' => 2];
            return ($order[$a->type] ?? 1) <=> ($order[$b->type] ?? 1);
        });

        $forApp = true;
        $logoBase64 = null;
        $logoPath = public_path('images/carelix-logo.png');
        if (file_exists($logoPath)) {
            $logoBase64 = base64_encode(file_get_contents($logoPath));
        }
        $html = view('admin.freelancer_payments.statement', compact(
            'freelancer',
            'statementRows',
            'totalEarned',
            'totalPaid',
            'balanceAmount',
            'forApp',
            'logoBase64'
        ))->render();

        return response($html, 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
        ]);
    }

    /**
     * Show payment invoice (view/print/PDF).
     */
    public function invoice($id)
    {
        $payment = FreelancerPayment::with('jobRequest')->findOrFail($id);
        $lineItems = $payment->getInvoiceLineItems();

        $freelancerId = $payment->job_request_id;
        $totalEarned = OperationDeploymentDetails::where('freelance_staff_id', $freelancerId)
            ->where('verify_payment', true)
            ->sum('vendor_payment');
        $totalPaid = FreelancerPayment::where('job_request_id', $freelancerId)
            ->where('status', 'completed')
            ->sum('amount');
        $balanceAmount = round((float) $totalEarned - (float) $totalPaid, 2);
        $balanceBeforeThisPayment = round((float) $totalEarned - ((float) $totalPaid - (float) $payment->amount), 2);

        return view('admin.freelancer_payments.invoice', compact(
            'payment',
            'lineItems',
            'balanceAmount',
            'balanceBeforeThisPayment'
        ));
    }

    /**
     * API: Return invoice HTML for app (WebView / in-app display).
     */
    public function invoiceApi($id)
    {
        $payment = FreelancerPayment::with('jobRequest')->findOrFail($id);
        $lineItems = $payment->getInvoiceLineItems();
        $freelancerId = $payment->job_request_id;
        $totalEarned = OperationDeploymentDetails::where('freelance_staff_id', $freelancerId)
            ->where('verify_payment', true)
            ->sum('vendor_payment');
        $totalPaid = FreelancerPayment::where('job_request_id', $freelancerId)
            ->where('status', 'completed')
            ->sum('amount');
        $balanceAmount = round((float) $totalEarned - (float) $totalPaid, 2);
        $balanceBeforeThisPayment = round((float) $totalEarned - ((float) $totalPaid - (float) $payment->amount), 2);
        $forApp = true;
        $logoBase64 = null;
        $logoPath = public_path('images/carelix-logo.png');
        if (file_exists($logoPath)) {
            $logoBase64 = base64_encode(file_get_contents($logoPath));
        }
        $html = view('admin.freelancer_payments.invoice', compact(
            'payment',
            'lineItems',
            'balanceAmount',
            'balanceBeforeThisPayment',
            'forApp',
            'logoBase64'
        ))->render();
        return response($html, 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
        ]);
    }

    public function getFreelancerPayments($freelancerId)
    {
        $paymentModels = FreelancerPayment::with(['jobRequest', 'createdBy'])
            ->where('job_request_id', $freelancerId)
            ->latest('payment_date')
            ->get();

        $freelancer = JobRequest::findOrFail($freelancerId);
        $totalPaid = $paymentModels->where('status', 'completed')->sum('amount');

        $payments = $paymentModels->map(function($payment) {
            // Format payment method text
            $paymentMethodText = ucwords(str_replace('_', ' ', $payment->payment_method));
            
            // Format payment date
            $formattedDate = $payment->payment_date ? $payment->payment_date->format('d-M-Y') : 'N/A';
            
            // Get status badge class
            $statusBadges = [
                'pending' => 'badge bg-warning',
                'completed' => 'badge bg-success',
                'failed' => 'badge bg-danger'
            ];
            $statusBadge = $statusBadges[$payment->status] ?? 'badge bg-secondary';
            
            return [
                'id' => $payment->id,
                'job_request_id' => $payment->job_request_id,
                'amount' => $payment->amount,
                'payment_method' => $payment->payment_method,
                'payment_date' => $payment->payment_date ? $payment->payment_date->format('Y-m-d') : null,
                'transaction_id' => $payment->transaction_id,
                'reference_number' => $payment->reference_number,
                'description' => $payment->description,
                'status' => $payment->status,
                'screenshot' => $payment->screenshot,
                'formatted_payment_date' => $formattedDate,
                'payment_method_text' => $paymentMethodText,
                'status_badge' => $statusBadge,
                'has_invoice' => $payment->hasInvoice(),
                'jobRequest' => $payment->jobRequest ? [
                    'id' => $payment->jobRequest->id,
                    'name' => $payment->jobRequest->name,
                    'contact_no' => $payment->jobRequest->contact_no,
                ] : null,
                'created_by' => $payment->createdBy ? [
                    'id' => $payment->createdBy->id,
                    'name' => $payment->createdBy->name,
                ] : null,
            ];
        });

        return response()->json([
            'payments' => $payments,
            'freelancer' => [
                'id' => $freelancer->id,
                'name' => $freelancer->name,
                'contact_no' => $freelancer->contact_no,
                'email' => $freelancer->email,
            ],
            'totalPaid' => $totalPaid
        ]);
    }
}

