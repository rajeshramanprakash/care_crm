<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\Cases;
use App\Models\User;
use App\Models\VendorCase;
use App\Models\Role;
use Carbon\Carbon;
use App\Models\AuditTrail;
use App\Models\Productivity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Helpers\CaseIdEncryption;

class CaseController extends Controller
{
    public function index($dashboard_filters = null)
    {
        $page_heading = 'Cases';
        $filter_params = "";
        $vendors = User::whereRaw("FIND_IN_SET(?, role_id)", [10])->get();
        $tpa_roles = User::whereRaw("FIND_IN_SET(?, role_id)", [8])->get();
        if ($dashboard_filters !== null) {
            $filter_params = ['dashboard_filters' => $dashboard_filters];
            $page_heading = ucwords(str_replace("_", " ", $dashboard_filters));
        }
        if ($dashboard_filters == 'main_claim_cases_approved' ||$dashboard_filters == 'main_claim_cases_inprocess' || $dashboard_filters == 'main_claim_cases_paid') {
            return view('vendor.case.index_two', compact('page_heading', 'filter_params', 'vendors', 'tpa_roles'));
        } elseif ($dashboard_filters == 'main_claim_cases_query' || $dashboard_filters == 'post_claim_cases_query' || $dashboard_filters == 'post_two_claim_cases_query' || $dashboard_filters == 'main_claim_cases_underprocess' || $dashboard_filters == 'post_claim_cases_underprocess' || $dashboard_filters == 'post_two_claim_cases_underprocess') {
            return view('vendor.case.index_three', compact('page_heading', 'filter_params', 'vendors', 'tpa_roles'));
        } elseif ($dashboard_filters == 'post_claim_cases_approved' ||$dashboard_filters == 'post_claim_cases_inprocess' || $dashboard_filters == 'post_claim_cases_paid') {
            return view('vendor.case.index_post', compact('page_heading', 'filter_params', 'vendors', 'tpa_roles'));
        } elseif ($dashboard_filters == 'post_two_claim_cases_approved' || $dashboard_filters == 'post_two_claim_cases_inprocess' || $dashboard_filters == 'post_two_claim_cases_paid') {
            return view('vendor.case.index_post_two', compact('page_heading', 'filter_params', 'vendors', 'tpa_roles'));
        } else {
            return view('vendor.case.index', compact('page_heading', 'filter_params', 'vendors', 'tpa_roles'));
        }
    }

    public function ajax_list(Request $request)
    {
        $auth_user = Auth::guard('Vendor')->user();

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
            'is_priority',
            'hospital',
            'status',
            'approved_date',
            'paid_date',
            'approved_amt',
            'post_status',
            'post_approved_date',
            'post_paid_date',
            'post_ammount',
            'status',
            'post_two_approved_date',
            'post_two_paid_date',
            'post_two_ammount',
            'claim_no',
            'post_claim_no',
            'post_two_claim_no',
            'member_id',
            'tpa_type',
            'tpa_allot_after_claim_no_received',
            'tpa_allot_after_claim_no_received_two',
            'post_tpa_type',
            'post_tpa_allot_after_claim_no_received',
            'post_tpa_allot_after_claim_no_received_two',
            'post_two_tpa_type',
            'post_tpa_allot_after_claim_no_received_two',
            'post_two_tpa_allot_after_claim_no_received_two',
            'forward_status',
            'assign_member_id',
            'forward_status_remark',
            'assign_member_role',
            'assign_member_post_role',
            'pre_courier_no',
        ])->where('created_by', $auth_user->id);
        if ($request->dashboard_filters != null) {

            if ($request->dashboard_filters == "main_claim_cases") {
                $cases->where('is_post_1', 0)->where('is_post_2', 0);
            } elseif ($request->dashboard_filters == "main_claim_cases_query") {
                $cases->where('is_post_1', 0)->where('is_post_2', 0)->where(['status' => 'Query']);
            } elseif ($request->dashboard_filters == "main_claim_cases_investigation") {
                $cases->where('is_post_1', 0)->where(['status' => 'Investigation']);
            } elseif ($request->dashboard_filters == "main_claim_cases_reject") {
                $cases->where('is_post_1', 0)->where(['status' => 'Reject']);
            } elseif ($request->dashboard_filters == "main_claim_cases_underprocess") {
                $cases->where('is_post_1', 0)->where(['status' => 'UnderProcess']);
            } elseif ($request->dashboard_filters == "main_claim_cases_approved") {
                $cases->where(['status' => 'Approved']);
            } elseif ($request->dashboard_filters == "main_claim_cases_inprocess") {
                $cases->where(['status' => 'InProcess']);
            } elseif ($request->dashboard_filters == "main_claim_cases_paid") {
                $cases->where(['status' => 'Paid']);
            } elseif ($request->dashboard_filters == "main_claim_cases_hold") {
                $cases->where(['forward_status' => '0'])->whereNotNull('forward_status_remark');
            } elseif ($request->dashboard_filters == "main_claim_cases_cancelled") {
                $cases->where(['forward_status' => '2'])->whereNotNull('forward_status_remark');
            } elseif ($request->dashboard_filters == "post_claim_cases") {
                $cases->where('is_post_1', 1)->where('is_post_2', 0);
            } elseif ($request->dashboard_filters == "post_claim_cases_query") {
                $cases->where('is_post_1', 1)->where(['post_status' => 'Query']);
            } elseif ($request->dashboard_filters == "post_claim_cases_investigation") {
                $cases->where('is_post_1', 1)->where(['post_status' => 'Investigation']);
            } elseif ($request->dashboard_filters == "post_claim_cases_reject") {
                $cases->where('is_post_1', 1)->where(['post_status' => 'Reject']);
            } elseif ($request->dashboard_filters == "post_claim_cases_underprocess") {
                $cases->where('is_post_1', 1)->where(['post_status' => 'UnderProcess']);
            } elseif ($request->dashboard_filters == "post_claim_cases_approved") {
                $cases->where('is_post_1', 1)->where(['post_status' => 'Approved']);
            } elseif ($request->dashboard_filters == "post_claim_cases_inprocess") {
                $cases->where('is_post_1', 1)->where(['post_status' => 'InProcess']);
            } elseif ($request->dashboard_filters == "post_claim_cases_paid") {
                $cases->where('is_post_1', 1)->where(['post_status' => 'Paid']);
            } elseif ($request->dashboard_filters == "post_two_claim_cases") {
                $cases->where('is_post_2', 1);
            } elseif ($request->dashboard_filters == "post_two_claim_cases_query") {
                $cases->where('is_post_2', 1)->where(['post_two_status' => 'Query']);
            } elseif ($request->dashboard_filters == "post_two_claim_cases_investigation") {
                $cases->where('is_post_2', 1)->where(['post_two_status' => 'Investigation']);
            } elseif ($request->dashboard_filters == "post_two_claim_cases_reject") {
                $cases->where('is_post_2', 1)->where(['post_two_status' => 'Reject']);
            } elseif ($request->dashboard_filters == "post_two_claim_cases_underprocess") {
                $cases->where('is_post_2', 1)->where(['post_two_status' => 'UnderProcess']);
            } elseif ($request->dashboard_filters == "post_two_claim_cases_approved") {
                $cases->where('is_post_2', 1)->where(['post_two_status' => 'Approved']);
            } elseif ($request->dashboard_filters == "post_two_claim_cases_inprocess") {
                $cases->where('is_post_2', 1)->where(['post_two_status' => 'InProcess']);
            } elseif ($request->dashboard_filters == "post_two_claim_cases_paid") {
                $cases->where('is_post_2', 1)->where(['post_two_status' => 'Paid']);
            } elseif ($request->dashboard_filters == "pending_activity_cases") {
                $cases->where(function ($query) {
                    $query->whereNotNull('pre_courier_no')
                        ->orWhereNotNull('pre_dispatch_pdf_attachment');
                })
                    ->where('is_post_1', 0)
                    ->orWhere(function ($query) {
                        $query->whereNotNull('post_courier_no')
                            ->orWhereNotNull('post_dispatch_pdf_attachment')
                            ->where('is_post_2', 0);
                    });
            }
        }

        return dataTables()->of($cases)
            ->addColumn('encrypted_id', function ($case) {
                return CaseIdEncryption::routeParam($case->id);
            })
            ->addColumn('case_department', function ($case) {
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
            })->addColumn('case_color', function ($case) {
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
            })
            ->addColumn('text_color', function ($case) {
                $text_color = ''; // Default text color for better contrast (white)
                if ($case->status == 'Query') {
                    $text_color = '#000000'; // Black text for better contrast
                } elseif ($case->status == 'Investigation') {
                    $text_color = '#000000'; // Black text for better contrast
                } elseif ($case->status == 'Reject') {
                    $text_color = '#FFFFFF'; // White text for better contrast
                } elseif ($case->status == 'UnderProcess') {
                    $text_color = '#000000'; // Black text for better contrast
                } elseif ($case->status == 'Approved' || $case->status == 'Paid') {
                    $text_color = '#FFFFFF'; // White text for better contrast
                }
                if (($case->forward_status == 0 || $case->forward_status == 2) && $case->forward_status_remark !== null) {
                    $text_color = '#FFFFFF'; // White text for better contrast
                }

                return $text_color;
            })
            ->addColumn('actions', function ($case) {
                return '<a href="' . route('vendor.case.show', CaseIdEncryption::routeParam($case->id)) . '" class="btn btn-info">View</a>';
            })
            ->make(true);
    }

    public function paid_cases_ajax(Request $request)
    {
        $auth_user = Auth::guard('Vendor')->user();
        $cases = VendorCase::where('user_id', $auth_user->id)->get();
        return datatables()->of($cases)->make(true);
    }

    public function store(Request $request)
    {
        $auth_user = Auth::guard('Vendor')->user();

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

        $validated['created_by'] = $auth_user->id;

        $case = Cases::create($validated);

        $case->case_code = "case_000$case->id";


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



        $auditResult = AuditTrail::createOrUpdateAuditTrail($case->id, $auth_user->id, $auth_user->f_name, 10, 'Vendor', 'Main');

        // Only increment productivity file_count if a new audit trail was created
        if ($auditResult['is_new']) {
            $today = Carbon::now()->format('Y-m-d');
            $role = session('logged_role');
            $roleName = Role::where('id', $role)->first()->name;
            $productivity = Productivity::firstOrNew([
                'user_id' => $auth_user->id,
                'role_id' => $role,
                'date' => $today,
            ]);
            $productivity->role_name = $roleName;
            $productivity->file_count += 1;
            $productivity->save();
        }



        return response()->json(['success' => true, 'message' => 'Case created successfully!']);
    }

    public function notification_view($case_code, $type)
    {
        $case = Cases::where('case_code', $case_code)->first();
        if ($case) {
            if ($type == 1) {
                $case->post_one_allow = null;
            } elseif ($type == 2) {
                $case->post_two_allow = null;
            } elseif ($type == 3) {
                $case->is_query = null;
            } elseif ($type == 4) {
                $case->is_query_post = null;
            } elseif ($type == 5) {
                $case->is_query_post_two = null;
            }
            $case->save();
            return redirect()->route('vendor.case.show', ['id' => $case->id]);
        }
        return redirect()->route('vendor.dashboard')->with('error', 'Case not found');
    }


    public function show($encryptedId)
    {
        $id = CaseIdEncryption::decrypt($encryptedId);
        $case = Cases::findOrFail($id);
        $assign_member = User::where('id', $case->assign_member_id)->first();

        $query_status = '';
        // if ($case->forward_status == 0 && $case->forward_status_remark == null) {
        //     $query_status = 'Pending';
        // } else if ($case->forward_status == 1) {
        //     $user = User::where('id', $case->assign_member_id)->first();
        //     if ($user->role_id == 2) {
        //         $query_status = 'Forwarded To Sales Department';
        //     } elseif ($user->role_id == 3) {
        //         $query_status = 'Forwarded To Doctor Department';
        //     } elseif ($user->role_id == 4) {
        //         $query_status = 'Forwarded To Medical Department';
        //     } elseif ($user->role_id == 5) {
        //         $query_status = 'Forwarded To Billing Department';
        //     } elseif ($user->role_id == 6) {
        //         $query_status = 'Forwarded To Lab Department';
        //     } elseif ($user->role_id == 7) {
        //         $query_status = 'Forwarded To Dispatch Department';
        //     } elseif ($user->role_id == 9) {
        //         $query_status = "Case Status: " . ($case->status ?? 'Processing');
        //     }
        // } else if ($case->forward_status == 0 && $case->forward_status_remark !== null) {
        //     $query_status = "Hold -- Reason: $case->forward_status_remark";
        // } elseif ($case->forward_status == 2 && $case->forward_status_remark !== null) {
        //     $query_status = "Cancelled -- Reason: $case->forward_status_remark";
        // }

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

            if ($case->pre_courier_no) {
                $query_status = "Case Status: " . ($case->status ?? 'Processing');
            }
        } else if ($case->forward_status == 0 && $case->forward_status_remark !== null) {
            $query_status = "Hold -- Reason: $case->forward_status_remark";
        } elseif ($case->forward_status == 2 && $case->forward_status_remark !== null) {
            $query_status = "Cancelled -- Reason: $case->forward_status_remark";
        }

        return view('vendor.case.show', compact('case', 'query_status', 'assign_member'));
    }

    public function update(Request $request, $id)
    {
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
            'aadhar_attachment' => 'required|file|mimes:jpg,jpeg,png,pdf,xls,webp,xlsx,docx,doc|max:20480',
            'aadhar_attachment_2' => 'required|file|mimes:jpg,jpeg,png,pdf,xls,webp,xlsx,docx,doc|max:20480',
            'pan_card' => 'required|file|mimes:jpg,jpeg,png,pdf,xls,webp,xlsx,docx,doc|max:20480',
            'cancelled_cheque' => 'required|file|mimes:jpg,jpeg,png,pdf,xls,webp,xlsx,docx,doc|max:20480',
            'policy' => 'required|file|mimes:jpg,jpeg,png,pdf,xls,webp,xlsx,docx,doc|max:20480',
        ]);

        $case = Cases::findOrFail($id);

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

        return response()->json([
            'success' => true,
            'message' => 'Case updated successfully!',
        ]);
    }

    public function should_create_post_1($case_id = 0, $status = 0)
    {
        $case = Cases::findOrFail($case_id);
        if ($status == 1) {
            $case->is_post_1 = 1;
        } else {
            $case->is_post_1 = 2;
        }
        if ($case->save()) {
            $doctor_users = User::whereRaw("FIND_IN_SET(?, role_id)", [3])->get();

            $next_member_assign = $doctor_users->where('is_next', true)->first();

            if (!$next_member_assign) {
                $next_member_assign = $doctor_users->first();
            }

            $case->doctor_assigned = "$next_member_assign->f_name $next_member_assign->f_name";
            $case->assign_member_post = $next_member_assign->id;
            $case->assign_member_post_role = 3; // main code
            $case->save();

            User::whereRaw("FIND_IN_SET(?, role_id)", [3])->update(['is_next' => false]);

            $next_member_index = $doctor_users->search($next_member_assign);
            $next_member_index = ($next_member_index + 1) % $doctor_users->count();
            $next_user = $doctor_users->get($next_member_index);
            $next_user->is_next = true;
            $next_user->save();
        }
        return redirect()->back();
    }

    public function should_create_post_2($case_id = 0, $status = 0)
    {
        $case = Cases::findOrFail($case_id);
        if ($status == 1) {
            $case->is_post_2 = 1;
        } else {
            $case->is_post_2 = 2;
        }
        if ($case->save()) {
            $doctor_users = User::whereRaw("FIND_IN_SET(?, role_id)", [3])->get();

            $next_member_assign = $doctor_users->where('is_next', true)->first();

            if (!$next_member_assign) {
                $next_member_assign = $doctor_users->first();
            }

            $case->doctor_assigned = "$next_member_assign->f_name $next_member_assign->f_name";
            $case->assign_member_post = $next_member_assign->id;
            $case->assign_member_post_role = 3;
            $case->save();

            User::whereRaw("FIND_IN_SET(?, role_id)", [3])->update(['is_next' => false]);

            $next_member_index = $doctor_users->search($next_member_assign);
            $next_member_index = ($next_member_index + 1) % $doctor_users->count();
            $next_user = $doctor_users->get($next_member_index);
            $next_user->is_next = true;
            $next_user->save();
        }
        return redirect()->back();
    }

    public function update_vendor_paid_case(Request $request){
        $case = VendorCase::findOrFail($request->case_id);
        $case->is_marked_vendor = $request->is_marked_vendor;
        $case->save();
        return response()->json(['success' => true, 'message' => 'Updated successfully']);
    }
}
