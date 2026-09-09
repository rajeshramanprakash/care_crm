<?php

namespace App\Http\Controllers\Operation;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\OperationLead;
use Yajra\DataTables\Facades\DataTables;
use App\Models\Vendor;
use App\Models\Location;
use App\Models\Service;
use App\Models\WhatsappMsgGroup;
use App\Models\User;
use App\Models\WhatsAppMessage;
use Illuminate\Support\Facades\Auth;
use App\Models\OperationLeadsPaymentDetail;
use App\Models\OperationDeploymentDetails;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;


class OperationLeadController extends Controller
{
    public function index()
    {
        $vendors = Vendor::where('status', 'active')->get();
        $locations = Location::get();
        $executives = User::whereRaw('FIND_IN_SET(role_id, "4")')->get();
        $services = Service::get();

        return view('operation.operation_leads.index', compact('vendors', 'locations', 'services'));
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
            'status' => 'nullable',
            'shift_type' => 'nullable|in:12hr,24hr,both',
            'status_remark' => 'nullable|string',
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
            'stopped_remark' => 'nullable|string',
        ]);

        Log::info($validated);

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

        return response()->json(['message' => 'Operation Lead added successfully', 'lead' => $lead]);
    }

    public function getLeads(Request $request)
    {        $auth_user = Auth::user();

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
            ->leftJoin('users', 'operation_leads.executive', '=', 'users.id')->where('operation_leads.executive', $auth_user->id)
            ->leftJoin(\DB::raw('operation_deployment_details as odd'), function($join) use ($latestDeployments) {
                $join->on('operation_leads.id', '=', 'odd.operation_lead_id')
                     ->whereIn('odd.id', $latestDeployments);
            })
            ->leftJoin('vendors', 'odd.vendor_id', '=', 'vendors.id');

        // Filter by user's assigned services (if services are set for this user)
        if ($auth_user->services) {
            $userServices = explode(',', $auth_user->services);
            $leads->whereIn('operation_leads.query', $userServices);
        }

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
                $filteredVendors = $this->getFilteredVendors($lead->location, $lead->query);
                $count = $filteredVendors->count();
                return '<button class="btn btn-sm btn-info vendor-count-btn" data-lead-id="'.$lead->id.'" data-location="'.$lead->location.'" data-query="'.$lead->query.'" title="Click to view available vendors">
                    <i class="fas fa-users"></i> '.$count.'
                </button>';
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

        // Filter vendors based on lead's location and service
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

        $deploymentDetails = OperationDeploymentDetails::where('operation_lead_id', $lead->id)
            ->with(['vendor', 'freelanceStaff'])
            ->get();

        // Find related CRM Lead by formatted id
        $leadId = \App\Models\Lead::extractIdFromFormattedId($lead->lead_id);
        $crm_lead = $leadId ? \App\Models\Lead::find($leadId) : null;

        // Return JSON for mobile app, view for web
        if (request()->expectsJson()) {
            return response()->json([
                'lead' => $lead,
                'paymentInvoices' => $paymentInvoices,
                'receivedPayments' => $receivedPayments,
                'deploymentDetails' => $deploymentDetails,
                'vendors' => $filteredVendors,
                'services' => $services,
                'locations' => $locations,
                'crm_lead' => $crm_lead
            ]);
        }

        return view('operation.operation_leads.show', compact('lead', 'paymentInvoices', 'receivedPayments', 'filteredVendors', 'services', 'locations', 'deploymentDetails', 'crm_lead', 'executives'));
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
            'status' => 'nullable',
            'shift_type' => 'nullable|in:12hr,24hr,both',
            'status_remark' => 'nullable|string',
            'price_issue_remark' => 'nullable|string',
            'inactive_remark' => 'nullable|string',
            'closed_remark' => 'nullable|string',
            'patient_name' => 'nullable|string',
            'age' => 'nullable|integer',
            'closed_rate' => 'nullable|numeric',
            'vendor_id' => 'nullable|exists:vendors,id',
            'staff_name' => 'nullable|string',
            'vendor_closed_rate' => 'nullable|numeric',
            'outstanding_payment' => 'nullable|numeric',
            'payment_plan' => 'nullable|string',
            'ongoing_stopped' => 'nullable|string',
            'stopped_remark' => 'nullable|string',
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
        
        return response()->json(['message' => 'Operation Lead updated successfully', 'lead' => $lead]);
    }

    public function destroy($id)
    {
        $lead = OperationLead::findOrFail($id);
        $lead->delete();
        return response()->json(['message' => 'Operation Lead deleted successfully']);
    }

    public function getLeadsForApp(Request $request)
    {
        $auth_user = Auth::user();

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
            ->where('operation_leads.executive', $auth_user->id)
            ->leftJoin(\DB::raw('operation_deployment_details as odd'), function($join) use ($latestDeployments) {
                $join->on('operation_leads.id', '=', 'odd.operation_lead_id')
                     ->whereIn('odd.id', $latestDeployments);
            })
            ->leftJoin('vendors', 'odd.vendor_id', '=', 'vendors.id');

        // Filter by user's assigned services (if services are set for this user)
        if ($auth_user->services) {
            $userServices = explode(',', $auth_user->services);
            $leads->whereIn('operation_leads.query', $userServices);
        }

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

        $leads = $leads->orderBy('operation_leads.created_at', 'desc')->get();

        if (request()->expectsJson()) {
            return response()->json($leads);
        }

        return view('operation.operation_leads.index', compact('leads'));
    }

    public function storePaymentDetail(Request $request, $leadId)
    {
        $request->validate([
            'payment_received' => 'nullable|string',
            'utr_number' => 'nullable|string|max:255',
            'received_date' => 'nullable|date',
            'screenshot' => 'required|file|mimes:jpeg,png,jpg,gif,pdf|max:20480',
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

        // Debug logging for mobile app
        if (request()->expectsJson()) {
            Log::info('Mobile app payment detail request', [
                'leadId' => $leadId,
                'requestData' => $request->all(),
                'hasFile' => $request->hasFile('screenshot'),
                'files' => $request->allFiles()
            ]);
        }

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

                Log::info('Payment outstanding calculated:', [
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
                Log::error('Error calculating outstanding payment: ' . $e->getMessage());
                $data['outstanding_payment'] = 0;
            }
        } else {
            $data['outstanding_payment'] = 0;
        }

        // Handle file uploads
        if ($request->hasFile('screenshot')) {
            $data['screenshot'] = $request->file('screenshot')->store('payment-screenshots', 'public');
            Log::info('Screenshot uploaded', ['path' => $data['screenshot']]);
        } else {
            Log::info('No screenshot file found in request');
        }

        if ($request->hasFile('invoice')) {
            $data['invoice'] = $request->file('invoice')->store('payment-invoices', 'public');
        }

        Log::info('Creating payment detail with data:', $data);
        $paymentDetail = OperationLeadsPaymentDetail::create($data);
        Log::info('Payment detail created:', ['id' => $paymentDetail->id, 'screenshot' => $paymentDetail->screenshot]);

        // Get the operation lead to get contact number
        $operationLead = OperationLead::find($leadId);
        if ($operationLead && $operationLead->contact_no) {
            $number = $operationLead->contact_no;
            $name = $operationLead->customer_name;

            // Log::info("Operation Lead Payment Detail: Sending payment confirmation template", [
            //     'lead_id' => $leadId,
            //     'contact_no' => $number,
            //     'customer_name' => $name
            // ]);

            $authKey = config('services.whatsapp.auth_key', env('WHATSAPP_AUTH_KEY'));
            $url = "https://rengage.mcube.com/api/wpbox/sendtemplatemessage";
            $payload = [
                "phone" => $number,
                "template_name" => "payment_confirmation_healthcare",
                "template_language" => "en_GB",
                "components" => [
                    [
                        "type" => "header",
                        "parameters" => [
                            ["type" => "text", "text" => "$name"],
                        ]
                    ]
                ]
            ];

            // Log::info("Operation Lead Payment Detail: Sending API request", ['url' => $url, 'payload' => $payload]);

            $response = \Illuminate\Support\Facades\Http::withHeaders([
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $authKey
            ])->post($url, $payload);

            if ($response->successful()) {
                $responseData = $response->json();
                // Log::info("Operation Lead Payment Detail: API response received", [
                //     'status_code' => $response->status(),
                //     'response_body' => $response->body()
                // ]);

                if (isset($responseData['status']) && $responseData['status'] === 'success') {
                    $currentTimestamp = \Carbon\Carbon::now();

                    // Extract the actual message ID from the complex response structure
                    $messageId = $number; // Default fallback
                    if (isset($responseData['message_id'])) {
                        if (is_array($responseData['message_id']) && isset($responseData['message_id']['messages'][0]['id'])) {
                            $messageId = $responseData['message_id']['messages'][0]['id'];
                        } elseif (is_string($responseData['message_id'])) {
                            $messageId = $responseData['message_id'];
                        }
                    }

                    // Log::info("Operation Lead Payment Detail: Creating WhatsApp message record", [
                    //     'msg_id' => $messageId,
                    //     'msg_from' => $number,
                    //     'type' => 'template'
                    // ]);

                    $newWaMsg = WhatsAppMessage::create([
                        'msg_id' => $messageId,
                        'msg_from' => "$number",
                        'time' => $currentTimestamp,
                        'type' => 'template',
                        'is_sent' => "1",
                        'body' => "*Hi $name* \n We've received your payment for Carelix Healthcare services. \n Thank you for your trust. Please let us know if you need any support."
                    ]);

                    // Log::info("Operation Lead Payment Detail: WhatsApp message created successfully", ['message_id' => $newWaMsg->id]);
                } else {
                    Log::warning("Operation Lead Payment Detail: API returned success but status is not 'success'", ['response_data' => $responseData]);
                }
            } else {
                // Log::error("Operation Lead Payment Detail: API request failed", [
                //     'status_code' => $response->status(),
                //     'response_body' => $response->body()
                // ]);
            }
        } else {
            Log::warning("Operation Lead Payment Detail: No contact number found for lead", ['lead_id' => $leadId]);
        }

        // Return JSON for mobile app, redirect for web
        if (request()->expectsJson()) {
            Log::info('Mobile app payment detail added successfully', [
                'leadId' => $leadId,
                'screenshotPath' => $data['screenshot'] ?? null
            ]);
            return response()->json([
                'success' => true,
                'message' => 'Payment detail added successfully',
                'screenshot_path' => $data['screenshot'] ?? null
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
        Log::info('Update payment detail method called with ID:', ['id' => $id]);
        Log::info('Request method:', ['method' => $request->method()]);
        Log::info('Request content type:', ['content_type' => $request->header('Content-Type')]);
        Log::info('Request expects JSON:', ['expects_json' => $request->expectsJson()]);

        $request->validate([
            'payment_received' => 'nullable|string',
            'utr_number' => 'nullable|string|max:255',
            'received_date' => 'nullable|date',
            'screenshot' => 'nullable|file|mimes:jpeg,png,jpg,gif,pdf|max:20480',
            'invoice' => 'nullable|file|mimes:pdf,doc,docx|max:20480',
            'outstanding_payment' => 'nullable|numeric',
        ]);

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

        // Calculate outstanding payment automatically
        $lead = OperationLead::findOrFail($payment->operation_lead_id);
        $closedRate = $lead->closed_rate ?: 0; // This is now per day rate

        // Get data based on content type
        $contentType = $request->header('Content-Type');
        $data = [];
        $uploadedFiles = [];

        if (strpos($contentType, 'application/json') !== false) {
            // Handle JSON request
            Log::info('Processing JSON request');
            $data = $request->json()->all();
            Log::info('JSON data received:', $data);
        } else if (strpos($contentType, 'multipart/form-data') !== false) {
            // Handle FormData request
            Log::info('Processing FormData request');

            // Try Laravel's built-in methods first
            $data = $request->all();
            Log::info('FormData from all():', $data);

            if (empty($data)) {
                $data = $request->input();
                Log::info('FormData from input():', $data);
            }

            // If Laravel methods don't work, try manual parsing
            if (empty($data)) {
                Log::info('Laravel methods failed, trying manual parsing');
                $rawInput = $request->getContent();

                // Extract boundary from Content-Type header
                preg_match('/boundary=(.*)$/', $contentType, $matches);
                $boundary = $matches[1] ?? '';

                if ($boundary) {
                    // Parse multipart data manually
                    $parts = explode("--$boundary", $rawInput);
                    Log::info('Number of parts found:', ['count' => count($parts)]);

                    foreach ($parts as $part) {
                        if (empty(trim($part)) || trim($part) === '--') {
                            continue;
                        }

                        // Extract field name
                        if (preg_match('/name="([^"]+)"/', $part, $nameMatches)) {
                            $fieldName = $nameMatches[1];

                            // Check if this is a file field
                            if (preg_match('/filename="([^"]+)"/', $part, $filenameMatches)) {
                                $filename = $filenameMatches[1];
                                Log::info("Processing file field: $fieldName", ['filename' => $filename]);

                                // Find the start of the actual file content (after the empty line)
                                // For binary files, we need to find the content by byte position, not by lines
                                $headerEndPos = strpos($part, "\r\n\r\n");
                                if ($headerEndPos !== false) {
                                    // Extract content after the double CRLF (which separates headers from content)
                                    $fileContent = substr($part, $headerEndPos + 4);

                                    // Remove the trailing boundary if present
                                    $boundaryPattern = "\r\n--" . $boundary;
                                    $boundaryPos = strrpos($fileContent, $boundaryPattern);
                                    if ($boundaryPos !== false) {
                                        $fileContent = substr($fileContent, 0, $boundaryPos);
                                    }

                                    // Also check for boundary without leading CRLF
                                    $boundaryPattern2 = "--" . $boundary;
                                    $boundaryPos2 = strrpos($fileContent, $boundaryPattern2);
                                    if ($boundaryPos2 !== false && $boundaryPos2 > strlen($fileContent) - 100) {
                                        $fileContent = substr($fileContent, 0, $boundaryPos2);
                                    }
                                } else {
                                    $fileContent = '';
                                }

                                if (!empty($fileContent)) {
                                    // Debug: Show first few bytes to verify it's binary data
                                    $firstBytes = substr(bin2hex($fileContent), 0, 32);
                                    Log::info("File content first 16 bytes (hex):", ['hex' => $firstBytes]);

                                    // Create a temporary file
                                    $tempPath = storage_path('app/temp/' . uniqid() . '_' . $filename);
                                    if (!file_exists(dirname($tempPath))) {
                                        mkdir(dirname($tempPath), 0755, true);
                                    }

                                    file_put_contents($tempPath, $fileContent);
                                    $uploadedFiles[$fieldName] = [
                                        'path' => $tempPath,
                                        'original_name' => $filename,
                                        'size' => strlen($fileContent)
                                    ];

                                    Log::info("File saved to temp path:", ['field' => $fieldName, 'path' => $tempPath, 'size' => strlen($fileContent)]);
                                }
                            } else {
                                // This is a text field - extract only the value after the empty line
                                $lines = explode("\r\n", $part);
                                $value = '';
                                $foundEmptyLine = false;

                                foreach ($lines as $line) {
                                    if (empty(trim($line))) {
                                        $foundEmptyLine = true;
                                        continue;
                                    }
                                    if ($foundEmptyLine) {
                                        $value .= $line . "\r\n";
                                    }
                                }

                                $value = trim($value);
                                // Remove any trailing boundary
                                $value = preg_replace('/--' . preg_quote($boundary, '/') . '--?$/', '', $value);
                                $value = trim($value);

                                if (!empty($value)) {
                                    $data[$fieldName] = $value;
                                    Log::info("Extracted text field: $fieldName", ['value' => $value]);
                                }
                            }
                        }
                    }
                }
            }
        } else {
            // Fallback - try all methods
            Log::info('Unknown content type, trying all methods');
            $data = $request->all();
            if (empty($data)) {
                $data = $request->input();
            }
            if (empty($data)) {
                $data = $request->post();
            }
        }

        Log::info('Final data to process:', $data);
        Log::info('Uploaded files:', $uploadedFiles);

        // Handle file uploads
        if (isset($uploadedFiles['screenshot'])) {
            Log::info('Processing uploaded screenshot file');
            try {
                // Delete old screenshot if exists
                if ($payment->screenshot) {
                    Log::info('Attempting to delete old screenshot:', ['path' => $payment->screenshot]);

                    // Check if the path looks valid before attempting to delete
                    if (is_string($payment->screenshot) && !empty($payment->screenshot) &&
                        !str_contains($payment->screenshot, 'content-disposition') &&
                        !str_contains($payment->screenshot, 'multipart')) {
                        try {
                            Storage::disk('public')->delete($payment->screenshot);
                            Log::info('Old screenshot deleted:', ['path' => $payment->screenshot]);
                        } catch (Exception $e) {
                            Log::warning('Failed to delete old screenshot:', ['error' => $e->getMessage()]);
                        }
                    } else {
                        Log::warning('Skipping deletion of corrupted screenshot path:', ['path' => $payment->screenshot]);
                    }
                }

                // Move file to storage
                $fileInfo = $uploadedFiles['screenshot'];
                Log::info('File info for screenshot:', $fileInfo);

                $storagePath = 'payment-screenshots/' . uniqid() . '_' . $fileInfo['original_name'];
                Log::info('Generated storage path:', ['path' => $storagePath]);

                // Read the file content and store it
                Log::info('Reading file from temp path:', ['temp_path' => $fileInfo['path']]);

                if (!file_exists($fileInfo['path'])) {
                    Log::error('Temporary file does not exist:', ['path' => $fileInfo['path']]);
                    throw new Exception('Temporary file not found');
                }

                $fileContent = file_get_contents($fileInfo['path']);

                if ($fileContent !== false) {
                    Log::info('File content read successfully, size:', ['size' => strlen($fileContent)]);
                    Log::info('Storing file to:', ['storage_path' => $storagePath]);

                    $result = Storage::disk('public')->put($storagePath, $fileContent);
                    Log::info('Storage result:', ['result' => $result]);

                    if ($result) {
                        // Clean up temp file
                        unlink($fileInfo['path']);
                        Log::info('Temporary file cleaned up');

                        $data['screenshot'] = $storagePath;
                        Log::info('New screenshot stored:', ['path' => $data['screenshot']]);
                    } else {
                        Log::error('Failed to store file to storage');
                        throw new Exception('Failed to store file');
                    }
                } else {
                    Log::error('Failed to read temporary file:', ['path' => $fileInfo['path']]);
                    throw new Exception('Failed to read temporary file');
                }
            } catch (Exception $e) {
                Log::error('Error processing screenshot file:', [
                    'error' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString()
                ]);
                // Don't throw the exception, just log it and continue
            }
        } else if ($request->hasFile('screenshot')) {
            Log::info('Screenshot file found in request via Laravel');
            // Delete old screenshot if exists
            if ($payment->screenshot) {
                Storage::disk('public')->delete($payment->screenshot);
                Log::info('Old screenshot deleted:', ['path' => $payment->screenshot]);
            }
            $data['screenshot'] = $request->file('screenshot')->store('payment-screenshots', 'public');
            Log::info('New screenshot stored:', ['path' => $data['screenshot']]);
        } else {
            Log::info('No screenshot file in request');
        }

        if (isset($uploadedFiles['invoice'])) {
            Log::info('Processing uploaded invoice file');
            try {
                // Delete old invoice if exists
                if ($payment->invoice) {
                    Storage::disk('public')->delete($payment->invoice);
                    Log::info('Old invoice deleted:', ['path' => $payment->invoice]);
                }

                // Move file to storage
                $fileInfo = $uploadedFiles['invoice'];
                $storagePath = 'payment-invoices/' . uniqid() . '_' . $fileInfo['original_name'];

                // Read the file content and store it
                $fileContent = file_get_contents($fileInfo['path']);
                if ($fileContent !== false) {
                    Storage::disk('public')->put($storagePath, $fileContent);

                    // Clean up temp file
                    unlink($fileInfo['path']);

                    $data['invoice'] = $storagePath;
                    Log::info('New invoice stored:', ['path' => $data['invoice']]);
                } else {
                    Log::error('Failed to read temporary file:', ['path' => $fileInfo['path']]);
                }
            } catch (Exception $e) {
                Log::error('Error processing invoice file:', ['error' => $e->getMessage()]);
            }
        } else if ($request->hasFile('invoice')) {
            Log::info('Invoice file found in request via Laravel');
            // Delete old invoice if exists
            if ($payment->invoice) {
                Storage::disk('public')->delete($payment->invoice);
                Log::info('Old invoice deleted:', ['path' => $payment->invoice]);
            }
            $data['invoice'] = $request->file('invoice')->store('payment-invoices', 'public');
            Log::info('New invoice stored:', ['path' => $data['invoice']]);
        } else {
            Log::info('No invoice file in request');
        }

        Log::info('Data before update:', $data);

        // Calculate outstanding payment for this specific payment detail
        if (isset($data['from_date_time']) && isset($data['to_date_time']) && isset($data['payment_received'])) {
            try {
                $fromDate = \Carbon\Carbon::parse($data['from_date_time']);
                $toDate = \Carbon\Carbon::parse($data['to_date_time']);
                $paymentReceived = (float) $data['payment_received'];
                $refundAmount = (float) ($data['refund_amount'] ?? 0);

                // Calculate work days (inclusive of both start and end dates)
                $workDays = $fromDate->diffInDays($toDate) + 1;

                // Calculate total expected amount for this payment period
                $totalExpectedAmount = $workDays * $closedRate;

                // Calculate outstanding payment for this specific payment detail
                $netAmount = $paymentReceived - $refundAmount;
                $outstandingAmount = $netAmount - $totalExpectedAmount;

                $data['outstanding_payment'] = $outstandingAmount; // Allow negative values

                Log::info('Payment outstanding calculated (update):', [
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
                Log::error('Error calculating outstanding payment on update: ' . $e->getMessage());
                $data['outstanding_payment'] = 0;
            }
        }

        // Only update if we have data
        if (!empty($data)) {
            $payment->update($data);
            Log::info('Payment detail updated successfully');
        } else {
            Log::info('No data to update');
        }

        // Always return JSON for mobile app requests
        Log::info('Returning JSON response for mobile app');
        return response()->json([
            'success' => true,
            'message' => 'Payment detail updated successfully'
        ]);
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
        $validationRules = [
            'deployment_date' => 'required|date',
            'deployment_status' => 'required|string',
            'vendor_id' => 'nullable|string',
            'staff_name' => 'nullable|string',
            'vendor_rate_per_day' => 'nullable|numeric',
            'verify_payment' => 'nullable|boolean',
        ];
        
        // Make deployment_to_date required if status is not "Pending" or "In Progress"
        $status = $request->input('deployment_status');
        if ($status && !in_array($status, ['Pending', 'In Progress'])) {
            $validationRules['deployment_to_date'] = 'required|date';
        }
        
        $request->validate($validationRules);

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
        }

        $deployment = OperationDeploymentDetails::create($data);

        // Generate payment invoice if deployment is In Progress
        if ($deployment->deployment_status === 'In Progress') {
            \App\Services\PaymentInvoiceService::generateInvoicesForDeployment($deployment);
        }

       $operationLead = OperationLead::find($leadId);
       if ($operationLead && $operationLead->contact_no) {
           $number = $operationLead->contact_no;
           $name = $operationLead->customer_name;

           $authKey = config('services.whatsapp.auth_key', env('WHATSAPP_AUTH_KEY'));
           $url = "https://rengage.mcube.com/api/wpbox/sendtemplatemessage";

           $staffName = $data['staff_name'] ?? 'Staff Member';
           $staffRole = $operationLead->query;
           $joiningDateTime = $data['deployment_date'] ?? now()->format('Y-m-d H:i:s');

           $payload = [
               "phone" => $number,
               "template_name" => "staff_update_healthcare",
               "template_language" => "en_GB",
               "components" => [
                   [
                       "type" => "header",
                       "parameters" => [
                           ["type" => "text", "text" => "$name"],
                       ]
                   ],
                   [
                       "type" => "body",
                       "parameters" => [
                           ["type" => "text", "text" => "$staffName"],
                           ["type" => "text", "text" => "$staffRole"],
                           ["type" => "text", "text" => "$joiningDateTime"],
                       ]
                   ]
               ]
           ];

           $response = \Illuminate\Support\Facades\Http::withHeaders([
               'Content-Type' => 'application/json',
               'Authorization' => 'Bearer ' . $authKey
           ])->post($url, $payload);

           if ($response->successful()) {
               $responseData = $response->json();
               if (isset($responseData['status']) && $responseData['status'] === 'success') {
                   $currentTimestamp = \Carbon\Carbon::now();

                   $messageId = $number;
                   if (isset($responseData['message_id'])) {
                       if (is_array($responseData['message_id']) && isset($responseData['message_id']['messages'][0]['id'])) {
                           $messageId = $responseData['message_id']['messages'][0]['id'];
                       } elseif (is_string($responseData['message_id'])) {
                           $messageId = $responseData['message_id'];
                       }
                   }

                   $newWaMsg = WhatsAppMessage::create([
                       'msg_id' => $messageId,
                       'msg_from' => "$number",
                       'time' => $currentTimestamp,
                       'type' => 'template',
                       'is_sent' => "1",
                       'body' => "*Hi $name* \n We would like to inform you that your healthcare staff has been assigned. \n Name: $staffName \n Role: $staffRole \n Joining Date/Time: $joiningDateTime \n Kindly acknowledge"
                   ]);

               } else {
                   Log::warning("Operation Lead Payment Detail: API returned success but status is not 'success'", ['response_data' => $responseData]);
               }
           } else {
           }
       } else {
           Log::warning("Operation Lead Payment Detail: No contact number found for lead", ['lead_id' => $leadId]);
       }



        return response()->json([
            'success' => true,
            'message' => 'Deployment detail added successfully',
            'deployment' => $deployment
        ]);
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
        $deployment = OperationDeploymentDetails::findOrFail($id);
        
        $validationRules = [
            'deployment_date' => 'required|date',
            'deployment_status' => 'required|string',
            'vendor_id' => 'nullable|string',
            'staff_name' => 'nullable|string',
            'vendor_rate_per_day' => 'nullable|numeric',
            'verify_payment' => 'nullable|boolean',
        ];
        
        // Make deployment_to_date required if status is changing from "In Progress" to something else (except "Pending")
        $oldStatus = $deployment->deployment_status;
        $newStatus = $request->input('deployment_status');
        
        if ($oldStatus === 'In Progress' && $newStatus && !in_array($newStatus, ['Pending', 'In Progress'])) {
            $validationRules['deployment_to_date'] = 'required|date';
        }
        
        $request->validate($validationRules);

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
            // Regular vendor, clear freelance staff ID
            $data['freelance_staff_id'] = null;
        }

        $deployment->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Deployment detail updated successfully',
            'deployment' => $deployment
        ]);
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
        $freelanceQuery = \App\Models\JobRequest::where('city', $location)
            ->where('job_title', $service)
            ->whereNotNull('name')
            ->where('name', '!=', '');

        // If not including inactive, only get active freelancers
        if (!$includeInactive) {
            $freelanceQuery->where('status', 'active');
        }

        $freelanceStaff = $freelanceQuery->get()
            ->map(function ($jobRequest) {
                return (object) [
                    'id' => $jobRequest->id, // Use actual ID for status updates
                    'name' => $jobRequest->name,
                    'contact_no' => $jobRequest->contact_no,
                    'is_freelance' => true,
                    'status' => $jobRequest->status
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
        try {
            $leadId = $request->input('lead_id');
            $location = $request->input('location');
            $query = $request->input('query');

            \Log::info('getVendorDetails called', [
                'lead_id' => $leadId,
                'location' => $location,
                'query' => $query,
                'user_id' => auth()->id()
            ]);

            if (!$leadId || !$location || !$query) {
                return response()->json([
                    'success' => false,
                    'message' => 'Missing required parameters'
                ], 400);
            }

            $lead = OperationLead::findOrFail($leadId);

            // Get only active vendors and freelancers for display
            $filteredVendors = $this->getFilteredVendors($location, $query, false);

            $vendorDetails = $filteredVendors->map(function ($vendor) {
                return [
                    'id' => $vendor->id,
                    'name' => $vendor->name,
                    'contact_no' => $vendor->contact_no ?? 'N/A',
                    'is_freelance' => $vendor->is_freelance ?? false,
                    'status' => $vendor->status ?? 'active'
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
        } catch (\Exception $e) {
            \Log::error('Error in getVendorDetails: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while fetching vendor details'
            ], 500);
        }
    }

    /**
     * Update freelancer status (active, inactive, blacklist)
     */
    public function updateFreelancerStatus(Request $request)
    {
        try {
            $request->validate([
                'freelancer_id' => 'required|integer',
                'status' => 'required|in:active,inactive,blacklist'
            ]);

            // Find the freelancer in JobRequest model
            $freelancer = \App\Models\JobRequest::find($request->freelancer_id);

            if (!$freelancer) {
                return response()->json([
                    'success' => false,
                    'message' => 'Freelancer not found'
                ], 404);
            }

            // Update the status
            $freelancer->status = $request->status;
            $freelancer->save();

            $statusText = ucfirst($request->status);
            if ($request->status === 'blacklist') {
                $statusText = 'Blacklisted';
            }

            return response()->json([
                'success' => true,
                'message' => "Freelancer status updated to {$statusText} successfully"
            ]);

        } catch (\Exception $e) {
            \Log::error('Error updating freelancer status: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while updating freelancer status'
            ], 500);
        }
    }

    /**
     * Get updated vendor count for a specific lead
     */
    public function getUpdatedVendorCount(Request $request)
    {
        try {
            $leadId = $request->input('lead_id');
            $location = $request->input('location');
            $query = $request->input('query');

            if (!$leadId || !$location || !$query) {
                return response()->json([
                    'success' => false,
                    'message' => 'Missing required parameters'
                ], 400);
            }

            // Get only active vendors and freelancers for count
            $filteredVendors = $this->getFilteredVendors($location, $query, false);
            $count = $filteredVendors->count();

            return response()->json([
                'success' => true,
                'count' => $count
            ]);

        } catch (\Exception $e) {
            \Log::error('Error getting updated vendor count: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while getting vendor count'
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
