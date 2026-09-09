<?php

namespace App\Http\Controllers\OperationManager;

use App\Http\Controllers\Controller;
use App\Models\OperationLead;
use App\Models\User;
use App\Models\Task;
use App\Models\PaymentInvoice;
use App\Models\ReceivedPayment;
use App\Models\OperationDeploymentDetails;
use App\Models\JobRequest;
use App\Models\CallLog;
use App\Models\CallDetails;
use App\Support\CallDirectionLabel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
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
        if (strlen($phone) >= 12 && substr($phone, 0, 2) === '91') {
            $phone = substr($phone, 2);
        }
        
        // Remove leading 0s if present
        $phone = ltrim($phone, '0');
        
        return $phone;
    }

    public function dashboard()
    {
        $user = Auth::user();

        // Get today's operation leads for the team
        $todayOperationLeads = OperationLead::whereIn('executive', function($query) use ($user) {
            $query->select('id')
                  ->from('users')
                  ->where('parent_id', $user->id);
        })
        ->whereDate('date_time', Carbon::today())
        ->count();

        // Get total team members
        $totalUsers = User::where('parent_id', $user->id)->count();
        $activeUsers = User::where('parent_id', $user->id)->where('is_active', 1)->count();
        $inactiveUsers = User::where('parent_id', $user->id)->where('is_active', 0)->count();

        // Get tasks for the operation manager
        $totalTasks = Task::where('assigned_to', $user->id)->count();
        $pendingTasks = Task::where('assigned_to', $user->id)->where('status', 'pending')->count();
        $completedTasks = Task::where('assigned_to', $user->id)->where('status', 'completed')->count();
        $overdueTasks = Task::where('assigned_to', $user->id)
            ->where('due_date', '<', Carbon::today())
            ->where('status', '!=', 'completed')
            ->count();

        // Calculate total outstanding amount for team leads
        $totalOutstandingAmount = $this->calculateTotalOutstandingAmount($user);

        // Calculate deployment pending count for team leads
        $deploymentPendingCount = $this->calculateDeploymentPendingCount($user);

        // Calculate profile pending count for team leads
        $profilePendingCount = $this->calculateProfilePendingCount($user);

        // Calculate ongoing count for team leads
        $ongoingCount = $this->calculateOngoingCount($user);

        // Calculate unverified payments count for team leads
        $unverifiedPayments = $this->calculateUnverifiedPaymentsCount($user);

        // Calculate job requests count (excluding inactive, active, and blacklisted)
        $nonActiveJobRequests = JobRequest::whereNotIn('status', ['inactive', 'active', 'blacklisted'])->count();

        // Calculate unverified vendor/freelancer payments count
        $unverifiedVendorFreelancerPayments = OperationDeploymentDetails::where('verify_payment', false)
            ->whereNotNull('vendor_payment')
            ->where('vendor_payment', '>', 0)
            ->count();

        $page_heading = 'Operation Manager Dashboard';

        return view('operation_manager.dashboard', compact(
            'page_heading',
            'todayOperationLeads',
            'totalUsers',
            'activeUsers',
            'inactiveUsers',
            'totalTasks',
            'pendingTasks',
            'completedTasks',
            'overdueTasks',
            'totalOutstandingAmount',
            'deploymentPendingCount',
            'profilePendingCount',
            'ongoingCount',
            'unverifiedPayments',
            'nonActiveJobRequests',
            'unverifiedVendorFreelancerPayments'
        ));
    }

    public function getStats()
    {
        try {
            $user = Auth::user();

            // Get today's operation leads for the team
            $todayOperationLeads = OperationLead::whereIn('executive', function($query) use ($user) {
                $query->select('id')
                      ->from('users')
                      ->where('parent_id', $user->id);
            })
            ->whereDate('date_time', Carbon::today())
            ->count();

            // Get total team members
            $totalUsers = User::where('parent_id', $user->id)->count();
            $activeUsers = User::where('parent_id', $user->id)->where('is_active', 1)->count();
            $inactiveUsers = User::where('parent_id', $user->id)->where('is_active', 0)->count();

            // Get tasks for the operation manager
            $totalTasks = Task::where('assigned_to', $user->id)->count();
            $pendingTasks = Task::where('assigned_to', $user->id)->where('status', 'pending')->count();
            $completedTasks = Task::where('assigned_to', $user->id)->where('status', 'completed')->count();
            $overdueTasks = Task::where('assigned_to', $user->id)
                ->where('due_date', '<', Carbon::today())
                ->where('status', '!=', 'completed')
                ->count();

            // Calculate total outstanding amount for team leads
            $totalOutstandingAmount = $this->calculateTotalOutstandingAmount($user);

            // Calculate deployment pending count for team leads
            $deploymentPendingCount = $this->calculateDeploymentPendingCount($user);

            // Calculate profile pending count for team leads
            $profilePendingCount = $this->calculateProfilePendingCount($user);

            // Calculate ongoing count for team leads
            $ongoingCount = $this->calculateOngoingCount($user);

            // Calculate unverified payments count for team leads
            $unverifiedPayments = $this->calculateUnverifiedPaymentsCount($user);

            // Calculate job requests count (excluding inactive, active, and blacklisted)
            $nonActiveJobRequests = JobRequest::whereNotIn('status', ['inactive', 'active', 'blacklisted'])->count();

            // Calculate unverified vendor/freelancer payments count
            $unverifiedVendorFreelancerPayments = OperationDeploymentDetails::where('verify_payment', false)
                ->whereNotNull('vendor_payment')
                ->where('vendor_payment', '>', 0)
                ->count();

            // Pending callbacks count (team members' non-answered calls for operation_lead / job_request)
            $teamMemberIds = User::where('parent_id', $user->id)->pluck('id')->toArray();
            $pendingCallbacksCount = CallDetails::whereIn('executive_id', $teamMemberIds)
                ->whereIn('call_for', ['operation_lead', 'job_request'])
                ->whereNotIn('call_status', ['answered', 'answer'])
                ->count();

            // Recent calls count (team members' calls in the past week)
            $oneWeekAgo = now()->subWeek();
            $recentCallsCount = CallDetails::whereIn('executive_id', $teamMemberIds)
                ->whereIn('call_for', ['operation_lead', 'job_request'])
                ->where('call_received_datetime', '>=', $oneWeekAgo)
                ->count();

            return response()->json([
                'success' => true,
                'data' => [
                    'todayOperationLeads' => $todayOperationLeads,
                    'totalUsers' => $totalUsers,
                    'activeUsers' => $activeUsers,
                    'inactiveUsers' => $inactiveUsers,
                    'totalTasks' => $totalTasks,
                    'pendingTasks' => $pendingTasks,
                    'completedTasks' => $completedTasks,
                    'overdueTasks' => $overdueTasks,
                    'totalOutstandingAmount' => $totalOutstandingAmount,
                    'deploymentPendingCount' => $deploymentPendingCount,
                    'profilePendingCount' => $profilePendingCount,
                    'ongoingCount' => $ongoingCount,
                    'unverifiedPayments' => $unverifiedPayments,
                    'nonActiveJobRequests' => $nonActiveJobRequests,
                    'unverifiedVendorFreelancerPayments' => $unverifiedVendorFreelancerPayments,
                    'pendingCallbacksCount' => $pendingCallbacksCount,
                    'recentCallsCount' => $recentCallsCount,
                ]
            ]);
        } catch (\Exception $e) {
            \Log::error('Error getting dashboard stats: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to load dashboard statistics'
            ], 500);
        }
    }

    public function getLeadsStats(Request $request)
    {
        try {
            $user = Auth::user();
            $period = $request->get('period', 'today');
            $startDate = $request->get('start_date');
            $endDate = $request->get('end_date');
            $selectedMonth = $request->get('selected_month');
            $selectedYear = $request->get('selected_year');

            // Get team member IDs
            $teamMemberIds = User::where('parent_id', $user->id)->pluck('id')->toArray();

            $query = OperationLead::whereIn('executive', $teamMemberIds);

            // Apply date filters based on period
            switch ($period) {
                case 'today':
                    $query->whereDate('date_time', Carbon::today());
                    break;
                case 'monthly':
                    if ($selectedMonth) {
                        [$year, $month] = explode('-', $selectedMonth);
                        $query->whereYear('date_time', $year)
                              ->whereMonth('date_time', $month);
                    } else {
                        $query->whereMonth('date_time', Carbon::now()->month)
                              ->whereYear('date_time', Carbon::now()->year);
                    }
                    break;
                case 'yearly':
                    if ($selectedYear) {
                        $query->whereYear('date_time', $selectedYear);
                    } else {
                        $query->whereYear('date_time', Carbon::now()->year);
                    }
                    break;
                case 'custom':
                    if ($startDate && $endDate) {
                        $query->whereBetween('date_time', [
                            Carbon::parse($startDate)->startOfDay(),
                            Carbon::parse($endDate)->endOfDay()
                        ]);
                    }
                    break;
            }

            $leads = $query->with('executive')->get();

            // Calculate stats
            $stats = [];
            if ($period === 'custom' && $startDate && $endDate) {
                $start = Carbon::parse($startDate);
                $end = Carbon::parse($endDate);
                $days = $start->diffInDays($end) + 1;

                for ($i = 0; $i < $days; $i++) {
                    $date = $start->copy()->addDays($i);
                    $stats[] = [
                        'date' => $date->format('Y-m-d'),
                        'count' => $leads->where('date_time', '>=', $date->startOfDay())
                                        ->where('date_time', '<=', $date->endOfDay())
                                        ->count()
                    ];
                }
            }

            return response()->json([
                'leads' => $leads,
                'stats' => $stats,
                'startDate' => $startDate,
                'endDate' => $endDate
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch leads stats: ' . $e->getMessage()], 500);
        }
    }

    public function getUsersStats(Request $request)
    {
        try {
            $user = Auth::user();
            $status = $request->get('status', 'all');

            $query = User::where('parent_id', $user->id)
                ->with(['role:id,name', 'location:id,name']);

            if ($status === 'active') {
                $query->where('is_active', 1);
            } elseif ($status === 'inactive') {
                $query->where('is_active', 0);
            }

            $users = $query->get();

            // Transform the data to ensure consistent structure
            $transformedUsers = $users->map(function ($user) {
                return [
                    'id' => $user->id,
                    'f_name' => $user->f_name,
                    'l_name' => $user->l_name,
                    'email' => $user->email,
                    'mobile' => $user->mobile,
                    'is_active' => $user->is_active,
                    'created_at' => $user->created_at,
                    'profile_image' => $user->profile_image,
                    'location_name' => $user->location ? $user->location->name : 'N/A',
                    'roles' => $user->role ? $user->role->name : 'N/A'
                ];
            });

            return response()->json(['users' => $transformedUsers]);
        } catch (\Exception $e) {
            \Log::error('getUsersStats Error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch users stats: ' . $e->getMessage()], 500);
        }
    }

    public function getTasks(Request $request)
    {
        try {
            $user = Auth::user();
            $type = $request->get('type', 'assigned_to_me');

            $query = Task::with(['assignedTo:id,f_name,l_name', 'assignedBy:id,f_name,l_name']);

            switch ($type) {
                case 'assigned_to_me':
                    $query->where('assigned_to', $user->id);
                    break;
                case 'assigned_by_me':
                    $query->where('assigned_by', $user->id);
                    break;
                case 'parent_manager_tasks':
                    // Get tasks assigned by parent manager
                    $parentManagerId = $user->parent_id;
                    if ($parentManagerId) {
                        $query->where('assigned_by', $parentManagerId)
                              ->where('assigned_to', $user->id);
                    } else {
                        $query->where('id', 0); // No results
                    }
                    break;
                case 'all':
                    // Get all tasks for team members
                    $teamMemberIds = User::where('parent_id', $user->id)->pluck('id')->toArray();
                    $teamMemberIds[] = $user->id; // Include self
                    $query->whereIn('assigned_to', $teamMemberIds);
                    break;
            }

            $tasks = $query->orderBy('created_at', 'desc')->get();

            return response()->json(['tasks' => $tasks]);
        } catch (\Exception $e) {
            \Log::error('getTasks Error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch tasks: ' . $e->getMessage()], 500);
        }
    }

    public function getTaskDetails($id)
    {
        try {
            $user = Auth::user();

            $task = Task::with(['assignedTo:id,f_name,l_name', 'assignedBy:id,f_name,l_name'])
                ->where(function($query) use ($user) {
                    $query->where('assigned_to', $user->id)
                          ->orWhere('assigned_by', $user->id);
                })
                ->findOrFail($id);

            return response()->json(['task' => $task]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch task details: ' . $e->getMessage()], 500);
        }
    }

    public function storeTask(Request $request)
    {
        try {
            $user = Auth::user();

            $request->validate([
                'title' => 'required|string|max:255',
                'description' => 'nullable|string',
                'priority' => 'required|in:low,medium,high,urgent',
                'due_date' => 'required|date|after_or_equal:today',
                'assigned_to' => 'required|exists:users,id'
            ]);

            // Verify the assigned user is a team member
            $teamMember = User::where('parent_id', $user->id)
                ->where('id', $request->assigned_to)
                ->first();

            if (!$teamMember) {
                return response()->json(['error' => 'Invalid team member selected'], 400);
            }

            $task = Task::create([
                'title' => $request->title,
                'description' => $request->description,
                'priority' => $request->priority,
                'due_date' => $request->due_date,
                'assigned_to' => $request->assigned_to,
                'assigned_by' => $user->id,
                'status' => 'pending'
            ]);

            return response()->json(['success' => true, 'task' => $task]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to create task: ' . $e->getMessage()], 500);
        }
    }

    public function updateTask(Request $request, $id)
    {
        try {
            $user = Auth::user();

            $task = Task::where('assigned_by', $user->id)->findOrFail($id);

            $request->validate([
                'title' => 'required|string|max:255',
                'description' => 'nullable|string',
                'priority' => 'required|in:low,medium,high,urgent',
                'due_date' => 'required|date',
                'assigned_to' => 'required|exists:users,id',
                'status' => 'required|in:pending,in_progress,completed,cancelled'
            ]);

            // Verify the assigned user is a team member
            $teamMember = User::where('parent_id', $user->id)
                ->where('id', $request->assigned_to)
                ->first();

            if (!$teamMember) {
                return response()->json(['error' => 'Invalid team member selected'], 400);
            }

            $task->update([
                'title' => $request->title,
                'description' => $request->description,
                'priority' => $request->priority,
                'due_date' => $request->due_date,
                'assigned_to' => $request->assigned_to,
                'status' => $request->status,
                'completed_at' => $request->status === 'completed' ? Carbon::now() : null
            ]);

            return response()->json(['success' => true, 'task' => $task]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to update task: ' . $e->getMessage()], 500);
        }
    }

    public function updateTaskStatus(Request $request, $id)
    {
        try {
            $user = Auth::user();

            $task = Task::where('assigned_to', $user->id)->findOrFail($id);

            $request->validate([
                'status' => 'required|in:pending,in_progress,completed,cancelled'
            ]);

            $task->update([
                'status' => $request->status,
                'completed_at' => $request->status === 'completed' ? Carbon::now() : null
            ]);

            return response()->json(['success' => true, 'task' => $task]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to update task status: ' . $e->getMessage()], 500);
        }
    }

    public function deleteTask($id)
    {
        try {
            $user = Auth::user();

            $task = Task::where('assigned_by', $user->id)->findOrFail($id);
            $task->delete();

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to delete task: ' . $e->getMessage()], 500);
        }
    }

    public function getTaskHistory(Request $request)
    {
        try {
            $user = Auth::user();

            $query = Task::with(['assignedTo:id,f_name,l_name', 'assignedBy:id,f_name,l_name'])
                ->where(function($q) use ($user) {
                    $q->where('assigned_to', $user->id)
                      ->orWhere('assigned_by', $user->id);
                });

            // Apply filters
            if ($request->has('date_from') && $request->date_from) {
                $query->whereDate('created_at', '>=', $request->date_from);
            }
            if ($request->has('date_to') && $request->date_to) {
                $query->whereDate('created_at', '<=', $request->date_to);
            }
            if ($request->has('assigned_to') && $request->assigned_to) {
                $query->where('assigned_to', $request->assigned_to);
            }
            if ($request->has('assigned_by') && $request->assigned_by) {
                $query->where('assigned_by', $request->assigned_by);
            }
            if ($request->has('status') && $request->status) {
                $query->where('status', $request->status);
            }
            if ($request->has('priority') && $request->priority) {
                $query->where('priority', $request->priority);
            }

            $tasks = $query->orderBy('created_at', 'desc')->get();

            return response()->json(['tasks' => $tasks]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch task history: ' . $e->getMessage()], 500);
        }
    }

    public function getTeamMembers()
    {
        try {
            $user = Auth::user();

            $teamMembers = User::where('parent_id', $user->id)
                ->select('id', 'f_name', 'l_name')
                ->get();

            return response()->json(['team_members' => $teamMembers]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch team members: ' . $e->getMessage()], 500);
        }
    }

    public function getCalendarData(Request $request)
    {
        try {
            $user = Auth::user();
            $year = $request->get('year', date('Y'));
            $month = $request->get('month', date('n'));

            $startDate = Carbon::create($year, $month, 1)->startOfMonth();
            $endDate = Carbon::create($year, $month, 1)->endOfMonth();

            $calendarData = [];

            // Get team member IDs
            $teamMemberIds = User::where('parent_id', $user->id)->pluck('id')->toArray();

            // Get follow-up leads (operation leads with status 'follow up' based on date_time field)
            $followUpLeads = OperationLead::whereIn('executive', $teamMemberIds)
                ->where('status', 'follow up')
                ->whereBetween('date_time', [$startDate, $endDate])
                ->get();

            foreach ($followUpLeads as $lead) {
                $date = Carbon::parse($lead->date_time)->format('Y-m-d');
                if (!isset($calendarData[$date])) {
                    $calendarData[$date] = ['follow_up' => 0];
                }
                $calendarData[$date]['follow_up']++;
            }

            return response()->json(['calendar_data' => $calendarData]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch calendar data: ' . $e->getMessage()], 500);
        }
    }

    public function getDateLeads(Request $request)
    {
        try {
            $user = Auth::user();
            $date = $request->get('date');

            if (!$date) {
                return response()->json(['error' => 'Date parameter is required'], 400);
            }

            $startOfDay = Carbon::parse($date)->startOfDay();
            $endOfDay = Carbon::parse($date)->endOfDay();

            // Get team member IDs
            $teamMemberIds = User::where('parent_id', $user->id)->pluck('id')->toArray();

            // Get follow-up leads (operation leads with status 'follow up' based on date_time field)
            $followUpLeads = OperationLead::whereIn('executive', $teamMemberIds)
                ->where('status', 'follow up')
                ->whereBetween('date_time', [$startOfDay, $endOfDay])
                ->select('id', 'lead_id', 'customer_name', 'contact_no', 'location', 'query', 'status', 'date_time')
                ->get();

            return response()->json([
                'leads' => $followUpLeads,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch leads for date: ' . $e->getMessage()], 500);
        }
    }

    private function calculateTotalOutstandingAmount($user)
    {
        try {
            // Get team member IDs
            $teamMemberIds = User::where('parent_id', $user->id)->pluck('id')->toArray();

            // Get operation lead IDs for the team
            $leadIds = OperationLead::whereIn('executive', $teamMemberIds)->pluck('id');

            if ($leadIds->isEmpty()) {
                return 0;
            }

            // Get total outstanding amount from payment invoices for team leads
            $totalInvoiced = PaymentInvoice::whereIn('operation_lead_id', $leadIds)
                ->sum('payment_amount');
            
            $totalReceived = ReceivedPayment::whereIn('operation_lead_id', $leadIds)
                ->sum('amount');
            
            $totalOutstanding = $totalInvoiced - $totalReceived;

            \Log::info('Operation Manager Total outstanding amount calculated:', ['amount' => $totalOutstanding, 'user_id' => $user->id]);

            return $totalOutstanding;
        } catch (\Exception $e) {
            \Log::error('Error calculating operation manager total outstanding amount: ' . $e->getMessage());
            return 0;
        }
    }

    public function getOutstandingAmountStats(Request $request)
    {
        try {
            $user = Auth::user();
            $period = $request->get('period', 'all');
            $currentDate = Carbon::now();

            // Get team member IDs
            $teamMemberIds = User::where('parent_id', $user->id)->pluck('id')->toArray();

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

            // Get outstanding amount details for team leads using payment invoices
            $query = PaymentInvoice::select(
                'payment_invoices.*',
                'operation_leads.lead_id',
                'operation_leads.customer_name',
                'operation_leads.contact_no',
                'operation_leads.created_at as lead_created_at',
                'users.f_name as executive_name',
                DB::raw('(SELECT COALESCE(SUM(amount), 0) FROM received_payments WHERE payment_invoice_id = payment_invoices.id) as total_received'),
                DB::raw('(payment_invoices.payment_amount - (SELECT COALESCE(SUM(amount), 0) FROM received_payments WHERE payment_invoice_id = payment_invoices.id)) as outstanding_payment')
            )
            ->leftJoin('operation_leads', 'payment_invoices.operation_lead_id', '=', 'operation_leads.id')
            ->leftJoin('users', 'operation_leads.executive', '=', 'users.id')
            ->whereIn('operation_leads.executive', $teamMemberIds)
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
            \Log::error('Error getting operation manager outstanding amount stats: ' . $e->getMessage());
            return response()->json([
                'error' => 'Failed to load outstanding amount data'
            ], 500);
        }
    }

    private function calculateDeploymentPendingCount($user)
    {
        try {
            // Get team member IDs
            $teamMemberIds = User::where('parent_id', $user->id)->pluck('id')->toArray();

            // Get operation lead IDs for the team
            $leadIds = OperationLead::whereIn('executive', $teamMemberIds)->pluck('id');

            if ($leadIds->isEmpty()) {
                return 0;
            }

            // Get count of deployment details with pending status for team leads
            $deploymentPendingCount = OperationDeploymentDetails::whereIn('operation_lead_id', $leadIds)
                ->where('deployment_status', 'pending')
                ->count();

            \Log::info('Operation Manager Deployment pending count calculated:', ['count' => $deploymentPendingCount, 'user_id' => $user->id]);

            return $deploymentPendingCount;
        } catch (\Exception $e) {
            \Log::error('Error calculating operation manager deployment pending count: ' . $e->getMessage());
            return 0;
        }
    }

    public function getDeploymentPendingStats(Request $request)
    {
        try {
            $user = Auth::user();

            // Get team member IDs
            $teamMemberIds = User::where('parent_id', $user->id)->pluck('id')->toArray();

            // Get deployment pending details for team leads (all leads, no date filtering)
            $deploymentPendingDetails = OperationDeploymentDetails::select(
                'operation_deployment_details.*',
                'operation_leads.lead_id',
                'operation_leads.customer_name',
                'operation_leads.contact_no',
                'operation_leads.created_at as lead_created_at',
                'users.f_name as executive_name',
                'vendors.name as vendor_name',
                'vendors.contact_no as vendor_contact_no',
                'job_requests.contact_no as freelance_contact_no',
                'job_requests.name as freelance_name'
            )
            ->leftJoin('operation_leads', 'operation_deployment_details.operation_lead_id', '=', 'operation_leads.id')
            ->leftJoin('users', 'operation_leads.executive', '=', 'users.id')
            ->leftJoin('vendors', 'operation_deployment_details.vendor_id', '=', 'vendors.id')
            ->leftJoin('job_requests', 'operation_deployment_details.freelance_staff_id', '=', 'job_requests.id')
            ->whereIn('operation_leads.executive', $teamMemberIds)
            ->where('operation_deployment_details.deployment_status', 'pending')
            ->orderBy('operation_deployment_details.created_at', 'desc')
            ->get()
            ->map(function($detail) {
                // Determine vendor number - prioritize freelance contact, then vendor contact
                $vendorNumber = $detail->freelance_contact_no ?: ($detail->vendor_contact_no ?: null);
                
                // Update vendor name to include (Freelance) if it's a freelance staff
                $vendorName = $detail->vendor_name;
                if ($detail->freelance_name) {
                    $vendorName = $detail->freelance_name . ' (Freelance)';
                } elseif ($detail->vendor_name) {
                    $vendorName = $detail->vendor_name;
                } else {
                    $vendorName = 'N/A';
                }

                return [
                    'operation_lead_id' => $detail->operation_lead_id,
                    'lead_id' => $detail->lead_id,
                    'customer_name' => $detail->customer_name,
                    'contact_no' => $detail->contact_no,
                    'executive_name' => $detail->executive_name,
                    'vendor_name' => $vendorName,
                    'vendor_contact_no' => $vendorNumber,
                    'staff_name' => $detail->staff_name,
                    'staff_number' => $detail->staff_number,
                    'deployment_date' => $detail->deployment_date,
                    'created_at' => $detail->created_at
                ];
            });

            // Calculate summary statistics (only total count)
            $totalDeploymentPending = $deploymentPendingDetails->count();

            return response()->json([
                'total_deployment_pending' => $totalDeploymentPending,
                'count' => $deploymentPendingDetails->count(),
                'deployment_pending_details' => $deploymentPendingDetails
            ]);

        } catch (\Exception $e) {
            \Log::error('Error getting operation manager deployment pending stats: ' . $e->getMessage());
            return response()->json([
                'error' => 'Failed to load deployment pending data'
            ], 500);
        }
    }

    private function calculateProfilePendingCount($user)
    {
        try {
            // Get team member IDs
            $teamMemberIds = User::where('parent_id', $user->id)->pluck('id')->toArray();

            // Get count of operation leads with profile pending status for team leads
            $profilePendingCount = OperationLead::whereIn('executive', $teamMemberIds)
                ->where('status', 'profile pending')
                ->count();

            \Log::info('Operation Manager Profile pending count calculated:', ['count' => $profilePendingCount, 'user_id' => $user->id]);

            return $profilePendingCount;
        } catch (\Exception $e) {
            \Log::error('Error calculating operation manager profile pending count: ' . $e->getMessage());
            return 0;
        }
    }

    public function getProfilePendingStats(Request $request)
    {
        try {
            $user = Auth::user();

            // Get team member IDs
            $teamMemberIds = User::where('parent_id', $user->id)->pluck('id')->toArray();

            // Get profile pending leads for team members (all leads, no date filtering)
            $profilePendingLeads = OperationLead::select(
                'operation_leads.*',
                'users.f_name as executive_name'
            )
            ->leftJoin('users', 'operation_leads.executive', '=', 'users.id')
            ->whereIn('operation_leads.executive', $teamMemberIds)
            ->where('operation_leads.status', 'profile pending')
            ->orderBy('operation_leads.created_at', 'desc')
            ->get();

            // Calculate summary statistics (only total count)
            $totalProfilePending = $profilePendingLeads->count();

            return response()->json([
                'total_profile_pending' => $totalProfilePending,
                'count' => $profilePendingLeads->count(),
                'profile_pending_leads' => $profilePendingLeads
            ]);

        } catch (\Exception $e) {
            \Log::error('Error getting operation manager profile pending stats: ' . $e->getMessage());
            return response()->json([
                'error' => 'Failed to load profile pending data'
            ], 500);
        }
    }

    private function calculateOngoingCount($user)
    {
        try {
            // Get team member IDs
            $teamMemberIds = User::where('parent_id', $user->id)->pluck('id')->toArray();

            // Get count of operation leads with ongoing status for team leads
            $ongoingCount = OperationLead::whereIn('executive', $teamMemberIds)
                ->where('ongoing_stopped', 'ongoing')
                ->count();

            \Log::info('Operation Manager Ongoing count calculated:', ['count' => $ongoingCount, 'user_id' => $user->id]);

            return $ongoingCount;
        } catch (\Exception $e) {
            \Log::error('Error calculating operation manager ongoing count: ' . $e->getMessage());
            return 0;
        }
    }

    public function getOngoingStats(Request $request)
    {
        try {
            $user = Auth::user();
            $period = $request->get('period', 'all');
            $currentDate = Carbon::now();

            // Get team member IDs
            $teamMemberIds = User::where('parent_id', $user->id)->pluck('id')->toArray();

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

            // Get ongoing leads for team members
            $query = OperationLead::select(
                'operation_leads.*',
                'users.f_name as executive_name'
            )
            ->leftJoin('users', 'operation_leads.executive', '=', 'users.id')
            ->whereIn('operation_leads.executive', $teamMemberIds)
            ->where('operation_leads.ongoing_stopped', 'ongoing');

            if ($startDate && $endDate) {
                $query->whereBetween('operation_leads.created_at', [$startDate, $endDate]);
            }

            $ongoingLeads = $query->orderBy('operation_leads.created_at', 'desc')->get();

            // Calculate summary statistics
            $totalOngoing = OperationLead::whereIn('executive', $teamMemberIds)
                ->where('ongoing_stopped', 'ongoing')
                ->count();

            $todayOngoing = OperationLead::whereIn('executive', $teamMemberIds)
                ->where('ongoing_stopped', 'ongoing')
                ->whereDate('created_at', $currentDate->toDateString())
                ->count();

            $monthOngoing = OperationLead::whereIn('executive', $teamMemberIds)
                ->where('ongoing_stopped', 'ongoing')
                ->whereMonth('created_at', $currentDate->month)
                ->whereYear('created_at', $currentDate->year)
                ->count();

            $yearOngoing = OperationLead::whereIn('executive', $teamMemberIds)
                ->where('ongoing_stopped', 'ongoing')
                ->whereYear('created_at', $currentDate->year)
                ->count();

            return response()->json([
                'total_ongoing' => $totalOngoing,
                'today_ongoing' => $todayOngoing,
                'month_ongoing' => $monthOngoing,
                'year_ongoing' => $yearOngoing,
                'count' => $ongoingLeads->count(),
                'ongoing_leads' => $ongoingLeads
            ]);

        } catch (\Exception $e) {
            \Log::error('Error getting operation manager ongoing stats: ' . $e->getMessage());
            return response()->json([
                'error' => 'Failed to load ongoing data'
            ], 500);
        }
    }

    private function calculateUnverifiedPaymentsCount($user)
    {
        try {
            // Get team member IDs
            $teamMemberIds = User::where('parent_id', $user->id)->pluck('id')->toArray();

            // Get operation lead IDs for the team
            $leadIds = OperationLead::whereIn('executive', $teamMemberIds)->pluck('id');

            if ($leadIds->isEmpty()) {
                return 0;
            }

            // Get count of unverified payments for team leads
            $unverifiedPayments = OperationDeploymentDetails::whereIn('operation_lead_id', $leadIds)
                ->where('verify_payment', false)
                ->whereNotNull('vendor_payment')
                ->where('vendor_payment', '>', 0)
                ->count();

            \Log::info('Operation Manager Unverified payments count calculated:', ['count' => $unverifiedPayments, 'user_id' => $user->id]);

            return $unverifiedPayments;
        } catch (\Exception $e) {
            \Log::error('Error calculating operation manager unverified payments count: ' . $e->getMessage());
            return 0;
        }
    }

    public function getPaymentDueStats(Request $request)
    {
        try {
            $user = Auth::user();

            // Get team member IDs
            $teamMemberIds = User::where('parent_id', $user->id)->pluck('id')->toArray();

            // Get operation lead IDs for the team
            $leadIds = OperationLead::whereIn('executive', $teamMemberIds)->pluck('id');

            if ($leadIds->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'total_count' => 0,
                        'total_amount' => 0,
                        'chart_data' => [],
                        'payments' => []
                    ]
                ]);
            }

            // Get all unverified payments for the team (no date filtering)
            $payments = OperationDeploymentDetails::whereIn('operation_lead_id', $leadIds)
                ->where('verify_payment', false)
                ->whereNotNull('vendor_payment')
                ->where('vendor_payment', '>', 0)
                ->join('operation_leads', 'operation_deployment_details.operation_lead_id', '=', 'operation_leads.id')
                ->leftJoin('vendors', 'operation_deployment_details.vendor_id', '=', 'vendors.id')
                ->select(
                    'operation_deployment_details.id',
                    'operation_deployment_details.operation_lead_id',
                    'operation_deployment_details.staff_name',
                    'operation_deployment_details.vendor_payment',
                    'operation_deployment_details.deployment_date',
                    'operation_deployment_details.created_at',
                    'operation_leads.lead_id',
                    'operation_leads.customer_name',
                    'vendors.name as vendor_name'
                )
                ->orderBy('operation_deployment_details.created_at', 'desc')
                ->get()
                ->map(function($payment) {
                    $viewUrl = null;
                    if ($payment->operation_lead_id) {
                        try {
                            $viewUrl = route('operation-manager.operation_leads.show', $payment->operation_lead_id);
                        } catch (\Exception $e) {
                            $viewUrl = '#';
                        }
                    } else {
                        $viewUrl = '#';
                    }

                    return [
                        'id' => $payment->id,
                        'lead_id' => $payment->lead_id ?: ('#' . $payment->operation_lead_id),
                        'customer_name' => $payment->customer_name ?: 'N/A',
                        'vendor_name' => $payment->vendor_name ?: 'N/A',
                        'staff_name' => $payment->staff_name ?: 'N/A',
                        'vendor_payment' => $payment->vendor_payment ?: 0,
                        'deployment_date' => $payment->deployment_date ? Carbon::parse($payment->deployment_date)->format('M d, Y') : 'N/A',
                        'created_at' => Carbon::parse($payment->created_at)->format('M d, Y H:i'),
                        'view_url' => $viewUrl
                    ];
                });

            // Calculate totals
            $totalCount = $payments->count();
            $totalAmount = $payments->sum('vendor_payment');

            return response()->json([
                'success' => true,
                'data' => [
                    'total_count' => $totalCount,
                    'total_amount' => $totalAmount,
                    'chart_data' => [],
                    'payments' => $payments
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Error getting operation manager payment due stats: ' . $e->getMessage());
            \Log::error('Operation manager payment due stats error trace: ' . $e->getTraceAsString());
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving payment due statistics: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getJobRequestStats(Request $request)
    {
        try {
            // Get all job requests with executive information (excluding inactive, active, and blacklisted)
            $jobRequests = JobRequest::with('executive')
                ->whereNotIn('status', ['inactive', 'active', 'blacklisted'])
                ->orderBy('created_at', 'desc')
                ->get();

            // Calculate summary statistics
            $totalJobRequests = $jobRequests->count();
            $statusCounts = $jobRequests->groupBy('status')->map->count();

            return response()->json([
                'success' => true,
                'data' => [
                    'total_job_requests' => $totalJobRequests,
                    'status_counts' => $statusCounts,
                    'job_requests' => $jobRequests->map(function($jobRequest) {
                        return [
                            'id' => $jobRequest->id,
                            'lead_id' => $jobRequest->lead_id,
                            'customer_name' => $jobRequest->customer_name,
                            'contact_no' => $jobRequest->contact_no,
                            'name' => $jobRequest->name,
                            'city' => $jobRequest->city,
                            'status' => $jobRequest->status,
                            'date_time' => $jobRequest->date_time,
                            'executive_name' => $jobRequest->executive ? ($jobRequest->executive->f_name . ' ' . $jobRequest->executive->l_name) : 'N/A',
                            'view_url' => route('operation.jobproc.show', $jobRequest->id)
                        ];
                    })
                ]
            ]);
        } catch (\Exception $e) {
            \Log::error('Error getting operation manager job request stats: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to load job request data'
            ], 500);
        }
    }

    public function getUnverifiedDeploymentPaymentsStats(Request $request)
    {
        try {
            $user = Auth::user();

            // Get team member IDs
            $teamMemberIds = User::where('parent_id', $user->id)->pluck('id')->toArray();

            // Get unverified deployment payments for team's leads
            $unverifiedDeployments = OperationDeploymentDetails::with([
                'operationLead' => function($query) {
                    $query->select('id', 'lead_id', 'customer_name', 'contact_no', 'location', 'executive');
                },
                'vendor',
                'freelanceStaff'
            ])
            ->whereHas('operationLead', function($query) use ($teamMemberIds) {
                $query->whereIn('executive', $teamMemberIds);
            })
            ->where('verify_payment', false)
            ->whereNotNull('vendor_payment')
            ->where('vendor_payment', '>', 0)
            ->orderBy('deployment_date', 'desc')
            ->get();

            // Format the data
            $deployments = $unverifiedDeployments->map(function($deployment) {
                $vendorName = '';
                if ($deployment->freelance_staff_id && $deployment->freelanceStaff) {
                    $vendorName = $deployment->freelanceStaff->name . ' (Freelance)';
                } elseif ($deployment->vendor) {
                    $vendorName = $deployment->vendor->name;
                }

                return [
                    'id' => $deployment->id,
                    'operation_lead_id' => $deployment->operation_lead_id,
                    'lead_id' => $deployment->operationLead->lead_id ?? '#' . $deployment->operation_lead_id,
                    'customer_name' => $deployment->operationLead->customer_name ?? 'N/A',
                    'contact_no' => $deployment->operationLead->contact_no ?? 'N/A',
                    'location' => $deployment->operationLead->location ?? 'N/A',
                    'vendor_name' => $vendorName ?: 'N/A',
                    'staff_name' => $deployment->staff_name ?? 'N/A',
                    'staff_number' => $deployment->staff_number ?? 'N/A',
                    'payment_term' => $deployment->payment_term ?? 'N/A',
                    'vendor_payment' => $deployment->vendor_payment,
                    'deployment_date' => $deployment->deployment_date ? date('d-M-Y H:i', strtotime($deployment->deployment_date)) : 'N/A',
                    'deployment_from_date' => $deployment->deployment_from_date ? date('d-M-Y H:i', strtotime($deployment->deployment_from_date)) : 'N/A',
                    'deployment_to_date' => $deployment->deployment_to_date ? date('d-M-Y H:i', strtotime($deployment->deployment_to_date)) : 'N/A',
                    'deployment_status' => $deployment->deployment_status ?? 'N/A',
                    'view_url' => route('operation-manager.operation_leads.show', $deployment->operation_lead_id),
                ];
            });

            $totalCount = $deployments->count();
            $totalAmount = $unverifiedDeployments->sum('vendor_payment');

            return response()->json([
                'success' => true,
                'data' => [
                    'total_count' => $totalCount,
                    'total_amount' => $totalAmount,
                    'deployments' => $deployments
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch unverified deployment payments: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getVendorFreelancerPaymentStats(Request $request)
    {
        try {
            $user = Auth::user();

            // Get unverified vendor/freelancer payments with related data
            $unverifiedPayments = OperationDeploymentDetails::with([
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
            $totalUnverified = $unverifiedPayments->count();
            $totalAmount = $unverifiedPayments->sum('vendor_payment');

            return response()->json([
                'success' => true,
                'data' => [
                    'total_unverified' => $totalUnverified,
                    'total_amount' => $totalAmount,
                    'payments' => $unverifiedPayments->map(function($payment) {
                        $lead = $payment->operationLead;
                        $vendorStaff = 'N/A';
                        
                        if ($payment->freelanceStaff) {
                            $vendorStaff = $payment->freelanceStaff->name . ' (Freelance)';
                        } elseif ($payment->vendor) {
                            $vendorStaff = $payment->vendor->name . ' (Vendor)';
                        }

                        return [
                            'id' => $payment->id,
                            'operation_lead_id' => $lead ? $lead->id : null,
                            'lead_id' => $lead ? ($lead->lead_id ?: ('#' . $lead->id)) : 'N/A',
                            'customer_name' => $lead ? $lead->customer_name : 'N/A',
                            'contact_no' => $lead ? $lead->contact_no : 'N/A',
                            'location' => $lead ? $lead->location : 'N/A',
                            'vendor_staff' => $vendorStaff,
                            'vendor_payment' => $payment->vendor_payment,
                            'deployment_date' => $payment->deployment_date ? \Carbon\Carbon::parse($payment->deployment_date)->format('d/m/Y') : 'N/A',
                            'created_at' => $payment->created_at ? \Carbon\Carbon::parse($payment->created_at)->format('d/m/Y H:i') : 'N/A',
                            'view_url' => $lead ? route('operation-manager.operation_leads.show', $lead->id) : '#'
                        ];
                    })
                ]
            ]);
        } catch (\Exception $e) {
            \Log::error('Error getting vendor/freelancer payment stats: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to load vendor/freelancer payment data'
            ], 500);
        }
    }

    public function getRecentLeadsStats(Request $request)
    {
        try {
            $user = Auth::user();
            
            // Get team member IDs
            $teamMemberIds = User::where('parent_id', $user->id)->pluck('id')->toArray();
            
            // Get 25 most recent leads based on created_at (when they were added to the system)
            // Using join to get executive name directly
            $recentLeads = OperationLead::leftJoin('users', 'operation_leads.executive', '=', 'users.id')
                ->whereIn('operation_leads.executive', $teamMemberIds)
                ->select(
                    'operation_leads.id',
                    'operation_leads.lead_id',
                    'operation_leads.date_time',
                    'operation_leads.customer_name',
                    'operation_leads.contact_no',
                    'operation_leads.location',
                    'operation_leads.query',
                    'operation_leads.status',
                    \DB::raw("CONCAT(users.f_name, ' ', users.l_name) as executive_name")
                )
                ->orderBy('operation_leads.created_at', 'desc')
                ->limit(25)
                ->get();

            // Get the latest lead date
            $latestLeadDate = OperationLead::whereIn('executive', $teamMemberIds)
                ->orderBy('created_at', 'desc')
                ->value('created_at');

            return response()->json([
                'success' => true,
                'data' => [
                    'total_count' => $recentLeads->count(),
                    'latest_lead_date' => $latestLeadDate,
                    'leads' => $recentLeads,
                ],
            ]);
        } catch (\Exception $e) {
            \Log::error('Error getting recent leads (OM): ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch recent leads statistics: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function getPendingCallbacks(Request $request)
    {
        try {
            $user = Auth::user();
            
            // Get team member IDs
            $teamMemberIds = User::where('parent_id', $user->id)->pluck('id')->toArray();
            
            // Get call logs with non-answered statuses for team members
            // Only include calls for OperationLead and JobRequest (not regular Leads)
            // Show all non-answered calls, and also show answered ones with response_datetime
            $callLogs = CallDetails::whereIn('executive_id', $teamMemberIds)
                ->whereIn('call_for', ['operation_lead', 'job_request']) // Only OperationLead and JobRequest calls
                ->whereNotIn('call_status', ['answered', 'answer']) // Get all non-answered calls
                ->orderBy('call_received_datetime', 'desc')
                ->get();
            
            $pendingCallbacks = [];
            $processedNumbers = []; // Track processed normalized numbers to avoid duplicates
            
            foreach ($callLogs as $callLog) {
                $contactNumber = $callLog->caller_id_number;
                $normalizedNumber = $this->normalizePhoneNumber($contactNumber);
                
                // Skip if we've already processed this normalized number (show only most recent call per number)
                if (in_array($normalizedNumber, $processedNumbers)) {
                    continue;
                }
                
                $processedNumbers[] = $normalizedNumber;
                
                // Try to find associated operation lead or job request by contact number
                $lead = null;
                $jobRequest = null;
                $leadCode = 'No Lead';
                $customerName = 'N/A';
                $viewUrl = null;
                $relatedId = null;
                
                // Check call_for to determine what to find
                if ($callLog->call_for === 'operation_lead') {
                    $lead = OperationLead::where(function($query) use ($contactNumber, $normalizedNumber, $teamMemberIds) {
                        $query->where('contact_no', $contactNumber)
                              ->orWhere('contact_no', $normalizedNumber)
                              ->orWhere('contact_no', '91' . $normalizedNumber)
                              ->orWhere('contact_no', '0' . $normalizedNumber);
                    })
                        ->whereIn('executive', $teamMemberIds)
                        ->orderBy('created_at', 'desc')
                        ->first();
                    if ($lead) {
                        $leadCode = $lead->lead_id ?: 'OP' . str_pad($lead->id, 8, '0', STR_PAD_LEFT);
                        $customerName = $lead->customer_name ?: 'N/A';
                        $viewUrl = route('operation-manager.operation_leads.show', $lead->id);
                        $relatedId = $lead->id;
                    }
                } elseif ($callLog->call_for === 'job_request') {
                    $jobRequest = JobRequest::where(function($query) use ($contactNumber, $normalizedNumber, $teamMemberIds) {
                        $query->where('contact_no', $contactNumber)
                              ->orWhere('contact_no', $normalizedNumber)
                              ->orWhere('contact_no', '91' . $normalizedNumber)
                              ->orWhere('contact_no', '0' . $normalizedNumber);
                    })
                        ->whereIn('executive_id', $teamMemberIds)
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
                
                // Check if there was a successful callback after this missed call
                // Don't filter by executive_id - any successful callback should mark it as responded
                $successfulCallback = CallDetails::where(function($query) use ($contactNumber, $normalizedNumber) {
                    $query->where('caller_id_number', $contactNumber)
                          ->orWhere('caller_id_number', $normalizedNumber)
                          ->orWhere('caller_id_number', '91' . $normalizedNumber)
                          ->orWhere('caller_id_number', '0' . $normalizedNumber);
                })
                    ->whereIn('call_for', ['operation_lead', 'job_request']) // Only OperationLead and JobRequest calls
                    ->where('call_received_datetime', '>', $callLog->call_received_datetime)
                    ->whereIn('call_status', ['answered', 'answer'])
                    ->orderBy('call_received_datetime', 'asc')
                    ->first();
                
                // Get the response time if there was a successful callback
                $responseDateTime = null;
                if ($successfulCallback) {
                    $responseDateTime = $successfulCallback->call_received_datetime ? 
                        $successfulCallback->call_received_datetime->format('d-M-Y H:i:s') : 'N/A';
                }

                // Don't skip - show all callbacks with response_datetime if answered
                
                // Get executive name
                $executiveName = 'N/A';
                if ($lead) {
                    $executiveName = $lead->executive_name ?? 'N/A';
                } elseif ($jobRequest && $jobRequest->executive_id) {
                    $executive = User::find($jobRequest->executive_id);
                    $executiveName = $executive ? ($executive->f_name . ' ' . $executive->l_name) : 'N/A';
                }
                
                $pendingCallbacks[] = [
                    'lead_no' => $leadCode,
                    'operation_lead_id' => $relatedId,
                    'unanswered_datetime' => $callLog->call_received_datetime ? $callLog->call_received_datetime->format('d-M-Y H:i:s') : 'N/A',
                    'customer_name' => $customerName,
                    'customer_number' => $contactNumber,
                    'lead_source' => 'Incoming Call',
                    'last_call_status' => $callLog->call_status,
                    'call_for' => $callLog->call_for ?? 'N/A', // Show call type
                    'call_for_display' => $callLog->call_for === 'operation_lead' ? 'Operation Lead' : ($callLog->call_for === 'job_request' ? 'Job Request' : 'N/A'),
                    'call_log_id' => $callLog->id,
                    'call_response_datetime' => $responseDateTime,
                    'executive_name' => $executiveName,
                    'view_url' => $viewUrl
                ];
            }
            
            return response()->json([
                'success' => true,
                'total_count' => count($pendingCallbacks),
                'callbacks' => $pendingCallbacks
            ]);
        } catch (\Exception $e) {
            \Log::error('Error getting pending callbacks (Operation Manager): ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Failed to load pending callbacks data: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getRecentCalls(Request $request)
    {
        try {
            $user = Auth::user();
            
            // Get team member IDs
            $teamMemberIds = User::where('parent_id', $user->id)->pluck('id')->toArray();
            
            // Get call logs from the past week with any status, including executive information
            $oneWeekAgo = now()->subWeek();
            
            // Only include calls for OperationLead and JobRequest (not regular Leads)
            $callLogs = CallDetails::with(['executive:id,f_name,l_name'])
                ->whereIn('executive_id', $teamMemberIds)
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
                    $lead = OperationLead::where(function($query) use ($contactNumber, $normalizedNumber, $teamMemberIds) {
                        $query->where('contact_no', $contactNumber)
                              ->orWhere('contact_no', $normalizedNumber)
                              ->orWhere('contact_no', '91' . $normalizedNumber)
                              ->orWhere('contact_no', '0' . $normalizedNumber);
                    })
                        ->whereIn('executive', $teamMemberIds)
                        ->orderBy('created_at', 'desc')
                        ->first();
                    if ($lead) {
                        $leadCode = $lead->lead_id ?: 'OP' . str_pad($lead->id, 8, '0', STR_PAD_LEFT);
                        $customerName = $lead->customer_name ?: 'N/A';
                        $viewUrl = route('operation-manager.operation_leads.show', $lead->id);
                        $relatedId = $lead->id;
                    }
                } elseif ($callLog->call_for === 'job_request') {
                    $jobRequest = JobRequest::where(function($query) use ($contactNumber, $normalizedNumber, $teamMemberIds) {
                        $query->where('contact_no', $contactNumber)
                              ->orWhere('contact_no', $normalizedNumber)
                              ->orWhere('contact_no', '91' . $normalizedNumber)
                              ->orWhere('contact_no', '0' . $normalizedNumber);
                    })
                        ->whereIn('executive_id', $teamMemberIds)
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
                    'operation_lead_id' => $relatedId,
                    'call_datetime' => $callLog->call_received_datetime ? $callLog->call_received_datetime->format('d-M-Y H:i:s') : 'N/A',
                    'executive_name' => $executiveName,
                    'customer_name' => $customerName,
                    'customer_number' => $contactNumber,
                    'call_status' => $callLog->call_status,
                    'call_for' => $callLog->call_for ?? 'N/A', // Show call type
                    'call_for_display' => $callLog->call_for === 'operation_lead' ? 'Operation Lead' : ($callLog->call_for === 'job_request' ? 'Job Request' : 'N/A'),
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
            \Log::error('Error getting recent calls (Operation Manager): ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Failed to load recent calls data: ' . $e->getMessage()
            ], 500);
        }
    }
}
