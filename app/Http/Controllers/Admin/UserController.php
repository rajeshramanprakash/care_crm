<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use App\Services\TataService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index()
    {
        // Check if this is an API request
        if (request()->expectsJson()) {
            $users = User::select([
                'users.id',
                'users.profile_image',
                'users.f_name',
                'users.l_name',
                'users.email',
                'users.mobile',
                'users.created_at',
                'users.role_id',
                'users.location_id',
                'users.parent_id',
                'users.services',
                DB::raw('(SELECT GROUP_CONCAT(name SEPARATOR ", ") FROM locations WHERE FIND_IN_SET(locations.id, users.location_id)) AS location_name'),
                DB::raw('(SELECT GROUP_CONCAT(name SEPARATOR ", ") FROM roles WHERE FIND_IN_SET(roles.id, users.role_id)) AS roles'),
                DB::raw('(SELECT CONCAT(f_name, " ", l_name) FROM users AS parent WHERE parent.id = users.parent_id) AS parent_name'),
                'users.lead_type',
                'users.is_active',
                'users.is_break',
                'users.tata_agent_id',
            ])
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('doctor_requests')
                    ->where(function ($sq) {
                        $sq->whereColumn('doctor_requests.mobile', 'users.mobile')
                            ->orWhereColumn('doctor_requests.contact_no', 'users.mobile');
                    });
            });

            if (request()->filled('role')) {
                $users = $users->whereRaw(
                    '(SELECT COUNT(*) FROM roles WHERE FIND_IN_SET(roles.id, users.role_id) AND roles.name = ?) > 0',
                    [request('role')]
                );
            }

            $users = $users->orderBy('users.id', 'desc')->get();

            return response()->json($users);
        }

        $page_heading = 'Users List';
        $roles = Role::all();
        $locations = \App\Models\Location::all();

        return view('admin.users.index', compact('page_heading', 'roles', 'locations'));
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
            'users.tata_agent_id',
        ])
        ->whereNotExists(function ($q) {
            $q->select(DB::raw(1))
                ->from('doctor_requests')
                ->where(function ($sq) {
                    $sq->whereColumn('doctor_requests.mobile', 'users.mobile')
                        ->orWhereColumn('doctor_requests.contact_no', 'users.mobile');
                });
        });

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
            ->filterColumn('parent_name', function($query, $keyword) {
                $query->whereRaw("(SELECT CONCAT(f_name, ' ', l_name) FROM users AS parent WHERE parent.id = users.parent_id) LIKE ?", ["%{$keyword}%"]);
            })
            ->filterColumn('location_name', function($query, $keyword) {
                $query->whereRaw("(SELECT GROUP_CONCAT(name SEPARATOR ', ') FROM locations WHERE FIND_IN_SET(locations.id, users.location_id)) LIKE ?", ["%{$keyword}%"]);
            })
            ->filterColumn('roles', function($query, $keyword) {
                $query->whereRaw("(SELECT GROUP_CONCAT(name SEPARATOR ', ') FROM roles WHERE FIND_IN_SET(roles.id, users.role_id)) LIKE ?", ["%{$keyword}%"]);
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
        $services = \App\Models\Service::all();

        $user = null;

        if ($id) {
            $user = User::findOrFail($id);
            $user->role_id = explode(',', $user->role_id);
            $user->location_id = explode(',', $user->location_id);
            $user->lead_type = isset($user->lead_type) ? explode(',', $user->lead_type) : [];
            $user->services = isset($user->services) ? explode(',', $user->services) : [];
        }

        return view('admin.users.manage', compact('user', 'roles', 'locations', 'parentUsers', 'lead_types', 'services'));
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
            'services' => 'nullable|array',
        ]);

        if ($validate->fails()) {
            return redirect()->back()->withErrors($validate)->withInput();
        }

        $roleIds = implode(',', $request->role_id);
        $locationIds = implode(',', $request->location_id);
        $leadTypes = implode(',', $request->lead_type);
        $services = $request->services ? implode(',', $request->services) : null;

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
                'services' => $services,
            ]);

            if ($request->filled('password')) {
                $user->update(['password' => bcrypt($request->password)]);
            }

            $message = 'User updated successfully!';
        } else {
            // Check for soft-deleted user with same email
            $existing = User::withTrashed()->where('email', $request->email)->first();
            if ($existing && $existing->trashed()) {
                // Restore and update the user
                $existing->restore();
                $existing->update([
                    'f_name' => $request->f_name,
                    'l_name' => $request->l_name,
                    'mobile' => $request->mobile,
                    'role_id' => $roleIds,
                    'location_id' => $locationIds,
                    'lead_type' => $leadTypes,
                    'parent_id' => $request->parent_id,
                    'password' => bcrypt($request->password),
                    'services' => $services,
                ]);
                $user = $existing;
                $message = 'User restored and updated successfully!';
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
                    'services' => $services,
                ]);
                $message = 'User created successfully!';
            }
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

    // API Methods for Mobile App
    public function getRoles()
    {
        $roles = Role::select('id', 'name')->get();
        return response()->json($roles);
    }

    public function getLocations()
    {
        $locations = \App\Models\Location::query()
            ->select('id', 'name', 'state')
            ->orderBy('name')
            ->get()
            ->map(fn (\App\Models\Location $location) => [
                'id' => $location->id,
                'name' => $location->name,
                'state' => $location->state,
                'display_label' => $location->display_label,
            ]);

        return response()->json($locations);
    }

    public function getParentUsers()
    {
        $parentUsers = User::whereRaw('FIND_IN_SET(role_id, "3,5")')
            ->select('id', 'f_name', 'l_name')
            ->get()
            ->map(function($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->f_name . ' ' . $user->l_name,
                    'f_name' => $user->f_name,
                    'l_name' => $user->l_name
                ];
            });
        return response()->json($parentUsers);
    }

    public function getLeadTypes()
    {
        $leadTypes = [
            ['id' => 'web', 'name' => 'Web'],
            ['id' => 'ivr', 'name' => 'IVR'],
            ['id' => 'whatsapp', 'name' => 'WhatsApp']
        ];
        return response()->json($leadTypes);
    }

    public function store(Request $request)
    {
        $validate = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'f_name' => 'required|string|min:3|max:255',
            'l_name' => 'required|string|min:3|max:255',
            'email' => 'required|email|unique:users,email,NULL,id,deleted_at,NULL',
            'mobile' => 'required|string|max:20',
            'role_id' => 'required|string',
            'location_id' => 'required|string',
            'lead_type' => 'required|string',
            'parent_id' => 'nullable|exists:users,id',
            'password' => 'required|min:6',
            'services' => 'nullable|string',
        ]);

        if ($validate->fails()) {
            return response()->json(['errors' => $validate->errors()], 422);
        }

        // Check for soft-deleted user with same email
        $existing = User::withTrashed()->where('email', $request->email)->first();
        if ($existing && $existing->trashed()) {
            // Restore and update the user
            $existing->restore();
            $existing->update([
                'f_name' => $request->f_name,
                'l_name' => $request->l_name,
                'mobile' => $request->mobile,
                'role_id' => $request->role_id,
                'location_id' => $request->location_id,
                'lead_type' => $request->lead_type,
                'parent_id' => $request->parent_id,
                'password' => bcrypt($request->password),
                'services' => $request->services ?: null,
            ]);
            $user = $existing;
            $message = 'User restored and updated successfully!';
        } else {
            $user = User::create([
                'f_name' => $request->f_name,
                'l_name' => $request->l_name,
                'email' => $request->email,
                'mobile' => $request->mobile,
                'role_id' => $request->role_id,
                'location_id' => $request->location_id,
                'lead_type' => $request->lead_type,
                'parent_id' => $request->parent_id,
                'password' => bcrypt($request->password),
                'services' => $request->services ?: null,
            ]);
            $message = 'User created successfully!';
        }

        return response()->json([
            'message' => $message,
            'user' => $user
        ]);
    }

    public function show($id)
    {
        $user = User::findOrFail($id);

        if (request()->expectsJson()) {
            // Get location names
            $locationIds = explode(',', $user->location_id);
            $locations = \App\Models\Location::whereIn('id', $locationIds)->pluck('name')->toArray();
            $user->location_name = implode(', ', $locations);

            // Get role names
            $roleIds = explode(',', $user->role_id);
            $roles = Role::whereIn('id', $roleIds)->pluck('name')->toArray();
            $user->roles = implode(', ', $roles);

            // Get parent name
            if ($user->parent_id) {
                $parent = User::find($user->parent_id);
                $user->parent_name = $parent ? $parent->f_name . ' ' . $parent->l_name : null;
            }

            return response()->json($user);
        }

        return view('admin.users.show', compact('user'));
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validate = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'f_name' => 'required|string|min:3|max:255',
            'l_name' => 'required|string|min:3|max:255',
            'email' => ['required','email',\Illuminate\Validation\Rule::unique('users')->ignore($id)->whereNull('deleted_at')],
            'mobile' => 'required|string|max:20',
            'role_id' => 'required|string',
            'location_id' => 'required|string',
            'lead_type' => 'required|string',
            'parent_id' => 'nullable|exists:users,id',
            'password' => 'nullable|min:6',
            'services' => 'nullable|string',
        ]);

        if ($validate->fails()) {
            return response()->json(['errors' => $validate->errors()], 422);
        }

        $user->update([
            'f_name' => $request->f_name,
            'l_name' => $request->l_name,
            'email' => $request->email,
            'mobile' => $request->mobile,
            'role_id' => $request->role_id,
            'location_id' => $request->location_id,
            'parent_id' => $request->parent_id,
            'lead_type' => $request->lead_type,
            'services' => $request->services ?: null,
        ]);

        if ($request->filled('password')) {
            $user->update(['password' => bcrypt($request->password)]);
        }

        return response()->json([
            'message' => 'User updated successfully',
            'user' => $user
        ]);
    }

    public function updateDutyStatus(Request $request, $id)
    {
        $user = User::findOrFail($id);
        $oldStatus = $user->is_active;
        $user->update(['is_active' => $request->is_active]);

        \Illuminate\Support\Facades\Log::info("Duty status update - User: {$user->f_name} {$user->l_name}, Old: {$oldStatus}, New: {$request->is_active}");

        // If going off duty, set end time for previous active session
        if ($oldStatus == 1 && $request->is_active == 0) {
            // Find the last active duty log and set end time
            $lastActiveLog = \App\Models\DutyLogs::where('user_id', $user->id)
                ->where('break_status', 'active')
                ->whereNull('break_end_time')
                ->latest()
                ->first();
            
            if ($lastActiveLog) {
                $lastActiveLog->update(['break_end_time' => now()]);
                \Illuminate\Support\Facades\Log::info("Closed previous active duty log: {$lastActiveLog->id}");
            }
        }
        
        // If going on duty, set end time for previous inactive session
        if ($oldStatus == 0 && $request->is_active == 1) {
            $lastInactiveLog = \App\Models\DutyLogs::where('user_id', $user->id)
                ->where('break_status', 'inactive')
                ->whereNull('break_end_time')
                ->latest()
                ->first();
            
            if ($lastInactiveLog) {
                $lastInactiveLog->update(['break_end_time' => now()]);
                \Illuminate\Support\Facades\Log::info("Closed previous inactive duty log: {$lastInactiveLog->id}");
            }
        }

        // Create duty log entry
        $dutyLog = new \App\Models\DutyLogs();
        $dutyLog->user_id = $user->id;
        $dutyLog->break_status = $request->is_active == 1 ? 'active' : 'inactive';
        $dutyLog->break_reason = $request->reason ?? null;
        $dutyLog->break_start_time = now();
        
        // If going off duty, set end time for this session immediately
        if ($request->is_active == 0) {
            $dutyLog->break_end_time = now();
        }
        
        $dutyLog->save();

        \Illuminate\Support\Facades\Log::info("Created new duty log: {$dutyLog->id}, Status: {$dutyLog->break_status}, Start: {$dutyLog->break_start_time}, End: {$dutyLog->break_end_time}");

        // Log the duty status change with reason if provided
        if ($request->has('reason') && $request->reason) {
            \Illuminate\Support\Facades\Log::info("User {$user->f_name} {$user->l_name} went off duty. Reason: {$request->reason}");
        }

        return response()->json([
            'message' => 'Duty status updated successfully',
            'user' => $user
        ]);
    }

    public function updateBreakStatus(Request $request, $id)
    {
        $user = User::findOrFail($id);
        $oldStatus = $user->is_break;
        $user->update(['is_break' => $request->is_break]);

        // If going on break, set end time for previous online session
        if ($oldStatus == 0 && $request->is_break == 1) {
            // Find the last online break log and set end time
            $lastOnlineLog = \App\Models\BreakLog::where('user_id', $user->id)
                ->where('break_status', 'online')
                ->whereNull('break_end_time')
                ->latest()
                ->first();
            
            if ($lastOnlineLog) {
                $lastOnlineLog->update(['break_end_time' => now()]);
            }
        }
        
        // If going online, set end time for previous offline session
        if ($oldStatus == 1 && $request->is_break == 0) {
            $lastOfflineLog = \App\Models\BreakLog::where('user_id', $user->id)
                ->where('break_status', 'offline')
                ->whereNull('break_end_time')
                ->latest()
                ->first();
            
            if ($lastOfflineLog) {
                $lastOfflineLog->update(['break_end_time' => now()]);
            }
        }

        // Create break log entry
        $breakLog = new \App\Models\BreakLog();
        $breakLog->user_id = $user->id;
        $breakLog->break_status = $request->is_break == 1 ? 'offline' : 'online';
        $breakLog->break_reason = $request->reason ?? null;
        $breakLog->break_start_time = now();
        
        // If going on break, set end time for this session immediately
        if ($request->is_break == 1) {
            $breakLog->break_end_time = now();
        }
        
        $breakLog->save();

        // Log the break status change with reason if provided
        if ($request->has('reason') && $request->reason) {
            \Illuminate\Support\Facades\Log::info("User {$user->f_name} {$user->l_name} went on break. Reason: {$request->reason}");
        }

        return response()->json([
            'message' => 'Break status updated successfully',
            'user' => $user
        ]);
    }

    /**
     * Create agent in Tata TeleServices system
     */
    public function createAgent(Request $request, $id)
    {
        try {
            $user = User::findOrFail($id);
            
            // Check if user already has Tata agent created
            if ($user->tata_agent_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Agent already created for this user'
                ], 400);
            }

            $tataService = new TataService();
            $result = $tataService->createAgent($user);

            // Debug: Log the result
            \Log::info('Create Agent Result', [
                'user_id' => $user->id,
                'result' => $result
            ]);

            if ($result['success']) {
                // Update user with Tata agent ID if provided in response
                $tataAgentId = null;
                if (isset($result['data']['data']['id'])) {
                    $tataAgentId = $result['data']['data']['id'];
                } elseif (isset($result['data']['user_id'])) {
                    $tataAgentId = $result['data']['user_id'];
                }
                
                if ($tataAgentId) {
                    $user->update(['tata_agent_id' => $tataAgentId]);
                    
                    // Automatically update the agent to make it fully active
                    $updateResult = $tataService->updateAgent($user, $tataAgentId);
                    
                    if ($updateResult['success']) {
                        return response()->json([
                            'success' => true,
                            'message' => 'Agent created and activated successfully',
                            'data' => $result['data'],
                            'update_data' => $updateResult['data']
                        ]);
                    } else {
                        // Agent created but update failed
                        return response()->json([
                            'success' => true,
                            'message' => 'Agent created but failed to activate: ' . $updateResult['message'],
                            'data' => $result['data'],
                            'warning' => 'Please manually update the agent to activate it'
                        ]);
                    }
                }

                return response()->json([
                    'success' => true,
                    'message' => $result['message'],
                    'data' => $result['data']
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $result['message']
                ], 400);
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create agent: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update agent in Tata TeleServices system
     */
    public function updateAgent(Request $request, $id)
    {
        try {
            $user = User::findOrFail($id);
            
            // Check if user has Tata agent ID
            if (!$user->tata_agent_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'No Tata agent ID found for this user. Please create agent first.'
                ], 400);
            }

            $tataService = new TataService();
            $result = $tataService->updateAgent($user, $user->tata_agent_id);

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => $result['message'],
                    'data' => $result['data']
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $result['message']
                ], 400);
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update agent: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete agent from Tata TeleServices system
     */
    public function deleteAgent(Request $request, $id)
    {
        try {
            $user = User::findOrFail($id);
            
            // Check if user has Tata agent ID
            if (!$user->tata_agent_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'No Tata agent ID found for this user.'
                ], 400);
            }

            $tataAgentId = $user->tata_agent_id;
            $tataService = new TataService();
            $result = $tataService->deleteAgent($tataAgentId);

            if ($result['success']) {
                // Clear the tata_agent_id from user record
                $user->update(['tata_agent_id' => null]);

                return response()->json([
                    'success' => true,
                    'message' => $result['message'],
                    'data' => $result['data']
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $result['message']
                ], 400);
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete agent: ' . $e->getMessage()
            ], 500);
        }
    }
}
