<?php

namespace App\Http\Controllers\Corporate;

use App\Http\Controllers\Controller;
use App\Models\CorporateUser;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;

class CorporatePortalController extends Controller
{
    private function currentUser(): ?CorporateUser
    {
        return CorporateUser::query()
            ->with(['insurer:id,name,company_name', 'broker:id,name,company_name'])
            ->find(session('corporate_user_id'));
    }

    public function dashboard()
    {
        $user = $this->currentUser();
        if (! $user || ! $user->is_active) {
            return redirect()->route('corporate.login')->with('error', 'Session expired. Please login again.');
        }

        return view('corporate.dashboard', [
            'user' => $user,
            'login_url' => url('/corporate/login'),
        ]);
    }

    public function showPasswordForm()
    {
        $user = $this->currentUser();
        if (! $user || ! $user->is_active) {
            return redirect()->route('corporate.login')->with('error', 'Session expired. Please login again.');
        }

        return view('corporate.password', compact('user'));
    }

    public function updatePassword(Request $request)
    {
        $user = $this->currentUser();
        if (! $user || ! $user->is_active) {
            return redirect()->route('corporate.login')->with('error', 'Session expired. Please login again.');
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

        return redirect()->route('corporate.password')->with('success', 'Password updated successfully.');
    }
}
