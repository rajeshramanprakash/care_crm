<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditTrail;
use App\Models\Cases;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AuditTrailController extends Controller
{
    public function index(){
        $page_heading = 'Audit Trail';
        return view('admin.audit_trail.index' , compact('page_heading'));
    }

    public function ajax(Request $request)
    {
        $case_code = $request->case_code;
        $case = Cases::where('case_code', $case_code)->first();
        if ($case) {
            $audit_trail = AuditTrail::where('case_code', $case->id)->get();
            return response()->json(['success' => true, 'data' => $audit_trail, 'case_code' =>$case_code]);
        }
        return response()->json(['success' => false, 'message' => 'Data not found']);
    }

    public function user_audit_trail(Request $request)
{
    $date = $request->input('date', Carbon::now()->format('Y-m-d'));
    $role_id = $request->input('role_id');
    $user_id = $request->input('user_id');

    // Build the query
    $query = AuditTrail::select(
        'audit_trail.case_code',
        'audit_trail.user_name',
        'audit_trail.role_name',
        'audit_trail.claim_type',
        'cases.name as patient_name',
        'cases.case_code as case_codes'
    )
    ->join('cases', 'cases.id', '=', 'audit_trail.case_code'); // Ensure correct join logic

    // Apply date filter
    $query->whereDate('audit_trail.created_at', $date);

    // Apply role_id filter if provided
    if (!empty($role_id)) {
        $query->where('audit_trail.role_id', $role_id);
    }

    // Apply user_id filter if provided
    if (!empty($user_id)) {
        $query->where('audit_trail.user_id', $user_id);
    }

    // Execute the query and fetch results
    $audit_trail = $query->get();

    // Return the response
    return response()->json([
        'success' => true,
        'data' => $audit_trail,
    ]);
}

}
