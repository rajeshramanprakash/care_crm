<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\HandlesLeadStatusRemarks;
use App\Http\Controllers\Concerns\HandlesOperationLeadStatusRemarks;
use App\Http\Controllers\Concerns\ResolvesB2BCorporateOperationLeadForDisplay;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\OperationLead;
use Yajra\DataTables\Facades\DataTables;
use App\Models\User;
use App\Models\Location;
use App\Models\Vendor;
use App\Models\Service;
use App\Models\WhatsappMsgGroup;
use App\Models\OperationLeadsPaymentDetail;
use App\Models\OperationDeploymentDetails;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class OperationLeadController extends Controller
{
    use HandlesLeadStatusRemarks;
    use HandlesOperationLeadStatusRemarks;
    use ResolvesB2BCorporateOperationLeadForDisplay;

    public function index()
    {
        // Check if this is an API request
        if (request()->expectsJson()) {
            try {
                \Log::info('Admin fetching operation leads');

                // Check if tables exist
                if (!\Schema::hasTable('operation_leads')) {
                    \Log::error('operation_leads table does not exist');
                    return response()->json(['error' => 'Database table missing'], 500);
                }

                if (!\Schema::hasTable('users')) {
                    \Log::error('users table does not exist');
                    return response()->json(['error' => 'Users table missing'], 500);
                }

                if (!\Schema::hasTable('vendors')) {
                    \Log::error('vendors table does not exist');
                    return response()->json(['error' => 'Vendors table missing'], 500);
                }

                // First try to get basic leads without joins
                $basicLeads = OperationLead::orderByDesc('lead_id')->get();
                \Log::info('Basic leads fetched', ['count' => $basicLeads->count()]);

                // Then try with joins
                $leads = OperationLead::select(
                    'operation_leads.*',
                    'vendors.name as vendor_name',
                    'users.f_name as executive_name'
                )
                ->leftJoin('users', 'operation_leads.executive', '=', 'users.id')
                ->leftJoin('vendors', 'operation_leads.vendor_id', '=', 'vendors.id')
                ->orderByDesc('operation_leads.lead_id')
                ->get()
                ->map(function ($lead) {
                    $lead->available_vendors_count = $this->getAvailableVendorsCount($lead->location, $lead->query);

                    return $lead;
                })
                ->values();

                \Log::info('Admin operation leads fetched successfully', ['count' => $leads->count()]);
                return response()->json($leads);
            } catch (\Exception $e) {
                \Log::error('Error fetching admin operation leads', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                return response()->json(['error' => 'Failed to fetch leads'], 500);
            }
        }

        $locations = Location::orderBy('name')->get();
        $executives = User::whereRaw('FIND_IN_SET(role_id, "4")')->get();
        $vendors = Vendor::all();
        $services = Service::get();
        return view('admin.operation_leads.index', compact('vendors', 'executives', 'locations', 'services'));
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
            'status' => 'nullable|in:profile,profile pending,profile shared,closed,follow up,inactive,price issue,future prospect',
            'follow_up_date' => 'nullable|date|required_if:status,follow up',
            'future_prospect_date' => 'nullable|date|required_if:status,future prospect',
            'shift_type' => 'nullable|in:12hr,24hr,both',
            'price_issue_remark' => 'nullable|string',
            'inactive_remark' => 'nullable|string',
            'closed_remark' => 'nullable|string',
            'patient_name' => 'nullable|string',
            'age' => 'nullable|integer',
            'closed_rate' => 'nullable|numeric',
            'vendor_id' => 'nullable',
            'staff_name' => 'nullable|string',
            'vendor_closed_rate' => 'nullable|numeric',
            'outstanding_payment' => 'nullable|numeric',
            'payment_plan' => 'nullable|string',
            'ongoing_stopped' => 'nullable|string',
        ]);

        Log::info($validated);

        if (($validated['status'] ?? null) !== 'follow up') {
            $validated['follow_up_date'] = null;
        }
        if (($validated['status'] ?? null) !== 'future prospect') {
            $validated['future_prospect_date'] = null;
        }

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

        // Generate lead_id
        $latestLead = OperationLead::orderBy('id', 'desc')->first();
        $nextNumber = $latestLead ? $latestLead->id + 1 : 1;
        $validated['lead_id'] = 'CHO' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);

        // Set current date/time if not provided
        if (empty($validated['date_time'])) {
            $validated['date_time'] = now();
        }
        
        $lead = OperationLead::create($validated);

        // Check if this is an API request
        if (request()->expectsJson()) {
            return response()->json(['message' => 'Operation Lead added successfully', 'lead' => $lead]);
        }

        return redirect()->back()->with('success', 'Operation Lead added successfully');
    }

    public function getLeads(Request $request)
    {
        // Subquery: latest deployment id for each lead
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
            ->leftJoin('users', 'operation_leads.executive', '=', 'users.id')
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
            ->addColumn('DT_RowIndex', function($lead) {
                return $lead->id;
            })
            ->addColumn('vendor_name', function($lead) { return $lead->vendor_name; })
            ->addColumn('executive_name', function($lead) { return $lead->executive_name; })
            ->addColumn('available_vendors_count', function($lead) {
                $count = $this->getAvailableVendorsCount($lead->location, $lead->query);
                return '<button class="btn btn-sm vendor-count-btn" data-lead-id="'.$lead->id.'" data-location="'.$lead->location.'" data-query="'.$lead->query.'" title="View Available Vendors & Freelancers">'.$count.'</button>';
            })
            ->addColumn('last_call_status', function($lead) {
                return $lead->last_call_status_display;
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
            ->rawColumns(['screenshot','action','available_vendors_count'])
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
        $locations = Location::orderBy('name')->get();
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

        $crm_lead = $lead->resolveCrmLead();
        $crmLeadStatusRemarks = $this->crmLeadStatusRemarksForDisplay($crm_lead);
        $operationStatusRemarks = $this->operationLeadStatusRemarksPayload($lead);
        $b2bCorporateLead = $this->b2bCorporateLeadForOperationShow($lead);

        // Check if this is an API request
        if (request()->expectsJson()) {
            return response()->json([
                'lead' => $lead,
                'paymentInvoices' => $paymentInvoices,
                'receivedPayments' => $receivedPayments,
                'deploymentDetails' => $deploymentDetails,
                'vendors' => $vendors,
                'filteredVendors' => $filteredVendors,
                'services' => $services,
                'crm_lead' => $crm_lead,
                'crm_lead_status_remarks' => $crmLeadStatusRemarks,
                'operation_status_remarks' => $operationStatusRemarks,
                'locations' => $locations
            ]);
        }

        return view('admin.operation_leads.show', compact('lead', 'paymentInvoices', 'receivedPayments', 'vendors', 'filteredVendors', 'services', 'deploymentDetails', 'crm_lead', 'crmLeadStatusRemarks', 'operationStatusRemarks', 'locations', 'executives', 'b2bCorporateLead'));
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
        \Log::info('Admin update operation lead called', [
            'id' => $id,
            'request_data' => $request->all(),
            'method' => $request->method(),
            'headers' => $request->headers->all()
        ]);

        // Define payment info fields
        $paymentInfoFields = ['payment_plan', 'ongoing_stopped', 'stopped_remark', 'outstanding_payment'];
        
        // Check if this is a payment info only update
        $isPaymentInfoOnly = true;
        foreach ($request->all() as $key => $value) {
            if (!in_array($key, $paymentInfoFields) && !in_array($key, ['_method', '_token'])) {
                $isPaymentInfoOnly = false;
                break;
            }
        }

        // For mobile app updates or payment info only updates, use lighter validation
        if (($request->has('mobile_app') && $request->mobile_app) || ($isPaymentInfoOnly && request()->expectsJson())) {
            $validationRules = [
                'status' => 'nullable|string',
                'follow_up_date' => 'nullable|date',
                'future_prospect_date' => 'nullable|date',
                'patient_name' => 'nullable|string',
                'age' => 'nullable|integer',
                'closed_rate' => 'nullable|numeric',
                'payment_plan' => 'nullable|string',
                'ongoing_stopped' => 'nullable|string',
                'stopped_remark' => 'nullable|string',
                'outstanding_payment' => 'nullable|numeric',
            ];
        } else {
            // For web updates, validate all fields
            $validationRules = [
                'date_time' => 'nullable|date',
                'executive' => 'required|exists:users,id',
                'customer_name' => 'required|string',
                'contact_no' => 'required|string',
                'address' => 'nullable|string',
                'location' => 'nullable|string',
                'query' => 'required',
                'query_remark' => 'nullable|string',
                'status' => 'required|in:profile,profile pending,profile shared,closed,follow up,inactive,price issue,future prospect',
                'follow_up_date' => 'nullable|date|required_if:status,follow up',
                'future_prospect_date' => 'nullable|date|required_if:status,future prospect',
                'shift_type' => 'nullable|in:12hr,24hr,both',
                'price_issue_remark' => 'nullable|string',
                'inactive_remark' => 'nullable|string',
                'patient_name' => 'nullable|string',
                'age' => 'nullable|integer',
                'closed_rate' => 'nullable|numeric',
                'vendor_id' => 'nullable',
                'staff_name' => 'nullable|string',
                'vendor_closed_rate' => 'nullable|numeric',
                'outstanding_payment' => 'nullable|numeric',
                'payment_plan' => 'nullable|string',
                'ongoing_stopped' => 'nullable|string',
                'stopped_remark' => 'nullable|string',
            ];
        }

        $validated = $request->validate($validationRules);

        // Normalize conditional datetime fields for BOTH web + mobile updates when status is present.
        // (Mobile app uses lighter validation but still needs these fields to persist.)
        if ($request->has('status')) {
            if (($validated['status'] ?? '') !== 'follow up') {
                $validated['follow_up_date'] = null;
            }
            if (($validated['status'] ?? '') !== 'future prospect') {
                $validated['future_prospect_date'] = null;
            }
        }

        $isFullWebUpdate = ! ($request->has('mobile_app') && $request->mobile_app)
            && ! ($isPaymentInfoOnly && request()->expectsJson());
        if ($isFullWebUpdate) {
            if (($validated['status'] ?? '') !== 'follow up') {
                $validated['follow_up_date'] = null;
            }
            if (($validated['status'] ?? '') !== 'future prospect') {
                $validated['future_prospect_date'] = null;
            }
        }

        $lead = OperationLead::findOrFail($id);
        $oldClosedRate = $lead->closed_rate;
        
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

        // Note: Outstanding payments are now calculated per payment detail based on work days
        // No need to update all payments when closed rate changes

        \Log::info('Admin operation lead updated successfully', [
            'id' => $id,
            'updated_fields' => array_keys($validated)
        ]);

        // Check if this is an API request
        if (request()->expectsJson()) {
            return response()->json(['message' => 'Operation Lead updated successfully', 'lead' => $lead]);
        }

        return redirect()->back()->with('success', 'Operation Lead updated successfully');
    }

    public function destroy($id)
    {
        $lead = OperationLead::findOrFail($id);
        $lead->delete();

        // Check if this is an API request
        if (request()->expectsJson()) {
            return response()->json(['message' => 'Operation Lead deleted successfully']);
        }

        return redirect()->back()->with('success', 'Operation Lead deleted successfully');
    }

    public function storePaymentDetail(Request $request, $leadId)
    {
        \Log::info('Admin storePaymentDetail called', [
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
            'screenshot' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:20480',
            'remark' => 'nullable|string|max:1000',
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

        // Check if this is an API request
        if (request()->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Payment detail added successfully',
                'payment' => $payment
            ]);
        }

        return redirect()->back()->with('success', 'Payment detail added successfully');
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
        \Log::info('Admin updatePaymentDetail called', [
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

        // Check if this is an API request
        if (request()->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Payment detail updated successfully',
                'payment' => $payment
            ]);
        }

        return redirect()->back()->with('success', 'Payment detail updated successfully');

        } catch (\Exception $e) {
            \Log::error('Error updating payment detail: ' . $e->getMessage(), [
                'id' => $id,
                'request_data' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);

            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error updating payment detail: ' . $e->getMessage()
                ], 500);
            }

            return redirect()->back()->with('error', 'Error updating payment detail: ' . $e->getMessage());
        }
    }



    public function destroyPaymentDetail($id)
    {
        $payment = OperationLeadsPaymentDetail::findOrFail($id);

        // Delete associated files
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
            $validationRules = [
                'deployment_date' => 'nullable|date',
                'deployment_from_date' => 'nullable|date',
                'deployment_to_date' => 'nullable|date',
                'deployment_status' => 'required|string',
                'duty_hours' => 'nullable|string|in:12hr,24hr',
                'vendor_id' => 'required',
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
            if ($deployment->verify_payment && $deployment->payment_verified_at === null) {
                $deployment->payment_verified_at = now();
                $deployment->save();
            }

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
        try {
            \Log::info('editDeploymentDetail called with ID: ' . $id);

            // Check if table exists
            if (!\Schema::hasTable('operation_deployment_details')) {
                \Log::error('operation_deployment_details table does not exist');
                return response()->json([
                    'success' => false,
                    'message' => 'Database table missing'
                ], 500);
            }

            $deployment = OperationDeploymentDetails::with(['vendor', 'freelanceStaff'])->findOrFail($id);
            \Log::info('Deployment found:', $deployment->toArray());

            return response()->json([
                'success' => true,
                'deployment' => $deployment
            ]);
        } catch (\Exception $e) {
            \Log::error('Error in editDeploymentDetail: ' . $e->getMessage(), [
                'id' => $id,
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error loading deployment detail: ' . $e->getMessage()
            ], 500);
        }
    }

    public function updateDeploymentDetail(Request $request, $id)
    {
        try {
            \Log::info('Admin updateDeploymentDetail called', [
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
                // vendor_id can be either a numeric vendor id or a "freelance_{id}" string,
                // so we cannot use the exists rule here. We validate it manually below.
                'vendor_id' => 'nullable',
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
            if ($deployment->verify_payment && $deployment->payment_verified_at === null) {
                $deployment->payment_verified_at = now();
                $deployment->save();
            }

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

    public function getExecutives()
    {
        $executives = User::whereRaw('FIND_IN_SET(role_id, "4")')
            ->select('id', 'f_name')
            ->get();
        return response()->json($executives);
    }

    /**
     * Get count of available vendors and freelancers for a lead
     */
    private function getAvailableVendorsCount($location, $service)
    {
        if (!$location || !$service) {
            return 0;
        }

        // Get the service ID from service name
        $serviceModel = Service::where('name', $service)->first();
        if (!$serviceModel) {
            return 0;
        }

        // Get the location ID from location name
        $locationModel = Location::where('name', $location)->first();
        if (!$locationModel) {
            return 0;
        }

        // Count active vendors that match the location and service
        $vendors = Vendor::where('status', 'active')->get();
        $vendorCount = $vendors->filter(function ($vendor) use ($serviceModel, $locationModel) {
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
        })->count();

        // Count only active freelance staff from job requests
        $freelanceCount = \App\Models\JobRequest::where('job_title', $service)
            ->whereNotNull('name')
            ->where('name', '!=', '')
            ->where('status', 'active')
            ->count();

        return $vendorCount + $freelanceCount;
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
            // Ensure service_city_shifts is an array
            $serviceCityShifts = $vendor->service_city_shifts;
            if (is_string($serviceCityShifts)) {
                $serviceCityShifts = json_decode($serviceCityShifts, true);
            }
            
            if (!$serviceCityShifts || !is_array($serviceCityShifts)) {
                return false;
            }

            // Check if vendor provides this service in this location
            foreach ($serviceCityShifts as $serviceData) {
                // Handle both array and object access, and ensure type matching
                $serviceId = $serviceData['service_id'] ?? ($serviceData['service_id'] ?? null);
                
                // Convert both to integers for comparison
                if (isset($serviceId) && (int)$serviceId === (int)$serviceModel->id) {
                    $cities = $serviceData['cities'] ?? ($serviceData['cities'] ?? []);
                    if (is_array($cities)) {
                        foreach ($cities as $cityData) {
                            $cityId = $cityData['city_id'] ?? ($cityData['city_id'] ?? null);
                            
                            // Convert both to integers for comparison
                            if (isset($cityId) && (int)$cityId === (int)$locationModel->id) {
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
            $vendor->rate_per_day = $vendor->rate_per_day ?? 0;
            return $vendor;
        })->concat($freelanceStaff->map(function ($freelancer) {
            $freelancer->rate_per_day = 0; // Freelancers don't have predefined rates
            return $freelancer;
        }));

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
        // Only show active freelancers in the modal by default
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
     * Update freelancer status
     */
    public function updateFreelancerStatus(Request $request)
    {
        $request->validate([
            'freelancer_id' => 'required|string',
            'status' => 'required|in:active,inactive,blacklist'
        ]);

        try {
            // Extract the actual ID from the freelancer_id (remove 'freelance_' prefix)
            $actualId = str_replace('freelance_', '', $request->freelancer_id);

            $jobRequest = \App\Models\JobRequest::findOrFail($actualId);
            $jobRequest->status = $request->status;
            $jobRequest->save();

            $statusText = ucfirst($request->status);
            if ($request->status === 'blacklist') {
                $statusText = 'Blacklisted';
            }

            return response()->json([
                'success' => true,
                'message' => "Freelancer status updated to {$statusText} successfully"
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update freelancer status: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get updated vendor count for a specific lead
     */
    public function getUpdatedVendorCount(Request $request)
    {
        $leadId = $request->input('lead_id');
        $location = $request->input('location');
        $query = $request->input('query');

        try {
            // Get only active freelancers for count
            $filteredVendors = $this->getFilteredVendors($location, $query, false);
            $activeCount = $filteredVendors->count();

            return response()->json([
                'success' => true,
                'count' => $activeCount
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get vendor count: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get vendor payment details and lead history
     */
    public function getVendorPaymentDetails($vendorId)
    {
        try {
            // Get vendor information
            $vendor = Vendor::find($vendorId);
            if (!$vendor) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vendor not found'
                ], 404);
            }

            // Get all deployment details for this vendor
            $deployments = OperationDeploymentDetails::where('vendor_id', $vendorId)
                ->with(['operationLead' => function($query) {
                    $query->select('id', 'lead_id', 'customer_name', 'query', 'location');
                }])
                ->get();

            // Calculate summary statistics (only verified payments)
            $totalEarned = $deployments->where('verify_payment', true)->sum('vendor_payment');
            $totalLeads = $deployments->count();
            $activeDeployments = $deployments->where('deployment_status', 'In Progress')->count();

            // Get total payments made to this vendor
            $totalPaymentsMade = \App\Models\VendorPayment::where('vendor_id', $vendorId)
                ->where('status', 'completed')
                ->sum('amount');

            // Calculate remaining amount (total earned - payments made)
            $remainingAmount = $totalEarned - $totalPaymentsMade;

            // Prepare lead details (only verified payments) - include deployment_detail id for payment selection
            $leads = $deployments->where('verify_payment', true)->map(function($deployment) {
                return [
                    'deployment_detail_id' => $deployment->id,
                    'operation_lead_id' => $deployment->operation_lead_id,
                    'lead_id' => $deployment->operationLead ? $deployment->operationLead->lead_id : 'N/A',
                    'customer_name' => $deployment->operationLead ? $deployment->operationLead->customer_name : 'N/A',
                    'service' => $deployment->operationLead ? $deployment->operationLead->query : 'N/A',
                    'location' => $deployment->operationLead ? $deployment->operationLead->location : 'N/A',
                    'deployment_date' => $deployment->deployment_date,
                    'deployment_from_date' => $deployment->deployment_from_date,
                    'deployment_to_date' => $deployment->deployment_to_date,
                    'vendor_rate_per_day' => $deployment->vendor_rate_per_day,
                    'vendor_payment' => $deployment->vendor_payment,
                    'deployment_status' => $deployment->deployment_status,
                    'verify_payment' => $deployment->verify_payment
                ];
            });

            return response()->json([
                'success' => true,
                'vendor' => [
                    'id' => $vendor->id,
                    'name' => $vendor->name,
                    'contact_no' => $vendor->contact_no,
                    'rate_per_day' => $vendor->rate_per_day
                ],
                'total_earned' => $totalEarned,
                'total_payments_made' => $totalPaymentsMade,
                'remaining_amount' => $remainingAmount,
                'total_leads' => $totalLeads,
                'active_deployments' => $activeDeployments,
                'leads' => $leads
            ]);

        } catch (\Exception $e) {
            \Log::error('Error getting vendor payment details: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error loading vendor payment details'
            ], 500);
        }
    }

    /**
     * Get freelancer payment details and lead history
     */
    public function getFreelancerPaymentDetails($freelancerId)
    {
        try {
            // Get freelancer (JobRequest) information
            $freelancer = \App\Models\JobRequest::find($freelancerId);
            if (!$freelancer) {
                return response()->json([
                    'success' => false,
                    'message' => 'Freelancer not found'
                ], 404);
            }

            // Get all deployment details for this freelancer
            $deployments = OperationDeploymentDetails::where('freelance_staff_id', $freelancerId)
                ->with(['operationLead' => function($query) {
                    $query->select('id', 'lead_id', 'customer_name', 'query', 'location');
                }])
                ->get();

            // Calculate summary statistics (only verified payments)
            $totalEarned = $deployments->where('verify_payment', true)->sum('vendor_payment');
            $totalLeads = $deployments->count();
            $activeDeployments = $deployments->whereIn('deployment_status', ['In Progress', 'Active', 'Ongoing'])->count();

            // Get total payments made to this freelancer
            $totalPaymentsMade = \App\Models\FreelancerPayment::where('job_request_id', $freelancerId)
                ->where('status', 'completed')
                ->sum('amount');

            // Calculate remaining amount (total earned - payments made)
            $remainingAmount = $totalEarned - $totalPaymentsMade;

            // Prepare lead details (only verified payments)
            $leads = $deployments->where('verify_payment', true)->map(function($deployment) {
                return [
                    'deployment_detail_id' => $deployment->id,
                    'lead_id' => $deployment->operationLead ? $deployment->operationLead->lead_id : 'N/A',
                    'customer_name' => $deployment->operationLead ? $deployment->operationLead->customer_name : 'N/A',
                    'service' => $deployment->operationLead ? $deployment->operationLead->query : 'N/A',
                    'location' => $deployment->operationLead ? $deployment->operationLead->location : 'N/A',
                    'deployment_date' => $deployment->deployment_date,
                    'deployment_from_date' => $deployment->deployment_from_date,
                    'deployment_to_date' => $deployment->deployment_to_date,
                    'vendor_rate_per_day' => $deployment->vendor_rate_per_day,
                    'vendor_payment' => $deployment->vendor_payment,
                    'deployment_status' => $deployment->deployment_status,
                    'verify_payment' => $deployment->verify_payment
                ];
            });

            return response()->json([
                'success' => true,
                'freelancer' => [
                    'id' => $freelancer->id,
                    'name' => $freelancer->name,
                    'contact_no' => $freelancer->contact_no,
                    'expected_salary' => $freelancer->expected_salary
                ],
                'total_earned' => $totalEarned,
                'total_payments_made' => $totalPaymentsMade,
                'remaining_amount' => $remainingAmount,
                'total_leads' => $totalLeads,
                'active_deployments' => $activeDeployments,
                'leads' => $leads
            ]);

        } catch (\Exception $e) {
            \Log::error('Error getting freelancer payment details: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error loading freelancer payment details'
            ], 500);
        }
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
            if ($verifyPayment && $deployment->payment_verified_at === null) {
                $deployment->payment_verified_at = now();
            }
            $deployment->save();

            return response()->json([
                'success' => true,
                'message' => 'Payment verification status updated successfully',
                'verify_payment' => $deployment->verify_payment,
                'vendor_payment' => $deployment->vendor_payment
            ]);
        } catch (\Exception $e) {
            \Log::error('Error toggling verify payment: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error updating payment verification status'
            ], 500);
        }
    }

    /**
     * Get all dates between deployment_from_date and deployment_to_date for a deployment,
     * and mark which ones are currently absent.
     */
    public function getDeploymentDates($id)
    {
        $deployment = OperationDeploymentDetails::findOrFail($id);

        if (!$deployment->deployment_from_date || !$deployment->deployment_to_date) {
            return response()->json([
                'success' => false,
                'message' => 'Deployment from/to dates are not set for this record.',
            ], 422);
        }

        $from = Carbon::parse($deployment->deployment_from_date)->startOfDay();
        $to = Carbon::parse($deployment->deployment_to_date)->startOfDay();

        if ($from->gt($to)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid deployment date range.',
            ], 422);
        }

        $dates = [];
        $cursor = $from->copy();
        $absentDates = is_array($deployment->absent_dates) ? $deployment->absent_dates : [];

        while ($cursor->lte($to)) {
            $dateString = $cursor->toDateString(); // Y-m-d
            $dates[] = [
                'date' => $dateString,
                'label' => $cursor->format('d-M-Y'),
                'absent' => in_array($dateString, $absentDates, true),
            ];
            $cursor->addDay();
        }

        return response()->json([
            'success' => true,
            'deployment_id' => $deployment->id,
            'rate_per_day' => (float) ($deployment->vendor_rate_per_day ?? 0),
            'vendor_payment' => (float) ($deployment->vendor_payment ?? 0),
            'dates' => $dates,
        ]);
    }

    /**
     * Toggle a single absent date for a deployment and recalculate vendor_payment.
     */
    public function toggleDeploymentAbsentDate(Request $request, $id)
    {
        $request->validate([
            'date' => 'required|date_format:Y-m-d',
        ]);

        $deployment = OperationDeploymentDetails::findOrFail($id);

        if (!$deployment->deployment_from_date || !$deployment->deployment_to_date || !$deployment->vendor_rate_per_day) {
            return response()->json([
                'success' => false,
                'message' => 'Deployment dates or rate per day are not set for this record.',
            ], 422);
        }

        $from = Carbon::parse($deployment->deployment_from_date)->startOfDay();
        $to = Carbon::parse($deployment->deployment_to_date)->startOfDay();
        $target = Carbon::createFromFormat('Y-m-d', $request->date)->startOfDay();

        if ($target->lt($from) || $target->gt($to)) {
            return response()->json([
                'success' => false,
                'message' => 'Selected date is outside the deployment range.',
            ], 422);
        }

        $absentDates = is_array($deployment->absent_dates) ? $deployment->absent_dates : [];

        if (in_array($request->date, $absentDates, true)) {
            // If already absent, remove (toggle off)
            $absentDates = array_values(array_filter($absentDates, function ($d) use ($request) {
                return $d !== $request->date;
            }));
        } else {
            // Mark as absent (toggle on)
            $absentDates[] = $request->date;
        }

        // Recalculate vendor_payment using total days minus absent days
        $totalDays = $from->diffInDays($to) + 1;
        $absentCount = count($absentDates);
        $effectiveDays = max(0, $totalDays - $absentCount);

        $ratePerDay = (float) $deployment->vendor_rate_per_day;
        $newPayment = $effectiveDays * $ratePerDay;

        $deployment->absent_dates = $absentDates;
        $deployment->vendor_payment = $newPayment;
        $deployment->save();

        // Build updated date list for UI
        $dates = [];
        $cursor = $from->copy();
        while ($cursor->lte($to)) {
            $dateString = $cursor->toDateString();
            $dates[] = [
                'date' => $dateString,
                'label' => $cursor->format('d-M-Y'),
                'absent' => in_array($dateString, $absentDates, true),
            ];
            $cursor->addDay();
        }

        return response()->json([
            'success' => true,
            'message' => 'Absent date updated successfully.',
            'deployment_id' => $deployment->id,
            'rate_per_day' => $ratePerDay,
            'vendor_payment' => $newPayment,
            'effective_days' => $effectiveDays,
            'total_days' => $totalDays,
            'absent_dates' => $absentDates,
            'dates' => $dates,
        ]);
    }

    /**
     * Save absent dates for a deployment (batch save). Creates a statement adjustment row when amount is deducted.
     */
    public function saveDeploymentAbsentDates(Request $request, $id)
    {
        $request->validate([
            'absent_dates' => 'nullable|array',
            'absent_dates.*' => 'date_format:Y-m-d',
        ]);

        $deployment = OperationDeploymentDetails::with('operationLead')->findOrFail($id);

        if (!$deployment->deployment_from_date || !$deployment->deployment_to_date || $deployment->vendor_rate_per_day === null) {
            return response()->json([
                'success' => false,
                'message' => 'Deployment dates or rate per day are not set for this record.',
            ], 422);
        }

        $from = Carbon::parse($deployment->deployment_from_date)->startOfDay();
        $to = Carbon::parse($deployment->deployment_to_date)->startOfDay();
        $totalDays = $from->diffInDays($to) + 1;

        $absentDates = $request->input('absent_dates', []);
        $absentDates = array_values(array_unique(array_filter($absentDates, function ($d) use ($from, $to) {
            $t = Carbon::parse($d)->startOfDay();
            return $t->gte($from) && $t->lte($to);
        })));

        $ratePerDay = (float) $deployment->vendor_rate_per_day;
        $effectiveDays = max(0, $totalDays - count($absentDates));
        $newPayment = round($effectiveDays * $ratePerDay, 2);

        $oldPayment = (float) ($deployment->vendor_payment ?? 0);
        $amountDeducted = round($oldPayment - $newPayment, 2);

        if ($amountDeducted > 0) {
            \App\Models\DeploymentAbsentAdjustment::create([
                'operation_deployment_detail_id' => $deployment->id,
                'adjusted_at' => now(),
                'amount_deducted' => $amountDeducted,
                'absent_dates' => $absentDates,
            ]);
        }

        $deployment->absent_dates = $absentDates;
        $deployment->vendor_payment = $newPayment;
        $deployment->save();

        $dates = [];
        $cursor = $from->copy();
        while ($cursor->lte($to)) {
            $dateString = $cursor->toDateString();
            $dates[] = [
                'date' => $dateString,
                'label' => $cursor->format('d-M-Y'),
                'absent' => in_array($dateString, $absentDates, true),
            ];
            $cursor->addDay();
        }

        return response()->json([
            'success' => true,
            'message' => 'Absent days saved successfully.',
            'deployment_id' => $deployment->id,
            'rate_per_day' => $ratePerDay,
            'vendor_payment' => $newPayment,
            'effective_days' => $effectiveDays,
            'total_days' => $totalDays,
            'absent_dates' => $absentDates,
            'dates' => $dates,
        ]);
    }

    /**
     * Show single payment invoice (view with print/download, payment records).
     * If ?download=1, returns PDF for direct download.
     */
    public function showPaymentInvoice($id)
    {
        $invoice = \App\Models\PaymentInvoice::with(['operationLead', 'receivedPayments'])
            ->findOrFail($id);
        $totalReceived = (float) $invoice->receivedPayments->sum('amount');
        $paymentAmount = (float) $invoice->payment_amount;
        if ($totalReceived >= $paymentAmount) {
            $status = 'paid';
        } elseif ($totalReceived > 0) {
            $status = 'partially_paid';
        } else {
            $status = 'unpaid';
        }

        if (request()->query('download') === '1') {
            $forPdf = true;
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('admin.operation_leads.payment_invoice_show', compact('invoice', 'totalReceived', 'status', 'forPdf'))
                ->setPaper('a4', 'portrait');
            return $pdf->download('invoice-' . preg_replace('/[^a-zA-Z0-9\-_]/', '-', $invoice->invoice_id) . '.pdf');
        }

        return view('admin.operation_leads.payment_invoice_show', compact('invoice', 'totalReceived', 'status'));
    }

    /**
     * Return payment invoice HTML for app (WebView in modal). Same data as showPaymentInvoice.
     */
    public function showPaymentInvoiceHtml($id)
    {
        $invoice = \App\Models\PaymentInvoice::with(['operationLead', 'receivedPayments'])
            ->findOrFail($id);
        $totalReceived = (float) $invoice->receivedPayments->sum('amount');
        $paymentAmount = (float) $invoice->payment_amount;
        if ($totalReceived >= $paymentAmount) {
            $status = 'paid';
        } elseif ($totalReceived > 0) {
            $status = 'partially_paid';
        } else {
            $status = 'unpaid';
        }

        $forApp = true;
        $html = view('admin.operation_leads.payment_invoice_show', compact('invoice', 'totalReceived', 'status', 'forApp'))->render();

        return response($html, 200, ['Content-Type' => 'text/html; charset=UTF-8']);
    }

    /**
     * Return payment invoice as PDF for app download. Same as web download=1.
     */
    public function showPaymentInvoicePdf($id)
    {
        $invoice = \App\Models\PaymentInvoice::with(['operationLead', 'receivedPayments'])
            ->findOrFail($id);
        $totalReceived = (float) $invoice->receivedPayments->sum('amount');
        $paymentAmount = (float) $invoice->payment_amount;
        if ($totalReceived >= $paymentAmount) {
            $status = 'paid';
        } elseif ($totalReceived > 0) {
            $status = 'partially_paid';
        } else {
            $status = 'unpaid';
        }

        $forPdf = true;
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('admin.operation_leads.payment_invoice_show', compact('invoice', 'totalReceived', 'status', 'forPdf'))
            ->setPaper('a4', 'portrait');
        $filename = 'invoice-' . preg_replace('/[^a-zA-Z0-9\-_]/', '-', $invoice->invoice_id) . '.pdf';

        return $pdf->download($filename);
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
        $payment = \App\Models\ReceivedPayment::with('paymentInvoice')->findOrFail($id);
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
