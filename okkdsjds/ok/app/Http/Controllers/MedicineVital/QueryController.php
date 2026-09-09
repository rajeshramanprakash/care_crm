<?php

namespace App\Http\Controllers\MedicineVital;

use App\Http\Controllers\Controller;
use App\Models\Cases;
use App\Models\Courier;
use App\Models\Query;
use App\Models\User;
use Illuminate\Http\Request;
use App\Helpers\CaseIdEncryption;

class QueryController extends Controller
{
    public function index($dashboard_filters = null)
    {
        $page_heading = 'Query';        
        $vendors = User::whereRaw("FIND_IN_SET(?, role_id)", [10])->get();
        $tpa_roles = User::whereRaw("FIND_IN_SET(?, role_id)", [8])->get();
        $filter_params = "";
        if ($dashboard_filters !== null) {
            $filter_params = ['dashboard_filters' => $dashboard_filters];
            $page_heading = ucwords(str_replace("_", " ", $dashboard_filters));
        }
        return view('medicinevital.query.index', compact('page_heading', 'filter_params', 'vendors', 'tpa_roles'));
    }

    public function ajax_list(Request $request)
    {
        $statusFilter = $request->get('status_filter', 'all');
        $courier = Courier::select(
            'courier.id as courier_id',
            'cases.id',
            'cases.case_code',
            'users.f_name',
            'cases.name',
            'cases.age',
            'cases.gender',
            'cases.doa',
            'cases.doa_time',
            'cases.dod',
            'cases.dod_time',
            'cases.corp',
            'cases.hospital',
            'cases.diagnosis',
            'cases.member_id',
            'cases.pre_courier_date',
            'cases.post_courier_date',
            'cases.post_two_courier_date',
            'courier.pod_no',
            'courier.case_type',
            'cases.approved_amt',
            'cases.post_ammount',
            'cases.post_two_ammount',
            'cases.claim_no',
            'cases.post_claim_no',
            'cases.post_two_claim_no',
            'cases.claim_no_link',
            'cases.post_claim_no_link',
            'cases.post_two_claim_no_link',
            'cases.status',
            'cases.post_status',
            'cases.post_two_status',
            'querys.id as query_id',
            'querys.query as query_text',
            'querys.query_pdf',
        )
            ->leftJoin('cases', 'courier.case_id', '=', 'cases.id')
            ->join('users', 'cases.created_by', '=', 'users.id')
            ->leftJoin('querys', function($join) {
                $join->on('querys.case_id', '=', 'cases.id');
            })
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
                    // Regular Query status filtering
                    $query->where(function($q) {
                        $q->where('courier.case_type', 'main')
                          ->where('cases.status', 'Query');
                    })
                    ->orWhere(function($q) {
                        $q->where('courier.case_type', 'post')
                          ->where('cases.post_status', 'Query');
                    })
                    ->orWhere(function($q) {
                        $q->where('courier.case_type', 'postTwo')
                          ->where('cases.post_two_status', 'Query');
                    });
                }
            })
            ->whereNull('querys.query_pdf')
->groupBy('cases.id')
            ->get();

        return dataTables()->of($courier)
        ->addColumn('encrypted_id', function ($case) {
            return CaseIdEncryption::routeParam($case->id);
        })
        ->make(true);
    }

    public function updateQueryPdf(Request $request, $id)
    {
        $request->validate([
            'pdf_field' => 'required|string',
            'pdf_file' => 'required|file|mimes:pdf|max:20480',
        ]);
        $query = \App\Models\Query::findOrFail($id);
        $field = $request->pdf_field;
        if (!in_array($field, ['query_pdf', 'post_query_pdf', 'post_two_query_pdf', 'other_query_pdf'])) {
            return response()->json(['success' => false, 'message' => 'Invalid PDF field.'], 400);
        }
        if ($request->hasFile('pdf_file')) {
            $filePath = $request->file('pdf_file')->store('attachments', 'public');
            $query->$field = $filePath;
            $query->save();

            $this->createTicketForQuery($query, $query->case_id);

            // Get the case from the query's case_id
            $case = \App\Models\Cases::find($query->case_id);
            if ($case) {
                $groupVendorData = \App\Models\User::where('id', $case->created_by)->first();
                if ($groupVendorData && $groupVendorData->vendor_case_group) {
                    $group = \App\Models\Group::find($groupVendorData->vendor_case_group);
                    if ($group) {
                        $grp_case_code = $case->case_code;
                        $grp_case_name = $case->name;
                        // Use post_two_claim_no if present, else post_claim_no, else claim_no
                        if (!empty($case->post_two_claim_no)) {
                            $grp_case_claim_no = $case->post_two_claim_no;
                        } elseif (!empty($case->post_claim_no)) {
                            $grp_case_claim_no = $case->post_claim_no;
                        } else {
                            $grp_case_claim_no = $case->claim_no;
                        }
                        $group->groupMessages()->create([
                            'sender_id' => 1,
                            'message' => "Query ready for Case ID $grp_case_code, Name: $grp_case_name, Claim Number: $grp_case_claim_no. Query PDF has been uploaded.",
                        ]);
                    }
                }
            }
            return response()->json(['success' => true, 'message' => 'PDF uploaded successfully.', 'file' => $filePath]);
        }

        return response()->json(['success' => false, 'message' => 'No file uploaded.'], 400);
    }


    /**
     * Get status counts for query filters
     */
    public function getStatusCounts(Request $request)
    {
        // Get all queries count
        $allCount = Courier::select('courier.id as courier_id')
            ->leftJoin('cases', 'courier.case_id', '=', 'cases.id')
            ->join('users', 'cases.created_by', '=', 'users.id')
            ->leftJoin('querys', function($join) {
                $join->on('querys.case_id', '=', 'cases.id');
            })
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
            ->where(function($query) {
                // Regular Query status filtering
                $query->where(function($q) {
                    $q->where('courier.case_type', 'main')
                      ->where('cases.status', 'Query');
                })
                ->orWhere(function($q) {
                    $q->where('courier.case_type', 'post')
                      ->where('cases.post_status', 'Query');
                })
                ->orWhere(function($q) {
                    $q->where('courier.case_type', 'postTwo')
                      ->where('cases.post_two_status', 'Query');
                });
            })
            ->whereNull('querys.query_pdf')
            ->groupBy('cases.id')
            ->get()
            ->count();

        // Get empty claim count
        $emptyClaimCount = Courier::select('courier.id as courier_id')
            ->leftJoin('cases', 'courier.case_id', '=', 'cases.id')
            ->join('users', 'cases.created_by', '=', 'users.id')
            ->leftJoin('querys', function($join) {
                $join->on('querys.case_id', '=', 'cases.id');
            })
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
            ->where(function($query) {
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
            })
            ->whereNull('querys.query_pdf')
            ->groupBy('cases.id')
            ->get()
            ->count();

        return response()->json([
            'all' => $allCount,
            'empty_claim' => $emptyClaimCount
        ]);
    }

    /**
     * Create ticket for query
     */
    private function createTicketForQuery($query, $caseId, $caseType = 'normal')
    {
        try {
            $ticketService = app(\App\Services\TicketService::class);
            $case = \App\Models\Cases::find($caseId);
            if (!$case) {
                return;
            }
            $vendor = \App\Models\User::find($case->created_by);
            if (!$vendor) {
                return;
            }
            $tpa = null;
            if ($caseType === 'post_1') {
                $tpa = \App\Models\User::find($case->post_tpa_allot_after_claim_no_received);
            } elseif ($caseType === 'post_2') {
                $tpa = \App\Models\User::find($case->post_two_tpa_allot_after_claim_no_received);
            } else {
                $tpa = \App\Models\User::find($case->tpa_allot_after_claim_no_received);
            }
            if (!$tpa) {
                return;
            }
            $ticket = $ticketService->createTicketFromQuery($query, $case, $vendor, $tpa, $caseType);
        } catch (\Exception $e) {
            \Log::error('Error creating ticket for query: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
        }
    }
}
