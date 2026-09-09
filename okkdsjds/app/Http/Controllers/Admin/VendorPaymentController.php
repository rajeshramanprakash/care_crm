<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Vendor;
use App\Models\VendorPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class VendorPaymentController extends Controller
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

        return view('admin.vendor_payments.index', compact(
            'vendors',
            'payments',
            'totalPayments',
            'totalPaymentsThisMonth',
            'pendingPayments',
            'totalVerifiedEarnings'
        ));
    }

    public function vendorHistory()
    {
        $vendors = Vendor::with(['payments' => function($query) {
            $query->completed();
        }])->where('status', 'active')->get();

        return view('admin.vendor_payments.vendor_history', compact('vendors'));
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
            $data = $request->all();
            $data['created_by'] = auth()->id();
            $data['status'] = 'completed';

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
        return response()->json($payment);
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
        $payments = VendorPayment::with(['vendor', 'createdBy'])
            ->where('vendor_id', $vendorId)
            ->latest('payment_date')
            ->get();

        $vendor = Vendor::findOrFail($vendorId);
        $totalPaid = $payments->where('status', 'completed')->sum('amount');

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
