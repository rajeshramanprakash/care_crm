<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TpaCase;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TpaController extends Controller
{
    public function index($dashboard_filters = null)
    {
        $page_heading = 'Cases';
        $filter_params = "";
        $tpaMembers = User::whereRaw("FIND_IN_SET(?, role_id)", [8])->get();

        if ($dashboard_filters !== null) {
            $filter_params = ['dashboard_filters' => $dashboard_filters];
            $page_heading = ucwords(str_replace("_", " ", $dashboard_filters));
        }

        return view('admin.tpacase.index', compact('page_heading', 'filter_params', 'tpaMembers'));
    }

    public function ajax_list(Request $request)
    {
        $cases = TpaCase::select([
            'tpa_cases.id',
            'tpa_cases.claim_no',
            'tpa_cases.name',
            'tpa_cases.hospital',
            'tpa_cases.corp',
            'tpa_cases.paid_amt',
            'tpa_cases.approved_type',
            'tpa_cases.commission',
            'users.f_name as tpa_member',
        ])->join('users', 'tpa_cases.user_id', '=', 'users.id');
        return datatables()->of($cases)->make(true);
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'claim_no' => 'required|string|max:255',
            'name' => 'required|string|max:255',
            'hospital' => 'required|string|max:255',
            'corp' => 'required|string|max:255',
            'paid_amt' => 'required|numeric',
            'approved_type' => 'required|string',
            'user_id' => 'required|exists:users,id',
        ]);

        $user = User::find($validatedData['user_id']);
        $commission_main = $user->commission_main ?? 0;
        $commission_first = $user->commission_first ?? 0;
        $commission_second = $user->commission_second ?? 0;

        $commission_percentage = 0;
        if ($validatedData['approved_type'] == 'Direct Commission') {
            $commission_percentage = $commission_main;
        } elseif ($validatedData['approved_type'] == 'First Commission') {
            $commission_percentage = $commission_first;
        } elseif ($validatedData['approved_type'] == 'Second Commission') {
            $commission_percentage = $commission_second;
        }

        $commission_amount = ($validatedData['paid_amt'] * $commission_percentage) / 100;
        $user->wallet += $commission_amount;

        $validatedData['commission'] = $commission_amount;

        $case = TpaCase::create($validatedData);
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Case added successfully!',
            'data' => $case
        ]);
    }


    public function destroy($id)
    {
        $case = TpaCase::findOrFail($id);
        $user = User::find($case->user_id);
        if ($user && isset($case->commission)) {
            $user->wallet -= $case->commission;
            $user->save();
        }
        $case->delete();

        return response()->json(['success' => true]);
    }
}
