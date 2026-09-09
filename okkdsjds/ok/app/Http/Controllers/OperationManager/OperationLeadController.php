<?php

namespace App\Http\Controllers\OperationManager;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\OperationLead;
use Yajra\DataTables\Facades\DataTables;
use App\Models\Vendor;
use App\Models\Location;
use App\Models\Service;
use App\Models\WhatsappMsgGroup;
use Illuminate\Support\Facades\Auth;
use App\Models\OperationLeadsPaymentDetail;
use App\Models\OperationDeploymentDetails;
use Illuminate\Support\Facades\Storage;
use App\Models\User;
use Illuminate\Support\Facades\DB;


class OperationLeadController extends Controller
{
    public function index()
    {
        $vendors = Vendor::all();
        $auth_user = Auth::user();
        $locations = Location::get();
        $executives = User::where('parent_id', $auth_user->id)->get();
        $services = Service::get();
        return view('operation_manager.operation_leads.index', compact('vendors', 'executives', 'locations', 'services'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'date_time' => 'nullable|date',
            'executive' => 'nullable|exists:users,id',
            'customer_name' => 'nullable|string',
            'contact_no' => 'required|string',
            'address' => 'nullable|string',
            'location' => 'nullable|string',
            'query' => 'nullable',
            'query_remark' => 'nullable|string',
            'status' => 'nullable|in:profile,profile pending,profile shared,closed,follow up,inactive,price issue',
            'shift_type' => 'nullable|in:12hr,24hr,both',
            'status_remark' => 'nullable|string',
            'price_issue_remark' => 'nullable|string',
            'inactive_remark' => 'nullable|string',
            'closed_remark' => 'nullable|string',
            'patient_name' => 'nullable|string',
            'patient_gender' => 'nullable|in:male,female,other',
            'age' => 'nullable|integer',
            'closed_rate' => 'nullable|numeric',
            'vendor_id' => 'nullable',
            'staff_name' => 'nullable|string',
            'vendor_closed_rate' => 'nullable|numeric',
            'outstanding_payment' => 'nullable|numeric',
            'payment_plan' => 'nullable|string',
            'ongoing_stopped' => 'nullable|string',
        ]);

        // Generate lead_id
        $latestLead = OperationLead::orderBy('id', 'desc')->first();
        $nextNumber = $latestLead ? $latestLead->id + 1 : 1;
        $validated['lead_id'] = 'CHO' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);

        $number = $validated['contact_no'];
        $execId = $validated['executive'];
        $group = WhatsappMsgGroup::where('whatsapp_number', $number)->first();
        if ($group) {
            $ids = array_filter(explode(',', $group->executive_ids));
            if (!in_array($execId, $ids)) {
                $ids[] = $execId;
                $group->executive_ids = implode(',', $ids);
                $group->save();
            }
        } else {
            WhatsappMsgGroup::create([
                'whatsapp_number' => $number,
                'executive_ids' => $execId,
            ]);
        }

        // Set current date/time if not provided
        if (empty($validated['date_time'])) {
            $validated['date_time'] = now();
        }
        
        $lead = OperationLead::create($validated);

        // Check if request is from mobile app
        if ($request->wantsJson() || $request->has('mobile_app')) {
            return response()->json(['message' => 'Operation Lead added successfully', 'lead' => $lead]);
        }

        return response()->json(['message' => 'Operation Lead added successfully', 'lead' => $lead]);
    }

    public function getLeads(Request $request)
    {        $auth_user = Auth::user();
        $users_ids = User::where('parent_id', $auth_user->id)->pluck('id');

        $latestDeployments = DB::table('operation_deployment_details as odd')
        ->select(DB::raw('MAX(odd.id) as id'))
        ->groupBy('odd.operation_lead_id');

    // Join with latest deployment details
    $leads = OperationLead::select(
            'operation_leads.*',
            'vendors.name as vendor_name',
            'users.f_name as executive_name',
            'odd.vendor_id as latest_vendor_id'
        )
        ->leftJoin('users', 'operation_leads.executive', '=', 'users.id')->whereIn('operation_leads.executive', $users_ids)
        ->leftJoin(\DB::raw('operation_deployment_details as odd'), function($join) use ($latestDeployments) {
            $join->on('operation_leads.id', '=', 'odd.operation_lead_id')
                 ->whereIn('odd.id', $latestDeployments);
        })
        ->leftJoin('vendors', 'odd.vendor_id', '=', 'vendors.id');

    if ($request->executives != null) {
        $leads->whereIn('operation_leads.executive', $request->executives);
    }

    if ($request->location != null) {
        $leads->whereIn('operation_leads.location', $request->location);
    }

    if ($request->query_filter != null) {
        $leads->whereIn('operation_leads.query', $request->query_filter);
    }
    if ($request->vendor != null) {
        $leads->whereIn('odd.vendor_id', $request->vendor);
    }
    if ($request->status != null) {
        $leads->whereIn('operation_leads.status', $request->status);
    }
    if ($request->ongoing_stopped != null) {
        $leads->whereIn('operation_leads.ongoing_stopped', $request->ongoing_stopped);
    }

    return DataTables::of($leads)
        ->addColumn('id', function($lead) {
            return $lead->id;
        })
        ->addColumn('vendor_name', function($lead) { return $lead->vendor_name; })
        ->addColumn('executive_name', function($lead) { return $lead->executive_name; })
        ->addColumn('last_call_status', function($lead) {
            return $lead->last_call_status_display;
        })
        ->addColumn('available_vendors_count', function($lead) {
            $count = $this->getFilteredVendors($lead->location, $lead->query)->count();
            $location = $lead->location;
            $query = $lead->query;
            return "<button type=\"button\" class=\"btn btn-sm vendor-count-btn\" data-lead-id=\"{$lead->id}\" data-location=\"{$location}\" data-query=\"{$query}\" title=\"View Available Vendors\">
                                <i class=\"fas fa-users me-1\"></i> {$count}
                            </button>";
        })
        ->addColumn('action', function($lead) {
            return '
                <div class="action-dropdown">
                    <button class="action-dropdown-btn" onclick="event.stopPropagation(); toggleDropdown(this)">
                        <i class="fas fa-ellipsis-v"></i>
                    </button>
                    <div class="action-dropdown-menu">
                        <button class="edit-btn" data-id="'.$lead->id.'"><i class="fas fa-pen icon-edit"></i> Edit</button>
                        <a href="'.route('admin.operation_leads.show', $lead->id).'" class="view-btn" target="_blank"><i class="fas fa-eye icon-view"></i> View</a>
                        <button class="delete-btn" data-id="'.$lead->id.'"><i class="fas fa-trash icon-delete"></i> Delete</button>
                    </div>
                </div>';
        })
        ->rawColumns(['screenshot','available_vendors_count','action'])
        ->make(true);
    }

    public function show($id)
    {
        $lead = OperationLead::select('operation_leads.*', 'vendors.name as vendor_name', 'users.f_name as executive_name')
            ->leftJoin('vendors', 'operation_leads.vendor_id', '=', 'vendors.id')
            ->leftJoin('users', 'operation_leads.executive', '=', 'users.id')
            ->findOrFail($id);

        $vendors = Vendor::all();
        $filteredVendors = $this->getFilteredVendors($lead->location, $lead->query);
        $services = Service::get();
        $locations = Location::get();
        $executives = User::whereRaw('FIND_IN_SET(role_id, "4")')->get(['id', 'f_name']);

        // Get payment invoices with received payments
        $paymentInvoices = \App\Models\PaymentInvoice::where('operation_lead_id', $lead->id)
            ->with('receivedPayments')
            ->orderBy('from_date', 'asc')
            ->get();

        // Get all received payments for this lead
        $receivedPayments = \App\Models\ReceivedPayment::where('operation_lead_id', $lead->id)
            ->with('paymentInvoice')
            ->orderBy('received_date', 'desc')
            ->get();

        $deploymentDetails = OperationDeploymentDetails::with(['vendor', 'freelanceStaff'])->where('operation_lead_id', $lead->id)
            ->get();

        $leadId = \App\Models\Lead::extractIdFromFormattedId($lead->lead_id);
        $crm_lead = $leadId ? \App\Models\Lead::find($leadId) : null;

        // Check if request is from mobile app
        if (request()->wantsJson() || request()->has('mobile_app')) {
            return response()->json([
                'lead' => $lead,
                'paymentInvoices' => $paymentInvoices,
                'receivedPayments' => $receivedPayments,
                'deploymentDetails' => $deploymentDetails,
                'vendors' => $vendors,
                'filteredVendors' => $filteredVendors,
                'services' => $services,
                'crm_lead' => $crm_lead,
                'locations' => $locations
            ]);
        }

        return view('operation_manager.operation_leads.show', compact('lead', 'paymentInvoices', 'receivedPayments', 'vendors', 'filteredVendors', 'services', 'deploymentDetails', 'crm_lead', 'locations', 'executives'));
    }

    public function edit($id)
    {
        $lead = OperationLead::with('crmLead')->findOrFail($id);
        
        // Get related Lead data if exists
        $relatedLead = null;
        if ($lead->crmLead) {
            $relatedLead = $lead->crmLead;
        } else {
            // Try to find by contact number if no direct relationship
            $relatedLead = \App\Models\Lead::where('contact_no', $lead->contact_no)->first();
        }
        
        $leadData = $lead->toArray();
        $leadData['related_lead'] = $relatedLead;
        
        return response()->json($leadData);
    }

    public function update(Request $request, $id)
    {

        $validationRules = [
            'date_time' => 'nullable|date',
            'executive' => 'nullable|exists:users,id',
            'customer_name' => 'nullable|string',
            'contact_no' => 'nullable|string',
            'address' => 'nullable|string',
            'location' => 'nullable|string',
            'query' => 'nullable',
            'query_remark' => 'nullable|string',
            'status' => 'nullable|in:profile,profile pending,profile shared,closed,follow up,inactive,price issue',
            'shift_type' => 'nullable|in:12hr,24hr,both',
            'status_remark' => 'nullable|string',
            'price_issue_remark' => 'nullable|string',
            'inactive_remark' => 'nullable|string',
            'closed_remark' => 'nullable|string',
            'patient_name' => 'nullable|string',
            'age' => 'nullable|integer',
            'closed_rate' => 'nullable|numeric',
            'vendor_id' => 'exists:vendors,id',
            'staff_name' => 'nullable|string',
            'vendor_closed_rate' => 'nullable|numeric',
            'outstanding_payment' => 'nullable|numeric',
            'payment_plan' => 'nullable|string',
            'ongoing_stopped' => 'nullable|string',
        ];



        $validated = $request->validate($validationRules);

        $lead = OperationLead::findOrFail($id);
        
        // Check if ongoing_stopped is changing to 'stopped'
        $wasOngoing = $lead->ongoing_stopped === 'ongoing';
        $nowStopped = isset($validated['ongoing_stopped']) && $validated['ongoing_stopped'] === 'stopped';
        
        $lead->update($validated);
        
        // If lead is being stopped, update all "In Progress" deployments
        if ($wasOngoing && $nowStopped) {
            $currentDateTime = now();
            \App\Models\OperationDeploymentDetails::where('operation_lead_id', $lead->id)
                ->where('deployment_status', 'In Progress')
                ->update([
                    'deployment_status' => 'Stop',
                    'deployment_to_date' => $currentDateTime,
                    'updated_at' => $currentDateTime
                ]);
        }

        // Check if request is from mobile app
        if ($request->wantsJson() || $request->has('mobile_app')) {
            return response()->json(['message' => 'Operation Lead updated successfully', 'lead' => $lead]);
        }

        return response()->json(['message' => 'Operation Lead updated successfully', 'lead' => $lead]);
    }

    public function destroy($id)
    {
        $lead = OperationLead::findOrFail($id);
        $lead->delete();
        return response()->json(['message' => 'Operation Lead deleted successfully']);
    }

    public function storePaymentDetail(Request $request, $leadId)
    {
        \Log::info('OperationManager storePaymentDetail called', [
            'leadId' => $leadId,
            'request_data' => $request->all(),
            'files' => $request->allFiles(),
            'method' => $request->method(),
            'headers' => $request->headers->all()
        ]);

        $request->validate([
            'payment_received' => 'nullable|numeric|min:0',
            'refund_amount' => 'nullable|numeric|min:0',
            'utr_number' => 'nullable|string|max:255',
            'received_date' => 'nullable|date',
            'from_date_time' => 'nullable|date',
            'to_date_time' => 'nullable|date|after:from_date_time',
            'screenshot' => 'required|image|mimes:jpeg,png,jpg,gif,pdf|max:20480',
            'remark' => 'nullable|string|max:1000',
        ], [
            'screenshot.required' => 'Please upload a payment screenshot. This is required for verification.',
            'screenshot.mimes' => 'Screenshot must be an image (jpeg, png, jpg, gif) or PDF file.',
            'screenshot.max' => 'Screenshot file size must not exceed 20MB.'
        ]);

        // Check for duplicate UTR number
        if ($request->filled('utr_number')) {
            if (OperationLeadsPaymentDetail::utrNumberExists($request->utr_number)) {
                return response()->json([
                    'success' => false,
                    'message' => 'UTR number already exists. Please use a different UTR number.'
                ], 422);
            }
        }

        $data = $request->all();
        $data['operation_lead_id'] = $leadId;

        // Calculate outstanding payment automatically
        $lead = OperationLead::findOrFail($leadId);
        $closedRate = $lead->closed_rate ?: 0; // This is now per day rate

        // Calculate outstanding payment for this specific payment detail
        if ($request->filled('from_date_time') && $request->filled('to_date_time') && $request->filled('payment_received')) {
            try {
                $fromDate = \Carbon\Carbon::parse($request->from_date_time);
                $toDate = \Carbon\Carbon::parse($request->to_date_time);
                $paymentReceived = (float) $request->payment_received;
                $refundAmount = (float) ($request->refund_amount ?: 0);

                // Calculate work days (inclusive of both start and end dates)
                $workDays = $fromDate->diffInDays($toDate) + 1;

                // Calculate total expected amount for this payment period
                $totalExpectedAmount = $workDays * $closedRate;

                // Calculate outstanding payment for this specific payment detail
                $netAmount = $paymentReceived - $refundAmount;
                $outstandingAmount = $netAmount - $totalExpectedAmount;

                $data['outstanding_payment'] = $outstandingAmount; // Allow negative values

                \Log::info('Payment outstanding calculated:', [
                    'from_date' => $fromDate->toDateTimeString(),
                    'to_date' => $toDate->toDateTimeString(),
                    'work_days' => $workDays,
                    'closed_rate_per_day' => $closedRate,
                    'total_expected' => $totalExpectedAmount,
                    'payment_received' => $paymentReceived,
                    'refund_amount' => $refundAmount,
                    'outstanding_payment' => $data['outstanding_payment']
                ]);
            } catch (\Exception $e) {
                \Log::error('Error calculating outstanding payment: ' . $e->getMessage());
                $data['outstanding_payment'] = 0;
            }
        } else {
            $data['outstanding_payment'] = 0;
        }

        // Handle file uploads
        if ($request->hasFile('screenshot')) {
            $data['screenshot'] = $request->file('screenshot')->store('payment-screenshots', 'public');
        }

        $payment = OperationLeadsPaymentDetail::create($data);

        // Check if request is from mobile app
        if ($request->wantsJson() || $request->has('mobile_app')) {
            return response()->json([
                'success' => true,
                'message' => 'Payment detail added successfully',
                'payment' => $payment
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Payment detail added successfully',
            'payment' => $payment
        ]);
    }

    public function editPaymentDetail($id)
    {
        $payment = OperationLeadsPaymentDetail::findOrFail($id);
        return response()->json([
            'success' => true,
            'payment' => $payment
        ]);
    }

    public function updatePaymentDetail(Request $request, $id)
    {
        \Log::info('OperationManager updatePaymentDetail called', [
            'id' => $id,
            'request_data' => $request->all(),
            'files' => $request->allFiles(),
            'method' => $request->method(),
            'headers' => $request->headers->all(),
            'url' => $request->url(),
            'route' => $request->route()->getName()
        ]);

        try {

        $request->validate([
            'payment_received' => 'nullable|numeric|min:0',
            'refund_amount' => 'nullable|numeric|min:0',
            'utr_number' => 'nullable|string|max:255',
            'received_date' => 'nullable|date',
            'from_date_time' => 'nullable|date',
            'to_date_time' => 'nullable|date',
            'screenshot' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'outstanding_payment' => 'nullable|numeric',
            'remark' => 'nullable|string|max:1000',
        ]);

        // Custom validation for date range
        if ($request->filled('from_date_time') && $request->filled('to_date_time')) {
            if ($request->from_date_time >= $request->to_date_time) {
                return response()->json([
                    'success' => false,
                    'message' => 'To Date & Time must be after From Date & Time.'
                ], 422);
            }
        }

        // Check for duplicate UTR number (excluding current record)
        if ($request->filled('utr_number')) {
            if (OperationLeadsPaymentDetail::utrNumberExists($request->utr_number, $id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'UTR number already exists. Please use a different UTR number.'
                ], 422);
            }
        }

        $payment = OperationLeadsPaymentDetail::findOrFail($id);
        $data = $request->all();

        // Calculate outstanding payment automatically
        $lead = OperationLead::findOrFail($payment->operation_lead_id);
        $closedRate = $lead->closed_rate ?: 0; // This is now per day rate

        // Calculate outstanding payment for this specific payment detail
        if ($request->filled('from_date_time') && $request->filled('to_date_time') && $request->filled('payment_received')) {
            try {
                $fromDate = \Carbon\Carbon::parse($request->from_date_time);
                $toDate = \Carbon\Carbon::parse($request->to_date_time);
                $paymentReceived = (float) $request->payment_received;
                $refundAmount = (float) ($request->refund_amount ?: 0);

                // Calculate work days (inclusive of both start and end dates)
                $workDays = $fromDate->diffInDays($toDate) + 1;

                // Calculate total expected amount for this payment period
                $totalExpectedAmount = $workDays * $closedRate;

                // Calculate outstanding payment for this specific payment detail
                $netAmount = $paymentReceived - $refundAmount;
                $outstandingAmount = $netAmount - $totalExpectedAmount;

                $data['outstanding_payment'] = $outstandingAmount; // Allow negative values

                \Log::info('Payment outstanding calculated (update):', [
                    'from_date' => $fromDate->toDateTimeString(),
                    'to_date' => $toDate->toDateTimeString(),
                    'work_days' => $workDays,
                    'closed_rate_per_day' => $closedRate,
                    'total_expected' => $totalExpectedAmount,
                    'payment_received' => $paymentReceived,
                    'refund_amount' => $refundAmount,
                    'outstanding_payment' => $data['outstanding_payment']
                ]);
            } catch (\Exception $e) {
                \Log::error('Error calculating outstanding payment: ' . $e->getMessage());
                $data['outstanding_payment'] = 0;
            }
        } else {
            $data['outstanding_payment'] = 0;
        }

        // Handle file uploads
        if ($request->hasFile('screenshot')) {
            // Delete old screenshot if exists
            if ($payment->screenshot) {
                Storage::disk('public')->delete($payment->screenshot);
            }
            $data['screenshot'] = $request->file('screenshot')->store('payment-screenshots', 'public');
        }

        $payment->update($data);

        // Check if request is from mobile app
        if ($request->wantsJson() || $request->has('mobile_app')) {
            return response()->json([
                'success' => true,
                'message' => 'Payment detail updated successfully',
                'payment' => $payment
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Payment detail updated successfully',
            'payment' => $payment
        ]);

        } catch (\Exception $e) {
            \Log::error('Error updating payment detail: ' . $e->getMessage(), [
                'id' => $id,
                'request_data' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);

            if ($request->wantsJson() || $request->has('mobile_app')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error updating payment detail: ' . $e->getMessage()
                ], 500);
            }

            return response()->json([
                'success' => false,
                'message' => 'Error updating payment detail: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroyPaymentDetail($id)
    {
        $payment = OperationLeadsPaymentDetail::findOrFail($id);

        if ($payment->screenshot) {
            Storage::disk('public')->delete($payment->screenshot);
        }
        if ($payment->invoice) {
            Storage::disk('public')->delete($payment->invoice);
        }

        $payment->delete();

        return redirect()->back()->with('success', 'Payment detail deleted successfully');
    }

    public function storeDeploymentDetail(Request $request, $leadId)
    {
        try {
            \Log::info('OperationManager storeDeploymentDetail called', [
                'leadId' => $leadId,
                'request_data' => $request->all(),
                'method' => $request->method()
            ]);

            $validationRules = [
                'deployment_date' => 'nullable|date',
                'deployment_from_date' => 'nullable|date',
                'deployment_to_date' => 'nullable|date',
                'deployment_status' => 'required|string',
                'duty_hours' => 'nullable|string|in:12hr,24hr',
                'vendor_id' => 'nullable|exists:vendors,id',
                'staff_name' => 'nullable|string',
                'vendor_rate_per_day' => 'nullable|numeric',
                'verify_payment' => 'nullable|boolean',
                'remark' => 'nullable|string|max:1000',
            ];
            
            // Make deployment_to_date required if status is not "Pending" or "In Progress"
            $status = $request->input('deployment_status');
            if ($status && !in_array($status, ['Pending', 'In Progress'])) {
                $validationRules['deployment_to_date'] = 'required|date';
            }
            
            $request->validate($validationRules);

            // Custom validation for date range
            if ($request->filled('deployment_from_date') && $request->filled('deployment_to_date')) {
                if ($request->deployment_from_date >= $request->deployment_to_date) {
                    return response()->json([
                        'success' => false,
                        'message' => 'To Date & Time must be after From Date & Time.'
                    ], 422);
                }
            }

            $data = $request->all();
            $data['operation_lead_id'] = $leadId;

            // Handle freelance staff vs regular vendor
            if (isset($data['vendor_id']) && strpos($data['vendor_id'], 'freelance_') === 0) {
                // This is a freelance staff member
                $freelanceId = str_replace('freelance_', '', $data['vendor_id']);
                $freelanceStaff = \App\Models\JobRequest::find($freelanceId);
                if ($freelanceStaff) {
                    $data['vendor_id'] = null; // No vendor ID for freelance
                    // Don't auto-fill staff name - let user enter it manually
                    $data['freelance_staff_id'] = $freelanceId; // Store freelance staff ID
                }
            } else {
                // Convert vendor_id to integer if it's not empty
                if (isset($data['vendor_id']) && $data['vendor_id'] !== '') {
                    $data['vendor_id'] = (int) $data['vendor_id'];
                } else {
                    $data['vendor_id'] = null;
                }
                $data['freelance_staff_id'] = null; // Clear freelance staff ID for regular vendors
            }

            // Ensure all required fields are present and properly formatted
            $data['deployment_date'] = $data['deployment_date'] ?? null;
            $data['deployment_from_date'] = $data['deployment_from_date'] ?? null;
            $data['deployment_to_date'] = $data['deployment_to_date'] ?? null;
            $data['deployment_status'] = $data['deployment_status'] ?? '';
            $data['duty_hours'] = $data['duty_hours'] ?? null;
            $data['staff_name'] = $data['staff_name'] ?? null;
            $data['vendor_rate_per_day'] = $data['vendor_rate_per_day'] ?? null;
            $data['remark'] = $data['remark'] ?? null;

            // Calculate vendor payment automatically
            if ($request->filled('deployment_from_date') && $request->filled('deployment_to_date') && $request->filled('vendor_rate_per_day')) {
                try {
                    $fromDate = \Carbon\Carbon::parse($request->deployment_from_date);
                    $toDate = \Carbon\Carbon::parse($request->deployment_to_date);
                    $ratePerDay = (float) $request->vendor_rate_per_day;

                    // Calculate work days (inclusive of both start and end dates)
                    $workDays = $fromDate->diffInDays($toDate) + 1;
                    $vendorPayment = $workDays * $ratePerDay;

                    $data['vendor_payment'] = $vendorPayment;

                    \Log::info('Vendor payment calculated:', [
                        'from_date' => $fromDate->toDateTimeString(),
                        'to_date' => $toDate->toDateTimeString(),
                        'work_days' => $workDays,
                        'rate_per_day' => $ratePerDay,
                        'vendor_payment' => $vendorPayment
                    ]);
                } catch (\Exception $e) {
                    \Log::error('Error calculating vendor payment: ' . $e->getMessage());
                    $data['vendor_payment'] = 0;
                }
            } else {
                $data['vendor_payment'] = 0;
            }

            \Log::info('Creating deployment with data:', $data);

            $deployment = OperationDeploymentDetails::create($data);

            \Log::info('Deployment created successfully');

            // Generate payment invoice if deployment is In Progress
            if ($deployment->deployment_status === 'In Progress') {
                \App\Services\PaymentInvoiceService::generateInvoicesForDeployment($deployment);
                \Log::info('Payment invoice generation triggered for deployment');
            }

            return response()->json([
                'success' => true,
                'message' => 'Deployment detail added successfully',
                'deployment' => $deployment
            ]);

        } catch (\Exception $e) {
            \Log::error('Error creating deployment detail: ' . $e->getMessage(), [
                'leadId' => $leadId,
                'request_data' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error creating deployment detail: ' . $e->getMessage()
            ], 500);
        }
    }

    public function editDeploymentDetail($id)
    {
        $deployment = OperationDeploymentDetails::with(['vendor', 'freelanceStaff'])->findOrFail($id);
        return response()->json([
            'success' => true,
            'deployment' => $deployment
        ]);
    }

    public function updateDeploymentDetail(Request $request, $id)
    {
        try {
            \Log::info('OperationManager updateDeploymentDetail called', [
                'id' => $id,
                'request_data' => $request->all(),
                'method' => $request->method(),
                'headers' => $request->headers->all()
            ]);

            $deployment = OperationDeploymentDetails::findOrFail($id);
            
            $validationRules = [
                'deployment_date' => 'nullable|date',
                'deployment_from_date' => 'nullable|date',
                'deployment_to_date' => 'nullable|date',
                'deployment_status' => 'required|string',
                'duty_hours' => 'nullable|string|in:12hr,24hr',
                'vendor_id' => 'nullable|exists:vendors,id',
                'staff_name' => 'nullable|string',
                'vendor_rate_per_day' => 'nullable|numeric',
                'verify_payment' => 'nullable|boolean',
                'remark' => 'nullable|string|max:1000',
            ];
            
            // Make deployment_to_date required if status is changing from "In Progress" to something else (except "Pending")
            $oldStatus = $deployment->deployment_status;
            $newStatus = $request->input('deployment_status');
            
            if ($oldStatus === 'In Progress' && $newStatus && !in_array($newStatus, ['Pending', 'In Progress'])) {
                $validationRules['deployment_to_date'] = 'required|date';
            }
            
            $request->validate($validationRules);

            // Custom validation for date range
            if ($request->filled('deployment_from_date') && $request->filled('deployment_to_date')) {
                if ($request->deployment_from_date >= $request->deployment_to_date) {
                    return response()->json([
                        'success' => false,
                        'message' => 'To Date & Time must be after From Date & Time.'
                    ], 422);
                }
            }

            $data = $request->all();

            // Handle freelance staff vs regular vendor
            if (isset($data['vendor_id']) && strpos($data['vendor_id'], 'freelance_') === 0) {
                // This is a freelance staff member
                $freelanceId = str_replace('freelance_', '', $data['vendor_id']);
                $freelanceStaff = \App\Models\JobRequest::find($freelanceId);
                if ($freelanceStaff) {
                    $data['vendor_id'] = null; // No vendor ID for freelance
                    // Don't auto-fill staff name - let user enter it manually
                    $data['freelance_staff_id'] = $freelanceId; // Store freelance staff ID
                }
            } else {
                // Convert vendor_id to integer if it's not empty
                if (isset($data['vendor_id']) && $data['vendor_id'] !== '') {
                    $data['vendor_id'] = (int) $data['vendor_id'];
                } else {
                    $data['vendor_id'] = null;
                }
                $data['freelance_staff_id'] = null; // Clear freelance staff ID for regular vendors
            }

            // Ensure all required fields are present and properly formatted
            $data['deployment_date'] = $data['deployment_date'] ?? null;
            $data['deployment_from_date'] = $data['deployment_from_date'] ?? null;
            $data['deployment_to_date'] = $data['deployment_to_date'] ?? null;
            $data['deployment_status'] = $data['deployment_status'] ?? '';
            $data['duty_hours'] = $data['duty_hours'] ?? null;
            $data['staff_name'] = $data['staff_name'] ?? null;
            $data['vendor_rate_per_day'] = $data['vendor_rate_per_day'] ?? null;
            $data['remark'] = $data['remark'] ?? null;

            // Calculate vendor payment automatically
            if ($request->filled('deployment_from_date') && $request->filled('deployment_to_date') && $request->filled('vendor_rate_per_day')) {
                try {
                    $fromDate = \Carbon\Carbon::parse($request->deployment_from_date);
                    $toDate = \Carbon\Carbon::parse($request->deployment_to_date);
                    $ratePerDay = (float) $request->vendor_rate_per_day;

                    // Calculate work days (inclusive of both start and end dates)
                    $workDays = $fromDate->diffInDays($toDate) + 1;
                    $vendorPayment = $workDays * $ratePerDay;

                    $data['vendor_payment'] = $vendorPayment;

                    \Log::info('Vendor payment calculated:', [
                        'from_date' => $fromDate->toDateTimeString(),
                        'to_date' => $toDate->toDateTimeString(),
                        'work_days' => $workDays,
                        'rate_per_day' => $ratePerDay,
                        'vendor_payment' => $vendorPayment
                    ]);
                } catch (\Exception $e) {
                    \Log::error('Error calculating vendor payment: ' . $e->getMessage());
                    $data['vendor_payment'] = 0;
                }
            } else {
                $data['vendor_payment'] = 0;
            }

            \Log::info('Updating deployment with data:', $data);

            $deployment->update($data);

            \Log::info('Deployment updated successfully');

            return response()->json([
                'success' => true,
                'message' => 'Deployment detail updated successfully',
                'deployment' => $deployment
            ]);

        } catch (\Exception $e) {
            \Log::error('Error updating deployment detail: ' . $e->getMessage(), [
                'id' => $id,
                'request_data' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error updating deployment detail: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroyDeploymentDetail($id)
    {
        $deployment = OperationDeploymentDetails::findOrFail($id);
        $deployment->delete();

        return response()->json([
            'success' => true,
            'message' => 'Deployment detail deleted successfully'
        ]);
    }

    public function getLeadsForApp(Request $request)
    {
        $auth_user = Auth::user();
        $users_ids = User::where('parent_id', $auth_user->id)->pluck('id');

        $latestDeployments = DB::table('operation_deployment_details as odd')
            ->select(DB::raw('MAX(odd.id) as id'))
            ->groupBy('odd.operation_lead_id');

        $leads = OperationLead::select(
                'operation_leads.*',
                'vendors.name as vendor_name',
                'users.f_name as executive_name',
                'odd.vendor_id as latest_vendor_id'
            )
            ->leftJoin('users', 'operation_leads.executive', '=', 'users.id')
            ->whereIn('operation_leads.executive', $users_ids)
            ->leftJoin(\DB::raw('operation_deployment_details as odd'), function($join) use ($latestDeployments) {
                $join->on('operation_leads.id', '=', 'odd.operation_lead_id')
                     ->whereIn('odd.id', $latestDeployments);
            })
            ->leftJoin('vendors', 'odd.vendor_id', '=', 'vendors.id')
            ->orderBy('operation_leads.id', 'desc')
            ->get();

        return response()->json($leads);
    }

    public function getExecutives(Request $request)
    {
        $auth_user = Auth::user();
        $executives = User::where('parent_id', $auth_user->id)->get(['id', 'f_name']);
        return response()->json($executives);
    }

    public function getVendors(Request $request)
    {
        $vendors = Vendor::all(['id', 'name']);
        return response()->json($vendors);
    }

    /**
     * Filter vendors based on location and service
     */
    private function getFilteredVendors($location, $service, $includeInactive = false)
    {
        // Get the service ID from service name
        $serviceModel = Service::where('name', $service)->first();
        if (!$serviceModel) {
            return collect();
        }

        // Get the location ID from location name
        $locationModel = Location::where('name', $location)->first();
        if (!$locationModel) {
            return collect();
        }

        // Get only active vendors and filter them
        $vendors = Vendor::where('status', 'active')->get();

        $filteredVendors = $vendors->filter(function ($vendor) use ($serviceModel, $locationModel) {
            if (!$vendor->service_city_shifts || !is_array($vendor->service_city_shifts)) {
                return false;
            }

            // Check if vendor provides this service in this location
            foreach ($vendor->service_city_shifts as $serviceData) {
                if (isset($serviceData['service_id']) && $serviceData['service_id'] == $serviceModel->id) {
                    if (isset($serviceData['cities']) && is_array($serviceData['cities'])) {
                        foreach ($serviceData['cities'] as $cityData) {
                            if (isset($cityData['city_id']) && $cityData['city_id'] == $locationModel->id) {
                                return true;
                            }
                        }
                    }
                }
            }
            return false;
        });

        // Get freelance staff from job requests
        $freelanceQuery = \App\Models\JobRequest::where('job_title', $service)
            ->whereNotNull('name')
            ->where('name', '!=', '');

        // If not including inactive, only get active freelancers
        if (!$includeInactive) {
            $freelanceQuery->where('status', 'active');
        }

        $freelanceStaff = $freelanceQuery->get()
            ->map(function ($jobRequest) {
                return (object) [
                    'id' => 'freelance_' . $jobRequest->id,
                    'name' => $jobRequest->name . ' (Freelance)',
                    'contact_no' => $jobRequest->contact_no,
                    'is_freelance' => true,
                    'status' => $jobRequest->status ?? 'active'
                ];
            });

        // Combine vendors and freelance staff
        $allOptions = $filteredVendors->map(function ($vendor) {
            $vendor->is_freelance = false;
            return $vendor;
        })->concat($freelanceStaff);

        return $allOptions;
    }

    /**
     * Get filtered vendors for deployment details
     */
    public function getFilteredVendorsForDeployment(Request $request)
    {
        $location = $request->input('location');
        $service = $request->input('service');

        $filteredVendors = $this->getFilteredVendors($location, $service);

        return response()->json([
            'success' => true,
            'vendors' => $filteredVendors->values()
        ]);
    }

    /**
     * Get vendor details for a specific lead
     */
    public function getVendorDetails(Request $request)
    {
        $leadId = $request->input('lead_id');
        $location = $request->input('location');
        $query = $request->input('query');

        $lead = OperationLead::findOrFail($leadId);
        // Only show active freelancers in the modal
        $filteredVendors = $this->getFilteredVendors($location, $query, false);

        $vendorDetails = $filteredVendors->map(function ($vendor) {
            return [
                'id' => $vendor->id,
                'name' => $vendor->name,
                'contact_no' => $vendor->contact_no ?? 'N/A',
                'is_freelance' => $vendor->is_freelance ?? false,
                'status' => $vendor->status ?? 'active' // Add status for freelancers
            ];
        });

        return response()->json([
            'success' => true,
            'vendors' => $vendorDetails->values(),
            'lead_info' => [
                'customer_name' => $lead->customer_name,
                'location' => $lead->location,
                'query' => $lead->query
            ]
        ]);
    }

    /**
     * Update freelancer status (active, inactive, blacklist)
     */
    public function updateFreelancerStatus(Request $request)
    {
        $request->validate([
            'freelancer_id' => 'required|string',
            'status' => 'required|in:active,inactive,blacklist'
        ]);

        $freelancerId = $request->input('freelancer_id');
        $status = $request->input('status');

        // Check if it's a freelance ID (starts with 'freelance_')
        if (strpos($freelancerId, 'freelance_') === 0) {
            $jobRequestId = str_replace('freelance_', '', $freelancerId);

            $jobRequest = \App\Models\JobRequest::findOrFail($jobRequestId);
            $jobRequest->update(['status' => $status]);

            $statusText = ucfirst($status);
            if ($status === 'blacklist') {
                $statusText = 'Blacklisted';
            }

            return response()->json([
                'success' => true,
                'message' => "Freelancer status updated to {$statusText} successfully",
                'freelancer' => [
                    'id' => $freelancerId,
                    'name' => $jobRequest->name,
                    'status' => $status
                ]
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Invalid freelancer ID'
        ], 400);
    }

    /**
     * Get updated vendor count for a specific lead
     */
    public function getUpdatedVendorCount(Request $request)
    {
        $leadId = $request->input('lead_id');
        $location = $request->input('location');
        $query = $request->input('query');

        $lead = OperationLead::findOrFail($leadId);
        $filteredVendors = $this->getFilteredVendors($location, $query, false);
        $count = $filteredVendors->count();

        return response()->json([
            'success' => true,
            'count' => $count,
            'lead_id' => $leadId
        ]);
    }

    /**
     * Toggle verify payment status for deployment detail
     */
    public function toggleVerifyPayment(Request $request, $id)
    {
        try {
            $request->validate([
                'verify_payment' => 'required|in:true,false,1,0'
            ]);

            $deployment = OperationDeploymentDetails::findOrFail($id);

            // Convert string boolean to actual boolean
            $verifyPayment = filter_var($request->verify_payment, FILTER_VALIDATE_BOOLEAN);
            $deployment->verify_payment = $verifyPayment;
            $deployment->save();

            return response()->json([
                'success' => true,
                'message' => 'Payment verification status updated successfully',
                'verify_payment' => $deployment->verify_payment,
                'deployment_amount' => $deployment->vendor_payment
            ]);

        } catch (\Exception $e) {
            \Log::error('Error toggling verify payment: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while updating payment verification status'
            ], 500);
        }
    }

    /**
     * Store received payment for an invoice
     */
    public function storeReceivedPayment(Request $request, $invoiceId)
    {
        try {
            $request->validate([
                'amount' => 'required|numeric|min:0',
                'received_date' => 'required|date',
                'utr_number' => 'nullable|string|max:255',
                'screenshot' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
                'remark' => 'nullable|string|max:1000',
            ]);

            $invoice = \App\Models\PaymentInvoice::findOrFail($invoiceId);

            $screenshotPath = null;
            if ($request->hasFile('screenshot')) {
                $screenshotPath = $request->file('screenshot')->store('payment-screenshots', 'public');
            }

            $receivedPayment = \App\Models\ReceivedPayment::create([
                'payment_invoice_id' => $invoice->id,
                'operation_lead_id' => $invoice->operation_lead_id,
                'amount' => $request->amount,
                'received_date' => $request->received_date,
                'utr_number' => $request->utr_number,
                'screenshot' => $screenshotPath,
                'remark' => $request->remark,
            ]);

            // Update invoice status
            \App\Services\PaymentInvoiceService::updateInvoiceStatus($invoice);

            return response()->json([
                'success' => true,
                'message' => 'Payment received successfully',
                'payment' => $receivedPayment
            ]);

        } catch (\Exception $e) {
            \Log::error('Error storing received payment: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error storing payment: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Edit received payment
     */
    public function editReceivedPayment($id)
    {
        $payment = \App\Models\ReceivedPayment::findOrFail($id);
        return response()->json([
            'success' => true,
            'payment' => $payment
        ]);
    }

    /**
     * Update received payment
     */
    public function updateReceivedPayment(Request $request, $id)
    {
        try {
            $request->validate([
                'amount' => 'required|numeric|min:0',
                'received_date' => 'required|date',
                'utr_number' => 'nullable|string|max:255',
                'screenshot' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
                'remark' => 'nullable|string|max:1000',
            ]);

            $receivedPayment = \App\Models\ReceivedPayment::findOrFail($id);

            $data = [
                'amount' => $request->amount,
                'received_date' => $request->received_date,
                'utr_number' => $request->utr_number,
                'remark' => $request->remark,
            ];

            if ($request->hasFile('screenshot')) {
                // Delete old screenshot
                if ($receivedPayment->screenshot) {
                    Storage::disk('public')->delete($receivedPayment->screenshot);
                }
                $data['screenshot'] = $request->file('screenshot')->store('payment-screenshots', 'public');
            }

            $receivedPayment->update($data);

            // Update invoice status
            \App\Services\PaymentInvoiceService::updateInvoiceStatus($receivedPayment->paymentInvoice);

            return response()->json([
                'success' => true,
                'message' => 'Payment updated successfully'
            ]);

        } catch (\Exception $e) {
            \Log::error('Error updating received payment: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error updating payment: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete received payment
     */
    public function destroyReceivedPayment($id)
    {
        try {
            $payment = \App\Models\ReceivedPayment::findOrFail($id);
            $invoice = $payment->paymentInvoice;

            // Delete screenshot
            if ($payment->screenshot) {
                Storage::disk('public')->delete($payment->screenshot);
            }

            $payment->delete();

            // Update invoice status
            \App\Services\PaymentInvoiceService::updateInvoiceStatus($invoice);

            return response()->json([
                'success' => true,
                'message' => 'Payment deleted successfully'
            ]);

        } catch (\Exception $e) {
            \Log::error('Error deleting received payment: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error deleting payment: ' . $e->getMessage()
            ], 500);
        }
    }

}
