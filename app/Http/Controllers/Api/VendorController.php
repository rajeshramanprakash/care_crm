<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Vendor;
use App\Models\VendorPayment;
use App\Models\OperationDeploymentDetails;
use App\Models\DeploymentLocationAttendance;
use App\Models\CustomerChatMessage;
use App\Models\CustomerChatCall;
use App\Models\OperationLead;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class VendorController extends Controller
{
    public function dashboard(Request $request)
    {
        $user = $request->user();
        
        // Get vendor info from cache (set during login)
        $vendorInfo = Cache::get('vendor_info_' . $user->id);
        
        if (!$vendorInfo) {
            // Fallback: try to find by mobile number
            $mobile = $user->mobile ?? null;
            if (!$mobile) {
                return response()->json([
                    'success' => false,
                    'message' => 'Mobile number not found'
                ], 404);
            }

            $vendor = Vendor::where('contact_no', $mobile)->first();
            if (!$vendor) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vendor not found'
                ], 404);
            }
            
            $vendorId = $vendor->id;
            $vendorName = $vendor->name ?? 'N/A';
        } else {
            $vendorId = $vendorInfo['vendor_id'];
            $vendorName = $vendorInfo['vendor_name'];
        }

        $vendor = Vendor::find($vendorId);
        if (!$vendor) {
            return response()->json([
                'success' => false,
                'message' => 'Vendor not found'
            ], 404);
        }

        // Get vendor payments
        $totalPayments = VendorPayment::where('vendor_id', $vendorId)->sum('amount');
        $completedPayments = VendorPayment::where('vendor_id', $vendorId)
            ->where('status', 'completed')
            ->sum('amount');
        $pendingPayments = VendorPayment::where('vendor_id', $vendorId)
            ->where('status', 'pending')
            ->sum('amount');

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

        return response()->json([
            'success' => true,
            'vendor' => $vendor,
            'vendor_name' => $vendorName,
            'total_payments' => $totalPayments,
            'completed_payments' => $completedPayments,
            'pending_payments' => $pendingPayments,
            'deployments' => $deployments,
            'active_deployments' => $activeDeployments,
        ]);
    }

    public function personalDetails(Request $request)
    {
        $user = $request->user();
        $vendorInfo = Cache::get('vendor_info_' . $user->id);
        
        if (!$vendorInfo) {
            $mobile = $user->mobile ?? null;
            if (!$mobile) {
                return response()->json(['success' => false, 'message' => 'Mobile number not found'], 404);
            }
            $vendor = Vendor::where('contact_no', $mobile)->first();
            if (!$vendor) {
                return response()->json(['success' => false, 'message' => 'Vendor not found'], 404);
            }
        } else {
            $vendor = Vendor::find($vendorInfo['vendor_id']);
            if (!$vendor) {
                return response()->json(['success' => false, 'message' => 'Vendor not found'], 404);
            }
        }

        // Get services and locations
        $services = \App\Models\Service::all();
        $locations = \App\Models\Location::all();

        $vendor = Vendor::with(['priceChangeRequests' => fn ($q) => $q->orderByDesc('created_at')->limit(20)])
            ->find($vendor->id);

        $pricingService = app(\App\Services\VendorServicePriceChangeService::class);
        $pricingItems = $pricingService->pricingItemsForVendor($vendor);
        $priceTypes = \App\Models\VendorServicePriceChangeRequest::priceTypes();
        $requestsByKey = $vendor->priceChangeRequests
            ->groupBy(fn ($r) => (int) $r->service_id.'|'.(int) $r->service_sub_service_id.'|'.(string) $r->price_type)
            ->map(fn ($group) => $group->first());

        $pricingRows = [];
        foreach ($pricingItems as $item) {
            $serviceId = (int) $item['service_id'];
            $subId = (int) $item['service_sub_service_id'];
            foreach ($priceTypes as $typeKey) {
                $reqKey = $serviceId.'|'.$subId.'|'.$typeKey;
                $latestReq = $requestsByKey[$reqKey] ?? null;
                $pricingRows[] = [
                    'service_id' => $serviceId,
                    'service_name' => $item['service_name'],
                    'service_sub_service_id' => $subId,
                    'label' => $item['label'],
                    'tags' => $item['tags'],
                    'price_type' => $typeKey,
                    'current_price' => $pricingService->currentPrice($vendor, $serviceId, $subId, $typeKey),
                    'latest_request' => $latestReq ? [
                        'id' => $latestReq->id,
                        'status' => $latestReq->status,
                        'current_price' => $latestReq->current_price,
                        'requested_price' => $latestReq->requested_price,
                        'admin_note' => $latestReq->admin_note,
                    ] : null,
                ];
            }
        }

        return response()->json([
            'success' => true,
            'vendor' => $vendor,
            'vendor_name' => $vendor->name ?? 'N/A',
            'services' => $services,
            'locations' => $locations,
            'pricing_items' => $pricingItems,
            'price_types' => $priceTypes,
            'pricing_rows' => $pricingRows,
        ]);
    }

    public function submitPriceChangeRequest(Request $request)
    {
        $user = $request->user();
        $vendorInfo = Cache::get('vendor_info_' . $user->id);

        if (!$vendorInfo) {
            $mobile = $user->mobile ?? null;
            if (!$mobile) {
                return response()->json(['success' => false, 'message' => 'Mobile number not found'], 404);
            }
            $vendor = Vendor::where('contact_no', $mobile)->first();
        } else {
            $vendor = Vendor::find($vendorInfo['vendor_id']);
        }

        if (!$vendor) {
            return response()->json(['success' => false, 'message' => 'Vendor not found'], 404);
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
            'message' => 'Price change request sent to admin for verification.',
            'request' => ['id' => $changeRequest->id, 'status' => $changeRequest->status],
        ]);
    }

    public function statement(Request $request)
    {
        $user = $request->user();
        $vendorInfo = Cache::get('vendor_info_' . $user->id);

        if (!$vendorInfo) {
            $mobile = $user->mobile ?? null;
            if (!$mobile) {
                return response()->json(['success' => false, 'message' => 'Mobile number not found'], 404);
            }
            $vendor = Vendor::where('contact_no', $mobile)->first();
            if (!$vendor) {
                return response()->json(['success' => false, 'message' => 'Vendor not found'], 404);
            }
            $vendorId = $vendor->id;
        } else {
            $vendorId = $vendorInfo['vendor_id'];
            $vendor = Vendor::find($vendorId);
        }

        if (!$vendor) {
            return response()->json(['success' => false, 'message' => 'Vendor not found'], 404);
        }

        $allVerified = OperationDeploymentDetails::with(['operationLead'])
            ->where('vendor_id', $vendorId)
            ->where('verify_payment', true)
            ->orderBy('deployment_from_date')
            ->orderBy('id')
            ->get();

        $statementRows = [];
        foreach ($allVerified as $item) {
            $debitAmount = (float) ($item->vendor_payment ?? 0);
            $rowDate = $item->payment_verified_at ?? $item->updated_at ?? $item->deployment_to_date ?? $item->created_at;
            $statementRows[] = [
                'type' => 'debit',
                'lead_id' => $item->operationLead?->lead_id ?? $item->id,
                'customer_name' => $item->operationLead?->customer_name ?? '—',
                'description' => 'Deployment earning',
                'debit_amount' => $debitAmount,
                'credit_amount' => 0,
                'row_date' => $rowDate ? \Carbon\Carbon::parse($rowDate)->format('d M Y') : '—',
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
            $statementRows[] = [
                'type' => 'absent_credit',
                'lead_id' => $detail->operationLead?->lead_id ?? $detail->id,
                'customer_name' => $detail->operationLead?->customer_name ?? '—',
                'description' => 'Absent day adjustment',
                'debit_amount' => 0,
                'credit_amount' => (float) $adj->amount_deducted,
                'row_date' => $adj->adjusted_at ? \Carbon\Carbon::parse($adj->adjusted_at)->format('d M Y') : '—',
            ];
        }

        $paidPayments = VendorPayment::where('vendor_id', $vendorId)
            ->where('status', 'completed')
            ->orderBy('payment_date')
            ->orderBy('id')
            ->get();

        foreach ($paidPayments as $payment) {
            $statementRows[] = [
                'type' => 'credit',
                'lead_id' => null,
                'customer_name' => null,
                'description' => $payment->description ?? 'Payment received',
                'debit_amount' => 0,
                'credit_amount' => (float) $payment->amount,
                'row_date' => $payment->payment_date ? \Carbon\Carbon::parse($payment->payment_date)->format('d M Y') : '—',
                'payment_method' => $payment->payment_method,
                'transaction_id' => $payment->transaction_id,
            ];
        }

        $totalEarned = (float) $allVerified->sum('vendor_payment');
        $totalPaid = (float) $paidPayments->sum('amount');
        $totalAbsentDeducted = (float) $adjustments->sum('amount_deducted');
        $balanceAmount = round($totalEarned - $totalPaid - $totalAbsentDeducted, 2);

        usort($statementRows, function ($a, $b) {
            return strtotime($a['row_date'] ?? '') <=> strtotime($b['row_date'] ?? '');
        });

        return response()->json([
            'success' => true,
            'vendor' => [
                'id' => $vendor->id,
                'name' => $vendor->name,
            ],
            'total_earned' => round($totalEarned, 2),
            'total_paid' => round($totalPaid, 2),
            'total_absent_deducted' => round($totalAbsentDeducted, 2),
            'balance_amount' => $balanceAmount,
            'statement_rows' => $statementRows,
        ]);
    }

    public function attendanceStatuses(Request $request)
    {
        $user = $request->user();
        $vendorInfo = Cache::get('vendor_info_' . $user->id);

        if (!$vendorInfo) {
            $mobile = $user->mobile ?? null;
            if (!$mobile) {
                return response()->json(['success' => false, 'message' => 'Mobile number not found'], 404);
            }
            $vendor = Vendor::where('contact_no', $mobile)->first();
            if (!$vendor) {
                return response()->json(['success' => false, 'message' => 'Vendor not found'], 404);
            }
            $vendorId = $vendor->id;
        } else {
            $vendorId = $vendorInfo['vendor_id'];
        }

        $validated = $request->validate([
            'operation_lead_ids' => 'required|array|min:1',
            'operation_lead_ids.*' => 'integer',
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

    public function bankDetails(Request $request)
    {
        $user = $request->user();
        $vendorInfo = Cache::get('vendor_info_' . $user->id);
        
        if (!$vendorInfo) {
            $mobile = $user->mobile ?? null;
            if (!$mobile) {
                return response()->json(['success' => false, 'message' => 'Mobile number not found'], 404);
            }
            $vendor = Vendor::where('contact_no', $mobile)->first();
            if (!$vendor) {
                return response()->json(['success' => false, 'message' => 'Vendor not found'], 404);
            }
        } else {
            $vendor = Vendor::find($vendorInfo['vendor_id']);
            if (!$vendor) {
                return response()->json(['success' => false, 'message' => 'Vendor not found'], 404);
            }
        }

        return response()->json([
            'success' => true,
            'vendor' => $vendor,
            'vendor_name' => $vendor->name ?? 'N/A',
        ]);
    }

    public function assignedLeads(Request $request)
    {
        $user = $request->user();
        $vendorInfo = Cache::get('vendor_info_' . $user->id);
        
        if (!$vendorInfo) {
            $mobile = $user->mobile ?? null;
            if (!$mobile) {
                return response()->json(['success' => false, 'message' => 'Mobile number not found'], 404);
            }
            $vendor = Vendor::where('contact_no', $mobile)->first();
            if (!$vendor) {
                return response()->json(['success' => false, 'message' => 'Vendor not found'], 404);
            }
            $vendorId = $vendor->id;
        } else {
            $vendorId = $vendorInfo['vendor_id'];
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
        $attendanceMap = DeploymentLocationAttendance::whereIn('operation_lead_id', $deployments->pluck('operation_lead_id')->unique())
            ->where('vendor_id', $vendorId)
            ->get()
            ->keyBy('operation_lead_id');
        $deployments->getCollection()->transform(function ($deployment) use ($attendanceMap) {
            $attendance = $attendanceMap->get($deployment->operation_lead_id);
            $deployment->location_attendance = [
                'customer_location_shared' => (bool) ($attendance && $attendance->customer_location_captured_at),
                'vendor_location_shared' => (bool) ($attendance && $attendance->vendor_location_captured_at),
                'attendance_marked' => (bool) ($attendance && $attendance->attendance_marked_at),
                'attendance_status' => $attendance->attendance_status ?? 'pending',
            ];
            return $deployment;
        });

        return response()->json([
            'success' => true,
            'deployments' => $deployments,
        ]);
    }

    public function submitAttendanceLocation(Request $request)
    {
        $user = $request->user();
        $vendorInfo = Cache::get('vendor_info_' . $user->id);

        if (!$vendorInfo) {
            $mobile = $user->mobile ?? null;
            if (!$mobile) {
                return response()->json(['success' => false, 'message' => 'Mobile number not found'], 404);
            }
            $vendor = Vendor::where('contact_no', $mobile)->first();
            if (!$vendor) {
                return response()->json(['success' => false, 'message' => 'Vendor not found'], 404);
            }
            $vendorId = $vendor->id;
        } else {
            $vendorId = $vendorInfo['vendor_id'];
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
                ? 'Attendance marked successfully'
                : 'Vendor location captured. Waiting for customer location.',
            'attendance' => [
                'customer_location_shared' => (bool) $attendance->customer_location_captured_at,
                'vendor_location_shared' => (bool) $attendance->vendor_location_captured_at,
                'attendance_marked' => (bool) $attendance->attendance_marked_at,
                'attendance_status' => $attendance->attendance_status,
                'is_location_matched' => (bool) $attendance->is_location_matched,
                'distance_meters' => $attendance->distance_meters,
            ],
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

    public function paymentDetails(Request $request)
    {
        $user = $request->user();
        $vendorInfo = Cache::get('vendor_info_' . $user->id);
        
        if (!$vendorInfo) {
            $mobile = $user->mobile ?? null;
            if (!$mobile) {
                return response()->json(['success' => false, 'message' => 'Mobile number not found'], 404);
            }
            $vendor = Vendor::where('contact_no', $mobile)->first();
            if (!$vendor) {
                return response()->json(['success' => false, 'message' => 'Vendor not found'], 404);
            }
            $vendorId = $vendor->id;
        } else {
            $vendorId = $vendorInfo['vendor_id'];
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
        $leadsWithPayments = $deployments->map(function($deployment) use (&$remainingPayment) {
            $lead = $deployment->operationLead;
            $deploymentPayment = $deployment->vendor_payment ?? 0;
            
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

        return response()->json([
            'success' => true,
            'total_payment' => $totalPayment,
            'completed_payments' => $completedPayments,
            'pending_payment' => $pendingPayment,
            'leads_with_payments' => $leadsWithPayments,
            'vendor_payments' => $vendorPayments,
        ]);
    }

    public function chats(Request $request)
    {
        $user = $request->user();
        $vendorInfo = Cache::get('vendor_info_' . $user->id);
        
        if (!$vendorInfo) {
            $mobile = $user->mobile ?? null;
            if (!$mobile) {
                return response()->json(['success' => false, 'message' => 'Mobile number not found'], 404);
            }
            $vendor = Vendor::where('contact_no', $mobile)->first();
            if (!$vendor) {
                return response()->json(['success' => false, 'message' => 'Vendor not found'], 404);
            }
            $vendorId = $vendor->id;
        } else {
            $vendorId = $vendorInfo['vendor_id'];
        }

        // Get all customers assigned to this vendor via OperationDeploymentDetails
        $deployments = OperationDeploymentDetails::where('vendor_id', $vendorId)
            ->with('operationLead')
            ->get();

        // Get unique customers
        $customers = $deployments->map(function($deployment) {
            return $deployment->operationLead;
        })->filter()->unique('id')->values();

        // Enrich customers with unread count and last message
        $enrichedCustomers = $customers->map(function($customer) use ($vendorId) {
            // Unread count (messages from customer to vendor)
            $unread = CustomerChatMessage::where('sender_type', 'customer')
                ->where('sender_id', $customer->id)
                ->where('receiver_type', 'vendor')
                ->where('receiver_id', $vendorId)
                ->where('is_read', false)
                ->count();

            // Last message (either direction)
            $lastMsg = CustomerChatMessage::where(function($q) use ($customer, $vendorId) {
                $q->where('sender_type', 'customer')
                  ->where('sender_id', $customer->id)
                  ->where('receiver_type', 'vendor')
                  ->where('receiver_id', $vendorId);
            })->orWhere(function($q) use ($customer, $vendorId) {
                $q->where('sender_type', 'vendor')
                  ->where('sender_id', $vendorId)
                  ->where('receiver_type', 'customer')
                  ->where('receiver_id', $customer->id);
            })->orderByDesc('created_at')->first();

            return [
                'id' => $customer->id,
                'customer_name' => $customer->customer_name ?? 'N/A',
                'contact_no' => $customer->contact_no ?? 'N/A',
                'profile_image' => $customer->profile_image ?? null,
                'unread_count' => $unread,
                'last_message' => $lastMsg ? [
                    'id' => $lastMsg->id,
                    'message' => $lastMsg->message,
                    'attachment' => $lastMsg->attachment,
                    'created_at' => $lastMsg->created_at,
                ] : null,
            ];
        });

        // Sort: unread first, then by last message time desc
        $sortedCustomers = $enrichedCustomers->sort(function($a, $b) {
            if ($a['unread_count'] > 0 && $b['unread_count'] == 0) return -1;
            if ($a['unread_count'] == 0 && $b['unread_count'] > 0) return 1;
            $aTime = $a['last_message'] ? strtotime($a['last_message']['created_at']) : 0;
            $bTime = $b['last_message'] ? strtotime($b['last_message']['created_at']) : 0;
            return $bTime <=> $aTime;
        })->values();

        return response()->json([
            'success' => true,
            'customers' => $sortedCustomers,
        ]);
    }

    public function getMessages($customerId, Request $request)
    {
        $user = $request->user();
        $vendorInfo = Cache::get('vendor_info_' . $user->id);
        
        if (!$vendorInfo) {
            $mobile = $user->mobile ?? null;
            if (!$mobile) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }
            $vendor = Vendor::where('contact_no', $mobile)->first();
            if (!$vendor) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }
            $vendorId = $vendor->id;
        } else {
            $vendorId = $vendorInfo['vendor_id'];
        }

        // Verify customer is assigned to this vendor
        $isAssigned = OperationDeploymentDetails::where('vendor_id', $vendorId)
            ->where('operation_lead_id', $customerId)
            ->exists();

        if (!$isAssigned) {
            return response()->json(['error' => 'Customer not assigned to you'], 403);
        }

        // Get the customer's contact_no to find all related operation lead IDs
        $customer = OperationLead::find($customerId);
        $customerContactNo = $customer ? $customer->contact_no : null;
        
        // Get all operation lead IDs with the same contact_no
        $operationLeadIds = [$customerId];
        if ($customerContactNo) {
            $relatedLeadIds = OperationLead::where('contact_no', $customerContactNo)
                ->pluck('id')
                ->toArray();
            $operationLeadIds = array_unique(array_merge($operationLeadIds, $relatedLeadIds));
            
            // Also get Lead IDs with the same contact_no (for backward compatibility)
            $leadIds = \App\Models\Lead::where('contact_no', $customerContactNo)
                ->pluck('id')
                ->toArray();
            $allCustomerIds = array_unique(array_merge($operationLeadIds, $leadIds));
        } else {
            $allCustomerIds = $operationLeadIds;
        }

        \Log::info('Vendor getMessages: Query parameters', [
            'vendor_id' => $vendorId,
            'customer_id' => $customerId,
            'customer_contact_no' => $customerContactNo,
            'operation_lead_ids' => $operationLeadIds,
            'all_customer_ids' => $allCustomerIds,
        ]);

        // Build query for messages - check both OperationLead IDs and Lead IDs
        $query = CustomerChatMessage::where(function($q) use ($operationLeadIds, $allCustomerIds, $vendorId) {
            $q->where(function($subQ) use ($operationLeadIds, $vendorId) {
                $subQ->where('sender_type', 'vendor')
                     ->where('sender_id', $vendorId)
                     ->where('receiver_type', 'customer')
                     ->whereIn('receiver_id', $operationLeadIds);
            })
            ->orWhere(function($subQ) use ($allCustomerIds, $vendorId) {
                // Check for messages from customers (both OperationLead and Lead IDs)
                $subQ->where('sender_type', 'customer')
                     ->whereIn('sender_id', $allCustomerIds)
                     ->where('receiver_type', 'vendor')
                     ->where('receiver_id', $vendorId);
            });
        });

        if ($request->has('last_message_id') && $request->last_message_id) {
            $query->where('id', '>', $request->last_message_id);
        }

        $messages = $query->with('repliedTo')->orderBy('created_at', 'asc')->get();
        
        \Log::info('Vendor getMessages: Messages found', [
            'count' => $messages->count(),
            'message_ids' => $messages->pluck('id')->toArray(),
            'last_message_id_param' => $request->last_message_id ?? null,
        ]);

        // Get call history - use allCustomerIds for customer calls
        $callsQuery = CustomerChatCall::where(function($q) use ($operationLeadIds, $allCustomerIds, $vendorId) {
            $q->where('caller_type', 'vendor')
              ->where('caller_id', $vendorId)
              ->where('receiver_type', 'customer')
              ->whereIn('receiver_id', $operationLeadIds);
        })->orWhere(function($q) use ($allCustomerIds, $vendorId) {
            $q->where('caller_type', 'customer')
              ->whereIn('caller_id', $allCustomerIds)
              ->where('receiver_type', 'vendor')
              ->where('receiver_id', $vendorId);
        });

        if ($request->has('last_message_id') && $request->last_message_id) {
            $lastMessage = CustomerChatMessage::find($request->last_message_id);
            if ($lastMessage) {
                $callsQuery->where('created_at', '>', $lastMessage->created_at);
            }
        }

        $calls = $callsQuery->orderBy('created_at', 'asc')->get();

        // Combine messages and calls
        $combined = collect();
        foreach ($messages as $msg) {
            $combined->push(['type' => 'message', 'id' => $msg->id, 'created_at' => $msg->created_at, 'data' => $msg]);
        }
        foreach ($calls as $call) {
            $combined->push(['type' => 'call', 'id' => 'call_' . $call->id, 'created_at' => $call->call_started_at ?? $call->created_at, 'data' => $call]);
        }
        $sorted = $combined->sortBy('created_at')->values();

        return response()->json($sorted);
    }

    public function sendMessage(Request $request)
    {
        $user = $request->user();
        $vendorInfo = Cache::get('vendor_info_' . $user->id);
        
        if (!$vendorInfo) {
            $mobile = $user->mobile ?? null;
            if (!$mobile) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }
            $vendor = Vendor::where('contact_no', $mobile)->first();
            if (!$vendor) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }
            $vendorId = $vendor->id;
        } else {
            $vendorId = $vendorInfo['vendor_id'];
        }

        $request->validate([
            'receiver_id' => 'required|exists:operation_leads,id',
            'message' => 'required_without:attachment|string|nullable',
            'attachment' => 'nullable|file|max:10240',
            'reply_to_id' => 'nullable|exists:customer_chat_messages,id'
        ]);

        // Verify customer is assigned to this vendor
        $isAssigned = OperationDeploymentDetails::where('vendor_id', $vendorId)
            ->where('operation_lead_id', $request->receiver_id)
            ->exists();

        if (!$isAssigned) {
            return response()->json(['error' => 'Customer not assigned to you'], 403);
        }

        $data = [
            'sender_type' => 'vendor',
            'sender_id' => $vendorId,
            'receiver_type' => 'customer',
            'receiver_id' => $request->receiver_id,
            'message' => $request->message,
            'is_read' => false,
            'reply_to_id' => $request->reply_to_id
        ];

        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $path = $file->store('customer_chat_attachments', 'public');
            $data['attachment'] = $path;
            $data['attachment_type'] = $file->getMimeType();
        }

        $message = CustomerChatMessage::create($data);

        return response()->json($message);
    }

    public function markAsRead($customerId, Request $request)
    {
        $user = $request->user();
        $vendorInfo = Cache::get('vendor_info_' . $user->id);
        
        if (!$vendorInfo) {
            $mobile = $user->mobile ?? null;
            if (!$mobile) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }
            $vendor = Vendor::where('contact_no', $mobile)->first();
            if (!$vendor) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }
            $vendorId = $vendor->id;
        } else {
            $vendorId = $vendorInfo['vendor_id'];
        }

        // Get all operation lead IDs with the same contact_no
        $customer = OperationLead::find($customerId);
        $customerContactNo = $customer ? $customer->contact_no : null;
        $operationLeadIds = [$customerId];
        if ($customerContactNo) {
            $relatedLeadIds = OperationLead::where('contact_no', $customerContactNo)
                ->pluck('id')
                ->toArray();
            $operationLeadIds = array_unique(array_merge($operationLeadIds, $relatedLeadIds));
        }

        CustomerChatMessage::where('sender_type', 'customer')
            ->whereIn('sender_id', $operationLeadIds)
            ->where('receiver_type', 'vendor')
            ->where('receiver_id', $vendorId)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return response()->json(['success' => true]);
    }

    public function updateProfileImage(Request $request)
    {
        $user = $request->user();
        $vendorInfo = Cache::get('vendor_info_' . $user->id);
        
        if (!$vendorInfo) {
            $mobile = $user->mobile ?? null;
            if (!$mobile) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
            }
            $vendor = Vendor::where('contact_no', $mobile)->first();
            if (!$vendor) {
                return response()->json(['success' => false, 'message' => 'Vendor not found'], 404);
            }
        } else {
            $vendor = Vendor::find($vendorInfo['vendor_id']);
            if (!$vendor) {
                return response()->json(['success' => false, 'message' => 'Vendor not found'], 404);
            }
        }

        if (!$request->hasFile('profile_image')) {
            return response()->json(['success' => false, 'message' => 'No image file found']);
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
        $user = $request->user();
        $vendorInfo = Cache::get('vendor_info_' . $user->id);
        
        if (!$vendorInfo) {
            $mobile = $user->mobile ?? null;
            if (!$mobile) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
            }
            $vendor = Vendor::where('contact_no', $mobile)->first();
            if (!$vendor) {
                return response()->json(['success' => false, 'message' => 'Vendor not found'], 404);
            }
            $vendorId = $vendor->id;
        } else {
            $vendorId = $vendorInfo['vendor_id'];
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

