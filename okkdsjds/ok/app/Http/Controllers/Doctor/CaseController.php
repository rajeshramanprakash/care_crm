<?php

namespace App\Http\Controllers\Doctor;

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
            return redirect()->route("doctor.dashboard");
        }
        return view('doctor.case.index', compact('page_heading', 'filter_params'));
    }

    public function ajax_list(Request $request)
    {
        $auth_user = Auth::guard('Doctor')->user();
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
            'cases.is_priority',
            'cases.dod_time',
            'cases.hospital',
            'cases.diagnosis',
            'cases.bill_range',
            'cases.is_working_row',
            'cases.post_status',
            'cases.post_two_status',
        ])
            ->with(['user:id,name'])
            ->where('forward_status', 1)
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
                }elseif ($request->dashboard_filters == "post_claim_cases") {
                    $cases->where('is_post_1', 1)->where('is_post_2', 0);
                }elseif ($request->dashboard_filters == "post_two_claim_cases") {
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
                return '<a href="' . route('doctor.case.show', CaseIdEncryption::routeParam($case->id)) . '" class="btn btn-info">View</a>';
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

    public function updateWorkingStatus(Request $request)
    {
        $validated = $request->validate([
            'case_id' => 'required|integer',
            'is_working' => 'required|boolean',
        ]);

        $case = Cases::findOrFail($validated['case_id']);
        $case->is_working_row = $validated['is_working'];
        $case->save();

        return response()->json(['success' => true, 'message' => 'Working status updated successfully']);
    }

    public function show($encryptedId)
    {
        $id = CaseIdEncryption::decrypt($encryptedId);
        $case = Cases::findOrFail($id);
        $tpa_roles = User::whereRaw("FIND_IN_SET(?, role_id)", [8])->get();
        return view('doctor.case.show', compact('case', 'tpa_roles'));
    }




    public function update(Request $request, $id)
    {
        $request->validate([
            'icp_attachment' => 'nullable|file|mimes:jpg,jpeg,png,pdf,xls,webp,xlsx,docx,doc|max:5120',
        ]);

        $case = Cases::findOrFail($id);

        if ($request->hasFile('icp_attachment')) {
            $case->icp_attachment = $request->file('icp_attachment')->store('attachments', 'public');
        }

        $case->save();


        if($case->save()){
            $user = Auth::user();
            $today = Carbon::now()->format('Y-m-d');

            $role = session('logged_role');
            $roleName = Role::where('id', $role)->first()->name;

            $auditResult = AuditTrail::createOrUpdateAuditTrail($case->id, $user->id, $user->f_name, 3, 'Doctor', 'Main');

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

            $medicineVital = User::whereRaw("FIND_IN_SET(?, role_id)", [4])->get();

            // Find the next available  member with is_next = true
            $next_member_assign = $medicineVital->where('is_next', true)->first();

            // If no member is marked as next, restart and assign the first one
            if (!$next_member_assign) {
                $next_member_assign = $medicineVital->first(); // Get the first member
            }

            $case->assign_member_id = $next_member_assign->id;
            $case->is_working_row = 0;

            $case->assign_member_role = 4; // main code
            $case->save();

            // Reset is_next for all members
            User::whereRaw("FIND_IN_SET(?, role_id)", [4])->update(['is_next' => false]);

            // Set is_next to true for the next  member in the list
            $next_member_index = $medicineVital->search($next_member_assign); // Get the index of the current member
            $next_member_index = ($next_member_index + 1) % $medicineVital->count(); // Move to the next member or reset to the first one
            $next_user = $medicineVital->get($next_member_index); // Get the next  member
            $next_user->is_next = true;
            $next_user->save(); // Save the is_next flag
        }

        return response()->json([
            'success' => true,
            'message' => 'Case updated successfully!',
        ]);
    }

    public function update_post_one(Request $request, $id)
    {
        $request->validate([
            'opd_attachment' => 'nullable|file|mimes:jpg,jpeg,png,pdf,xls,webp,xlsx,docx,doc|max:5120',
        ]);

        $case = Cases::findOrFail($id);

        if ($request->hasFile('opd_attachment')) {
            $case->opd_attachment = $request->file('opd_attachment')->store('attachments', 'public');
        }

        $case->save();


        if($case->save()){

            $user = Auth::user();
            $today = Carbon::now()->format('Y-m-d');

            $role = session('logged_role');
            $roleName = Role::where('id', $role)->first()->name;

            $auditResult = AuditTrail::createOrUpdateAuditTrail($case->id, $user->id, $user->f_name, 3, 'Doctor', 'Post one');

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

            $medicineVital = User::whereRaw("FIND_IN_SET(?, role_id)", [5])->get();

            // Find the next available  member with is_next = true
            $next_member_assign = $medicineVital->where('is_next', true)->first();

            // If no member is marked as next, restart and assign the first one
            if (!$next_member_assign) {
                $next_member_assign = $medicineVital->first(); // Get the first member
            }

            $case->assign_member_post = $next_member_assign->id;
            $case->is_working_row = 0;
            $case->assign_member_post_role = 5; // main code

            $case->save();

            // Reset is_next for all members
            User::whereRaw("FIND_IN_SET(?, role_id)", [5])->update(['is_next' => false]);

            // Set is_next to true for the next  member in the list
            $next_member_index = $medicineVital->search($next_member_assign); // Get the index of the current member
            $next_member_index = ($next_member_index + 1) % $medicineVital->count(); // Move to the next member or reset to the first one
            $next_user = $medicineVital->get($next_member_index); // Get the next  member
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
            'opd_attachment' => 'nullable|file|mimes:jpg,jpeg,png,pdf,xls,webp,xlsx,docx,doc|max:5120',
        ]);

        $case = Cases::findOrFail($id);

        if ($request->hasFile('opd_attachment')) {
            $case->opd_attachment_2 = $request->file('opd_attachment')->store('attachments', 'public');
        }

        $case->save();

        if($case->save()){

            $user = Auth::user();
            $today = Carbon::now()->format('Y-m-d');

            $role = session('logged_role');
            $roleName = Role::where('id', $role)->first()->name;

            // Update or insert productivity record
            $auditResult = AuditTrail::createOrUpdateAuditTrail($case->id, $user->id, $user->f_name, 3, 'Doctor', 'Post two');

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

            $medicineVital = User::whereRaw("FIND_IN_SET(?, role_id)", [5])->get();

            // Find the next available  member with is_next = true
            $next_member_assign = $medicineVital->where('is_next', true)->first();

            // If no member is marked as next, restart and assign the first one
            if (!$next_member_assign) {
                $next_member_assign = $medicineVital->first(); // Get the first member
            }

            $case->assign_member_post = $next_member_assign->id;
            $case->is_working_row = 0;
            $case->assign_member_post_role = 5; // main code
            $case->save();

            // Reset is_next for all members
            User::whereRaw("FIND_IN_SET(?, role_id)", [5])->update(['is_next' => false]);

            // Set is_next to true for the next  member in the list
            $next_member_index = $medicineVital->search($next_member_assign); // Get the index of the current member
            $next_member_index = ($next_member_index + 1) % $medicineVital->count(); // Move to the next member or reset to the first one
            $next_user = $medicineVital->get($next_member_index); // Get the next  member
            $next_user->is_next = true;
            $next_user->save(); // Save the is_next flag
        }

        return response()->json([
            'success' => true,
            'message' => 'Case updated successfully!',
        ]);
    }
}
