<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index()
    {
        $page_heading = 'Users List';
        $roles = Role::all();
        $locations = \App\Models\Location::all();

        return view('manager.users.index', compact('page_heading', 'roles', 'locations'));
    }

    public function getUsers(Request $request)
    {

        $users = User::select([
            'users.id',
            'users.profile_image',
            'users.f_name',
            'users.l_name',
            'users.email',
            'users.mobile',
            'users.created_at',
            DB::raw('(SELECT GROUP_CONCAT(name SEPARATOR ", ") FROM locations WHERE FIND_IN_SET(locations.id, users.location_id)) AS location_name'),
            DB::raw('(SELECT GROUP_CONCAT(name SEPARATOR ", ") FROM roles WHERE FIND_IN_SET(roles.id, users.role_id)) AS roles'),
            DB::raw('(SELECT CONCAT(f_name, " ", l_name) FROM users AS parent WHERE parent.id = users.parent_id) AS parent_name'),
            'users.lead_type',
            'users.is_active',
            'users.is_break',
        ])->where('parent_id', auth()->user()->id);

        if ($request->has('role') && !empty($request->role)) {
            $users = $users->whereRaw('(SELECT COUNT(*) FROM roles WHERE FIND_IN_SET(roles.id, users.role_id) AND roles.name = ?) > 0', [$request->role]);
        }

        return datatables()->of($users)
            ->addColumn('roles', function ($user) {
                return $user->roles ?: '<span class="text-muted">No roles</span>';
            })
            ->addColumn('location_name', function ($user) {
                return $user->location_name ?: '<span class="text-muted">No location</span>';
            })
            ->addColumn('lead_type', function ($user) {
                if (!$user->lead_type) return '<span class="text-muted">No lead type</span>';
                $types = explode(',', $user->lead_type);
                return implode(', ', array_map('ucfirst', $types));
            })
            ->rawColumns(['action', 'roles', 'location_name', 'lead_type'])
            ->make(true);
    }

    public function manage($id = null)
    {
        $roles = Role::select('id', 'name')->get();
        $locations = \App\Models\Location::all();
        $parentUsers = User::whereRaw('FIND_IN_SET(role_id, "3,5")')->get();
        $lead_types = ['web', 'ivr', 'whatsapp'];

        $user = null;

        if ($id) {
            $user = User::findOrFail($id);
            $user->role_id = explode(',', $user->role_id);
            $user->location_id = explode(',', $user->location_id);
            $user->lead_type = isset($user->lead_type) ? explode(',', $user->lead_type) : [];
        }

        return view('admin.users.manage', compact('user', 'roles', 'locations', 'parentUsers', 'lead_types'));
    }

    public function manage_process(Request $request, $id = null)
    {
        $validate = Validator::make($request->all(), [
            'f_name' => 'required|string|min:3|max:255',
            'l_name' => 'required|string|min:3|max:255',
            'email' => ['required','email',Rule::unique('users')->ignore($id)->whereNull('deleted_at')],
            'mobile' => 'required|string|max:20',
            'role_id' => 'required|array|min:1',
            'location_id' => 'required|array|min:1',
            'lead_type' => 'required|array|min:1',
            'parent_id' => 'nullable|exists:users,id',
            'password' => $id ? 'nullable|min:6' : 'required|min:6',
        ]);

        if ($validate->fails()) {
            return redirect()->back()->withErrors($validate)->withInput();
        }

        $roleIds = implode(',', $request->role_id);
        $locationIds = implode(',', $request->location_id);
        $leadTypes = implode(',', $request->lead_type);

        if ($id) {
            $user = User::findOrFail($id);
            $user->update([
                'f_name' => $request->f_name,
                'l_name' => $request->l_name,
                'email' => $request->email,
                'mobile' => $request->mobile,
                'role_id' => $roleIds,
                'location_id' => $locationIds,
                'parent_id' => $request->parent_id,
                'lead_type' => $leadTypes,
            ]);

            if ($request->filled('password')) {
                $user->update(['password' => bcrypt($request->password)]);
            }

            $message = 'User updated successfully!';
        } else {
            $user = User::create([
                'f_name' => $request->f_name,
                'l_name' => $request->l_name,
                'email' => $request->email,
                'mobile' => $request->mobile,
                'role_id' => $roleIds,
                'location_id' => $locationIds,
                'lead_type' => $leadTypes,
                'parent_id' => $request->parent_id,
                'password' => bcrypt($request->password),
            ]);

            $message = 'User created successfully!';
        }

        return redirect()->route('admin.users.index')->with('status', [
            'alert_type' => 'success',
            'message' => $message,
        ]);
    }

    public function destroy($id)
    {
        $user = User::find($id);
        $user->delete();

        return response()->json(['status' => 'success', 'message' => 'User deleted successfully!']);
    }

    public function getUsersForApp(Request $request)
    {
        $users = User::select([
            'users.id',
            'users.profile_image',
            'users.f_name',
            'users.l_name',
            'users.email',
            'users.mobile',
            'users.created_at',
            DB::raw('(SELECT GROUP_CONCAT(name SEPARATOR ", ") FROM locations WHERE FIND_IN_SET(locations.id, users.location_id)) AS location_name'),
            DB::raw('(SELECT GROUP_CONCAT(name SEPARATOR ", ") FROM roles WHERE FIND_IN_SET(roles.id, users.role_id)) AS roles'),
            DB::raw('(SELECT CONCAT(f_name, " ", l_name) FROM users AS parent WHERE parent.id = users.parent_id) AS parent_name'),
            'users.lead_type',
            'users.is_active',
            'users.is_break',
        ])->where('parent_id', auth()->user()->id);

        if ($request->has('role') && !empty($request->role)) {
            $users = $users->whereRaw('(SELECT COUNT(*) FROM roles WHERE FIND_IN_SET(roles.id, users.role_id) AND roles.name = ?) > 0', [$request->role]);
        }

        $users = $users->get();

        // Transform the data for mobile app
        $transformedUsers = $users->map(function ($user) {
            return [
                'id' => $user->id,
                'profile_image' => $user->profile_image,
                'f_name' => $user->f_name,
                'l_name' => $user->l_name,
                'email' => $user->email,
                'mobile' => $user->mobile,
                'created_at' => $user->created_at,
                'location_name' => $user->location_name ?: 'No location',
                'roles' => $user->roles ?: 'No roles',
                'parent_name' => $user->parent_name,
                'lead_type' => $user->lead_type ? implode(', ', array_map('ucfirst', explode(',', $user->lead_type))) : 'No lead type',
                'is_active' => (bool) $user->is_active,
                'is_break' => (bool) $user->is_break,
            ];
        });

        return response()->json($transformedUsers);
    }

    public function toggleDutyStatus(Request $request, $id)
    {
        try {
            $user = User::where('id', $id)
                ->where('parent_id', auth()->user()->id)
                ->first();

            if (!$user) {
                return response()->json(['success' => false, 'message' => 'User not found or not under your supervision'], 404);
            }

            $newStatus = !$user->is_active;
            $user->update(['is_active' => $newStatus]);

            // Log the duty status change
            if ($newStatus) {
                // User went on duty
                \App\Models\DutyLogs::create([
                    'user_id' => $user->id,
                    'break_status' => 'active',
                    'break_reason' => 'Started duty',
                    'break_start_time' => now(),
                    'break_end_time' => null,
                ]);
            } else {
                // User went off duty - need reason
                $reason = $request->input('reason', 'No reason provided');

                // End the current active duty log
                $activeLog = \App\Models\DutyLogs::where('user_id', $user->id)
                    ->where('break_status', 'active')
                    ->whereNull('break_end_time')
                    ->first();

                if ($activeLog) {
                    $activeLog->update([
                        'break_end_time' => now(),
                        'break_reason' => $reason
                    ]);
                }

                // Create new inactive log
                \App\Models\DutyLogs::create([
                    'user_id' => $user->id,
                    'break_status' => 'inactive',
                    'break_reason' => $reason,
                    'break_start_time' => now(),
                    'break_end_time' => now(),
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => $newStatus ? 'User set to On Duty' : 'User set to Off Duty',
                'is_active' => $newStatus
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating duty status: ' . $e->getMessage()
            ], 500);
        }
    }

    public function toggleBreakStatus(Request $request, $id)
    {
        try {
            $user = User::where('id', $id)
                ->where('parent_id', auth()->user()->id)
                ->first();

            if (!$user) {
                return response()->json(['success' => false, 'message' => 'User not found or not under your supervision'], 404);
            }

            $newStatus = !$user->is_break;
            $user->update(['is_break' => $newStatus]);

            // Log the break status change
            if ($newStatus) {
                // User went on break - need reason
                $reason = $request->input('reason', 'No reason provided');

                \App\Models\BreakLog::create([
                    'user_id' => $user->id,
                    'break_status' => 'offline',
                    'break_reason' => $reason,
                    'break_start_time' => now(),
                    'break_end_time' => null,
                ]);
            } else {
                // User went active
                $reason = $request->input('reason', 'Break ended');

                // End the current active break log
                $activeLog = \App\Models\BreakLog::where('user_id', $user->id)
                    ->where('break_status', 'offline')
                    ->whereNull('break_end_time')
                    ->first();

                if ($activeLog) {
                    $activeLog->update([
                        'break_end_time' => now(),
                        'break_reason' => $reason
                    ]);
                }

                // Create new online log
                \App\Models\BreakLog::create([
                    'user_id' => $user->id,
                    'break_status' => 'online',
                    'break_reason' => $reason,
                    'break_start_time' => now(),
                    'break_end_time' => now(),
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => $newStatus ? 'User set to Break' : 'User set to Active',
                'is_break' => $newStatus
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating break status: ' . $e->getMessage()
            ], 500);
        }
    }
}
