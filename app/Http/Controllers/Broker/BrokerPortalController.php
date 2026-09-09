<?php

namespace App\Http\Controllers\Broker;

use App\Http\Controllers\Controller;
use App\Models\BrokerUser;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;

class BrokerPortalController extends Controller
{
    private function currentUser(): ?BrokerUser
    {
        return BrokerUser::find(session('broker_user_id'));
    }

    public function dashboard()
    {
        $user = $this->currentUser();
        if (! $user || ! $user->is_active) {
            return redirect()->route('broker.login')->with('error', 'Session expired. Please login again.');
        }

        return view('broker.dashboard', compact('user'));
    }

    public function showPasswordForm()
    {
        $user = $this->currentUser();
        if (! $user || ! $user->is_active) {
            return redirect()->route('broker.login')->with('error', 'Session expired. Please login again.');
        }

        return view('broker.password', compact('user'));
    }

    public function updatePassword(Request $request)
    {
        $user = $this->currentUser();
        if (! $user || ! $user->is_active) {
            return redirect()->route('broker.login')->with('error', 'Session expired. Please login again.');
        }

        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'confirmed', Password::min(6)],
        ]);

        if (! $user->checkPassword($data['current_password'])) {
            return back()->withErrors(['current_password' => 'Current password is incorrect.']);
        }

        $user->password = $data['password'];
        $user->save();

        return redirect()->route('broker.password')->with('success', 'Password updated successfully.');
    }
}
