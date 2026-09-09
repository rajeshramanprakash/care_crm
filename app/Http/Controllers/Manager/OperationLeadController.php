<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\OperationLead;
use App\Models\WhatsappMsgGroup;
use Yajra\DataTables\Facades\DataTables;

class OperationLeadController extends Controller
{
    public function index()
    {
        return view('manager.operation_leads.index');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'date_time' => 'nullable|date',
            'executive' => 'nullable|string',
            'customer_name' => 'nullable|string',
            'contact_no' => 'required|string',
            'address' => 'nullable|string',
            'location' => 'nullable|string',
            'query' => 'nullable|in:attendant required,nurse required,on call nurse,medicine required,physiotherapy,doctor',
            'status' => 'nullable|in:prospect,followup,no response,price issue,duplicate,job request,spam,closed',
            'patient_name' => 'nullable|string',
            'age' => 'nullable|integer',
            'quoted_rate' => 'nullable|numeric',
            'closed_rate' => 'nullable|numeric',
            'vendor_name' => 'nullable|string',
            'staff_name' => 'nullable|string',
            'vendor_closed_rate' => 'nullable|numeric',
            'deployment_date_time' => 'nullable|date',
            'deployment_status' => 'nullable|string',
            'payment_received' => 'nullable|numeric',
            'outstanding_payment' => 'nullable|numeric',
            'received_date_time' => 'nullable|date',
            'screenshot' => 'nullable|image',
            'invoice' => 'nullable|string',
        ]);

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

        if ($request->hasFile('screenshot')) {
            $validated['screenshot'] = $request->file('screenshot')->store('operation_leads/screenshots', 'public');
        }

        // Generate lead_id
        $latestLead = \App\Models\OperationLead::orderBy('id', 'desc')->first();
        $nextNumber = $latestLead ? $latestLead->id + 1 : 1;
        $validated['lead_id'] = 'CHO' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);

        // Set current date/time if not provided
        if (empty($validated['date_time'])) {
            $validated['date_time'] = now();
        }
        
        $lead = \App\Models\OperationLead::create($validated);

        return response()->json(['message' => 'Operation Lead added successfully', 'lead' => $lead]);
    }

    public function getLeads()
    {
        $leads = OperationLead::select('*');
        return DataTables::of($leads)
            ->addColumn('serial_no', function($lead) { return $lead->id; })
            ->editColumn('screenshot', function($lead) {
                if ($lead->screenshot) {
                    return '<img src="' . asset('storage/' . $lead->screenshot) . '" alt="Screenshot" style="max-width:60px;max-height:60px;">';
                }
                return '';
            })
            ->addColumn('action', function($lead) {
                return '
                    <div class="action-dropdown">
                        <button class="action-dropdown-btn" onclick="event.stopPropagation(); toggleDropdown(this)">
                            <i class="fas fa-ellipsis-v"></i>
                        </button>
                        <div class="action-dropdown-menu">
                            <button class="edit-btn" data-id="'.$lead->id.'"><i class="fas fa-pen icon-edit"></i> Edit</button>
                            <button class="view-btn" data-id="'.$lead->id.'"><i class="fas fa-eye icon-view"></i> View</button>
                            <button class="delete-btn" data-id="'.$lead->id.'"><i class="fas fa-trash icon-delete"></i> Delete</button>
                        </div>
                    </div>';
            })
            ->rawColumns(['screenshot','action'])
            ->make(true);
    }

    public function show($id)
    {
        $lead = \App\Models\OperationLead::findOrFail($id);
        return response()->json($lead);
    }

    public function edit($id)
    {
        $lead = \App\Models\OperationLead::findOrFail($id);
        return response()->json($lead);
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'date_time' => 'required|date',
            'executive' => 'required|string',
            'customer_name' => 'required|string',
            'contact_no' => 'required|string',
            'address' => 'nullable|string',
            'location' => 'nullable|string',
            'query' => 'required|in:attendant required,nurse required,on call nurse,medicine required,physiotherapy,doctor',
            'status' => 'required|in:prospect,followup,no response,price issue,duplicate,job request,spam,closed',
            'patient_name' => 'nullable|string',
            'age' => 'nullable|integer',
            'quoted_rate' => 'nullable|numeric',
            'closed_rate' => 'nullable|numeric',
            'vendor_name' => 'nullable|string',
            'staff_name' => 'nullable|string',
            'vendor_closed_rate' => 'nullable|numeric',
            'deployment_date_time' => 'nullable|date',
            'deployment_status' => 'nullable|string',
            'payment_received' => 'nullable|numeric',
            'outstanding_payment' => 'nullable|numeric',
            'received_date_time' => 'nullable|date',
            'screenshot' => 'nullable|image',
            'invoice' => 'nullable|string',
        ]);

        $lead = \App\Models\OperationLead::findOrFail($id);
        if ($request->hasFile('screenshot')) {
            $validated['screenshot'] = $request->file('screenshot')->store('operation_leads/screenshots', 'public');
        }
        $lead->update($validated);
        return response()->json(['message' => 'Operation Lead updated successfully', 'lead' => $lead]);
    }

    public function destroy($id)
    {
        $lead = \App\Models\OperationLead::findOrFail($id);
        $lead->delete();
        return response()->json(['message' => 'Operation Lead deleted successfully']);
    }
}
