<?php

namespace App\Http\Controllers\PostSales;

use App\Http\Controllers\Controller;
use App\Models\Cases;
use App\Models\Query;
use App\Models\TpaCase;
use App\Models\User;
use App\Models\VendorCase;
use App\Models\Ticket;
use App\Services\TicketService;
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
            return view('postsales.case.index_two', compact('page_heading', 'filter_params', 'vendors', 'tpa_roles'));
        }elseif ($dashboard_filters == 'main_claim_cases_query' ||$dashboard_filters == 'main_claim_cases_investigation' || $dashboard_filters == 'post_claim_cases_query' ||$dashboard_filters == 'post_claim_cases_investigation' || $dashboard_filters == 'post_two_claim_cases_query'|| $dashboard_filters == 'post_two_claim_cases_investigation'|| $dashboard_filters == 'main_claim_cases_underprocess'|| $dashboard_filters == 'post_claim_cases_underprocess'|| $dashboard_filters == 'post_two_claim_cases_underprocess'){
            return view('postsales.case.index_three', compact('page_heading', 'filter_params', 'vendors', 'tpa_roles'));
        } elseif ($dashboard_filters == 'post_claim_cases_approved' ||$dashboard_filters == 'post_claim_cases_inprocess' || $dashboard_filters == 'post_claim_cases_paid') {
            return view('postsales.case.index_post', compact('page_heading', 'filter_params', 'vendors', 'tpa_roles'));
        } elseif ($dashboard_filters == 'post_two_claim_cases_approved' ||$dashboard_filters == 'post_two_claim_cases_inprocess' || $dashboard_filters == 'post_two_claim_cases_paid') {
            return view('postsales.case.index_post_two', compact('page_heading', 'filter_params', 'vendors', 'tpa_roles'));
        } else {
            return view('postsales.case.index', compact('page_heading', 'filter_params', 'vendors', 'tpa_roles'));
        }
    }

    public function ajax_list(Request $request)
    {
        $auth_user = Auth::guard('PostSales')->user();
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
            'cases.post_two_status',
            'cases.post_approved_date',
            'cases.post_paid_date',
            'cases.post_ammount',

            'cases.post_two_approved_date',
            'cases.post_two_paid_date',
            'cases.post_two_ammount',

            'cases.claim_no',
            'cases.post_claim_no',
            'cases.post_two_claim_no',

            'cases.claim_no_link',
            'cases.post_claim_no_link',
            'cases.post_two_claim_no_link',

            'cases.tpa_type',
            'cases.tpa_allot_after_claim_no_received',
            'cases.tpa_allot_after_claim_no_received_two',

            'cases.post_tpa_type',
            'cases.post_tpa_allot_after_claim_no_received',
            'cases.post_tpa_allot_after_claim_no_received_two',

            'cases.post_two_tpa_type',
            'cases.post_two_tpa_allot_after_claim_no_received',
            'cases.post_two_tpa_allot_after_claim_no_received_two',

            'cases.assign_member_role',
            'cases.assign_member_post_role',
            'cases.pre_courier_no',
        ])->groupBy('cases.id')
            ->with(['user:id,f_name'])
            ->where('cases.forward_status', 1)
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

        if ($request->dashboard_filters != null) {

            if ($request->dashboard_filters == "main_claim_cases") {
                $cases->where('is_post_1', 0)->where('is_post_2', 0);
            } elseif ($request->dashboard_filters == "main_claim_cases_query") {
                $cases->where('is_post_1', 0)->where(['status' => 'Query']);
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
            }
        }

        return dataTables()->of($cases)
            ->addColumn('created_by', function ($case) {
                return $case->user ? $case->user->f_name : 'N/A';
            })
            ->addColumn('encrypted_id', function ($case) {
                return CaseIdEncryption::routeParam($case->id);
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
                } elseif ($case->status == 'Approved' || $case->status == 'InProcess'  || $case->status == 'Paid') {
                    $color_code = '#28A745';
                }
                return $color_code;
            })
            ->addColumn('text_color', function ($case) {
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
            })
            ->addColumn('actions', function ($case) {
                return '<a href="' . route('postsales.case.show', CaseIdEncryption::routeParam($case->id)) . '" class="btn btn-info">View</a>';
            })
            ->make(true);
    }

    public function show($encryptedId)
    {
        $id = CaseIdEncryption::decrypt($encryptedId);
        $case = Cases::findOrFail($id);
        $tpa_roles = User::whereRaw("FIND_IN_SET(?, role_id)", [8])->get();
        return view('postsales.case.show', compact('case', 'tpa_roles'));
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

    public function query_add(Request $request)
    {
        $request->validate([
            'query' => 'required|string',
            'case_id' => 'required|integer',
            'query_pdf' => 'nullable|file|mimes:jpg,jpeg,png,pdf,xls,webp,xlsx,docx,doc|max:20480',
        ]);

        $auth_user = Auth::guard('PostSales')->user();

        $query = new Query();
        $query->case_id = $request->input('case_id');
        $query->created_by = $auth_user->id;
        $query->query = $request->input('query');

        // Get case data first
        $case = \App\Models\Cases::find($query->case_id);

        if ($request->hasFile('query_pdf')) {
            $filePath = $request->file('query_pdf')->store('attachments', 'public');
            $query->query_pdf = $filePath;

            $groupVendorData = \App\Models\User::where('id', $case->created_by)->first();
            $group = \App\Models\Group::find($groupVendorData->vendor_case_group);
            $grp_case_code = $case->case_code;
            $grp_case_name = $case->name;
            if (!empty($case->post_two_claim_no)) {
                $grp_case_claim_no = $case->post_two_claim_no;
            } elseif (!empty($case->post_claim_no)) {
                $grp_case_claim_no = $case->post_claim_no;
            } else {
                $grp_case_claim_no = $case->claim_no;
            }
            if ($group) {
                $group->groupMessages()->create([
                    'sender_id' => 1,
                    'message' => "Query ready for Case ID $grp_case_code, Name: $grp_case_name, Claim Number: $grp_case_claim_no. Query PDF has been uploaded.",
                ]);
            }
        }

        try {
            $query->save();

            if ($request->hasFile('query_pdf')) {
                $this->createTicketForQuery($query, $case);
                $message = "Query Created and Ticket Generated.";
            } else {
                $message = "Query Created Successfully.";
            }

            session()->flash('status', [
                'success' => true,
                'alert_type' => 'success',
                'message' => $message,
            ]);
        } catch (\Exception $e) {
            session()->flash('status', [
                'success' => false,
                'alert_type' => 'error',
                'message' => "Internal server error occurred.",
            ]);
        }

        return redirect()->back();
    }

    /**
     * Create ticket for query
     */
    private function createTicketForQuery($query, $case)
    {
        try {
            \Log::info('Starting ticket creation for query: ' . $query->id);

            $ticketService = app(\App\Services\TicketService::class);

            // Get vendor
            $vendor = User::find($case->created_by);

            if (!$vendor) {
                \Log::error('Vendor not found for case: ' . $case->id);
                return;
            }

            // Get TPA based on case type
            $tpa = null;
            $caseType = 'normal';

            if (!empty($case->post_two_claim_no)) {
                $caseType = 'post_2';
                $tpa = User::find($case->post_two_tpa_allot_after_claim_no_received);
            } elseif (!empty($case->post_claim_no)) {
                $caseType = 'post_1';
                $tpa = User::find($case->post_tpa_allot_after_claim_no_received);
            } else {
                $tpa = User::find($case->tpa_allot_after_claim_no_received);
            }

            \Log::info('Vendor ID: ' . $case->created_by . ', TPA ID: ' . ($tpa ? $tpa->id : 'NULL'));
            \Log::info('Vendor found: ' . ($vendor ? 'Yes' : 'No') . ', TPA found: ' . ($tpa ? 'Yes' : 'No'));
            \Log::info('Case type determined: ' . $caseType);

            // If TPA is not assigned, we can't create a ticket yet
            if (!$tpa) {
                \Log::info('TPA not assigned for case: ' . $case->id . '. Ticket creation skipped.');
                return;
            }

            // Create ticket
            $ticket = $ticketService->createTicketFromQuery($query, $case, $vendor, $tpa, $caseType);

            \Log::info('Ticket created successfully: ' . ($ticket ? $ticket->id : 'Failed'));

        } catch (\Exception $e) {
            \Log::error('Error creating ticket for query: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
        }
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'claim_no' => 'nullable|string',
            'approved_amt' => 'nullable|string',
            'status' => 'nullable|string',
            'paid_date' => 'nullable',
            'approved_date' => 'nullable',
            'patient_details_form' => 'nullable|file|mimes:jpg,jpeg,png,pdf,xls,webp,xlsx,docx,doc|max:20480',
        ]);
        $case = Cases::findOrFail($id);
        $case->claim_no = $request->claim_no;
        $case->approved_amt = $request->approved_amt;
        $case->approved_date = $request->approved_date;
        $case->paid_date = $request->paid_date;

        $get_the_tpa_commission_type = $case->tpa_type;
        $tpa1 = $case->tpa_allot_after_claim_no_received;
        $tpa2 = $case->tpa_allot_after_claim_no_received_two;
        $no_commission_tpa = is_string($case->no_commission_tpa) ? json_decode($case->no_commission_tpa, true) : ($case->no_commission_tpa ?? []);
        if ($request->status == 'Investigation') {
            if ($get_the_tpa_commission_type === 'direct') {
                if (!in_array($tpa1, $no_commission_tpa)) {
                    $no_commission_tpa[] = $tpa1;
                }
            } elseif ($get_the_tpa_commission_type === 'first') {
                if (!in_array($tpa1, $no_commission_tpa)) {
                    $no_commission_tpa[] = $tpa1;
                }
                if (!in_array($tpa2, $no_commission_tpa)) {
                    $no_commission_tpa[] = $tpa2;
                }
            }
        }

        if($request->status == 'Approved'){
            $groupVendorData = \App\Models\User::where('id', $case->created_by)->first();
            $group = \App\Models\Group::find($groupVendorData->vendor_case_group);
            $grp_case_code = $case->case_code;
            $grp_case_name = $case->name;
            $grp_case_claim_no = $request->claim_no;
            if ($group) {
                $group->groupMessages()->create([
                    'sender_id' => 1,
                    'message' => "Case ID $grp_case_code for $grp_case_name (Claim Number: $grp_case_claim_no) has been approved.",
                ]);
            }
        }

        $case->no_commission_tpa = $no_commission_tpa;

        if ($request->status == 'Query' && $case->status != 'Query') {
            $case->is_query = 1;
        }

        // Close all active tickets for this case if status changes from Query to something else
        if ($case->status == 'Query' && $request->status != 'Query') {
            $this->closeAllTicketsForCase($case->id);
        }

        if ($request->status == 'Paid' && $case->status != 'Paid') {
            $approvedAmt = $request->approved_amt;

            $mainVendorUser = User::where('id', $case->created_by)->first();
            if ($mainVendorUser) {
                $commissionMain = ($mainVendorUser->commission_main / 100) * $approvedAmt;
                $case->commission_vendor = $commissionMain;
                $mainVendorUser->wallet -= $commissionMain;
                $mainVendorUser->save();

                 $group = \App\Models\Group::find($mainVendorUser->vendor_payment_group);
                $grp_case_code = $case->case_code;
                $grp_case_name = $case->name;
                $grp_case_claim_no = $request->claim_no;
                if ($group) {
                    $group->groupMessages()->create([
                        'sender_id' => 1,
                        'message' => "Case ID $grp_case_code, Name: $grp_case_name, Claim Number: $grp_case_claim_no, Paid Amount: $approvedAmt, Commission Amount: $commissionMain, Status: Paid",
                    ]);
                }
                VendorCase::create([
                    'user_id' => $mainVendorUser->id,
                    'case_code' => $case->case_code,
                    'claim_no' => $case->claim_no,
                    'name' => $case->name,
                    'hospital' => $case->hospital,
                    'corp' => $case->corp,
                    'paid_amt' => $approvedAmt,
                    'commission' => $commissionMain,
                    'paid_date' => now(),
                ]);
            }

            if ($get_the_tpa_commission_type === 'direct') {
                $mainTpaUser = User::where('id', $case->tpa_allot_after_claim_no_received)->first();
                if ($mainTpaUser && !in_array($tpa1, $no_commission_tpa)) {
                    $commissionMain = ($mainTpaUser->commission_main / 100) * $approvedAmt;
                    $case->commission_main_tpa = $commissionMain;
                    $mainTpaUser->wallet += $commissionMain;
                    $mainTpaUser->save();

                    TpaCase::create([
                        'user_id' => $mainTpaUser->id,
                        'claim_no' => $case->claim_no,
                        'name' => $case->name,
                        'hospital' => $case->hospital,
                        'corp' => $case->corp,
                        'paid_amt' => $approvedAmt,
                        'approved_type' => 'Direct Commission',
                        'commission' => $commissionMain,
                    ]);
                } else {
                    $case->commission_main_tpa = 0;
                }
            } elseif ($get_the_tpa_commission_type === 'first') {
                $firstTpaUser = User::where('id', $tpa1)->first();
                $secondTpaUser = User::where('id', $tpa2)->first();

                if ($firstTpaUser && !in_array($tpa1, $no_commission_tpa)) {
                    $commissionFirst = ($firstTpaUser->commission_first / 100) * $approvedAmt;
                    $case->commission_first_tpa = $commissionFirst;
                    $firstTpaUser->wallet += $commissionFirst;
                    $firstTpaUser->save();

                    TpaCase::create([
                        'user_id' => $firstTpaUser->id,
                        'claim_no' => $case->claim_no,
                        'name' => $case->name,
                        'hospital' => $case->hospital,
                        'corp' => $case->corp,
                        'paid_amt' => $approvedAmt,
                        'approved_type' => 'First Commission',
                        'commission' => $commissionFirst,
                    ]);
                } else {
                    $case->commission_first_tpa = 0;
                }

                if ($secondTpaUser && !in_array($tpa2, $no_commission_tpa)) {
                    $commissionSecond = ($secondTpaUser->commission_second / 100) * $approvedAmt;
                    $case->commission_second_tpa = $commissionSecond;
                    $secondTpaUser->wallet += $commissionSecond;
                    $secondTpaUser->save();

                    TpaCase::create([
                        'user_id' => $secondTpaUser->id,
                        'claim_no' => $case->claim_no,
                        'name' => $case->name,
                        'hospital' => $case->hospital,
                        'corp' => $case->corp,
                        'paid_amt' => $approvedAmt,
                        'approved_type' => 'Second Commission',
                        'commission' => $commissionSecond,
                    ]);
                } else {
                    $case->commission_second_tpa = 0;
                }
            }
        }

        if ($request->hasFile('patient_details_form')) {
            $case->patient_details_form = $request->file('patient_details_form')->store('attachments', 'public');
        }
        $case->status = $request->status;
        $case->save();

        return response()->json([
            'success' => true,
            'message' => 'Case updated successfully!',
        ]);
    }

    public function update_post_one(Request $request, $id)
    {
        $request->validate([
            'post_claim_no' => 'nullable|string',
            'post_ammount' => 'nullable|string',
            'post_status' => 'nullable|string',
            'post_paid_date' => 'nullable',
            'post_approved_date' => 'nullable',
            'post_patient_details_form' => 'nullable|file|mimes:jpg,jpeg,png,pdf,xls,webp,xlsx,docx,doc|max:20480',
        ]);
        $case = Cases::findOrFail($id);
        $case->post_claim_no = $request->post_claim_no;
        $case->post_ammount = $request->post_ammount;
        $case->post_approved_date = $request->post_approved_date;
        $case->post_paid_date = $request->post_paid_date;

        $get_the_tpa_commission_type = $case->post_tpa_type;
        $tpa1 = $case->post_tpa_allot_after_claim_no_received;
        $tpa2 = $case->post_tpa_allot_after_claim_no_received_two;
        $post_no_commission_tpa = is_string($case->post_no_commission_tpa) ? json_decode($case->post_no_commission_tpa, true) : ($case->post_no_commission_tpa ?? []);

        if ($request->post_status == 'Query' && $case->post_status != 'Query') {
            $case->is_query_post = 1;
        }

        // Close all active tickets for this case if post status changes from Query to something else
        if ($case->post_status == 'Query' && $request->post_status != 'Query') {
            $this->closeAllTicketsForCase($case->id);
        }


        if($request->post_status == 'Approved'){
            $groupVendorData = \App\Models\User::where('id', $case->created_by)->first();
            $group = \App\Models\Group::find($groupVendorData->vendor_case_group);
            $grp_case_code = $case->case_code;
            $grp_case_name = $case->name;
            $grp_case_claim_no = $request->post_claim_no;
            if ($group) {
                $group->groupMessages()->create([
                    'sender_id' => 1,
                    'message' => "Case ID $grp_case_code for $grp_case_name (Claim Number: $grp_case_claim_no) has been approved.",
                ]);
            }
        }

        if ($request->post_status == 'Investigation') {
            if ($get_the_tpa_commission_type === 'direct') {
                if (!in_array($tpa1, $post_no_commission_tpa)) {
                    $post_no_commission_tpa[] = $tpa1;
                    TpaCase::create([
                        'user_id' => $tpa1,
                        'name' => $case->name,
                        'hospital' => $case->hospital,
                        'corp' => $case->corp,
                        'paid_amt' => 0,
                        'approved_type' => 'direct',
                        'commission' => 0,
                    ]);
                }
            } elseif ($get_the_tpa_commission_type === 'first') {
                if (!in_array($tpa1, $post_no_commission_tpa)) {
                    $post_no_commission_tpa[] = $tpa1;
                    TpaCase::create([
                        'user_id' => $tpa1,
                        'name' => $case->name,
                        'hospital' => $case->hospital,
                        'corp' => $case->corp,
                        'paid_amt' => 0,
                        'approved_type' => 'direct',
                        'commission' => 0,
                    ]);
                }
                if (!in_array($tpa2, $post_no_commission_tpa)) {
                    $post_no_commission_tpa[] = $tpa2;
                    TpaCase::create([
                        'user_id' => $tpa1,
                        'name' => $case->name,
                        'hospital' => $case->hospital,
                        'corp' => $case->corp,
                        'paid_amt' => 0,
                        'approved_type' => 'direct',
                        'commission' => 0,
                    ]);
                }
            }
        }
        $case->post_no_commission_tpa = $post_no_commission_tpa;

        if ($request->post_status == 'Paid' && $case->post_status != 'Paid') {
            $approvedAmt = $request->post_ammount;

            // Vendor commission
            $mainVendorUser = User::where('id', $case->created_by)->first();
            if ($mainVendorUser) {
                $commissionMain = ($mainVendorUser->commission_main / 100) * $approvedAmt;
                $case->commission_vendor = $commissionMain;
                $mainVendorUser->wallet -= $commissionMain;
                $mainVendorUser->save();
                 $group = \App\Models\Group::find($mainVendorUser->vendor_payment_group);
                $grp_case_code = $case->case_code;
                $grp_case_name = $case->name;
                $grp_case_claim_no = $request->post_claim_no;
                if ($group) {
                    $group->groupMessages()->create([
                        'sender_id' => 1,
                        'message' => "Case ID $grp_case_code, Name: $grp_case_name, Claim Number: $grp_case_claim_no, Paid Amount: $approvedAmt, Commission Amount: $commissionMain, Status: Paid",
                    ]);
                }
                VendorCase::create([
                    'user_id' => $mainVendorUser->id,
                    'claim_no' => $case->post_claim_no,
                    'case_code' => $case->case_code,
                    'name' => $case->name,
                    'hospital' => $case->hospital,
                    'corp' => $case->corp,
                    'paid_amt' => $approvedAmt,
                    'commission' => $commissionMain,
                    'paid_date' => now(),
                ]);
            }

            // TPA commission allocation
            if ($get_the_tpa_commission_type === 'direct') {
                $mainTpaUser = User::where('id', $case->post_tpa_allot_after_claim_no_received)->first();
                if ($mainTpaUser  && !in_array($tpa1, $post_no_commission_tpa)) {
                    $commissionMain = ($mainTpaUser->commission_main / 100) * $approvedAmt;
                    $case->commission_main_tpa = $commissionMain;
                    $mainTpaUser->wallet += $commissionMain;
                    $mainTpaUser->save();

                    TpaCase::create([
                        'user_id' => $mainTpaUser->id,
                        'claim_no' => $case->post_claim_no,
                        'name' => $case->name,
                        'hospital' => $case->hospital,
                        'corp' => $case->corp,
                        'paid_amt' => $approvedAmt,
                        'approved_type' => 'direct',
                        'commission' => $commissionMain,
                    ]);
                } else {
                    $case->commission_main_tpa = 0;
                }
            } elseif ($get_the_tpa_commission_type === 'first') {
                // First and second TPA commission allocation
                $firstTpaUser = User::where('id', $tpa1)->first();
                $secondTpaUser = User::where('id', $tpa2)->first();

                if ($firstTpaUser && !in_array($tpa1, $post_no_commission_tpa)) {
                    $commissionFirst = ($firstTpaUser->commission_first / 100) * $approvedAmt;
                    $case->commission_first_tpa = $commissionFirst;
                    $firstTpaUser->wallet += $commissionFirst;
                    $firstTpaUser->save();

                    // Save the commission in TpaCases
                    TpaCase::create([
                        'user_id' => $firstTpaUser->id,
                        'claim_no' => $case->post_claim_no,
                        'name' => $case->name,
                        'hospital' => $case->hospital,
                        'corp' => $case->corp,
                        'paid_amt' => $approvedAmt,
                        'approved_type' => 'first',
                        'commission' => $commissionFirst,
                    ]);
                } else {
                    $case->commission_first_tpa = 0;
                }

                if ($secondTpaUser && !in_array($tpa2, $post_no_commission_tpa)) {
                    $commissionSecond = ($secondTpaUser->commission_second / 100) * $approvedAmt;
                    $case->commission_second_tpa = $commissionSecond;
                    $secondTpaUser->wallet += $commissionSecond;
                    $secondTpaUser->save();

                    // Save the commission in TpaCases
                    TpaCase::create([
                        'user_id' => $secondTpaUser->id,
                        'claim_no' => $case->post_claim_no,
                        'name' => $case->name,
                        'hospital' => $case->hospital,
                        'corp' => $case->corp,
                        'paid_amt' => $approvedAmt,
                        'approved_type' => 'first',
                        'commission' => $commissionSecond,
                    ]);
                } else {
                    $case->commission_second_tpa = 0;
                }
            }
        }
        if ($request->hasFile('post_patient_details_form')) {
            $case->post_patient_details_form = $request->file('post_patient_details_form')->store('attachments', 'public');
        }
        $case->post_status = $request->post_status;
        $case->save();
        return response()->json([
            'success' => true,
            'message' => 'Case updated successfully!',
        ]);
    }

    public function update_post_two(Request $request, $id)
    {
        $request->validate([
            'post_two_claim_no' => 'nullable|string',
            'post_two_ammount' => 'nullable|string',
            'post_two_status' => 'nullable|string',
            'post_two_paid_date' => 'nullable',
            'post_two_approved_date' => 'nullable',
            'post_two_patient_details_form' => 'nullable|file|mimes:jpg,jpeg,png,pdf,xls,webp,xlsx,docx,doc|max:20480',
        ]);
        $case = Cases::findOrFail($id);
        $case->post_two_claim_no = $request->post_two_claim_no;
        $case->post_two_ammount = $request->post_two_ammount;
        $case->post_two_approved_date = $request->post_two_approved_date;
        $case->post_two_paid_date = $request->post_two_paid_date;

        $get_the_tpa_commission_type = $case->post_two_tpa_type;
        $tpa1 = $case->post_two_tpa_allot_after_claim_no_received;
        $tpa2 = $case->post_two_tpa_allot_after_claim_no_received_two;

        $post_two_no_commission_tpa = is_string($case->post_two_no_commission_tpa) ? json_decode($case->post_two_no_commission_tpa, true) : ($case->post_two_no_commission_tpa ?? []);

        if($request->post_two_status == 'Approved'){
            $groupVendorData = \App\Models\User::where('id', $case->created_by)->first();
            $group = \App\Models\Group::find($groupVendorData->vendor_case_group);
            $grp_case_code = $case->case_code;
            $grp_case_name = $case->name;
            $grp_case_claim_no = $request->post_two_claim_no;
            if ($group) {
                $group->groupMessages()->create([
                    'sender_id' => 1,
                    'message' => "Case ID $grp_case_code for $grp_case_name (Claim Number: $grp_case_claim_no) has been approved.",
                ]);
            }
        }


        if ($request->post_two_status == 'Investigation') {
            if ($get_the_tpa_commission_type === 'direct') {
                if (!in_array($tpa1, $post_two_no_commission_tpa)) {
                    $post_two_no_commission_tpa[] = $tpa1;
                }
            } elseif ($get_the_tpa_commission_type === 'first') {
                if (!in_array($tpa1, $post_two_no_commission_tpa)) {
                    $post_two_no_commission_tpa[] = $tpa1;
                }
                if (!in_array($tpa2, $post_two_no_commission_tpa)) {
                    $post_two_no_commission_tpa[] = $tpa2;
                }
            }
        }
        $case->post_two_no_commission_tpa = $post_two_no_commission_tpa;

        if ($request->post_two_status == 'Query' && $case->post_two_status != 'Query') {
            $case->is_query_post_two = 1;
        }

        // Close all active tickets for this case if post_two status changes from Query to something else
        if ($case->post_two_status == 'Query' && $request->post_two_status != 'Query') {
            $this->closeAllTicketsForCase($case->id);
        }

        if ($request->post_two_status == 'Paid' && $case->post_two_status != 'Paid') {
            $approvedAmt = $request->post_two_ammount;

            $mainVendorUser = User::where('id', $case->created_by)->first();
            if ($mainVendorUser) {
                $commissionMain = ($mainVendorUser->commission_main / 100) * $approvedAmt;
                $case->commission_vendor = $commissionMain;
                $mainVendorUser->wallet -= $commissionMain;
                $mainVendorUser->save();


                 $group = \App\Models\Group::find($mainVendorUser->vendor_payment_group);
                $grp_case_code = $case->case_code;
                $grp_case_name = $case->name;
                $grp_case_claim_no = $request->post_two_claim_no;
                if ($group) {
                    $group->groupMessages()->create([
                        'sender_id' => 1,
                        'message' => "Case ID $grp_case_code, Name: $grp_case_name, Claim Number: $grp_case_claim_no, Paid Amount: $approvedAmt, Commission Amount: $commissionMain, Status: Paid",
                    ]);
                }

                VendorCase::create([
                    'user_id' => $mainVendorUser->id,
                    'case_code' => $case->case_code,
                    'claim_no' => $case->post_two_claim_no,
                    'name' => $case->name,
                    'hospital' => $case->hospital,
                    'corp' => $case->corp,
                    'paid_amt' => $approvedAmt,
                    'commission' => $commissionMain,
                    'paid_date' => now(),
                ]);
            }

            if ($get_the_tpa_commission_type === 'direct') {
                $mainTpaUser = User::where('id', $case->post_two_tpa_allot_after_claim_no_received)->first();
                if ($mainTpaUser && !in_array($tpa1, $post_two_no_commission_tpa)) {
                    $commissionMain = ($mainTpaUser->commission_main / 100) * $approvedAmt;
                    $case->commission_main_tpa = $commissionMain;
                    $mainTpaUser->wallet += $commissionMain;
                    $mainTpaUser->save();

                    TpaCase::create([
                        'user_id' => $mainTpaUser->id,
                        'claim_no' => $case->post_two_claim_no,
                        'name' => $case->name,
                        'hospital' => $case->hospital,
                        'corp' => $case->corp,
                        'paid_amt' => $approvedAmt,
                        'approved_type' => 'direct',
                        'commission' => $commissionMain,
                    ]);
                } else {
                    $case->commission_main_tpa = 0;
                }
            } elseif ($get_the_tpa_commission_type === 'first') {
                $firstTpaUser = User::where('id', $tpa1)->first();
                $secondTpaUser = User::where('id', $tpa2)->first();

                if ($firstTpaUser && !in_array($tpa1, $post_two_no_commission_tpa)) {
                    $commissionFirst = ($firstTpaUser->commission_first / 100) * $approvedAmt;
                    $case->commission_first_tpa = $commissionFirst;
                    $firstTpaUser->wallet += $commissionFirst;
                    $firstTpaUser->save();

                    TpaCase::create([
                        'user_id' => $firstTpaUser->id,
                        'claim_no' => $case->post_two_claim_no,
                        'name' => $case->name,
                        'hospital' => $case->hospital,
                        'corp' => $case->corp,
                        'paid_amt' => $approvedAmt,
                        'approved_type' => 'first',
                        'commission' => $commissionFirst,
                    ]);
                } else {
                    $case->commission_first_tpa = 0;
                }

                if ($secondTpaUser && !in_array($tpa2, $post_two_no_commission_tpa)) {
                    $commissionSecond = ($secondTpaUser->commission_second / 100) * $approvedAmt;
                    $case->commission_second_tpa = $commissionSecond;
                    $secondTpaUser->wallet += $commissionSecond;
                    $secondTpaUser->save();

                    TpaCase::create([
                        'user_id' => $secondTpaUser->id,
                        'claim_no' => $case->post_two_claim_no,
                        'name' => $case->name,
                        'hospital' => $case->hospital,
                        'corp' => $case->corp,
                        'paid_amt' => $approvedAmt,
                        'approved_type' => 'first',
                        'commission' => $commissionSecond,
                    ]);
                } else {
                    $case->commission_second_tpa = 0;
                }
            }
        }
        if ($request->hasFile('post_two_patient_details_form')) {
            $case->post_two_patient_details_form = $request->file('post_two_patient_details_form')->store('attachments', 'public');
        }
        $case->post_two_status = $request->post_two_status;
        $case->save();
        return response()->json([
            'success' => true,
            'message' => 'Case updated successfully!',
        ]);
    }

    /**
     * Close all active tickets for a specific case
     */
    private function closeAllTicketsForCase($caseId)
    {
        $ticketService = new TicketService();
        
        // Get all active tickets for this case
        $activeTickets = Ticket::where('case_id', $caseId)
            ->where('is_active', true)
            ->get();

        // Close each active ticket
        foreach ($activeTickets as $ticket) {
            $ticketService->closeTicket($ticket);
        }

        Log::info("Closed " . $activeTickets->count() . " active tickets for case ID: " . $caseId);
    }
}
