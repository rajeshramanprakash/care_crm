<?php

namespace App\Http\Controllers\Freelancer;

use App\Http\Controllers\Controller;
use App\Models\JobRequest;
use App\Models\OperationDeploymentDetails;
use App\Models\FreelancerPayment;
use App\Models\DeploymentLocationAttendance;
use App\Models\OperationLead;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class FreelancerController extends Controller
{
    public function dashboard()
    {
        // Get freelancer ID from session
        $freelancerId = Session::get('freelancer_id');
        
        if (!$freelancerId) {
            return redirect()->route('home')->with('error', 'Please login to access freelancer dashboard.');
        }

        $freelancer = JobRequest::find($freelancerId);
        
        if (!$freelancer) {
            Session::forget(['freelancer_id', 'freelancer_name', 'login_type']);
            return redirect()->route('home')->with('error', 'Freelancer account not found.');
        }

        // Get active deployments (In Progress, Active, Ongoing status)
        $activeDeployments = OperationDeploymentDetails::where('freelance_staff_id', $freelancerId)
            ->whereIn('deployment_status', ['In Progress', 'Active', 'Ongoing'])
            ->with('operationLead')
            ->orderBy('deployment_from_date', 'desc')
            ->get();

        // Get all deployments for reference
        $deployments = OperationDeploymentDetails::where('freelance_staff_id', $freelancerId)
            ->with('operationLead')
            ->orderBy('created_at', 'desc')
            ->get();

        // Get job request details
        $page_heading = 'Freelancer Dashboard';
        $freelancerName = $freelancer->name ?? 'N/A';

        return view('freelancer.dashboard', compact('page_heading', 'freelancer', 'freelancerName', 'deployments', 'activeDeployments'));
    }

    public function personalDetails()
    {
        $freelancerId = Session::get('freelancer_id');
        
        if (!$freelancerId) {
            return redirect()->route('home')->with('error', 'Please login to access freelancer dashboard.');
        }

        $freelancer = JobRequest::with(['priceChangeRequests' => fn ($q) => $q->orderByDesc('created_at')->limit(20)])
            ->find($freelancerId);
        
        if (!$freelancer) {
            Session::forget(['freelancer_id', 'freelancer_name', 'login_type']);
            return redirect()->route('home')->with('error', 'Freelancer account not found.');
        }

        $pricingService = app(\App\Services\FreelancerServicePriceChangeService::class);
        $serviceRow = \App\Models\Service::query()->where('name', $freelancer->job_title)->first();
        $pricingItems = $pricingService->pricingItemsForFreelancer($freelancer);
        $priceTypes = \App\Models\FreelancerServicePriceChangeRequest::priceTypesForShift($freelancer->shift);
        $requestsByKey = $freelancer->priceChangeRequests
            ->groupBy(fn ($r) => (int) $r->service_sub_service_id.'|'.(string) $r->price_type)
            ->map(fn ($group) => $group->first());

        // Get services and locations for display (if needed)
        $services = \App\Models\Service::orderBy('name', 'asc')->get();
        $locations = \App\Models\Location::orderBy('name', 'asc')->get();

        $page_heading = 'Personal Details';
        $freelancerName = $freelancer->name ?? 'N/A';

        return view('freelancer.personal-details', compact(
            'page_heading',
            'freelancer',
            'freelancerName',
            'services',
            'locations',
            'serviceRow',
            'pricingItems',
            'priceTypes',
            'requestsByKey',
            'pricingService'
        ));
    }

    public function updateProfileImage(Request $request)
    {
        $freelancerId = Session::get('freelancer_id');
        
        if (!$freelancerId) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        if (!$request->hasFile('profile_image')) {
            return response()->json(['success' => false, 'message' => 'No image file found']);
        }

        $freelancer = JobRequest::find($freelancerId);
        
        if (!$freelancer) {
            return response()->json(['success' => false, 'message' => 'Freelancer not found']);
        }

        $result = $freelancer->applyProfileImageUpload($request->file('profile_image'));

        return response()->json($result);
    }

    public function uploadDocument(Request $request)
    {
        $freelancerId = Session::get('freelancer_id');
        
        if (!$freelancerId) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $request->validate([
            'document' => 'required|file|mimes:jpeg,jpg,png,pdf|max:10240',
            'document_type' => 'required|in:aadhar_card,pan_card,qualification_certificate'
        ]);

        $freelancer = JobRequest::find($freelancerId);
        
        if (!$freelancer) {
            return response()->json(['success' => false, 'message' => 'Freelancer not found']);
        }

        $file = $request->file('document');
        $documentType = $request->document_type;
        $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
        $path = $file->storeAs('public/documents/freelancers', $filename);

        // Delete old document if exists
        if ($freelancer->$documentType) {
            Storage::disk('public')->delete($freelancer->$documentType);
        }

        // Update document
        $freelancer->$documentType = 'documents/freelancers/' . $filename;
        $freelancer->save();

        $documentUrl = asset('storage/documents/freelancers/' . $filename);

        return response()->json([
            'success' => true,
            'message' => 'Document uploaded successfully',
            'document_url' => $documentUrl,
            'document_name' => ucfirst(str_replace('_', ' ', $documentType))
        ]);
    }

    public function uploadBankDocument(Request $request)
    {
        $freelancerId = Session::get('freelancer_id');
        
        if (!$freelancerId) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $request->validate([
            'document' => 'required|file|mimes:jpeg,jpg,png,pdf|max:10240'
        ]);

        $freelancer = JobRequest::find($freelancerId);
        
        if (!$freelancer) {
            return response()->json(['success' => false, 'message' => 'Freelancer not found']);
        }

        $file = $request->file('document');
        $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
        $path = $file->storeAs('public/documents/freelancers', $filename);

        // Delete old bank document if exists
        if ($freelancer->bank_document) {
            Storage::disk('public')->delete($freelancer->bank_document);
        }

        // Update bank document
        $freelancer->bank_document = 'documents/freelancers/' . $filename;
        $freelancer->save();

        $documentUrl = asset('storage/documents/freelancers/' . $filename);

        return response()->json([
            'success' => true,
            'message' => 'Bank document uploaded successfully',
            'document_url' => $documentUrl
        ]);
    }

    public function bankDetails()
    {
        $freelancerId = Session::get('freelancer_id');
        
        if (!$freelancerId) {
            return redirect()->route('home')->with('error', 'Please login to access freelancer dashboard.');
        }

        $freelancer = JobRequest::find($freelancerId);
        
        if (!$freelancer) {
            Session::forget(['freelancer_id', 'freelancer_name', 'login_type']);
            return redirect()->route('home')->with('error', 'Freelancer account not found.');
        }

        $page_heading = 'Bank Account Details';
        $freelancerName = $freelancer->name ?? 'N/A';

        return view('freelancer.bank-details', compact('page_heading', 'freelancer', 'freelancerName'));
    }

    public function emergencyDetails()
    {
        $freelancerId = Session::get('freelancer_id');
        
        if (!$freelancerId) {
            return redirect()->route('home')->with('error', 'Please login to access freelancer dashboard.');
        }

        $freelancer = JobRequest::find($freelancerId);
        
        if (!$freelancer) {
            Session::forget(['freelancer_id', 'freelancer_name', 'login_type']);
            return redirect()->route('home')->with('error', 'Freelancer account not found.');
        }

        $page_heading = 'Emergency Details';
        $freelancerName = $freelancer->name ?? 'N/A';

        return view('freelancer.emergency-details', compact('page_heading', 'freelancer', 'freelancerName'));
    }

    public function customerChats()
    {
        $freelancerId = Session::get('freelancer_id');
        
        if (!$freelancerId) {
            return redirect()->route('home')->with('error', 'Please login to access freelancer dashboard.');
        }

        $freelancer = JobRequest::find($freelancerId);
        
        if (!$freelancer) {
            Session::forget(['freelancer_id', 'freelancer_name', 'login_type']);
            return redirect()->route('home')->with('error', 'Freelancer account not found.');
        }

        $page_heading = 'Customer Chats';
        $freelancerName = $freelancer->name ?? 'N/A';

        return view('freelancer.customer-chats', compact('page_heading', 'freelancer', 'freelancerName'));
    }

    public function assignedLeads(Request $request)
    {
        $freelancerId = Session::get('freelancer_id');
        
        if (!$freelancerId) {
            return redirect()->route('home')->with('error', 'Please login to access freelancer dashboard.');
        }

        $freelancer = JobRequest::find($freelancerId);
        
        if (!$freelancer) {
            Session::forget(['freelancer_id', 'freelancer_name', 'login_type']);
            return redirect()->route('home')->with('error', 'Freelancer account not found.');
        }

        // Get all deployments assigned to this freelancer
        $query = OperationDeploymentDetails::where('freelance_staff_id', $freelancerId)
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
            ->where('freelancer_id', $freelancerId)
            ->whereDate('attendance_date', $today)
            ->get()
            ->keyBy('operation_lead_id');
        $deployments->getCollection()->transform(function ($deployment) use ($attendanceMap) {
            $attendance = $attendanceMap->get($deployment->operation_lead_id);
            $deployment->today_attendance = $attendance;
            return $deployment;
        });

        $page_heading = 'Assigned Leads';
        $freelancerName = $freelancer->name ?? 'N/A';

        return view('freelancer.assigned-leads', compact('page_heading', 'freelancer', 'freelancerName', 'deployments'));
    }

    public function submitAttendanceLocation(Request $request)
    {
        $freelancerId = Session::get('freelancer_id');
        if (!$freelancerId) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
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
                'message' => 'Customer ne abhi location attendance enable nahi kiya. Customer ko apne dashboard par "Attendance mark" on karwana hoga.',
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
                ? 'Attendance marked successfully.'
                : 'Location captured. Waiting for customer location.',
            'attendance_status' => $attendance->attendance_status,
            'is_location_matched' => (bool) $attendance->is_location_matched,
            'distance_meters' => $attendance->distance_meters,
        ]);
    }

    public function attendanceStatuses(Request $request)
    {
        $freelancerId = Session::get('freelancer_id');
        if (!$freelancerId) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $validated = $request->validate([
            'operation_lead_ids' => 'required|array|min:1',
            'operation_lead_ids.*' => 'integer|exists:operation_leads,id',
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

    public function updatePersonalDetails(Request $request)
    {
        $freelancerId = Session::get('freelancer_id');
        
        if (!$freelancerId) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $freelancer = JobRequest::find($freelancerId);
        
        if (!$freelancer) {
            return response()->json(['success' => false, 'message' => 'Freelancer not found']);
        }

        $request->validate([
            'field_name' => 'required|string',
            'field_value' => 'nullable|string|max:255'
        ]);

        $fieldName = $request->field_name;
        $fieldValue = $request->field_value;

        // Only allow updating fields that are currently empty
        $allowedFields = ['name', 'customer_name', 'age', 'gender', 'contact_no', 'mobile', 'job_title', 'location', 'total_experience', 'expected_salary', 'shift'];
        
        if (!in_array($fieldName, $allowedFields)) {
            return response()->json(['success' => false, 'message' => 'Invalid field name']);
        }

        // Check if field is already filled (don't allow editing existing values)
        $currentValue = $freelancer->$fieldName;
        if ($currentValue) {
            // For age, check if it contains pipe (age|gender format) - if it does, extract just age part
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

        // Special handling for age and gender
        if ($fieldName === 'age') {
            // If age already has gender, preserve it
            if ($currentValue && strpos($currentValue, '|') !== false) {
                $parts = explode('|', $currentValue);
                $fieldValue = $fieldValue . '|' . ($parts[1] ?? '');
            } else {
                // If gender is provided separately, combine them
                if ($request->has('gender') && $request->gender) {
                    $fieldValue = $fieldValue . '|' . $request->gender;
                }
            }
        }

        // Update the field
        $freelancer->$fieldName = $fieldValue;
        $freelancer->save();

        return response()->json([
            'success' => true,
            'message' => ucfirst(str_replace('_', ' ', $fieldName)) . ' updated successfully',
            'field_value' => $fieldValue
        ]);
    }

    public function submitPriceChangeRequest(Request $request)
    {
        $freelancerId = Session::get('freelancer_id');
        if (! $freelancerId) {
            return response()->json(['success' => false, 'message' => 'Please login to your freelancer account.'], 401);
        }

        $freelancer = JobRequest::find($freelancerId);
        if (! $freelancer) {
            return response()->json(['success' => false, 'message' => 'Freelancer account not found.'], 404);
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

    public function updateBankDetails(Request $request)
    {
        $freelancerId = Session::get('freelancer_id');
        
        if (!$freelancerId) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $freelancer = JobRequest::find($freelancerId);
        
        if (!$freelancer) {
            return response()->json(['success' => false, 'message' => 'Freelancer not found']);
        }

        $request->validate([
            'field_name' => 'required|string',
            'field_value' => 'nullable|string|max:255'
        ]);

        $fieldName = $request->field_name;
        $fieldValue = $request->field_value;

        // Only allow updating bank detail fields that are currently empty
        $allowedFields = ['account_name', 'account_number', 'ifsc_code', 'upi_id'];
        
        if (!in_array($fieldName, $allowedFields)) {
            return response()->json(['success' => false, 'message' => 'Invalid field name']);
        }

        // Check if field is already filled (don't allow editing existing values)
        $currentValue = $freelancer->$fieldName;
        if ($currentValue) {
            return response()->json(['success' => false, 'message' => 'This field is already filled and cannot be edited']);
        }

        // Update the field
        $freelancer->$fieldName = $fieldValue;
        $freelancer->save();

        return response()->json([
            'success' => true,
            'message' => ucfirst(str_replace('_', ' ', $fieldName)) . ' updated successfully',
            'field_value' => $fieldValue
        ]);
    }

    public function paymentDetails()
    {
        $freelancerId = Session::get('freelancer_id');
        
        if (!$freelancerId) {
            return redirect()->route('home')->with('error', 'Please login to access freelancer dashboard.');
        }

        $freelancer = JobRequest::find($freelancerId);
        
        if (!$freelancer) {
            Session::forget(['freelancer_id', 'freelancer_name', 'login_type']);
            return redirect()->route('home')->with('error', 'Freelancer account not found.');
        }

        // Get total payment from verified deployments
        $totalPayment = OperationDeploymentDetails::where('freelance_staff_id', $freelancerId)
            ->where('verify_payment', true)
            ->sum('vendor_payment');

        // Get completed payments from FreelancerPayment table
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

        // Get all freelancer payments ordered by date (oldest first for FIFO allocation)
        $freelancerPayments = FreelancerPayment::where('job_request_id', $freelancerId)
            ->orderBy('payment_date', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        // Get deployments ordered by date (oldest first for FIFO allocation)
        $deploymentsOrdered = OperationDeploymentDetails::where('freelance_staff_id', $freelancerId)
            ->where('verify_payment', true)
            ->with('operationLead')
            ->orderBy('deployment_from_date', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        // Allocate payments to deployments using FIFO logic
        $paymentAllocations = [];
        $deploymentIndex = 0;
        
        foreach ($freelancerPayments as $payment) {
            if ($payment->status !== 'completed') {
                $paymentAllocations[$payment->id] = [];
                continue;
            }
            
            $remainingAmount = $payment->amount;
            $allocatedLeads = [];
            
            // Allocate payment to deployments in order
            while ($remainingAmount > 0 && $deploymentIndex < $deploymentsOrdered->count()) {
                $deployment = $deploymentsOrdered[$deploymentIndex];
                $deploymentPayment = $deployment->vendor_payment ?? 0;
                
                if ($deploymentPayment > 0) {
                    $lead = $deployment->operationLead;
                    $leadId = $lead->lead_id ?? 'N/A';
                    
                    if ($remainingAmount >= $deploymentPayment) {
                        // Full payment for this deployment
                        $allocatedLeads[] = $leadId;
                        $remainingAmount -= $deploymentPayment;
                        $deploymentIndex++;
                    } else {
                        // Partial payment - this deployment is partially paid
                        $allocatedLeads[] = $leadId . ' (Partial)';
                        break; // Payment exhausted
                    }
                } else {
                    $deploymentIndex++;
                }
            }
            
            $paymentAllocations[$payment->id] = $allocatedLeads;
        }

        // Re-order payments by date desc for display
        $freelancerPayments = FreelancerPayment::where('job_request_id', $freelancerId)
            ->orderBy('payment_date', 'desc')
            ->orderBy('id', 'desc')
            ->get()
            ->map(function($payment) use ($paymentAllocations) {
                $payment->allocated_leads = $paymentAllocations[$payment->id] ?? [];
                return $payment;
            });

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
        $freelancerName = $freelancer->name ?? 'N/A';

        return view('freelancer.payment-details', compact(
            'page_heading',
            'freelancer',
            'freelancerName',
            'totalPayment',
            'completedPayments',
            'pendingPayment',
            'leadsWithPayments',
            'freelancerPayments'
        ));
    }

    /**
     * Show statement for logged-in freelancer (same as admin statement view).
     */
    public function statement()
    {
        $freelancerId = Session::get('freelancer_id');
        if (!$freelancerId) {
            return redirect()->route('home')->with('error', 'Please login to access.');
        }
        $freelancer = JobRequest::find($freelancerId);
        if (!$freelancer) {
            Session::forget(['freelancer_id', 'freelancer_name', 'login_type']);
            return redirect()->route('home')->with('error', 'Freelancer account not found.');
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
}
