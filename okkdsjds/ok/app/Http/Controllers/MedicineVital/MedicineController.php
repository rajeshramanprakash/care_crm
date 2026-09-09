<?php

namespace App\Http\Controllers\MedicineVital;

use App\Http\Controllers\Controller;
use App\Models\Cases;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
    

class MedicineController extends Controller
{
    public function dashboard()
    {
        $auth_user = Auth::guard('MedicineVital')->user();
        $loggedRoles = session('logged_role');
        $main_claim_cases = Cases::where('is_post_1', 0)->where('is_post_2', 0)->where('forward_status', 1)
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
            ->groupBy('id')->get()->count();
            
        $post_claim_cases = Cases::where('is_post_1', 1)->where('is_post_2', 0)->where('forward_status', 1)
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
            ->groupBy('id')->get()->count();
            
        $post_two_claim_cases = Cases::where('is_post_2', 1)->where('forward_status', 1)
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
            ->groupBy('id')->get()->count();
        return view('medicinevital.dashboard', compact(
            'main_claim_cases',
            'post_claim_cases',
            'post_two_claim_cases',
        ));
    }
}
