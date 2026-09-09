<?php

namespace App\Http\Controllers\TPA;

use App\Http\Controllers\Controller;
use App\Models\Cases;
use App\Models\Query;
use App\Models\TpaCase;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Helpers\CaseIdEncryption;

class CaseController extends Controller
{
    public function index($dashboard_filters = null)
    {
        $page_heading = 'Cases';
        $filter_params = "";
        if ($dashboard_filters !== null) {
            $filter_params = ['dashboard_filters' => $dashboard_filters];
            $page_heading = ucwords(str_replace("_", " ", $dashboard_filters));
        }
        return view('tpa.case.index', compact('page_heading'));
    }

    public function ajax_list(Request $request)
    {
        $auth_user = Auth::guard('TPA')->user();
        $cases = TpaCase::select([
            'tpa_cases.id',
            'tpa_cases.user_id',
            'tpa_cases.claim_no',
            'tpa_cases.name',
            'tpa_cases.hospital',
            'tpa_cases.corp',
            'tpa_cases.paid_amt',
            'tpa_cases.approved_type',
            'tpa_cases.commission',
        ])->where('user_id', $auth_user->id);
        return dataTables()->of($cases)
            ->addColumn('encrypted_id', function ($case) {
                return CaseIdEncryption::routeParam($case->id);
            })
            ->make(true);
    }

    public function show($encryptedId)
    {
        $id = CaseIdEncryption::decrypt($encryptedId);
        $case = Cases::findOrFail($id);
        $tpa_roles = User::whereRaw("FIND_IN_SET(?, role_id)", [8])->get();
        return view('tpa.case.show', compact('case', 'tpa_roles'));
    }
}
