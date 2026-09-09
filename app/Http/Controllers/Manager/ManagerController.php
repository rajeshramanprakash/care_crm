<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\CallDetails;
use App\Models\Lead;
use App\Models\Location;
use App\Models\Service;
use App\Models\Task;
use App\Models\User;
use App\Support\CallDirectionLabel;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ManagerController extends Controller
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

    public function dashboard(Request $request)
    {
        $page_heading = 'Manager Dashboard';

        // Get today's leads count for the logged-in manager's team
        $managerId = auth()->user()->id;
        $teamMemberIds = \App\Models\User::where('parent_id', $managerId)->pluck('id')->toArray();
        // Note: Manager themselves don't have leads, only their team members do

        $todayLeads = Lead::whereIn('executive', $teamMemberIds)
            ->whereDate('created_at', Carbon::today())
            ->count();

        // Get team members data with location names
        $teamMembers = \App\Models\User::where('parent_id', $managerId)->get();

        // Add location names to each team member
        foreach ($teamMembers as $member) {
            if ($member->location_id) {
                $locationIds = is_array($member->location_id) ? $member->location_id : explode(',', $member->location_id);
                $locations = Location::whereIn('id', $locationIds)->pluck('name')->toArray();
                $member->location_names = implode(', ', $locations);
            } else {
                $member->location_names = null;
            }
        }

        $activeUsers = $teamMembers->where('status', 1)->count();
        $inactiveUsers = $teamMembers->where('status', 0)->count();

        // Get task statistics including parent manager assigned tasks
        $managerId = auth()->user()->id;
        $teamMemberIds = \App\Models\User::where('parent_id', $managerId)->pluck('id')->toArray();
        $allUserIds = array_merge([$managerId], $teamMemberIds); // Include manager themselves

        // Get parent manager ID
        $parentManagerId = \App\Models\User::where('id', $managerId)->value('parent_id');

        $totalTasks = Task::where(function($q) use ($allUserIds, $parentManagerId) {
            $q->whereIn('assigned_to', $allUserIds)
              ->orWhereIn('assigned_by', $allUserIds);

            // Also include tasks assigned by parent manager to team members
            if ($parentManagerId) {
                $q->orWhere(function($subQ) use ($parentManagerId, $allUserIds) {
                    $subQ->where('assigned_by', $parentManagerId)
                         ->whereIn('assigned_to', $allUserIds);
                });
            }
        })->count();

        // Return JSON for API requests
        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'todayLeads' => $todayLeads,
                'activeUsers' => $activeUsers,
                'inactiveUsers' => $inactiveUsers,
                'totalUsers' => $activeUsers + $inactiveUsers,
                'totalTasks' => $totalTasks,
                'teamMembers' => $teamMembers->map(function($member) {
                    // Handle role - it might be a string or a relationship object
                    $roleValue = $member->role;
                    if (is_object($roleValue) && isset($roleValue->name)) {
                        $roleValue = $roleValue->name;
                    } elseif (is_array($roleValue) && isset($roleValue['name'])) {
                        $roleValue = $roleValue['name'];
                    }
                    
                    return [
                        'id' => $member->id,
                        'f_name' => $member->f_name,
                        'l_name' => $member->l_name,
                        'email' => $member->email,
                        'mobile' => $member->mobile,
                        'role' => $roleValue ?? 'N/A',
                        'status' => $member->status,
                        'location_names' => $member->location_names,
                    ];
                }),
            ]);
        }

        $locations = Location::get();
        $services = Service::get();
        return view('manager.dashboard', compact('page_heading', 'todayLeads', 'teamMembers', 'activeUsers', 'inactiveUsers', 'totalTasks', 'locations', 'services'));
    }

    public function getLeadsStats(Request $request)
    {
        try {
            $period = $request->get('period', 'today');
            $startDate = $request->get('start_date');
            $endDate = $request->get('end_date');
            $selectedMonth = $request->get('selected_month');
            $selectedYear = $request->get('selected_year');

            // Get team member IDs (only team members, not manager themselves)
            $managerId = auth()->user()->id;
            $teamMemberIds = \App\Models\User::where('parent_id', $managerId)->pluck('id')->toArray();

            // If no team members, return empty results
            if (empty($teamMemberIds)) {
                return response()->json([
                    'stats' => (object)[
                        'total_leads' => 0,
                        'follow_up' => 0,
                        'future_prospect' => 0,
                        'prospect' => 0,
                        'no_response' => 0,
                        'price_issue' => 0,
                        'duplicate' => 0,
                        'spam' => 0
                    ],
                    'leads' => [],
                    'period' => $period,
                    'start_date' => Carbon::today()->format('Y-m-d'),
                    'end_date' => Carbon::today()->format('Y-m-d')
                ]);
            }

            // Filter by team members
            $baseQuery = Lead::whereIn('leads.executive', $teamMemberIds);

            // Set date range based on period
            switch ($period) {
                case 'today':
                    $startDate = Carbon::today();
                    $endDate = Carbon::today()->endOfDay();
                    break;
                case 'monthly':
                    if ($selectedMonth && $selectedYear) {
                        $startDate = Carbon::create($selectedYear, $selectedMonth, 1)->startOfMonth();
                        $endDate = Carbon::create($selectedYear, $selectedMonth, 1)->endOfMonth();
                    } else {
                        $startDate = Carbon::now()->startOfMonth();
                        $endDate = Carbon::now()->endOfMonth();
                    }
                    break;
                case 'yearly':
                    if ($selectedYear) {
                        $startDate = Carbon::create($selectedYear, 1, 1)->startOfYear();
                        $endDate = Carbon::create($selectedYear, 12, 31)->endOfYear();
                    } else {
                        $startDate = Carbon::now()->startOfYear();
                        $endDate = Carbon::now()->endOfYear();
                    }
                    break;
                case 'custom':
                    if ($startDate && $endDate) {
                        $startDate = Carbon::parse($startDate)->startOfDay();
                        $endDate = Carbon::parse($endDate)->endOfDay();
                    } else {
                        $startDate = Carbon::today();
                        $endDate = Carbon::today()->endOfDay();
                    }
                    break;
                default:
                    $startDate = Carbon::today();
                    $endDate = Carbon::today()->endOfDay();
            }

            // Get statistics
            $stats = $baseQuery->whereBetween('leads.created_at', [$startDate, $endDate])
                ->selectRaw('
                    COUNT(*) as total_leads,
                    SUM(CASE WHEN leads.status = "follow-up" THEN 1 ELSE 0 END) as follow_up,
                    SUM(CASE WHEN leads.status = "future prospect" THEN 1 ELSE 0 END) as future_prospect,
                    SUM(CASE WHEN leads.status = "prospect" THEN 1 ELSE 0 END) as prospect,
                    SUM(CASE WHEN leads.status = "no response" THEN 1 ELSE 0 END) as no_response,
                    SUM(CASE WHEN leads.status = "price issue" THEN 1 ELSE 0 END) as price_issue,
                    SUM(CASE WHEN leads.status = "duplicate" THEN 1 ELSE 0 END) as duplicate,
                    SUM(CASE WHEN leads.status = "spam" THEN 1 ELSE 0 END) as spam
                ')
                ->first();

            // Get leads data with executive information
            $leads = Lead::select([
                'leads.*',
                \DB::raw("CONCAT(users.f_name, ' ', COALESCE(users.l_name, '')) as executive_name")
            ])
            ->leftJoin('users', 'leads.executive', '=', 'users.id')
            ->whereIn('leads.executive', $teamMemberIds)
            ->whereBetween('leads.created_at', [$startDate, $endDate])
            ->orderBy('leads.created_at', 'desc')
            ->get();

            return response()->json([
                'stats' => $stats,
                'leads' => $leads,
                'period' => $period,
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d')
            ]);
        } catch (\Exception $e) {
                            return response()->json(['error' => 'Failed to fetch leads data: ' . $e->getMessage()], 500);
            }
        }

        public function getTasks(Request $request)
        {
            try {
                $tab = $request->get('tab', 'assigned-to-me');
                $filterDate = $request->get('filter_date');

                $managerId = auth()->user()->id;
                $teamMemberIds = \App\Models\User::where('parent_id', $managerId)->pluck('id')->toArray();
                $allUserIds = array_merge([$managerId], $teamMemberIds);

                // Get parent manager ID
                $parentManagerId = \App\Models\User::where('id', $managerId)->value('parent_id');

                $query = Task::with(['assignedTo', 'assignedBy']);

                // Apply tab filter
                switch ($tab) {
                    case 'assigned-to-me':
                        $query->where('assigned_to', $managerId);
                        break;
                    case 'assigned-by-me':
                        $query->where('assigned_by', $managerId);
                        break;
                    case 'parent-manager-tasks':
                        if ($parentManagerId) {
                            $query->where('assigned_by', $parentManagerId)
                                  ->whereIn('assigned_to', $allUserIds);
                        } else {
                            // If no parent manager, return empty result
                            $query->where('id', 0);
                        }
                        break;
                    case 'all-tasks':
                        $query->where(function($q) use ($managerId, $allUserIds, $parentManagerId) {
                            $q->whereIn('assigned_to', $allUserIds)
                              ->orWhereIn('assigned_by', $allUserIds);

                            // Also include tasks assigned by parent manager to team members
                            if ($parentManagerId) {
                                $q->orWhere(function($subQ) use ($parentManagerId, $allUserIds) {
                                    $subQ->where('assigned_by', $parentManagerId)
                                         ->whereIn('assigned_to', $allUserIds);
                                });
                            }
                        });
                        break;
                }

                // Apply date filter
                if ($filterDate) {
                    $query->whereDate('created_at', $filterDate);
                }

                $tasks = $query->orderBy('created_at', 'desc')->get();

                // Add user names to tasks
                $tasks->each(function($task) {
                    $task->assigned_to_name = $task->assignedTo ? $task->assignedTo->f_name . ' ' . $task->assignedTo->l_name : 'N/A';
                    $task->assigned_by_name = $task->assignedBy ? $task->assignedBy->f_name . ' ' . $task->assignedBy->l_name : 'N/A';
                });

                return response()->json([
                    'tasks' => $tasks,
                    'tab' => $tab
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
                    'assigned_to' => 'required|exists:users,id',
                    'priority' => 'required|in:low,medium,high',
                    'due_date' => 'required|date|after_or_equal:today',
                    'status' => 'required|in:pending,in_progress,completed'
                ]);

                $task = Task::create([
                    'title' => $request->title,
                    'description' => $request->description,
                    'assigned_to' => $request->assigned_to,
                    'assigned_by' => auth()->user()->id,
                    'priority' => $request->priority,
                    'due_date' => $request->due_date,
                    'status' => $request->status
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Task created successfully',
                    'task' => $task
                ]);

            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to create task: ' . $e->getMessage()
                ], 500);
            }
        }

        public function editTask($id)
        {
            try {
                $task = Task::with(['assignedTo', 'assignedBy'])->findOrFail($id);

                // Check if user has permission to edit this task
                $managerId = auth()->user()->id;
                $teamMemberIds = \App\Models\User::where('parent_id', $managerId)->pluck('id')->toArray();
                $allUserIds = array_merge([$managerId], $teamMemberIds);

                if (!in_array($task->assigned_to, $allUserIds) && !in_array($task->assigned_by, $allUserIds)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'You do not have permission to edit this task'
                    ], 403);
                }

                // Add user names to task
                $task->assigned_to_name = $task->assignedTo ? $task->assignedTo->f_name . ' ' . $task->assignedTo->l_name : 'N/A';
                $task->assigned_by_name = $task->assignedBy ? $task->assignedBy->f_name . ' ' . $task->assignedBy->l_name : 'N/A';

                return response()->json([
                    'success' => true,
                    'task' => $task
                ]);

            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to load task: ' . $e->getMessage()
                ], 500);
            }
        }

        public function updateTask(Request $request, $id)
        {
            try {
                $task = Task::findOrFail($id);

                // Check if user has permission to update this task
                $managerId = auth()->user()->id;
                $teamMemberIds = \App\Models\User::where('parent_id', $managerId)->pluck('id')->toArray();
                $allUserIds = array_merge([$managerId], $teamMemberIds);

                if (!in_array($task->assigned_to, $allUserIds) && !in_array($task->assigned_by, $allUserIds)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'You do not have permission to update this task'
                    ], 403);
                }

                $request->validate([
                    'title' => 'required|string|max:255',
                    'description' => 'nullable|string',
                    'assigned_to' => 'required|exists:users,id',
                    'priority' => 'required|in:low,medium,high',
                    'due_date' => 'required|date',
                    'status' => 'required|in:pending,in_progress,completed'
                ]);

                $task->update([
                    'title' => $request->title,
                    'description' => $request->description,
                    'assigned_to' => $request->assigned_to,
                    'priority' => $request->priority,
                    'due_date' => $request->due_date,
                    'status' => $request->status
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Task updated successfully'
                ]);

            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update task: ' . $e->getMessage()
                ], 500);
            }
        }

        public function updateTaskStatus(Request $request, $id)
        {
            try {
                $request->validate([
                    'status' => 'required|in:pending,in_progress,completed,cancelled'
                ]);

                $task = Task::findOrFail($id);
                $managerId = auth()->user()->id;
                $teamMemberIds = \App\Models\User::where('parent_id', $managerId)->pluck('id')->toArray();
                $allUserIds = array_merge([$managerId], $teamMemberIds);

                // Check if user has permission to update this task
                if (!in_array($task->assigned_to, $allUserIds) && !in_array($task->assigned_by, $allUserIds)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'You do not have permission to update this task'
                    ], 403);
                }

                $task->status = $request->status;
                if ($request->status === 'completed') {
                    $task->completed_at = now();
                } else {
                    $task->completed_at = null;
                }
                $task->save();

                return response()->json([
                    'success' => true,
                    'message' => 'Task status updated successfully',
                    'task' => $task
                ]);
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update task status: ' . $e->getMessage()
                ], 500);
            }
        }

        public function destroyTask($id)
        {
            try {
                $task = Task::findOrFail($id);

                // Check if user has permission to delete this task
                $managerId = auth()->user()->id;
                $teamMemberIds = \App\Models\User::where('parent_id', $managerId)->pluck('id')->toArray();
                $allUserIds = array_merge([$managerId], $teamMemberIds);

                if (!in_array($task->assigned_to, $allUserIds) && !in_array($task->assigned_by, $allUserIds)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'You do not have permission to delete this task'
                    ], 403);
                }

                $task->delete();

                return response()->json([
                    'success' => true,
                    'message' => 'Task deleted successfully'
                ]);

            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to delete task: ' . $e->getMessage()
                ], 500);
            }
    }

    public function getCalendarData(Request $request)
    {
        try {
            $year = $request->get('year', date('Y'));
            $month = $request->get('month', date('n'));

            // Team member IDs for this manager
            $managerId = auth()->user()->id;
            $teamMemberIds = \App\Models\User::where('parent_id', $managerId)->pluck('id')->toArray();

            if (empty($teamMemberIds)) {
                return response()->json(['calendar_data' => []]);
            }

            $startDate = Carbon::create($year, $month, 1)->startOfMonth();
            $endDate = Carbon::create($year, $month, 1)->endOfMonth();

            $calendarData = [];

            // Get follow-up leads (leads with status 'follow-up' created in this month)
            $followUpLeads = Lead::whereIn('leads.executive', $teamMemberIds)
                ->where('leads.status', 'follow-up')
                ->whereBetween('leads.created_at', [$startDate, $endDate])
                ->get();

            foreach ($followUpLeads as $lead) {
                $date = Carbon::parse($lead->created_at)->format('Y-m-d');
                if (!isset($calendarData[$date])) {
                    $calendarData[$date] = ['follow_up' => 0, 'future_prospect' => 0];
                }
                $calendarData[$date]['follow_up']++;
            }

            // Get future prospect leads (leads with status 'future prospect' based on future_prospect_date)
            $futureProspectLeads = Lead::whereIn('leads.executive', $teamMemberIds)
                ->where('leads.status', 'future prospect')
                ->whereNotNull('leads.future_prospect_date')
                ->whereBetween('leads.future_prospect_date', [$startDate, $endDate])
                ->get();

            foreach ($futureProspectLeads as $lead) {
                $date = Carbon::parse($lead->future_prospect_date)->format('Y-m-d');
                if (!isset($calendarData[$date])) {
                    $calendarData[$date] = ['follow_up' => 0, 'future_prospect' => 0];
                }
                $calendarData[$date]['future_prospect']++;
            }

            // Debug: Log the calendar data
            \Log::info('Manager Calendar Data', [
                'year' => $year,
                'month' => $month,
                'team_member_ids' => $teamMemberIds,
                'follow_up_count' => $followUpLeads->count(),
                'future_prospect_count' => $futureProspectLeads->count(),
                'calendar_data' => $calendarData
            ]);

            return response()->json(['calendar_data' => $calendarData]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch calendar data: ' . $e->getMessage()], 500);
        }
    }

    public function getLeadDetails($id)
    {
        try {
            $managerId = auth()->user()->id;
            $teamMemberIds = \App\Models\User::where('parent_id', $managerId)->pluck('id')->toArray();

            if (empty($teamMemberIds)) {
                return response()->json(['error' => 'No team members found'], 404);
            }

            $lead = Lead::with([
                    'executiveUser:id,f_name,l_name',
                ])
                ->whereIn('executive', $teamMemberIds)
                ->findOrFail($id);

            $details = [
                'id' => $lead->id,
                'formatted_id' => $lead->formatted_id ?? ('#' . $lead->id),
                'customer_name' => $lead->customer_name,
                'contact_no' => $lead->contact_no,
                'alternate_contact_no' => $lead->alternate_contact_no ?? null,
                'email' => $lead->email ?? null,
                'location' => $lead->location ?? null,
                'query' => $lead->query ?? null,
                'status' => $lead->status ?? null,
                'stage' => $lead->stage ?? null,
                'source' => $lead->source ?? null,
                'date_time' => $lead->created_at,
                'executive_name' => $lead->executiveUser ? ($lead->executiveUser->f_name . ' ' . $lead->executiveUser->l_name) : null,
                'last_call_status' => $lead->last_call_status_display ?? null,
                'remarks' => $lead->remarks ?? null,
                'patient_name' => $lead->patient_name ?? null,
                'patient_gender' => $lead->patient_gender ?? null,
                'price_issue_remark' => $lead->price_issue_remark ?? null,
                'inactive_remark' => $lead->inactive_remark ?? null,
            ];

            return response()->json(['success' => true, 'lead' => $details]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch lead details: ' . $e->getMessage()], 500);
        }
    }

    public function getDateLeads(Request $request)
    {
        try {
            $date = $request->get('date');
            $managerId = auth()->user()->id;
            $teamMemberIds = \App\Models\User::where('parent_id', $managerId)->pluck('id')->toArray();

            if (empty($teamMemberIds)) {
                return response()->json([
                    'follow_up_leads' => [],
                    'prospect_leads' => [],
                ]);
            }

            $startOfDay = Carbon::parse($date)->startOfDay();
            $endOfDay = Carbon::parse($date)->endOfDay();

            // Get follow-up leads (leads with status 'follow-up' created on this date)
            $followUpLeads = Lead::whereIn('leads.executive', $teamMemberIds)
                ->where('leads.status', 'follow-up')
                ->whereBetween('leads.created_at', [$startOfDay, $endOfDay])
                ->leftJoin('users', 'leads.executive', '=', 'users.id')
                ->select('leads.id', 'leads.customer_name', 'leads.contact_no', 'leads.query', 'leads.created_at', 'leads.executive', \DB::raw("CONCAT(users.f_name, ' ', COALESCE(users.l_name, '')) as executive_name"))
                ->get();

            // Get future prospect leads (leads with status 'future prospect' based on future_prospect_date)
            $prospectLeads = Lead::whereIn('leads.executive', $teamMemberIds)
                ->where('leads.status', 'future prospect')
                ->whereNotNull('leads.future_prospect_date')
                ->whereDate('leads.future_prospect_date', $date)
                ->leftJoin('users', 'leads.executive', '=', 'users.id')
                ->select('leads.id', 'leads.customer_name', 'leads.contact_no', 'leads.query', 'leads.future_prospect_date', 'leads.executive', \DB::raw("CONCAT(users.f_name, ' ', COALESCE(users.l_name, '')) as executive_name"))
                ->get();


            return response()->json([
                'follow_up_leads' => $followUpLeads,
                // Keep key name for frontend compatibility, though it contains future prospect
                'prospect_leads' => $prospectLeads,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch leads for date: ' . $e->getMessage()], 500);
        }
    }

    public function getRecentLeads(Request $request)
    {
        try {
            $user = Auth::user();
            
            // Get team member IDs (leads of team members under this manager)
            $teamMemberIds = \App\Models\User::where('parent_id', $user->id)->pluck('id')->toArray();
            
            // Get 25 most recent leads from team members based on created_at (when they were added to the system)
            $recentLeads = Lead::select(
                    'leads.*',
                    \DB::raw("CONCAT(users.f_name, ' ', COALESCE(users.l_name, '')) as executive")
                )
                ->leftJoin('users', 'leads.executive', '=', 'users.id')
                ->whereIn('leads.executive', $teamMemberIds)
                ->orderBy('leads.created_at', 'desc')
                ->limit(25)
                ->get();

            return response()->json([
                'success' => true,
                'leads' => $recentLeads
            ]);
        } catch (\Exception $e) {
            \Log::error('Error getting recent leads (Manager): ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Failed to load recent leads data'
            ], 500);
        }
    }

    public function getRecentCalls(Request $request)
    {
        try {
            $user = Auth::user();
            
            // Get team member IDs
            $teamMemberIds = User::where('parent_id', $user->id)->pluck('id')->toArray();
            
            // Get call logs from the past week with any status
            $oneWeekAgo = now()->subWeek();
            
            // Only include calls for Leads (not OperationLead or JobRequest)
            $callLogs = CallDetails::with(['executive:id,f_name,l_name'])
                ->whereIn('executive_id', $teamMemberIds)
                ->where('call_for', 'lead') // Only Lead calls
                ->where('call_received_datetime', '>=', $oneWeekAgo)
                ->orderBy('call_received_datetime', 'desc')
                ->get();
            
            $recentCalls = [];
            $successfulCount = 0;
            $failedCount = 0;
            
            // Show ALL calls, not grouped by phone number
            foreach ($callLogs as $callLog) {
                $contactNumber = $callLog->caller_id_number;
                $normalizedNumber = $this->normalizePhoneNumber($contactNumber);
                
                // Try to find associated lead by contact number
                $lead = Lead::where(function($query) use ($contactNumber, $normalizedNumber, $teamMemberIds) {
                    $query->where('contact_no', $contactNumber)
                          ->orWhere('contact_no', $normalizedNumber)
                          ->orWhere('contact_no', '91' . $normalizedNumber)
                          ->orWhere('contact_no', '0' . $normalizedNumber);
                })
                    ->whereIn('executive', $teamMemberIds)
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
                
                // Use lead_code from CallDetails if available, otherwise try to find lead
                $leadCode = $callLog->lead_code ?: ($lead ? ($lead->lead_code ?: $lead->formatted_id) : 'No Lead');
                $customerName = $callLog->customer_name ?: ($lead ? $lead->customer_name : 'N/A');
                
                $recentCalls[] = [
                    'lead_no' => $leadCode,
                    'lead_id' => $callLog->lead_id ?: ($lead ? $lead->id : null),
                    'call_datetime' => $callLog->call_received_datetime ? $callLog->call_received_datetime->format('d-M-Y H:i:s') : 'N/A',
                    'executive_name' => $executiveName,
                    'customer_name' => $customerName,
                    'customer_number' => $contactNumber,
                    'call_status' => $callLog->call_status,
                    'call_direction' => CallDirectionLabel::display($callLog->call_type, $callLog->raw_data),
                    'duration' => $callLog->call_duration ? gmdate('H:i:s', $callLog->call_duration) : 'N/A',
                    'call_log_id' => $callLog->id,
                    'view_url' => ($callLog->lead_id || $lead) ? route('manager.leads.show', $callLog->lead_id ?: $lead->id) : null,
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
            \Log::error('Error getting recent calls (Manager): ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Failed to load recent calls data: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getActiveCalls(Request $request)
    {
        try {
            $user = Auth::user();
            $now = now();
            $tenMinutesAgo = now()->subMinutes(10);
            
            // Get team member IDs
            $teamMemberIds = User::where('parent_id', $user->id)->pluck('id')->toArray();
            
            // Get call logs from the last 10 minutes for team members
            // Include both processed and unprocessed calls for active tracking
            // Only include calls for Leads (not OperationLead or JobRequest)
            $callLogs = CallDetails::whereIn('executive_id', $teamMemberIds)
                ->where('call_for', 'lead') // Only Lead calls
                ->where('call_received_datetime', '>=', $tenMinutesAgo)
                ->orderBy('call_received_datetime', 'desc')
                ->get();
            
            $activeCalls = [];
            $processedNumbers = []; // Track already processed normalized numbers
            
            foreach ($callLogs as $log) {
                // Use caller_id_number to find the associated lead
                $contactNumber = $log->caller_id_number;
                $normalizedNumber = $this->normalizePhoneNumber($contactNumber);

                // Skip if we've already processed this normalized number (show only most recent call per number)
                if (in_array($normalizedNumber, $processedNumbers)) {
                    continue;
                }
                $processedNumbers[] = $normalizedNumber;
                
                // Find the associated lead by contact number
                $lead = Lead::where(function($query) use ($contactNumber, $normalizedNumber, $teamMemberIds) {
                    $query->where('contact_no', $contactNumber)
                          ->orWhere('contact_no', $normalizedNumber)
                          ->orWhere('contact_no', '91' . $normalizedNumber)
                          ->orWhere('contact_no', '0' . $normalizedNumber);
                })
                    ->whereIn('executive', $teamMemberIds)
                    ->orderBy('created_at', 'desc')
                    ->first();
    
                $leadNo = $log->lead_code ?? ($lead ? ($lead->lead_code ?: $lead->formatted_id) : 'No Lead');
                $customerName = $log->customer_name ?? ($lead ? $lead->customer_name : 'N/A');
                    
                $activeCalls[] = [
                    'lead_id' => $lead ? $lead->id : null,
                    'lead_code' => $leadNo,
                    'customer_name' => $customerName,
                    'patient_name' => $lead ? $lead->patient_name : 'N/A',
                    'contact_no' => $contactNumber,
                    'location' => $lead ? $lead->location : 'N/A',
                    'query' => $lead ? $lead->query : 'N/A',
                    'lead_status' => $lead ? $lead->status : 'N/A',
                    'stage' => $lead ? $lead->stage : 'N/A',
                    'call_time' => $log->call_received_datetime->format('Y-m-d H:i:s'),
                    'call_direction' => CallDirectionLabel::display($log->call_type, $log->raw_data),
                    'call_status' => $log->call_status,
                    'agent_name' => $log->agent_name,
                    'agent_number' => $log->agent_number,
                    'is_active' => $log->call_status === 'ringing' || $log->call_status === 'answered',
                    'minutes_ago' => $log->call_received_datetime->diffInMinutes($now),
                    'call_log_id' => $log->id,
                    'view_url' => $lead ? route('manager.leads.show', $lead->id) : null
                ];
            }
            
            return response()->json([
                'success' => true,
                'calls' => $activeCalls
            ]);
        } catch (\Exception $e) {
            \Log::error('Error getting active calls (Manager): ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Failed to load active calls data: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getPendingCallbacks(Request $request)
    {
        try {
            $user = Auth::user();
            
            // Get team member IDs (users where the manager is parent)
            $teamMemberIds = User::where('parent_id', $user->id)->pluck('id')->toArray();
            
            if (empty($teamMemberIds)) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'total_pending' => 0,
                        'leads' => []
                    ]
                ]);
            }

            // Get all pending calls for team members (not answered)
            // Only include calls for Leads (not OperationLead or JobRequest)
            $allCallLogsQuery = CallDetails::select('call_details.*')
                ->whereIn('call_details.executive_id', $teamMemberIds)
                ->where('call_details.call_for', 'lead') // Only Lead calls
                ->whereNotIn('call_details.call_status', ['answered', 'answer'])
                ->with(['executive:id,f_name,l_name']); // Eager load executive to avoid N+1 queries
            
            $allCallLogs = $allCallLogsQuery->orderBy('call_details.call_received_datetime', 'desc')->get();
            
            // Group by normalized phone number AND executive_id to get latest call per number per executive
            // This ensures each team member's pending callbacks are counted separately
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
                
                // Check if there was a successful callback after this call (using normalized number)
                // Don't filter by executive_id - any successful callback should mark it as responded
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

                // Try to find associated lead by contact number from team members
                $lead = Lead::where(function($query) use ($contactNumber, $normalizedNumber, $teamMemberIds) {
                    $query->where('contact_no', $contactNumber)
                          ->orWhere('contact_no', $normalizedNumber)
                          ->orWhere('contact_no', '91' . $normalizedNumber)
                          ->orWhere('contact_no', '0' . $normalizedNumber);
                })
                    ->whereIn('executive', $teamMemberIds)
                    ->orderBy('created_at', 'desc')
                    ->first();

                // Get executive name
                $executiveName = 'N/A';
                if ($callLog->executive) {
                    $executiveName = trim($callLog->executive->f_name . ' ' . ($callLog->executive->l_name ?? ''));
                } elseif ($callLog->executive_id) {
                    // Fallback if relationship not loaded
                    $executive = User::find($callLog->executive_id);
                    if ($executive) {
                        $executiveName = trim($executive->f_name . ' ' . ($executive->l_name ?? ''));
                    }
                }

                // Use lead_code from CallDetails if available, otherwise try to find lead
                $leadCode = $callLog->lead_code ?: ($lead ? ($lead->lead_code ?: $lead->formatted_id) : 'No Lead');
                $customerName = $callLog->customer_name ?: ($lead ? $lead->customer_name : 'N/A');

                $pendingCallbacks[] = [
                    'id' => $lead ? $lead->id : null,
                    'lead_code' => $leadCode,
                    'customer_name' => $customerName,
                    'patient_name' => $lead ? $lead->patient_name : 'N/A',
                    'contact_no' => $contactNumber,
                    'location' => $lead ? $lead->location : 'N/A',
                    'query' => $lead ? $lead->query : 'N/A',
                    'status' => $lead ? $lead->status : 'N/A',
                    'stage' => $lead ? $lead->stage : 'N/A',
                    'last_call_status' => $callLog->call_status,
                    'last_updated' => $callLog->call_received_datetime,
                    'created_at' => $lead ? $lead->created_at : $callLog->call_received_datetime,
                    'view_url' => $lead ? route('manager.leads.show', $lead->id) : null,
                    'response_datetime' => $responseDateTime,
                    'duration' => $callLog->call_duration ? gmdate('H:i:s', $callLog->call_duration) : 'N/A',
                    'executive_name' => $executiveName
                ];
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'total_pending' => count($pendingCallbacks),
                    'leads' => $pendingCallbacks
                ]
            ]);
        } catch (\Exception $e) {
            \Log::error('Error getting pending callbacks (Manager): ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Failed to load pending callbacks data: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getTeamMembers(Request $request)
    {
        try {
            $managerId = auth()->user()->id;
            $teamMembers = \App\Models\User::where('parent_id', $managerId)->get();

            // Add location names to each team member
            foreach ($teamMembers as $member) {
                if ($member->location_id) {
                    $locationIds = is_array($member->location_id) ? $member->location_id : explode(',', $member->location_id);
                    $locations = Location::whereIn('id', $locationIds)->pluck('name')->toArray();
                    $member->location_names = implode(', ', $locations);
                } else {
                    $member->location_names = null;
                }
            }

            return response()->json([
                'success' => true,
                'teamMembers' => $teamMembers->map(function($member) {
                    // Handle role - it might be a string or a relationship object
                    $roleValue = $member->role;
                    if (is_object($roleValue) && isset($roleValue->name)) {
                        $roleValue = $roleValue->name;
                    } elseif (is_array($roleValue) && isset($roleValue['name'])) {
                        $roleValue = $roleValue['name'];
                    }
                    
                    return [
                        'id' => $member->id,
                        'f_name' => $member->f_name,
                        'l_name' => $member->l_name,
                        'email' => $member->email,
                        'mobile' => $member->mobile,
                        'role' => $roleValue ?? 'N/A',
                        'status' => $member->status,
                        'location_names' => $member->location_names,
                    ];
                }),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to load team members: ' . $e->getMessage()
            ], 500);
        }
    }
}
