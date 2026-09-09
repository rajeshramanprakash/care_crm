<?php

namespace App\Http\Controllers\Insurer;

use App\Http\Controllers\Controller;
use App\Models\InsurerUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InsurerAuthController extends Controller
{
    public function showLogin()
    {
        if (session()->has('insurer_user_id') && session('login_type') === 'insurer') {
            return redirect()->route('insurer.dashboard');
        }

        return view('insurer.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'username' => ['required', 'string', 'max:100'],
            'password' => ['required', 'string'],
        ]);

        $user = InsurerUser::query()
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
            'insurer_user_id' => $user->id,
            'insurer_user_name' => $user->name,
            'login_type' => 'insurer',
        ]);

        return redirect()->route('insurer.dashboard');
    }

    public function logout(Request $request)
    {
        session()->forget(['insurer_user_id', 'insurer_user_name', 'login_type']);
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('insurer.login')->with('success', 'Logged out successfully.');
    }
}
