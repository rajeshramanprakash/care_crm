<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\Cases;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VendorController extends Controller
{
    public function dashboard()
    {

        $auth_user = Auth::guard('Vendor')->user();

        $main_claim_cases = Cases::where('is_post_1', 0)->where('is_post_2', 0)
            ->where('created_by', $auth_user->id)
            ->count();
        $main_claim_cases_query = Cases::where('is_post_1', 0)
            ->where(['status' => 'Query'])
            ->where('created_by', $auth_user->id)
            ->count();
        $main_claim_cases_investigation = Cases::where('is_post_1', 0)
            ->where(['status' => 'Investigation'])
            ->where('created_by', $auth_user->id)
            ->count();
        $main_claim_cases_reject = Cases::where('is_post_1', 0)
            ->where(['status' => 'Reject'])
            ->where('created_by', $auth_user->id)
            ->count();
        $main_claim_cases_underprocess = Cases::where('is_post_1', 0)
            ->where(['status' => 'UnderProcess'])
            ->where('created_by', $auth_user->id)
            ->count();
        $main_claim_cases_approved = Cases::where(['status' => 'Approved'])
            ->where('created_by', $auth_user->id)
            ->count();
        $main_claim_cases_inprocess = Cases::where(['status' => 'InProcess'])
            ->where('created_by', $auth_user->id)
            ->count();
        $main_claim_cases_paid = Cases::where(['status' => 'Paid'])
            ->where('created_by', $auth_user->id)
            ->count();
        $main_claim_cases_hold = Cases::where(['forward_status' => '0'])
            ->whereNotNull('forward_status_remark')
            ->where('created_by', $auth_user->id)
            ->count();
        $main_claim_cases_cancelled = Cases::where(['forward_status' => '2'])
            ->whereNotNull('forward_status_remark')
            ->where('created_by', $auth_user->id)
            ->count();


        $post_claim_cases = Cases::where('is_post_1', 1)->where('is_post_2', 0)
            ->where('created_by', $auth_user->id)
            ->count();
        $post_claim_cases_query = Cases::where('is_post_1', 1)
            ->where(['post_status' => 'Query'])
            ->where('created_by', $auth_user->id)
            ->count();
        $post_claim_cases_investigation = Cases::where('is_post_1', 1)
            ->where(['post_status' => 'Investigation'])
            ->where('created_by', $auth_user->id)
            ->count();
        $post_claim_cases_reject = Cases::where('is_post_1', 1)
            ->where(['post_status' => 'Reject'])
            ->where('created_by', $auth_user->id)
            ->count();
        $post_claim_cases_underprocess = Cases::where('is_post_1', 1)
            ->where(['post_status' => 'UnderProcess'])
            ->where('created_by', $auth_user->id)
            ->count();
        $post_claim_cases_approved = Cases::where('is_post_1', 1)
            ->where(['post_status' => 'Approved'])
            ->where('created_by', $auth_user->id)
            ->count();
        $post_claim_cases_inprocess = Cases::where('is_post_1', 1)
            ->where(['post_status' => 'InProcess'])
            ->where('created_by', $auth_user->id)
            ->count();
        $post_claim_cases_paid = Cases::where('is_post_1', 1)
            ->where(['post_status' => 'Paid'])
            ->where('created_by', $auth_user->id)
            ->count();


        $post_two_claim_cases = Cases::where('is_post_2', 1)
            ->where('created_by', $auth_user->id)
            ->count();
        $post_two_claim_cases_query = Cases::where('is_post_2', 1)
            ->where(['post_two_status' => 'Query'])
            ->where('created_by', $auth_user->id)
            ->count();
        $post_two_claim_cases_investigation = Cases::where('is_post_2', 1)
            ->where(['post_two_status' => 'Investigation'])
            ->where('created_by', $auth_user->id)
            ->count();
        $post_two_claim_cases_reject = Cases::where('is_post_2', 1)
            ->where(['post_two_status' => 'Reject'])
            ->where('created_by', $auth_user->id)
            ->count();
        $post_two_claim_cases_underprocess = Cases::where('is_post_2', 1)
            ->where(['post_two_status' => 'UnderProcess'])
            ->where('created_by', $auth_user->id)
            ->count();
        $post_two_claim_cases_approved = Cases::where('is_post_2', 1)
            ->where(['post_two_status' => 'Approved'])
            ->where('created_by', $auth_user->id)
            ->count();
        $post_two_claim_cases_inprocess = Cases::where('is_post_2', 1)
            ->where(['post_two_status' => 'InProcess'])
            ->where('created_by', $auth_user->id)
            ->count();
        $post_two_claim_cases_paid = Cases::where('is_post_2', 1)
            ->where(['post_two_status' => 'Paid'])
            ->where('created_by', $auth_user->id)
            ->count();


        // $pending_activity_cases_count = Cases::where('created_by', $auth_user->id)->where(function ($query) {
        //     $query->whereNotNull('pre_courier_no')
        //         ->orWhereNotNull('pre_dispatch_pdf_attachment');
        // })
        //     ->where('is_post_1', 0)
        //     ->orWhere(function ($query) {
        //         $query->whereNotNull('post_courier_no')
        //             ->orWhereNotNull('post_dispatch_pdf_attachment')
        //             ->where('is_post_2', 0);
        //     })
        //     ->count();


        $casesData = [];

        $postOneCasesData = Cases::where('post_one_allow', 1)
            ->where('created_by', $auth_user->id)
            ->get(['case_code'])
            ->map(function ($case) {
                return [
                    'case_code' => $case->case_code,
                    'desc' => 'Confirmation to the case to proceed for post',
                    'type' => 1
                ];
            });

        $postTwoCasesData = Cases::where('post_two_allow', 1)
            ->where('created_by', $auth_user->id)
            ->get(['case_code'])
            ->map(function ($case) {
                return [
                    'case_code' => $case->case_code,
                    'desc' => 'Confirmation to the case to proceed for post two',
                    'type' => 2
                ];
            });

        $queryCasesData = Cases::where('is_query', 1)
            ->where('created_by', $auth_user->id)
            ->get(['case_code'])
            ->map(function ($case) {
                return [
                    'case_code' => $case->case_code,
                    'desc' => 'There is a query for this case',
                    'type' => 3
                ];
            });

        $queryPostOneData = Cases::where('is_query_post', 1)
            ->where('created_by', $auth_user->id)
            ->get(['case_code'])
            ->map(function ($case) {
                return [
                    'case_code' => $case->case_code,
                    'desc' => 'There is a query for this post case',
                    'type' => 4
                ];
            });

        $queryPostTwoData = Cases::where('is_query_post_two', 1)
            ->where('created_by', $auth_user->id)
            ->get(['case_code'])
            ->map(function ($case) {
                return [
                    'case_code' => $case->case_code,
                    'desc' => 'There is a query for this post two case',
                    'type' => 5
                ];
            });

        $casesData = collect($postOneCasesData)
            ->merge($postTwoCasesData)
            ->merge($queryCasesData)
            ->merge($queryPostOneData)
            ->merge($queryPostTwoData)
            ->sortByDesc('case_code');
        $pending_activity_cases_count = $casesData->count();


        return view('vendor.dashboard', compact(
            'main_claim_cases',
            'main_claim_cases_query',
            'main_claim_cases_investigation',
            'main_claim_cases_reject',
            'main_claim_cases_underprocess',
            'main_claim_cases_approved',
            'main_claim_cases_paid',
            'main_claim_cases_hold',
            'main_claim_cases_cancelled',
            'main_claim_cases_inprocess',

            'post_claim_cases',
            'post_claim_cases_query',
            'post_claim_cases_investigation',
            'post_claim_cases_reject',
            'post_claim_cases_underprocess',
            'post_claim_cases_approved',
            'post_claim_cases_paid',
            'post_claim_cases_inprocess',

            'post_two_claim_cases',
            'post_two_claim_cases_query',
            'post_two_claim_cases_investigation',
            'post_two_claim_cases_reject',
            'post_two_claim_cases_underprocess',
            'post_two_claim_cases_approved',
            'post_two_claim_cases_paid',
            'post_two_claim_cases_inprocess',


            'pending_activity_cases_count',
            'casesData'
        ));
    }
}
