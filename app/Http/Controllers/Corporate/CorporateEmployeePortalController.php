<?php

namespace App\Http\Controllers\Corporate;

use App\Http\Controllers\Controller;
use App\Models\CorporateEmployee;

class CorporateEmployeePortalController extends Controller
{
    public function dashboard()
    {
        $employee = CorporateEmployee::query()
            ->with('corporate.insurer:id,name,company_name', 'corporate.broker:id,name,company_name')
            ->find(session('corporate_employee_id'));

        if (! $employee || ! $employee->is_active) {
            return redirect()->route('corporate.employee.login')->with('error', 'Session expired. Please login again.');
        }

        return view('corporate.employee.dashboard', compact('employee'));
    }
}
