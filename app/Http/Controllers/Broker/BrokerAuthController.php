<?php

namespace App\Http\Controllers\Broker;

use App\Http\Controllers\Controller;
use App\Models\BrokerUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BrokerAuthController extends Controller
{
    public function showLogin()
    {
        if (session()->has('broker_user_id') && session('login_type') === 'broker') {
            return redirect()->route('broker.dashboard');
        }

        return view('broker.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'username' => ['required', 'string', 'max:100'],
            'password' => ['required', 'string'],
        ]);

        $user = BrokerUser::query()
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
            'broker_user_id' => $user->id,
            'broker_user_name' => $user->name,
            'login_type' => 'broker',
        ]);

        return redirect()->route('broker.dashboard');
    }

    public function logout(Request $request)
    {
        session()->forget(['broker_user_id', 'broker_user_name', 'login_type']);
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('broker.login')->with('success', 'Logged out successfully.');
    }
}
