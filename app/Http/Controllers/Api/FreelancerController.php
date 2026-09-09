<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\JobRequest;
use App\Models\OperationDeploymentDetails;
use App\Models\FreelancerPayment;
use App\Models\CustomerChatMessage;
use App\Models\CustomerChatCall;
use App\Models\OperationLead;
use App\Models\DeploymentLocationAttendance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class FreelancerController extends Controller
{
    public function dashboard(Request $request)
    {
        $user = $request->user();
        $freelancerInfo = Cache::get('freelancer_info_' . $user->id);
        
        if (!$freelancerInfo) {
            $mobile = $user->mobile ?? null;
            if (!$mobile) {
                return response()->json(['success' => false, 'message' => 'Mobile number not found'], 404);
            }
            $freelancer = JobRequest::where('mobile', $mobile)
                ->orWhere('contact_no', $mobile)
                ->first();
            if (!$freelancer) {
                return response()->json(['success' => false, 'message' => 'Freelancer not found'], 404);
            }
            $freelancerId = $freelancer->id;
        } else {
            $freelancerId = $freelancerInfo['freelancer_id'];
        }

        $freelancer = JobRequest::find($freelancerId);
        if (!$freelancer) {
            return response()->json(['success' => false, 'message' => 'Freelancer not found'], 404);
        }

        // Get active deployments
        $activeDeployments = OperationDeploymentDetails::where('freelance_staff_id', $freelancerId)
            ->whereIn('deployment_status', ['In Progress', 'Active', 'Ongoing'])
            ->with('operationLead')
            ->orderBy('deployment_from_date', 'desc')
            ->get();

        // Get all deployments
        $deployments = OperationDeploymentDetails::where('freelance_staff_id', $freelancerId)
            ->with('operationLead')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'freelancer' => $freelancer,
            'freelancer_name' => $freelancer->name ?? 'Freelancer',
            'deployments' => $deployments,
            'active_deployments' => $activeDeployments,
        ]);
    }

    public function personalDetails(Request $request)
    {
        $user = $request->user();
        $freelancerInfo = Cache::get('freelancer_info_' . $user->id);
        
        if (!$freelancerInfo) {
            $mobile = $user->mobile ?? null;
            if (!$mobile) {
                return response()->json(['success' => false, 'message' => 'Mobile number not found'], 404);
            }
            $freelancer = JobRequest::where('mobile', $mobile)
                ->orWhere('contact_no', $mobile)
                ->first();
            if (!$freelancer) {
                return response()->json(['success' => false, 'message' => 'Freelancer not found'], 404);
            }
        } else {
            $freelancer = JobRequest::find($freelancerInfo['freelancer_id']);
            if (!$freelancer) {
                return response()->json(['success' => false, 'message' => 'Freelancer not found'], 404);
            }
        }

        $services = \App\Models\Service::orderBy('name', 'asc')->get();
        $locations = \App\Models\Location::orderBy('name', 'asc')->get();

        // Generate full URLs for documents (similar to CustomerController)
        $aadharCardUrl = null;
        $panCardUrl = null;
        $qualificationCertificateUrl = null;
        
        // Get base URL from request or APP_URL
        $scheme = $request->getScheme();
        $host = $request->getHost();
        $port = $request->getPort();
        
        // If host is localhost, try to get from headers or use APP_URL
        if ($host === 'localhost' || $host === '127.0.0.1') {
            // Try X-Forwarded-Host first (for proxies)
            $forwardedHost = $request->header('X-Forwarded-Host');
            if ($forwardedHost) {
                $host = $forwardedHost;
            } else {
                // Try to extract from Origin or Referer header
                $origin = $request->header('Origin');
                $referer = $request->header('Referer');
                if ($origin) {
                    $parsedOrigin = parse_url($origin);
                    if ($parsedOrigin && isset($parsedOrigin['host'])) {
                        $host = $parsedOrigin['host'];
                        $port = $parsedOrigin['port'] ?? $port;
                    }
                } elseif ($referer) {
                    $parsedReferer = parse_url($referer);
                    if ($parsedReferer && isset($parsedReferer['host'])) {
                        $host = $parsedReferer['host'];
                        $port = $parsedReferer['port'] ?? $port;
                    }
                } else {
                    // Fallback to APP_URL
                    $appUrl = config('app.url');
                    if ($appUrl && $appUrl !== 'http://localhost') {
                        $parsedAppUrl = parse_url($appUrl);
                        if ($parsedAppUrl && isset($parsedAppUrl['host'])) {
                            $host = $parsedAppUrl['host'];
                            $port = $parsedAppUrl['port'] ?? $port;
                        }
                    }
                }
            }
        }
        
        $baseUrl = $scheme . '://' . $host . ($port && $port != 80 && $port != 443 ? ':' . $port : '');
        
        if ($freelancer->aadhar_card) {
            if (strpos($freelancer->aadhar_card, 'http') === 0) {
                $aadharCardUrl = $freelancer->aadhar_card;
            } else {
                $storagePath = Storage::disk('public')->url($freelancer->aadhar_card);
                if (strpos($storagePath, 'http') !== 0) {
                    $aadharCardUrl = $baseUrl . $storagePath;
                } else {
                    // If Storage::url() returns a full URL but with localhost, replace it
                    $aadharCardUrl = str_replace('http://localhost', $baseUrl, $storagePath);
                    $aadharCardUrl = str_replace('http://127.0.0.1', $baseUrl, $aadharCardUrl);
                }
            }
        }
        
        if ($freelancer->pan_card) {
            if (strpos($freelancer->pan_card, 'http') === 0) {
                $panCardUrl = $freelancer->pan_card;
            } else {
                $storagePath = Storage::disk('public')->url($freelancer->pan_card);
                if (strpos($storagePath, 'http') !== 0) {
                    $panCardUrl = $baseUrl . $storagePath;
                } else {
                    // If Storage::url() returns a full URL but with localhost, replace it
                    $panCardUrl = str_replace('http://localhost', $baseUrl, $storagePath);
                    $panCardUrl = str_replace('http://127.0.0.1', $baseUrl, $panCardUrl);
                }
            }
        }
        
        if ($freelancer->qualification_certificate) {
            if (strpos($freelancer->qualification_certificate, 'http') === 0) {
                $qualificationCertificateUrl = $freelancer->qualification_certificate;
            } else {
                $storagePath = Storage::disk('public')->url($freelancer->qualification_certificate);
                if (strpos($storagePath, 'http') !== 0) {
                    $qualificationCertificateUrl = $baseUrl . $storagePath;
                } else {
                    // If Storage::url() returns a full URL but with localhost, replace it
                    $qualificationCertificateUrl = str_replace('http://localhost', $baseUrl, $storagePath);
                    $qualificationCertificateUrl = str_replace('http://127.0.0.1', $baseUrl, $qualificationCertificateUrl);
                }
            }
        }

        // Add document URLs to freelancer object
        $freelancer->aadhar_card_url = $aadharCardUrl;
        $freelancer->pan_card_url = $panCardUrl;
        $freelancer->qualification_certificate_url = $qualificationCertificateUrl;

        $freelancer = JobRequest::with(['priceChangeRequests' => fn ($q) => $q->orderByDesc('created_at')->limit(20)])
            ->find($freelancer->id);

        $pricingService = app(\App\Services\FreelancerServicePriceChangeService::class);
        $serviceRow = \App\Models\Service::query()->where('name', $freelancer->job_title)->first();
        $pricingItems = $pricingService->pricingItemsForFreelancer($freelancer);
        $priceTypes = \App\Models\FreelancerServicePriceChangeRequest::priceTypesForShift($freelancer->shift);
        $requestsByKey = $freelancer->priceChangeRequests
            ->groupBy(fn ($r) => (int) $r->service_sub_service_id.'|'.(string) $r->price_type)
            ->map(fn ($group) => $group->first());

        $serviceId = $serviceRow ? (int) $serviceRow->id : 0;
        $pricingRows = [];
        foreach ($pricingItems as $item) {
            $subId = (int) $item['service_sub_service_id'];
            foreach ($priceTypes as $typeKey) {
                $reqKey = $subId.'|'.$typeKey;
                $latestReq = $requestsByKey[$reqKey] ?? null;
                $pricingRows[] = [
                    'service_sub_service_id' => $subId,
                    'label' => $item['label'],
                    'tags' => $item['tags'],
                    'price_type' => $typeKey,
                    'current_price' => $pricingService->currentPrice($freelancer, $serviceId, $subId, $typeKey),
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
            'freelancer' => $freelancer,
            'services' => $services,
            'locations' => $locations,
            'service_row' => $serviceRow ? ['id' => $serviceRow->id, 'name' => $serviceRow->name] : null,
            'pricing_items' => $pricingItems,
            'price_types' => $priceTypes,
            'pricing_rows' => $pricingRows,
        ]);
    }

    public function submitPriceChangeRequest(Request $request)
    {
        $user = $request->user();
        $freelancerInfo = Cache::get('freelancer_info_' . $user->id);

        if (!$freelancerInfo) {
            $mobile = $user->mobile ?? null;
            if (!$mobile) {
                return response()->json(['success' => false, 'message' => 'Mobile number not found'], 404);
            }
            $freelancer = JobRequest::where('mobile', $mobile)
                ->orWhere('contact_no', $mobile)
                ->first();
        } else {
            $freelancer = JobRequest::find($freelancerInfo['freelancer_id']);
        }

        if (!$freelancer) {
            return response()->json(['success' => false, 'message' => 'Freelancer not found'], 404);
        }

        $request->validate([
            'service_sub_service_id' => ['nullable', 'integer', 'min:0'],
            'price_type' => ['required', 'string', 'in:12hr,24hr,onetime'],
            'requested_price' => ['required', 'numeric', 'min:0.01'],
        ]);

        try {
            $changeRequest = app(\App\Services\FreelancerServicePriceChangeService::class)->submit($freelancer, [
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
            'request' => [
                'id' => $changeRequest->id,
                'status' => $changeRequest->status,
            ],
        ]);
    }

    public function statement(Request $request)
    {
        $user = $request->user();
        $freelancerInfo = Cache::get('freelancer_info_' . $user->id);

        if (!$freelancerInfo) {
            $mobile = $user->mobile ?? null;
            if (!$mobile) {
                return response()->json(['success' => false, 'message' => 'Mobile number not found'], 404);
            }
            $freelancer = JobRequest::where('mobile', $mobile)
                ->orWhere('contact_no', $mobile)
                ->first();
            if (!$freelancer) {
                return response()->json(['success' => false, 'message' => 'Freelancer not found'], 404);
            }
            $freelancerId = $freelancer->id;
        } else {
            $freelancerId = $freelancerInfo['freelancer_id'];
            $freelancer = JobRequest::find($freelancerId);
        }

        if (!$freelancer) {
            return response()->json(['success' => false, 'message' => 'Freelancer not found'], 404);
        }

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

        $paidPayments = FreelancerPayment::with(['jobRequest', 'createdBy'])
            ->where('job_request_id', $freelancerId)
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
            'freelancer' => [
                'id' => $freelancer->id,
                'name' => $freelancer->name,
                'lead_id' => $freelancer->lead_id,
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
        $freelancerInfo = Cache::get('freelancer_info_' . $user->id);

        if (!$freelancerInfo) {
            $mobile = $user->mobile ?? null;
            if (!$mobile) {
                return response()->json(['success' => false, 'message' => 'Mobile number not found'], 404);
            }
            $freelancer = JobRequest::where('mobile', $mobile)
                ->orWhere('contact_no', $mobile)
                ->first();
            if (!$freelancer) {
                return response()->json(['success' => false, 'message' => 'Freelancer not found'], 404);
            }
            $freelancerId = $freelancer->id;
        } else {
            $freelancerId = $freelancerInfo['freelancer_id'];
        }

        $validated = $request->validate([
            'operation_lead_ids' => 'required|array|min:1',
            'operation_lead_ids.*' => 'integer',
        ]);

        $rows = DeploymentLocationAttendance::where('freelancer_id', $freelancerId)
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
        $freelancerInfo = Cache::get('freelancer_info_' . $user->id);
        
        if (!$freelancerInfo) {
            $mobile = $user->mobile ?? null;
            if (!$mobile) {
                return response()->json(['success' => false, 'message' => 'Mobile number not found'], 404);
            }
            $freelancer = JobRequest::where('mobile', $mobile)
                ->orWhere('contact_no', $mobile)
                ->first();
            if (!$freelancer) {
                return response()->json(['success' => false, 'message' => 'Freelancer not found'], 404);
            }
        } else {
            $freelancer = JobRequest::find($freelancerInfo['freelancer_id']);
            if (!$freelancer) {
                return response()->json(['success' => false, 'message' => 'Freelancer not found'], 404);
            }
        }

        return response()->json([
            'success' => true,
            'freelancer' => $freelancer,
        ]);
    }

    public function assignedLeads(Request $request)
    {
        $user = $request->user();
        $freelancerInfo = Cache::get('freelancer_info_' . $user->id);
        
        if (!$freelancerInfo) {
            $mobile = $user->mobile ?? null;
            if (!$mobile) {
                return response()->json(['success' => false, 'message' => 'Mobile number not found'], 404);
            }
            $freelancer = JobRequest::where('mobile', $mobile)
                ->orWhere('contact_no', $mobile)
                ->first();
            if (!$freelancer) {
                return response()->json(['success' => false, 'message' => 'Freelancer not found'], 404);
            }
            $freelancerId = $freelancer->id;
        } else {
            $freelancerId = $freelancerInfo['freelancer_id'];
        }

        $query = OperationDeploymentDetails::where('freelance_staff_id', $freelancerId)
            ->with(['operationLead'])
            ->orderBy('created_at', 'desc');

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
            ->where('freelancer_id', $freelancerId)
            ->whereDate('attendance_date', now()->toDateString())
            ->get()
            ->keyBy('operation_lead_id');
        $deployments->getCollection()->transform(function ($deployment) use ($attendanceMap) {
            $attendance = $attendanceMap->get($deployment->operation_lead_id);
            $deployment->location_attendance = [
                'customer_location_shared' => (bool) ($attendance && $attendance->customer_location_captured_at),
                'freelancer_location_shared' => (bool) ($attendance && $attendance->freelancer_location_captured_at),
                'freelancer_selfie_uploaded' => (bool) ($attendance && $attendance->freelancer_selfie_path),
                'attendance_marked' => (bool) ($attendance && $attendance->attendance_marked_at),
                'attendance_status' => $attendance->attendance_status ?? 'pending',
                'customer_attendance_enabled' => (bool) ($deployment->operationLead && $deployment->operationLead->customer_location_attendance_enabled),
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
        $freelancerInfo = Cache::get('freelancer_info_' . $user->id);

        if (!$freelancerInfo) {
            $mobile = $user->mobile ?? null;
            if (!$mobile) {
                return response()->json(['success' => false, 'message' => 'Mobile number not found'], 404);
            }
            $freelancer = JobRequest::where('mobile', $mobile)
                ->orWhere('contact_no', $mobile)
                ->first();
            if (!$freelancer) {
                return response()->json(['success' => false, 'message' => 'Freelancer not found'], 404);
            }
            $freelancerId = $freelancer->id;
        } else {
            $freelancerId = $freelancerInfo['freelancer_id'];
        }

        $validated = $request->validate([
            'operation_lead_id' => 'required|integer|exists:operation_leads,id',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'selfie' => 'required|image|max:5120',
        ]);

        $lead = OperationLead::find($validated['operation_lead_id']);
        if (! $lead || ! $lead->customer_location_attendance_enabled) {
            return response()->json([
                'success' => false,
                'message' => 'Customer ne abhi location attendance enable nahi kiya. Customer ko dashboard par "Attendance mark" on karwana hoga.',
            ], 422);
        }

        $isAssigned = OperationDeploymentDetails::where('freelance_staff_id', $freelancerId)
            ->where('operation_lead_id', $validated['operation_lead_id'])
            ->exists();
        if (! $isAssigned) {
            return response()->json(['success' => false, 'message' => 'Lead is not assigned to this freelancer'], 403);
        }

        $attendance = DeploymentLocationAttendance::firstOrNew([
            'operation_lead_id' => $validated['operation_lead_id'],
            'vendor_id' => null,
            'freelancer_id' => $freelancerId,
            'attendance_date' => now()->toDateString(),
        ]);

        $attendance->freelancer_latitude = $validated['latitude'];
        $attendance->freelancer_longitude = $validated['longitude'];
        $attendance->freelancer_location_captured_at = now();

        if ($request->hasFile('selfie')) {
            if (! empty($attendance->freelancer_selfie_path)) {
                Storage::disk('public')->delete($attendance->freelancer_selfie_path);
            }
            $attendance->freelancer_selfie_path = $request->file('selfie')->store('deployment_attendance_selfies', 'public');
            $attendance->freelancer_selfie_captured_at = now();
        }

        if ($attendance->customer_location_captured_at) {
            $distance = $this->distanceInMeters(
                (float) $attendance->customer_latitude,
                (float) $attendance->customer_longitude,
                (float) $attendance->freelancer_latitude,
                (float) $attendance->freelancer_longitude
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
                : 'Freelancer location captured. Waiting for customer location.',
            'attendance' => [
                'customer_location_shared' => (bool) $attendance->customer_location_captured_at,
                'freelancer_location_shared' => (bool) $attendance->freelancer_location_captured_at,
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
        $freelancerInfo = Cache::get('freelancer_info_' . $user->id);
        
        if (!$freelancerInfo) {
            $mobile = $user->mobile ?? null;
            if (!$mobile) {
                return response()->json(['success' => false, 'message' => 'Mobile number not found'], 404);
            }
            $freelancer = JobRequest::where('mobile', $mobile)
                ->orWhere('contact_no', $mobile)
                ->first();
            if (!$freelancer) {
                return response()->json(['success' => false, 'message' => 'Freelancer not found'], 404);
            }
            $freelancerId = $freelancer->id;
        } else {
            $freelancerId = $freelancerInfo['freelancer_id'];
        }

        // Get total payment from verified deployments
        $totalPayment = OperationDeploymentDetails::where('freelance_staff_id', $freelancerId)
            ->where('verify_payment', true)
            ->sum('vendor_payment');

        // Get completed payments
        $completedPayments = FreelancerPayment::where('job_request_id', $freelancerId)
            ->where('status', 'completed')
            ->sum('amount');

        // Calculate pending payment
        $pendingPayment = $totalPayment - $completedPayments;

        // Get all deployments with payment details
        $deployments = OperationDeploymentDetails::where('freelance_staff_id', $freelancerId)
            ->where('verify_payment', true)
            ->with('operationLead')
            ->orderBy('deployment_from_date', 'desc')
            ->get();

        // Get all freelancer payments
        $freelancerPayments = FreelancerPayment::where('job_request_id', $freelancerId)
            ->orderBy('payment_date', 'desc')
            ->get();

        // Calculate payment allocations (FIFO)
        $paymentAllocations = [];
        $deploymentsOrdered = OperationDeploymentDetails::where('freelance_staff_id', $freelancerId)
            ->where('verify_payment', true)
            ->with('operationLead')
            ->orderBy('deployment_from_date', 'asc')
            ->get();

        $deploymentIndex = 0;
        foreach ($freelancerPayments as $payment) {
            if ($payment->status !== 'completed') {
                $paymentAllocations[$payment->id] = [];
                continue;
            }
            
            $remainingAmount = $payment->amount;
            $allocatedLeads = [];
            
            while ($remainingAmount > 0 && $deploymentIndex < $deploymentsOrdered->count()) {
                $deployment = $deploymentsOrdered[$deploymentIndex];
                $deploymentPayment = $deployment->vendor_payment ?? 0;
                
                if ($deploymentPayment > 0) {
                    $lead = $deployment->operationLead;
                    $leadId = $lead->lead_id ?? 'N/A';
                    
                    if ($remainingAmount >= $deploymentPayment) {
                        $allocatedLeads[] = $leadId;
                        $remainingAmount -= $deploymentPayment;
                        $deploymentIndex++;
                    } else {
                        $allocatedLeads[] = $leadId . ' (Partial)';
                        break;
                    }
                } else {
                    $deploymentIndex++;
                }
            }
            
            $paymentAllocations[$payment->id] = $allocatedLeads;
        }

        $freelancerPayments = $freelancerPayments->map(function($payment) use ($paymentAllocations) {
            $payment->allocated_leads = $paymentAllocations[$payment->id] ?? [];
            return $payment;
        });

        // Map deployments to leads with payment status
        $remainingPayment = $completedPayments;
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
            ];
        });

        return response()->json([
            'success' => true,
            'total_payment' => $totalPayment,
            'completed_payments' => $completedPayments,
            'pending_payment' => $pendingPayment,
            'leads_with_payments' => $leadsWithPayments,
            'freelancer_payments' => $freelancerPayments,
        ]);
    }

    public function updateProfileImage(Request $request)
    {
        $user = $request->user();
        $freelancerInfo = Cache::get('freelancer_info_' . $user->id);
        
        if (!$freelancerInfo) {
            $mobile = $user->mobile ?? null;
            if (!$mobile) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
            }
            $freelancer = JobRequest::where('mobile', $mobile)
                ->orWhere('contact_no', $mobile)
                ->first();
            if (!$freelancer) {
                return response()->json(['success' => false, 'message' => 'Freelancer not found'], 404);
            }
        } else {
            $freelancer = JobRequest::find($freelancerInfo['freelancer_id']);
            if (!$freelancer) {
                return response()->json(['success' => false, 'message' => 'Freelancer not found'], 404);
            }
        }

        if (!$request->hasFile('profile_image')) {
            return response()->json(['success' => false, 'message' => 'No image file found']);
        }

        $result = $freelancer->applyProfileImageUpload($request->file('profile_image'));

        return response()->json($result);
    }

    public function uploadDocument(Request $request)
    {
        $user = $request->user();
        $freelancerInfo = Cache::get('freelancer_info_' . $user->id);
        
        if (!$freelancerInfo) {
            $mobile = $user->mobile ?? null;
            if (!$mobile) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
            }
            $freelancer = JobRequest::where('mobile', $mobile)
                ->orWhere('contact_no', $mobile)
                ->first();
            if (!$freelancer) {
                return response()->json(['success' => false, 'message' => 'Freelancer not found'], 404);
            }
        } else {
            $freelancer = JobRequest::find($freelancerInfo['freelancer_id']);
            if (!$freelancer) {
                return response()->json(['success' => false, 'message' => 'Freelancer not found'], 404);
            }
        }

        $request->validate([
            'document' => 'required|file|mimes:jpeg,jpg,png,pdf|max:10240',
            'document_type' => 'required|in:aadhar_card,pan_card,qualification_certificate'
        ]);

        $file = $request->file('document');
        $documentType = $request->document_type;
        $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
        $path = $file->storeAs('documents/freelancers', $filename, 'public');

        if ($freelancer->$documentType) {
            Storage::disk('public')->delete($freelancer->$documentType);
        }

        $freelancer->$documentType = $path;
        $freelancer->save();

        $documentUrl = Storage::url($path);
        $documentName = ucwords(str_replace('_', ' ', $documentType));

        return response()->json([
            'success' => true,
            'message' => $documentName . ' uploaded successfully',
            'document_url' => $documentUrl,
            'document_name' => $documentName
        ]);
    }

    public function uploadBankDocument(Request $request)
    {
        $user = $request->user();
        $freelancerInfo = Cache::get('freelancer_info_' . $user->id);
        
        if (!$freelancerInfo) {
            $mobile = $user->mobile ?? null;
            if (!$mobile) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
            }
            $freelancer = JobRequest::where('mobile', $mobile)
                ->orWhere('contact_no', $mobile)
                ->first();
            if (!$freelancer) {
                return response()->json(['success' => false, 'message' => 'Freelancer not found'], 404);
            }
        } else {
            $freelancer = JobRequest::find($freelancerInfo['freelancer_id']);
            if (!$freelancer) {
                return response()->json(['success' => false, 'message' => 'Freelancer not found'], 404);
            }
        }

        $request->validate([
            'document' => 'required|file|mimes:jpeg,jpg,png,pdf|max:10240'
        ]);

        $file = $request->file('document');
        $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
        $path = $file->storeAs('documents/freelancers', $filename, 'public');

        if ($freelancer->bank_document) {
            Storage::disk('public')->delete($freelancer->bank_document);
        }

        $freelancer->bank_document = $path;
        $freelancer->save();

        $documentUrl = Storage::url($path);

        return response()->json([
            'success' => true,
            'message' => 'Bank document uploaded successfully',
            'document_url' => $documentUrl
        ]);
    }

    public function updateBankDetails(Request $request)
    {
        $user = $request->user();
        $freelancerInfo = Cache::get('freelancer_info_' . $user->id);
        
        if (!$freelancerInfo) {
            $mobile = $user->mobile ?? null;
            if (!$mobile) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
            }
            $freelancer = JobRequest::where('mobile', $mobile)
                ->orWhere('contact_no', $mobile)
                ->first();
            if (!$freelancer) {
                return response()->json(['success' => false, 'message' => 'Freelancer not found'], 404);
            }
        } else {
            $freelancer = JobRequest::find($freelancerInfo['freelancer_id']);
            if (!$freelancer) {
                return response()->json(['success' => false, 'message' => 'Freelancer not found'], 404);
            }
        }

        $request->validate([
            'field_name' => 'required|string',
            'field_value' => 'nullable|string|max:255'
        ]);

        $fieldName = $request->field_name;
        $fieldValue = $request->field_value;

        $allowedFields = ['account_name', 'account_number', 'ifsc_code', 'upi_id'];
        
        if (!in_array($fieldName, $allowedFields)) {
            return response()->json(['success' => false, 'message' => 'Invalid field name']);
        }

        $currentValue = $freelancer->$fieldName;
        if ($currentValue) {
            return response()->json(['success' => false, 'message' => 'This field is already filled and cannot be edited']);
        }

        $freelancer->$fieldName = $fieldValue;
        $freelancer->save();

        return response()->json([
            'success' => true,
            'message' => ucfirst(str_replace('_', ' ', $fieldName)) . ' updated successfully',
            'field_value' => $fieldValue
        ]);
    }

    public function updatePersonalDetails(Request $request)
    {
        $user = $request->user();
        $freelancerInfo = Cache::get('freelancer_info_' . $user->id);
        
        if (!$freelancerInfo) {
            $mobile = $user->mobile ?? null;
            if (!$mobile) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
            }
            $freelancer = JobRequest::where('mobile', $mobile)
                ->orWhere('contact_no', $mobile)
                ->first();
            if (!$freelancer) {
                return response()->json(['success' => false, 'message' => 'Freelancer not found'], 404);
            }
        } else {
            $freelancer = JobRequest::find($freelancerInfo['freelancer_id']);
            if (!$freelancer) {
                return response()->json(['success' => false, 'message' => 'Freelancer not found'], 404);
            }
        }

        $request->validate([
            'field_name' => 'required|string',
            'field_value' => 'nullable|string|max:255'
        ]);

        $fieldName = $request->field_name;
        $fieldValue = $request->field_value;

        $allowedFields = ['name', 'customer_name', 'age', 'gender', 'contact_no', 'mobile', 'job_title', 'location', 'total_experience', 'expected_salary', 'shift'];
        
        if (!in_array($fieldName, $allowedFields)) {
            return response()->json(['success' => false, 'message' => 'Invalid field name']);
        }

        $currentValue = $freelancer->$fieldName;
        if ($currentValue) {
            if ($fieldName === 'age') {
                if (strpos($currentValue, '|') !== false) {
                    $agePart = explode('|', $currentValue)[0];
                    if ($agePart) {
                        return response()->json(['success' => false, 'message' => 'This field is already filled and cannot be edited']);
                    }
                } else if ($currentValue) {
                    return response()->json(['success' => false, 'message' => 'This field is already filled and cannot be edited']);
                }
            } else {
                return response()->json(['success' => false, 'message' => 'This field is already filled and cannot be edited']);
            }
        }

        if ($fieldName === 'age') {
            if ($currentValue && strpos($currentValue, '|') !== false) {
                $parts = explode('|', $currentValue);
                $fieldValue = $fieldValue . '|' . ($parts[1] ?? '');
            } else if ($request->has('gender') && $request->gender) {
                $fieldValue = $fieldValue . '|' . $request->gender;
            }
        }

        $freelancer->$fieldName = $fieldValue;
        $freelancer->save();

        return response()->json([
            'success' => true,
            'message' => ucfirst(str_replace('_', ' ', $fieldName)) . ' updated successfully',
            'field_value' => $fieldValue
        ]);
    }

    // Customer Chats methods - similar to vendor
    public function chats(Request $request)
    {
        $user = $request->user();
        $freelancerInfo = Cache::get('freelancer_info_' . $user->id);
        
        if (!$freelancerInfo) {
            $mobile = $user->mobile ?? null;
            if (!$mobile) {
                return response()->json(['success' => false, 'message' => 'Mobile number not found'], 404);
            }
            $freelancer = JobRequest::where('mobile', $mobile)
                ->orWhere('contact_no', $mobile)
                ->first();
            if (!$freelancer) {
                return response()->json(['success' => false, 'message' => 'Freelancer not found'], 404);
            }
            $freelancerId = $freelancer->id;
        } else {
            $freelancerId = $freelancerInfo['freelancer_id'];
        }

        // Get all deployments assigned to this freelancer
        $deployments = OperationDeploymentDetails::where('freelance_staff_id', $freelancerId)
            ->with('operationLead')
            ->get();

        // Get unique customers by contact_no
        $customersByContact = collect();
        foreach ($deployments as $deployment) {
            $lead = $deployment->operationLead;
            if ($lead && $lead->contact_no) {
                $contactNo = $lead->contact_no;
                if (!$customersByContact->has($contactNo)) {
                    // Get all operation lead IDs with this contact_no
                    $operationLeadIds = OperationLead::where('contact_no', $contactNo)
                        ->pluck('id')
                        ->toArray();

                    // Get unread count across all operation leads with this contact_no
                    $unreadCount = CustomerChatMessage::where('receiver_type', 'freelancer')
                        ->where('receiver_id', $freelancerId)
                        ->where('sender_type', 'customer')
                        ->whereIn('sender_id', $operationLeadIds)
                        ->where('is_read', false)
                        ->count();

                    // Get last message across all operation leads with this contact_no
                    $lastMessage = CustomerChatMessage::where(function($q) use ($freelancerId, $operationLeadIds) {
                        $q->where(function($q2) use ($freelancerId, $operationLeadIds) {
                            $q2->where('sender_type', 'freelancer')
                               ->where('sender_id', $freelancerId)
                               ->where('receiver_type', 'customer')
                               ->whereIn('receiver_id', $operationLeadIds);
                        })->orWhere(function($q2) use ($freelancerId, $operationLeadIds) {
                            $q2->where('sender_type', 'customer')
                               ->whereIn('sender_id', $operationLeadIds)
                               ->where('receiver_type', 'freelancer')
                               ->where('receiver_id', $freelancerId);
                        });
                    })->orderBy('created_at', 'desc')->first();

                    // Use the first operation lead ID as the primary customer ID
                    $primaryCustomerId = $operationLeadIds[0] ?? $lead->id;

                    // Generate profile image URL
                    $profileImage = $lead->profile_image ?? null;
                    $profileImageUrl = null;
                    if ($profileImage) {
                        if (strpos($profileImage, 'http') === 0) {
                            $profileImageUrl = $profileImage;
                        } else {
                            // Generate full URL using request's scheme and host
                            $storagePath = Storage::disk('public')->url($profileImage);
                            // If Storage::url() returns a relative path, prepend the request URL
                            if (strpos($storagePath, 'http') !== 0) {
                                $scheme = $request->getScheme();
                                $host = $request->getHost();
                                $port = $request->getPort();
                                $baseUrl = $scheme . '://' . $host . ($port && $port != 80 && $port != 443 ? ':' . $port : '');
                                $profileImageUrl = $baseUrl . $storagePath;
                            } else {
                                $profileImageUrl = $storagePath;
                            }
                        }
                    }

                    $customersByContact->put($contactNo, [
                        'id' => $primaryCustomerId,
                        'customer_name' => $lead->customer_name ?? 'Customer',
                        'contact_no' => $lead->contact_no ?? 'N/A',
                        'profile_image' => $profileImage,
                        'profile_image_url' => $profileImageUrl,
                        'unread_count' => $unreadCount,
                        'last_message' => $lastMessage,
                    ]);
                }
            }
        }

        $customers = $customersByContact->values();

        return response()->json([
            'success' => true,
            'customers' => $customers->values(),
        ]);
    }

    public function getMessages($customerId, Request $request)
    {
        $user = $request->user();
        $freelancerInfo = Cache::get('freelancer_info_' . $user->id);
        
        if (!$freelancerInfo) {
            $mobile = $user->mobile ?? null;
            if (!$mobile) {
                return response()->json(['success' => false, 'message' => 'Mobile number not found'], 404);
            }
            $freelancer = JobRequest::where('mobile', $mobile)
                ->orWhere('contact_no', $mobile)
                ->first();
            if (!$freelancer) {
                return response()->json(['success' => false, 'message' => 'Freelancer not found'], 404);
            }
            $freelancerId = $freelancer->id;
        } else {
            $freelancerId = $freelancerInfo['freelancer_id'];
        }

        // Verify assignment - check if freelancer is assigned to this customer
        $isAssigned = OperationDeploymentDetails::where('freelance_staff_id', $freelancerId)
            ->where('operation_lead_id', $customerId)
            ->exists();

        if (!$isAssigned) {
            // Check if there are existing messages (customer might have been reassigned but chat history exists)
            $hasMessages = CustomerChatMessage::where(function($q) use ($freelancerId, $customerId) {
                $q->where(function($q2) use ($freelancerId, $customerId) {
                    $q2->where('sender_type', 'freelancer')
                       ->where('sender_id', $freelancerId)
                       ->where('receiver_type', 'customer')
                       ->where('receiver_id', $customerId);
                })->orWhere(function($q2) use ($freelancerId, $customerId) {
                    $q2->where('sender_type', 'customer')
                       ->where('sender_id', $customerId)
                       ->where('receiver_type', 'freelancer')
                       ->where('receiver_id', $freelancerId);
                });
            })->exists();

            if (!$hasMessages) {
                return response()->json(['success' => false, 'message' => 'Customer not assigned to you'], 403);
            }
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

        \Log::info('Freelancer getMessages: Query parameters', [
            'freelancer_id' => $freelancerId,
            'customer_id' => $customerId,
            'customer_contact_no' => $customerContactNo,
            'operation_lead_ids' => $operationLeadIds,
            'all_customer_ids' => $allCustomerIds,
        ]);

        // Build query for messages - check both OperationLead IDs and Lead IDs
        $query = CustomerChatMessage::where(function($q) use ($operationLeadIds, $allCustomerIds, $freelancerId) {
            $q->where(function($subQ) use ($operationLeadIds, $freelancerId) {
                $subQ->where('sender_type', 'freelancer')
                     ->where('sender_id', $freelancerId)
                     ->where('receiver_type', 'customer')
                     ->whereIn('receiver_id', $operationLeadIds);
            })
            ->orWhere(function($subQ) use ($allCustomerIds, $freelancerId) {
                // Check for messages from customers (both OperationLead and Lead IDs)
                $subQ->where('sender_type', 'customer')
                     ->whereIn('sender_id', $allCustomerIds)
                     ->where('receiver_type', 'freelancer')
                     ->where('receiver_id', $freelancerId);
            });
        });
        
        \Log::info('Freelancer getMessages: SQL query', [
            'sql' => $query->toSql(),
            'bindings' => $query->getBindings(),
        ]);

        if ($request->has('last_message_id') && $request->last_message_id > 0) {
            $query->where('id', '>', $request->last_message_id);
        }

        $messages = $query->with('repliedTo')->orderBy('created_at', 'asc')->get();
        
        \Log::info('Freelancer getMessages: Messages found', [
            'count' => $messages->count(),
            'message_ids' => $messages->pluck('id')->toArray(),
            'last_message_id_param' => $request->last_message_id ?? null,
        ]);

        // Get call history - use allCustomerIds for customer calls
        $callsQuery = CustomerChatCall::where(function($q) use ($operationLeadIds, $allCustomerIds, $freelancerId) {
            $q->where('caller_type', 'freelancer')
              ->where('caller_id', $freelancerId)
              ->where('receiver_type', 'customer')
              ->whereIn('receiver_id', $operationLeadIds);
        })->orWhere(function($q) use ($allCustomerIds, $freelancerId) {
            $q->where('caller_type', 'customer')
              ->whereIn('caller_id', $allCustomerIds)
              ->where('receiver_type', 'freelancer')
              ->where('receiver_id', $freelancerId);
        });

        if ($request->has('last_message_id') && $request->last_message_id > 0) {
            $lastMessage = CustomerChatMessage::find($request->last_message_id);
            if ($lastMessage) {
                $callsQuery->where('created_at', '>', $lastMessage->created_at);
            }
        }

        $calls = $callsQuery->orderBy('created_at', 'asc')->get();

        // Combine messages and calls
        $items = collect();
        
        foreach ($messages as $message) {
            $items->push([
                'type' => 'message',
                'id' => $message->id,
                'created_at' => $message->created_at->toISOString(),
                'data' => $message,
            ]);
        }

        foreach ($calls as $call) {
            $items->push([
                'type' => 'call',
                'id' => 'call_' . $call->id,
                'created_at' => ($call->call_started_at ?? $call->created_at)->toISOString(),
                'data' => $call,
            ]);
        }

        // Sort by created_at
        $items = $items->sortBy('created_at')->values();

        return response()->json($items);
    }

    public function sendMessage(Request $request)
    {
        $user = $request->user();
        $freelancerInfo = Cache::get('freelancer_info_' . $user->id);
        
        if (!$freelancerInfo) {
            $mobile = $user->mobile ?? null;
            if (!$mobile) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
            }
            $freelancer = JobRequest::where('mobile', $mobile)
                ->orWhere('contact_no', $mobile)
                ->first();
            if (!$freelancer) {
                return response()->json(['success' => false, 'message' => 'Freelancer not found'], 404);
            }
            $freelancerId = $freelancer->id;
        } else {
            $freelancerId = $freelancerInfo['freelancer_id'];
        }

        $request->validate([
            'receiver_id' => 'required|integer|exists:operation_leads,id',
            'message' => 'required_without:attachment|string|nullable',
            'attachment' => 'nullable|file|max:10240',
            'reply_to_id' => 'nullable|integer|exists:customer_chat_messages,id',
        ]);

        // Verify assignment - check if freelancer is assigned to this customer
        $isAssigned = OperationDeploymentDetails::where('freelance_staff_id', $freelancerId)
            ->where('operation_lead_id', $request->receiver_id)
            ->exists();

        if (!$isAssigned) {
            return response()->json(['success' => false, 'message' => 'Customer not assigned to you'], 403);
        }

        $message = new CustomerChatMessage();
        $message->sender_type = 'freelancer';
        $message->sender_id = $freelancerId;
        $message->receiver_type = 'customer';
        $message->receiver_id = $request->receiver_id;
        $message->message = $request->message;
        $message->is_read = false;

        if ($request->has('reply_to_id')) {
            $message->reply_to_id = $request->reply_to_id;
        }

        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('attachments', $filename, 'public');
            $message->attachment = $path;
            $message->attachment_type = $file->getMimeType();
        }

        $message->save();

        return response()->json([
            'success' => true,
            'message' => $message,
        ]);
    }

    public function markAsRead($customerId, Request $request)
    {
        $user = $request->user();
        $freelancerInfo = Cache::get('freelancer_info_' . $user->id);
        
        if (!$freelancerInfo) {
            $mobile = $user->mobile ?? null;
            if (!$mobile) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
            }
            $freelancer = JobRequest::where('mobile', $mobile)
                ->orWhere('contact_no', $mobile)
                ->first();
            if (!$freelancer) {
                return response()->json(['success' => false, 'message' => 'Freelancer not found'], 404);
            }
            $freelancerId = $freelancer->id;
        } else {
            $freelancerId = $freelancerInfo['freelancer_id'];
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

        CustomerChatMessage::where('receiver_type', 'freelancer')
            ->where('receiver_id', $freelancerId)
            ->where('sender_type', 'customer')
            ->whereIn('sender_id', $operationLeadIds)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return response()->json(['success' => true]);
    }

    public function viewDocument(Request $request, $documentType, $freelancerId)
    {
        Log::info('Freelancer document request received', [
            'document_type' => $documentType,
            'freelancer_id' => $freelancerId,
            'has_token' => $request->has('token'),
        ]);
        
        // Support token authentication via query parameter for Image component
        $token = $request->query('token');
        $user = null;
        
        if ($token) {
            try {
                $user = \Laravel\Sanctum\PersonalAccessToken::findToken($token)?->tokenable;
            } catch (\Exception $e) {
                Log::error('Error finding token:', ['error' => $e->getMessage()]);
            }
        } else {
            $user = $request->user();
        }
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 401);
        }

        // Get freelancer info
        $freelancerInfo = Cache::get('freelancer_info_' . $user->id);
        
        if (!$freelancerInfo) {
            $mobile = $user->mobile ?? null;
            if (!$mobile) {
                return response()->json([
                    'success' => false,
                    'message' => 'Freelancer information not found'
                ], 404);
            }
            $freelancer = JobRequest::where('mobile', $mobile)
                ->orWhere('contact_no', $mobile)
                ->first();
            if (!$freelancer) {
                return response()->json([
                    'success' => false,
                    'message' => 'Freelancer not found'
                ], 404);
            }
        } else {
            $freelancer = JobRequest::find($freelancerInfo['freelancer_id']);
            if (!$freelancer) {
                return response()->json([
                    'success' => false,
                    'message' => 'Freelancer not found'
                ], 404);
            }
        }

        // Verify the document belongs to this freelancer
        if ($freelancer->id != $freelancerId) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access to this document'
            ], 403);
        }

        // Get document path based on type
        $documentPath = null;
        $allowedTypes = ['aadhar_card', 'pan_card', 'qualification_certificate'];
        
        if (!in_array($documentType, $allowedTypes)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid document type'
            ], 400);
        }

        $documentPath = $freelancer->$documentType;

        if (!$documentPath) {
            return response()->json([
                'success' => false,
                'message' => 'Document not found'
            ], 404);
        }

        $path = storage_path('app/public/' . $documentPath);

        if (!file_exists($path)) {
            Log::warning('Document file not found', [
                'freelancer_id' => $freelancerId,
                'document_type' => $documentType,
                'document_path' => $documentPath,
                'full_path' => $path
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Document file not found on server'
            ], 404);
        }

        // Determine content type based on file extension
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $contentType = 'image/jpeg'; // default
        if ($extension === 'png') {
            $contentType = 'image/png';
        } elseif ($extension === 'gif') {
            $contentType = 'image/gif';
        } elseif ($extension === 'webp') {
            $contentType = 'image/webp';
        } elseif ($extension === 'pdf') {
            $contentType = 'application/pdf';
        }

        Log::info('Serving freelancer document file', [
            'freelancer_id' => $freelancerId,
            'document_type' => $documentType,
            'file_path' => $path,
            'file_exists' => file_exists($path),
            'file_size' => file_exists($path) ? filesize($path) : 0,
            'content_type' => $contentType
        ]);
        
        // Use response()->file() which properly handles binary files for React Native
        return response()->file($path, [
            'Content-Type' => $contentType,
            'Cache-Control' => 'public, max-age=3600',
            'Access-Control-Allow-Origin' => '*',
            'Access-Control-Allow-Methods' => 'GET, OPTIONS',
            'Access-Control-Allow-Headers' => 'Content-Type, Authorization',
        ]);
    }
}

