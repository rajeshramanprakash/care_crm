<?php

namespace App\Http\Controllers\SubAdmin;

use App\Http\Controllers\Controller;
use App\Models\Cases;
use App\Models\Courier;
use App\Models\TpaCase;
use App\Models\User;
use App\Models\VendorCase;
use App\Models\Query;
use App\Models\Ticket;
use App\Services\TicketService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Helpers\CaseIdEncryption;
class CourierController extends Controller
{
    public function index($dashboard_filters = null)
    {
        $page_heading = 'Courier';
        $vendors = User::whereRaw("FIND_IN_SET(?, role_id)", [10])->get();
        $tpa_roles = User::whereRaw("FIND_IN_SET(?, role_id)", [8])->get();
        $filter_params = "";
        if ($dashboard_filters !== null) {
            $filter_params = ['dashboard_filters' => $dashboard_filters];
            $page_heading = ucwords(str_replace("_", " ", $dashboard_filters));
        }
        return view('subadmin.courier.index', compact('page_heading', 'filter_params', 'vendors', 'tpa_roles'));
    }

    public function ajax_list(Request $request)
    {
        $statusFilter = $request->get('status_filter');
        
        $courier = Courier::select(
            'courier.id as courier_id',
            'cases.id',
            'cases.case_code',
            'users.f_name',
            'cases.name',
            'cases.corp',
            'cases.hospital',
            'cases.diagnosis',
            'cases.member_id',
            'cases.pre_courier_date', //for main claim
            'cases.post_courier_date', //for post claim
            'cases.post_two_courier_date', //for postTwo claim
            'courier.pod_no',
            'courier.case_type',
            'cases.approved_amt', //for main claim
            'cases.post_ammount', //for post claim
            'cases.post_two_ammount', //for postTwo claim
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
            'cases.post_tpa_allot_after_claim_no_received_two',
            'cases.post_two_tpa_allot_after_claim_no_received_two',


            'cases.status', //for main claim
            'cases.post_status', //for post claim
            'cases.post_two_status', //for postTwo claim
        )
            ->leftJoin('cases', 'courier.case_id', '=', 'cases.id')
            ->join('users', 'cases.created_by', '=', 'users.id')
            ->where(function ($query) {
                // Don't show cases that are on hold
                $query->where(function ($q) {
                    $q->where('cases.post_status', '!=', 'Hold')
                      ->orWhereNull('cases.post_status');
                })
                ->where(function ($q) {
                    $q->where('cases.post_two_status', '!=', 'Hold')
                      ->orWhereNull('cases.post_two_status');
                });
            })
            ->where(function($query) use ($statusFilter) {
                if ($statusFilter === 'empty_claim') {
                    // Filter for cases with empty claim numbers based on case type
                    $query->where(function($q) {
                        $q->where(function($subQ) {
                            // Main cases with empty claim_no
                            $subQ->where('courier.case_type', 'main')
                                 ->where(function($emptyQ) {
                                     $emptyQ->whereNull('cases.claim_no')
                                            ->orWhere('cases.claim_no', '')
                                            ->orWhere('cases.claim_no', ' ')
                                            ->orWhere('cases.claim_no', 'NULL')
                                            ->orWhere('cases.claim_no', 'null');
                                 });
                        })
                        ->orWhere(function($subQ) {
                            // Post cases with empty post_claim_no
                            $subQ->where('courier.case_type', 'post')
                                 ->where(function($emptyQ) {
                                     $emptyQ->whereNull('cases.post_claim_no')
                                            ->orWhere('cases.post_claim_no', '')
                                            ->orWhere('cases.post_claim_no', ' ')
                                            ->orWhere('cases.post_claim_no', 'NULL')
                                            ->orWhere('cases.post_claim_no', 'null');
                                 });
                        })
                        ->orWhere(function($subQ) {
                            // PostTwo cases with empty post_two_claim_no
                            $subQ->where('courier.case_type', 'postTwo')
                                 ->where(function($emptyQ) {
                                     $emptyQ->whereNull('cases.post_two_claim_no')
                                            ->orWhere('cases.post_two_claim_no', '')
                                            ->orWhere('cases.post_two_claim_no', ' ')
                                            ->orWhere('cases.post_two_claim_no', 'NULL')
                                            ->orWhere('cases.post_two_claim_no', 'null');
                                 });
                        });
                    });
                } else {
                    // Regular status filtering
                    $query->where(function($q) use ($statusFilter) {
                        $q->where('courier.case_type', 'main')
                          ->where(function($subQ) use ($statusFilter) {
                              if ($statusFilter && $statusFilter !== 'all') {
                                  $subQ->where('cases.status', $statusFilter);
                              }
                              // Remove the exclusion of 'Paid' and 'Reject' statuses
                          });
                    })
                    ->orWhere(function($q) use ($statusFilter) {
                        $q->where('courier.case_type', 'post')
                          ->where(function($subQ) use ($statusFilter) {
                              if ($statusFilter && $statusFilter !== 'all') {
                                  $subQ->where('cases.post_status', $statusFilter);
                              }
                              // Remove the exclusion of 'Paid' and 'Reject' statuses
                          });
                    })
                    ->orWhere(function($q) use ($statusFilter) {
                        $q->where('courier.case_type', 'postTwo')
                          ->where(function($subQ) use ($statusFilter) {
                              if ($statusFilter && $statusFilter !== 'all') {
                                  $subQ->where('cases.post_two_status', $statusFilter);
                              }
                              // Remove the exclusion of 'Paid' and 'Reject' statuses
                          });
                    });
                }
            })
            ->groupBy('cases.id')
            ->get();

        return dataTables()->of($courier)
        ->addColumn('encrypted_id', function ($case) {
            return CaseIdEncryption::routeParam($case->id);
        })
            ->addColumn('case_color', function ($case) {
                $color_code = '';
                if ($case->case_type == 'main') {
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
                } elseif ($case->case_type == 'post') {
                    if ($case->post_status == 'Query') {
                        $color_code = '#FFB6C1';
                    } elseif ($case->post_status == 'Investigation') {
                        $color_code = '#ADD8E6';
                    } elseif ($case->post_status == 'Reject') {
                        $color_code = '#6C757D';
                    } elseif ($case->post_status == 'UnderProcess') {
                        $color_code = '#FFC107';
                    } elseif ($case->post_status == 'Approved' || $case->post_status == 'InProcess' ||  $case->post_status == 'Paid') {
                        $color_code = '#28A745';
                    }
                } else {
                    if ($case->post_two_status == 'Query') {
                        $color_code = '#FFB6C1';
                    } elseif ($case->post_two_status == 'Investigation') {
                        $color_code = '#ADD8E6';
                    } elseif ($case->post_two_status == 'Reject') {
                        $color_code = '#6C757D';
                    } elseif ($case->post_two_status == 'UnderProcess') {
                        $color_code = '#FFC107';
                    } elseif ($case->post_two_status == 'Approved' || $case->post_two_status == 'InProcess' ||  $case->post_two_status == 'Paid') {
                        $color_code = '#28A745';
                    }
                }

                return $color_code;
            })
            ->addColumn('text_color', function ($case) {
                $text_color = '';
                if ($case->case_type == 'main') {
                    if ($case->status == 'Query') {
                        $text_color = '#000000';
                    } elseif ($case->status == 'Investigation') {
                        $text_color = '#000000';
                    } elseif ($case->status == 'Reject') {
                        $text_color = '#FFFFFF';
                    } elseif ($case->status == 'UnderProcess') {
                        $text_color = '#000000';
                    } elseif ($case->status == 'Approved' || $case->status == 'InProcess' || $case->status == 'Paid') {
                        $text_color = '#FFFFFF';
                    }
                } elseif ($case->case_type == 'post') {
                    if ($case->post_status == 'Query') {
                        $text_color = '#000000';
                    } elseif ($case->post_status == 'Investigation') {
                        $text_color = '#000000';
                    } elseif ($case->post_status == 'Reject') {
                        $text_color = '#FFFFFF';
                    } elseif ($case->post_status == 'UnderProcess') {
                        $text_color = '#000000';
                    } elseif ($case->post_status == 'Approved' || $case->post_status == 'InProcess' || $case->post_status == 'Paid') {
                        $text_color = '#FFFFFF';
                    }
                } else {
                    if ($case->post_two_status == 'Query') {
                        $text_color = '#000000';
                    } elseif ($case->post_two_status == 'Investigation') {
                        $text_color = '#000000';
                    } elseif ($case->post_two_status == 'Reject') {
                        $text_color = '#FFFFFF';
                    } elseif ($case->post_two_status == 'UnderProcess') {
                        $text_color = '#000000';
                    } elseif ($case->post_two_status == 'Approved' || $case->post_two_status == 'InProcess' || $case->post_two_status == 'Paid') {
                        $text_color = '#FFFFFF';
                    }
                }
                return $text_color;
            })
            ->make(true);
    }


    public function delete($id)
    {
        $case = Courier::findOrFail($id);
        $case->delete();
        session()->flash('status', ['success' => true, 'alert_type' => 'success', 'message' => "Courier deleted."]);
        return redirect()->back();
    }

    public function update(Request $request)
    {
        $request->validate([
            'claim_no' => 'nullable|string',
            'approved_amt' => 'nullable|string',
            'status' => 'nullable|string',
            'paid_date' => 'nullable',
            'approved_date' => 'nullable',
            'patient_details_form' => 'nullable|file|mimes:jpg,jpeg,png,pdf,xls,webp,xlsx,docx,doc|max:20480',
            'query_text' => 'nullable|string',
        ]);
        $case = Cases::findOrFail($request->case_id);
        $case->approved_amt = $request->approved_amt;

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
            if ($group) {
            $grp_case_code = $case->case_code;
            $grp_case_name = $case->name;
            $grp_case_claim_no = $request->claim_no;
            $msg = $group->groupMessages()->create([
                'sender_id' => 1,
                'message' => "Case ID $grp_case_code for $grp_case_name (Claim Number: $grp_case_claim_no) has been approved.",
            ]);
        }
        }

        $case->no_commission_tpa = $no_commission_tpa;

        if ($request->status == 'Query' && $case->status != 'Query') {
            $case->is_query = 1;
            $query = new \App\Models\Query();
            $query->case_id = $request->case_id;
            $query->created_by = auth()->user()->id;
            $query->query = $request->query_text;
            $query->save();
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
                 if ($group) {
                $grp_case_code = $case->case_code;
                $grp_case_name = $case->name;
                $grp_case_claim_no = $request->claim_no;
                $msg = $group->groupMessages()->create([
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
        
        if ($request->ajax()) {
            return response()->json(['message' => 'Case updated successfully.']);
        }
        
        session()->flash('status', ['success' => true, 'alert_type' => 'success', 'message' => "Case updated successfully."]);
        return redirect()->back();
    }

    public function update_post_one(Request $request)
    {
        $request->validate([
            'post_claim_no' => 'nullable|string',
            'post_ammount' => 'nullable|string',
            'post_status' => 'nullable|string',
            'post_paid_date' => 'nullable',
            'post_approved_date' => 'nullable',
            'post_patient_details_form' => 'nullable|file|mimes:jpg,jpeg,png,pdf,xls,webp,xlsx,docx,doc|max:20480',
            'post_query_text' => 'nullable|string',
        ]);
        $case = Cases::findOrFail($request->case_id);
        $case->post_ammount = $request->post_ammount;

        $get_the_tpa_commission_type = $case->post_tpa_type;
        $tpa1 = $case->post_tpa_allot_after_claim_no_received;
        $tpa2 = $case->post_tpa_allot_after_claim_no_received_two;
        $post_no_commission_tpa = is_string($case->post_no_commission_tpa) ? json_decode($case->post_no_commission_tpa, true) : ($case->post_no_commission_tpa ?? []);

        if ($request->post_status == 'Query' && $case->post_status != 'Query') {
            $case->is_query_post = 1;
            $query = new \App\Models\Query();
            $query->case_id = $request->case_id;
            $query->created_by = auth()->user()->id;
            $query->query = $request->query_text;
            $query->save();
        }

        // Close all active tickets for this case if post status changes from Query to something else
        if ($case->post_status == 'Query' && $request->post_status != 'Query') {
            $this->closeAllTicketsForCase($case->id);
        }

        if($request->post_status == 'Approved'){
            $groupVendorData = \App\Models\User::where('id', $case->created_by)->first();
            $group = \App\Models\Group::find($groupVendorData->vendor_case_group);
            if ($group) {
            $grp_case_code = $case->case_code;
            $grp_case_name = $case->name;
            $grp_case_claim_no = $request->post_claim_no;
            $msg = $group->groupMessages()->create([
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

            $mainVendorUser = User::where('id', $case->created_by)->first();
            if ($mainVendorUser) {
                $commissionMain = ($mainVendorUser->commission_main / 100) * $approvedAmt;
                $case->commission_vendor = $commissionMain;
                $mainVendorUser->wallet -= $commissionMain;
                $mainVendorUser->save();
  $group = \App\Models\Group::find($mainVendorUser->vendor_payment_group);
  if ($group) {
                $grp_case_code = $case->case_code;
                $grp_case_name = $case->name;
                $grp_case_claim_no = $request->post_claim_no;
                $msg = $group->groupMessages()->create([
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
                        'name' => $case->name,
                        'hospital' => $case->hospital,  // Adjust field as necessary
                        'corp' => $case->corp,          // Adjust field as necessary
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
        
        if ($request->ajax()) {
            return response()->json(['message' => 'Case updated successfully.']);
        }
        
        session()->flash('status', ['success' => true, 'alert_type' => 'success', 'message' => "Case updated successfully."]);
        return redirect()->back();
    }

    public function update_post_two(Request $request)
    {
        $request->validate([
            'post_two_claim_no' => 'nullable|string',
            'post_two_ammount' => 'nullable|string',
            'post_two_status' => 'nullable|string',
            'post_two_paid_date' => 'nullable',
            'post_two_approved_date' => 'nullable',
            'post_two_patient_details_form' => 'nullable|file|mimes:jpg,jpeg,png,pdf,xls,webp,xlsx,docx,doc|max:20480',
            'post_two_query_text' => 'nullable|string',
        ]);
        $case = Cases::findOrFail($request->case_id);
        $case->post_two_ammount = $request->post_two_ammount;

        $get_the_tpa_commission_type = $case->post_two_tpa_type;
        $tpa1 = $case->post_two_tpa_allot_after_claim_no_received;
        $tpa2 = $case->post_two_tpa_allot_after_claim_no_received_two;

        $post_two_no_commission_tpa = is_string($case->post_two_no_commission_tpa) ? json_decode($case->post_two_no_commission_tpa, true) : ($case->post_two_no_commission_tpa ?? []);


        if($request->post_two_status == 'Approved'){
            $groupVendorData = \App\Models\User::where('id', $case->created_by)->first();
            $group = \App\Models\Group::find($groupVendorData->vendor_case_group);
            if ($group) {
            $grp_case_code = $case->case_code;
            $grp_case_name = $case->name;
            $grp_case_claim_no = $request->post_two_claim_no;
            $msg = $group->groupMessages()->create([
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
            $query = new \App\Models\Query();
            $query->case_id = $request->case_id;
            $query->created_by = auth()->user()->id;
            $query->query = $request->query_text;
            $query->save();
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
                 if ($group) {
                $grp_case_code = $case->case_code;
                $grp_case_name = $case->name;
                $grp_case_claim_no = $request->post_two_claim_no;
                $msg = $group->groupMessages()->create([
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
        
        if ($request->ajax()) {
            return response()->json(['message' => 'Case updated successfully.']);
        }
        
        session()->flash('status', ['success' => true, 'alert_type' => 'success', 'message' => "Case updated successfully."]);
        return redirect()->back();
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

    public function getStatusCounts()
    {
        $courier = Courier::select(
            'courier.case_type',
            'cases.status',
            'cases.post_status', 
            'cases.post_two_status'
        )
            ->leftJoin('cases', 'courier.case_id', '=', 'cases.id')
            ->join('users', 'cases.created_by', '=', 'users.id')
            ->where(function($query) {
                $query->where(function($q) {
                    $q->where('courier.case_type', 'main')
                      ->where(function($subQ) {
                          $subQ->where('cases.status', '!=', 'Paid')
                               ->where('cases.status', '!=', 'Reject');
                      });
                })
                ->orWhere(function($q) {
                    $q->where('courier.case_type', 'post')
                      ->where(function($subQ) {
                          $subQ->where('cases.post_status', '!=', 'Paid')
                               ->where('cases.post_status', '!=', 'Reject');
                      });
                })
                ->orWhere(function($q) {
                    $q->where('courier.case_type', 'postTwo')
                      ->where(function($subQ) {
                          $subQ->where('cases.post_two_status', '!=', 'Paid')
                               ->where('cases.post_two_status', '!=', 'Reject');
                      });
                });
            })
            ->groupBy('cases.id')
            ->get();

        // Count empty claim numbers - need to get fresh data for accurate counting
        $emptyClaimQuery = Courier::select('courier.case_type', 'cases.claim_no', 'cases.post_claim_no', 'cases.post_two_claim_no', 'cases.id')
            ->leftJoin('cases', 'courier.case_id', '=', 'cases.id')
            ->join('users', 'cases.created_by', '=', 'users.id')
            ->where(function($query) {
                $query->where(function($q) {
                    // Main cases with empty claim_no
                    $q->where('courier.case_type', 'main')
                      ->where(function($emptyQ) {
                          $emptyQ->whereNull('cases.claim_no')
                                 ->orWhere('cases.claim_no', '')
                                 ->orWhere('cases.claim_no', ' ')
                                 ->orWhere('cases.claim_no', 'NULL')
                                 ->orWhere('cases.claim_no', 'null');
                      });
                })
                ->orWhere(function($q) {
                    // Post cases with empty post_claim_no
                    $q->where('courier.case_type', 'post')
                      ->where(function($emptyQ) {
                          $emptyQ->whereNull('cases.post_claim_no')
                                 ->orWhere('cases.post_claim_no', '')
                                 ->orWhere('cases.post_claim_no', ' ')
                                 ->orWhere('cases.post_claim_no', 'NULL')
                                 ->orWhere('cases.post_claim_no', 'null');
                      });
                })
                ->orWhere(function($q) {
                    // PostTwo cases with empty post_two_claim_no
                    $q->where('courier.case_type', 'postTwo')
                      ->where(function($emptyQ) {
                          $emptyQ->whereNull('cases.post_two_claim_no')
                                 ->orWhere('cases.post_two_claim_no', '')
                                 ->orWhere('cases.post_two_claim_no', ' ')
                                 ->orWhere('cases.post_two_claim_no', 'NULL')
                                 ->orWhere('cases.post_two_claim_no', 'null');
                      });
                });
            })
            ->groupBy('cases.id');
            
        // Get the actual records for debugging
        $emptyClaimRecords = $emptyClaimQuery->get();
        $emptyClaimCount = $emptyClaimRecords->count();

        // Initialize counts
        $counts = [
            'all' => $courier->count(),
            'Query' => 0,
            'Investigation' => 0,
            'UnderProcess' => 0,
            'Approved' => 0,
            'InProcess' => 0,
            'Paid' => 0,
            'Reject' => 0,
            'empty_claim' => $emptyClaimCount
        ];

        // Count each status
        foreach ($courier as $case) {
            $status = '';
            if ($case->case_type === 'main') {
                $status = $case->status;
            } elseif ($case->case_type === 'post') {
                $status = $case->post_status;
            } elseif ($case->case_type === 'postTwo') {
                $status = $case->post_two_status;
            }

            if ($status && isset($counts[$status])) {
                $counts[$status]++;
            }
        }

        return response()->json($counts);
    }
}
