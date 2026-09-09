<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\Cases;
use App\Models\Lead;
use App\Models\Task;
use App\Models\User;
use App\Models\CallLog;
use App\Models\CallDetails;
use App\Models\Location;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SalesController extends Controller
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

    public function dashboard(){
        $page_heading = 'Sales Dashboard';

        // Get today's leads count for the logged-in user
        $todayLeads = Lead::where('executive', auth()->user()->id)
            ->whereDate('created_at', Carbon::today())
            ->count();

        // Get task statistics for the logged-in user including parent manager assigned tasks
        $userId = auth()->user()->id;
        $parentManagerId = User::where('id', $userId)->value('parent_id');

        $totalTasks = Task::where(function($q) use ($userId, $parentManagerId) {
            $q->where('assigned_to', $userId)
              ->orWhere('assigned_by', $userId);

            // Also include tasks assigned by parent manager to this user
            if ($parentManagerId) {
                $q->orWhere(function($subQ) use ($parentManagerId, $userId) {
                    $subQ->where('assigned_by', $parentManagerId)
                         ->where('assigned_to', $userId);
                });
            }
        })->count();

        $pendingTasks = Task::where(function($q) use ($userId, $parentManagerId) {
            $q->where('assigned_to', $userId)
              ->where('status', 'pending');

            // Also include pending tasks assigned by parent manager to this user
            if ($parentManagerId) {
                $q->orWhere(function($subQ) use ($parentManagerId, $userId) {
                    $subQ->where('assigned_by', $parentManagerId)
                         ->where('assigned_to', $userId)
                         ->where('status', 'pending');
                });
            }
        })->count();

        $completedTasks = Task::where(function($q) use ($userId, $parentManagerId) {
            $q->where('assigned_to', $userId)
              ->where('status', 'completed');

            // Also include completed tasks assigned by parent manager to this user
            if ($parentManagerId) {
                $q->orWhere(function($subQ) use ($parentManagerId, $userId) {
                    $subQ->where('assigned_by', $parentManagerId)
                         ->where('assigned_to', $userId)
                         ->where('status', 'completed');
                });
            }
        })->count();

        $overdueTasks = Task::where(function($q) use ($userId, $parentManagerId) {
            $q->where('assigned_to', $userId)
              ->where('due_date', '<', Carbon::today())
              ->where('status', '!=', 'completed');

            // Also include overdue tasks assigned by parent manager to this user
            if ($parentManagerId) {
                $q->orWhere(function($subQ) use ($parentManagerId, $userId) {
                    $subQ->where('assigned_by', $parentManagerId)
                         ->where('assigned_to', $userId)
                         ->where('due_date', '<', Carbon::today())
                         ->where('status', '!=', 'completed');
                });
            }
        })->count();

        // Get locations and services for Edit Lead Modal
        $locations = Location::get();
        $services = Service::get();

        return view('sales.dashboard', compact('page_heading', 'todayLeads', 'totalTasks', 'pendingTasks', 'completedTasks', 'overdueTasks', 'locations', 'services'));
    }

    public function getLeadsStats(Request $request)
    {
        try {
            $period = $request->get('period', 'today');
            $startDate = $request->get('start_date');
            $endDate = $request->get('end_date');
            $selectedMonth = $request->get('selected_month');
            $selectedYear = $request->get('selected_year');

            // Filter by the logged-in user's ID (Sales Executive)
            $baseQuery = Lead::where('executive', auth()->user()->id);

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
                    SUM(CASE WHEN status = "follow-up" THEN 1 ELSE 0 END) as follow_up,
                    SUM(CASE WHEN status = "future prospect" THEN 1 ELSE 0 END) as future_prospect,
                    SUM(CASE WHEN status = "prospect" THEN 1 ELSE 0 END) as prospect,
                    SUM(CASE WHEN status = "no response" THEN 1 ELSE 0 END) as no_response,
                    SUM(CASE WHEN status = "price issue" THEN 1 ELSE 0 END) as price_issue,
                    SUM(CASE WHEN status = "duplicate" THEN 1 ELSE 0 END) as duplicate,
                    SUM(CASE WHEN status = "spam" THEN 1 ELSE 0 END) as spam
                ')
                ->first();

            // Get leads data (filtered by user)
            $leads = Lead::select('leads.*', \DB::raw("CONCAT(users.f_name, ' ', COALESCE(users.l_name, '')) as executive_name"))
                ->leftJoin('users', 'leads.executive', '=', 'users.id')
                ->where('leads.executive', auth()->user()->id)
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
            $type = $request->get('type', 'all');
            $startDate = $request->get('start_date');
            $endDate = $request->get('end_date');
            $userId = auth()->user()->id;

            // Get parent manager ID
            $parentManagerId = User::where('id', $userId)->value('parent_id');

            $query = Task::with(['assignedTo', 'assignedBy']);

            switch ($type) {
                case 'assigned_to_me':
                    $query->where('assigned_to', $userId);
                    break;
                case 'assigned_by_me':
                    $query->where('assigned_by', $userId);
                    break;
                case 'parent_manager_tasks':
                    if ($parentManagerId) {
                        $query->where('assigned_by', $parentManagerId)
                              ->where('assigned_to', $userId);
                    } else {
                        // If no parent manager, return empty result
                        $query->where('id', 0);
                    }
                    break;
                case 'all':
                default:
                    $query->where(function($q) use ($userId, $parentManagerId) {
                        $q->where('assigned_to', $userId)
                          ->orWhere('assigned_by', $userId);

                        // Also include tasks assigned by parent manager to this user
                        if ($parentManagerId) {
                            $q->orWhere(function($subQ) use ($parentManagerId, $userId) {
                                $subQ->where('assigned_by', $parentManagerId)
                                     ->where('assigned_to', $userId);
                            });
                        }
                    });
                    break;
            }

            // Apply date filter if provided
            if ($startDate && $endDate) {
                $query->whereBetween('created_at', [
                    Carbon::parse($startDate)->startOfDay(),
                    Carbon::parse($endDate)->endOfDay()
                ]);
            } elseif ($startDate) {
                $query->whereDate('created_at', '>=', Carbon::parse($startDate)->startOfDay());
            } elseif ($endDate) {
                $query->whereDate('created_at', '<=', Carbon::parse($endDate)->endOfDay());
            }

            $tasks = $query->orderBy('created_at', 'desc')->get();

            return response()->json(['tasks' => $tasks]);
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
                'due_date' => 'required|date|after_or_equal:today'
            ]);

            // Force assignment to current user only
            $task = Task::create([
                'title' => $request->title,
                'description' => $request->description,
                'priority' => $request->priority,
                'due_date' => $request->due_date,
                'assigned_to' => auth()->user()->id, // Always assign to current user
                'assigned_by' => auth()->user()->id,
                'status' => 'pending'
            ]);

            return response()->json(['success' => true, 'task' => $task]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to create task: ' . $e->getMessage()], 500);
        }
    }

    public function updateTaskStatus(Request $request, $id)
    {
        try {
            $request->validate([
                'status' => 'required|in:pending,in_progress,completed,cancelled'
            ]);

            $task = Task::findOrFail($id);
            $userId = auth()->user()->id;

            // Check if user is assigned to this task or assigned this task
            if ($task->assigned_to != $userId && $task->assigned_by != $userId) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            $task->status = $request->status;
            if ($request->status === 'completed') {
                $task->completed_at = now();
            } else {
                $task->completed_at = null;
            }
            $task->save();

            return response()->json(['success' => true, 'task' => $task]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to update task status: ' . $e->getMessage()], 500);
        }
    }

    public function updateTask(Request $request, $id)
    {
        try {
            $request->validate([
                'title' => 'required|string|max:255',
                'description' => 'nullable|string',
                'priority' => 'required|in:low,medium,high,urgent',
                'due_date' => 'required|date|after_or_equal:today'
            ]);

            $task = Task::findOrFail($id);
            $userId = auth()->user()->id;

            // Check if user is assigned to this task or assigned this task
            if ($task->assigned_to != $userId && $task->assigned_by != $userId) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            // Prevent editing completed tasks
            if ($task->status === 'completed') {
                return response()->json(['error' => 'Cannot edit completed tasks'], 400);
            }

            $task->title = $request->title;
            $task->description = $request->description;
            $task->priority = $request->priority;
            $task->due_date = $request->due_date;
            $task->save();

            return response()->json(['success' => true, 'task' => $task]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to update task: ' . $e->getMessage()], 500);
        }
    }

    public function getCalendarData(Request $request)
    {
        try {
            $year = $request->get('year', date('Y'));
            $month = $request->get('month', date('n'));
            $userId = auth()->user()->id;

            // Get the first and last day of the month
            $startDate = Carbon::create($year, $month, 1)->startOfMonth();
            $endDate = Carbon::create($year, $month, 1)->endOfMonth();

            // Get follow-up leads (based on created_at)
            $followUpLeads = Lead::where('executive', $userId)
                ->where('status', 'follow-up')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->get();

            // Get future prospect leads (based on future_prospect_date)
            $futureProspectLeads = Lead::where('executive', $userId)
                ->where('status', 'future prospect')
                ->whereNotNull('future_prospect_date')
                ->whereBetween('future_prospect_date', [$startDate, $endDate])
                ->get();

            // Group leads by date
            $calendarData = [];

            // Process follow-up leads
            foreach ($followUpLeads as $lead) {
                $date = Carbon::parse($lead->created_at)->format('Y-m-d');

                if (!isset($calendarData[$date])) {
                    $calendarData[$date] = ['follow_up' => 0, 'future_prospect' => 0];
                }

                $calendarData[$date]['follow_up']++;
            }

            // Process future prospect leads
            foreach ($futureProspectLeads as $lead) {
                $date = Carbon::parse($lead->future_prospect_date)->format('Y-m-d');

                if (!isset($calendarData[$date])) {
                    $calendarData[$date] = ['follow_up' => 0, 'future_prospect' => 0];
                }

                $calendarData[$date]['future_prospect']++;
            }

            return response()->json([
                'success' => true,
                'calendar_data' => $calendarData
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch calendar data: ' . $e->getMessage()], 500);
        }
    }

    public function getDateLeads(Request $request)
    {
        try {
            $date = $request->get('date');
            $userId = auth()->user()->id;

            if (!$date) {
                return response()->json(['error' => 'Date is required'], 400);
            }

            $startDate = Carbon::parse($date)->startOfDay();
            $endDate = Carbon::parse($date)->endOfDay();

            // Get follow-up leads for the date with executive name (based on created_at)
            $followUpLeads = Lead::where('executive', $userId)
                ->where('status', 'follow-up')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->with('executiveUser:id,f_name,l_name')
                ->select('id', 'customer_name', 'contact_no', 'query', 'created_at', 'executive')
                ->get()
                ->map(function($lead) {
                    $lead->executive_name = $lead->executiveUser ?
                        $lead->executiveUser->f_name . ' ' . $lead->executiveUser->l_name :
                        'N/A';
                    return $lead;
                });

            // Get future prospect leads for the date with executive name (based on future_prospect_date)
            $prospectLeads = Lead::where('executive', $userId)
                ->where('status', 'future prospect')
                ->whereNotNull('future_prospect_date')
                ->whereDate('future_prospect_date', $date)
                ->with('executiveUser:id,f_name,l_name')
                ->select('id', 'customer_name', 'contact_no', 'query', 'future_prospect_date', 'executive')
                ->get()
                ->map(function($lead) {
                    $lead->executive_name = $lead->executiveUser ?
                        $lead->executiveUser->f_name . ' ' . $lead->executiveUser->l_name :
                        'N/A';
                    return $lead;
                });

            return response()->json([
                'success' => true,
                'follow_up_leads' => $followUpLeads,
                'prospect_leads' => $prospectLeads
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch date leads: ' . $e->getMessage()], 500);
        }
    }

    public function getLeadDetails($id)
    {
        try {
            $userId = auth()->user()->id;
            $lead = Lead::with([
                    'executiveUser:id,f_name,l_name',
                ])
                ->where('executive', $userId)
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

    public function callLogsCount()
    {
        try {
            $userId = auth()->user()->id;
            $count = CallLog::where('executive_id', $userId)
                ->where('is_processed', false)
                ->count();

            return response()->json(['count' => $count]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch call logs count: ' . $e->getMessage()], 500);
        }
    }

    public function callLogs()
    {
        try {
            $userId = auth()->user()->id;
            $callLogs = CallLog::where('executive_id', $userId)
                ->where('is_processed', false)
                ->with(['executive', 'lead'])
                ->orderBy('call_received_datetime', 'desc')
                ->get();

            return response()->json(['callLogs' => $callLogs]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch call logs: ' . $e->getMessage()], 500);
        }
    }

    public function markCallLogProcessed($id)
    {
        try {
            $userId = auth()->user()->id;
            $callLog = CallLog::where('id', $id)
                ->where('executive_id', $userId)
                ->first();

            if (!$callLog) {
                return response()->json(['error' => 'Call log not found'], 404);
            }

            $callLog->update(['is_processed' => true]);

            return response()->json(['success' => true, 'message' => 'Call log marked as processed']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to mark call log as processed: ' . $e->getMessage()], 500);
        }
    }

    public function getRecentLeads(Request $request)
    {
        try {
            $user = Auth::user();
            
            // Get 25 most recent leads based on created_at (when they were added to the system)
            $recentLeads = Lead::select(
                    'leads.*',
                    \DB::raw("CONCAT(users.f_name, ' ', COALESCE(users.l_name, '')) as executive")
                )
                ->leftJoin('users', 'leads.executive', '=', 'users.id')
                ->where('leads.executive', $user->id)
                ->orderBy('leads.created_at', 'desc')
                ->limit(25)
                ->get();

            return response()->json([
                'success' => true,
                'leads' => $recentLeads
            ]);
        } catch (\Exception $e) {
            \Log::error('Error getting recent leads stats: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Failed to load recent leads data'
            ], 500);
        }
    }

    public function getActiveCalls(Request $request)
    {
        try {
            $user = Auth::user();
            $now = now();
            $tenMinutesAgo = now()->subMinutes(10);
            
            // Get call logs from the last 10 minutes for this executive
            // Include both processed and unprocessed calls for active tracking
            // Only include calls for Leads (not OperationLead or JobRequest)
            $callLogs = CallDetails::where('executive_id', $user->id)
                ->where('call_for', 'lead') // Only Lead calls
                ->where('call_received_datetime', '>=', $tenMinutesAgo)
                ->orderBy('call_received_datetime', 'desc')
                ->get();
            
            $activeCalls = [];
            $processedNumbers = []; // Track already processed numbers
            
            foreach ($callLogs as $log) {
                // Use caller_id_number to find the associated lead
                $contactNumber = $log->caller_id_number;
                $normalizedNumber = $this->normalizePhoneNumber($contactNumber);

                // Skip if we've already processed this number (show only most recent call per number)
                if (in_array($normalizedNumber, $processedNumbers)) {
                    continue;
                }
                $processedNumbers[] = $normalizedNumber;
                
                // Find the associated lead by contact number
                $lead = Lead::where(function($query) use ($contactNumber, $normalizedNumber, $user) {
                    $query->where('contact_no', $contactNumber)
                          ->orWhere('contact_no', $normalizedNumber)
                          ->orWhere('contact_no', '91' . $normalizedNumber)
                          ->orWhere('contact_no', '0' . $normalizedNumber);
                })
                    ->where('executive', $user->id)
                    ->orderBy('created_at', 'desc')
                    ->first();
    
                
                    $leadNo = $log->lead_code ?? ($lead ? ($lead->lead_code ?: $lead->formatted_id) : 'No Lead');
                    $customerName = $log->customer_name ?? ($lead->customer_name ?? 'N/A');
                    
                        
                $activeCalls[] = [
                    'lead_id' => $lead->id ?? 'No Lead',
                    'lead_code' => $leadNo,
                    'customer_name' => $customerName,
                    'patient_name' => $lead->patient_name ?? 'N/A',
                    'contact_no' => $contactNumber,
                    'location' => $lead->location ?? 'N/A',
                    'query' => $lead->query ?? 'N/A',
                    'lead_status' => $lead->status ?? 'N/A',
                    'stage' => $lead->stage ?? 'N/A',
                    'call_time' => $log->call_received_datetime->format('Y-m-d H:i:s'),
                    'call_direction' => $log->call_type ?? 'Incoming',
                    'call_status' => $log->call_status,
                    'agent_name' => $log->agent_name,
                    'agent_number' => $log->agent_number,
                    'is_active' => $log->call_status === 'ringing' || $log->call_status === 'answered', // Define what constitutes an 'active' call
                    'minutes_ago' => $log->call_received_datetime->diffInMinutes($now),
                    'call_log_id' => $log->id
                ];
            }
            
            return response()->json([
                'success' => true,
                'calls' => $activeCalls
            ]);
        } catch (\Exception $e) {
            \Log::error('Error getting active calls: ' . $e->getMessage());
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

            // Get all pending calls for this executive (not answered)
            // Only include calls for Leads (not OperationLead or JobRequest)
            $allCallLogsQuery = CallDetails::select('call_details.*')
                ->where('call_details.executive_id', $user->id)
                ->where('call_details.call_for', 'lead') // Only Lead calls
                ->whereNotIn('call_details.call_status', ['answered', 'answer']);
            
            $allCallLogs = $allCallLogsQuery->orderBy('call_details.call_received_datetime', 'desc')->get();
            
            // Group by normalized phone number and get latest call per number
            $normalizedNumbers = [];
            $callLogs = [];
            
            foreach ($allCallLogs as $callLog) {
                $normalizedNumber = $this->normalizePhoneNumber($callLog->caller_id_number);
                
                if (!isset($normalizedNumbers[$normalizedNumber])) {
                    $normalizedNumbers[$normalizedNumber] = $callLog;
                } else {
                    // Keep the latest call
                    if ($callLog->call_received_datetime > $normalizedNumbers[$normalizedNumber]->call_received_datetime) {
                        $normalizedNumbers[$normalizedNumber] = $callLog;
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

                // Skip if already responded (answered call after missed)
                if ($responseDateTime) {
                    continue;
                }

                // Try to find associated lead by contact number
                $lead = Lead::where(function($query) use ($contactNumber, $normalizedNumber, $user) {
                    $query->where('contact_no', $contactNumber)
                          ->orWhere('contact_no', $normalizedNumber)
                          ->orWhere('contact_no', '91' . $normalizedNumber)
                          ->orWhere('contact_no', '0' . $normalizedNumber);
                })
                    ->where('executive', $user->id)
                    ->orderBy('created_at', 'desc')
                    ->first();

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
                    'view_url' => $lead ? route('sales.leads.show', $lead->id) : null,
                    'response_datetime' => $responseDateTime,
                    'duration' => $callLog->call_duration ? gmdate('H:i:s', $callLog->call_duration) : 'N/A'
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
            \Log::error('Error getting pending callbacks: ' . $e->getMessage());
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
            
            // Get call logs from the past week with any status
            // Only include calls for Leads (not OperationLead or JobRequest)
            $oneWeekAgo = now()->subWeek();
            
            $callLogs = CallDetails::where('executive_id', $user->id)
                ->where('call_for', 'lead') // Only Lead calls
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
                
                // Try to find associated lead by contact number
                $lead = Lead::where(function($query) use ($contactNumber, $normalizedNumber, $user) {
                    $query->where('contact_no', $contactNumber)
                          ->orWhere('contact_no', $normalizedNumber)
                          ->orWhere('contact_no', '91' . $normalizedNumber)
                          ->orWhere('contact_no', '0' . $normalizedNumber);
                })
                    ->where('executive', $user->id)
                    ->orderBy('created_at', 'desc')
                    ->first();
                
                // Count successful vs failed calls
                if (in_array($callLog->call_status, ['answered', 'answer'])) {
                    $successfulCount++;
                } else {
                    $failedCount++;
                }
                
                // Use lead_code from CallDetails if available, otherwise try to find lead
                $leadCode = $callLog->lead_code ?: ($lead ? ($lead->lead_code ?: $lead->formatted_id) : 'No Lead');
                $customerName = $callLog->customer_name ?: ($lead ? $lead->customer_name : 'N/A');
                
                $recentCalls[] = [
                    'lead_no' => $leadCode,
                    'lead_id' => $callLog->lead_id ?: ($lead ? $lead->id : null),
                    'call_datetime' => $callLog->call_received_datetime ? $callLog->call_received_datetime->format('d-M-Y H:i:s') : 'N/A',
                    'customer_name' => $customerName,
                    'customer_number' => $contactNumber,
                    'call_status' => $callLog->call_status,
                    'duration' => $callLog->call_duration ? gmdate('H:i:s', $callLog->call_duration) : 'N/A',
                    'call_log_id' => $callLog->id,
                    'view_url' => ($callLog->lead_id || $lead) ? route('sales.leads.show', $callLog->lead_id ?: $lead->id) : null,
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
            \Log::error('Error getting recent calls (Sales): ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Failed to load recent calls data: ' . $e->getMessage()
            ], 500);
        }
    }

}
