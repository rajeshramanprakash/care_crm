<?php

namespace App\Http\Controllers;

use App\Models\Cases;
use Illuminate\Http\Request;

class MassDataModifyController extends Controller
{
    public function vendor_data_replicate()
    {
        $post_one_assign_case = Cases::where('is_post_1', 0)
            ->whereNotNull('pre_dispatch_pdf_attachment')
            ->update(['post_one_allow' => 1]);

        $post_two_assign_case = Cases::where('is_post_1', 1)->where('is_post_2', 0)
            ->whereNotNull('post_dispatch_pdf_attachment')
            ->update(['post_two_allow' => 1]);

        $is_query = Cases::where(['status' => 'Query'])
            ->update(['is_query' => 1]);

        $is_query_post = Cases::where('is_post_1', 1)
            ->where(['post_status' => 'Query'])
            ->update(['is_query_post' => 1]);

        $is_query_post_two = Cases::where('is_post_2', 1)
            ->where(['post_two_status' => 'Query'])
            ->update(['is_query_post_two' => 1]);
    }

    public function send_case_from_past_to_next_department()
    {
        $loggedRoles = 7;


        $main_claim_cases = Cases::where('is_post_1', 0)->where('is_post_2', 0)->where('forward_status', 1)
        ->where(function ($query) use ($loggedRoles) {
            $query->where('assign_member_post_role', $loggedRoles)
            ->orWhere('assign_member_role', $loggedRoles);
        });
        $post_claim_cases = Cases::where('is_post_1', 1)->where('is_post_2', 0)->where('forward_status', 1)
        ->where(function ($query) use ($loggedRoles) {
            $query->where('assign_member_post_role', $loggedRoles)
            ->orWhere('assign_member_role', $loggedRoles);
        });
                $post_two_claim_cases = Cases::where('is_post_2', 1)->where('forward_status', 1)
        ->where(function ($query) use ($loggedRoles) {
            $query->where('assign_member_post_role', $loggedRoles)
            ->orWhere('assign_member_role', $loggedRoles);
        });

            $main_claim_cases->update(['assign_member_role' => $loggedRoles]);

            $main_claim_cases->whereNotNull('pre_courier_no')->update(['assign_member_role' => 9, 'is_working_row' => 0]);


            $post_claim_cases->update(['assign_member_post_role' => $loggedRoles]);

            $post_claim_cases->whereNotNull('post_courier_no')->update(['assign_member_post_role' => 9, 'is_working_row'=> 0]);


            $post_two_claim_cases->update(['assign_member_post_role' => $loggedRoles]);

            $post_two_claim_cases->whereNotNull('post_two_courier_no')->update(['assign_member_post_role' => 9, 'is_working_row'=> 0]);
    }
}
