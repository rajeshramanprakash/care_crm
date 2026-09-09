<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required'
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $credentials = $request->only('email', 'password');
        if (Auth::attempt($credentials)) {
            $user = Auth::user();


            $roles = explode(',', $user->role_id);

            if (empty($roles)) {
                Auth::logout();
                return redirect()->back()->with('error', 'No roles assigned to this account.');
            }

            if (count($roles) > 1) {
                $roleNames = Role::whereIn('id', $roles)->pluck('name')->toArray();
                return view('role-select', ['roles' => $roleNames]);
            }

            $role = Role::find($roles[0]);
            if (!$role) {
                Auth::logout();
                Log::error('Invalid role configuration for user: ' . $user->id . ', role_id: ' . $roles[0]);
                return redirect()->back()->with('error', 'Invalid role configuration.');
            }

            try {
                session()->put('logged_role', $role->id);
                session()->put('role_name', $role->name);
                session()->save();

                Log::info('User logged in successfully', [
                    'user_id' => $user->id,
                    'role_id' => $role->id,
                    'role_name' => $role->name
                ]);

                return $this->redirectBasedOnRole($role->name);
            } catch (\Exception $e) {
                Log::error('Failed to set session data', [
                    'user_id' => $user->id,
                    'role_id' => $role->id,
                    'error' => $e->getMessage()
                ]);
                Auth::logout();
                return redirect()->back()->with('error', 'Login failed. Please try again.');
            }
        }

        return redirect()->back()->with('error', 'Invalid credentials');
    }

    public function selectRole(Request $request)
    {
        if (!Auth::check()) {
            return redirect()->route('home')->with('error', 'Please login first');
        }

        $roleName = $request->input('role');
        $user = Auth::user();

        if (!$roleName) {
            Log::warning('Role selection attempted without role name', ['user_id' => $user->id]);
            return redirect()->back()->with('error', 'Please select a role');
        }

        $role = Role::where('name', $roleName)->first();

        if (!$role) {
            Log::error('Invalid role selected', [
                'user_id' => $user->id,
                'role_name' => $roleName
            ]);
            return redirect()->back()->with('error', 'Invalid role selected');
        }

        $userRoles = explode(',', $user->role_id);

        if (!in_array($role->id, $userRoles)) {
            Log::warning('Unauthorized role selection attempt', [
                'user_id' => $user->id,
                'attempted_role' => $roleName,
                'user_roles' => $userRoles
            ]);
            return redirect()->back()->with('error', 'You are not authorized to select this role');
        }

        try {
            session()->put('logged_role', $role->id);
            session()->put('role_name', $role->name);
            session()->save();

            Log::info('Role selected successfully', [
                'user_id' => $user->id,
                'role_id' => $role->id,
                'role_name' => $role->name
            ]);

            return $this->redirectBasedOnRole($roleName);
        } catch (\Exception $e) {
            Log::error('Failed to set role session data', [
                'user_id' => $user->id,
                'role_id' => $role->id,
                'error' => $e->getMessage()
            ]);
            return redirect()->back()->with('error', 'Failed to select role. Please try again.');
        }
    }

    private function redirectBasedOnRole($role)
    {
        $routes = [
            'Admin' => 'admin.dashboard',
            'Sales' => 'sales.dashboard',
            'Sales Manager' => 'manager.dashboard',
            'Operation Manager' => 'operation-manager.dashboard',
            'Operation' => 'operation.dashboard'
        ];

        if (!isset($routes[$role])) {
            Log::error('No route defined for role: ' . $role);
            return redirect()->route('home')->with('error', 'Invalid role configuration');
    }

        return redirect()->route($routes[$role]);
    }

    public function logout()
    {
        $userId = Auth::id();
        $roleName = session('role_name');

        Log::info('User logged out', [
            'user_id' => $userId,
            'role_name' => $roleName
        ]);

        Session::flush();
        Auth::logout();
        return redirect()->route('home');
    }

    public function update_profile_image(Request $request, $member_id = null)
    {
        if ($request->hasFile('profile_image')) {
            $file = $request->file('profile_image');
            $filename = time() . '.' . $file->getClientOriginalExtension();
            $file->storeAs('public/profile_images', $filename);

            $user = $member_id ? User::find($member_id) : Auth::user();
            if (!$user) {
                return response()->json(['success' => false, 'message' => 'User not found']);
            }

            $user->profile_image = 'profile_images/' . $filename;
            $user->save();

            return response()->json(['success' => true, 'message' => 'Profile image updated successfully']);
        }

        return response()->json(['success' => false, 'message' => 'No image file found']);
    }
}
