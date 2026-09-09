<?php

namespace App\Http\Controllers\SubAdmin;

use App\Http\Controllers\Controller;
use App\Models\Cases;
use Illuminate\Http\Request;

class SubAdminController extends Controller
{
    public function dashboard(){

        $main_claim_cases = Cases::where('is_post_1', 0)
        ->where(['forward_status' => 0, 'forward_status_remark'=> null])
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
        ->count();


        return view('subadmin.dashboard', compact(
            'main_claim_cases',
        ));
    }
}
