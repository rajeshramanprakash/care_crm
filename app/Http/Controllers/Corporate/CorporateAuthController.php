<?php

namespace App\Http\Controllers\Corporate;

use App\Http\Controllers\Controller;
use App\Models\CorporateUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CorporateAuthController extends Controller
{
    public function showLogin()
    {
        if (session()->has('corporate_user_id') && session('login_type') === 'corporate') {
            return redirect()->route('corporate.dashboard');
        }

        return view('corporate.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'username' => ['required', 'string', 'max:100'],
            'password' => ['required', 'string'],
        ]);

        $user = CorporateUser::query()
            ->with(['insurer:id,name,company_name', 'broker:id,name,company_name'])
            ->where('username', $credentials['username'])
            ->first();

        if (! $user || ! $user->is_active || ! $user->checkPassword($credentials['password'])) {
            return back()
                ->withInput($request->only('username'))
                ->with('error', 'Invalid username or password, or account is inactive.');
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        $request->session()->regenerate();

        session([
            'corporate_user_id' => $user->id,
            'corporate_user_name' => $user->corporate_name,
            'corporate_username' => $user->username,
            'login_type' => 'corporate',
            'corporate_created_by' => $user->createdByLabel(),
        ]);

        return redirect()->route('corporate.dashboard');
    }

    public function logout(Request $request)
    {
        session()->forget([
            'corporate_user_id',
            'corporate_user_name',
            'corporate_username',
            'corporate_created_by',
            'login_type',
        ]);
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('corporate.login')->with('success', 'Logged out successfully.');
    }
}
