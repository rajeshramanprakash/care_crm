<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cases;
use App\Models\User;
use App\Models\VendorCase;
use App\Models\Role;
use App\Models\AuditTrail;
use App\Models\Productivity;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class VendorCaseApiController extends Controller
{
    public function index(Request $request)
    {
        // Check if user is vendor
        $user = Auth::user();
        $roleIds = explode(',', $user->role_id);

        if (!in_array('10', $roleIds)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $cases = Cases::select([
            'id',
            'case_code',
            'name',
            'age',
            'member_id',
            'corp',
            'relation',
            'gender',
            'doa',
            'dod',
            'doa_time',
            'dod_time',
            'is_priority',
            'hospital',
            'status',
            'post_status',
            'post_two_status',
            'approved_date',
            'paid_date',
            'approved_amt',
            'post_ammount',
            'post_two_ammount',
            'claim_no',
            'post_claim_no',
            'post_two_claim_no',
            'claim_no_link',
            'post_claim_no_link',
            'post_two_claim_no_link',
            'forward_status',
            'forward_status_remark',
            'assign_member_role',
            'pre_courier_no',
            'created_at',
        ])->where('created_by', $user->id);

        // Apply filters
        if ($request->dashboard_filters) {
            $cases = $this->applyFilters($cases, $request->dashboard_filters);
        }

        $cases = $cases->orderBy('is_priority', 'desc')
                      ->orderBy('id', 'desc')
                      ->get();

        $formattedCases = $cases->map(function ($case) {
            return [
                'id' => $case->id,
                'case_code' => $case->case_code,
                'name' => $case->name,
                'age' => $case->age,
                'gender' => $case->gender,
                'member_id' => $case->member_id,
                'corp' => $case->corp,
                'relation' => $case->relation,
                'doa' => $case->doa,
                'doa_time' => $case->doa_time,
                'dod' => $case->dod,
                'dod_time' => $case->dod_time,
                'status' => $case->status,
                'post_status' => $case->post_status,
                'post_two_status' => $case->post_two_status,
                'approved_amt' => $case->approved_amt,
                'post_ammount' => $case->post_ammount,
                'post_two_ammount' => $case->post_two_ammount,
                'claim_no' => $case->claim_no,
                'post_claim_no' => $case->post_claim_no,
                'post_two_claim_no' => $case->post_two_claim_no,
                'case_department' => $this->getCaseDepartment($case),
                'case_color' => $this->getCaseColor($case),
                'text_color' => $this->getTextColor($case),
                'created_at' => $case->created_at->format('Y-m-d H:i:s')
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $formattedCases
        ]);
    }

    public function show($id)
    {
        // Check if user is vendor
        $user = Auth::user();
        $roleIds = explode(',', $user->role_id);

        if (!in_array('10', $roleIds)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $case = Cases::with(['get_guery'])->where('created_by', $user->id)->findOrFail($id);

        // Format the case data
        $formattedCase = [
            'id' => $case->id,
            'case_code' => $case->case_code,
            'name' => $case->name,
            'age' => $case->age,
            'gender' => $case->gender,
            'member_id' => $case->member_id,
            'corp' => $case->corp,
            'relation' => $case->relation,
            'doa' => $case->doa,
            'doa_time' => $case->doa_time,
            'dod' => $case->dod,
            'dod_time' => $case->dod_time,
            'status' => $case->status,
            'post_status' => $case->post_status,
            'post_two_status' => $case->post_two_status,
            'approved_amt' => $case->approved_amt,
            'post_ammount' => $case->post_ammount,
            'post_two_ammount' => $case->post_two_ammount,
            'claim_no' => $case->claim_no,
            'post_claim_no' => $case->post_claim_no,
            'post_two_claim_no' => $case->post_two_claim_no,
            'claim_no_link' => $case->claim_no_link,
            'post_claim_no_link' => $case->post_claim_no_link,
            'post_two_claim_no_link' => $case->post_two_claim_no_link,
            'aadhar_attachment' => $case->aadhar_attachment,
            'aadhar_attachment_2' => $case->aadhar_attachment_2,
            'pan_card' => $case->pan_card,
            'cancelled_cheque' => $case->cancelled_cheque,
            'policy' => $case->policy,
            'forward_status' => $case->forward_status,
            'forward_status_remark' => $case->forward_status_remark,
            'assign_member_role' => $case->assign_member_role,
            'pre_courier_no' => $case->pre_courier_no,
            'case_department' => $this->getCaseDepartment($case),
            'case_color' => $this->getCaseColor($case),
            'text_color' => $this->getTextColor($case),
            'get_guery' => $case->get_guery,
            'created_at' => $case->created_at->format('Y-m-d H:i:s')
        ];

        return response()->json([
            'success' => true,
            'data' => $formattedCase
        ]);
    }

    public function store(Request $request)
    {
        // Check if user is vendor
        $user = Auth::user();
        $roleIds = explode(',', $user->role_id);

        if (!in_array('10', $roleIds)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'member_id' => 'required|string|max:255',
            'age' => 'required|integer',
            'gender' => 'required|in:Male,Female,Other',
            'doa' => 'nullable|date',
            'doa_time' => 'nullable|date_format:H:i',
            'dod' => 'nullable|date',
            'dod_time' => 'nullable|date_format:H:i',
            'tpa' => 'nullable',
            'corp' => 'nullable|string|max:255',
            'relation' => 'nullable|string|max:255',
            'aadhar_attachment' => 'nullable|file|mimes:jpg,jpeg,png,pdf,xls,webp,xlsx,docx,doc|max:20480',
            'aadhar_attachment_2' => 'nullable|file|mimes:jpg,jpeg,png,pdf,xls,webp,xlsx,docx,doc|max:20480',
            'pan_card' => 'nullable|file|mimes:jpg,jpeg,png,pdf,xls,webp,xlsx,docx,doc|max:20480',
            'cancelled_cheque' => 'nullable|file|mimes:jpg,jpeg,png,pdf,xls,webp,xlsx,docx,doc|max:20480',
            'policy' => 'nullable|file|mimes:jpg,jpeg,png,pdf,xls,webp,xlsx,docx,doc|max:20480',
        ]);

        $validated['created_by'] = $user->id;

        $case = Cases::create($validated);
        $case->case_code = "case_000$case->id";

        // Handle file uploads
        if ($request->hasFile('aadhar_attachment')) {
            $case->aadhar_attachment = $request->file('aadhar_attachment')->store('attachments', 'public');
        }
        if ($request->hasFile('aadhar_attachment_2')) {
            $case->aadhar_attachment_2 = $request->file('aadhar_attachment_2')->store('attachments', 'public');
        }
        if ($request->hasFile('pan_card')) {
            $case->pan_card = $request->file('pan_card')->store('attachments', 'public');
        }
        if ($request->hasFile('cancelled_cheque')) {
            $case->cancelled_cheque = $request->file('cancelled_cheque')->store('attachments', 'public');
        }
        if ($request->hasFile('policy')) {
            $case->policy = $request->file('policy')->store('attachments', 'public');
        }

        $case->save();

        // Create audit trail
        $audit_trail = new AuditTrail();
        $audit_trail->case_code = $case->id;
        $audit_trail->user_id = $user->id;
        $audit_trail->user_name = $user->f_name;
        $audit_trail->role_id = 10;
        $audit_trail->role_name = 'Vendor';
        $audit_trail->claim_type = 'Main';
        $audit_trail->save();

        // Update productivity
        $today = Carbon::now()->format('Y-m-d');
        $role = Role::where('id', 10)->first();
        $productivity = Productivity::firstOrNew([
            'user_id' => $user->id,
            'role_id' => 10,
            'date' => $today,
        ]);
        $productivity->role_name = $role->name;
        $productivity->file_count += 1;
        $productivity->save();

        return response()->json([
            'success' => true,
            'message' => 'Case created successfully!'
        ]);
    }

    public function update(Request $request, $id)
    {
        // Check if user is vendor
        $user = Auth::user();
        $roleIds = explode(',', $user->role_id);

        if (!in_array('10', $roleIds)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'age' => 'required|integer',
            'corp' => 'required|string|max:255',
            'member_id' => 'required|string|max:255',
            'relation' => 'required|string|max:255',
            'gender' => 'required|string',
            'doa' => 'required|date',
            'doa_time' => 'required',
            'tpa' => 'required',
            'dod' => 'required|date',
            'dod_time' => 'required',
        ]);

        $case = Cases::where('created_by', $user->id)->findOrFail($id);

        $case->name = $request->name;
        $case->age = $request->age;
        $case->corp = $request->corp;
        $case->tpa = $request->tpa;
        $case->relation = $request->relation;
        $case->gender = $request->gender;
        $case->doa = $request->doa;
        $case->doa_time = $request->doa_time;
        $case->dod = $request->dod;
        $case->dod_time = $request->dod_time;
        $case->member_id = $request->member_id;

        $case->save();

        return response()->json([
            'success' => true,
            'message' => 'Case updated successfully!',
        ]);
    }

    public function destroy($id)
    {
        // Check if user is vendor
        $user = Auth::user();
        $roleIds = explode(',', $user->role_id);

        if (!in_array('10', $roleIds)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $case = Cases::where('created_by', $user->id)->findOrFail($id);
        $case->delete();

        return response()->json([
            'success' => true,
            'message' => 'Case deleted successfully!'
        ]);
    }

    private function applyFilters($query, $filter)
    {
        switch ($filter) {
            case "main_claim_cases":
                return $query->where('is_post_1', 0)->where('is_post_2', 0);
            case "main_claim_cases_query":
                return $query->where('is_post_1', 0)->where('is_post_2', 0)->where(['status' => 'Query']);
            case "main_claim_cases_investigation":
                return $query->where('is_post_1', 0)->where(['status' => 'Investigation']);
            case "main_claim_cases_reject":
                return $query->where('is_post_1', 0)->where(['status' => 'Reject']);
            case "main_claim_cases_underprocess":
                return $query->where('is_post_1', 0)->where(['status' => 'UnderProcess']);
            case "main_claim_cases_approved":
                return $query->where(['status' => 'Approved']);
            case "main_claim_cases_inprocess":
                return $query->where(['status' => 'InProcess']);
            case "main_claim_cases_paid":
                return $query->where(['status' => 'Paid']);
            case "post_claim_cases":
                return $query->where('is_post_1', 1)->where('is_post_2', 0);
            case "post_claim_cases_query":
                return $query->where('is_post_1', 1)->where(['post_status' => 'Query']);
            case "post_claim_cases_approved":
                return $query->where('is_post_1', 1)->where(['post_status' => 'Approved']);
            case "post_claim_cases_paid":
                return $query->where('is_post_1', 1)->where(['post_status' => 'Paid']);
            case "post_two_claim_cases":
                return $query->where('is_post_2', 1);
            case "post_two_claim_cases_approved":
                return $query->where('is_post_2', 1)->where(['post_two_status' => 'Approved']);
            case "post_two_claim_cases_paid":
                return $query->where('is_post_2', 1)->where(['post_two_status' => 'Paid']);
            default:
                return $query;
        }
    }

    private function getCaseDepartment($case)
    {
        $query_status = 'Pending';

        if ($case->forward_status == 0 && $case->forward_status_remark == null) {
            $query_status = 'Pending';
        } else if ($case->forward_status == 1) {
            $query_status = 'Forwarded To Sales Department';
            if ($case->assign_member_role == 3) {
                $query_status = 'Forwarded To Doctor Department';
            } elseif ($case->assign_member_role == 4) {
                $query_status = 'Forwarded To Medical Department';
            } elseif ($case->assign_member_role == 5) {
                $query_status = 'Forwarded To Billing Department';
            } elseif ($case->assign_member_role == 6) {
                $query_status = 'Forwarded To Lab Department';
            } elseif ($case->assign_member_role == 7) {
                $query_status = 'Forwarded To Dispatch Department';
            }

            if($case->pre_courier_no){
                $query_status = "Case Status: " . ($case->status ?? 'Processing');
            }
        } else if ($case->forward_status == 0 && $case->forward_status_remark !== null) {
            $query_status = "Hold -- Reason: $case->forward_status_remark";
        } elseif ($case->forward_status == 2 && $case->forward_status_remark !== null) {
            $query_status = "Cancelled -- Reason: $case->forward_status_remark";
        }

        return $query_status;
    }

    private function getCaseColor($case)
    {
        $color_code = '';
        if (($case->forward_status == 0 || $case->forward_status == 2) && $case->forward_status_remark !== null) {
            $color_code = '#DC3545';
        }
        if ($case->status == 'Query') {
            $color_code = '#FFB6C1';
        } elseif ($case->status == 'Investigation') {
            $color_code = '#ADD8E6';
        } elseif ($case->status == 'Reject') {
            $color_code = '#6C757D';
        } elseif ($case->status == 'UnderProcess') {
            $color_code = '#FFC107';
        } elseif ($case->status == 'Approved' || $case->status == 'InProcess' || $case->status == 'Paid') {
            $color_code = '#28A745';
        }

        return $color_code;
    }

    private function getTextColor($case)
    {
        $text_color = '';
        if ($case->status == 'Query') {
            $text_color = '#000000';
        } elseif ($case->status == 'Investigation') {
            $text_color = '#000000';
        } elseif ($case->status == 'Reject') {
            $text_color = '#FFFFFF';
        } elseif ($case->status == 'UnderProcess') {
            $text_color = '#000000';
        } elseif ($case->status == 'Approved' || $case->status == 'Paid') {
            $text_color = '#FFFFFF';
        }
        if (($case->forward_status == 0 || $case->forward_status == 2) && $case->forward_status_remark !== null) {
            $text_color = '#FFFFFF';
        }

        return $text_color;
    }
}
