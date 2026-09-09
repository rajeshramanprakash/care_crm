<?php

namespace App\Http\Controllers\OperationManager;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class LeadController extends Controller
{
    public function index()
    {
        $page_heading = 'Operation Leads';
        return view('operation_manager.leads.index', compact('page_heading'));
    }

    public function getLeads(Request $request)
    {
        $leads = Lead::select([
            'id',
            'date',
            'executive',
            'customer_name',
            'contact_no',
            'location',
            'lead_source',
            'query',
            'status',
            'requirement',
            'vendor',
            'closed_amt',
            'vendor_amt',
            'margin',
            'amt_received',
            'invoice'
        ])->where('query', 'LIKE', '%operation%')
          ->orWhere('query', 'LIKE', '%attendant%')
          ->orWhere('query', 'LIKE', '%nurse%');

        return DataTables::of($leads)
            ->addColumn('action', function ($lead) {
                return '
                    <div class="action-dropdown">
                        <button class="action-dropdown-btn" onclick="event.stopPropagation(); toggleDropdown(this)">
                            <i class="fas fa-ellipsis-v"></i>
                        </button>
                        <div class="action-dropdown-menu">
                            <button class="edit-btn" data-id="'.$lead->id.'"><i class="fas fa-pen icon-edit"></i> Edit</button>
                            <button class="view-btn" data-id="'.$lead->id.'"><i class="fas fa-eye icon-view"></i> View</button>
                        </div>
                    </div>
                ';
            })
            ->editColumn('date', function ($lead) {
                return date('d-m-Y', strtotime($lead->date));
            })
            ->editColumn('closed_amt', function ($lead) {
                return $lead->closed_amt ? '₹' . number_format($lead->closed_amt, 2) : '-';
            })
            ->editColumn('vendor_amt', function ($lead) {
                return $lead->vendor_amt ? '₹' . number_format($lead->vendor_amt, 2) : '-';
            })
            ->editColumn('margin', function ($lead) {
                return $lead->margin ? '₹' . number_format($lead->margin, 2) : '-';
            })
            ->editColumn('amt_received', function ($lead) {
                return $lead->amt_received ? '₹' . number_format($lead->amt_received, 2) : '-';
            })
            ->rawColumns(['action'])
            ->make(true);
    }

    public function edit($id)
    {
        $lead = Lead::findOrFail($id);
        return response()->json($lead);
    }

    public function update(Request $request, $id)
    {
        $lead = Lead::findOrFail($id);

        $validated = $request->validate([
            'date' => 'required|date',
            'executive' => 'required|string',
            'customer_name' => 'required|string',
            'contact_no' => 'required|string',
            'location' => 'required|string',
            'lead_source' => 'required|string',
            'query' => 'required|string',
            'status' => 'required|string',
            'requirement' => 'required|string',
            'vendor' => 'nullable|string',
            'closed_amt' => 'nullable|numeric',
            'vendor_amt' => 'nullable|numeric',
            'margin' => 'nullable|numeric',
            'amt_received' => 'nullable|numeric',
            'invoice' => 'nullable|string'
        ]);

        $lead->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Lead updated successfully'
        ]);
    }

    public function show($id)
    {
        $lead = Lead::findOrFail($id);
        return response()->json($lead);
    }
}
