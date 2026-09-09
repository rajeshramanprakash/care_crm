<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Concerns\HandlesLeadStatusRemarks;
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
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Services\ApiRelayUrlResolver;
use App\Events\FutureProspectReminderDue;
use Carbon\Carbon;




class LeadController extends Controller
{
    use HandlesLeadStatusRemarks;

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
        $leads = Lead::select([
            'leads.id',
            'leads.date',
            'leads.executive',
            'leads.customer_name',
            'leads.patient_name',
            'leads.patient_gender',
            'leads.age',
            'leads.contact_type',
            'leads.contact_no',
            'leads.location',
            'leads.lead_source',
            'leads.query',
            'leads.status',
            'leads.follow_up_date',
            'leads.stage',
            'leads.inactive_stage_remark',
            'leads.shift_type',
            'leads.future_prospect_date',
            'leads.prospect_rate',
            'leads.last_call_status',
            'leads.recording_url',
            'leads.query_remarks',
            'leads.status_remarks',
            'users.f_name as executive_name',
            Lead::computedLeadSourceSelect(),
        ])
            ->leftJoin('users', 'leads.executive', '=', 'users.id')
            ->leftJoin('b2b_leads as bl', 'bl.lead_id', '=', 'leads.id')
            ->leftJoin('b2b_users as bu', 'bu.id', '=', 'bl.b2b_user_id')
            ->where('leads.executive', $auth_user->id);

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

        // Return JSON for API requests (same order as CRM: newest first)
        if ($request->expectsJson()) {
            $leads = $leads->orderBy('leads.id', 'desc')->get();

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
                    'age' => $lead->age,
                    'contact_type' => $lead->contact_type,
                    'contact_no' => $lead->contact_no,
                    'last_call_status' => $lead->last_call_status,
                    'recording_url' => $lead->recording_url,
                    'location' => $lead->location,
                    'lead_source' => $lead->computed_lead_source ?? $lead->lead_source,
                    'query' => $lead->query,
                    'query_remarks' => $lead->query_remarks,
                    'status' => $lead->status ?? '-',
                    'status_remarks' => $lead->status_remarks,
                    'stage' => $lead->stage ?? 'active',
                    'inactive_stage_remark' => $lead->inactive_stage_remark,
                    'shift_type' => $lead->shift_type,
                    // App/JSON clients need machine-parseable wall times (same as CRM DB), not display-only strings.
                    'follow_up_date' => $lead->follow_up_date?->format('Y-m-d H:i:s'),
                    'future_prospect_date' => $lead->future_prospect_date?->format('Y-m-d H:i:s'),
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
                    ? \Carbon\Carbon::parse($lead->future_prospect_date)->format('d-m-Y H:i')
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
            ->editColumn('lead_source', function ($lead) {
                return $lead->computed_lead_source ?? $lead->lead_source ?? '-';
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
        $lead = Lead::query()
            ->select('leads.*')
            ->leftJoin('b2b_leads as bl', 'bl.lead_id', '=', 'leads.id')
            ->leftJoin('b2b_users as bu', 'bu.id', '=', 'bl.b2b_user_id')
            ->where('leads.id', $id)
            ->addSelect([Lead::computedLeadSourceSelect()])
            ->firstOrFail();

        $lead->lead_source = $lead->computed_lead_source ?? $lead->lead_source;
        $lead->makeHidden(['computed_lead_source']);
        $lead->lead_code = $lead->formatted_id;

        return response()->json($this->appendLeadStatusRemarksToResponse($lead));
    }

    public function update(Request $request, $id)
    {
        $lead = Lead::findOrFail($id);
        $effectiveStatus = $request->filled('status') ? (string) $request->input('status') : (string) ($lead->status ?? '');
        $effectiveStage = $request->filled('stage') ? (string) $request->input('stage') : (string) ($lead->stage ?? '');

        // Normalize request for API/app: empty strings to null so validation passes
        $merge = [];
        if ($request->has('age') && $request->age === '') {
            $merge['age'] = null;
        } elseif ($request->has('age') && is_numeric($request->age)) {
            $merge['age'] = (int) $request->age;
        }
        if ($request->has('future_prospect_date') && $request->future_prospect_date === '') {
            $merge['future_prospect_date'] = null;
        }
        if ($request->has('follow_up_date') && $request->follow_up_date === '') {
            $merge['follow_up_date'] = null;
        }
        if ($request->has('prospect_rate')) {
            if ($effectiveStatus === 'prospect' && ($request->prospect_rate === '' || $request->prospect_rate === null)) {
                $merge['prospect_rate'] = 0;
            } elseif ($effectiveStatus !== 'prospect') {
                $merge['prospect_rate'] = null;
            } elseif (is_numeric($request->prospect_rate)) {
                $merge['prospect_rate'] = (float) $request->prospect_rate;
            }
        }
        if (!empty($merge)) {
            $request->merge($merge);
        }
        if (! $request->filled('executive')) {
            $request->merge(['executive' => (int) ($lead->executive ?? auth()->id())]);
        }
        if (! $request->filled('contact_type') && ! empty($lead->contact_type)) {
            $request->merge(['contact_type' => (string) $lead->contact_type]);
        }
        if (! $request->filled('contact_no') && ! empty($lead->contact_no)) {
            $request->merge(['contact_no' => (string) $lead->contact_no]);
        }
        if (! $request->filled('lead_source') && ! empty($lead->lead_source)) {
            $request->merge(['lead_source' => (string) $lead->lead_source]);
        }

        $request->validate([
            'customer_name' => 'nullable|string',
            'patient_name' => 'nullable|string',
            'patient_gender' => 'nullable|in:male,female,other',
            'age' => 'nullable|integer|min:0|max:150',
            'contact_type' => 'nullable|in:call,whatsapp,email',
            'contact_no' => 'required|string',
            'location' => 'nullable|string',
            'query_remarks' => 'nullable|string',
            'new_status_remark' => 'nullable|string',
            'lead_source' => 'required|string|max:255',
            'query' => 'nullable|string',
            'status' => 'nullable|in:follow-up,future prospect,prospect,no response,price issue,duplicate,spam',
            'stage' => 'nullable|in:active,inactive,closed,profile required',
            'inactive_stage_remark' => 'nullable|string',
            'shift_type' => 'nullable|in:12hr,24hr,both',
            'follow_up_date' => 'required_if:status,follow-up|date|nullable',
            'future_prospect_date' => 'required_if:status,future prospect|date|nullable',
            'prospect_rate' => 'required_if:status,prospect|nullable|numeric',
            'executive' => 'required|exists:users,id',
        ]);

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
            'inactive_stage_remark',
            'shift_type',
            'follow_up_date',
            'future_prospect_date',
            'prospect_rate',
            'query_remarks',
        ]);

        if ($effectiveStatus !== 'follow-up') {
            $data['follow_up_date'] = null;
        } elseif (! empty($data['follow_up_date'])) {
            $parsed = Lead::parseFutureProspectDateInput($data['follow_up_date']);
            $data['follow_up_date'] = $parsed?->format('Y-m-d H:i:s');
        }
        if ($effectiveStatus !== 'future prospect') {
            $data['future_prospect_date'] = null;
        } elseif (! empty($data['future_prospect_date'])) {
            $parsed = Lead::parseFutureProspectDateInput($data['future_prospect_date']);
            $data['future_prospect_date'] = $parsed?->format('Y-m-d H:i:s');
        }
        if ($effectiveStatus !== 'prospect') {
            $data['prospect_rate'] = null;
        }
        if ($effectiveStage !== 'inactive') {
            $data['inactive_stage_remark'] = null;
        }

        // Hidden edit field can be empty; do not wipe stored crm/app/web source on save
        if (array_key_exists('lead_source', $data) && trim((string) ($data['lead_source'] ?? '')) === '') {
            unset($data['lead_source']);
        }

        if ($err = $this->validateSalesManagerNewStatusRemark($lead, $request)) {
            return $err;
        }

        $lead->update($data);
        $this->processNewStatusRemark($lead, $request, $effectiveStatus);
        $lead->refresh();

        // Handle OperationLead creation if status is prospect (AFTER lead update)
        if($effectiveStatus === 'prospect' && $effectiveStage == 'closed'){
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


                $operation_lead->date_time = $lead->date;
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
            $url = ApiRelayUrlResolver::resolve("https://rengage.mcube.com/api/wpbox/sendtemplatemessage");
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
                $url = ApiRelayUrlResolver::resolve("https://rengage.mcube.com/api/wpbox/sendtemplatemessage");
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
        if (! $request->filled('executive')) {
            $request->merge(['executive' => auth()->id()]);
        }
        $request->validate([
            'customer_name' => 'nullable|string',
            'patient_name' => 'nullable|string',
            'patient_gender' => 'nullable|in:male,female,other',
            'age' => 'nullable|integer|min:0|max:150',
            'contact_type' => 'nullable|in:call,whatsapp,email',
            'contact_no' => 'required|string',
            'location' => 'nullable|string',
            'lead_source' => 'required|string|max:255',
            'query_remarks' => 'nullable|string',
            'new_status_remark' => 'nullable|string',
            'query' => 'nullable|string',
            'status' => 'nullable|in:follow-up,future prospect,prospect,no response,price issue,duplicate,spam',
            'stage' => 'nullable|in:active,inactive,closed,profile required',
            'inactive_stage_remark' => 'nullable|string',
            'shift_type' => 'nullable|in:12hr,24hr,both',
            'follow_up_date' => 'required_if:status,follow-up|nullable|date',
            'future_prospect_date' => 'required_if:status,future prospect|nullable|date',
            'prospect_rate' => 'nullable',
            'executive' => 'required|exists:users,id',
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
            'inactive_stage_remark',
            'shift_type',
            'follow_up_date',
            'future_prospect_date',
            'prospect_rate',
            'query_remarks',
            'executive',
        ]);

        if ($request->status !== 'follow-up') {
            $data['follow_up_date'] = null;
        } elseif (! empty($data['follow_up_date'])) {
            $parsed = Lead::parseFutureProspectDateInput($data['follow_up_date']);
            $data['follow_up_date'] = $parsed?->format('Y-m-d H:i:s');
        }
        if ($request->status !== 'future prospect') {
            $data['future_prospect_date'] = null;
        } elseif (! empty($data['future_prospect_date'])) {
            $parsed = Lead::parseFutureProspectDateInput($data['future_prospect_date']);
            $data['future_prospect_date'] = $parsed?->format('Y-m-d H:i:s');
        }
        if ($request->status !== 'prospect') {
            $data['prospect_rate'] = null;
        }
        if ($request->stage !== 'inactive') {
            $data['inactive_stage_remark'] = null;
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
        if ($err = $this->validateSalesManagerNewStatusRemark($lead, $request)) {
            $lead->delete();

            return $err;
        }
        $this->processNewStatusRemark($lead, $request, $request->status);
        $lead->refresh();

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
                $url = ApiRelayUrlResolver::resolve("https://rengage.mcube.com/api/wpbox/sendtemplatemessage");
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
        $lead = Lead::query()
            ->select('leads.*')
            ->leftJoin('b2b_leads as bl', 'bl.lead_id', '=', 'leads.id')
            ->leftJoin('b2b_users as bu', 'bu.id', '=', 'bl.b2b_user_id')
            ->where('leads.id', $id)
            ->addSelect([Lead::computedLeadSourceSelect()])
            ->firstOrFail();

        $services = Service::get();
        $locations = Location::get();

        $lead->lead_source = $lead->computed_lead_source ?? $lead->lead_source;
        $lead->makeHidden(['computed_lead_source']);

        // Return JSON for API requests
        if (request()->expectsJson()) {
            $lead->lead_code = $lead->formatted_id;

            return response()->json($this->appendLeadStatusRemarksToResponse($lead));
        }

        $lead->load('statusRemarks');

        return view('sales.leads.show', compact('lead', 'services', 'locations'));
    }

    /**
     * When QUEUE_CONNECTION=sync, delayed reminder jobs never run at the future contact time.
     * While a sales user has the app open, we periodically run the same dispatch as the scheduler
     * so Reverb can push the modal when the datetime is due.
     */
    public function runFutureProspectReminderCheck()
    {
        $userId = Auth::id();
        if (! $userId) {
            return response()->json(['ran' => true, 'due' => null, 'next_due_ts' => null, 'server_now_ts' => Carbon::now()->timestamp]);
        }

        // Dispatch is for CRM/Reverb only; the JSON `due` payload is built from the DB below.
        // Running Artisan synchronously here often exceeds mobile client timeouts (10–30s).
        if (Cache::add('sales_fp_dispatch:'.$userId, true, now()->addSeconds(55))) {
            dispatch(function () use ($userId) {
                try {
                    Artisan::call('leads:dispatch-future-prospect-reminders');
                } catch (\Throwable $e) {
                    Log::warning('runFutureProspectReminderCheck: dispatch command failed', [
                        'user_id' => $userId,
                        'message' => $e->getMessage(),
                    ]);
                }
            })->afterResponse();
        }

        $now = Carbon::now();

        // Reminder payload: same calendar day as the scheduled slot only (see Lead::isScheduledSalesContactReminderDueThisAppDay).
        // future_prospect_reminder_at still dedupes queue broadcasts across the day where applicable.
        $lead = Lead::query()
            ->where('executive', $userId)
            ->where(function ($q) use ($now) {
                $q->where(function ($q1) use ($now) {
                    $q1->whereRaw('LOWER(TRIM(status)) = ?', ['future prospect'])
                        ->whereNotNull('future_prospect_date')
                        ->where('future_prospect_date', '<=', $now);
                })->orWhere(function ($q2) use ($now) {
                    $q2->whereRaw('LOWER(TRIM(status)) = ?', ['follow-up'])
                        ->whereNotNull('follow_up_date')
                        ->where('follow_up_date', '<=', $now);
                });
            })
            // When several leads are overdue, prefer the most recently scheduled slot (DESC).
            // Otherwise the oldest backlog (e.g. 10:02) always wins and a new 10:05 reminder never surfaces first in the app.
            ->orderByRaw('CASE WHEN LOWER(TRIM(status)) = ? THEN follow_up_date ELSE future_prospect_date END desc', ['follow-up'])
            ->orderBy('updated_at', 'desc')
            ->orderBy('id', 'desc')
            ->first();

        if ($lead && ! $this->salesLeadScheduledContactIsDue($lead, $now)) {
            $lead = null;
        }

        $payload = null;
        if ($lead) {
            $payload = (new FutureProspectReminderDue($lead))->broadcastWith();
        }

        // Also return the next scheduled (future) reminder time so the UI can set an exact timer (no refresh needed).
        $nextLead = Lead::query()
            ->where('executive', $userId)
            ->where(function ($q) {
                $q->where(function ($q1) {
                    $q1->whereRaw('LOWER(TRIM(status)) = ?', ['future prospect'])->whereNotNull('future_prospect_date');
                })->orWhere(function ($q2) {
                    $q2->whereRaw('LOWER(TRIM(status)) = ?', ['follow-up'])->whereNotNull('follow_up_date');
                });
            })
            ->whereRaw('CASE WHEN LOWER(TRIM(status)) = ? THEN follow_up_date ELSE future_prospect_date END >= ?', ['follow-up', $now])
            ->orderByRaw('CASE WHEN LOWER(TRIM(status)) = ? THEN follow_up_date ELSE future_prospect_date END asc', ['follow-up'])
            ->first();

        $nextDue = null;
        if ($nextLead) {
            $nextDue = $nextLead->status === 'follow-up' ? $nextLead->follow_up_date : $nextLead->future_prospect_date;
        }

        return response()->json([
            'ran' => true,
            'due' => $payload,
            'next_due_ts' => $nextDue?->timestamp,
            'server_now_ts' => $now->timestamp,
        ]);
    }

    /**
     * Callback reminder only on the calendar day of the scheduled slot (app timezone),
     * and not before the scheduled wall time.
     */
    private function salesLeadScheduledContactIsDue(Lead $lead, Carbon $now): bool
    {
        return $lead->isScheduledSalesContactReminderDueThisAppDay($now);
    }
}
