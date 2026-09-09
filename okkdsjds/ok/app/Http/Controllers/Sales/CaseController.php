<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\Cases;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Role;
use Carbon\Carbon;
use App\Models\AuditTrail;
use App\Models\Productivity;
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
            return redirect()->route('sales.dashboard');
        }
        return view('sales.case.index', compact('page_heading', 'filter_params',));
    }

    public function ajax_list(Request $request)
    {
        $auth_user = Auth::guard('Sales')->user();
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
            'cases.past_hospital',
            'cases.past_diagnosis',
            'cases.bill_range',
            'cases.sum_insured',
            'cases.tpa',
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
                }elseif ($request->dashboard_filters == "post_claim_cases") {
                    $cases->where('is_post_1', 1)->where('is_post_2', 0);
                }elseif ($request->dashboard_filters == "post_two_claim_cases") {
                    $cases->where('is_post_1', 2);
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
                return '<a href="' . route('sales.case.show', CaseIdEncryption::routeParam($case->id)) . '" class="btn btn-info">View</a>';
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
        return view('sales.case.show', compact('case', 'tpa_roles'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'doa' => 'required|date',
            'doa_time' => 'required',
            'dod' => 'required|date',
            'dod_time' => 'required',
            'hospital' => 'nullable',
            'diagnosis' => 'nullable',
        ]);

        $case = Cases::findOrFail($id);

        $case->doa = $request->doa;
        $case->doa_time = $request->doa_time;
        $case->dod = $request->dod;
        $case->dod_time = $request->dod_time;
        $case->hospital = $request->hospital;
        $case->diagnosis = $request->diagnosis;

        if($case->save()){

            $user = Auth::user();
            $today = Carbon::now()->format('Y-m-d');

            $role = session('logged_role');
            $roleName = Role::where('id', $role)->first()->name;

            $auditResult = AuditTrail::createOrUpdateAuditTrail($case->id, $user->id, $user->f_name, 2, 'Sales', 'Main');

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
            

            $doctor_users = User::whereRaw("FIND_IN_SET(?, role_id)", [3])->get();

            $next_member_assign = $doctor_users->where('is_next', true)->first();

            if (!$next_member_assign) {
                $next_member_assign = $doctor_users->first();
            }

            $case->doctor_assigned = "$next_member_assign->f_name $next_member_assign->f_name";
            $case->assign_member_id = $next_member_assign->id;
            $case->is_working_row = 0;


            $case->assign_member_role = 3; // main code

            $case->save();

            User::whereRaw("FIND_IN_SET(?, role_id)", [3])->update(['is_next' => false]);

            $next_member_index = $doctor_users->search($next_member_assign);
            $next_member_index = ($next_member_index + 1) % $doctor_users->count();
            $next_user = $doctor_users->get($next_member_index);
            $next_user->is_next = true;
            $next_user->save();
        }

        return response()->json([
            'success' => true,
            'message' => 'Case updated successfully!',
        ]);
    }
}
