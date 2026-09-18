<?php

namespace App\Http\Controllers\SubAdmin;

use App\Http\Controllers\Controller;
use App\Models\Vendor;
use App\Models\VendorPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class SubAdminVendorPaymentController extends Controller
{
    public function index()
    {
        $vendors = Vendor::where('status', 'active')->get();
        $payments = VendorPayment::with(['vendor', 'createdBy'])
            ->latest('payment_date')
            ->paginate(20);

        // Get summary statistics
        $totalPayments = VendorPayment::completed()->sum('amount');
        $totalPaymentsThisMonth = VendorPayment::completed()
            ->whereMonth('payment_date', Carbon::now()->month)
            ->sum('amount');
        $pendingPayments = VendorPayment::pending()->count();
        
        // Get total verified earnings from deployments
        $totalVerifiedEarnings = \App\Models\OperationDeploymentDetails::where('verify_payment', true)
            ->sum('vendor_payment');

        return view('subadmin.vendor_payments.index', compact(
            'vendors',
            'payments',
            'totalPayments',
            'totalPaymentsThisMonth',
            'pendingPayments',
            'totalVerifiedEarnings'
        ));
    }

    public function indexApi()
    {
        $vendors = Vendor::where('status', 'active')->get();
        $vendorIds = $vendors->pluck('id');
        
        // Get all payments for these vendors in one query
        $allPayments = VendorPayment::whereIn('vendor_id', $vendorIds)
            ->where('status', 'completed')
            ->get()
            ->groupBy('vendor_id');
        
        $vendorsData = $vendors->map(function($vendor) use ($allPayments) {
            $payments = $allPayments->get($vendor->id, collect());
            
            $totalPaid = $payments->sum('amount');
            $lastPayment = $payments->sortByDesc('payment_date')->first();
            
            return [
                'id' => $vendor->id,
                'name' => $vendor->name,
                'contact_no' => $vendor->contact_no,
                'email' => $vendor->email,
                'total_paid' => $totalPaid,
                'payments' => [],
                'last_payment_date' => $lastPayment ? $lastPayment->payment_date->format('Y-m-d') : null,
            ];
        });

        return response()->json([
            'vendors' => $vendorsData
        ]);
    }

    public function vendorHistory()
    {
        $vendors = Vendor::with(['payments' => function($query) {
            $query->completed();
        }])->where('status', 'active')->get();

        return view('subadmin.vendor_payments.vendor_history', compact('vendors'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'vendor_id' => 'required|exists:vendors,id',
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
                'vendor_id', 'amount', 'payment_method', 'transaction_id',
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
                $filename = 'vendor_payment_' . time() . '_' . $screenshot->getClientOriginalName();
                $path = $screenshot->storeAs('vendor_payments', $filename, 'public');
                $data['screenshot'] = $path;
            }

            VendorPayment::create($data);

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
        $payment = VendorPayment::with(['vendor', 'createdBy'])->findOrFail($id);
        $invoiceLineItems = $payment->getInvoiceLineItems();
        
        return response()->json([
            'id' => $payment->id,
            'vendor_id' => $payment->vendor_id,
            'amount' => $payment->amount,
            'payment_method' => $payment->payment_method,
            'payment_date' => $payment->payment_date,
            'transaction_id' => $payment->transaction_id,
            'reference_number' => $payment->reference_number,
            'description' => $payment->description,
            'deployment_detail_ids' => $payment->deployment_detail_ids,
            'status' => $payment->status,
            'screenshot' => $payment->screenshot,
            'formatted_payment_date' => $payment->formatted_payment_date,
            'payment_method_text' => $payment->payment_method_text,
            'status_badge' => $payment->status_badge,
            'vendor' => $payment->vendor,
            'created_by' => $payment->createdBy,
            'has_invoice' => $payment->hasInvoice(),
            'invoice_line_items' => $invoiceLineItems->map(fn($d) => [
                'lead_id' => $d->operationLead ? $d->operationLead->lead_id : 'N/A',
                'customer_name' => $d->operationLead ? $d->operationLead->customer_name : 'N/A',
                'service' => $d->operationLead ? $d->operationLead->query : 'N/A',
                'from_date' => $d->deployment_from_date?->format('d-M-Y'),
                'to_date' => $d->deployment_to_date?->format('d-M-Y'),
                'amount' => $d->vendor_payment,
            ]),
        ]);
    }

    public function edit($id)
    {
        // Ensure the 'vendor' relationship is eager loaded
        $payment = VendorPayment::with('vendor')->findOrFail($id);
        $vendors = Vendor::where('status', 'active')->get();

        // Format the payment date for the date input field
        $payment->payment_date = $payment->payment_date->format('Y-m-d');

        return response()->json([
            'payment' => $payment,
            'vendors' => $vendors
        ]);
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'vendor_id' => 'required|exists:vendors,id',
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
            $payment = VendorPayment::findOrFail($id);
            $data = $request->all();

            // Handle screenshot upload
            if ($request->hasFile('screenshot')) {
                // Delete old screenshot if exists
                if ($payment->screenshot) {
                    Storage::disk('public')->delete($payment->screenshot);
                }

                $screenshot = $request->file('screenshot');
                $filename = 'vendor_payment_' . time() . '_' . $screenshot->getClientOriginalName();
                $path = $screenshot->storeAs('vendor_payments', $filename, 'public');
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
            $payment = VendorPayment::findOrFail($id);

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

    public function getVendorPayments($vendorId)
    {
        $paymentModels = VendorPayment::with(['vendor', 'createdBy'])
            ->where('vendor_id', $vendorId)
            ->latest('payment_date')
            ->get();

        $vendor = Vendor::findOrFail($vendorId);
        $totalPaid = $paymentModels->where('status', 'completed')->sum('amount');

        $payments = $paymentModels->map(function($payment) {
            return [
                'id' => $payment->id,
                'vendor_id' => $payment->vendor_id,
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
                'vendor' => $payment->vendor,
                'created_by' => $payment->createdBy,
                'has_invoice' => $payment->hasInvoice(),
            ];
        });

        return response()->json([
            'payments' => $payments,
            'vendor' => $vendor,
            'totalPaid' => $totalPaid
        ]);
    }

    public function getPaymentStats()
    {
        $stats = [
            'total_payments' => VendorPayment::completed()->sum('amount'),
            'this_month' => VendorPayment::completed()
                ->whereMonth('payment_date', Carbon::now()->month)
                ->sum('amount'),
            'pending_count' => VendorPayment::pending()->count(),
            'total_count' => VendorPayment::count(),
            'total_verified_earnings' => \App\Models\OperationDeploymentDetails::where('verify_payment', true)
                ->sum('vendor_payment')
        ];

        return response()->json($stats);
    }

    /**
     * Show full statement for a vendor: separate rows for (1) verify/debit and (2) payment/credit.
     * Debit rows = when lead was verified / added in Deployment Details (date = verify/add time).
     * Credit rows = when payment was made (date = payment date/time).
     */
    public function statement($vendorId)
    {
        $vendor = Vendor::findOrFail($vendorId);

        // 1) Debit rows: one per verified deployment (date = when verified/added)
        $allVerified = \App\Models\OperationDeploymentDetails::with(['operationLead', 'freelanceStaff'])
            ->where('vendor_id', $vendorId)
            ->where('verify_payment', true)
            ->orderBy('deployment_from_date')
            ->orderBy('id')
            ->get();

        $statementRows = [];
        foreach ($allVerified as $item) {
            $debitAmount = (float) ($item->vendor_payment ?? 0);
            // Use payment_verified_at so debit row date doesn't change when absent is saved later
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

        // 2) Absent-adjustment rows: one per deployment absent adjustment (credit = amount deducted)
        $adjustments = \App\Models\DeploymentAbsentAdjustment::with(['operationDeploymentDetail.operationLead'])
            ->whereHas('operationDeploymentDetail', function ($q) use ($vendorId) {
                $q->where('vendor_id', $vendorId);
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

        // 3) Credit rows: one row per payment (single or multiple leads – one row only)
        $paidPayments = VendorPayment::with(['vendor', 'createdBy'])
            ->where('vendor_id', $vendorId)
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

        // Sort by row date (asc) so balance runs chronologically; debit before credit on same day if needed
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
            // Debit first, then absent_credit, then credit
            $order = ['debit' => 0, 'absent_credit' => 1, 'credit' => 2];
            return ($order[$a->type] ?? 1) <=> ($order[$b->type] ?? 1);
        });

        return view('subadmin.vendor_payments.statement', compact(
            'vendor',
            'statementRows',
            'totalEarned',
            'totalPaid',
            'balanceAmount'
        ));
    }

    /**
     * API: Return statement HTML for app (WebView / in-app display, optional PDF download).
     */
    public function statementApi($vendorId)
    {
        $vendor = Vendor::findOrFail($vendorId);

        $allVerified = \App\Models\OperationDeploymentDetails::with(['operationLead', 'freelanceStaff'])
            ->where('vendor_id', $vendorId)
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
            ->whereHas('operationDeploymentDetail', function ($q) use ($vendorId) {
                $q->where('vendor_id', $vendorId);
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

        $paidPayments = VendorPayment::with(['vendor', 'createdBy'])
            ->where('vendor_id', $vendorId)
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

        $forApp = true;
        $logoBase64 = null;
        $logoPath = public_path('images/carelix-logo.png');
        if (file_exists($logoPath)) {
            $logoBase64 = base64_encode(file_get_contents($logoPath));
        }
        $html = view('subadmin.vendor_payments.statement', compact(
            'vendor',
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
     * Show invoice for a vendor payment (view in modal or new tab; print/save as PDF from browser).
     */
    public function invoice($id)
    {
        $payment = VendorPayment::with(['vendor', 'createdBy'])->findOrFail($id);
        $lineItems = $payment->getInvoiceLineItems();

        // Total earned & paid for running balance per row
        $totalEarned = \App\Models\OperationDeploymentDetails::where('vendor_id', $payment->vendor_id)
            ->where('verify_payment', true)
            ->sum('vendor_payment');
        $totalPaid = VendorPayment::where('vendor_id', $payment->vendor_id)
            ->where('status', 'completed')
            ->sum('amount');
        $balanceAmount = round((float) $totalEarned - (float) $totalPaid, 2);
        // Remaining BEFORE this payment (so running balance: after each lead's payment, remaining = this - that row's amount)
        $balanceBeforeThisPayment = round((float) $totalEarned - ((float) $totalPaid - (float) $payment->amount), 2);

        return view('subadmin.vendor_payments.invoice', compact('payment', 'lineItems', 'balanceAmount', 'balanceBeforeThisPayment'));
    }

    /**
     * API: Return invoice HTML for app (WebView / in-app display).
     */
    public function invoiceApi($id)
    {
        $payment = VendorPayment::with(['vendor', 'createdBy'])->findOrFail($id);
        $lineItems = $payment->getInvoiceLineItems();
        $totalEarned = \App\Models\OperationDeploymentDetails::where('vendor_id', $payment->vendor_id)
            ->where('verify_payment', true)
            ->sum('vendor_payment');
        $totalPaid = VendorPayment::where('vendor_id', $payment->vendor_id)
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
        $html = view('subadmin.vendor_payments.invoice', compact('payment', 'lineItems', 'balanceAmount', 'balanceBeforeThisPayment', 'forApp', 'logoBase64'))->render();
        return response($html, 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
        ]);
    }

    public function downloadScreenshot($id)
    {
        $payment = VendorPayment::findOrFail($id);

        if (!$payment->screenshot) {
            return response()->json([
                'status' => 'error',
                'message' => 'No screenshot found for this payment'
            ], 404);
        }

        $path = storage_path('app/public/' . $payment->screenshot);

        if (!file_exists($path)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Screenshot file not found'
            ], 404);
        }

        return response()->download($path);
    }
}
