<?php

namespace App\Http\Controllers\SubAdmin;

use App\Http\Controllers\Concerns\HandlesLeadStatusRemarks;
use App\Http\Controllers\Controller;
use App\Models\LeadStatusRemark;
use App\Facades\UserAssignment;
use App\Models\Lead;
use App\Models\User;
use App\Models\WhatsappMsgGroup;
use App\Models\Location;
use App\Models\Service;
use App\Models\OperationLead;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SubAdminLeadController extends Controller
{
    use HandlesLeadStatusRemarks;

    public function __construct()
    {
        $this->middleware('can:view_leads')->only(['index', 'getLeads', 'show', 'getExecutives', 'getLocations']);
        $this->middleware('can:create_leads')->only(['store', 'create']);
        $this->middleware('can:edit_leads')->only(['edit', 'update', 'updateStatusRemark']);
        $this->middleware('can:delete_leads')->only(['destroy']);
    }

    public function index()
    {
        // Check if this is an API request
        if (request()->expectsJson()) {
            $leads = Lead::select([
                'leads.id',
                'leads.date',
                'leads.executive',
                'leads.customer_name',
                'leads.patient_name',
                'leads.patient_gender',
                'leads.contact_type',
                'leads.contact_no',
                'leads.location',
                'leads.lead_source',
                'leads.query',
                'leads.status',
                'leads.future_prospect_date',
                'leads.prospect_rate',
                'leads.stage',
                'leads.last_call_status',
                'leads.recording_url',
                DB::raw("CONCAT(users.f_name, ' ', users.l_name) as executive_name"),
                Lead::computedLeadSourceSelect()
            ])->leftJoin('users', 'leads.executive', '=', 'users.id')
            ->leftJoin('b2b_leads as bl', 'bl.lead_id', '=', 'leads.id')
            ->leftJoin('b2b_users as bu', 'bu.id', '=', 'bl.b2b_user_id')
            ->orderBy('leads.id', 'desc')
            ->get()
            ->map(function ($lead) {
                $lead->lead_source = $lead->computed_lead_source ?? $lead->lead_source;
                return $lead;
            });

            return response()->json($leads);
        }

        $locations = Location::get();
        $executives = User::whereRaw('FIND_IN_SET(role_id, "2")')->get();
        $services = Service::get();
        return view('subadmin.leads.index', compact('executives', 'locations', 'services'));
    }

    public function getLeads(Request $request)
    {
        $leads = Lead::select([
            'leads.id',
            'leads.date',
            'leads.executive',
            'leads.customer_name',
            'leads.patient_name',
            'leads.patient_gender',
            'leads.contact_type',
            'leads.contact_no',
            'leads.location',
            'leads.lead_source',
            'leads.query',
            'leads.status',
            'leads.future_prospect_date',
            'leads.prospect_rate',
            'leads.stage',
            'leads.last_call_status',
            'leads.recording_url',
            'users.f_name as executive_name',
            Lead::computedLeadSourceSelect()
        ])->leftJoin('users', 'leads.executive', '=', 'users.id');
        $leads->leftJoin('b2b_leads as bl', 'bl.lead_id', '=', 'leads.id');
        $leads->leftJoin('b2b_users as bu', 'bu.id', '=', 'bl.b2b_user_id');

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

        $rawLeadSource = trim((string) ($lead->getAttributes()['lead_source'] ?? ''));
        $lead->lead_source_display = $lead->computed_lead_source ?? ($rawLeadSource !== '' ? $rawLeadSource : null);
        $lead->lead_source_raw = $rawLeadSource;
        // Form dropdown uses stored DB value (web/ivrs/…), not computed b2b label.
        $lead->lead_source = $rawLeadSource;
        $lead->makeHidden(['computed_lead_source']);
        $lead->lead_code = $lead->formatted_id;

        return response()->json($this->appendLeadStatusRemarksToResponse($lead, true));
    }

    public function updateStatusRemark(Request $request, $leadId, $remarkId)
    {
        $request->validate(['remark' => 'required|string']);

        $lead = Lead::findOrFail($leadId);
        $remark = LeadStatusRemark::query()
            ->where('lead_id', $lead->id)
            ->whereKey($remarkId)
            ->firstOrFail();

        $remark->update(['remark' => trim($request->remark)]);
        $this->syncLeadLatestStatusRemark($lead->fresh());

        return response()->json([
            'message' => 'Remark updated',
            'remark' => [
                'id' => $remark->id,
                'remark' => $remark->remark,
                'created_by_name' => $remark->created_by_name,
                'created_at' => $remark->created_at?->format('d M Y, h:i A'),
            ],
        ]);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'executive' => 'required',
            'customer_name' => 'required|string',
            'patient_name' => 'nullable|string',
            'patient_gender' => 'nullable|in:male,female,other',
            'age' => 'nullable|integer|min:0|max:150',
            'contact_type' => 'nullable|in:call,whatsapp,email',
            'contact_no' => 'nullable|string',
            'location' => 'nullable|string',
            'lead_source' => 'nullable|string|max:255',
            'query' => 'nullable|string',
            'query_remarks' => 'nullable|string',
            'status' => 'nullable|in:follow-up,future prospect,prospect,no response,price issue,duplicate,spam',
            'follow_up_date' => 'required_if:status,follow-up|date|nullable',
            'stage' => 'nullable|in:active,inactive,closed,profile required',
            'inactive_stage_remark' => 'nullable|string',
            'shift_type' => 'nullable|in:12hr,24hr,both',
            'future_prospect_date' => 'required_if:status,future prospect|date|nullable',
            'prospect_rate' => 'required_if:status,prospect|nullable',
            'new_status_remark' => 'nullable|string',
            'remark_updates' => 'nullable|array',
            'remark_updates.*' => 'nullable|string',
        ]);

        $lead = Lead::findOrFail($id);
        $effectiveStatus = $request->filled('status') ? (string) $request->input('status') : (string) ($lead->status ?? '');
        $effectiveStage = $request->filled('stage') ? (string) $request->input('stage') : (string) ($lead->stage ?? '');

        // Set future_prospect_date to null if status is not future prospect
        $data = $request->only([
            'executive',
            'customer_name',
            'patient_name',
            'patient_gender',
            'age',
            'contact_type',
            'contact_no',
            'location',
            'lead_source',
            'query',
            'query_remarks',
            'status',
            'follow_up_date',
            'stage',
            'inactive_stage_remark',
            'shift_type',
            'future_prospect_date',
            'prospect_rate'
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

        $lead->update($data);
        $this->processAdminStatusRemarkUpdates($lead, $request);
        $this->processNewStatusRemark($lead, $request, $effectiveStatus);
        $lead->refresh();

        // Handle OperationLead creation/update when status is prospect and stage is closed (same as Sales/Manager)
        if ($effectiveStatus === 'prospect' && $effectiveStage == 'closed') {
            $location = Location::where('name', $lead->location)->first();
            $getUser = UserAssignment::getAssigningUser(4, $location ? $location->id : null);
            $operation_lead = OperationLead::where('contact_no', $lead->contact_no)->first();

            if (!$operation_lead) {
                $operation_lead = new OperationLead();
                $operation_lead->executive = $getUser ? $getUser->id : $lead->executive;
                if ($location) {
                    Log::info('Admin Lead Update: Location found for OperationLead', [
                        'location_id' => $location->id,
                        'location_name' => $location->name,
                        'assigned_executive' => $operation_lead->executive
                    ]);
                } else {
                    Log::warning('Admin Lead Update: Location not found, using fallback assignment', [
                        'location_name' => $lead->location,
                        'assigned_executive' => $operation_lead->executive
                    ]);
                }
                $operation_lead->customer_name = $lead->customer_name;
                $operation_lead->contact_type = $lead->contact_type;
                $operation_lead->contact_no = $lead->contact_no;
                $operation_lead->address = $lead->address ?? null;
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
                $execId = $operation_lead->executive;
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
                        'executive_ids' => (string) $execId,
                    ]);
                }

                Log::info('Admin Lead Update: OperationLead created successfully', [
                    'operation_lead_id' => $operation_lead->id,
                    'lead_id' => $lead->id,
                    'contact_no' => $lead->contact_no,
                    'executive' => $operation_lead->executive
                ]);
            } else {
                $operation_lead->date_time = $lead->date;
                $operation_lead->save();

                Log::info('Admin Lead Update: OperationLead already exists, updated timestamp', [
                    'operation_lead_id' => $operation_lead->id,
                    'lead_id' => $lead->id,
                    'contact_no' => $lead->contact_no
                ]);
            }
        }

        if (request()->expectsJson()) {
            return response()->json([
                'message' => 'Lead updated successfully',
                'lead' => $lead
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
            'lead_source' => 'nullable|string|max:255',
            'query' => 'nullable|string',
            'query_remarks' => 'nullable|string',
            'status' => 'nullable|in:follow-up,future prospect,prospect,no response,price issue,duplicate,spam',
            'stage' => 'nullable|in:active,inactive,closed,profile required',
            'inactive_stage_remark' => 'nullable|string',
            'follow_up_date' => 'required_if:status,follow-up|nullable|date',
            'future_prospect_date' => 'required_if:status,future prospect|nullable|date',
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
            'query_remarks',
            'status',
            'follow_up_date',
            'stage',
            'inactive_stage_remark',
            'future_prospect_date',
            'prospect_rate',
            'executive'
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

        $lead = Lead::create(array_merge(
            $data,
            [
                'date' => now(),
                'lead_source' => $request->filled('lead_source')
                    ? (string) $request->input('lead_source')
                    : 'manual',
            ]
        ));

        // WhatsApp group logic: add executive to group for this number, or create if not exists
        if ($data['contact_no']) {
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
        }

        if (request()->expectsJson()) {
            return response()->json([
                'message' => 'Lead created successfully',
                'lead' => $lead
            ]);
        }

        return response()->json([
            'message' => 'Lead created successfully',
            'lead' => $lead
        ]);
    }

    public function show($id)
    {
        $lead = Lead::select(
                'leads.*',
                'users.f_name as executive_name'
            )
            ->leftJoin('users', 'leads.executive', '=', 'users.id')
            ->leftJoin('b2b_leads as bl', 'bl.lead_id', '=', 'leads.id')
            ->leftJoin('b2b_users as bu', 'bu.id', '=', 'bl.b2b_user_id')
            ->addSelect([Lead::computedLeadSourceSelect()])
            ->where('leads.id', $id)
            ->firstOrFail();

        $lead->lead_source = $lead->computed_lead_source ?? $lead->lead_source;
        $lead->makeHidden(['computed_lead_source']);

        if (request()->expectsJson()) {
            $lead->lead_code = $lead->formatted_id;

            return response()->json($this->appendLeadStatusRemarksToResponse($lead, true));
        }

        $lead->load('statusRemarks');
        $lead_code = $lead->formatted_id;

        return view('subadmin.leads.show', compact('lead', 'lead_code'));
    }

    public function destroy($id)
    {
        try {
            $lead = Lead::findOrFail($id);
            $lead->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Lead deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete lead: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getExecutives()
    {
        $executives = User::whereRaw('FIND_IN_SET(role_id, "2")')
            ->select('id', 'f_name', 'l_name')
            ->get();

        return response()->json($executives);
    }

    public function getLocations()
    {
        $locations = Location::query()
            ->select('id', 'name', 'state')
            ->orderBy('name')
            ->get()
            ->map(fn (Location $location) => [
                'id' => $location->id,
                'name' => $location->name,
                'state' => $location->state,
                'display_label' => $location->display_label,
            ]);

        return response()->json($locations);
    }
}
