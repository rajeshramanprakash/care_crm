<?php

namespace App\Http\Controllers\Dispatcher;

use App\Http\Controllers\Controller;
use App\Models\AuditTrail;
use App\Models\Cases;
use App\Models\Courier;
use App\Models\Productivity;
use App\Models\Role;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Helpers\CaseIdEncryption;

class CaseController extends Controller
{
    public function index($dashboard_filters = null)
    {
        Log::info('yash');
        $page_heading = 'Cases';
        $filter_params = "";
        if ($dashboard_filters !== null) {
            $filter_params = ['dashboard_filters' => $dashboard_filters];
            $page_heading = ucwords(str_replace("_", " ", $dashboard_filters));
        }
        if($dashboard_filters == null){
            return redirect()->route('dispatcher.dashboard');
        }
        return view('dispatcher.case.index', compact('page_heading', 'filter_params'));
    }

    public function ajax_list(Request $request)
    {
        $auth_user = Auth::guard('Dispatcher')->user();
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
            'cases.ipd_no_entry',
            'cases.hospital',
            'cases.diagnosis',
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
            ->distinct();

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
                return '<a href="' . route('dispatcher.case.show', CaseIdEncryption::routeParam($case->id)) . '" class="btn btn-info">View</a>';
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
        return view('dispatcher.case.show', compact('case', 'tpa_roles'));
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

    public function update(Request $request, $id)
    {
        $request->validate([
            'pre_courier_no' => 'required|string',
            'pre_courier_date' => 'required|date',
            'pre_dispatch_pdf_attachment' => 'required|file|mimes:jpg,jpeg,png,pdf,xls,webp,xlsx,docx,doc|max:15360',
        ]);
        
        Log::info($request->all());

        $case = Cases::findOrFail($id);

        $case->pre_courier_no = $request->pre_courier_no;
        $case->pre_courier_date = $request->pre_courier_date;
        $case->post_one_allow = 1;

        $courier = new Courier();
        $courier->case_type = 'main';
        $courier->pod_no = $request->pod_no;
        $courier->case_id = $case->id;
        $courier->save();

        if ($request->hasFile('pre_dispatch_pdf_attachment')) {
    try {
        $file = $request->file('pre_dispatch_pdf_attachment');
        Log::info('Uploading file', [
            'original_name' => $file->getClientOriginalName(),
            'size' => $file->getSize(),
            'extension' => $file->getClientOriginalExtension(),
        ]);

        $path = $file->store('attachments', 'public');
        $case->pre_dispatch_pdf_attachment = $path;
    } catch (\Exception $e) {
        \Log::error('File upload failed: ' . $e->getMessage());
        return response()->json([
            'success' => false,
            'message' => 'File upload failed.',
            'error' => $e->getMessage(),
        ], 500);
    }
}


        if($case->save()){
            $user = Auth::user();
            $today = Carbon::now()->format('Y-m-d');

            $role = session('logged_role');
            $roleName = Role::where('id', $role)->first()->name;

            $auditResult = AuditTrail::createOrUpdateAuditTrail($case->id, $user->id, $user->f_name, 7, 'Dispatch', 'Main');

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

            $postsales = User::whereRaw("FIND_IN_SET(?, role_id)", [9])->get();
            $next_member_assign = $postsales->where('is_next', true)->first();

            if (!$next_member_assign) {
                $next_member_assign = $postsales->first(); // Get the first member
            }

            $case->assign_member_post_sales = $next_member_assign->id;
            $case->assign_member_id = $next_member_assign->id;
            $case->assign_member_post = $next_member_assign->id;
            $case->is_working_row = 0;
            $case->assign_member_role = 9;
            $case->save();

            // Reset is_next for all members
            User::whereRaw("FIND_IN_SET(?, role_id)", [9])->update(['is_next' => false]);

            // Set is_next to true for the next  member in the list
            $next_member_index = $postsales->search($next_member_assign); // Get the index of the current member
            $next_member_index = ($next_member_index + 1) % $postsales->count(); // Move to the next member or reset to the first one
            $next_user = $postsales->get($next_member_index);
            $next_user->is_next = true;
            $next_user->save();

            // Group message for post two
            $groupVendorData = \App\Models\User::where('id', $case->created_by)->first();
            if ($groupVendorData && $groupVendorData->vendor_case_group && $case->is_post_1 == 0) {
                $group = \App\Models\Group::find($groupVendorData->vendor_case_group);
                if ($group) {
                    $group->groupMessages()->create([
                        'sender_id' => 1,
                        'message' => "Confirmation for the Case ID: {$case->case_code}, Name: {$case->name} to make Post. <a href='" . route('vendor.case.notification_view', [$case->case_code, 1]) . "' class='btn btn-primary btn-sm' style='margin-left:10px;'>Yes</a>",
                    ]);
                }
            }

            // Group message for post
            $groupVendorData = \App\Models\User::where('id', $case->created_by)->first();
            if ($groupVendorData && $groupVendorData->vendor_case_group && $case->is_post_1 == 1) {
                $group = \App\Models\Group::find($groupVendorData->vendor_case_group);
                if ($group) {
                    $group->groupMessages()->create([
                        'sender_id' => 1,
                        'message' => "Confirmation for the Case ID: {$case->case_code}, Name: {$case->name} to make Post Two. <a href='" . route('vendor.case.notification_view', [$case->case_code, 2]) . "' class='btn btn-primary btn-sm' style='margin-left:10px;'>Yes</a>",
                    ]);
                }
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Case updated successfully!',
        ]);
    }
    public function update_post_one(Request $request, $id)
    {
        $request->validate([
            'post_courier_no' => 'required|string',
            'post_courier_date' => 'required|date',
            'post_dispatch_pdf_attachment' => 'required|file|mimes:jpg,jpeg,png,pdf,xls,webp,xlsx,docx,doc|max:15360',
        ]);

        $case = Cases::findOrFail($id);

        $case->post_courier_no = $request->post_courier_no;
        $case->post_courier_date = $request->post_courier_date;
        $case->post_two_allow = 1;
        $case->is_working_row = 0;

        $courier = new Courier();
        $courier->case_type = 'post';
        $courier->pod_no = $request->post_pod_no;
        $courier->case_id = $case->id;
        $courier->save();

        if ($request->hasFile('post_dispatch_pdf_attachment')) {
            $case->post_dispatch_pdf_attachment = $request->file('post_dispatch_pdf_attachment')->store('attachments', 'public');
        }

        $case->assign_member_id = $case->assign_member_post_sales;
        $case->assign_member_post = $case->assign_member_post_sales;
        $case->assign_member_post_role = 9; // main code
        $case->save();
        $user = Auth::user();
        $today = Carbon::now()->format('Y-m-d');

        $role = session('logged_role');
        $roleName = Role::where('id', $role)->first()->name;

        $auditResult = AuditTrail::createOrUpdateAuditTrail($case->id, $user->id, $user->f_name, 7, 'Dispatch', 'Post one');

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

        // Group message for post two
        $groupVendorData = \App\Models\User::where('id', $case->created_by)->first();
        if ($groupVendorData && $groupVendorData->vendor_case_group) {
            $group = \App\Models\Group::find($groupVendorData->vendor_case_group);
            if ($group) {
                $group->groupMessages()->create([
                    'sender_id' => 1,
                    'message' => "Case ID {$case->case_code}, Name: {$case->name} has been forwarded for Post Two. <button class='btn btn-primary btn-sm' data-notification-case-id='{$case->id}' style='margin-left:10px;'>View Case</button>",
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Case updated successfully!',
        ]);
    }
    public function update_post_two(Request $request, $id)
    {
        $request->validate([
            'post_two_courier_no' => 'required|string',
            'post_two_courier_date' => 'required|date',
            'post_two_dispatch_pdf_attachment' => 'required|file|mimes:jpg,jpeg,png,pdf,xls,webp,xlsx,docx,doc|max:15360',
        ]);

        $case = Cases::findOrFail($id);

        $case->post_two_courier_no = $request->post_two_courier_no;
        $case->post_two_courier_date = $request->post_two_courier_date;
        $case->is_working_row = 0;

        $courier = new Courier();
        $courier->case_type = 'postTwo';
        $courier->pod_no = $request->post_two_pod_no;
        $courier->case_id = $case->id;
        $courier->save();


        if ($request->hasFile('post_two_dispatch_pdf_attachment')) {
            $case->post_two_dispatch_pdf_attachment = $request->file('post_two_dispatch_pdf_attachment')->store('attachments', 'public');
        }

        $case->assign_member_id = $case->assign_member_post_sales;
        $case->assign_member_post = $case->assign_member_post_sales;
        $case->assign_member_post_role = 9; // main code
        $case->save();
        $user = Auth::user();
            $today = Carbon::now()->format('Y-m-d');

            $role = session('logged_role');
            $roleName = Role::where('id', $role)->first()->name;

            $auditResult = AuditTrail::createOrUpdateAuditTrail($case->id, $user->id, $user->f_name, 7, 'Dispatch', 'Post two');

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


        return response()->json([
            'success' => true,
            'message' => 'Case updated successfully!',
        ]);
    }
}
