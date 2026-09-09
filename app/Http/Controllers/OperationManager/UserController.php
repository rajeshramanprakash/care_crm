<?php

namespace App\Http\Controllers\OperationManager;

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

        return view('operation_manager.users.index', compact('page_heading', 'roles', 'locations'));
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
            ->addColumn('action', function ($user) {
                return '<a href="' . route('admin.users.manage', $user->id) . '" class="btn btn-sm btn-primary">Edit</a>
                    <a href="javascript:void(0);" data-id="' . $user->id . '" class="btn btn-sm btn-danger delete-btn">Delete</a>';
            })
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

        $usersData = $users->get();

        return response()->json([
            'data' => $usersData,
            'total' => $usersData->count()
        ]);
    }

    public function getRoles()
    {
        $roles = Role::select('id', 'name')->get();
        return response()->json($roles);
    }
}
