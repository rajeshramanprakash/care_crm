<?php

namespace App\Http\Controllers\SubAdmin;

use App\Http\Controllers\Controller;
use App\Models\Cases;
use App\Models\Lead;
use App\Models\OperationLead;
use App\Models\User;
use App\Models\Location;
use App\Models\Task;
use App\Models\Service;
use App\Models\PaymentInvoice;
use App\Models\ReceivedPayment;
use App\Models\OperationDeploymentDetails;
use App\Models\Vendor;
use App\Models\VendorPayment;
use App\Models\CallLog;
use App\Models\CallDetails;
use App\Models\JobRequest;
use App\Models\DeploymentLocationAttendance;
use App\Support\CallDirectionLabel;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class SubAdminController extends Controller
{
    public function __construct()
    {
        // $this->middleware('can:view_dashboard')->only(['dashboard']);
    }

    public function dashboard(){
        // Get today's counts for cards
        $newSalesLeadsCount = Lead::whereDate('created_at', Carbon::today())->count();
        $newOperationLeadsCount = OperationLead::whereDate('created_at', Carbon::today())->count();
        $activeUsersCount = User::where('is_active', 1)->count();
        $inactiveUsersCount = User::where('is_active', 0)->count();

        // Get task counts
        $todayTasksCount = Task::whereDate('created_at', Carbon::today())->count();
        $totalTasksCount = Task::count();
        $pendingTasksCount = Task::where('status', 'pending')->count();
        $completedTasksCount = Task::where('status', 'completed')->count();
        $overdueTasksCount = Task::where('due_date', '<', Carbon::now())
            ->where('status', '!=', 'completed')
            ->count();

        // Get latest 10 sales leads with executive name
        $latestSalesLeads = Lead::select(
                'leads.*',
                'users.f_name as executive'
            )
            ->leftJoin('users', 'leads.executive', '=', 'users.id')
            ->latest()
            ->take(10)
            ->get();

        // Get latest 10 operation leads with executive name and vendor name
        $latestOperationLeads = OperationLead::select(
                'operation_leads.*',
                'users.f_name as executive_name',
                'vendors.name as vendor_name'
            )
            ->leftJoin('users', 'operation_leads.executive', '=', 'users.id')
            ->leftJoin('vendors', 'operation_leads.vendor_id', '=', 'vendors.id')
            ->latest()
            ->take(10)
            ->get();

        // Get services for any dropdowns or filters
        $services = Service::get();
        $locations = Location::get();
        $executives = User::whereRaw('FIND_IN_SET(role_id, "2")')->get();

        // Calculate today's revenue
        $todayRevenue = $this->calculateTodayRevenue();

        // Get pending deployments count
        $pendingDeploymentsCount = OperationDeploymentDetails::where('deployment_status', 'pending')->count();

        // Calculate total outstanding amount from all operation leads
        $totalOutstandingAmount = $this->calculateTotalOutstandingAmount();

        // Get profile pending count
        $profilePendingCount = OperationLead::where('status', 'profile pending')->count();

        // Calculate total vendor remaining amount
        $totalVendorRemainingAmount = $this->calculateTotalVendorRemainingAmount();

        return view('subadmin.dashboard', compact(
            'newSalesLeadsCount',
            'newOperationLeadsCount',
            'activeUsersCount',
            'inactiveUsersCount',
            'latestSalesLeads',
            'latestOperationLeads',
            'todayTasksCount',
            'totalTasksCount',
            'pendingTasksCount',
            'completedTasksCount',
            'overdueTasksCount',
            'services',
            'locations',
            'executives',
            'todayRevenue',
            'pendingDeploymentsCount',
            'totalOutstandingAmount',
            'profilePendingCount',
            'totalVendorRemainingAmount'
        ));
    }

    public function dashboardApi()
    {
        // Get today's counts for cards
        $newSalesLeadsCount = Lead::whereDate('created_at', Carbon::today())->count();
        $newOperationLeadsCount = OperationLead::whereDate('created_at', Carbon::today())->count();
        $activeUsersCount = User::where('is_active', 1)->count();
        $inactiveUsersCount = User::where('is_active', 0)->count();

        // Get task counts
        $todayTasksCount = Task::whereDate('created_at', Carbon::today())->count();
        $totalTasksCount = Task::count();
        $pendingTasksCount = Task::where('status', 'pending')->count();
        $completedTasksCount = Task::where('status', 'completed')->count();
        $overdueTasksCount = Task::where('due_date', '<', Carbon::now())
            ->where('status', '!=', 'completed')
            ->count();

        // Calculate today's revenue
        $todayRevenue = $this->calculateTodayRevenue();

        // Get pending deployments count
        $pendingDeploymentsCount = OperationDeploymentDetails::where('deployment_status', 'pending')->count();

        // Calculate total outstanding amount from all operation leads
        $totalOutstandingAmount = $this->calculateTotalOutstandingAmount();

        // Get profile pending count
        $profilePendingCount = OperationLead::where('status', 'profile pending')->count();

        // Calculate total vendor remaining amount
        $totalVendorRemainingAmount = $this->calculateTotalVendorRemainingAmount();

        // Get unverified deployment payments count
        $unverifiedPaymentsCount = OperationDeploymentDetails::where('verify_payment', false)
            ->whereNotNull('vendor_payment')
            ->where('vendor_payment', '>', 0)
            ->count();

        // Get pending callbacks count (optimized - calculate directly)
        try {
            $allCallLogs = CallDetails::select('call_details.*')
                ->whereNotIn('call_details.call_status', ['answered', 'answer'])
                ->whereIn('call_details.call_for', ['lead', 'operation_lead', 'job_request'])
                ->get();
            
            $normalizedNumbers = [];
            foreach ($allCallLogs as $callLog) {
                $normalizedNumber = $this->normalizePhoneNumber($callLog->caller_id_number);
                if (!isset($normalizedNumbers[$normalizedNumber])) {
                    $normalizedNumbers[$normalizedNumber] = $callLog;
                } else {
                    if ($callLog->call_received_datetime > $normalizedNumbers[$normalizedNumber]->call_received_datetime) {
                        $normalizedNumbers[$normalizedNumber] = $callLog;
                    }
                }
            }
            
            $pendingCallbacksCount = 0;
            foreach ($normalizedNumbers as $callLog) {
                $contactNumber = $callLog->caller_id_number;
                $normalizedNumber = $this->normalizePhoneNumber($contactNumber);
                
                $successfulCallback = CallDetails::where(function($query) use ($contactNumber, $normalizedNumber) {
                    $query->where('caller_id_number', $contactNumber)
                          ->orWhere('caller_id_number', $normalizedNumber)
                          ->orWhere('caller_id_number', '91' . $normalizedNumber)
                          ->orWhere('caller_id_number', '0' . $normalizedNumber);
                })
                ->where('call_received_datetime', '>', $callLog->call_received_datetime)
                ->whereIn('call_status', ['answered', 'answer'])
                ->exists();
                
                if (!$successfulCallback) {
                    $pendingCallbacksCount++;
                }
            }
        } catch (\Exception $e) {
            \Log::error('Error calculating pending callbacks count: ' . $e->getMessage());
            $pendingCallbacksCount = 0;
        }

        // Get recent calls count (optimized - calculate directly)
        try {
            $oneWeekAgo = Carbon::now()->subWeek();
            
            $recentCallsSales = CallDetails::whereHas('executive', function($query) {
                $query->whereHas('role', function($roleQuery) {
                    $roleQuery->whereIn('name', ['Sales', 'Sales Manager']);
                });
            })
            ->where('call_for', 'lead')
            ->where('call_received_datetime', '>=', $oneWeekAgo)
            ->distinct('caller_id_number')
            ->count('caller_id_number');
            
            $recentCallsOperation = CallDetails::whereHas('executive', function($query) {
                $query->whereHas('role', function($roleQuery) {
                    $roleQuery->whereIn('name', ['Operation', 'Operation Manager']);
                });
            })
            ->whereIn('call_for', ['operation_lead', 'job_request'])
            ->where('call_received_datetime', '>=', $oneWeekAgo)
            ->distinct('caller_id_number')
            ->count('caller_id_number');
            
            $recentCallsCount = $recentCallsSales + $recentCallsOperation;
        } catch (\Exception $e) {
            \Log::error('Error calculating recent calls count: ' . $e->getMessage());
            $recentCallsCount = 0;
        }

        // Get calendar follow-up count for current month
        try {
            $currentMonth = Carbon::now()->month;
            $currentYear = Carbon::now()->year;
            $startDate = Carbon::create($currentYear, $currentMonth, 1)->startOfMonth();
            $endDate = Carbon::create($currentYear, $currentMonth, 1)->endOfMonth();

            $salesFollowupCount = DB::table('leads')
                ->where('status', 'follow-up')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->count();

            $salesFutureProspectCount = DB::table('leads')
                ->where('status', 'future prospect')
                ->whereNotNull('future_prospect_date')
                ->whereBetween('future_prospect_date', [$startDate, $endDate])
                ->count();

            $operationFollowupCount = DB::table('operation_leads')
                ->where('status', 'follow-up')
                ->whereBetween('date_time', [$startDate, $endDate])
                ->count();

            $calendarFollowupCount = $salesFollowupCount + $salesFutureProspectCount + $operationFollowupCount;
        } catch (\Exception $e) {
            \Log::error('Error calculating calendar follow-up count: ' . $e->getMessage());
            $calendarFollowupCount = 0;
        }

        return response()->json([
            'newSalesLeadsCount' => $newSalesLeadsCount,
            'newOperationLeadsCount' => $newOperationLeadsCount,
            'activeUsersCount' => $activeUsersCount,
            'inactiveUsersCount' => $inactiveUsersCount,
            'todayTasksCount' => $todayTasksCount,
            'totalTasksCount' => $totalTasksCount,
            'pendingTasksCount' => $pendingTasksCount,
            'completedTasksCount' => $completedTasksCount,
            'overdueTasksCount' => $overdueTasksCount,
            'todayRevenue' => $todayRevenue,
            'pendingDeploymentsCount' => $pendingDeploymentsCount,
            'totalOutstandingAmount' => $totalOutstandingAmount,
            'profilePendingCount' => $profilePendingCount,
            'totalVendorRemainingAmount' => $totalVendorRemainingAmount,
            'unverifiedPaymentsCount' => $unverifiedPaymentsCount,
            'pendingCallbacksCount' => $pendingCallbacksCount,
            'recentCallsCount' => $recentCallsCount,
            'calendarFollowupCount' => $calendarFollowupCount,
        ]);
    }

    // Get detailed sales leads statistics
    public function getSalesLeadsStats(Request $request)
    {
        $period = $request->get('period', 'today');
        $currentDate = Carbon::now();

        if ($period === 'custom') {
            $startDate = Carbon::parse($request->get('start_date'));
            $endDate = Carbon::parse($request->get('end_date'));
            $groupBy = 'DATE(leads.created_at)';
        } else {
            switch ($period) {
                case 'today':
                    $startDate = $currentDate->copy()->startOfDay();
                    $endDate = $currentDate->copy()->endOfDay();
                    $groupBy = 'HOUR(leads.created_at)';
                    break;
                case 'monthly':
                    $selectedMonth = $request->get('selected_month');
                    if ($selectedMonth) {
                        $date = Carbon::parse($selectedMonth . '-01');
                        $startDate = $date->copy()->startOfMonth();
                        $endDate = $date->copy()->endOfMonth();
                    } else {
                        $startDate = $currentDate->copy()->startOfMonth();
                        $endDate = $currentDate->copy()->endOfMonth();
                    }
                    $groupBy = 'DATE(leads.created_at)';
                    break;
                case 'yearly':
                    $selectedYear = $request->get('selected_year');
                    if ($selectedYear) {
                        $startDate = Carbon::createFromDate($selectedYear, 1, 1)->startOfYear();
                        $endDate = Carbon::createFromDate($selectedYear, 12, 31)->endOfYear();
                    } else {
                        $startDate = $currentDate->copy()->startOfYear();
                        $endDate = $currentDate->copy()->endOfYear();
                    }
                    $groupBy = 'MONTH(leads.created_at)';
                    break;
                default:
                    $startDate = $currentDate->copy()->startOfDay();
                    $endDate = $currentDate->copy()->endOfDay();
                    $groupBy = 'HOUR(leads.created_at)';
            }
        }

        // Build base query
        $baseQuery = Lead::whereBetween('leads.created_at', [$startDate, $endDate]);

        // Add executive filter if provided
        $executive = $request->get('executive');
        if ($executive) {
            $baseQuery->join('users', 'leads.executive', '=', 'users.id')
                     ->where('users.f_name', $executive);
        }

        // Get statistics
        $stats = $baseQuery->select(
            DB::raw("$groupBy as date"),
            DB::raw('COUNT(*) as count')
        )
        ->groupBy(DB::raw($groupBy))
        ->orderBy('date')
        ->get();

        // Get detailed leads for the period
        $leads = Lead::select(
            'leads.*',
            'users.f_name as executive'
        )
        ->leftJoin('users', 'leads.executive', '=', 'users.id')
        ->whereBetween('leads.created_at', [$startDate, $endDate]);

        // Add executive filter to leads query
        if ($executive) {
            $leads->where('users.f_name', $executive);
        }

        $leads = $leads->orderBy('leads.created_at', 'desc')->get();

        return response()->json([
            'stats' => $stats,
            'leads' => $leads,
            'period' => $period,
            'startDate' => $startDate->format('Y-m-d'),
            'endDate' => $endDate->format('Y-m-d')
        ]);
    }

    // Get detailed operation leads statistics
    public function getOperationLeadsStats(Request $request)
    {
        $period = $request->get('period', 'today');
        $currentDate = Carbon::now();

        if ($period === 'custom') {
            $startDate = Carbon::parse($request->get('start_date'));
            $endDate = Carbon::parse($request->get('end_date'));
            $groupBy = 'DATE(operation_leads.created_at)';
        } else {
            switch ($period) {
                case 'today':
                    $startDate = $currentDate->copy()->startOfDay();
                    $endDate = $currentDate->copy()->endOfDay();
                    $groupBy = 'HOUR(operation_leads.created_at)';
                    break;
                case 'monthly':
                    $selectedMonth = $request->get('selected_month');
                    if ($selectedMonth) {
                        $date = Carbon::parse($selectedMonth . '-01');
                        $startDate = $date->copy()->startOfMonth();
                        $endDate = $date->copy()->endOfMonth();
                    } else {
                        $startDate = $currentDate->copy()->startOfMonth();
                        $endDate = $currentDate->copy()->endOfMonth();
                    }
                    $groupBy = 'DATE(operation_leads.created_at)';
                    break;
                case 'yearly':
                    $selectedYear = $request->get('selected_year');
                    if ($selectedYear) {
                        $startDate = Carbon::createFromDate($selectedYear, 1, 1)->startOfYear();
                        $endDate = Carbon::createFromDate($selectedYear, 12, 31)->endOfYear();
                    } else {
                        $startDate = $currentDate->copy()->startOfYear();
                        $endDate = $currentDate->copy()->endOfYear();
                    }
                    $groupBy = 'MONTH(operation_leads.created_at)';
                    break;
                default:
                    $startDate = $currentDate->copy()->startOfDay();
                    $endDate = $currentDate->copy()->endOfDay();
                    $groupBy = 'HOUR(operation_leads.created_at)';
            }
        }

        // Build base query
        $baseQuery = OperationLead::whereBetween('operation_leads.created_at', [$startDate, $endDate]);

        // Add executive filter if provided
        $executive = $request->get('executive');
        if ($executive) {
            $baseQuery->join('users', 'operation_leads.executive', '=', 'users.id')
                     ->where('users.f_name', $executive);
        }

        // Get statistics
        $stats = $baseQuery->select(
            DB::raw("$groupBy as date"),
            DB::raw('COUNT(*) as count')
        )
        ->groupBy(DB::raw($groupBy))
        ->orderBy('date')
        ->get();

        // Get detailed leads for the period
        $leads = OperationLead::select(
            'operation_leads.*',
            'users.f_name as executive_name',
            'vendors.name as vendor_name'
        )
        ->leftJoin('users', 'operation_leads.executive', '=', 'users.id')
        ->leftJoin('vendors', 'operation_leads.vendor_id', '=', 'vendors.id')
        ->whereBetween('operation_leads.created_at', [$startDate, $endDate]);

        // Add executive filter to leads query
        if ($executive) {
            $leads->where('users.f_name', $executive);
        }

        $leads = $leads->orderBy('operation_leads.created_at', 'desc')->get();

        return response()->json([
            'stats' => $stats,
            'leads' => $leads,
            'period' => $period,
            'startDate' => $startDate->format('Y-m-d'),
            'endDate' => $endDate->format('Y-m-d')
        ]);
    }

    // Get users statistics
    public function getUsersStats(Request $request)
    {
        try {
            $filter = $request->get('filter', 'all');

            // Get total counts
            $totalUsers = User::count();
            $activeUsers = User::where('is_active', 1)->count();
            $inactiveUsers = User::where('is_active', 0)->count();

            // Get users based on filter with role information
            $usersQuery = User::select(
                'users.id',
                'users.f_name',
                'users.l_name',
                'users.email',
                'users.mobile',
                'users.role_id',
                'users.is_active',
                'users.created_at',
                'roles.name as role_name'
            )
            ->leftJoin('roles', 'users.role_id', '=', 'roles.id');

            switch ($filter) {
                case 'active':
                    $usersQuery->where('users.is_active', 1);
                    break;
                case 'inactive':
                    $usersQuery->where('users.is_active', 0);
                    break;
                case 'all':
                default:
                    // No filter, get all users
                    break;
            }

            $users = $usersQuery->orderBy('users.created_at', 'desc')->get();

            return response()->json([
                'totalUsers' => $totalUsers,
                'activeUsers' => $activeUsers,
                'inactiveUsers' => $inactiveUsers,
                'users' => $users,
                'filter' => $filter
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error loading users data: ' . $e->getMessage()
            ], 500);
        }
    }

    // Get user details for modal
    public function getUserDetails($userId)
    {
        try {
            $user = User::select(
                'users.id',
                'users.f_name',
                'users.l_name',
                'users.email',
                'users.mobile',
                'users.role_id',
                'users.is_active',
                'users.created_at',
                'users.updated_at',
                'roles.name as role_name'
            )
            ->leftJoin('roles', 'users.role_id', '=', 'roles.id')
            ->where('users.id', $userId)
            ->first();

            if (!$user) {
                return response()->json([
                    'error' => 'User not found'
                ], 404);
            }

            return response()->json($user);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error loading user details: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getSalesExecutives()
    {
        try {
            $executives = Lead::select('users.f_name as executive_name')
                ->join('users', 'leads.executive', '=', 'users.id')
                ->whereNotNull('leads.executive')
                ->where('leads.executive', '!=', '')
                ->distinct()
                ->pluck('executive_name')
                ->filter()
                ->sort()
                ->values();

            return response()->json(['executives' => $executives]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error loading sales executives'], 500);
        }
    }

    public function getOperationExecutives()
    {
        try {
            $executives = OperationLead::select('users.f_name as executive_name')
                ->join('users', 'operation_leads.executive', '=', 'users.id')
                ->whereNotNull('operation_leads.executive')
                ->where('operation_leads.executive', '!=', '')
                ->distinct()
                ->pluck('executive_name')
                ->filter()
                ->sort()
                ->values();

            return response()->json(['executives' => $executives]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error loading operation executives'], 500);
        }
    }

    // Task Management Methods
    public function getTasks(Request $request)
    {
        try {
            $type = $request->get('type', 'all');
            $startDate = $request->get('start_date');
            $endDate = $request->get('end_date');

            $query = Task::with(['assignedTo', 'assignedBy']);

            switch ($type) {
                case 'today':
                    $query->whereDate('created_at', Carbon::today());
                    break;
                case 'pending':
                    $query->where('status', 'pending');
                    break;
                case 'completed':
                    $query->where('status', 'completed');
                    break;
                case 'all':
                default:
                    // Show all tasks
                    break;
            }

            // Apply date filters
            if ($startDate) {
                $query->whereDate('created_at', '>=', $startDate);
            }
            if ($endDate) {
                $query->whereDate('created_at', '<=', $endDate);
            }

            $tasks = $query->orderBy('created_at', 'desc')->get();

            // Add user names to tasks
            $tasks->each(function($task) {
                $task->assigned_to_name = $task->assignedTo ? $task->assignedTo->f_name . ' ' . $task->assignedTo->l_name : 'N/A';
                $task->assigned_by_name = $task->assignedBy ? $task->assignedBy->f_name . ' ' . $task->assignedBy->l_name : 'N/A';
                $task->assigned_to_parent_id = $task->assignedTo ? $task->assignedTo->parent_id : null;
                $task->assigned_by_parent_id = $task->assignedBy ? $task->assignedBy->parent_id : null;
            });

            return response()->json([
                'success' => true,
                'tasks' => $tasks
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch tasks: ' . $e->getMessage()], 500);
        }
    }

    public function storeTask(Request $request)
    {
        try {
            $request->validate([
                'title' => 'required|string|max:255',
                'description' => 'nullable|string',
                'priority' => 'required|in:low,medium,high,urgent',
                'assigned_to' => 'required|exists:users,id',
                'due_date' => 'required|date|after:today'
            ]);

            $task = Task::create([
                'title' => $request->title,
                'description' => $request->description,
                'priority' => $request->priority,
                'due_date' => $request->due_date,
                'status' => 'pending',
                'assigned_to' => $request->assigned_to,
                'assigned_by' => auth()->user()->id
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Task created successfully',
                'task' => $task
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to create task: ' . $e->getMessage()], 500);
        }
    }

    public function editTask($id)
    {
        try {
            $task = Task::with(['assignedTo', 'assignedBy'])->findOrFail($id);

            // Add user names to task
            $task->assigned_to_name = $task->assignedTo ? $task->assignedTo->f_name . ' ' . $task->assignedTo->l_name : 'N/A';
            $task->assigned_by_name = $task->assignedBy ? $task->assignedBy->f_name . ' ' . $task->assignedBy->l_name : 'N/A';

            return response()->json([
                'success' => true,
                'task' => $task
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch task: ' . $e->getMessage()], 500);
        }
    }

    public function updateTask(Request $request, $id)
    {
        try {
            $task = Task::findOrFail($id);

            $request->validate([
                'title' => 'required|string|max:255',
                'description' => 'nullable|string',
                'priority' => 'required|in:low,medium,high,urgent',
                'assigned_to' => 'required|exists:users,id',
                'due_date' => 'required|date',
                'status' => 'required|in:pending,in_progress,completed,cancelled'
            ]);

            $task->update([
                'title' => $request->title,
                'description' => $request->description,
                'priority' => $request->priority,
                'assigned_to' => $request->assigned_to,
                'due_date' => $request->due_date,
                'status' => $request->status
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Task updated successfully',
                'task' => $task
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to update task: ' . $e->getMessage()], 500);
        }
    }

    public function destroyTask($id)
    {
        try {
            $task = Task::findOrFail($id);
            $task->delete();

            return response()->json([
                'success' => true,
                'message' => 'Task deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to delete task: ' . $e->getMessage()], 500);
        }
    }

    public function getTaskHistory(Request $request)
    {
        try {
            $type = $request->get('type', 'all');
            $startDate = $request->get('start_date');
            $endDate = $request->get('end_date');
            $userId = auth()->user()->id;

            $query = Task::with(['assignedTo', 'assignedBy']);

            switch ($type) {
                case 'assigned_by_me':
                    $query->where('assigned_by', $userId);
                    break;
                case 'assigned_to_me':
                    $query->where('assigned_to', $userId);
                    break;
                case 'completed':
                    $query->where('status', 'completed');
                    break;
                case 'all':
                default:
                    // Show all tasks
                    break;
            }

            // Apply date filters
            if ($startDate) {
                $query->whereDate('created_at', '>=', $startDate);
            }
            if ($endDate) {
                $query->whereDate('created_at', '<=', $endDate);
            }

            $tasks = $query->orderBy('created_at', 'desc')->get();

            // Add user names to tasks
            $tasks->each(function($task) {
                $task->assigned_to_name = $task->assignedTo ? $task->assignedTo->f_name . ' ' . $task->assignedTo->l_name : 'N/A';
                $task->assigned_by_name = $task->assignedBy ? $task->assignedBy->f_name . ' ' . $task->assignedBy->l_name : 'N/A';
                $task->assigned_to_parent_id = $task->assignedTo ? $task->assignedTo->parent_id : null;
                $task->assigned_by_parent_id = $task->assignedBy ? $task->assignedBy->parent_id : null;
            });

            return response()->json([
                'success' => true,
                'tasks' => $tasks
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch task history: ' . $e->getMessage()], 500);
        }
    }

    // Get all users for task assignment dropdown (including parent members)
    public function getUsers()
    {
        try {
            $users = User::select(
                'users.id',
                'users.f_name',
                'users.l_name',
                'users.email',
                'users.parent_id',
                'roles.name as role_name'
            )
            ->leftJoin('roles', 'users.role_id', '=', 'roles.id')
            ->where('users.is_active', 1) // Only active users
            ->orderBy('users.f_name', 'asc')
            ->get();

            return response()->json(['users' => $users]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error loading users: ' . $e->getMessage()], 500);
        }
    }

    public function getCalendarData(Request $request)
    {
        try {
            $year = $request->get('year', date('Y'));
            $month = $request->get('month', date('n'));

            \Log::info('Calendar data request', ['year' => $year, 'month' => $month]);

            // Get start and end dates for the month
            $startDate = Carbon::create($year, $month, 1)->startOfMonth();
            $endDate = Carbon::create($year, $month, 1)->endOfMonth();

            // Get sales leads data
            $salesFollowupLeads = DB::table('leads')
                ->select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(*) as count'))
                ->where('status', 'follow-up')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->groupBy(DB::raw('DATE(created_at)'))
                ->get()
                ->keyBy('date');

            $salesFutureProspectLeads = DB::table('leads')
                ->select(DB::raw('DATE(future_prospect_date) as date'), DB::raw('COUNT(*) as count'))
                ->where('status', 'future prospect')
                ->whereNotNull('future_prospect_date')
                ->whereBetween('future_prospect_date', [$startDate, $endDate])
                ->groupBy(DB::raw('DATE(future_prospect_date)'))
                ->get()
                ->keyBy('date');

            // Get operation leads data (only follow-up status)
            $operationFollowupLeads = DB::table('operation_leads')
                ->select(DB::raw('DATE(date_time) as date'), DB::raw('COUNT(*) as count'))
                ->where('status', 'follow-up')
                ->whereBetween('date_time', [$startDate, $endDate])
                ->groupBy(DB::raw('DATE(date_time)'))
                ->get()
                ->keyBy('date');

            // Combine all data
            $calendarData = [];

            // Generate all dates in the month
            $currentDate = $startDate->copy();
            while ($currentDate->lte($endDate)) {
                $dateStr = $currentDate->format('Y-m-d');

                $calendarData[$dateStr] = [
                    'sales_followup' => $salesFollowupLeads->get($dateStr)->count ?? 0,
                    'sales_future_prospect' => $salesFutureProspectLeads->get($dateStr)->count ?? 0,
                    'operation_followup' => $operationFollowupLeads->get($dateStr)->count ?? 0,
                ];

                $currentDate->addDay();
            }

            return response()->json($calendarData);

        } catch (\Exception $e) {
            \Log::error('Error fetching calendar data: ' . $e->getMessage());
            return response()->json([
                'error' => 'Failed to fetch calendar data'
            ], 500);
        }
    }

    public function getCalendarLeads(Request $request)
    {
        try {
            $date = $request->get('date');
            if (!$date) {
                return response()->json(['error' => 'Date parameter is required'], 400);
            }

            \Log::info('Calendar leads request', ['date' => $date]);

            $targetDate = Carbon::parse($date)->format('Y-m-d');

            // Get sales leads for the date
            $salesLeads = DB::table('leads')
                ->select('leads.id', 'leads.customer_name', 'leads.contact_no', 'users.f_name as executive', 'leads.status')
                ->leftJoin('users', 'leads.executive', '=', 'users.id')
                ->where(function($query) use ($targetDate) {
                    $query->where(function($q) use ($targetDate) {
                        $q->where('leads.status', 'follow-up')
                          ->whereDate('leads.created_at', $targetDate);
                    })->orWhere(function($q) use ($targetDate) {
                        $q->where('leads.status', 'future prospect')
                          ->whereDate('leads.future_prospect_date', $targetDate);
                    });
                })
                ->get();

            // Get operation leads for the date (only follow-up status)
            $operationLeads = DB::table('operation_leads')
                ->select('operation_leads.id', 'operation_leads.customer_name', 'operation_leads.contact_no', 'users.f_name as executive_name', 'operation_leads.status')
                ->leftJoin('users', 'operation_leads.executive', '=', 'users.id')
                ->where('operation_leads.status', 'follow-up')
                ->whereDate('operation_leads.date_time', $targetDate)
                ->get();

            return response()->json([
                'sales_leads' => $salesLeads,
                'operation_leads' => $operationLeads
            ]);

        } catch (\Exception $e) {
            \Log::error('Error fetching calendar leads: ' . $e->getMessage());
            return response()->json([
                'error' => 'Failed to fetch leads for the selected date'
            ], 500);
        }
    }

    // Revenue calculation methods
    private function calculateTodayRevenue()
    {
        try {
            $today = Carbon::today();

            // Get all operation leads created today
            $todayLeads = OperationLead::whereDate('created_at', $today)->pluck('id');

            \Log::info('Today leads count: ' . $todayLeads->count());

            if ($todayLeads->isEmpty()) {
                \Log::info('No leads found for today');
                return 0;
            }

            // Calculate total received payments
            $totalReceived = ReceivedPayment::whereIn('operation_lead_id', $todayLeads)
                ->sum('amount');

            // Calculate total vendor payments
            $totalVendorPayments = OperationDeploymentDetails::whereIn('operation_lead_id', $todayLeads)
                ->sum(DB::raw('COALESCE(vendor_payment, 0)'));

            $netRevenue = $totalReceived - $totalVendorPayments;

            \Log::info('Revenue calculation:', [
                'total_received' => $totalReceived,
                'total_vendor_payments' => $totalVendorPayments,
                'net_revenue' => $netRevenue
            ]);

            // Net revenue = Total Received - Vendor Payments
            return $netRevenue;

        } catch (\Exception $e) {
            \Log::error('Error calculating today revenue: ' . $e->getMessage());
            return 0;
        }
    }

    public function getRevenueStats(Request $request)
    {
        try {
            $period = $request->get('period', 'today');
            $currentDate = Carbon::now();

            if ($period === 'custom') {
                $startDate = Carbon::parse($request->get('start_date'));
                $endDate = Carbon::parse($request->get('end_date'));
            } else {
                switch ($period) {
                    case 'today':
                        $startDate = $currentDate->copy()->startOfDay();
                        $endDate = $currentDate->copy()->endOfDay();
                        break;
                    case 'monthly':
                        $selectedMonth = $request->get('selected_month');
                        if ($selectedMonth) {
                            $date = Carbon::parse($selectedMonth . '-01');
                            $startDate = $date->copy()->startOfMonth();
                            $endDate = $date->copy()->endOfMonth();
                        } else {
                            $startDate = $currentDate->copy()->startOfMonth();
                            $endDate = $currentDate->copy()->endOfMonth();
                        }
                        break;
                    case 'yearly':
                        $selectedYear = $request->get('selected_year');
                        if ($selectedYear) {
                            $startDate = Carbon::createFromDate($selectedYear, 1, 1)->startOfYear();
                            $endDate = Carbon::createFromDate($selectedYear, 12, 31)->endOfYear();
                        } else {
                            $startDate = $currentDate->copy()->startOfYear();
                            $endDate = $currentDate->copy()->endOfYear();
                        }
                        break;
                    default:
                        $startDate = $currentDate->copy()->startOfDay();
                        $endDate = $currentDate->copy()->endOfDay();
                }
            }

            // Get operation leads for the period
            $leadIds = OperationLead::whereBetween('created_at', [$startDate, $endDate])->pluck('id');

            if ($leadIds->isEmpty()) {
                return response()->json([
                    'total_received' => 0,
                    'vendor_payments' => 0,
                    'net_revenue' => 0,
                    'revenue_details' => []
                ]);
            }

            // Calculate total received payments
            $totalReceived = ReceivedPayment::whereIn('operation_lead_id', $leadIds)
                ->sum('amount');

            // Calculate total vendor payments
            $totalVendorPayments = OperationDeploymentDetails::whereIn('operation_lead_id', $leadIds)
                ->sum(DB::raw('COALESCE(vendor_payment, 0)'));

            // Get detailed revenue breakdown
            $revenueDetails = $this->getRevenueDetails($leadIds);

            return response()->json([
                'total_received' => $totalReceived,
                'vendor_payments' => $totalVendorPayments,
                'net_revenue' => $totalReceived - $totalVendorPayments,
                'revenue_details' => $revenueDetails
            ]);

        } catch (\Exception $e) {
            \Log::error('Error getting revenue stats: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch revenue data'], 500);
        }
    }

    private function getRevenueDetails($leadIds)
    {
        try {
            return OperationLead::select(
                'operation_leads.id as lead_id',
                'operation_leads.customer_name',
                'operation_leads.created_at as date',
                DB::raw('COALESCE(SUM(COALESCE(payment_details.payment_received, 0) + COALESCE(payment_details.refund_amount, 0)), 0) as total_received'),
                DB::raw('COALESCE(SUM(COALESCE(deployment_details.vendor_payment, 0)), 0) as vendor_payment'),
                DB::raw("CONCAT(users.f_name, ' ', COALESCE(users.l_name, '')) as executive_name")
            )
            ->leftJoin('operation_leads_payment_details as payment_details', 'operation_leads.id', '=', 'payment_details.operation_lead_id')
            ->leftJoin('operation_deployment_details as deployment_details', 'operation_leads.id', '=', 'deployment_details.operation_lead_id')
            ->leftJoin('users', 'operation_leads.executive', '=', 'users.id')
            ->whereIn('operation_leads.id', $leadIds)
            ->groupBy('operation_leads.id', 'operation_leads.customer_name', 'operation_leads.created_at', 'users.f_name', 'users.l_name')
            ->orderBy('operation_leads.created_at', 'desc')
            ->get();

        } catch (\Exception $e) {
            \Log::error('Error getting revenue details: ' . $e->getMessage());
            return [];
        }
    }

    public function getPendingDeployments(Request $request)
    {
        try {
            $today = Carbon::today();
            $weekStart = Carbon::now()->startOfWeek();
            $weekEnd = Carbon::now()->endOfWeek();

            // Get pending deployments with related data
            $pendingDeployments = OperationDeploymentDetails::select(
                'operation_deployment_details.*',
                'operation_leads.customer_name',
                'operation_leads.contact_no',
                'operation_leads.lead_id',
                'operation_leads.id as operation_lead_id',
                'operation_leads.executive',
                'vendors.name as vendor_name',
                'vendors.contact_no as vendor_contact_no',
                \DB::raw("CONCAT(users.f_name, ' ', COALESCE(users.l_name, '')) as executive_name")
            )
            ->leftJoin('operation_leads', 'operation_deployment_details.operation_lead_id', '=', 'operation_leads.id')
            ->leftJoin('vendors', 'operation_deployment_details.vendor_id', '=', 'vendors.id')
            ->leftJoin('users', 'operation_leads.executive', '=', 'users.id')
            ->where('operation_deployment_details.deployment_status', 'pending')
            ->orderBy('operation_deployment_details.deployment_date', 'asc')
            ->get();

            // Calculate counts
            $todayPending = $pendingDeployments->where('deployment_date', $today)->count();
            $weekPending = $pendingDeployments->whereBetween('deployment_date', [$weekStart, $weekEnd])->count();
            $overdue = $pendingDeployments->where('deployment_date', '<', $today)->count();

            return response()->json([
                'total_pending' => $pendingDeployments->count(),
                'today_pending' => $todayPending,
                'week_pending' => $weekPending,
                'overdue' => $overdue,
                'deployments' => $pendingDeployments
            ]);

        } catch (\Exception $e) {
            \Log::error('Error getting pending deployments: ' . $e->getMessage());
            return response()->json([
                'error' => 'Failed to load pending deployments data'
            ], 500);
        }
    }

    private function calculateTotalOutstandingAmount()
    {
        try {
            // Get total invoiced amount from payment invoices
            $totalInvoiced = PaymentInvoice::sum('payment_amount');
            
            // Get total received amount from received payments
            $totalReceived = ReceivedPayment::sum('amount');
            
            // Calculate outstanding (invoiced - received)
            $totalOutstanding = $totalInvoiced - $totalReceived;

            \Log::info('Total outstanding amount calculated:', [
                'invoiced' => $totalInvoiced,
                'received' => $totalReceived,
                'outstanding' => $totalOutstanding
            ]);

            return $totalOutstanding;
        } catch (\Exception $e) {
            \Log::error('Error calculating total outstanding amount: ' . $e->getMessage());
            return 0;
        }
    }

    public function getOutstandingAmountStats(Request $request)
    {
        try {
            $period = $request->get('period', 'all');
            $currentDate = Carbon::now();

            if ($period === 'custom') {
                $startDate = Carbon::parse($request->get('start_date'));
                $endDate = Carbon::parse($request->get('end_date'));
            } else {
                switch ($period) {
                    case 'today':
                        $startDate = $currentDate->copy()->startOfDay();
                        $endDate = $currentDate->copy()->endOfDay();
                        break;
                    case 'monthly':
                        $selectedMonth = $request->get('selected_month');
                        if ($selectedMonth) {
                            $date = Carbon::parse($selectedMonth . '-01');
                            $startDate = $date->copy()->startOfMonth();
                            $endDate = $date->copy()->endOfMonth();
                        } else {
                            $startDate = $currentDate->copy()->startOfMonth();
                            $endDate = $currentDate->copy()->endOfMonth();
                        }
                        break;
                    case 'yearly':
                        $selectedYear = $request->get('selected_year');
                        if ($selectedYear) {
                            $startDate = Carbon::createFromDate($selectedYear, 1, 1)->startOfYear();
                            $endDate = Carbon::createFromDate($selectedYear, 12, 31)->endOfYear();
                        } else {
                            $startDate = $currentDate->copy()->startOfYear();
                            $endDate = $currentDate->copy()->endOfYear();
                        }
                        break;
                    default:
                        $startDate = null;
                        $endDate = null;
                }
            }

            // Get payment invoices with outstanding amounts
            $query = PaymentInvoice::select(
                'payment_invoices.*',
                'operation_leads.customer_name',
                'operation_leads.contact_no',
                'operation_leads.lead_id',
                'operation_leads.created_at as lead_created_at',
                DB::raw('(SELECT COALESCE(SUM(amount), 0) FROM received_payments WHERE payment_invoice_id = payment_invoices.id) as total_received'),
                DB::raw('(payment_invoices.payment_amount - (SELECT COALESCE(SUM(amount), 0) FROM received_payments WHERE payment_invoice_id = payment_invoices.id)) as outstanding_payment')
            )
            ->leftJoin('operation_leads', 'payment_invoices.operation_lead_id', '=', 'operation_leads.id')
            ->havingRaw('outstanding_payment != 0');

            if ($startDate && $endDate) {
                $query->whereBetween('payment_invoices.from_date', [$startDate, $endDate]);
            }

            $outstandingDetails = $query->orderByRaw('outstanding_payment DESC')->get();

            // Calculate totals
            $totalOutstanding = $outstandingDetails->sum('outstanding_payment');
            $positiveOutstanding = $outstandingDetails->where('outstanding_payment', '>', 0)->sum('outstanding_payment');
            $negativeOutstanding = $outstandingDetails->where('outstanding_payment', '<', 0)->sum('outstanding_payment');
            $count = $outstandingDetails->count();

            return response()->json([
                'total_outstanding' => $totalOutstanding,
                'positive_outstanding' => $positiveOutstanding,
                'negative_outstanding' => $negativeOutstanding,
                'count' => $count,
                'outstanding_details' => $outstandingDetails
            ]);

        } catch (\Exception $e) {
            \Log::error('Error getting outstanding amount stats: ' . $e->getMessage());
            return response()->json([
                'error' => 'Failed to load outstanding amount data'
            ], 500);
        }
    }

    public function getProfilePendingStats(Request $request)
    {
        try {
            $period = $request->get('period', 'all');
            $currentDate = Carbon::now();

            if ($period === 'custom') {
                $startDate = Carbon::parse($request->get('start_date'));
                $endDate = Carbon::parse($request->get('end_date'));
            } else {
                switch ($period) {
                    case 'today':
                        $startDate = $currentDate->copy()->startOfDay();
                        $endDate = $currentDate->copy()->endOfDay();
                        break;
                    case 'monthly':
                        $selectedMonth = $request->get('selected_month');
                        if ($selectedMonth) {
                            $date = Carbon::parse($selectedMonth . '-01');
                            $startDate = $date->copy()->startOfMonth();
                            $endDate = $date->copy()->endOfMonth();
                        } else {
                            $startDate = $currentDate->copy()->startOfMonth();
                            $endDate = $currentDate->copy()->endOfMonth();
                        }
                        break;
                    case 'yearly':
                        $selectedYear = $request->get('selected_year');
                        if ($selectedYear) {
                            $startDate = Carbon::createFromDate($selectedYear, 1, 1)->startOfYear();
                            $endDate = Carbon::createFromDate($selectedYear, 12, 31)->endOfYear();
                        } else {
                            $startDate = $currentDate->copy()->startOfYear();
                            $endDate = $currentDate->copy()->endOfYear();
                        }
                        break;
                    default:
                        $startDate = null;
                        $endDate = null;
                }
            }

            // Get profile pending leads
            $query = OperationLead::select(
                'operation_leads.*',
                'users.f_name as executive_name'
            )
            ->leftJoin('users', 'operation_leads.executive', '=', 'users.id')
            ->where('operation_leads.status', 'profile pending');

            if ($startDate && $endDate) {
                $query->whereBetween('operation_leads.created_at', [$startDate, $endDate]);
            }

            $profilePendingLeads = $query->orderBy('operation_leads.created_at', 'desc')->get();

            // Calculate summary statistics
            $totalProfilePending = OperationLead::where('status', 'profile pending')->count();
            $todayProfilePending = OperationLead::where('status', 'profile pending')
                ->whereDate('created_at', $currentDate->toDateString())
                ->count();
            $monthProfilePending = OperationLead::where('status', 'profile pending')
                ->whereMonth('created_at', $currentDate->month)
                ->whereYear('created_at', $currentDate->year)
                ->count();
            $yearProfilePending = OperationLead::where('status', 'profile pending')
                ->whereYear('created_at', $currentDate->year)
                ->count();

            return response()->json([
                'total_profile_pending' => $totalProfilePending,
                'today_profile_pending' => $todayProfilePending,
                'month_profile_pending' => $monthProfilePending,
                'year_profile_pending' => $yearProfilePending,
                'count' => $profilePendingLeads->count(),
                'profile_pending_leads' => $profilePendingLeads
            ]);

        } catch (\Exception $e) {
            \Log::error('Error getting profile pending stats: ' . $e->getMessage());
            return response()->json([
                'error' => 'Failed to load profile pending data'
            ], 500);
        }
    }

    private function calculateTotalVendorRemainingAmount()
    {
        try {
            // Get all vendors
            $vendors = Vendor::all();
            $totalRemaining = 0;

            foreach ($vendors as $vendor) {
                // Get total earned by this vendor from deployments (only verified payments)
                $totalEarned = OperationDeploymentDetails::where('vendor_id', $vendor->id)
                    ->where('verify_payment', true)
                    ->sum(DB::raw('COALESCE(vendor_payment, 0)'));

                // Get total payments made to this vendor
                $totalPaymentsMade = VendorPayment::where('vendor_id', $vendor->id)
                    ->where('status', 'completed')
                    ->sum(DB::raw('COALESCE(amount, 0)'));

                // Calculate remaining amount for this vendor
                $remainingAmount = $totalEarned - $totalPaymentsMade;
                $totalRemaining += $remainingAmount;
            }

            \Log::info('Total vendor remaining amount calculated:', ['amount' => $totalRemaining]);

            return $totalRemaining;
        } catch (\Exception $e) {
            \Log::error('Error calculating total vendor remaining amount: ' . $e->getMessage());
            return 0;
        }
    }

    public function getVendorPaymentStats(Request $request)
    {
        try {
            $period = $request->get('period', 'all');
            $currentDate = Carbon::now();

            if ($period === 'custom') {
                $startDate = Carbon::parse($request->get('start_date'));
                $endDate = Carbon::parse($request->get('end_date'));
            } else {
                switch ($period) {
                    case 'today':
                        $startDate = $currentDate->copy()->startOfDay();
                        $endDate = $currentDate->copy()->endOfDay();
                        break;
                    case 'monthly':
                        $selectedMonth = $request->get('selected_month');
                        if ($selectedMonth) {
                            $date = Carbon::parse($selectedMonth . '-01');
                            $startDate = $date->copy()->startOfMonth();
                            $endDate = $date->copy()->endOfMonth();
                        } else {
                            $startDate = $currentDate->copy()->startOfMonth();
                            $endDate = $currentDate->copy()->endOfMonth();
                        }
                        break;
                    case 'yearly':
                        $selectedYear = $request->get('selected_year');
                        if ($selectedYear) {
                            $startDate = Carbon::createFromDate($selectedYear, 1, 1)->startOfYear();
                            $endDate = Carbon::createFromDate($selectedYear, 12, 31)->endOfYear();
                        } else {
                            $startDate = $currentDate->copy()->startOfYear();
                            $endDate = $currentDate->copy()->endOfYear();
                        }
                        break;
                    default:
                        $startDate = null;
                        $endDate = null;
                }
            }

            // Get all vendors with their payment details
            $vendors = Vendor::all();
            $vendorDetails = [];

            foreach ($vendors as $vendor) {
                // Get total earned by this vendor from deployments (only verified payments)
                $totalEarned = OperationDeploymentDetails::where('vendor_id', $vendor->id)
                    ->where('verify_payment', true)
                    ->sum(DB::raw('COALESCE(vendor_payment, 0)'));

                // Get total payments made to this vendor
                $totalPaymentsMade = VendorPayment::where('vendor_id', $vendor->id)
                    ->where('status', 'completed')
                    ->sum(DB::raw('COALESCE(amount, 0)'));

                // Calculate remaining amount for this vendor
                $remainingAmount = $totalEarned - $totalPaymentsMade;

                // Get deployment details for this vendor
                $deployments = OperationDeploymentDetails::where('vendor_id', $vendor->id)
                    ->with(['operationLead' => function($query) {
                        $query->select('id', 'customer_name', 'query', 'location');
                    }])
                    ->get();

                if ($remainingAmount != 0 || $deployments->count() > 0) {
                    $vendorDetails[] = [
                        'vendor_id' => $vendor->id,
                        'vendor_name' => $vendor->name,
                        'vendor_contact' => $vendor->contact_no,
                        'total_earned' => $totalEarned,
                        'total_payments_made' => $totalPaymentsMade,
                        'remaining_amount' => $remainingAmount,
                        'deployments_count' => $deployments->count(),
                        'deployments' => $deployments->map(function($deployment) {
                            return [
                                'lead_id' => $deployment->operationLead ? $deployment->operationLead->id : 'N/A',
                                'customer_name' => $deployment->operationLead ? $deployment->operationLead->customer_name : 'N/A',
                                'service' => $deployment->operationLead ? $deployment->operationLead->query : 'N/A',
                                'location' => $deployment->operationLead ? $deployment->operationLead->location : 'N/A',
                                'deployment_date' => $deployment->deployment_date,
                                'vendor_payment' => $deployment->vendor_payment,
                                'deployment_status' => $deployment->deployment_status
                            ];
                        })
                    ];
                }
            }

            // Calculate totals
            $totalEarned = collect($vendorDetails)->sum('total_earned');
            $totalPaymentsMade = collect($vendorDetails)->sum('total_payments_made');
            $totalRemaining = collect($vendorDetails)->sum('remaining_amount');
            $totalVendors = count($vendorDetails);

            return response()->json([
                'total_earned' => $totalEarned,
                'total_payments_made' => $totalPaymentsMade,
                'total_remaining' => $totalRemaining,
                'total_vendors' => $totalVendors,
                'vendor_details' => $vendorDetails
            ]);

        } catch (\Exception $e) {
            \Log::error('Error getting vendor payment stats: ' . $e->getMessage());
            return response()->json([
                'error' => 'Failed to load vendor payment data'
            ], 500);
        }
    }

    public function getUnverifiedDeploymentPaymentsStats(Request $request)
    {
        try {
            // Get unverified deployment payments for all operation leads
            $unverifiedDeployments = OperationDeploymentDetails::with([
                'operationLead',
                'vendor',
                'freelanceStaff'
            ])
            ->where('verify_payment', false)
            ->whereNotNull('vendor_payment')
            ->where('vendor_payment', '>', 0)
            ->orderBy('created_at', 'desc')
            ->get();

            // Calculate summary statistics
            $totalUnverified = $unverifiedDeployments->count();
            $totalAmount = $unverifiedDeployments->sum('vendor_payment');

            // Format deployment data for response
            $deployments = $unverifiedDeployments->map(function($deployment) {
                $vendorName = 'N/A';
                if ($deployment->freelance_staff_id && $deployment->freelanceStaff) {
                    $vendorName = $deployment->freelanceStaff->name . ' (Freelance)';
                } elseif ($deployment->vendor) {
                    $vendorName = $deployment->vendor->name;
                }

                // Get executive name using the executive() relationship
                $executiveName = 'N/A';
                if ($deployment->operationLead) {
                    $executive = User::find($deployment->operationLead->executive);
                    if ($executive) {
                        $executiveName = $executive->f_name . ' ' . $executive->l_name;
                    }
                }

                return [
                    'id' => $deployment->id,
                    'lead_id' => $deployment->operationLead ? $deployment->operationLead->lead_id : 'N/A',
                    'customer_name' => $deployment->operationLead ? $deployment->operationLead->customer_name : 'N/A',
                    'contact_no' => $deployment->operationLead ? $deployment->operationLead->contact_no : 'N/A',
                    'location' => $deployment->operationLead ? $deployment->operationLead->location : 'N/A',
                    'vendor_name' => $vendorName,
                    'staff_name' => $deployment->staff_name ?: 'N/A',
                    'staff_number' => $deployment->staff_number ?: 'N/A',
                    'payment_term' => $deployment->payment_term ?: 'N/A',
                    'deployment_date' => $deployment->deployment_date,
                    'deployment_from_date' => $deployment->deployment_from_date,
                    'deployment_to_date' => $deployment->deployment_to_date,
                    'deployment_status' => $deployment->deployment_status ?: 'N/A',
                    'vendor_payment' => $deployment->vendor_payment,
                    'executive_name' => $executiveName,
                    'verify_payment' => $deployment->verify_payment
                ];
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'total_unverified' => $totalUnverified,
                    'total_amount' => $totalAmount,
                    'deployments' => $deployments
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Error getting unverified deployment payments stats: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Failed to load unverified deployment payments data'
            ], 500);
        }
    }

    /**
     * Normalize phone number by removing 91 prefix and leading zeros
     */
    private function normalizePhoneNumber($phone)
    {
        if (empty($phone)) {
            return $phone;
        }
        
        // Remove all non-digit characters
        $phone = preg_replace('/\D/', '', $phone);
        
        // Remove leading 91 if present (India country code)
        // Check if number starts with 91 and has 12+ digits
        if (strlen($phone) >= 12 && substr($phone, 0, 2) === '91') {
            $phone = substr($phone, 2);
        }
        
        // Remove leading 0s if present
        $phone = ltrim($phone, '0');
        
        return $phone;
    }

    public function getPendingCallbacks(Request $request)
    {
        try {
            // Get filter type from request (optional: 'all', 'lead', 'operation_lead', 'job_request')
            $filterType = $request->input('call_for', 'all');
            
            // Get all pending calls first
            $allCallLogsQuery = CallDetails::select('call_details.*')
                ->whereNotIn('call_details.call_status', ['answered', 'answer']);
            
            // Filter by call_for if specified
            if ($filterType !== 'all') {
                $allCallLogsQuery->where('call_details.call_for', $filterType);
            } else {
                // Show all valid call_for types (exclude null)
                $allCallLogsQuery->whereIn('call_details.call_for', ['lead', 'operation_lead', 'job_request']);
            }
            
            $allCallLogs = $allCallLogsQuery->orderBy('call_details.call_received_datetime', 'desc')->get();
            
            // Group by normalized phone number AND executive_id to get latest call per number per executive
            // This ensures each executive's pending callbacks are counted separately
            $normalizedNumbers = [];
            $callLogs = [];
            
            foreach ($allCallLogs as $callLog) {
                $normalizedNumber = $this->normalizePhoneNumber($callLog->caller_id_number);
                $executiveId = $callLog->executive_id ?? 'no_executive';
                
                // Create a unique key combining normalized number and executive_id
                $uniqueKey = $normalizedNumber . '_' . $executiveId;
                
                if (!isset($normalizedNumbers[$uniqueKey])) {
                    $normalizedNumbers[$uniqueKey] = $callLog;
                } else {
                    // Keep the latest call for this executive and phone number combination
                    if ($callLog->call_received_datetime > $normalizedNumbers[$uniqueKey]->call_received_datetime) {
                        $normalizedNumbers[$uniqueKey] = $callLog;
                    }
                }
            }
            
            $callLogs = collect($normalizedNumbers)->values()->all();

            $pendingCallbacks = [];

            foreach ($callLogs as $callLog) {
                $contactNumber = $callLog->caller_id_number;
                $normalizedNumber = $this->normalizePhoneNumber($contactNumber);
                
                // Try to find the associated lead/operation lead/job request using normalized number
                // Check both original and normalized formats
                $operationLead = OperationLead::where(function($query) use ($contactNumber, $normalizedNumber) {
                    $query->where('contact_no', $contactNumber)
                          ->orWhere('contact_no', $normalizedNumber)
                          ->orWhere('contact_no', '91' . $normalizedNumber)
                          ->orWhere('contact_no', '0' . $normalizedNumber);
                })->first();
                
                $lead = Lead::where(function($query) use ($contactNumber, $normalizedNumber) {
                    $query->where('contact_no', $contactNumber)
                          ->orWhere('contact_no', $normalizedNumber)
                          ->orWhere('contact_no', '91' . $normalizedNumber)
                          ->orWhere('contact_no', '0' . $normalizedNumber);
                })->first();
                
                $jobRequest = JobRequest::where(function($query) use ($contactNumber, $normalizedNumber) {
                    $query->where('contact_no', $contactNumber)
                          ->orWhere('contact_no', $normalizedNumber)
                          ->orWhere('contact_no', '91' . $normalizedNumber)
                          ->orWhere('contact_no', '0' . $normalizedNumber);
                })->first();
                
                // Check if there was a successful callback after this call (using normalized number)
                $successfulCallback = CallDetails::where(function($query) use ($contactNumber, $normalizedNumber) {
                    $query->where('caller_id_number', $contactNumber)
                          ->orWhere('caller_id_number', $normalizedNumber)
                          ->orWhere('caller_id_number', '91' . $normalizedNumber)
                          ->orWhere('caller_id_number', '0' . $normalizedNumber);
                })
                    ->where('call_received_datetime', '>', $callLog->call_received_datetime)
                    ->whereIn('call_status', ['answered', 'answer'])
                    ->orderBy('call_received_datetime', 'asc')
                    ->first();
                
                // Get the response time if there was a successful callback
                $responseDateTime = null;
                if ($successfulCallback) {
                    $responseDateTime = $successfulCallback->call_received_datetime;
                }

                // Don't skip - show all callbacks with response_datetime if answered

                // Get executive name
                $executiveName = 'N/A';
                if ($callLog->executive_id) {
                    $executive = User::find($callLog->executive_id);
                    if ($executive) {
                        $executiveName = $executive->f_name . ' ' . $executive->l_name;
                    }
                }

                // Determine lead info based on what we found and call_for
                $leadNo = 'N/A';
                $customerName = 'Unknown';
                $leadSource = 'N/A';
                $viewUrl = '#';
                $relatedId = null;
                $callFor = $callLog->call_for ?? 'N/A';

                // Use call_for if available, otherwise determine from found records
                if ($callFor === 'operation_lead' && $operationLead) {
                    $leadNo = $operationLead->lead_id ?: 'OP' . str_pad($operationLead->id, 8, '0', STR_PAD_LEFT);
                    $customerName = $operationLead->customer_name;
                    $leadSource = $operationLead->lead_source ?? 'N/A';
                    $viewUrl = route('subadmin.operation_leads.show', $operationLead->id);
                    $relatedId = $operationLead->id;
                } elseif ($callFor === 'job_request' && $jobRequest) {
                    $leadNo = $jobRequest->lead_id ?: 'JR' . str_pad($jobRequest->id, 8, '0', STR_PAD_LEFT);
                    $customerName = $jobRequest->customer_name;
                    $leadSource = 'Job Request';
                    $viewUrl = '#'; // JobRequest route not available
                    $relatedId = $jobRequest->id;
                } elseif ($callFor === 'lead' && $lead) {
                    $leadNo = $lead->lead_code ?: $lead->formatted_id;
                    $customerName = $lead->customer_name;
                    $leadSource = $lead->lead_source ?? 'N/A';
                    $viewUrl = route('subadmin.leads.show', $lead->id);
                    $relatedId = $lead->id;
                } elseif ($operationLead) {
                    // Fallback: Use operation lead if found
                    $leadNo = $operationLead->lead_id ?: 'OP' . str_pad($operationLead->id, 8, '0', STR_PAD_LEFT);
                    $customerName = $operationLead->customer_name;
                    $leadSource = $operationLead->lead_source ?? 'N/A';
                    $viewUrl = route('subadmin.operation_leads.show', $operationLead->id);
                    $relatedId = $operationLead->id;
                    $callFor = 'operation_lead';
                } elseif ($lead) {
                    // Fallback: Use lead if found
                    $leadNo = $lead->lead_code ?: $lead->formatted_id;
                    $customerName = $lead->customer_name;
                    $leadSource = $lead->lead_source ?? 'N/A';
                    $viewUrl = route('subadmin.leads.show', $lead->id);
                    $relatedId = $lead->id;
                    $callFor = 'lead';
                } elseif ($jobRequest) {
                    // Fallback: Use job request if found
                    $leadNo = $jobRequest->lead_id ?: 'JR' . str_pad($jobRequest->id, 8, '0', STR_PAD_LEFT);
                    $customerName = $jobRequest->customer_name;
                    $leadSource = 'Job Request';
                    $viewUrl = '#'; // JobRequest route not available
                    $relatedId = $jobRequest->id;
                    $callFor = 'job_request';
                }

                // Use CallDetails data if available
                if ($callLog->lead_code) {
                    $leadNo = $callLog->lead_code;
                }
                if ($callLog->customer_name) {
                    $customerName = $callLog->customer_name;
                }

                $pendingCallbacks[] = [
                    'lead_no' => $leadNo,
                    'operation_lead_id' => $relatedId,
                    'call_datetime' => $callLog->call_received_datetime,
                    'customer_name' => $customerName,
                    'customer_number' => $contactNumber,
                    'lead_source' => $leadSource,
                    'call_status' => $callLog->call_status,
                    'call_for' => $callFor,
                    'call_for_display' => $callFor === 'operation_lead' ? 'Operation Lead' : 
                                         ($callFor === 'job_request' ? 'Job Request' : 
                                         ($callFor === 'lead' ? 'Lead' : 'N/A')),
                    'call_log_id' => $callLog->id,
                    'executive_name' => $executiveName,
                    'view_url' => $viewUrl,
                    'response_datetime' => $responseDateTime,
                    'duration' => $callLog->call_duration ? gmdate('H:i:s', $callLog->call_duration) : 'N/A'
                ];
            }

            return response()->json([
                'success' => true,
                'total_count' => count($pendingCallbacks),
                'callbacks' => $pendingCallbacks
            ]);

        } catch (\Exception $e) {
            \Log::error('Error getting pending callbacks: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch pending callbacks: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function getRecentCallsSales(Request $request)
    {
        try {
            // Get call logs from the past week for sales executives
            $oneWeekAgo = now()->subWeek();
            
            // Only include calls for Leads (Sales team handles leads)
            $callLogs = CallDetails::with(['executive:id,f_name,l_name'])
                ->whereHas('executive', function($query) {
                    $query->whereHas('role', function($roleQuery) {
                        $roleQuery->whereIn('name', ['Sales', 'Sales Manager']);
                    });
                })
                ->where('call_for', 'lead') // Only Lead calls for Sales
                ->where('call_received_datetime', '>=', $oneWeekAgo)
                ->orderBy('call_received_datetime', 'desc')
                ->get();
            
            $recentCalls = [];
            $successfulCount = 0;
            $failedCount = 0;
            
            // Show ALL calls, not grouped by phone number - each call should appear separately
            foreach ($callLogs as $callLog) {
                $contactNumber = $callLog->caller_id_number;
                $normalizedNumber = $this->normalizePhoneNumber($contactNumber);
                
                // Try to find associated sales lead by contact number
                $lead = Lead::where(function($query) use ($contactNumber, $normalizedNumber) {
                    $query->where('contact_no', $contactNumber)
                          ->orWhere('contact_no', $normalizedNumber)
                          ->orWhere('contact_no', '91' . $normalizedNumber)
                          ->orWhere('contact_no', '0' . $normalizedNumber);
                })
                    ->orderBy('created_at', 'desc')
                    ->first();
                
                // Count successful vs failed calls
                if (in_array($callLog->call_status, ['answered', 'answer'])) {
                    $successfulCount++;
                } else {
                    $failedCount++;
                }
                
                // Get executive name
                $executiveName = 'N/A';
                if ($callLog->executive) {
                    $executiveName = trim($callLog->executive->f_name . ' ' . $callLog->executive->l_name);
                }
                
                // Use CallDetails data if available
                $leadNo = $callLog->lead_code ?: ($lead ? ($lead->lead_code ?: $lead->formatted_id) : 'No Lead');
                $customerName = $callLog->customer_name ?: ($lead ? $lead->customer_name : 'N/A');

                $recentCalls[] = [
                    'lead_no' => $leadNo,
                    'lead_id' => $lead ? $lead->id : null,
                    'call_datetime' => $callLog->call_received_datetime ? $callLog->call_received_datetime->format('d-M-Y H:i:s') : 'N/A',
                    'executive_name' => $executiveName,
                    'customer_name' => $customerName,
                    'customer_number' => $contactNumber,
                    'call_status' => $callLog->call_status,
                    'call_for' => $callLog->call_for ?? 'lead',
                    'call_for_display' => $callLog->call_for === 'lead' ? 'Lead' : 
                                         ($callLog->call_for === 'operation_lead' ? 'Operation Lead' : 
                                         ($callLog->call_for === 'job_request' ? 'Job Request' : 'Lead')),
                    'call_direction' => CallDirectionLabel::display($callLog->call_type, $callLog->raw_data),
                    'duration' => $callLog->call_duration ? gmdate('H:i:s', $callLog->call_duration) : 'N/A',
                    'call_log_id' => $callLog->id,
                    'view_url' => $lead ? route('subadmin.leads.show', $lead->id) : null,
                    'recording_url' => $callLog->recording_url ?? null,
                    'has_recording' => !empty($callLog->recording_url)
                ];
            }
            
            return response()->json([
                'success' => true,
                'total_count' => count($recentCalls),
                'successful_count' => $successfulCount,
                'failed_count' => $failedCount,
                'calls' => $recentCalls
            ]);
        } catch (\Exception $e) {
            \Log::error('Error getting recent sales calls (Admin): ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Failed to load recent sales calls data: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getRecentCallsOperation(Request $request)
    {
        try {
            // Get call logs from the past week for operation executives
            $oneWeekAgo = now()->subWeek();
            
            // Only include calls for OperationLead and JobRequest (Operation team handles these)
            $callLogs = CallDetails::with(['executive:id,f_name,l_name'])
                ->whereHas('executive', function($query) {
                    $query->whereHas('role', function($roleQuery) {
                        $roleQuery->whereIn('name', ['Operation', 'Operation Manager']);
                    });
                })
                ->whereIn('call_for', ['operation_lead', 'job_request']) // Only OperationLead and JobRequest calls
                ->where('call_received_datetime', '>=', $oneWeekAgo)
                ->orderBy('call_received_datetime', 'desc')
                ->get();
            
            $recentCalls = [];
            $successfulCount = 0;
            $failedCount = 0;
            
            // Show ALL calls, not grouped by phone number - each call should appear separately
            foreach ($callLogs as $callLog) {
                $contactNumber = $callLog->caller_id_number;
                $normalizedNumber = $this->normalizePhoneNumber($contactNumber);
                
                // Try to find associated operation lead or job request by contact number
                $lead = null;
                $jobRequest = null;
                $leadCode = 'No Lead';
                $customerName = 'N/A';
                $viewUrl = null;
                $relatedId = null;
                
                // Check call_for to determine what to find
                if ($callLog->call_for === 'operation_lead') {
                    $lead = OperationLead::where(function($query) use ($contactNumber, $normalizedNumber) {
                        $query->where('contact_no', $contactNumber)
                              ->orWhere('contact_no', $normalizedNumber)
                              ->orWhere('contact_no', '91' . $normalizedNumber)
                              ->orWhere('contact_no', '0' . $normalizedNumber);
                    })
                        ->orderBy('created_at', 'desc')
                        ->first();
                    if ($lead) {
                        $leadCode = $lead->lead_id ?: 'OP' . str_pad($lead->id, 8, '0', STR_PAD_LEFT);
                        $customerName = $lead->customer_name ?: 'N/A';
                        $viewUrl = route('subadmin.operation_leads.show', $lead->id);
                        $relatedId = $lead->id;
                    }
                } elseif ($callLog->call_for === 'job_request') {
                    $jobRequest = JobRequest::where(function($query) use ($contactNumber, $normalizedNumber) {
                        $query->where('contact_no', $contactNumber)
                              ->orWhere('contact_no', $normalizedNumber)
                              ->orWhere('contact_no', '91' . $normalizedNumber)
                              ->orWhere('contact_no', '0' . $normalizedNumber);
                    })
                        ->orderBy('created_at', 'desc')
                        ->first();
                    if ($jobRequest) {
                        $leadCode = 'JR' . str_pad($jobRequest->id, 8, '0', STR_PAD_LEFT);
                        $customerName = $jobRequest->customer_name ?: 'N/A';
                        $viewUrl = '#'; // JobRequest doesn't have a show route yet
                        $relatedId = $jobRequest->id;
                    }
                }
                
                // Use CallDetails data if available
                if ($callLog->lead_code) {
                    $leadCode = $callLog->lead_code;
                }
                if ($callLog->customer_name) {
                    $customerName = $callLog->customer_name;
                }
                
                // Count successful vs failed calls
                if (in_array($callLog->call_status, ['answered', 'answer'])) {
                    $successfulCount++;
                } else {
                    $failedCount++;
                }
                
                // Get executive name
                $executiveName = 'N/A';
                if ($callLog->executive) {
                    $executiveName = trim($callLog->executive->f_name . ' ' . $callLog->executive->l_name);
                } elseif ($jobRequest && $jobRequest->executive_id) {
                    $executive = User::find($jobRequest->executive_id);
                    $executiveName = $executive ? ($executive->f_name . ' ' . $executive->l_name) : 'N/A';
                }
                
                $recentCalls[] = [
                    'lead_no' => $leadCode,
                    'lead_id' => $relatedId,
                    'call_datetime' => $callLog->call_received_datetime ? $callLog->call_received_datetime->format('d-M-Y H:i:s') : 'N/A',
                    'executive_name' => $executiveName,
                    'customer_name' => $customerName,
                    'customer_number' => $contactNumber,
                    'call_status' => $callLog->call_status,
                    'call_for' => $callLog->call_for ?? 'N/A',
                    'call_for_display' => $callLog->call_for === 'operation_lead' ? 'Operation Lead' : 
                                         ($callLog->call_for === 'job_request' ? 'Job Request' : 'N/A'),
                    'call_direction' => CallDirectionLabel::display($callLog->call_type, $callLog->raw_data),
                    'duration' => $callLog->call_duration ? gmdate('H:i:s', $callLog->call_duration) : 'N/A',
                    'call_log_id' => $callLog->id,
                    'view_url' => $viewUrl,
                    'recording_url' => $callLog->recording_url ?? null,
                    'has_recording' => !empty($callLog->recording_url)
                ];
            }
            
            return response()->json([
                'success' => true,
                'total_count' => count($recentCalls),
                'successful_count' => $successfulCount,
                'failed_count' => $failedCount,
                'calls' => $recentCalls
            ]);
        } catch (\Exception $e) {
            \Log::error('Error getting recent operation calls (Admin): ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Failed to load recent operation calls data: ' . $e->getMessage()
            ], 500);
        }
    }

    public function locationAttendance(Request $request)
    {
        $date = $request->get('attendance_date', now()->toDateString());

        $likeTerm = static function (string $value): string {
            $t = trim($value);

            return '%' . str_replace(['%', '_'], ['\%', '\_'], $t) . '%';
        };

        $query = OperationDeploymentDetails::query()
            ->with([
                'operationLead:id,lead_id,customer_name,contact_no,executive',
                'operationLead.executive:id,f_name,l_name',
                'vendor:id,name',
                'freelanceStaff:id,name',
            ])
            ->where(function ($q) {
                $q->whereNotNull('vendor_id')->orWhereNotNull('freelance_staff_id');
            });

        if ($request->filled('operation_lead_id')) {
            $query->where('operation_lead_id', (int) $request->integer('operation_lead_id'));
        }

        if ($request->filled('customer_q')) {
            $term = $likeTerm($request->string('customer_q')->toString());
            $query->whereHas('operationLead', function ($q) use ($term) {
                $q->where(function ($q2) use ($term) {
                    $q2->where('customer_name', 'like', $term)
                        ->orWhere('contact_no', 'like', $term)
                        ->orWhere('lead_id', 'like', $term);
                });
            });
        }

        if ($request->filled('provider_q')) {
            $term = $likeTerm($request->string('provider_q')->toString());
            $query->where(function ($q) use ($term) {
                $q->whereHas('vendor', fn ($v) => $v->where('name', 'like', $term))
                    ->orWhereHas('freelanceStaff', fn ($f) => $f->where('name', 'like', $term));
            });
        }

        if ($request->filled('executive_q')) {
            $term = $likeTerm($request->string('executive_q')->toString());
            $query->whereHas('operationLead', function ($q) use ($term) {
                $q->whereHas('executive', function ($ex) use ($term) {
                    $ex->where('f_name', 'like', $term)
                        ->orWhere('l_name', 'like', $term)
                        ->orWhereRaw(
                            "CONCAT(COALESCE(f_name,''), ' ', COALESCE(l_name,'')) LIKE ?",
                            [$term]
                        );
                });
            });
        }

        $assignmentType = $request->get('assignment_type');
        if ($assignmentType === 'vendor') {
            $query->whereNotNull('vendor_id');
        } elseif ($assignmentType === 'freelancer') {
            $query->whereNotNull('freelance_staff_id');
        }

        $query->orderByDesc('deployment_from_date')
            ->orderByDesc('id');

        $rows = $query->paginate(10)->withQueryString();

        $deploymentItems = $rows->getCollection();
        if ($deploymentItems->isNotEmpty()) {
            $leadIds = $deploymentItems->pluck('operation_lead_id')->unique()->values()->all();

            $startDates = $deploymentItems->map(function ($item) {
                return optional($item->deployment_from_date ?: $item->deployment_date)->toDateString();
            })->filter()->values();
            $endDates = $deploymentItems->map(function ($item) {
                return optional($item->deployment_to_date ?: $item->deployment_from_date ?: $item->deployment_date)->toDateString();
            })->filter()->values();

            $minDate = $startDates->isNotEmpty() ? $startDates->min() : now()->toDateString();
            $maxDate = $endDates->isNotEmpty() ? $endDates->max() : now()->toDateString();

            $attendanceRows = DeploymentLocationAttendance::query()
                ->whereIn('operation_lead_id', $leadIds)
                ->whereBetween('attendance_date', [$minDate, $maxDate])
                ->get();

            $attendanceMap = [];
            foreach ($attendanceRows as $attendanceRow) {
                $type = $attendanceRow->vendor_id ? 'vendor' : 'freelancer';
                $providerId = $attendanceRow->vendor_id ?: $attendanceRow->freelancer_id;
                if (!$providerId) {
                    continue;
                }
                $attendanceDate = optional($attendanceRow->attendance_date)->toDateString();
                if (!$attendanceDate) {
                    continue;
                }
                $key = $attendanceRow->operation_lead_id . '|' . $type . '|' . $providerId;
                $attendanceMap[$key][$attendanceDate] = $attendanceRow;
            }

            $deploymentItems->transform(function ($item) use ($attendanceMap) {
                $type = $item->vendor_id ? 'vendor' : 'freelancer';
                $providerId = $item->vendor_id ?: $item->freelance_staff_id;
                $key = $item->operation_lead_id . '|' . $type . '|' . $providerId;

                $start = $item->deployment_from_date ?: $item->deployment_date;
                $end = $item->deployment_to_date ?: $item->deployment_from_date ?: $item->deployment_date;

                if (!$start || !$end) {
                    $item->attendance_day_statuses = [];
                    return $item;
                }

                $startDate = Carbon::parse($start);
                $endDate = Carbon::parse($end);
                if ($startDate->gt($endDate)) {
                    [$startDate, $endDate] = [$endDate, $startDate];
                }

                $statuses = [];
                $cursor = $startDate->copy()->startOfDay();
                $rangeEnd = $endDate->copy()->startOfDay();
                $maxDots = 180;
                $dotsCount = 0;

                while ($cursor->lte($rangeEnd) && $dotsCount < $maxDots) {
                    $day = $cursor->toDateString();
                    $row = $attendanceMap[$key][$day] ?? null;
                    $isMarked = $row
                        && $row->attendance_status === 'present'
                        && (bool) $row->is_location_matched;

                    $statuses[] = [
                        'date' => $day,
                        'is_marked' => $isMarked,
                        'status' => $row->attendance_status ?? 'pending',
                    ];
                    $cursor->addDay();
                    $dotsCount++;
                }

                $item->attendance_day_statuses = $statuses;
                return $item;
            });
        }

        return view('admin.attendance.location-attendance', compact('rows', 'date'));
    }

    /**
     * Day-by-day GPS + selfie detail for a deployment (admin location attendance "View").
     */
    public function locationAttendanceDeploymentDetail(OperationDeploymentDetails $deployment)
    {
        $deployment->load([
            'operationLead:id,lead_id,customer_name,contact_no',
            'vendor:id,name',
            'freelanceStaff:id,name',
        ]);

        $isVendor = (bool) $deployment->vendor_id;
        if (! $isVendor && ! $deployment->freelance_staff_id) {
            return response()->json(['success' => false, 'message' => 'Invalid deployment assignment'], 404);
        }

        $tz = config('app.timezone');

        $start = $deployment->deployment_from_date ?: $deployment->deployment_date;
        $end = $deployment->deployment_to_date ?: $deployment->deployment_from_date ?: $deployment->deployment_date;

        $providerName = $isVendor
            ? ($deployment->vendor->name ?? 'Vendor')
            : ($deployment->freelanceStaff->name ?? 'Freelancer');

        $deploymentPayload = [
            'id' => $deployment->id,
            'operation_lead_id' => $deployment->operation_lead_id,
            'lead_ref' => $deployment->operationLead->lead_id ?? ('#'.$deployment->operation_lead_id),
            'customer_name' => $deployment->operationLead->customer_name ?? 'N/A',
            'contact_no' => $deployment->operationLead->contact_no ?? '',
            'assignment_type' => $isVendor ? 'vendor' : 'freelancer',
            'provider_name' => $providerName,
            'deployment_from' => $start ? Carbon::parse($start)->timezone($tz)->format('d M Y, h:i A') : null,
            'deployment_to' => $end ? Carbon::parse($end)->timezone($tz)->format('d M Y, h:i A') : null,
        ];

        if (! $start || ! $end) {
            return response()->json([
                'success' => true,
                'deployment' => $deploymentPayload,
                'days' => [],
            ]);
        }

        $startDate = Carbon::parse($start)->startOfDay();
        $endDate = Carbon::parse($end)->startOfDay();
        if ($startDate->gt($endDate)) {
            $tmp = $startDate->copy();
            $startDate = $endDate->copy();
            $endDate = $tmp;
        }

        $q = DeploymentLocationAttendance::query()
            ->where('operation_lead_id', $deployment->operation_lead_id);
        if ($isVendor) {
            $q->where('vendor_id', $deployment->vendor_id)->whereNull('freelancer_id');
        } else {
            $q->where('freelancer_id', $deployment->freelance_staff_id)->whereNull('vendor_id');
        }

        $records = $q
            ->whereBetween('attendance_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->orderBy('attendance_date')
            ->get()
            ->keyBy(function (DeploymentLocationAttendance $r) {
                $d = $r->attendance_date;
                if ($d instanceof Carbon) {
                    return $d->toDateString();
                }

                return $d ? Carbon::parse($d)->toDateString() : '';
            });

        $days = [];
        $cursor = $startDate->copy();
        $guard = 0;
        while ($cursor->lte($endDate) && $guard < 400) {
            $dayStr = $cursor->toDateString();
            /** @var DeploymentLocationAttendance|null $att */
            $att = $records->get($dayStr);
            $days[] = $this->locationAttendanceDayDetailPayload($att, $isVendor, $dayStr, $tz);
            $cursor->addDay();
            $guard++;
        }

        return response()->json([
            'success' => true,
            'deployment' => $deploymentPayload,
            'days' => $days,
        ]);
    }

    private function locationAttendanceDayDetailPayload(?DeploymentLocationAttendance $att, bool $isVendor, string $dayStr, string $tz): array
    {
        $dateLabel = Carbon::parse($dayStr)->timezone($tz)->format('l, d M Y');

        if (! $att) {
            return [
                'date_iso' => $dayStr,
                'date_label' => $dateLabel,
                'has_record' => false,
            ];
        }

        $fmt = static fn ($carbon) => $carbon
            ? Carbon::parse($carbon)->timezone($tz)->format('d M Y, h:i A')
            : null;

        if ($isVendor) {
            $provLat = $att->vendor_latitude;
            $provLng = $att->vendor_longitude;
            $provAt = $att->vendor_location_captured_at;
            $selfieUrl = null;
            $selfieAt = null;
        } else {
            $provLat = $att->freelancer_latitude;
            $provLng = $att->freelancer_longitude;
            $provAt = $att->freelancer_location_captured_at;
            $selfiePath = $att->freelancer_selfie_path;
            $selfieUrl = $selfiePath ? Storage::disk('public')->url($selfiePath) : null;
            if ($selfieUrl && str_starts_with($selfieUrl, '/')) {
                $selfieUrl = url($selfieUrl);
            }
            $selfieAt = $fmt($att->freelancer_selfie_captured_at);
        }

        $custLat = $att->customer_latitude;
        $custLng = $att->customer_longitude;

        $maps = static function ($lat, $lng) {
            if ($lat === null || $lng === null) {
                return null;
            }

            return 'https://www.google.com/maps?q='.rawurlencode((string) $lat).','.rawurlencode((string) $lng);
        };

        return [
            'date_iso' => $dayStr,
            'date_label' => $dateLabel,
            'has_record' => true,
            'attendance_status' => $att->attendance_status,
            'is_location_matched' => (bool) $att->is_location_matched,
            'distance_meters' => $att->distance_meters,
            'provider_location_at' => $fmt($provAt),
            'provider_latitude' => $provLat !== null ? (float) $provLat : null,
            'provider_longitude' => $provLng !== null ? (float) $provLng : null,
            'provider_maps_url' => $maps($provLat, $provLng),
            'freelancer_selfie_url' => $selfieUrl,
            'freelancer_selfie_at' => $selfieAt,
            'customer_location_at' => $fmt($att->customer_location_captured_at),
            'customer_latitude' => $custLat !== null ? (float) $custLat : null,
            'customer_longitude' => $custLng !== null ? (float) $custLng : null,
            'customer_maps_url' => $maps($custLat, $custLng),
            'attendance_marked_at' => $fmt($att->attendance_marked_at),
        ];
    }
}
