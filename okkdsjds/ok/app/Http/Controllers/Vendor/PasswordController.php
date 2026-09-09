<?php
namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class PasswordController extends Controller
{
    public function verifyPassword(Request $request)
    {
        $request->validate([
            'password' => 'required'
        ]);

        $auth_user = Auth::guard('Vendor')->user();

        if (Hash::check($request->password, $auth_user->v_password)) {
            session(['vendor_password_verified' => true]);
            return response()->json(['success' => true]);
        }

        return response()->json(['success' => false]);
    }

    public function checkPasswordSession()
    {
        if (session()->has('vendor_password_verified') && session('vendor_password_verified') === true) {
            return response()->json(['access' => true]);
        }

        return response()->json(['access' => false]);
    }
}
