<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\VendorCase;
use Illuminate\Http\Request;

class VendorCaseController extends Controller
{
    public function paid_cases_ajax(Request $request)
    {
        $cases = VendorCase::select('vendor_cases.*', 'users.f_name as user_name')
        ->join('users', 'users.id', '=', 'vendor_cases.user_id');
        return datatables()->of($cases)
        ->filterColumn('user_name', function ($query, $keyword) {
            $query->whereRaw('LOWER(users.f_name) LIKE ?', ["%{$keyword}%"]);
        })
        ->make(true);
    }

    public function delete($id)
    {
        $case = VendorCase::findOrFail($id);
        $mainVendorUser = User::where('id', $case->user_id)->first();
        $mainVendorUser->wallet += $case->commission;
        $mainVendorUser->save();

        $case->delete();
        
        session()->flash('status', ['success' => true, 'alert_type' => 'success', 'message' => "Vendor Paid Case deleted."]);
        return redirect()->back();
    }

    public function update_vendor_paid_case(Request $request){
        $case = VendorCase::findOrFail($request->case_id);
        $case->is_marked = $request->is_marked;
        $case->save();

        return response()->json(['success' => true, 'message' => 'Updated successfully']);
    }
}
