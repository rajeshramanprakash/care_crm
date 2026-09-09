<?php

namespace App\Http\Controllers\Insurer;

use App\Http\Controllers\Controller;
use App\Models\InsurerUser;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;

class InsurerPortalController extends Controller
{
    private function currentUser(): ?InsurerUser
    {
        return InsurerUser::find(session('insurer_user_id'));
    }

    public function dashboard()
    {
        $user = $this->currentUser();
        if (! $user || ! $user->is_active) {
            return redirect()->route('insurer.login')->with('error', 'Session expired. Please login again.');
        }

        return view('insurer.dashboard', compact('user'));
    }

    public function showPasswordForm()
    {
        $user = $this->currentUser();
        if (! $user || ! $user->is_active) {
            return redirect()->route('insurer.login')->with('error', 'Session expired. Please login again.');
        }

        return view('insurer.password', compact('user'));
    }

    public function updatePassword(Request $request)
    {
        $user = $this->currentUser();
        if (! $user || ! $user->is_active) {
            return redirect()->route('insurer.login')->with('error', 'Session expired. Please login again.');
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

        return redirect()->route('insurer.password')->with('success', 'Password updated successfully.');
    }
}
