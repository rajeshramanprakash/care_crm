<?php

namespace App\Http\Controllers\Corporate;

use App\Http\Controllers\Controller;
use App\Models\CorporateEmployee;
use App\Models\CorporateUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CorporateEmployeeAuthController extends Controller
{
    public function showLogin()
    {
        if (session()->has('corporate_employee_id') && session('login_type') === 'corporate_employee') {
            return redirect()->route('corporate.employee.dashboard');
        }

        return view('corporate.employee.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'corporate_username' => ['required', 'string', 'max:100'],
            'employee_id' => ['required', 'string', 'max:100'],
            'password' => ['required', 'string'],
        ]);

        $corporate = CorporateUser::query()
            ->where('username', $credentials['corporate_username'])
            ->where('is_active', true)
            ->first();

        if (! $corporate) {
            return back()
                ->withInput($request->only('corporate_username', 'employee_id'))
                ->with('error', 'Invalid corporate username or account is inactive.');
        }

        $employee = CorporateEmployee::query()
            ->where('corporate_user_id', $corporate->id)
            ->where('employee_id', $credentials['employee_id'])
            ->first();

        if (! $employee || ! $employee->is_active || ! $employee->checkPassword($credentials['password'])) {
            return back()
                ->withInput($request->only('corporate_username', 'employee_id'))
                ->with('error', 'Invalid employee ID or password, or account is inactive.');
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        $request->session()->regenerate();

        session([
            'corporate_employee_id' => $employee->id,
            'corporate_employee_name' => $employee->employee_name,
            'corporate_employee_code' => $employee->employee_id,
            'corporate_user_id' => $corporate->id,
            'corporate_user_name' => $corporate->corporate_name,
            'corporate_username' => $corporate->username,
            'login_type' => 'corporate_employee',
        ]);

        return redirect()->route('corporate.employee.dashboard');
    }

    public function logout(Request $request)
    {
        session()->forget([
            'corporate_employee_id',
            'corporate_employee_name',
            'corporate_employee_code',
            'corporate_user_id',
            'corporate_user_name',
            'corporate_username',
            'login_type',
        ]);
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('corporate.employee.login')->with('success', 'Logged out successfully.');
    }
}
