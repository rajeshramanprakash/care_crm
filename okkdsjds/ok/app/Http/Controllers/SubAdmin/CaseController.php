<?php

namespace App\Http\Controllers\SubAdmin;

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
        $vendors = User::whereRaw("FIND_IN_SET(?, role_id)", [10])->get();
        $tpa_roles = User::whereRaw("FIND_IN_SET(?, role_id)", [8])->get();
        $filter_params = "";
        if ($dashboard_filters !== null) {
            $filter_params = ['dashboard_filters' => $dashboard_filters];
            $page_heading = ucwords(str_replace("_", " ", $dashboard_filters));
        }


        return view('subadmin.case.index', compact('page_heading', 'filter_params', 'vendors', 'tpa_roles'));
    }

    public function store(Request $request)
    {
        $auth_user = Auth::guard('Admin')->user();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'member_id' => 'required|string|max:255',
            'age' => 'required|integer',
            'gender' => 'required|in:Male,Female,Other',
            'doa' => 'nullable|date',
            'doa_time' => 'nullable|date_format:H:i',
            'dod' => 'nullable|date',
            'dod_time' => 'nullable|date_format:H:i',
            'corp' => 'nullable|string|max:255',
            'relation' => 'nullable|string|max:255',
            'aadhar_attachment' => 'nullable|file|mimes:jpg,jpeg,png,pdf,xls,webp,xlsx,docx,doc|max:20480',
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
        return response()->json(['success' => true, 'message' => 'Case created successfully!']);
    }

    public function ajax_list(Request $request)
    {
        $auth_user = Auth::guard('Admin')->user();
        $cases = Cases::select([
            'cases.id',
            'cases.case_code',
            'cases.name',
            'cases.age',
            'cases.member_id',
            'cases.corp',
            'cases.hospital',
            'cases.relation',
            'cases.gender',
            'cases.doa',
            'cases.doa_time',
            'cases.dod',
            'cases.dod_time',
            'cases.created_by',
            'cases.is_priority',
            'cases.forward_status',
            'cases.assign_member_id',
            'cases.forward_status_remark',
            'cases.status',
            'cases.approved_date',
            'cases.paid_date',
            'cases.diagnosis',
            'cases.approved_amt',

            'cases.post_status',
            'cases.post_approved_date',
            'cases.post_paid_date',
            'cases.post_ammount',

            'cases.status',
            'cases.post_two_approved_date',
            'cases.post_two_paid_date',
            'cases.post_two_ammount',

            'cases.claim_no',
            'cases.post_claim_no',
            'cases.post_two_claim_no',

            'cases.claim_no_link',
            'cases.post_claim_no_link',
            'cases.post_two_claim_no_link',
            'cases.member_id',

            'cases.tpa_type',
            'cases.tpa_allot_after_claim_no_received',
            'cases.tpa_allot_after_claim_no_received_two',

            'cases.post_tpa_type',
            'cases.post_tpa_allot_after_claim_no_received',
            'cases.post_tpa_allot_after_claim_no_received_two',

            'cases.post_two_tpa_type',
            'cases.post_tpa_allot_after_claim_no_received_two',
            'cases.post_two_tpa_allot_after_claim_no_received_two',

            'cases.assign_member_role',
            'cases.assign_member_post_role',
            'cases.pre_courier_no',

        ])
            ->with(['user:id,f_name,role_id'])
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
            });

        if ($request->has('vendor_member') && $request->vendor_member != '') {
            $cases->whereIn('cases.created_by', $request->vendor_member);
        }

        // if ($request->dashboard_filters != null) {
            // if ($request->dashboard_filters == "main_claim_cases") {
                $cases->where('is_post_1', 0)->where(['forward_status' => 0, 'forward_status_remark' => null])->where('is_post_1', 0)->where('is_post_1', 0);
            // }
        // }

        return dataTables()->of($cases)
            ->addColumn('created_by', function ($case) {
                return $case->user ? $case->user->f_name : 'N/A';
            })
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
            })
            ->addColumn('post_case_department', function ($case) {
                $query_status = 'Pending';
                    if ($case->assign_member_post_role) {
                        switch ($case->assign_member_post_role) {
                            case 2:
                                $query_status = 'Forwarded To Sales Department';
                                break;
                            case 3:
                                $query_status = 'Forwarded To Doctor Department';
                                break;
                            case 4:
                                $query_status = 'Forwarded To Medical Department';
                                break;
                            case 5:
                                $query_status = 'Forwarded To Billing Department';
                                break;
                            case 6:
                                $query_status = 'Forwarded To Lab Department';
                                break;
                            case 7:
                                $query_status = 'Forwarded To Dispatch Department';
                                break;
                            case 9:
                                $query_status = "Case Status: " . ($case->status ?? 'Processing');
                                break;
                        }
                    }
                return $query_status;
            })
            ->addColumn('case_color', function ($case) {
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
                } elseif ($case->status == 'Approved' || $case->status == 'InProcess' ||  $case->status == 'Paid') {
                    $color_code = '#28A745';
                }
                return $color_code;
            })
            ->addColumn('text_color', function ($case) {
                $text_color = '';
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
                return '<a href="' . route('subadmin.case.show', CaseIdEncryption::routeParam($case->id)) . '" class="btn btn-info">View</a>';
            })
            ->make(true);
    }

    public function setPriority(Request $request, $id)
    {
        $case = Cases::findOrFail($id);
        $case->is_priority = $request->input('is_priority') ? 1 : 0;
        $case->save();

        return response()->json(['message' => 'Priority status updated successfully.']);
    }

    public function show($encryptedId)
    {
        $id = CaseIdEncryption::decrypt($encryptedId);
        $case = Cases::findOrFail($id);
        $tpa_roles = User::whereRaw("FIND_IN_SET(?, role_id)", [8])->get();
        $cases_with_same_memeber_id = Cases::select('id', 'case_code')->where('member_id', $case->member_id)->where('id', '!=', $case->id)->get();
        $query_status = '';


        if ($case->forward_status == 0 && $case->forward_status_remark == null) {
            $query_status = 'Pending';
        } else if ($case->forward_status == 1) {
            if ($case->assign_member_role == 2) {
                $query_status = 'Forwarded To Sales Department';
            } elseif ($case->assign_member_role == 3) {
                $query_status = 'Forwarded To Doctor Department';
            } elseif ($case->assign_member_role == 4) {
                $query_status = 'Forwarded To Medical Department';
            } elseif ($case->assign_member_role == 5) {
                $query_status = 'Forwarded To Billing Department';
            } elseif ($case->assign_member_role == 6) {
                $query_status = 'Forwarded To Lab Department';
            } elseif ($case->assign_member_role == 7) {
                $query_status = 'Forwarded To Dispatch Department';
            } elseif ($case->assign_member_role == 9) {
                $query_status = "Case Status: " . ($case->status ?? 'Processing');
            }
        } else if ($case->forward_status == 0 && $case->forward_status_remark !== null) {
            $query_status = "Hold -- Reason: $case->forward_status_remark";
        } elseif ($case->forward_status == 2 && $case->forward_status_remark !== null) {
            $query_status = "Cancelled -- Reason: $case->forward_status_remark";
        }

        return view('subadmin.case.show', compact('case', 'tpa_roles', 'query_status', 'cases_with_same_memeber_id'));
    }

    public function delete($id)
    {
        $case = Cases::findOrFail($id);
        $case->delete();
        session()->flash('status', ['success' => true, 'alert_type' => 'success', 'message' => "Case deleted."]);
        return redirect()->back();
    }

    public function cases_status_update($case_id, $status)
    {
        $case = Cases::findOrFail($case_id);

        $case->forward_status = $status;

        if ($status == 1) {
            // Fetch all sales members with role_id = 2
            $sales_users = User::whereRaw("FIND_IN_SET(?, role_id)", [2])->get();

            // Find the next available sales member with is_next = true
            $next_sales_member = $sales_users->where('is_next', true)->first();

            // If no member is marked as next, restart and assign the first one
            if (!$next_sales_member) {
                $next_sales_member = $sales_users->first(); // Get the first member
            }

            // Assign this member to the case
            $case->assign_member_id = $next_sales_member->id;
            $case->assign_member_role = 2;
            $case->save();

            // Reset is_next for all members
            User::whereRaw("FIND_IN_SET(?, role_id)", [2])->update(['is_next' => false]);

            // Set is_next to true for the next sales member in the list
            $next_member_index = $sales_users->search($next_sales_member); // Get the index of the current member
            $next_member_index = ($next_member_index + 1) % $sales_users->count(); // Move to the next member or reset to the first one
            $next_sales_user = $sales_users->get($next_member_index); // Get the next sales member
            $next_sales_user->is_next = true;
            $next_sales_user->save(); // Save the is_next flag
        }
        $case->forward_status_remark = null;
        $case->save();
        session()->flash('status', ['success' => true, 'alert_type' => 'success', 'message' => "Status updated."]);
        return redirect()->back();
    }

    public function cases_status_remark(Request $request)
    {
        $case = Cases::findOrFail($request->id);
        $case->forward_status = 2;
        $case->forward_status_remark = $request->remark;
        $case->save();
        session()->flash('status', ['success' => true, 'alert_type' => 'success', 'message' => "Status updated."]);
        return redirect()->back();
    }

    public function admin_cases_status_remark_hold(Request $request)
    {
        $case = Cases::findOrFail($request->id);
        $case->forward_status = 0;
        $case->forward_status_remark = $request->remark;
        $case->save();
        session()->flash('status', ['success' => true, 'alert_type' => 'success', 'message' => "Status updated."]);
        return redirect()->back();
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
            'doa' => 'nullable|date',
            'doa_time' => 'nullable',
            'dod' => 'nullable|date',
            'dod_time' => 'nullable',
            'sum_insured' => 'nullable',
            'bill_range' => 'nullable',
            'past_hospital' => 'nullable',
            'past_diagnosis' => 'nullable',
            'hospital' => 'nullable',
            'diagnosis' => 'nullable',
            'tpa' => 'nullable',
            'tpa_allot_after_claim_no_received' => 'nullable',
            'claim_no' => 'nullable',
            'tpa_type' => 'nullable',
            'tpa_allot_after_claim_no_received_two' => 'nullable',
            'aadhar_attachment' => 'nullable|file|mimes:jpg,jpeg,png,pdf,xls,webp,xlsx,docx,doc|max:20480',
            'aadhar_attachment_2' => 'nullable|file|mimes:jpg,jpeg,png,pdf,xls,webp,xlsx,docx,doc|max:20480',
            'pan_card' => 'nullable|file|mimes:jpg,jpeg,png,pdf,xls,webp,xlsx,docx,doc|max:20480',
            'cancelled_cheque' => 'nullable|file|mimes:jpg,jpeg,png,pdf,xls,webp,xlsx,docx,doc|max:20480',
            'policy' => 'nullable|file|mimes:jpg,jpeg,png,pdf,xls,webp,xlsx,docx,doc|max:20480',
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
        $case->member_id = $request->member_id;
        $case->sum_insured = $request->sum_insured;
        $case->bill_range = $request->bill_range;
        $case->past_hospital = $request->past_hospital;
        $case->past_diagnosis = $request->past_diagnosis;
        $case->hospital = $request->hospital;
        $case->diagnosis = $request->diagnosis;
        $case->tpa = $request->tpa;
        $case->tpa_allot_after_claim_no_received = $request->tpa_allot_after_claim_no_received;
        $case->tpa_type = $request->tpa_type;
        $case->tpa_allot_after_claim_no_received_two = $request->tpa_allot_after_claim_no_received_two;
        $case->claim_no = $request->claim_no;

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

        $user = Auth::user();
            $today = Carbon::now()->format('Y-m-d');

            $role = session('logged_role');
            $roleName = Role::where('id', $role)->first()->name;

            $auditResult = AuditTrail::createOrUpdateAuditTrail($case->id, $user->id, $user->f_name, $role, $roleName, 'Main');

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

        $case->save();

        return response()->json([
            'success' => true,
            'message' => 'Case updated successfully!',
        ]);
    }

    public function save_tpa(Request $request)
    {
        $request->validate([
            'case_id' => 'required|exists:cases,id',
            'tpa_type' => 'nullable|string',
            'tpa_allot_after_claim_no_received' => 'nullable',
            'tpa_allot_after_claim_no_received_two' => 'nullable',
            'tpa_type' => 'nullable|string',
            'tpa_allot_after_claim_no_received' => 'nullable',
            'tpa_allot_after_claim_no_received_two' => 'nullable',
            'tpa_type' => 'nullable|string',
            'tpa_allot_after_claim_no_received' => 'nullable',
            'tpa_allot_after_claim_no_received_two' => 'nullable',
        ]);

        $case = Cases::findOrFail($request->input('case_id'));

        // Update TPA fields
        $case->tpa_type = $request->input('tpa_type');
        $case->tpa_allot_after_claim_no_received = $request->input('tpa_allot_after_claim_no_received');
        $case->tpa_allot_after_claim_no_received_two = $request->input('tpa_allot_after_claim_no_received_two');

        $case->post_tpa_type = $request->input('post_tpa_type');
        $case->post_tpa_allot_after_claim_no_received = $request->input('post_tpa_allot_after_claim_no_received');
        $case->post_tpa_allot_after_claim_no_received_two = $request->input('post_tpa_allot_after_claim_no_received_two');

        $case->post_two_tpa_type = $request->input('post_two_tpa_type');
        $case->post_two_tpa_allot_after_claim_no_received = $request->input('post_two_tpa_allot_after_claim_no_received');
        $case->post_two_tpa_allot_after_claim_no_received_two = $request->input('post_two_tpa_allot_after_claim_no_received_two');

        // Save the case
        $case->save();

        return response()->json(['message' => 'TPA allotment saved successfully.']);
    }

    public function save_claimno(Request $request)
    {
        $request->validate([
            'case_id' => 'required|exists:cases,id',
            'claim_no' => 'required|string',
            'post_claim_no' => 'nullable',
            'post_two_claim_no' => 'nullable',
        ]);

        // Retrieve the case by ID
        $case = Cases::findOrFail($request->input('case_id'));

        $case->claim_no = $request->input('claim_no');
        $case->claim_no_link = $request->input('claim_no_link');
        $case->post_claim_no = $request->input('post_claim_no');
        $case->post_claim_no_link = $request->input('post_claim_no_link');
        $case->post_two_claim_no = $request->input('post_two_claim_no');
        $case->post_two_claim_no_link = $request->input('post_two_claim_no_link');

        // Save the case
        $case->save();

        // Send group message after claim no is added, for each claim type (priority: post_two > post > main)
        $groupVendorData = \App\Models\User::where('id', $case->created_by)->first();
        if ($groupVendorData && $groupVendorData->vendor_case_group) {
            $group = \App\Models\Group::find($groupVendorData->vendor_case_group);
            if ($group) {
                if ($request->filled('post_two_claim_no')) {
                    $group->groupMessages()->create([
                        'sender_id' => 1,
                        'message' => "Post Two claim number added for Case ID {$case->case_code}, Name: {$case->name}, Post Two Claim Number: {$case->post_two_claim_no}, Post Two Claim Link: {$case->post_two_claim_no_link}",
                    ]);
                } elseif ($request->filled('post_claim_no')) {
                    $group->groupMessages()->create([
                        'sender_id' => 1,
                        'message' => "Post claim number added for Case ID {$case->case_code}, Name: {$case->name}, Post Claim Number: {$case->post_claim_no}, Post Claim Link: {$case->post_claim_no_link}",
                    ]);
                } elseif ($request->filled('claim_no')) {
                    $group->groupMessages()->create([
                        'sender_id' => 1,
                        'message' => "Main claim number added for Case ID {$case->case_code}, Name: {$case->name}, Main Claim Number: {$case->claim_no}, Main Claim Link: {$case->claim_no_link}",
                    ]);
                }
            }
        }

        return response()->json(['message' => 'Saved successfully.']);
    }

    public function update_files(Request $request, $id)
    {
        $request->validate([
            'patient_details_form' => 'nullable|file|mimes:jpg,jpeg,png,pdf,xls,webp,xlsx,docx,doc|max:15360',
            'icp_attachment' => 'nullable|file|mimes:jpg,jpeg,png,pdf,xls,webp,xlsx,docx,doc|max:15360',
            'medicine_vitals_attached' => 'nullable|file|mimes:jpg,jpeg,png,pdf,xls,webp,xlsx,docx,doc|max:15360',
            'medicine_detail' => 'nullable|file|mimes:jpg,jpeg,png,pdf,xls,webp,xlsx,docx,doc|max:15360',
            'aadhar_attachment' => 'nullable|file|mimes:jpg,jpeg,png,pdf,xls,webp,xlsx,docx,doc|max:15360',
            'aadhar_attachment_2' => 'nullable|file|mimes:jpg,jpeg,png,pdf,xls,webp,xlsx,docx,doc|max:15360',
            'pan_card' => 'nullable|file|mimes:jpg,jpeg,png,pdf,xls,webp,xlsx,docx,doc|max:15360',
            'cancelled_cheque' => 'nullable|file|mimes:jpg,jpeg,png,pdf,xls,webp,xlsx,docx,doc|max:15360',
            'policy' => 'nullable|file|mimes:jpg,jpeg,png,pdf,xls,webp,xlsx,docx,doc|max:15360',
            'bill_attachment_1' => 'nullable|file|mimes:jpg,jpeg,png,pdf,xls,webp,xlsx,docx,doc|max:15360',
            'discharge_summary_attachment' => 'nullable|file|mimes:jpg,jpeg,png,pdf,xls,webp,xlsx,docx,doc|max:15360',
        ]);
        $case = Cases::findOrFail($id);

        if ($request->hasFile('patient_details_form')) {
            $case->patient_details_form = $request->file('patient_details_form')->store('attachments', 'public');
        }
        if ($request->hasFile('icp_attachment')) {
            $case->icp_attachment = $request->file('icp_attachment')->store('attachments', 'public');
        }
        if ($request->hasFile('medicine_vitals_attached')) {
            $case->medicine_vitals_attached = $request->file('medicine_vitals_attached')->store('attachments', 'public');
        }
        if ($request->hasFile('medicine_detail')) {
            $case->medicine_detail = $request->file('medicine_detail')->store('attachments', 'public');
        }
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
        if ($request->hasFile('bill_attachment_1')) {
            $case->bill_attachment_1 = $request->file('bill_attachment_1')->store('attachments', 'public');
        }
        if ($request->hasFile('discharge_summary_attachment')) {
            $case->discharge_summary_attachment = $request->file('discharge_summary_attachment')->store('attachments', 'public');
        }

        $case->save();
        return response()->json([
            'success' => true,
            'message' => 'Case files updated successfully!',
        ]);
    }

    public function post_update(Request $request)
    {
        $request->validate([
            'case_id' => 'required|exists:cases,id',
            'post_courier_no' => 'nullable',
            'post_courier_date' => 'nullable',
            'post_claim_no' => 'nullable',
            'post_ammount' => 'nullable',
            'post_patient_details_form' => 'nullable|file',
            'post_dispatch_pdf_attachment' => 'nullable|file',
            'opd_attachment' => 'nullable|file',
            'bill_attachment_post' => 'nullable|file',
        ]);

        $case = Cases::findOrFail($request->input('case_id'));
        $case->post_courier_no = $request->input('post_courier_no');
        $case->post_courier_date = $request->input('post_courier_date');
        $case->post_claim_no = $request->input('post_claim_no');
        $case->post_ammount = $request->input('post_ammount');

        if ($request->hasFile('post_patient_details_form')) {
            $case->post_patient_details_form = $request->file('post_patient_details_form')->store('attachments', 'public');
        }
        if ($request->hasFile('post_dispatch_pdf_attachment')) {
            $case->post_dispatch_pdf_attachment = $request->file('post_dispatch_pdf_attachment')->store('attachments', 'public');
        }
        if ($request->hasFile('opd_attachment')) {
            $case->opd_attachment = $request->file('opd_attachment')->store('attachments', 'public');
        }
        if ($request->hasFile('bill_attachment_post')) {
            $case->bill_attachment_post = $request->file('bill_attachment_post')->store('attachments', 'public');
        }

        $case->save();

        return response()->json([
            'success' => true,
            'message' => 'Updated Successfully!',
        ]);
    }

    public function post_two_update(Request $request)
    {
        $request->validate([
            'case_id' => 'required|exists:cases,id',
            'post_two_courier_no' => 'nullable',
            'post_two_courier_date' => 'nullable',
            'post_two_claim_no' => 'nullable',
            'post_two_ammount' => 'nullable',
            'post_two_patient_details_form' => 'nullable|file',
            'post_two_dispatch_pdf_attachment' => 'nullable|file',
            'opd_attachment_2' => 'nullable|file',
            'bill_attachment_post_two' => 'nullable|file',
        ]);

        $case = Cases::findOrFail($request->input('case_id'));
        $case->post_two_courier_no = $request->input('post_two_courier_no');
        $case->post_two_courier_date = $request->input('post_two_courier_date');
        $case->post_two_claim_no = $request->input('post_two_claim_no');
        $case->post_two_ammount = $request->input('post_two_ammount');

        if ($request->hasFile('post_two_patient_details_form')) {
            $case->post_two_patient_details_form = $request->file('post_two_patient_details_form')->store('attachments', 'public');
        }
        if ($request->hasFile('post_two_dispatch_pdf_attachment')) {
            $case->post_two_dispatch_pdf_attachment = $request->file('post_two_dispatch_pdf_attachment')->store('attachments', 'public');
        }
        if ($request->hasFile('opd_attachment_2')) {
            $case->opd_attachment_2 = $request->file('opd_attachment_2')->store('attachments', 'public');
        }
        if ($request->hasFile('bill_attachment_post_two')) {
            $case->bill_attachment_post_two = $request->file('bill_attachment_post_two')->store('attachments', 'public');
        }

        $case->save();

        return response()->json([
            'success' => true,
            'message' => 'Updated Successfully!',
        ]);
    }
}
