<?php

namespace App\Http\Controllers\MedicineVital;

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
        if ($dashboard_filters == null) {
            return redirect()->route('medicinevital.dashboard');
        }
        return view('medicinevital.case.index', compact('page_heading', 'filter_params',));
    }

    public function ajax_list(Request $request)
    {
        $auth_user = Auth::guard('MedicineVital')->user();
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
            'cases.hospital',
            'cases.diagnosis',
            'cases.is_priority',
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
            } elseif ($request->dashboard_filters == "post_claim_cases") {
                $cases->where('is_post_1', 1)->where('is_post_2', 0);
            } elseif ($request->dashboard_filters == "post_two_claim_cases") {
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
                return '<a href="' . route('medicinevital.case.show', CaseIdEncryption::routeParam($case->id)) . '" class="btn btn-info">View</a>';
            })
            ->addColumn('case_color', function ($case) {
                $color_code = '';
                if ($case->is_working_row) {
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
        return view('medicinevital.case.show', compact('case', 'tpa_roles'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'medicine_vitals_attached' => 'nullable|file|mimes:jpg,jpeg,png,pdf,xls,webp,xlsx,docx,doc|max:20480',
            'medicine_detail' => 'nullable|file|mimes:jpg,jpeg,png,pdf,xls,webp,xlsx,docx,doc|max:20480',
        ]);

        $case = Cases::findOrFail($id);

        if ($request->hasFile('medicine_vitals_attached')) {
            $case->medicine_vitals_attached = $request->file('medicine_vitals_attached')->store('attachments', 'public');
        }
        if ($request->hasFile('medicine_detail')) {
            $case->medicine_detail = $request->file('medicine_detail')->store('attachments', 'public');
        }

        if ($case->save()) {

            $user = Auth::user();
            $today = Carbon::now()->format('Y-m-d');

            $role = session('logged_role');
            $roleName = Role::where('id', $role)->first()->name;

            $auditResult = AuditTrail::createOrUpdateAuditTrail($case->id, $user->id, $user->f_name, 4, 'Medicine', 'Main');

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

            $bill_maker = User::whereRaw("FIND_IN_SET(?, role_id)", [5])->get();

            $next_member_assign = $bill_maker->where('is_next', true)->first();

            if (!$next_member_assign) {
                $next_member_assign = $bill_maker->first();
            }

            $case->assign_member_id = $next_member_assign->id;
            $case->is_working_row = 0;
            $case->assign_member_role = 5; // main code
            $case->save();
            User::whereRaw("FIND_IN_SET(?, role_id)", [5])->update(['is_next' => false]);

            $next_member_index = $bill_maker->search($next_member_assign);
            $next_member_index = ($next_member_index + 1) % $bill_maker->count();
            $next_user = $bill_maker->get($next_member_index);
            $next_user->is_next = true;
            $next_user->save();
        }

        return response()->json([
            'success' => true,
            'message' => 'Case updated successfully!',
        ]);
    }
}
