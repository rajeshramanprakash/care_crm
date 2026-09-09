<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\User;
use App\Models\WhatsappMsgGroup;
use App\Models\Location;
use App\Models\Service;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\DB;

class LeadController extends Controller
{
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
                DB::raw("CONCAT(users.f_name, ' ', users.l_name) as executive_name")
            ])->leftJoin('users', 'leads.executive', '=', 'users.id')
            ->orderBy('leads.id', 'desc')
            ->get();

            return response()->json($leads);
        }

        $locations = Location::get();
        $executives = User::whereRaw('FIND_IN_SET(role_id, "2")')->get();
        $services = Service::get();
        return view('admin.leads.index', compact('executives', 'locations', 'services'));
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
            'users.f_name as executive_name'
        ])->leftJoin('users', 'leads.executive', '=', 'users.id');

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

    public function edit($id)
    {
        $lead = Lead::findOrFail($id);
        $lead->lead_code = $lead->formatted_id;
        return response()->json($lead);
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
            'lead_source' => 'nullable|in:web,ivrs,whatsapp,manual',
            'query' => 'nullable|string',
            'query_remarks' => 'nullable|string',
            'status' => 'nullable|in:follow-up,future prospect,prospect,no response,price issue,duplicate,spam',
            'stage' => 'nullable|in:active,inactive,closed,profile required',
            'shift_type' => 'nullable|in:12hr,24hr,both',
            'future_prospect_date' => 'required_if:status,future prospect|date|nullable',
            'prospect_rate' => 'required_if:status,prospect|nullable'
        ]);

        $lead = Lead::findOrFail($id);

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
            'stage',
            'shift_type',
            'future_prospect_date',
            'prospect_rate'
        ]);

        if ($request->status !== 'future prospect') {
            $data['future_prospect_date'] = null;
        }
        if ($request->status !== 'prospect') {
            $data['prospect_rate'] = null;
        }

        $lead->update($data);

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
            'lead_source' => 'nullable|in:web,ivrs,whatsapp,manual',
            'query' => 'nullable|string',
            'query_remarks' => 'nullable|string',
            'status' => 'nullable|in:follow-up,future prospect,prospect,no response,price issue,duplicate,spam',
            'stage' => 'nullable|in:active,inactive,closed,profile required',
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
            'query_remarks',
            'status',
            'stage',
            'future_prospect_date',
            'prospect_rate',
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
                'lead_source' => 'manual',
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
            ->findOrFail($id);

        if (request()->expectsJson()) {
            $lead->lead_code = $lead->formatted_id;
            return response()->json($lead);
        }

        $lead_code = $lead->formatted_id;
        return view('sales.leads.show', compact('lead', 'lead_code'));
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
        $locations = Location::select('id', 'name')->get();

        return response()->json($locations);
    }
}
