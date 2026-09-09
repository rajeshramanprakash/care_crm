<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\OperationLead;
use App\Models\JobRequest;
use App\Models\Location;
use App\Models\WhatsappMsgGroup;
use App\Models\User;
use App\Models\WhatsAppMessage;
use App\Models\Service;
use App\Facades\UserAssignment;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;




class LeadController extends Controller
{
    public function index()
    {
        $locations = Location::get();
        $executives = User::whereRaw('FIND_IN_SET(role_id, "2")')->get();
        $services = Service::get();
        return view('sales.leads.index', compact('locations', 'executives', 'services'));
    }

    public function getLeads(Request $request)
    {
        $auth_user = Auth::user();
        $leads = Lead::select(['leads.*',
        'leads.last_call_status',
        'leads.recording_url',
        'users.f_name as executive_name'])
            ->leftJoin('users', 'leads.executive', '=', 'users.id')->where('leads.executive', $auth_user->id);

            if ($request->executives != null) {
                $leads->whereIn('leads.executive', $request->executives);
            }

            if ($request->location != null) {
                $leads->whereIn('leads.location', $request->location);
            }
            if ($request->query_filter != null) {
                $leads->whereIn('leads.query', $request->query_filter);
            }
            if ($request->lead_source != null) {
                $leads->whereIn('leads.lead_source', $request->lead_source);
            }
            if ($request->status != null) {
                $leads->whereIn('leads.status', $request->status);
            }
            if ($request->stage != null) {
                $leads->whereIn('leads.stage', $request->stage);
            }

        // Return JSON for API requests
        if ($request->expectsJson()) {
            $leads = $leads->get();

            // Transform data for API response
            $transformedData = $leads->map(function ($lead) {
                return [
                    'id' => $lead->id,
                    'lead_code' => $lead->formatted_id,
                    'date' => $lead->date,
                    'executive' => $lead->executive_name ?? '-',
                    'customer_name' => $lead->customer_name,
                    'patient_name' => $lead->patient_name,
                    'patient_gender' => $lead->patient_gender,
                    'contact_type' => $lead->contact_type,
                    'contact_no' => $lead->contact_no,
                    'last_call_status' => $lead->last_call_status,
                    'recording_url' => $lead->recording_url,
                    'location' => $lead->location,
                    'lead_source' => $lead->lead_source,
                    'query' => $lead->query,
                    'query_remarks' => $lead->query_remarks,
                    'status' => $lead->status ?? '-',
                    'status_remarks' => $lead->status_remarks,
                    'stage' => $lead->stage ?? 'active',
                    'future_prospect_date' => ($lead->status === 'future prospect' && $lead->future_prospect_date)
                        ? date('d-m-Y', strtotime($lead->future_prospect_date))
                        : null,
                    'prospect_rate' => ($lead->status === 'prospect' && $lead->prospect_rate !== null)
                        ? $lead->prospect_rate
                        : null
                ];
            });

            return response()->json([
                'data' => $transformedData
            ]);
        }

        return DataTables::of($leads)
            ->editColumn('status', function ($lead) {
                return $lead->status ?? '-';
            })
            ->addColumn('lead_code', function ($lead) {
                return $lead->formatted_id;
            })
            ->editColumn('future_prospect_date', function ($lead) {
                return ($lead->status === 'future prospect' && $lead->future_prospect_date)
                    ? date('d-m-Y', strtotime($lead->future_prospect_date))
                    : '-';
            })
            ->editColumn('prospect_rate', function ($lead) {
                return ($lead->status === 'prospect' && $lead->prospect_rate !== null)
                    ? $lead->prospect_rate . ' Rupees'
                    : '-';
            })
            ->editColumn('stage', function ($lead) {
                return $lead->stage ?? 'active';
            })
            ->editColumn('executive', function ($lead) {
                return $lead->executive_name ?? '-';
            })
            ->addColumn('action', function ($lead) {
                return '<button class="btn btn-primary btn-sm edit-btn" data-id="' . $lead->id . '">
                    <i class="fas fa-edit"></i>
                </button>';
            })
            ->rawColumns(['action'])
            ->make(true);
    }

    public function edit($id)
    {
        $lead = Lead::findOrFail($id);
        $lead->lead_code = $lead->formatted_id;
        return response()->json($lead);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'customer_name' => 'required|string',
            'patient_name' => 'nullable|string',
            'patient_gender' => 'nullable|in:male,female,other',
            'age' => 'nullable|integer|min:0|max:150',
            'contact_type' => 'nullable|in:call,whatsapp,email',
            'contact_no' => 'nullable|string',
            'location' => 'nullable|string',
            'query_remarks' => 'nullable|string',
            'status_remarks' => 'nullable|string',
            'lead_source' => 'nullable|in:web,ivrs,whatsapp,manual',
            'query' => 'nullable|string',
            'status' => 'nullable|in:follow-up,future prospect,prospect,no response,price issue,duplicate,spam',
            'stage' => 'nullable|in:active,inactive,closed,profile required',
            'shift_type' => 'nullable|in:12hr,24hr,both',
            'future_prospect_date' => 'required_if:status,future prospect|date|nullable',
            'prospect_rate' => 'required_if:status,prospect|nullable'
        ]);

        $lead = Lead::findOrFail($id);

        $data = $request->only([
            'customer_name',
            'patient_name',
            'patient_gender',
            'age',
            'contact_type',
            'contact_no',
            'location',
            'lead_source',
            'query',
            'status',
            'stage',
            'shift_type',
            'future_prospect_date',
            'prospect_rate',
            'query_remarks',
            'status_remarks'
        ]);

        if ($request->status !== 'future prospect') {
            $data['future_prospect_date'] = null;
        }
        if ($request->status !== 'prospect') {
            $data['prospect_rate'] = null;
        }

        $lead->update($data);

        // Handle OperationLead creation if status is prospect (AFTER lead update)
        if($request->status === 'prospect' && $request->stage == 'closed'){
            $location = Location::where('name', $lead->location)->first();

             $getUser =UserAssignment::getAssigningUser(4, $location->id);
            $operation_lead = OperationLead::where('contact_no', $lead->contact_no)->first();
            if(!$operation_lead){
                $operation_lead = new OperationLead();
                if($location) {
                    $operation_lead->executive = $getUser->id;
                    Log::info('Sales Lead Update: Location found for OperationLead', [
                        'location_id' => $location->id,
                        'location_name' => $location->name,
                        'assigned_executive' => $operation_lead->executive
                    ]);
                } else {
                    // Fallback if location not found
                    $operation_lead->executive = $getUser->id;
                    Log::warning('Sales Lead Update: Location not found, using fallback assignment', [
                        'location_name' => $lead->location,
                        'assigned_executive' => $operation_lead->executive
                    ]);
                }
                $operation_lead->customer_name = $lead->customer_name;
                $operation_lead->contact_type = $lead->contact_type;
                $operation_lead->contact_no = $lead->contact_no;
                $operation_lead->address = $lead->address;
                $operation_lead->location = $lead->location;
                $operation_lead->query = $lead->query;
                $operation_lead->status = 'follow-up';
                $operation_lead->closed_rate = $lead->prospect_rate;
                $operation_lead->lead_id = $lead->formatted_id;
                $operation_lead->patient_gender = $lead->patient_gender;
                $operation_lead->patient_name = $lead->patient_name;
                $operation_lead->shift_type = $lead->shift_type;


                $operation_lead->date_time = now();
                $operation_lead->save();

                $number = $lead->contact_no;
                $execId = $getUser->id;
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

                Log::info('Sales Lead Update: OperationLead created successfully', [
                    'operation_lead_id' => $operation_lead->id,
                    'lead_id' => $lead->id,
                    'contact_no' => $lead->contact_no,
                    'executive' => $operation_lead->executive
                ]);
            } else {
                $operation_lead->date_time = $lead->date;
                $operation_lead->save();

                Log::info('Sales Lead Update: OperationLead already exists, updated timestamp', [
                    'operation_lead_id' => $operation_lead->id,
                    'lead_id' => $lead->id,
                    'contact_no' => $lead->contact_no
                ]);
            }
        }

        if($request->input('query') == 'attendant' || $request->input('query') == 'nurse' || $request->input('query') == 'on call nurse'){
            Log::info("Sales Lead Update: Sending job_request_followup template", [
                'lead_id' => $lead->id,
                'contact_no' => $lead->contact_no,
                'query' => $request->input('query')
            ]);

            $number = $lead->contact_no;
            $name = $lead->customer_name;
            $authKey = config('services.whatsapp.auth_key', env('WHATSAPP_AUTH_KEY'));
            $url = "https://rengage.mcube.com/api/wpbox/sendtemplatemessage";
            $payload = [
                "phone" => $number,
                "template_name" => "patient_care_details_request",
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

            Log::info("Sales Lead Update: Sending API request", ['url' => $url, 'payload' => $payload]);

            $response = \Illuminate\Support\Facades\Http::withHeaders([
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $authKey
            ])->post($url, $payload);
                        if ($response->successful()) {
                $responseData = $response->json();
                Log::info("Sales Lead Update: API response received", [
                    'status_code' => $response->status(),
                    'response_body' => $response->body()
                ]);

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
                        'body' => "*Hi $name* \n Thank you for speaking with Carelix Healthcare. To assist you better, please share the patient details: \n – Patient Name \n – Age / Sex \n – Weight \n – Required care hours (12hrs / 24hrs) \n – Full Address \n -City \n – Patient condition \n – Bedridden or non-bedridden"
                    ]);

                    Log::info("Sales Lead Update: WhatsApp message created successfully", ['message_id' => $newWaMsg->id]);
                } else {
                    Log::warning("Sales Lead Update: API returned success but status is not 'success'", ['response_data' => $responseData]);
                }
            } else {
                Log::error("Sales Lead Update: API request failed", [
                    'status_code' => $response->status(),
                    'response_body' => $response->body()
                ]);
            }
        }

        // Handle JobRequest creation if query is job request (AFTER lead update)
        if($request->input('query') == 'job request'){
            Log::info('Sales Lead Update: Attempting to create JobRequest', [
                'lead_id' => $lead->id,
                'contact_no' => $lead->contact_no,
                'query' => $request->input('query'),
                'executive_id' => auth()->user()->id
            ]);

            $job_request = JobRequest::where('contact_no', $lead->contact_no)->first();
            if(!$job_request){
                $location = Location::where('name', $lead->location)->first();
                $getUser = UserAssignment::getAssigningUser(4, $location->id);

                $job_request = new JobRequest();
                $job_request->executive_id = $getUser->id;
                $job_request->customer_name = $lead->customer_name;
                $job_request->contact_no = $lead->contact_no;
                $job_request->name = $lead->patient_name ?: $lead->customer_name;
                $job_request->age = '25|' . ($lead->patient_gender ?: 'male'); // Default age and gender
                $job_request->expected_salary = 0; // Default salary
                $job_request->shift = '12'; // Default shift
                $job_request->total_experience = '0 years'; // Default experience
                $job_request->job_title = $lead->query_remarks ?: 'General Job'; // Use query remarks as job title
                $job_request->other_remark = $lead->query_remarks;
                $job_request->city = $lead->location;
                $job_request->remark = $lead->status_remarks;
                $job_request->status = 'active';
                $job_request->lead_id = $lead->id;
                $job_request->date_time = $lead->date;
                $job_request->save();

                Log::info('Sales Lead Update: Location found for JobRequest', [
                    'location_id' => $location->id,
                    'location_name' => $location->name,
                    'assigned_executive' => $job_request->executive_id
                ]);

                $number = $lead->contact_no;
                $execId = $getUser->id;
                $name = $lead->customer_name;

                $authKey = config('services.whatsapp.auth_key', env('WHATSAPP_AUTH_KEY'));
                $url = "https://rengage.mcube.com/api/wpbox/sendtemplatemessage";
                $payload = [
                    "phone" => $number,
                    "template_name" => "job_request_followup",
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

                Log::info("Sales Lead Update: Sending job_request_followup template for JobRequest", [
                    'job_request_id' => $job_request->id,
                    'contact_no' => $number,
                    'executive_id' => $execId
                ]);

                Log::info("Sales Lead Update: Sending API request", ['url' => $url, 'payload' => $payload]);

                $response = \Illuminate\Support\Facades\Http::withHeaders([
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $authKey
                ])->post($url, $payload);
                if ($response->successful()) {
                    $responseData = $response->json();
                    Log::info("Sales Lead Update: API response received for JobRequest", [
                        'status_code' => $response->status(),
                        'response_body' => $response->body()
                    ]);

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

                        Log::info("Sales Lead Update: Creating WhatsApp message record for JobRequest", [
                            'msg_id' => $messageId,
                            'msg_from' => $number,
                            'type' => 'template'
                        ]);

                        $newWaMsg = WhatsAppMessage::create([
                            'msg_id' => $messageId,
                            'msg_from' => "$number",
                            'time' => $currentTimestamp,
                            'type' => 'template',
                            'is_sent' => "1",
                            'body' => "*Hi $name* \n Thank you for your interest in joining Carelix Healthcare. \n To proceed, please share the following details: \n – Your current address \n – City \n – Comfortable working hours (12hrs / 24hrs) \n – Salary expectations \n – Preferred role (Attendant / Nursing) \n – Education \n – Any certificate (if available) \n– experience in this field"
                        ]);

                        Log::info("Sales Lead Update: WhatsApp message created successfully for JobRequest", ['message_id' => $newWaMsg->id]);
                    } else {
                        Log::warning("Sales Lead Update: API returned success but status is not 'success' for JobRequest", ['response_data' => $responseData]);
                    }
                } else {
                    Log::error("Sales Lead Update: API request failed for JobRequest", [
                        'status_code' => $response->status(),
                        'response_body' => $response->body()
                    ]);
                }

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

                Log::info('Sales Lead Update: JobRequest created successfully', [
                    'job_request_id' => $job_request->id,
                    'lead_id' => $lead->id,
                    'contact_no' => $lead->contact_no,
                    'executive_id' => $job_request->executive_id
                ]);
            } else {
                $job_request->date_time = $lead->date;
                $job_request->save();

                Log::info('Sales Lead Update: JobRequest already exists, updated timestamp', [
                    'job_request_id' => $job_request->id,
                    'lead_id' => $lead->id,
                    'contact_no' => $lead->contact_no
                ]);
            }
        } else {
            Log::info('Sales Lead Update: JobRequest creation skipped', [
                'lead_id' => $lead->id,
                'query' => $request->input('query'),
                'query_expected' => 'job request',
                'condition_met' => $request->input('query') == 'job request'
            ]);
        }

        return response()->json([
            'message' => 'Lead updated successfully',
            'lead' => $lead
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'customer_name' => 'nullable|string',
            'patient_name' => 'nullable|string',
            'patient_gender' => 'nullable|in:male,female,other',
            'age' => 'nullable|integer|min:0|max:150',
            'contact_type' => 'nullable|in:call,whatsapp,email',
            'contact_no' => 'required|string',
            'location' => 'nullable|string',
            'lead_source' => 'nullable|in:web,ivrs,whatsapp,manual',
            'query_remarks' => 'nullable|string',
            'status_remarks' => 'nullable|string',
            'query' => 'nullable|string',
            'status' => 'nullable|in:follow-up,future prospect,prospect,no response,price issue,duplicate,spam',
            'stage' => 'nullable|in:active,inactive,closed,profile required',
            'shift_type' => 'nullable|in:12hr,24hr,both',
            'future_prospect_date' => 'nullable|date',
            'prospect_rate' => 'nullable'
        ]);

        // Set future_prospect_date to null if status is not future prospect
        $data = $request->only([
            'customer_name',
            'patient_name',
            'patient_gender',
            'age',
            'contact_type',
            'contact_no',
            'location',
            'lead_source',
            'query',
            'status',
            'stage',
            'shift_type',
            'future_prospect_date',
            'prospect_rate',
            'query_remarks',
            'status_remarks'
        ]);

        if ($request->status !== 'future prospect') {
            $data['future_prospect_date'] = null;
        }
        if ($request->status !== 'prospect') {
            $data['prospect_rate'] = null;
        }

        // Create the lead first
        $lead = Lead::create(array_merge(
            $data,
            [
                'date' => now(),
                'executive' => auth()->user()->id,
                'lead_source' => 'manual',
                'contact_type' => 'call'
            ]
        ));

        // Handle JobRequest creation if query is job request (AFTER lead creation)
        if($request->input('query') == 'job request'){
            Log::info('Sales Lead Create: Attempting to create JobRequest', [
                'lead_id' => $lead->id,
                'contact_no' => $lead->contact_no,
                'query' => $request->input('query'),
                'executive_id' => auth()->user()->id
            ]);

            $job_request = JobRequest::where('contact_no', $lead->contact_no)->first();
            if(!$job_request){
                $location = Location::where('name', $lead->location)->first();
                $getUser = UserAssignment::getAssigningUser(4, $location->id);

                $job_request = new JobRequest();
                $job_request->executive_id = $getUser->id;
                $job_request->customer_name = $lead->customer_name;
                $job_request->contact_no = $lead->contact_no;
                $job_request->name = $lead->patient_name ?: $lead->customer_name;
                $job_request->age = '25|' . ($lead->patient_gender ?: 'male'); // Default age and gender
                $job_request->expected_salary = 0; // Default salary
                $job_request->shift = '12'; // Default shift
                $job_request->total_experience = '0 years'; // Default experience
                $job_request->job_title = $lead->query_remarks ?: 'General Job'; // Use query remarks as job title
                $job_request->other_remark = $lead->query_remarks;
                $job_request->city = $lead->location;
                $job_request->remark = $lead->status_remarks;
                $job_request->status = 'inactive';
                $job_request->lead_id = $lead->id;
                $job_request->date_time = $lead->date;
                $job_request->save();

                Log::info('Sales Lead Create: Location found for JobRequest', [
                    'location_id' => $location->id,
                    'location_name' => $location->name,
                    'assigned_executive' => $job_request->executive_id
                ]);

                $number = $lead->contact_no;
                $execId = $getUser->id;
                $name = $lead->customer_name;

                $authKey = config('services.whatsapp.auth_key', env('WHATSAPP_AUTH_KEY'));
                $url = "https://rengage.mcube.com/api/wpbox/sendtemplatemessage";
                $payload = [
                    "phone" => $number,
                    "template_name" => "job_request_followup",
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

                Log::info("Sales Lead Create: Sending job_request_followup template for JobRequest", [
                    'job_request_id' => $job_request->id,
                    'contact_no' => $number,
                    'executive_id' => $execId
                ]);

                Log::info("Sales Lead Create: Sending API request", ['url' => $url, 'payload' => $payload]);

                $response = \Illuminate\Support\Facades\Http::withHeaders([
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $authKey
                ])->post($url, $payload);
                if ($response->successful()) {
                    $responseData = $response->json();
                    Log::info("Sales Lead Create: API response received for JobRequest", [
                        'status_code' => $response->status(),
                        'response_body' => $response->body()
                    ]);

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

                        Log::info("Sales Lead Create: Creating WhatsApp message record for JobRequest", [
                            'msg_id' => $messageId,
                            'msg_from' => $number,
                            'type' => 'template'
                        ]);

                        $newWaMsg = WhatsAppMessage::create([
                            'msg_id' => $messageId,
                            'msg_from' => "$number",
                            'time' => $currentTimestamp,
                            'type' => 'template',
                            'is_sent' => "1",
                            'body' => "*Hi $name* \n Thank you for your interest in joining Carelix Healthcare. \n To proceed, please share the following details: \n – Your current address \n – City \n – Comfortable working hours (12hrs / 24hrs) \n – Salary expectations \n – Preferred role (Attendant / Nursing) \n – Education \n – Any certificate (if available) \n– experience in this field"
                        ]);

                        Log::info("Sales Lead Create: WhatsApp message created successfully for JobRequest", ['message_id' => $newWaMsg->id]);
                    } else {
                        Log::warning("Sales Lead Create: API returned success but status is not 'success' for JobRequest", ['response_data' => $responseData]);
                    }
                } else {
                    Log::error("Sales Lead Create: API request failed for JobRequest", [
                        'status_code' => $response->status(),
                        'response_body' => $response->body()
                    ]);
                }

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

                Log::info('Sales Lead Create: JobRequest created successfully', [
                    'job_request_id' => $job_request->id,
                    'lead_id' => $lead->id,
                    'contact_no' => $lead->contact_no,
                    'executive_id' => $job_request->executive_id
                ]);
            } else {
                $job_request->date_time = $lead->date;
                $job_request->save();

                Log::info('Sales Lead Create: JobRequest already exists, updated timestamp', [
                    'job_request_id' => $job_request->id,
                    'lead_id' => $lead->id,
                    'contact_no' => $lead->contact_no
                ]);
            }
        } else {
            Log::info('Sales Lead Create: JobRequest creation skipped', [
                'lead_id' => $lead->id,
                'query' => $request->input('query'),
                'query_expected' => 'job request',
                'condition_met' => $request->input('query') == 'job request'
            ]);
        }

        $number = $data['contact_no'];
        $execId = auth()->user()->id;
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

        return response()->json([
            'message' => 'Lead created successfully',
            'lead' => $lead
        ]);
    }

    public function show($id)
    {
        $lead = Lead::findOrFail($id);
        $services = Service::get();
        $locations = Location::get();

        // Return JSON for API requests
        if (request()->expectsJson()) {
            $lead->lead_code = $lead->formatted_id;
            return response()->json($lead);
        }

        return view('sales.leads.show', compact('lead', 'services', 'locations'));
    }
}
