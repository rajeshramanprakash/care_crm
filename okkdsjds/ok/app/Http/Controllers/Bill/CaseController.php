<?php

namespace App\Http\Controllers\Bill;

use App\Http\Controllers\Controller;
use App\Models\AuditTrail;
use App\Models\Cases;
use App\Models\Productivity;
use App\Models\Role;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Helpers\CaseIdEncryption;

class CaseController extends Controller
{
    public function index($dashboard_filters = null)
    {
        $page_heading = 'Cases';
        $filter_params = "";
        if ($dashboard_filters !== null) {
            $filter_params = ['dashboard_filters' => $dashboard_filters];
            $page_heading = ucwords(str_replace("_", " ", $dashboard_filters));
        }
        if($dashboard_filters == null){
            return redirect()->route('billing.dashboard');
        }
        return view('bill.case.index', compact('page_heading', 'filter_params'));
    }

    public function ajax_list(Request $request)
    {
        $auth_user = Auth::guard('Bill')->user();
        $loggedRoles = session('logged_role');
        $cases = Cases::select([
            'cases.id',
            'cases.case_code',
            'cases.name',
            'cases.age',
            'cases.member_id',
            'cases.corp',
            'cases.relation',
            'cases.gender',
            'cases.doa',
            'cases.doa_time',
            'cases.dod',
            'cases.dod_time',
            'cases.is_priority',
            'cases.hospital',
            'cases.diagnosis',
            'cases.bill_range',
            'cases.is_working_row',
            'cases.post_status',
            'cases.post_two_status',
            'users.f_name as created_by_name',
        ])
            ->with(['user:id,name'])
            ->where('forward_status', 1)
            ->join('users', 'cases.created_by', '=', 'users.id')
            ->where(function ($query) use ($loggedRoles) {
                $query->where('assign_member_post_role', $loggedRoles)
                ->orWhere('assign_member_role', $loggedRoles);
            })
            ->where(function ($query) {
                // Don't show cases that are on hold
                $query->where(function ($q) {
                    $q->where('post_status', '!=', 'Hold')
                      ->orWhereNull('post_status');
                })
                ->where(function ($q) {
                    $q->where('post_two_status', '!=', 'Hold')
                      ->orWhereNull('post_two_status');
                });
            })
            ->groupBy('cases.id');

        if ($request->dashboard_filters != null) {
            if ($request->dashboard_filters == "main_claim_cases") {
                $cases->where('is_post_1', 0)->where('is_post_2', 0);
            } elseif ($request->dashboard_filters == "post_claim_cases") {
                $cases->where('is_post_1', 1)->where('is_post_2', 0);
            } elseif ($request->dashboard_filters == "post_two_claim_cases") {
                $cases->where('is_post_2', 1);
            }
        }
        return dataTables()->of($cases)
            ->addColumn('created_by', function ($case) {
                return $case->user ? $case->user->name : 'N/A';
            })
            ->addColumn('encrypted_id', function ($case) {
                return CaseIdEncryption::routeParam($case->id);
            })
            ->addColumn('actions', function ($case) {
                return '<a href="' . route('bill.case.show', CaseIdEncryption::routeParam($case->id)) . '" class="btn btn-info">View</a>';
            })
            ->addColumn('case_color', function ($case) {
                $color_code = '';
                if ($case->is_working_row !== 0) {
                    $auth_user = Auth::user();
                    if ($auth_user->id == $case->is_working_row) {
                        $color_code = '#12cf004a';
                    } else {
                        $color_code = '#FFB6C1';
                    }
                }
                return $color_code;
            })
            ->make(true);
    }

    public function show($encryptedId)
    {
        $id = CaseIdEncryption::decrypt($encryptedId);
        $case = Cases::findOrFail($id);
        $tpa_roles = User::whereRaw("FIND_IN_SET(?, role_id)", [8])->get();
        return view('bill.case.show', compact('case', 'tpa_roles'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'ipd_no_entry' => 'required',
            'bill_attachment_1' => 'nullable|file|mimes:jpg,jpeg,png,pdf,xls,webp,xlsx,docx,doc|max:20480',
            'discharge_summary_attachment' => 'nullable|file|mimes:jpg,jpeg,png,pdf,xls,webp,xlsx,docx,doc|max:20480',
        ]);

        $case = Cases::findOrFail($id);
        $case->ipd_no_entry = $request->ipd_no_entry;
        if ($request->hasFile('bill_attachment_1')) {
            $case->bill_attachment_1 = $request->file('bill_attachment_1')->store('attachments', 'public');
        }
        if ($request->hasFile('discharge_summary_attachment')) {
            $case->discharge_summary_attachment = $request->file('discharge_summary_attachment')->store('attachments', 'public');
        }
        $case->save();

        if ($case->save()) {

            $user = Auth::user();
            $today = Carbon::now()->format('Y-m-d');

            $role = session('logged_role');
            $roleName = Role::where('id', $role)->first()->name;

            $auditResult = AuditTrail::createOrUpdateAuditTrail($case->id, $user->id, $user->f_name, 5, 'Billing', 'Main');

            // Only increment productivity file_count if a new audit trail was created
            if ($auditResult['is_new']) {
                // Update or insert productivity record
                $productivity = Productivity::firstOrNew([
                    'user_id' => $user->id,
                    'role_id' => $role,
                    'date' => $today,
                ]);

                $productivity->role_name = $roleName;
                $productivity->file_count += 1;
                $productivity->save();
            }

            $lab_maker = User::whereRaw("FIND_IN_SET(?, role_id)", [6])->get();

            $next_member_assign = $lab_maker->where('is_next', true)->first();

            if (!$next_member_assign) {
                $next_member_assign = $lab_maker->first();
            }

            $case->assign_member_id = $next_member_assign->id;
            $case->is_working_row = 0;
            $case->assign_member_role = 6; // main code
            $case->save();

            User::whereRaw("FIND_IN_SET(?, role_id)", [6])->update(['is_next' => false]);

            $next_member_index = $lab_maker->search($next_member_assign);
            $next_member_index = ($next_member_index + 1) % $lab_maker->count();
            $next_user = $lab_maker->get($next_member_index);
            $next_user->is_next = true;
            $next_user->save();
        }

        return response()->json([
            'success' => true,
            'message' => 'Case updated successfully!',
        ]);
    }

    public function update_post_one(Request $request, $id)
    {
        $request->validate([
            'bill_attachment_post' => 'nullable|file|mimes:jpg,jpeg,png,pdf,xls,webp,xlsx,docx,doc|max:20480',
        ]);

        $case = Cases::findOrFail($id);
        $case->ipd_no_entry = $request->ipd_no_entry;
        if ($request->hasFile('bill_attachment_post')) {
            $case->bill_attachment_post = $request->file('bill_attachment_post')->store('attachments', 'public');
        }
        $case->save();

        if ($case->save()) {
            $lab_maker = User::whereRaw("FIND_IN_SET(?, role_id)", [6])->get();

            $next_member_assign = $lab_maker->where('is_next', true)->first();

            if (!$next_member_assign) {
                $next_member_assign = $lab_maker->first();
            }

            $case->assign_member_post = $next_member_assign->id;
            $case->is_working_row = 0;
            $case->assign_member_post_role = 6; // main code
            $case->save();



            $user = Auth::user();
            $today = Carbon::now()->format('Y-m-d');

            $role = session('logged_role');
            $roleName = Role::where('id', $role)->first()->name;

            $auditResult = AuditTrail::createOrUpdateAuditTrail($case->id, $user->id, $user->f_name, 5, 'Billing', 'Post one');

            // Only increment productivity file_count if a new audit trail was created
            if ($auditResult['is_new']) {
                // Update or insert productivity record
                $productivity = Productivity::firstOrNew([
                    'user_id' => $user->id,
                    'role_id' => $role,
                    'date' => $today,
                ]);

                $productivity->role_name = $roleName;
                $productivity->file_count += 1;
                $productivity->save();
            }


            User::whereRaw("FIND_IN_SET(?, role_id)", [6])->update(['is_next' => false]);

            $next_member_index = $lab_maker->search($next_member_assign);
            $next_member_index = ($next_member_index + 1) % $lab_maker->count();
            $next_user = $lab_maker->get($next_member_index);
            $next_user->is_next = true;
            $next_user->save();
        }

        return response()->json([
            'success' => true,
            'message' => 'Case updated successfully!',
        ]);
    }
    public function update_post_two(Request $request, $id)
    {
        $request->validate([
            'bill_attachment_post_two' => 'nullable|file|mimes:jpg,jpeg,png,pdf,xls,webp,xlsx,docx,doc|max:20480',
        ]);

        $case = Cases::findOrFail($id);
        $case->ipd_no_entry = $request->ipd_no_entry;
        if ($request->hasFile('bill_attachment_post_two')) {
            $case->bill_attachment_post_two = $request->file('bill_attachment_post_two')->store('attachments', 'public');
        }
        $case->save();

        if ($case->save()) {
            $user = Auth::user();
            $today = Carbon::now()->format('Y-m-d');

            $role = session('logged_role');
            $roleName = Role::where('id', $role)->first()->name;

            $auditResult = AuditTrail::createOrUpdateAuditTrail($case->id, $user->id, $user->f_name, 5, 'Billing', 'Post two');

            // Only increment productivity file_count if a new audit trail was created
            if ($auditResult['is_new']) {
                $productivity = Productivity::firstOrNew([
                    'user_id' => $user->id,
                    'role_id' => $role,
                    'date' => $today,
                ]);

                $productivity->role_name = $roleName;
                $productivity->file_count += 1;
                $productivity->save();
            }


            $lab_maker = User::whereRaw("FIND_IN_SET(?, role_id)", [6])->get();

            $next_member_assign = $lab_maker->where('is_next', true)->first();

            if (!$next_member_assign) {
                $next_member_assign = $lab_maker->first();
            }

            $case->assign_member_post = $next_member_assign->id;
            $case->is_working_row = 0;
            $case->assign_member_post_role = 6; // main code
            $case->save();

            User::whereRaw("FIND_IN_SET(?, role_id)", [6])->update(['is_next' => false]);

            $next_member_index = $lab_maker->search($next_member_assign);
            $next_member_index = ($next_member_index + 1) % $lab_maker->count();
            $next_user = $lab_maker->get($next_member_index);
            $next_user->is_next = true;
            $next_user->save();
        }

        return response()->json([
            'success' => true,
            'message' => 'Case updated successfully!',
        ]);
    }

    public function updateWorkingStatus(Request $request)
    {
        $user = Auth::user();
        $validated = $request->validate([
            'case_id' => 'required|integer',
            'is_working' => 'required|boolean',
        ]);

        $case = Cases::findOrFail($validated['case_id']);
        if( $validated['is_working']){
            $case->is_working_row = $user->id;
        }else{
            $case->is_working_row = 0;
        }
        $case->save();

        return response()->json(['success' => true, 'message' => 'Working status updated successfully']);
    }
}
