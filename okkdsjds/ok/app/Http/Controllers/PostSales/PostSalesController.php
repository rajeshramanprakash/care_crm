<?php

namespace App\Http\Controllers\PostSales;

use App\Http\Controllers\Controller;
use App\Models\Cases;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PostSalesController extends Controller
{
    public function dashboard(){

        $auth_user = Auth::guard('PostSales')->user();

        $main_claim_cases = Cases::where('forward_status', 1)->where('is_post_1', 0)->where('is_post_2', 0)
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
        ->groupBy('id')
        ->get()
        ->count();
        $main_claim_cases_query = Cases::where('forward_status', 1)->where('is_post_1', 0)->where('forward_status', 1)
        ->where(['status' => 'Query'])
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
        ->groupBy('id')
        ->get()
        ->count();
        $main_claim_cases_investigation = Cases::where('forward_status', 1)->where('is_post_1', 0)->where('forward_status', 1)
        ->where(['status' => 'Investigation'])
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
        ->groupBy('id')
        ->get()
        ->count();
        $main_claim_cases_reject = Cases::where('forward_status', 1)->where('is_post_1', 0)->where('forward_status', 1)
        ->where(['status' => 'Reject'])
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
        ->groupBy('id')
        ->get()
        ->count();
        $main_claim_cases_underprocess = Cases::where('forward_status', 1)->where('is_post_1', 0)->where('forward_status', 1)
        ->where(['status' => 'UnderProcess'])
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
        ->groupBy('id')
        ->get()
        ->count();
        $main_claim_cases_approved = Cases::where('forward_status', 1)->where(['status' => 'Approved'])->where('forward_status', 1)
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
        ->groupBy('id')
        ->get()
        ->count();
        $main_claim_cases_inprocess = Cases::where('forward_status', 1)->where(['status' => 'InProcess'])->where('forward_status', 1)
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
        ->groupBy('id')
        ->get()
        ->count();
        $main_claim_cases_paid = Cases::where('forward_status', 1)->where(['status' => 'Paid'])->where('forward_status', 1)
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
        ->groupBy('id')
        ->get()
        ->count();


        $post_claim_cases = Cases::where('forward_status', 1)->where('is_post_1', 1)->where('is_post_2', 0)->where('forward_status', 1)
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
        ->groupBy('id')
        ->get()
        ->count();
        $post_claim_cases_query = Cases::where('forward_status', 1)->where('is_post_1', 1)->where('forward_status', 1)
        ->where(['post_status' => 'Query'])
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
        ->groupBy('id')
        ->get()
        ->count();
        $post_claim_cases_investigation = Cases::where('forward_status', 1)->where('is_post_1', 1)->where('forward_status', 1)
        ->where(['post_status' => 'Investigation'])
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
        ->groupBy('id')
        ->get()
        ->count();
        $post_claim_cases_reject = Cases::where('forward_status', 1)->where('is_post_1', 1)->where('forward_status', 1)
        ->where(['post_status' => 'Reject'])
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
        ->groupBy('id')
        ->get()
        ->count();
        $post_claim_cases_underprocess = Cases::where('forward_status', 1)->where('is_post_1', 1)->where('forward_status', 1)
        ->where(['post_status' => 'UnderProcess'])
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
        ->groupBy('id')
        ->get()
        ->count();
        $post_claim_cases_approved = Cases::where('forward_status', 1)->where(['post_status' => 'Approved'])->where('forward_status', 1)
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
        ->groupBy('id')
        ->get()
        ->count();
        $post_claim_cases_inprocess = Cases::where('forward_status', 1)->where(['post_status' => 'InProcess'])->where('forward_status', 1)
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
        ->groupBy('id')
        ->get()
        ->count();
        $post_claim_cases_paid = Cases::where('forward_status', 1)->where(['post_status' => 'Paid'])->where('forward_status', 1)
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
        ->groupBy('id')
        ->get()
        ->count();


        $post_two_claim_cases = Cases::where('forward_status', 1)->where('is_post_2', 1)->where('forward_status', 1)
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
        ->groupBy('id')
        ->get()
        ->count();
        $post_two_claim_cases_query = Cases::where('forward_status', 1)->where('is_post_2', 1)->where('forward_status', 1)
        ->where(['post_two_status' => 'Query'])
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
        ->groupBy('id')
        ->get()
        ->count();
        $post_two_claim_cases_investigation = Cases::where('forward_status', 1)->where('is_post_2', 1)->where('forward_status', 1)
        ->where(['post_two_status' => 'Investigation'])
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
        ->groupBy('id')
        ->get()
        ->count();

        $post_two_claim_cases_reject = Cases::where('forward_status', 1)->where('is_post_2', 1)->where('forward_status', 1)
        ->where(['post_two_status' => 'Reject'])
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
        ->groupBy('id')
        ->get()
        ->count();

        $post_two_claim_cases_underprocess = Cases::where('forward_status', 1)->where('is_post_2', 1)->where('forward_status', 1)
        ->where(['post_two_status' => 'UnderProcess'])
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
        ->groupBy('id')
        ->get()
        ->count();

        $post_two_claim_cases_approved = Cases::where('forward_status', 1)->where(['post_two_status' => 'Approved'])->where('forward_status', 1)
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
        ->groupBy('id')
        ->get()
        ->count();

        $post_two_claim_cases_inprocess = Cases::where('forward_status', 1)->where(['post_two_status' => 'InProcess'])->where('forward_status', 1)
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
        ->groupBy('id')
        ->get()
        ->count();

        $post_two_claim_cases_paid = Cases::where('forward_status', 1)->where(['post_two_status' => 'Paid'])->where('forward_status', 1)
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
        ->groupBy('id')
        ->get()
        ->count();

        return view('postsales.dashboard', compact(
            'main_claim_cases',
            'main_claim_cases_query',
            'main_claim_cases_investigation',
            'main_claim_cases_reject',
            'main_claim_cases_underprocess',
            'main_claim_cases_approved',
            'main_claim_cases_paid',
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
        ));
    }
}
