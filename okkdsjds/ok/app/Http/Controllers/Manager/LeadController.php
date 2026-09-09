<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\JobRequest;
use App\Models\Location;
use App\Models\WhatsAppMessage;
use App\Facades\UserAssignment;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\WhatsappMsgGroup;
use App\Models\FavoriteChat;
use App\Models\Service;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;


class LeadController extends Controller
{
    public function index()
    {
        $auth_user = Auth::user();
        $executives = User::where('parent_id', $auth_user->id)->get();
        $locations = Location::get();
        $services = Service::get();

        return view('manager.leads.index', compact('executives', 'locations', 'services'));
    }

        public function getLeads(Request $request)
    {
        $auth_user = Auth::user();
        $users_ids = User::where('parent_id', $auth_user->id)->pluck('id');

        $leads = Lead::select(['leads.*',
        'leads.last_call_status',
        'leads.recording_url',
         'users.f_name as executive_name'])
            ->leftJoin('users', 'leads.executive', '=', 'users.id')
            ->whereIn('leads.executive', $users_ids);

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

    // Separate function for mobile app API
    public function getLeadsForApp(Request $request)
    {
        $auth_user = Auth::user();
        $users_ids = User::where('parent_id', $auth_user->id)->pluck('id');

        // If no child users, return empty array
        if ($users_ids->isEmpty()) {
            return response()->json([]);
        }

        $leads = Lead::select([
            'leads.*',
            'leads.last_call_status',
            'leads.recording_url',
            DB::raw("CONCAT(users.f_name, ' ', COALESCE(users.l_name, '')) as executive_name")
        ])
        ->leftJoin('users', 'leads.executive', '=', 'users.id')
        ->whereIn('leads.executive', $users_ids);

        // Apply filters if provided
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

        $leads = $leads->get();
        return response()->json($leads);
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
            'contact_type' => 'required|in:call,whatsapp,email',
            'contact_no' => 'required|string',
            'location' => 'required|string',
            'query_remarks' => 'nullable|string',
            'status_remarks' => 'nullable|string',
            'lead_source' => 'required|in:web,ivrs,whatsapp,manual',
            'query' => 'required|string',
            'status' => 'required|in:follow-up,future prospect,prospect,no response,price issue,duplicate,spam',
            'stage' => 'required|in:active,inactive,closed,profile required',
            'shift_type' => 'nullable|in:12hr,24hr,both',
            'future_prospect_date' => 'required_if:status,future prospect|date|nullable',
            'prospect_rate' => 'required_if:status,prospect|nullable',
            'executive' => 'required|exists:users,id'
        ]);

        $lead = Lead::findOrFail($id);

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
            'status_remarks',
            'executive'
        ]);

        if ($request->status !== 'future prospect') {
            $data['future_prospect_date'] = null;
        }
        if ($request->status !== 'prospect') {
            $data['prospect_rate'] = null;
        }

        $lead->update($data);

        // Handle JobRequest creation if query is job request (AFTER lead update)
        if($request->input('query') == 'job request'){
            Log::info('Manager Lead Update: Attempting to create JobRequest', [
                'lead_id' => $lead->id,
                'contact_no' => $lead->contact_no,
                'query' => $request->input('query'),
                'executive_id' => $lead->executive
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

                Log::info('Manager Lead Update: Location found for JobRequest', [
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

                Log::info("Manager Lead Update: Sending job_request_followup template for JobRequest", [
                    'job_request_id' => $job_request->id,
                    'contact_no' => $number,
                    'executive_id' => $execId
                ]);

                $response = \Illuminate\Support\Facades\Http::withHeaders([
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $authKey
                ])->post($url, $payload);

                if ($response->successful()) {
                    $responseData = $response->json();
                    Log::info("Manager Lead Update: API response received for JobRequest", [
                        'status_code' => $response->status(),
                        'response_body' => $response->body()
                    ]);

                    if (isset($responseData['status']) && $responseData['status'] === 'success') {
                        $currentTimestamp = \Carbon\Carbon::now();

                        $messageId = $number; // Default fallback
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
                            'body' => "*Hi $name* \n Thank you for your interest in joining Carelix Healthcare. \n To proceed, please share the following details: \n – Your current address \n – City \n – Comfortable working hours (12hrs / 24hrs) \n – Salary expectations \n – Preferred role (Attendant / Nursing) \n – Education \n – Any certificate (if available) \n– experience in this field"
                        ]);

                        Log::info("Manager Lead Update: WhatsApp message created successfully for JobRequest", ['message_id' => $newWaMsg->id]);
                    } else {
                        Log::warning("Manager Lead Update: API returned success but status is not 'success' for JobRequest", ['response_data' => $responseData]);
                    }
                } else {
                    Log::error("Manager Lead Update: API request failed for JobRequest", [
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

                Log::info('Manager Lead Update: JobRequest created successfully', [
                    'job_request_id' => $job_request->id,
                    'lead_id' => $lead->id,
                    'contact_no' => $lead->contact_no,
                    'executive_id' => $job_request->executive_id
                ]);
            } else {
                $job_request->date_time = $lead->date;
                $job_request->save();

                Log::info('Manager Lead Update: JobRequest already exists, updated timestamp', [
                    'job_request_id' => $job_request->id,
                    'lead_id' => $lead->id,
                    'contact_no' => $lead->contact_no
                ]);
            }
        } else {
            Log::info('Manager Lead Update: JobRequest creation skipped', [
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
            'prospect_rate' => 'nullable',
            'executive' => 'nullable|exists:users,id'
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
            'status_remarks',
            'executive'
        ]);

        if ($request->status !== 'future prospect') {
            $data['future_prospect_date'] = null;
        }
        if ($request->status !== 'prospect') {
            $data['prospect_rate'] = null;
        }

        $lead = Lead::create(array_merge(
            $data,
            [
                'date' => now(),
                'lead_source' => 'manual'
            ]
        ));

        // Handle JobRequest creation if query is job request
        if ($data['query'] == 'job request') {
            Log::info('Manager Lead Create: Attempting to create JobRequest', [
                'lead_id' => $lead->id,
                'contact_no' => $lead->contact_no,
                'query' => $data['query'],
                'executive_id' => $lead->executive
            ]);

            $job_request = JobRequest::where('contact_no', $lead->contact_no)->first();
            if (!$job_request) {
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

                Log::info('Manager Lead Create: Location found for JobRequest', [
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

                Log::info("Manager Lead Create: Sending job_request_followup template for JobRequest", [
                    'job_request_id' => $job_request->id,
                    'contact_no' => $number,
                    'executive_id' => $execId
                ]);

                $response = \Illuminate\Support\Facades\Http::withHeaders([
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $authKey
                ])->post($url, $payload);

                if ($response->successful()) {
                    $responseData = $response->json();
                    Log::info("Manager Lead Create: API response received for JobRequest", [
                        'status_code' => $response->status(),
                        'response_body' => $response->body()
                    ]);

                    if (isset($responseData['status']) && $responseData['status'] === 'success') {
                        $currentTimestamp = \Carbon\Carbon::now();

                        $messageId = $number; // Default fallback
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
                            'body' => "*Hi $name* \n Thank you for your interest in joining Carelix Healthcare. \n To proceed, please share the following details: \n – Your current address \n – City \n – Comfortable working hours (12hrs / 24hrs) \n – Salary expectations \n – Preferred role (Attendant / Nursing) \n – Education \n – Any certificate (if available) \n– experience in this field"
                        ]);

                        Log::info("Manager Lead Create: WhatsApp message created successfully for JobRequest", ['message_id' => $newWaMsg->id]);
                    } else {
                        Log::warning("Manager Lead Create: API returned success but status is not 'success' for JobRequest", ['response_data' => $responseData]);
                    }
                } else {
                    Log::error("Manager Lead Create: API request failed for JobRequest", [
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

                Log::info('Manager Lead Create: JobRequest created successfully', [
                    'job_request_id' => $job_request->id,
                    'lead_id' => $lead->id,
                    'contact_no' => $lead->contact_no,
                    'executive_id' => $job_request->executive_id
                ]);
            } else {
                $job_request->date_time = $lead->date;
                $job_request->save();

                Log::info('Manager Lead Create: JobRequest already exists, updated timestamp', [
                    'job_request_id' => $job_request->id,
                    'lead_id' => $lead->id,
                    'contact_no' => $lead->contact_no
                ]);
            }
        } else {
            Log::info('Manager Lead Create: JobRequest creation skipped', [
                'lead_id' => $lead->id,
                'query' => $data['query'],
                'query_expected' => 'job request',
                'condition_met' => $data['query'] == 'job request'
            ]);
        }

        $number = $data['contact_no'];
        $execId = $data['executive'];
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

        // If API request, return JSON data
        if (request()->expectsJson()) {
            return response()->json($lead);
        }

        return view('manager.leads.show', compact('lead', 'services', 'locations'));
    }

    public function getExecutives()
    {
        $auth_user = Auth::user();
        $executives = User::where('parent_id', $auth_user->id)
            ->select('id', 'f_name', 'l_name')
            ->get();

        return response()->json($executives);
    }

    public function getLocations()
    {
        $locations = Location::select('id', 'name')->get();
        return response()->json($locations);
    }

}
