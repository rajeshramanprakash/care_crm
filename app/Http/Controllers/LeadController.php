<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class LeadController extends Controller
{
    public function index()
    {
        return view('leads.index');
    }

    public function getLeads()
    {
        $leads = Lead::select(['id', 'date', 'executive', 'customer_name', 'contact_no', 'location', 'lead_source', 'query', 'status', 'requirement', 'vendor', 'closed_amt', 'vendor_amt', 'margin', 'amt_received', 'invoice']);

        return DataTables::of($leads)
            ->addColumn('action', function ($lead) {
                $viewBtn = '<a href="' . route('leads.show', $lead->id) . '" class="btn btn-sm btn-info">View</a>';
                $deleteBtn = '';
                if (auth()->user()->role_id == 1) { // Admin role
                    $deleteBtn = ' <a href="javascript:void(0);" onclick="deleteLead(' . $lead->id . ')" class="btn btn-sm btn-danger">Delete</a>';
                }
                return $viewBtn . $deleteBtn;
            })
            ->rawColumns(['action'])
            ->make(true);
    }

    public function show($id)
    {
        $lead = Lead::findOrFail($id);
        return view('leads.show', compact('lead'));
    }

    public function destroy($id)
    {
        if (auth()->user()->role_id != 1) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        Lead::destroy($id);
        return response()->json(['success' => true]);
    }
} 