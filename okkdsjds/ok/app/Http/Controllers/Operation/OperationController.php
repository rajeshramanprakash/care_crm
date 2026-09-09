<?php

namespace App\Http\Controllers\Operation;

use App\Http\Controllers\Controller;
use App\Models\Cases;
use App\Models\OperationLead;
use App\Models\Lead;
use App\Models\Task;
use App\Models\User;
use App\Models\CallLog;
use App\Models\CallDetails;
use App\Models\OperationDeploymentDetails;
use App\Models\JobRequest;
use App\Models\Vendor;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class OperationController extends Controller
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
        $user = Auth::user();
        $today = Carbon::today();

        // Get today's operation leads for this user
        $todayQuery = OperationLead::where('executive', $user->id);
        if ($user->services) {
            $userServices = explode(',', $user->services);
            $todayQuery->whereIn('query', $userServices);
        }
        $todayOperationLeads = $todayQuery->whereDate('created_at', $today)->count();

        // Get total operation leads for this user
        $totalQuery = OperationLead::where('executive', $user->id);
        if ($user->services) {
            $userServices = explode(',', $user->services);
            $totalQuery->whereIn('query', $userServices);
        }
        $totalOperationLeads = $totalQuery->count();

        // Get this week's operation leads for this user
        $weekStart = Carbon::now()->startOfWeek();
        $weekEnd = Carbon::now()->endOfWeek();
        $weekQuery = OperationLead::where('executive', $user->id);
        if ($user->services) {
            $userServices = explode(',', $user->services);
            $weekQuery->whereIn('query', $userServices);
        }
        $thisWeekOperationLeads = $weekQuery->whereBetween('created_at', [$weekStart, $weekEnd])->count();

        // Get this month's operation leads for this user
        $monthStart = Carbon::now()->startOfMonth();
        $monthEnd = Carbon::now()->endOfMonth();
        $monthQuery = OperationLead::where('executive', $user->id);
        if ($user->services) {
            $userServices = explode(',', $user->services);
            $monthQuery->whereIn('query', $userServices);
        }
        $thisMonthOperationLeads = $monthQuery->whereBetween('created_at', [$monthStart, $monthEnd])->count();

        // Get recent operation leads for this user
        $recentQuery = OperationLead::where('executive', $user->id);
        if ($user->services) {
            $userServices = explode(',', $user->services);
            $recentQuery->whereIn('query', $userServices);
        }
        $recentOperationLeads = $recentQuery->orderBy('created_at', 'desc')->limit(5)->get();

        // Get task statistics for the logged-in user including parent manager assigned tasks
        $userId = $user->id;
        $parentManagerId = User::where('id', $userId)->value('parent_id');

        $totalTasks = Task::where('assigned_to', $userId)
            ->orWhere('assigned_by', $userId)
            ->orWhere(function($query) use ($userId, $parentManagerId) {
                $query->where('assigned_to', $userId)
                      ->where('assigned_by', $parentManagerId);
            })
            ->count();

        $pendingTasks = Task::where('assigned_to', $userId)
            ->where('status', 'pending')
            ->count();

        $completedTasks = Task::where('assigned_to', $userId)
            ->where('status', 'completed')
            ->count();

        $overdueTasks = Task::where('assigned_to', $userId)
            ->where('due_date', '<', Carbon::today())
            ->where('status', '!=', 'completed')
            ->count();

        // Calculate outstanding payments for this user's operation leads (using new invoice system)
        // Only count positive outstanding amounts (pending payments, not overpaid)
        $paymentInvoices = \App\Models\PaymentInvoice::whereHas('operationLead', function($query) use ($user) {
                $query->where('executive', $user->id);
            })
            ->with('receivedPayments')
            ->get();
        
        $outstandingPayments = $paymentInvoices->sum(function($invoice) {
            $outstanding = $invoice->payment_amount - $invoice->receivedPayments->sum('amount');
            // Only count positive outstanding (pending payments)
            return $outstanding > 0 ? $outstanding : 0;
        });

        // Calculate deployment pending count for current user
        $myDeploymentPending = OperationDeploymentDetails::whereHas('operationLead', function($query) use ($user) {
                $query->where('executive', $user->id);
            })
            ->where('deployment_status', 'Pending')
            ->count();

        // Calculate deployment pending count for team (excluding current user)
        $teamDeploymentPending = OperationDeploymentDetails::whereHas('operationLead', function($query) use ($user) {
                $query->where('executive', '!=', $user->id);
            })
            ->where('deployment_status', 'Pending')
            ->count();

        // Total deployment pending (for display purposes)
        $deploymentPending = $myDeploymentPending + $teamDeploymentPending;

        // Calculate profile pending count for current user
        $myProfileQuery = OperationLead::where('executive', $user->id)->where('status', 'profile pending');
        if ($user->services) {
            $userServices = explode(',', $user->services);
            $myProfileQuery->whereIn('query', $userServices);
        }
        $myProfilePending = $myProfileQuery->count();

        // Calculate profile pending count for team (excluding current user)
        $teamProfilePending = OperationLead::where('executive', '!=', $user->id)
            ->where('status', 'profile pending')
            ->count();

        // Total profile pending count
        $profilePending = $myProfilePending + $teamProfilePending;

        // Calculate ongoing leads count for this user's operation leads
        $ongoingQuery = OperationLead::where('executive', $user->id)->where('ongoing_stopped', 'ongoing');
        if ($user->services) {
            $userServices = explode(',', $user->services);
            $ongoingQuery->whereIn('query', $userServices);
        }
        $ongoingLeads = $ongoingQuery->count();

        // Calculate unverified payments count for this user's deployment details
        $unverifiedPayments = OperationDeploymentDetails::whereHas('operationLead', function($query) use ($user) {
                $query->where('executive', $user->id);
            })
            ->where('verify_payment', false)
            ->whereNotNull('vendor_payment')
            ->where('vendor_payment', '>', 0)
            ->count();

        // Calculate job requests count (excluding inactive, active, and blacklisted)
        $nonActiveJobRequests = JobRequest::whereNotIn('status', ['inactive', 'active', 'blacklisted'])->count();

        // Calculate unverified vendor/freelancer payments count
        $unverifiedVendorFreelancerPayments = OperationDeploymentDetails::where('verify_payment', false)
            ->whereNotNull('vendor_payment')
            ->where('vendor_payment', '>', 0)
            ->count();

        // Get services and vendors for Add Operation Lead modal
        $services = Service::get();
        $vendors = Vendor::where('status', 'active')->get();

        return view('operation.dashboard', compact(
            'todayOperationLeads',
            'totalOperationLeads',
            'thisWeekOperationLeads',
            'thisMonthOperationLeads',
            'recentOperationLeads',
            'totalTasks',
            'pendingTasks',
            'completedTasks',
            'overdueTasks',
            'outstandingPayments',
            'deploymentPending',
            'myDeploymentPending',
            'teamDeploymentPending',
            'myProfilePending',
            'teamProfilePending',
            'profilePending',
            'ongoingLeads',
            'unverifiedPayments',
            'nonActiveJobRequests',
            'unverifiedVendorFreelancerPayments',
            'user',
            'services',
            'vendors'
        ));
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

            $baseQuery = OperationLead::where('executive', $user->id);

            // Filter by user's assigned services (if services are set for this user)
            if ($user->services) {
                $userServices = explode(',', $user->services);
                $baseQuery->whereIn('query', $userServices);
            }

            switch ($period) {
                case 'today':
                    $startDate = Carbon::today();
                    $endDate = Carbon::today()->endOfDay();
                    $groupBy = 'HOUR(operation_leads.created_at)';
                    break;

                case 'monthly':
                    if ($selectedMonth) {
                        $monthParts = explode('-', $selectedMonth);
                        $year = $monthParts[0];
                        $month = $monthParts[1];
                        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
                        $endDate = Carbon::create($year, $month, 1)->endOfMonth();
                    } else {
                        $startDate = Carbon::now()->startOfMonth();
                        $endDate = Carbon::now()->endOfMonth();
                    }
                    $groupBy = 'DATE(operation_leads.created_at)';
                    break;

                case 'yearly':
                    if ($selectedYear) {
                        $startDate = Carbon::create($selectedYear, 1, 1)->startOfYear();
                        $endDate = Carbon::create($selectedYear, 12, 31)->endOfYear();
                    } else {
                        $startDate = Carbon::now()->startOfYear();
                        $endDate = Carbon::now()->endOfYear();
                    }
                    $groupBy = 'MONTH(operation_leads.created_at)';
                    break;

                case 'custom':
                    if ($startDate && $endDate) {
                        $startDate = Carbon::parse($startDate)->startOfDay();
                        $endDate = Carbon::parse($endDate)->endOfDay();
                    } else {
                        $startDate = Carbon::now()->startOfMonth();
                        $endDate = Carbon::now()->endOfMonth();
                    }
                    $groupBy = 'DATE(operation_leads.created_at)';
                    break;

                default:
                    $startDate = Carbon::today();
                    $endDate = Carbon::today()->endOfDay();
                    $groupBy = 'HOUR(operation_leads.created_at)';
            }

            // Get statistics
            $stats = $baseQuery->selectRaw("{$groupBy} as date, COUNT(*) as count")
                ->whereBetween('operation_leads.created_at', [$startDate, $endDate])
                ->groupBy('date')
                ->orderBy('date', 'asc')
                ->get();

            // Get leads data
            $leadsQuery = OperationLead::where('executive', $user->id);
            if ($user->services) {
                $userServices = explode(',', $user->services);
                $leadsQuery->whereIn('query', $userServices);
            }
            $leads = $leadsQuery->whereBetween('operation_leads.created_at', [$startDate, $endDate])
                ->orderBy('operation_leads.created_at', 'desc')
                ->get();

            return response()->json([
                'stats' => $stats,
                'leads' => $leads,
                'startDate' => $startDate->toDateString(),
                'endDate' => $endDate->toDateString(),
                'period' => $period
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch leads statistics: ' . $e->getMessage()], 500);
        }
    }

    public function getOutstandingPaymentsStats(Request $request)
    {
        try {
            $user = Auth::user();
            $period = $request->get('period', 'today');
            $customStartDate = $request->get('start_date');
            $customEndDate = $request->get('end_date');
            $selectedMonth = $request->get('selected_month');
            $selectedYear = $request->get('selected_year');

            // Initialize date variables
            $startDate = null;
            $endDate = null;

            switch ($period) {
                case 'today':
                    // For today, show all outstanding invoices (no date filter on invoice period)
                    // But we keep the dates for display purposes
                    $startDate = Carbon::today();
                    $endDate = Carbon::today()->endOfDay();
                    // Don't apply date filter - show all outstanding
                    break;

                case 'monthly':
                    if ($selectedMonth) {
                        $monthParts = explode('-', $selectedMonth);
                        $year = $monthParts[0];
                        $month = $monthParts[1];
                        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
                        $endDate = Carbon::create($year, $month, 1)->endOfMonth();
                    } else {
                        $startDate = Carbon::now()->startOfMonth();
                        $endDate = Carbon::now()->endOfMonth();
                    }
                    // Apply date filter - show invoices with period overlapping selected month
                    break;

                case 'yearly':
                    if ($selectedYear) {
                        $startDate = Carbon::create($selectedYear, 1, 1)->startOfYear();
                        $endDate = Carbon::create($selectedYear, 12, 31)->endOfYear();
                    } else {
                        $startDate = Carbon::now()->startOfYear();
                        $endDate = Carbon::now()->endOfYear();
                    }
                    // Apply date filter - show invoices with period overlapping selected year
                    break;

                case 'custom':
                    if ($customStartDate && $customEndDate) {
                        $startDate = Carbon::parse($customStartDate)->startOfDay();
                        $endDate = Carbon::parse($customEndDate)->endOfDay();
                    } else {
                        return response()->json(['error' => 'Start date and end date are required for custom period'], 400);
                    }
                    // Apply date filter - show invoices with period overlapping custom range
                    break;

                default:
                    // For any other case, show all outstanding (no date filter)
                    break;
            }

            // Get all payment invoices for this user, then filter by outstanding and date
            // For outstanding payments, we show all invoices with outstanding, 
            // then optionally filter by invoice period if period is not 'all'
            $baseQuery = \App\Models\PaymentInvoice::whereHas('operationLead', function($query) use ($user) {
                $query->where('executive', $user->id);
            });

            // For Outstanding Payments Statistics, show ALL outstanding invoices regardless of period
            // The period selector is just for UI organization/display, not for filtering
            // This ensures the modal shows the same data as the dashboard card
            // Don't apply any date filter - show all outstanding invoices

            $paymentInvoices = $baseQuery->with(['operationLead.executive' => function($q) {
                      $q->select('id', 'f_name', 'l_name');
                  }, 'receivedPayments'])
            ->get();

            // Filter invoices with positive outstanding only (pending payments)
            $invoicesWithOutstanding = $paymentInvoices->filter(function($invoice) {
                $totalReceived = $invoice->receivedPayments->sum('amount');
                $outstanding = $invoice->payment_amount - $totalReceived;
                return $outstanding > 0; // Only include invoices with pending payments
            });

            // Calculate total outstanding amount (only positive)
            $totalOutstanding = $invoicesWithOutstanding->sum(function($invoice) {
                $totalReceived = $invoice->receivedPayments->sum('amount');
                return $invoice->payment_amount - $totalReceived;
            });

            // Calculate statistics
            $totalInvoices = $invoicesWithOutstanding->count();
            $avgOutstanding = $totalInvoices > 0 ? $totalOutstanding / $totalInvoices : 0;

            // Format invoice details for frontend (matching expected structure)
            $invoiceDetails = $invoicesWithOutstanding->map(function($invoice) {
                $totalReceived = $invoice->receivedPayments->sum('amount');
                $outstanding = $invoice->payment_amount - $totalReceived;
                
                // Get executive name safely
                $executiveName = 'N/A';
                if ($invoice->operationLead && $invoice->operationLead->executive) {
                    $executive = $invoice->operationLead->executive;
                    if (is_object($executive) && isset($executive->f_name)) {
                        $executiveName = trim(($executive->f_name ?? '') . ' ' . ($executive->l_name ?? ''));
                    }
                }
                
                return [
                    'id' => $invoice->id,
                    'invoice_id' => $invoice->invoice_id,
                    'from_date' => $invoice->from_date->format('Y-m-d'),
                    'to_date' => $invoice->to_date->format('Y-m-d'),
                    'work_days' => $invoice->work_days,
                    'payment_amount' => (float) $invoice->payment_amount,
                    'received_amount' => (float) $totalReceived,
                    'outstanding_amount' => (float) $outstanding,
                    'is_received' => $invoice->is_received,
                    'operation_lead' => [
                        'id' => $invoice->operationLead->id,
                        'lead_id' => $invoice->operationLead->id, // For display as Lead ID
                        'customer_name' => $invoice->operationLead->customer_name,
                        'contact_no' => $invoice->operationLead->contact_no,
                        'location' => $invoice->operationLead->location,
                        'status' => $invoice->operationLead->status,
                        'executive_name' => $executiveName,
                    ]
                ];
            })->values(); // Reset array keys

            return response()->json([
                'success' => true,
                'total_outstanding' => $totalOutstanding,
                'total_payments' => $totalInvoices, // Changed from total_invoices to match JS
                'avg_outstanding' => $avgOutstanding,
                'payment_details' => $invoiceDetails, // Changed from invoice_details to match JS
                'start_date' => $startDate ? $startDate->format('Y-m-d') : null,
                'end_date' => $endDate ? $endDate->format('Y-m-d') : null,
                'period' => $period
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch outstanding payments data: ' . $e->getMessage()], 500);
        }
    }

    public function getDeploymentPendingStats(Request $request)
    {
        try {
            $user = Auth::user();
            $userFilter = $request->get('user_filter', 'mine'); // mine, team, all

            // Build base query based on user filter
            $baseQuery = OperationDeploymentDetails::where('deployment_status', 'Pending');

            if ($userFilter === 'mine') {
                $baseQuery->whereHas('operationLead', function($query) use ($user) {
                    $query->where('executive', $user->id);
                    // Filter by user's assigned services
                    if ($user->services) {
                        $userServices = explode(',', $user->services);
                        $query->whereIn('query', $userServices);
                    }
                });
            } elseif ($userFilter === 'team') {
                $baseQuery->whereHas('operationLead', function($query) use ($user) {
                    $query->where('executive', '!=', $user->id);
                });
            }
            // For 'all', no additional filter is needed

            // Get deployment details without date filtering - show all pending deployments
            $deploymentDetails = $baseQuery->with(['operationLead' => function($query) {
                $query->select('id', 'lead_id', 'customer_name', 'contact_no', 'location', 'status', 'created_at', 'executive')
                      ->with(['executive' => function($q) {
                          $q->select('id', 'f_name', 'l_name');
                      }]);
            }, 'vendor', 'freelanceStaff'])->get();

            // Calculate statistics
            $totalPending = $deploymentDetails->count();

            // Calculate total deployments with same user filter (all time)
            $totalDeploymentsQuery = OperationDeploymentDetails::query();

            if ($userFilter === 'mine') {
                $totalDeploymentsQuery->whereHas('operationLead', function($query) use ($user) {
                    $query->where('executive', $user->id);
                    // Filter by user's assigned services
                    if ($user->services) {
                        $userServices = explode(',', $user->services);
                        $query->whereIn('query', $userServices);
                    }
                });
            } elseif ($userFilter === 'team') {
                $totalDeploymentsQuery->whereHas('operationLead', function($query) use ($user) {
                    $query->where('executive', '!=', $user->id);
                });
            }

            $totalDeployments = $totalDeploymentsQuery->count();
            $pendingPercentage = $totalDeployments > 0 ? ($totalPending / $totalDeployments) * 100 : 0;

            return response()->json([
                'success' => true,
                'total_pending' => $totalPending,
                'total_deployments' => $totalDeployments,
                'pending_percentage' => $pendingPercentage,
                'deployment_details' => $deploymentDetails,
                'user_filter' => $userFilter
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch deployment pending data: ' . $e->getMessage()], 500);
        }
    }

    public function getProfilePendingStats(Request $request)
    {
        try {
            $user = Auth::user();
            $userFilter = $request->get('user_filter', 'mine'); // mine, team, all

            // Base query for profile pending leads
            $baseQuery = OperationLead::where('status', 'profile pending');

            // Apply user filter
            if ($userFilter === 'mine') {
                $baseQuery->where('executive', $user->id);
                // Filter by user's assigned services (if services are set for this user)
                if ($user->services) {
                    $userServices = explode(',', $user->services);
                    $baseQuery->whereIn('query', $userServices);
                }
            } elseif ($userFilter === 'team') {
                $baseQuery->where('executive', '!=', $user->id);
            }
            // For 'all', no additional filter is applied

            // Get all profile pending leads (no date filtering)
            $profilePendingLeads = $baseQuery
                ->select('id', 'lead_id', 'customer_name', 'contact_no', 'location', 'query', 'query_remark', 'status', 'created_at', 'date_time', 'executive', 'patient_name', 'patient_gender', 'shift_type')
                ->with(['executive' => function($query) {
                    $query->select('id', 'f_name', 'l_name');
                }])
                ->get();

            // Calculate statistics
            $totalProfilePending = $profilePendingLeads->count();

            // Calculate total leads with same user filter (no date filtering)
            $totalLeadsQuery = OperationLead::query();

            if ($userFilter === 'mine') {
                $totalLeadsQuery->where('executive', $user->id);
            } elseif ($userFilter === 'team') {
                $totalLeadsQuery->where('executive', '!=', $user->id);
            }

            $totalLeads = $totalLeadsQuery->count();
            $profilePendingPercentage = $totalLeads > 0 ? ($totalProfilePending / $totalLeads) * 100 : 0;

            return response()->json([
                'success' => true,
                'total_profile_pending' => $totalProfilePending,
                'total_leads' => $totalLeads,
                'profile_pending_percentage' => $profilePendingPercentage,
                'profile_pending_leads' => $profilePendingLeads,
                'user_filter' => $userFilter
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch profile pending data: ' . $e->getMessage()], 500);
        }
    }

    public function getPaymentDueStats(Request $request)
    {
        try {
            $user = Auth::user();
            $period = $request->get('period', 'today');
            $startDate = $request->get('start_date');
            $endDate = $request->get('end_date');
            $selectedMonth = $request->get('selected_month');
            $selectedYear = $request->get('selected_year');

            // Build base query for unverified payments
            $baseQuery = OperationDeploymentDetails::whereHas('operationLead', function($query) use ($user) {
                    $query->where('executive', $user->id);
                })
                ->where('verify_payment', false)
                ->whereNotNull('vendor_payment')
                ->where('vendor_payment', '>', 0);

            switch ($period) {
                case 'today':
                    $startDate = Carbon::today();
                    $endDate = Carbon::today()->endOfDay();
                    $groupBy = 'HOUR(operation_deployment_details.created_at)';
                    break;

                case 'monthly':
                    if ($selectedMonth) {
                        $monthParts = explode('-', $selectedMonth);
                        $year = $monthParts[0];
                        $month = $monthParts[1];
                        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
                        $endDate = Carbon::create($year, $month, 1)->endOfMonth();
                    } else {
                        $startDate = Carbon::now()->startOfMonth();
                        $endDate = Carbon::now()->endOfMonth();
                    }
                    $groupBy = 'DATE(operation_deployment_details.created_at)';
                    break;

                case 'yearly':
                    if ($selectedYear) {
                        $startDate = Carbon::create($selectedYear, 1, 1)->startOfYear();
                        $endDate = Carbon::create($selectedYear, 12, 31)->endOfYear();
                    } else {
                        $startDate = Carbon::now()->startOfYear();
                        $endDate = Carbon::now()->endOfYear();
                    }
                    $groupBy = 'MONTH(operation_deployment_details.created_at)';
                    break;

                case 'custom':
                    if ($startDate && $endDate) {
                        $startDate = Carbon::parse($startDate)->startOfDay();
                        $endDate = Carbon::parse($endDate)->endOfDay();
                    } else {
                        $startDate = Carbon::now()->startOfMonth();
                        $endDate = Carbon::now()->endOfMonth();
                    }
                    $groupBy = 'DATE(operation_deployment_details.created_at)';
                    break;

                default:
                    $startDate = Carbon::today();
                    $endDate = Carbon::today()->endOfDay();
                    $groupBy = 'HOUR(operation_deployment_details.created_at)';
                    break;
            }

            // Apply date filter
            $baseQuery->whereBetween('operation_deployment_details.created_at', [$startDate, $endDate]);

            // Get total count
            $totalCount = $baseQuery->count();

            // Get total amount
            $totalAmount = $baseQuery->sum('vendor_payment');

            // Get detailed data for chart (create new query instance)
            $chartData = OperationDeploymentDetails::whereHas('operationLead', function($query) use ($user) {
                    $query->where('executive', $user->id);
                })
                ->where('verify_payment', false)
                ->whereNotNull('vendor_payment')
                ->where('vendor_payment', '>', 0)
                ->whereBetween('operation_deployment_details.created_at', [$startDate, $endDate])
                ->selectRaw("
                    {$groupBy} as period,
                    COUNT(*) as count,
                    SUM(vendor_payment) as total_amount
                ")
                ->groupBy('period')
                ->orderBy('period')
                ->get();

            // Get detailed list of unverified payments with proper joins (create new query instance)
            $payments = OperationDeploymentDetails::whereHas('operationLead', function($query) use ($user) {
                    $query->where('executive', $user->id);
                })
                ->where('verify_payment', false)
                ->whereNotNull('vendor_payment')
                ->where('vendor_payment', '>', 0)
                ->whereBetween('operation_deployment_details.created_at', [$startDate, $endDate])
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
                ->limit(50)
                ->get()
                ->map(function($payment) {
                    $viewUrl = null;
                    if ($payment->operation_lead_id) {
                        try {
                            $viewUrl = route('operation.operation_leads.show', $payment->operation_lead_id);
                        } catch (\Exception $e) {
                            $viewUrl = '#';
                        }
                    } else {
                        $viewUrl = '#';
                    }

                    return [
                        'id' => $payment->id,
                        'lead_id' => '#' . $payment->lead_id,
                        'customer_name' => $payment->customer_name ?: 'N/A',
                        'vendor_name' => $payment->vendor_name ?: 'N/A',
                        'staff_name' => $payment->staff_name ?: 'N/A',
                        'vendor_payment' => $payment->vendor_payment ?: 0,
                        'deployment_date' => $payment->deployment_date ? Carbon::parse($payment->deployment_date)->format('M d, Y') : 'N/A',
                        'created_at' => Carbon::parse($payment->created_at)->format('M d, Y H:i'),
                        'view_url' => $viewUrl
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => [
                    'total_count' => $totalCount,
                    'total_amount' => $totalAmount,
                    'chart_data' => $chartData,
                    'payments' => $payments,
                'period' => $period,
                    'start_date' => $startDate->format('Y-m-d'),
                    'end_date' => $endDate->format('Y-m-d')
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Error getting payment due stats: ' . $e->getMessage());
            \Log::error('Payment due stats error trace: ' . $e->getTraceAsString());
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving payment due statistics: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getOngoingStoppedStats(Request $request)
    {
        try {
            $user = Auth::user();
            $period = $request->get('period', 'today');
            $startDate = $request->get('start_date');
            $endDate = $request->get('end_date');
            $selectedMonth = $request->get('selected_month');
            $selectedYear = $request->get('selected_year');

            $baseQuery = OperationLead::where('executive', $user->id)
                ->where('ongoing_stopped', 'ongoing');

            // Filter by user's assigned services (if services are set for this user)
            if ($user->services) {
                $userServices = explode(',', $user->services);
                $baseQuery->whereIn('query', $userServices);
            }

            switch ($period) {
                case 'today':
                    $startDate = Carbon::today();
                    $endDate = Carbon::today()->endOfDay();
                    break;

                case 'monthly':
                    if ($selectedMonth) {
                        $monthParts = explode('-', $selectedMonth);
                        $year = $monthParts[0];
                        $month = $monthParts[1];
                        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
                        $endDate = Carbon::create($year, $month, 1)->endOfMonth();
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
                        return response()->json(['error' => 'Start date and end date are required for custom period'], 400);
                    }
                    break;

                default:
                    return response()->json(['error' => 'Invalid period'], 400);
            }

            // Get ongoing leads with date filtering
            $ongoingLeads = $baseQuery->whereBetween('created_at', [$startDate, $endDate])
                ->select('id', 'lead_id', 'customer_name', 'contact_no', 'location', 'query', 'status', 'ongoing_stopped', 'created_at', 'date_time')
                ->get();

            // Calculate statistics
            $totalOngoing = $ongoingLeads->count();
            $totalLeads = OperationLead::where('executive', $user->id)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->count();
            $ongoingPercentage = $totalLeads > 0 ? ($totalOngoing / $totalLeads) * 100 : 0;

            return response()->json([
                'success' => true,
                'total_ongoing' => $totalOngoing,
                'total_leads' => $totalLeads,
                'ongoing_percentage' => $ongoingPercentage,
                'ongoing_leads' => $ongoingLeads,
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
                'period' => $period
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch ongoing/stopped data: ' . $e->getMessage()], 500);
        }
    }

    public function getTasks(Request $request)
    {
        try {
            $type = $request->get('type', 'assigned_to_me');
            $startDate = $request->get('start_date');
            $endDate = $request->get('end_date');
            $userId = auth()->user()->id;
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
                    $query->where('assigned_to', $userId)
                          ->where('assigned_by', $parentManagerId);
                    break;
                case 'history':
                    // Show all tasks where user is involved (assigned to, assigned by, or assigned by parent manager)
                    $query->where(function($q) use ($userId, $parentManagerId) {
                        $q->where('assigned_to', $userId)
                          ->orWhere('assigned_by', $userId);
                        if ($parentManagerId) {
                            $q->orWhere(function($subQ) use ($userId, $parentManagerId) {
                                $subQ->where('assigned_to', $userId)
                                     ->where('assigned_by', $parentManagerId);
                            });
                        }
                    });
                    break;
                case 'all':
                default:
                    $query->where(function($q) use ($userId, $parentManagerId) {
                        $q->where('assigned_to', $userId)
                          ->orWhere('assigned_by', $userId)
                          ->orWhere(function($subQ) use ($userId, $parentManagerId) {
                              $subQ->where('assigned_to', $userId)
                                   ->where('assigned_by', $parentManagerId);
                          });
                    });
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
                'due_date' => 'required|date|after:today'
            ]);

            $task = Task::create([
                'title' => $request->title,
                'description' => $request->description,
                'priority' => $request->priority,
                'due_date' => $request->due_date,
                'status' => 'pending',
                'assigned_to' => auth()->user()->id,
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

            // Check if user has permission to view this task
            $userId = auth()->user()->id;
            $parentManagerId = User::where('id', $userId)->value('parent_id');

            if ($task->assigned_to != $userId &&
                $task->assigned_by != $userId &&
                !($task->assigned_to == $userId && $task->assigned_by == $parentManagerId)) {
                return response()->json(['error' => 'Unauthorized access'], 403);
            }

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

            // Check if user has permission to edit this task
            $userId = auth()->user()->id;
            if ($task->assigned_by != $userId) {
                return response()->json(['error' => 'Unauthorized access'], 403);
            }

            $request->validate([
                'title' => 'required|string|max:255',
                'description' => 'nullable|string',
                'priority' => 'required|in:low,medium,high,urgent',
                'due_date' => 'required|date',
                'status' => 'required|in:pending,in_progress,completed,cancelled'
            ]);

            $task->update($request->all());

            return response()->json([
                'success' => true,
                'message' => 'Task updated successfully',
                'task' => $task
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to update task: ' . $e->getMessage()], 500);
        }
    }

    public function updateTaskStatus(Request $request, $id)
    {
        try {
            $task = Task::findOrFail($id);

            // Check if user is assigned to this task
            if ($task->assigned_to != auth()->user()->id) {
                return response()->json(['error' => 'Unauthorized access'], 403);
            }

            $request->validate([
                'status' => 'required|in:pending,in_progress,completed,cancelled'
            ]);

            $task->update(['status' => $request->status]);

            return response()->json([
                'success' => true,
                'message' => 'Task status updated successfully',
                'task' => $task
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to update task status: ' . $e->getMessage()], 500);
        }
    }

    public function destroyTask($id)
    {
        try {
            $task = Task::findOrFail($id);

            // Check if user has permission to delete this task
            if ($task->assigned_by != auth()->user()->id) {
                return response()->json(['error' => 'Unauthorized access'], 403);
            }

            $task->delete();

            return response()->json([
                'success' => true,
                'message' => 'Task deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to delete task: ' . $e->getMessage()], 500);
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

            // Get follow-up leads (operation leads with status 'follow up' based on date_time field)
            $followUpQuery = OperationLead::where('executive', $user->id)->where('status', 'follow up');
            if ($user->services) {
                $userServices = explode(',', $user->services);
                $followUpQuery->whereIn('query', $userServices);
            }
            $followUpLeads = $followUpQuery->whereBetween('date_time', [$startDate, $endDate])->get();

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

            // Get follow-up leads (operation leads with status 'follow up' based on date_time field)
            $followUpQuery = OperationLead::where('executive', $user->id)->where('status', 'follow up');
            if ($user->services) {
                $userServices = explode(',', $user->services);
                $followUpQuery->whereIn('query', $userServices);
            }
            $followUpLeads = $followUpQuery->whereBetween('date_time', [$startOfDay, $endOfDay])
                ->select('id', 'lead_id', 'customer_name', 'contact_no', 'location', 'query', 'status', 'date_time')
                ->get();

            return response()->json([
                'leads' => $followUpLeads,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch leads for date: ' . $e->getMessage()], 500);
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
                            'lead_id' => $jobRequest->lead_id ?: ('#' . $jobRequest->id),
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
            \Log::error('Error getting job request stats: ' . $e->getMessage());
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

            // Get unverified deployment payments for current user's leads
            $unverifiedDeployments = OperationDeploymentDetails::with([
                'operationLead' => function($query) {
                    $query->select('id', 'lead_id', 'customer_name', 'contact_no', 'location', 'executive');
                },
                'vendor',
                'freelanceStaff'
            ])
            ->whereHas('operationLead', function($query) use ($user) {
                $query->where('executive', $user->id);
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
                    'view_url' => route('operation.operation_leads.show', $deployment->operation_lead_id),
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
                            'lead_id' => $lead ? ($lead->lead_id ?: ('#' . $lead->id)) : 'N/A',
                            'customer_name' => $lead ? $lead->customer_name : 'N/A',
                            'contact_no' => $lead ? $lead->contact_no : 'N/A',
                            'location' => $lead ? $lead->location : 'N/A',
                            'vendor_staff' => $vendorStaff,
                            'vendor_payment' => $payment->vendor_payment,
                            'deployment_date' => $payment->deployment_date ? \Carbon\Carbon::parse($payment->deployment_date)->format('d/m/Y') : 'N/A',
                            'created_at' => $payment->created_at ? \Carbon\Carbon::parse($payment->created_at)->format('d/m/Y H:i') : 'N/A',
                            'view_url' => $lead ? route('operation.operation_leads.show', $lead->id) : '#'
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
            
            // Get 25 most recent leads based on created_at (when they were added to the system)
            // Using join to get executive name directly
            $recentLeads = OperationLead::leftJoin('users', 'operation_leads.executive', '=', 'users.id')
                ->where('operation_leads.executive', $user->id)
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
            $latestQuery = OperationLead::where('executive', $user->id);
            if ($user->services) {
                $userServices = explode(',', $user->services);
                $latestQuery->whereIn('query', $userServices);
            }
            $latestLeadDate = $latestQuery->orderBy('created_at', 'desc')->value('created_at');

            return response()->json([
                'success' => true,
                'data' => [
                    'total_count' => $recentLeads->count(),
                    'latest_lead_date' => $latestLeadDate,
                    'leads' => $recentLeads,
                ],
            ]);
        } catch (\Exception $e) {
            \Log::error('Error getting recent leads: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch recent leads statistics: ' . $e->getMessage(),
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
            $callLogs = CallDetails::where('executive_id', $user->id)
                ->where('call_for', 'operation_lead')
                ->where('call_received_datetime', '>=', $tenMinutesAgo)
                ->orderBy('call_received_datetime', 'desc')
                ->get();
            
            $activeCalls = [];
            $processedNumbers = []; // Track already processed numbers
            
            foreach ($callLogs as $log) {
                // Use caller_id_number to find the associated lead
                $contactNumber = $log->caller_id_number;
                
                // Skip if we've already processed this number (show only most recent call per number)
                if (in_array($contactNumber, $processedNumbers)) {
                    continue;
                }
                
                $processedNumbers[] = $contactNumber;
                $normalizedNumber = $this->normalizePhoneNumber($contactNumber);

                 // Skip if we've already processed this normalized number (show only most recent call per number)
            if (in_array($normalizedNumber, $processedNumbers)) {
                continue;
            }
            $processedNumbers[] = $normalizedNumber;
            $lead = OperationLead::where(function($query) use ($contactNumber, $normalizedNumber, $user) {
                $query->where('contact_no', $contactNumber)
                      ->orWhere('contact_no', $normalizedNumber)
                      ->orWhere('contact_no', '91' . $normalizedNumber)
                      ->orWhere('contact_no', '0' . $normalizedNumber);
            })
                ->where('executive', $user->id)
                ->orderBy('created_at', 'desc')
                ->first();
                
                if ($lead) {
                    $callTime = \Carbon\Carbon::parse($log->call_received_datetime);
                    $minutesAgo = $callTime->diffInMinutes($now);
                    
                    // Consider call as "active" if it's less than 2 minutes old
                    $isActive = $minutesAgo < 2;
                    
                    $activeCalls[] = [
                        'operation_lead_id' => $lead->id,
                        'lead_id' => $lead->lead_id,
                        'customer_name' => $lead->customer_name,
                        'patient_name' => $lead->patient_name,
                        'contact_no' => $lead->contact_no,
                        'location' => $lead->location,
                        'query' => $lead->query,
                        'lead_status' => $lead->status,
                        'ongoing_stopped' => $lead->ongoing_stopped,
                        'call_time' => $log->call_received_datetime,
                        'call_direction' => 'Incoming',
                        'call_status' => $log->call_status,
                        'agent_name' => $log->agent_name,
                        'agent_number' => $log->agent_number,
                        'is_active' => $isActive,
                        'minutes_ago' => $minutesAgo,
                        'call_log_id' => $log->id
                    ];
                }
            }
            
            return response()->json([
                'success' => true,
                'calls' => $activeCalls
            ]);
        } catch (\Exception $e) {
            \Log::error('Error getting active calls (Operation): ' . $e->getMessage());
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
            // Only include calls for OperationLead and JobRequest (not regular Leads)
            $allCallLogsQuery = CallDetails::select('call_details.*')
                ->where('call_details.executive_id', $user->id)
                ->whereIn('call_details.call_for', ['operation_lead', 'job_request']) // Only OperationLead and JobRequest calls
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

                // Try to find associated operation lead or job request by contact number
                $lead = null;
                $jobRequest = null;
                $leadCode = 'No Lead';
                $customerName = 'N/A';
                $viewUrl = null;
                $relatedId = null;
                
                // Check call_for to determine what to find
                if ($callLog->call_for === 'operation_lead') {
                    $lead = OperationLead::where(function($query) use ($contactNumber, $normalizedNumber, $user) {
                        $query->where('contact_no', $contactNumber)
                              ->orWhere('contact_no', $normalizedNumber)
                              ->orWhere('contact_no', '91' . $normalizedNumber)
                              ->orWhere('contact_no', '0' . $normalizedNumber);
                    })
                        ->where('executive', $user->id)
                        ->orderBy('created_at', 'desc')
                        ->first();
                    if ($lead) {
                        $leadCode = $lead->lead_id ?: 'OP' . str_pad($lead->id, 8, '0', STR_PAD_LEFT);
                        $customerName = $lead->customer_name ?: 'N/A';
                        $viewUrl = route('operation.operation_leads.show', $lead->id);
                        $relatedId = $lead->id;
                    }
                } elseif ($callLog->call_for === 'job_request') {
                    $jobRequest = JobRequest::where(function($query) use ($contactNumber, $normalizedNumber, $user) {
                        $query->where('contact_no', $contactNumber)
                              ->orWhere('contact_no', $normalizedNumber)
                              ->orWhere('contact_no', '91' . $normalizedNumber)
                              ->orWhere('contact_no', '0' . $normalizedNumber);
                    })
                        ->where('executive_id', $user->id)
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
            \Log::error('Error getting pending callbacks (Operation): ' . $e->getMessage());
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
            // Only include calls for OperationLead and JobRequest (not regular Leads)
            $oneWeekAgo = now()->subWeek();
            
            $callLogs = CallDetails::where('executive_id', $user->id)
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
                    $lead = OperationLead::where(function($query) use ($contactNumber, $normalizedNumber, $user) {
                        $query->where('contact_no', $contactNumber)
                              ->orWhere('contact_no', $normalizedNumber)
                              ->orWhere('contact_no', '91' . $normalizedNumber)
                              ->orWhere('contact_no', '0' . $normalizedNumber);
                    })
                        ->where('executive', $user->id)
                        ->orderBy('created_at', 'desc')
                        ->first();
                    if ($lead) {
                        $leadCode = $lead->lead_id ?: 'OP' . str_pad($lead->id, 8, '0', STR_PAD_LEFT);
                        $customerName = $lead->customer_name ?: 'N/A';
                        $viewUrl = route('operation.operation_leads.show', $lead->id);
                        $relatedId = $lead->id;
                    }
                } elseif ($callLog->call_for === 'job_request') {
                    $jobRequest = JobRequest::where(function($query) use ($contactNumber, $normalizedNumber, $user) {
                        $query->where('contact_no', $contactNumber)
                              ->orWhere('contact_no', $normalizedNumber)
                              ->orWhere('contact_no', '91' . $normalizedNumber)
                              ->orWhere('contact_no', '0' . $normalizedNumber);
                    })
                        ->where('executive_id', $user->id)
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
                
                $recentCalls[] = [
                    'lead_no' => $leadCode,
                    'operation_lead_id' => $relatedId,
                    'call_datetime' => $callLog->call_received_datetime ? $callLog->call_received_datetime->format('d-M-Y H:i:s') : 'N/A',
                    'customer_name' => $customerName,
                    'customer_number' => $contactNumber,
                    'call_status' => $callLog->call_status,
                    'call_for' => $callLog->call_for ?? 'N/A', // Show call type
                    'call_for_display' => $callLog->call_for === 'operation_lead' ? 'Operation Lead' : ($callLog->call_for === 'job_request' ? 'Job Request' : 'N/A'),
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
            \Log::error('Error getting recent calls (Operation): ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Failed to load recent calls data: ' . $e->getMessage()
            ], 500);
        }
    }

}
