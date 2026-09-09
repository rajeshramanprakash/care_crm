<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\Vendor;
use App\Models\VendorPayment;
use App\Models\VendorCase;
use App\Models\OperationDeploymentDetails;
use App\Models\DeploymentLocationAttendance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class VendorController extends Controller
{
    public function dashboard()
    {
        // Get vendor ID from session
        $vendorId = Session::get('vendor_id');
        
        if (!$vendorId) {
            return redirect()->route('home')->with('error', 'Please login to access vendor dashboard.');
        }

        $vendor = Vendor::find($vendorId);
        
        if (!$vendor) {
            Session::forget(['vendor_id', 'vendor_name', 'login_type']);
            return redirect()->route('home')->with('error', 'Vendor account not found.');
        }

        // Get vendor payments
        $totalPayments = VendorPayment::where('vendor_id', $vendorId)->sum('amount');
        $completedPayments = VendorPayment::where('vendor_id', $vendorId)
            ->where('status', 'completed')
            ->sum('amount');
        $pendingPayments = VendorPayment::where('vendor_id', $vendorId)
            ->where('status', 'pending')
            ->sum('amount');

        // Get vendor cases (if table exists)
        $vendorCases = collect();
        try {
            if (Schema::hasTable('vendor_cases')) {
                $vendorCases = VendorCase::where('vendor_id', $vendorId)
                    ->with('case')
                    ->orderBy('created_at', 'desc')
                    ->get();
            }
        } catch (\Exception $e) {
            // Table doesn't exist, use empty collection
            $vendorCases = collect();
        }

        // Get deployments assigned to this vendor
        $deployments = OperationDeploymentDetails::where('vendor_id', $vendorId)
            ->with('operationLead')
            ->orderBy('created_at', 'desc')
            ->get();

        // Get active deployments (In Progress status)
        $activeDeployments = OperationDeploymentDetails::where('vendor_id', $vendorId)
            ->whereIn('deployment_status', ['In Progress', 'Active'])
            ->with('operationLead')
            ->orderBy('deployment_from_date', 'desc')
            ->get();

        $page_heading = 'Vendor Dashboard';
        $vendorName = $vendor->name ?? 'N/A';

        return view('vendor.portal_dashboard', compact(
            'page_heading',
            'vendor',
            'vendorName',
            'totalPayments',
            'completedPayments',
            'pendingPayments',
            'vendorCases',
            'deployments',
            'activeDeployments'
        ));
    }

    public function personalDetails()
    {
        $vendorId = Session::get('vendor_id');

        if (! $vendorId) {
            return redirect()->route('home')->with('error', 'Please login to access vendor dashboard.');
        }

        $vendor = Vendor::with(['priceChangeRequests' => fn ($q) => $q->orderByDesc('created_at')->limit(20)])
            ->find($vendorId);

        if (! $vendor) {
            Session::forget(['vendor_id', 'vendor_name', 'login_type']);

            return redirect()->route('home')->with('error', 'Vendor account not found.');
        }

        $pricingService = app(\App\Services\VendorServicePriceChangeService::class);
        $vendorBlocks = \App\Services\VendorServiceSync::blocksForVendor($vendor);
        $pricingItems = $pricingService->pricingItemsForVendor($vendor);
        $priceTypes = \App\Models\VendorServicePriceChangeRequest::priceTypes();
        $requestsByKey = $vendor->priceChangeRequests
            ->groupBy(fn ($r) => (int) $r->service_id.'|'.(int) $r->service_sub_service_id.'|'.(string) $r->price_type)
            ->map(fn ($group) => $group->first());

        $page_heading = 'Personal Details';
        $vendorName = $vendor->name ?? 'N/A';

        return view('vendor.personal-details', compact(
            'page_heading',
            'vendor',
            'vendorName',
            'vendorBlocks',
            'pricingItems',
            'priceTypes',
            'requestsByKey',
            'pricingService'
        ));
    }

    public function submitPriceChangeRequest(Request $request)
    {
        $vendorId = Session::get('vendor_id');
        if (! $vendorId) {
            return response()->json(['success' => false, 'message' => 'Please login to your vendor account.'], 401);
        }

        $vendor = Vendor::find($vendorId);
        if (! $vendor) {
            return response()->json(['success' => false, 'message' => 'Vendor account not found.'], 404);
        }

        $request->validate([
            'service_id' => ['required', 'integer', 'min:1'],
            'service_sub_service_id' => ['nullable', 'integer', 'min:0'],
            'price_type' => ['required', 'string', 'in:12hr,24hr,onetime'],
            'requested_price' => ['required', 'numeric', 'min:0.01'],
        ]);

        try {
            $changeRequest = app(\App\Services\VendorServicePriceChangeService::class)->submit($vendor, [
                'service_id' => (int) $request->input('service_id'),
                'service_sub_service_id' => (int) $request->input('service_sub_service_id', 0),
                'price_type' => (string) $request->input('price_type'),
                'requested_price' => (float) $request->input('requested_price'),
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            $msg = collect($e->errors())->flatten()->first() ?: 'Could not submit price change request.';

            return response()->json(['success' => false, 'message' => $msg], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Price change request admin ko bhej di gayi.',
            'request' => ['id' => $changeRequest->id, 'status' => $changeRequest->status],
        ]);
    }

    public function bankDetails()
    {
        $vendorId = Session::get('vendor_id');
        
        if (!$vendorId) {
            return redirect()->route('home')->with('error', 'Please login to access vendor dashboard.');
        }

        $vendor = Vendor::find($vendorId);
        
        if (!$vendor) {
            Session::forget(['vendor_id', 'vendor_name', 'login_type']);
            return redirect()->route('home')->with('error', 'Vendor account not found.');
        }

        $page_heading = 'Bank Account Details';
        $vendorName = $vendor->name ?? 'N/A';

        return view('vendor.bank-details', compact('page_heading', 'vendor', 'vendorName'));
    }

    public function emergencyDetails()
    {
        $vendorId = Session::get('vendor_id');
        
        if (!$vendorId) {
            return redirect()->route('home')->with('error', 'Please login to access vendor dashboard.');
        }

        $vendor = Vendor::find($vendorId);
        
        if (!$vendor) {
            Session::forget(['vendor_id', 'vendor_name', 'login_type']);
            return redirect()->route('home')->with('error', 'Vendor account not found.');
        }

        $page_heading = 'Emergency Details';
        $vendorName = $vendor->name ?? 'N/A';

        return view('vendor.emergency-details', compact('page_heading', 'vendor', 'vendorName'));
    }

    public function assignedLeads(Request $request)
    {
        $vendorId = Session::get('vendor_id');
        
        if (!$vendorId) {
            return redirect()->route('home')->with('error', 'Please login to access vendor dashboard.');
        }

        $vendor = Vendor::find($vendorId);
        
        if (!$vendor) {
            Session::forget(['vendor_id', 'vendor_name', 'login_type']);
            return redirect()->route('home')->with('error', 'Vendor account not found.');
        }

        // Get all deployments assigned to this vendor
        $query = OperationDeploymentDetails::where('vendor_id', $vendorId)
            ->with(['operationLead'])
            ->orderBy('created_at', 'desc');

        // Apply filters
        if ($request->filled('status')) {
            $query->where('deployment_status', $request->status);
        }

        if ($request->filled('from_date')) {
            $query->whereDate('deployment_date', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $query->whereDate('deployment_date', '<=', $request->to_date);
        }

        if ($request->filled('customer_name')) {
            $query->whereHas('operationLead', function($q) use ($request) {
                $q->where('customer_name', 'like', '%' . $request->customer_name . '%');
            });
        }

        if ($request->filled('contact_no')) {
            $query->whereHas('operationLead', function($q) use ($request) {
                $q->where('contact_no', 'like', '%' . $request->contact_no . '%');
            });
        }

        $deployments = $query->paginate(20);
        $today = now()->toDateString();
        $attendanceMap = DeploymentLocationAttendance::whereIn('operation_lead_id', $deployments->pluck('operation_lead_id')->unique()->all())
            ->where('vendor_id', $vendorId)
            ->whereDate('attendance_date', $today)
            ->get()
            ->keyBy('operation_lead_id');
        $deployments->getCollection()->transform(function ($deployment) use ($attendanceMap) {
            $attendance = $attendanceMap->get($deployment->operation_lead_id);
            $deployment->today_attendance = $attendance;
            return $deployment;
        });

        $page_heading = 'Assigned Leads';
        $vendorName = $vendor->name ?? 'N/A';

        return view('vendor.assigned-leads', compact('page_heading', 'vendor', 'vendorName', 'deployments'));
    }

    public function submitAttendanceLocation(Request $request)
    {
        $vendorId = Session::get('vendor_id');
        if (!$vendorId) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $validated = $request->validate([
            'operation_lead_id' => 'required|integer|exists:operation_leads,id',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
        ]);

        $isAssigned = OperationDeploymentDetails::where('vendor_id', $vendorId)
            ->where('operation_lead_id', $validated['operation_lead_id'])
            ->exists();
        if (! $isAssigned) {
            return response()->json(['success' => false, 'message' => 'Lead is not assigned to this vendor'], 403);
        }

        $attendance = DeploymentLocationAttendance::firstOrNew([
            'operation_lead_id' => $validated['operation_lead_id'],
            'vendor_id' => $vendorId,
            'freelancer_id' => null,
            'attendance_date' => now()->toDateString(),
        ]);

        $attendance->vendor_latitude = $validated['latitude'];
        $attendance->vendor_longitude = $validated['longitude'];
        $attendance->vendor_location_captured_at = now();

        if ($attendance->customer_location_captured_at) {
            $distance = $this->distanceInMeters(
                (float) $attendance->customer_latitude,
                (float) $attendance->customer_longitude,
                (float) $attendance->vendor_latitude,
                (float) $attendance->vendor_longitude
            );
            $attendance->distance_meters = $distance;
            $attendance->is_location_matched = $distance <= 250;
            if ($attendance->is_location_matched) {
                $attendance->attendance_marked_at = now();
                $attendance->attendance_status = 'present';
            } else {
                $attendance->attendance_status = 'location_not_matched';
            }
        } elseif (! $attendance->attendance_marked_at) {
            $attendance->attendance_status = 'waiting_for_customer_location';
        }

        $attendance->save();

        return response()->json([
            'success' => true,
            'message' => $attendance->attendance_marked_at
                ? 'Attendance marked successfully.'
                : 'Location captured. Waiting for customer location.',
            'attendance_status' => $attendance->attendance_status,
            'is_location_matched' => (bool) $attendance->is_location_matched,
            'distance_meters' => $attendance->distance_meters,
        ]);
    }

    public function attendanceStatuses(Request $request)
    {
        $vendorId = Session::get('vendor_id');
        if (!$vendorId) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $validated = $request->validate([
            'operation_lead_ids' => 'required|array|min:1',
            'operation_lead_ids.*' => 'integer|exists:operation_leads,id',
        ]);

        $rows = DeploymentLocationAttendance::where('vendor_id', $vendorId)
            ->whereDate('attendance_date', now()->toDateString())
            ->whereIn('operation_lead_id', $validated['operation_lead_ids'])
            ->get()
            ->keyBy('operation_lead_id');

        $statuses = [];
        foreach ($validated['operation_lead_ids'] as $leadId) {
            $attendance = $rows->get($leadId);
            $statuses[$leadId] = [
                'attendance_status' => $attendance->attendance_status ?? 'pending',
                'is_location_matched' => (bool) ($attendance->is_location_matched ?? false),
                'attendance_marked' => (bool) ($attendance->attendance_marked_at ?? false),
            ];
        }

        return response()->json([
            'success' => true,
            'statuses' => $statuses,
        ]);
    }

    private function distanceInMeters(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371000;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) * sin($dLat / 2)
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2))
            * sin($dLng / 2) * sin($dLng / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return round($earthRadius * $c, 2);
    }

    public function paymentDetails()
    {
        $vendorId = Session::get('vendor_id');
        
        if (!$vendorId) {
            return redirect()->route('home')->with('error', 'Please login to access vendor dashboard.');
        }

        $vendor = Vendor::find($vendorId);
        
        if (!$vendor) {
            Session::forget(['vendor_id', 'vendor_name', 'login_type']);
            return redirect()->route('home')->with('error', 'Vendor account not found.');
        }

        // Get total payment from verified deployments
        $totalPayment = OperationDeploymentDetails::where('vendor_id', $vendorId)
            ->where('verify_payment', true)
            ->sum('vendor_payment');

        // Get completed payments from VendorPayment table
        $completedPayments = VendorPayment::where('vendor_id', $vendorId)
            ->where('status', 'completed')
            ->sum('amount');

        // Calculate pending payment
        $pendingPayment = $totalPayment - $completedPayments;

        // Get all deployments with payment details
        $deployments = OperationDeploymentDetails::where('vendor_id', $vendorId)
            ->where('verify_payment', true)
            ->with('operationLead')
            ->orderBy('deployment_from_date', 'desc')
            ->get();

        // Get all vendor payments
        $vendorPayments = VendorPayment::where('vendor_id', $vendorId)
            ->orderBy('payment_date', 'desc')
            ->get();

        // Calculate cumulative payments to determine which deployments are paid
        $totalReceived = $completedPayments;
        $remainingPayment = $totalReceived;
        
        // Map deployments to leads with payment status
        // We'll mark deployments as completed based on cumulative payment allocation
        $leadsWithPayments = $deployments->map(function($deployment) use (&$remainingPayment) {
            $lead = $deployment->operationLead;
            $deploymentPayment = $deployment->vendor_payment ?? 0;
            
            // Check if this deployment's payment has been covered by received payments
            // We allocate payments in order (FIFO - First In First Out)
            $isPaymentComplete = false;
            if ($deploymentPayment > 0 && $remainingPayment >= $deploymentPayment) {
                $isPaymentComplete = true;
                $remainingPayment -= $deploymentPayment;
            }
            
            return [
                'deployment_id' => $deployment->id,
                'lead_id' => $lead->lead_id ?? 'N/A',
                'customer_name' => $lead->customer_name ?? 'N/A',
                'contact_no' => $lead->contact_no ?? 'N/A',
                'location' => $lead->location ?? 'N/A',
                'query' => $lead->query ?? 'N/A',
                'deployment_from_date' => $deployment->deployment_from_date,
                'deployment_to_date' => $deployment->deployment_to_date,
                'duty_hours' => $deployment->duty_hours,
                'vendor_payment' => $deploymentPayment,
                'payment_status' => $isPaymentComplete ? 'Completed' : 'Pending',
                'deployment_status' => $deployment->deployment_status ?? 'N/A'
            ];
        });

        $page_heading = 'Payment Details';
        $vendorName = $vendor->name ?? 'N/A';

        return view('vendor.payment-details', compact(
            'page_heading',
            'vendor',
            'vendorName',
            'totalPayment',
            'completedPayments',
            'pendingPayment',
            'leadsWithPayments',
            'vendorPayments'
        ));
    }

    /**
     * Show statement for logged-in vendor (same structure as admin vendor statement).
     */
    public function statement()
    {
        $vendorId = Session::get('vendor_id');
        if (!$vendorId) {
            return redirect()->route('home')->with('error', 'Please login to access vendor dashboard.');
        }
        $vendor = Vendor::find($vendorId);
        if (!$vendor) {
            Session::forget(['vendor_id', 'vendor_name', 'login_type']);
            return redirect()->route('home')->with('error', 'Vendor account not found.');
        }

        $allVerified = OperationDeploymentDetails::with(['operationLead', 'freelanceStaff'])
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

        return view('admin.vendor_payments.statement', compact(
            'vendor',
            'statementRows',
            'totalEarned',
            'totalPaid',
            'balanceAmount'
        ));
    }

    public function customerChats()
    {
        $vendorId = Session::get('vendor_id');
        
        if (!$vendorId) {
            return redirect()->route('home')->with('error', 'Please login to access vendor dashboard.');
        }

        $vendor = Vendor::find($vendorId);
        
        if (!$vendor) {
            Session::forget(['vendor_id', 'vendor_name', 'login_type']);
            return redirect()->route('home')->with('error', 'Vendor account not found.');
        }

        $page_heading = 'Customer Chats';
        $vendorName = $vendor->name ?? 'N/A';

        return view('vendor.customer-chats', compact('page_heading', 'vendor', 'vendorName'));
    }

    public function getUnreadCounts()
    {
        // Return empty counts for now since vendor chat functionality is not fully implemented
        return response()->json([]);
    }

    public function updateProfileImage(Request $request)
    {
        $vendorId = Session::get('vendor_id');
        
        if (!$vendorId) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        if (!$request->hasFile('profile_image')) {
            return response()->json(['success' => false, 'message' => 'No image file found']);
        }

        $vendor = Vendor::find($vendorId);
        
        if (!$vendor) {
            return response()->json(['success' => false, 'message' => 'Vendor not found']);
        }

        $file = $request->file('profile_image');
        $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
        $path = $file->storeAs('public/profile_images', $filename);

        // Delete old profile image if exists
        if ($vendor->profile_image) {
            Storage::disk('public')->delete($vendor->profile_image);
        }

        // Update profile image
        $vendor->profile_image = 'profile_images/' . $filename;
        $vendor->save();

        $profileImageUrl = Storage::url('profile_images/' . $filename);

        return response()->json([
            'success' => true,
            'message' => 'Profile image updated successfully',
            'profile_image_url' => $profileImageUrl
        ]);
    }

    public function uploadDocument(Request $request)
    {
        $vendorId = Session::get('vendor_id');
        
        if (!$vendorId) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $request->validate([
            'document' => 'required|file|mimes:jpeg,jpg,png,pdf|max:10240', // 10MB max
            'document_type' => 'required|in:aadhar_card,pan_card,qualification_certificate,bank_document'
        ]);

        $vendor = Vendor::find($vendorId);
        
        if (!$vendor) {
            return response()->json(['success' => false, 'message' => 'Vendor not found'], 404);
        }

        $file = $request->file('document');
        $documentType = $request->document_type;
        
        // Generate filename
        $filename = 'vendor_' . $vendorId . '_' . $documentType . '_' . time() . '.' . $file->getClientOriginalExtension();
        
        // Store file based on document type
        $path = $file->storeAs('documents/vendors', $filename, 'public');

        // Delete old document if exists
        $oldDocument = $vendor->$documentType;
        if ($oldDocument) {
            Storage::disk('public')->delete($oldDocument);
        }

        // Update vendor document
        $vendor->$documentType = $path;
        $vendor->save();

        $documentUrl = Storage::url($path);
        $documentName = ucwords(str_replace('_', ' ', $documentType));

        return response()->json([
            'success' => true,
            'message' => $documentName . ' uploaded successfully',
            'document_url' => $documentUrl,
            'document_name' => $documentName
        ]);
    }
}
