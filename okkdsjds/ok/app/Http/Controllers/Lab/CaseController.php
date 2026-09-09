<?php

namespace App\Http\Controllers\Lab;

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
            return redirect()->route("lab.dashboard");
        }
        return view('lab.case.index', compact('page_heading'));
    }

    public function ajax_list(Request $request)
    {
        $auth_user = Auth::guard('Lab')->user();
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
                return '<a href="' . route('lab.case.show', CaseIdEncryption::routeParam($case->id)) . '" class="btn btn-info">View</a>';
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
        return view('lab.case.show', compact('case', 'tpa_roles'));
    }

    public function update(Request $request, $id)
    {
        $case = Cases::findOrFail($id);
        $case->check_box = 1;
        if($case->save()){
            $dispatcher = User::whereRaw("FIND_IN_SET(?, role_id)", [7])->get();

            // Find the next available  member with is_next = true
            $next_member_assign = $dispatcher->where('is_next', true)->first();

            // If no member is marked as next, restart and assign the first one
            if (!$next_member_assign) {
                $next_member_assign = $dispatcher->first(); // Get the first member
            }

            $case->assign_member_id = $next_member_assign->id;
            $case->is_working_row = 0;
            $case->assign_member_role = 7; // main code
            $case->save();

            $user = Auth::user();
            $today = Carbon::now()->format('Y-m-d');

            $role = session('logged_role');
            $roleName = Role::where('id', $role)->first()->name;

            $auditResult = AuditTrail::createOrUpdateAuditTrail($case->id, $user->id, $user->f_name, 6, 'Lab', 'Main');

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

            // Reset is_next for all members
            User::whereRaw("FIND_IN_SET(?, role_id)", [7])->update(['is_next' => false]);

            // Set is_next to true for the next  member in the list
            $next_member_index = $dispatcher->search($next_member_assign); // Get the index of the current member
            $next_member_index = ($next_member_index + 1) % $dispatcher->count(); // Move to the next member or reset to the first one
            $next_user = $dispatcher->get($next_member_index); // Get the next  member
            $next_user->is_next = true;
            $next_user->save(); // Save the is_next flag
        }

        return redirect()->route('lab.case.index');
        // return response()->json([
        //     'success' => true,
        //     'message' => 'Case updated successfully!',
        // ]);
    }
    public function update_post_one(Request $request, $id)
    {
        $case = Cases::findOrFail($id);
        $case->check_box_post = 1;
        if($case->save()){
            $dispatcher = User::whereRaw("FIND_IN_SET(?, role_id)", [7])->get();

            // Find the next available  member with is_next = true
            $next_member_assign = $dispatcher->where('is_next', true)->first();

            // If no member is marked as next, restart and assign the first one
            if (!$next_member_assign) {
                $next_member_assign = $dispatcher->first(); // Get the first member
            }

            $case->assign_member_post = $next_member_assign->id;
            $case->is_working_row = 0;
            $case->assign_member_post_role = 7; // main code
            $case->save();

            $user = Auth::user();
            $today = Carbon::now()->format('Y-m-d');

            $role = session('logged_role');
            $roleName = Role::where('id', $role)->first()->name;

            $auditResult = AuditTrail::createOrUpdateAuditTrail($case->id, $user->id, $user->f_name, 6, 'Lab', 'Post one');

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

            // Reset is_next for all members
            User::whereRaw("FIND_IN_SET(?, role_id)", [7])->update(['is_next' => false]);

            // Set is_next to true for the next  member in the list
            $next_member_index = $dispatcher->search($next_member_assign); // Get the index of the current member
            $next_member_index = ($next_member_index + 1) % $dispatcher->count(); // Move to the next member or reset to the first one
            $next_user = $dispatcher->get($next_member_index); // Get the next  member
            $next_user->is_next = true;
            $next_user->save(); // Save the is_next flag
        }

        return redirect()->route('lab.case.index');
        // return response()->json([
        //     'success' => true,
        //     'message' => 'Case updated successfully!',
        // ]);
    }
    public function update_post_two(Request $request, $id)
    {
        $case = Cases::findOrFail($id);
        $case->check_box_post_two = 1;
        if($case->save()){
            $dispatcher = User::whereRaw("FIND_IN_SET(?, role_id)", [7])->get();

            // Find the next available  member with is_next = true
            $next_member_assign = $dispatcher->where('is_next', true)->first();

            // If no member is marked as next, restart and assign the first one
            if (!$next_member_assign) {
                $next_member_assign = $dispatcher->first(); // Get the first member
            }

            $case->assign_member_post = $next_member_assign->id;
            $case->is_working_row = 0;
            $case->assign_member_post_role = 7; // main code
            $case->save();

            $user = Auth::user();
            $today = Carbon::now()->format('Y-m-d');

            $role = session('logged_role');
            $roleName = Role::where('id', $role)->first()->name;

            // Update or insert productivity record
            $auditResult = AuditTrail::createOrUpdateAuditTrail($case->id, $user->id, $user->f_name, 6, 'Lab', 'Post two');

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


            // Reset is_next for all members
            User::whereRaw("FIND_IN_SET(?, role_id)", [7])->update(['is_next' => false]);

            // Set is_next to true for the next  member in the list
            $next_member_index = $dispatcher->search($next_member_assign); // Get the index of the current member
            $next_member_index = ($next_member_index + 1) % $dispatcher->count(); // Move to the next member or reset to the first one
            $next_user = $dispatcher->get($next_member_index); // Get the next  member
            $next_user->is_next = true;
            $next_user->save(); // Save the is_next flag
        }

        return redirect()->route('lab.case.index');
        // return response()->json([
        //     'success' => true,
        //     'message' => 'Case updated successfully!',
        // ]);
    }

    /**
     * Handle upload of lab files for a case.
     */
    public function uploadLabFiles(Request $request, $id)
    {
        $request->validate([
            'lab_files' => 'required|array',
            'lab_files.*' => 'file|max:51200', // 50MB per file
        ]);

        $case = Cases::findOrFail($id);
        $uploadedFiles = $request->file('lab_files');
        $paths = $case->lab_files ?? [];

        foreach ($uploadedFiles as $file) {
            $paths[] = $file->store('lab_files', 'public');
        }

        $case->lab_files = $paths;
        $case->save();

        return response()->json(['success' => true, 'files' => $paths]);
    }
}
