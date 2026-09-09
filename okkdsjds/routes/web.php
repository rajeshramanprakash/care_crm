<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers;
use App\Http\Controllers\Controller;
use App\Models\VendorCase;
use App\Models\User;
use App\Models\BreakLog;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\LeadController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\OperationLeadController;
use App\Http\Controllers\LeadMatchController;
use App\Http\Controllers\LeadOperationSyncController;
use App\Facades\UserAssignment;
use App\Services\OutboundCall;
use Illuminate\Http\Request;
use App\Http\Controllers\WhatsappMsgController;
use App\Http\Controllers\GroupChatController;
use App\Http\Controllers\Admin\ServiceController;
use Illuminate\Support\Facades\Http;
use App\Models\DutyLogs;

/*
|----------------------------------------------------------------------
| Web Routes
|----------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/test', function () {
    $user = UserAssignment::getAssigningUser(1)->id;
    return $user;
});

// Route to migrate existing call logs to call_details table
Route::get('/migrate-call-logs', function () {
    try {
        $callLogs = \App\Models\CallLog::all();
        $migratedCount = 0;
        $skippedCount = 0;
        
        foreach ($callLogs as $callLog) {
            // Check if already migrated
            $existing = \App\Models\CallDetails::where('caller_id_number', $callLog->caller_id_number)
                ->where('call_received_datetime', $callLog->call_received_datetime)
                ->first();
                
            if ($existing) {
                $skippedCount++;
                continue;
            }
            
            // Migrate to call_details
            \App\Models\CallDetails::create([
                'lead_id' => $callLog->lead_id,
                'executive_id' => $callLog->executive_id,
                'caller_id_number' => $callLog->caller_id_number,
                'agent_number' => $callLog->agent_number,
                'agent_name' => $callLog->agent_name,
                'call_status' => $callLog->call_status,
                'recording_url' => $callLog->recording_url,
                'call_received_datetime' => $callLog->call_received_datetime,
                'customer_name' => $callLog->customer_name,
                'lead_code' => $callLog->lead_code,
                'is_processed' => $callLog->is_processed,
                'call_type' => 'inbound',
                'call_source' => 'migrated',
                'raw_data' => null
            ]);
            
            $migratedCount++;
        }
        
        return response()->json([
            'success' => true,
            'message' => 'Call logs migration completed successfully',
            'migrated_count' => $migratedCount,
            'skipped_count' => $skippedCount,
            'total_original' => $callLogs->count()
        ]);
        
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => 'Migration failed: ' . $e->getMessage()
        ], 500);
    }
});

Route::get('/prospect-operation-lead-matches', [LeadMatchController::class, 'prospectMatches']);
Route::get('/sync-prospect-operation-leads', [LeadOperationSyncController::class, 'createFromProspects']);

// Comprehensive route to migrate call details from all sources (leads, operation leads, job requests)
Route::get('/migrate-all-call-details', function () {
    try {
        $migratedCount = 0;
        $skippedCount = 0;
        $errors = [];
        
        // 1. Migrate from CallLogs table
        $callLogs = \App\Models\CallLog::all();
        foreach ($callLogs as $callLog) {
            try {
                $existing = \App\Models\CallDetails::where('caller_id_number', $callLog->caller_id_number)
                    ->where('call_received_datetime', $callLog->call_received_datetime)
                    ->first();
                    
                if ($existing) {
                    $skippedCount++;
                    continue;
                }
                
                \App\Models\CallDetails::create([
                    'lead_id' => $callLog->lead_id,
                    'executive_id' => $callLog->executive_id,
                    'caller_id_number' => $callLog->caller_id_number,
                    'agent_number' => $callLog->agent_number,
                    'agent_name' => $callLog->agent_name,
                    'call_status' => $callLog->call_status,
                    'recording_url' => $callLog->recording_url,
                    'call_received_datetime' => $callLog->call_received_datetime,
                    'customer_name' => $callLog->customer_name,
                    'lead_code' => $callLog->lead_code,
                    'is_processed' => $callLog->is_processed,
                    'call_type' => 'inbound',
                    'call_source' => 'call_logs_migrated',
                    'raw_data' => null
                ]);
                
                $migratedCount++;
            } catch (\Exception $e) {
                $errors[] = "CallLog ID {$callLog->id}: " . $e->getMessage();
            }
        }
        
        // 2. Migrate from Leads table (extract from recording_url JSON)
        $leads = \App\Models\Lead::whereNotNull('recording_url')
            ->where('recording_url', '!=', '')
            ->where('recording_url', '!=', '[]')
            ->get();
            
        foreach ($leads as $lead) {
            try {
                $recordings = json_decode($lead->recording_url, true);
                if (!is_array($recordings)) continue;
                
                foreach ($recordings as $recording) {
                    if (!isset($recording['metadata'])) continue;
                    
                    $metadata = json_decode($recording['metadata'], true);
                    if (!is_array($metadata)) continue;
                    
                    $callDateTime = isset($recording['timestamp']) 
                        ? \Carbon\Carbon::parse($recording['timestamp'])
                        : $lead->created_at;
                    
                    // Check if already exists
                    $existing = \App\Models\CallDetails::where('lead_id', $lead->id)
                        ->where('caller_id_number', $lead->contact_no)
                        ->where('call_received_datetime', $callDateTime)
                        ->first();
                        
                    if ($existing) {
                        $skippedCount++;
                        continue;
                    }
                    
                    \App\Models\CallDetails::create([
                        'lead_id' => $lead->id,
                        'executive_id' => $lead->executive,
                        'caller_id_number' => $lead->contact_no,
                        'agent_number' => $metadata['agent_number'] ?? 'Unknown',
                        'agent_name' => $metadata['agent_name'] ?? 'Unknown',
                        'call_status' => $metadata['dialstatus'] ?? $lead->last_call_status ?? 'unknown',
                        'recording_url' => $recording['url'] ?? null,
                        'call_received_datetime' => $callDateTime,
                        'customer_name' => $lead->customer_name,
                        'lead_code' => $lead->formatted_id,
                        'is_processed' => true,
                        'call_type' => 'inbound',
                        'call_source' => 'leads_migrated',
                        'raw_data' => $recording
                    ]);
                    
                    $migratedCount++;
                }
            } catch (\Exception $e) {
                $errors[] = "Lead ID {$lead->id}: " . $e->getMessage();
            }
        }
        
        // 3. Migrate from Operation Leads table
        $operationLeads = \App\Models\OperationLead::whereNotNull('recording_url')
            ->where('recording_url', '!=', '')
            ->where('recording_url', '!=', '[]')
            ->get();
            
        foreach ($operationLeads as $opLead) {
            try {
                $recordings = json_decode($opLead->recording_url, true);
                if (!is_array($recordings)) continue;
                
                foreach ($recordings as $recording) {
                    if (!isset($recording['metadata'])) continue;
                    
                    $metadata = json_decode($recording['metadata'], true);
                    if (!is_array($metadata)) continue;
                    
                    $callDateTime = isset($recording['timestamp']) 
                        ? \Carbon\Carbon::parse($recording['timestamp'])
                        : $opLead->created_at;
                    
                    // Check if already exists
                    $existing = \App\Models\CallDetails::where('caller_id_number', $opLead->contact_no)
                        ->where('call_received_datetime', $callDateTime)
                        ->where('call_source', 'operation_leads_migrated')
                        ->first();
                        
                    if ($existing) {
                        $skippedCount++;
                        continue;
                    }
                    
                    \App\Models\CallDetails::create([
                        'lead_id' => $opLead->id,
                        'executive_id' => $opLead->executive,
                        'caller_id_number' => $opLead->contact_no,
                        'agent_number' => $metadata['agent_number'] ?? 'Unknown',
                        'agent_name' => $metadata['agent_name'] ?? 'Unknown',
                        'call_status' => $metadata['dialstatus'] ?? $opLead->last_call_status ?? 'unknown',
                        'recording_url' => $recording['url'] ?? null,
                        'call_received_datetime' => $callDateTime,
                        'customer_name' => $opLead->customer_name,
                        'lead_code' => $opLead->lead_id,
                        'is_processed' => true,
                        'call_type' => 'inbound',
                        'call_source' => 'operation_leads_migrated',
                        'raw_data' => $recording
                    ]);
                    
                    $migratedCount++;
                }
            } catch (\Exception $e) {
                $errors[] = "OperationLead ID {$opLead->id}: " . $e->getMessage();
            }
        }
        
        // 4. Migrate from Job Requests table
        $jobRequests = \App\Models\JobRequest::whereNotNull('recording_url')
            ->where('recording_url', '!=', '')
            ->where('recording_url', '!=', '[]')
            ->get();
            
        foreach ($jobRequests as $jobRequest) {
            try {
                $recordings = json_decode($jobRequest->recording_url, true);
                if (!is_array($recordings)) continue;
                
                foreach ($recordings as $recording) {
                    if (!isset($recording['metadata'])) continue;
                    
                    $metadata = json_decode($recording['metadata'], true);
                    if (!is_array($metadata)) continue;
                    
                    $callDateTime = isset($recording['timestamp']) 
                        ? \Carbon\Carbon::parse($recording['timestamp'])
                        : $jobRequest->created_at;
                    
                    // Check if already exists
                    $existing = \App\Models\CallDetails::where('caller_id_number', $jobRequest->contact_no)
                        ->where('call_received_datetime', $callDateTime)
                        ->where('call_source', 'job_requests_migrated')
                        ->first();
                        
                    if ($existing) {
                        $skippedCount++;
                        continue;
                    }
                    
                    \App\Models\CallDetails::create([
                        'lead_id' => $jobRequest->id,
                        'executive_id' => $jobRequest->executive_id,
                        'caller_id_number' => $jobRequest->contact_no,
                        'agent_number' => $metadata['agent_number'] ?? 'Unknown',
                        'agent_name' => $metadata['agent_name'] ?? 'Unknown',
                        'call_status' => $metadata['dialstatus'] ?? $jobRequest->last_call_status ?? 'unknown',
                        'recording_url' => $recording['url'] ?? null,
                        'call_received_datetime' => $callDateTime,
                        'customer_name' => $jobRequest->customer_name,
                        'lead_code' => $jobRequest->lead_id,
                        'is_processed' => true,
                        'call_type' => 'inbound',
                        'call_source' => 'job_requests_migrated',
                        'raw_data' => $recording
                    ]);
                    $migratedCount++;
                }
            } catch (\Exception $e) {
                $errors[] = "JobRequest ID {$jobRequest->id}: " . $e->getMessage();
            }
        }
        
        return response()->json([
            'success' => true,
            'message' => 'Comprehensive call details migration completed',
            'migrated_count' => $migratedCount,
            'skipped_count' => $skippedCount,
            'total_sources' => [
                'call_logs' => $callLogs->count(),
                'leads' => $leads->count(),
                'operation_leads' => $operationLeads->count(),
                'job_requests' => $jobRequests->count()
            ],
            'errors' => $errors
        ]);
        
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => 'Migration failed: ' . $e->getMessage()
        ], 500);
    }
});




Route::get('operation-leads/generate-invoices', function () {
    try {
        \Artisan::call('invoices:generate');
        $output = \Artisan::output();
        return response()->json([
            'success' => true,
            'message' => 'Invoice generation command executed successfully',
            'output' => $output
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Error generating invoices: ' . $e->getMessage()
        ], 500);
    }
});


// run per min
Route::get('make_whatsapp_grp_from_all_leads', [WhatsappMsgController::class, 'make_whatsapp_grp_from_all_leads']);

// run per 10 days
// Route::get('whatsapp_template_msg_send_service_status_checkin_cron', [WhatsappTemplateMsgController::class, 'whatsapp_template_msg_send_service_status_checkin_cron']);



Route::get('call-outbound/{customer_number}', function ($customer_number) {
    return OutboundCall::call($customer_number);
})->name('call-outbound');

Route::get('break_active/{id}/{is_break}/{break_reason?}', function($id, $is_break, $break_reason = null){
    $user =  User::where('id', $id)->first();

    if (!$user) {
        return response()->json(['error' => 'User not found'], 404);
    }

    $user->is_break = $is_break;
    $user->save();

    // Tata API integration for break status
    if ($user->tata_agent_id) {
        try {
            $tataService = new \App\Services\TataService();
            
            if ($is_break == '1') {
                // User going on break - block and disable agent
                $tataService->updateAgent($user, $user->tata_agent_id, [
                    'block_agent' => true,
                    'block_web_login' => true,
                    'disable_agent' => true,
                    'login_based_calling' => false
                ]);
            } else {
                // User coming back from break - unblock and enable agent
                $tataService->updateAgent($user, $user->tata_agent_id, [
                    'block_agent' => false,
                    'block_web_login' => false,
                    'disable_agent' => false,
                    'login_based_calling' => false
                ]);
            }
        } catch (\Exception $e) {
            \Log::error('Tata API error during break status update', [
                'user_id' => $user->id,
                'tata_agent_id' => $user->tata_agent_id,
                'is_break' => $is_break,
                'error' => $e->getMessage()
            ]);
        }
    }

    $payload = [
        'Authorization'  => 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJUSEVfQ0xBSU0iLCJhdWQiOiJUSEVfQVVESUVOQ0UiLCJpYXQiOjE3NTAwNTg0MDUsImV4cF9kYXRhIjoxNzgxNTk0NDA1LCJkYXRhIjp7ImJpZCI6IjYyODUifX0.p_lUHQLOXkPwk9QlOlu32MLvszJwhUSN6jrHBqxiLlo',
        'email' => $user->email,
        'break' => $is_break,
    ];

    if ($is_break == '1' && $break_reason) {
        $payload['break_reason'] = $break_reason;
    }

    // $response = Http::withHeaders([
    //     'Content-Type' => 'application/json',
    // ])->post('https://api.mcube.com/Break-api/break-api', $payload);
    // $breakStatus = $is_break == '1' ? 'offline' : 'online';

    if ($is_break == '1') {
        \App\Models\BreakLog::create([
            'user_id' => $user->id,
            'break_status' => 'offline',
            'break_reason' => $break_reason,
            'break_start_time' => now(),
            // 'api_response' => $response->json()
        ]);
    } else {
        $lastBreak = \App\Models\BreakLog::where('user_id', $user->id)
            ->where('break_status', 'offline')
            ->whereNull('break_end_time')
            ->latest()
            ->first();

        if ($lastBreak) {
            $lastBreak->update([
                'break_end_time' => now()
            ]);
        }
        \App\Models\BreakLog::create([
            'user_id' => $user->id,
            'break_status' => 'online',
            'break_reason' => null,
            'break_start_time' => now(),
            // 'api_response' => $response->json()
        ]);
    }
 return response()->json([
        "agent_status" => "Success",
        "status" => 200
    ], 200);});

Route::get('duty_active/{id}/{is_active}/{reason?}', function($id, $is_active, $reason = null) {
    $user = User::where('id', $id)->first();
    if (!$user) {
        return response()->json(['error' => 'User not found'], 404);
    }
    $user->is_active = $is_active;
    $user->save();

    $dutyStatus = $is_active == '1' ? 'online' : 'offline';

    if ($is_active == '1') {
        DutyLogs::create([
            'user_id' => $user->id,
            'break_status' => 'online',
            'break_reason' => $reason,
            'break_start_time' => now(),
            'break_end_time' => null,
        ]);
    } else {
        // Find the last online log without end time
        $lastDuty = DutyLogs::where('user_id', $user->id)
            ->where('break_status', 'online')
            ->whereNull('break_end_time')
            ->latest()
            ->first();
        if ($lastDuty) {
            $lastDuty->update([
                'break_end_time' => now()
            ]);
        }
        DutyLogs::create([
            'user_id' => $user->id,
            'break_status' => 'offline',
            'break_reason' => $reason,
            'break_start_time' => now(),
            'break_end_time' => now(),
        ]);
    }
    return response()->json(['status' => 'success', 'is_active' => $is_active]);
});


// Public Route
Route::get('/', function () {
    return view('login');
})->name('home');


Route::get('whatsapp_chat/{id}', [Controllers\WhatsappMsgController::class, 'whatsapp_msg_get'])->name('whatsapp_chat.get');
Route::get('whatsapp_chat_new/{id}', [Controllers\WhatsappMsgController::class, 'whatsapp_msg_get_new'])->name('whatsapp_chat.get_new');
Route::post('whatsapp_msg_send', [Controllers\WhatsappMsgController::class, 'whatsapp_msg_send'])->name('whatsapp_chat.send');
Route::post('whatsapp_msg_send_hi', [Controllers\WhatsappMsgController::class, 'whatsapp_msg_send_hi'])->name('whatsapp_chat.send.hi');
Route::post('whatsapp_msg_status', [Controllers\WhatsappMsgController::class, 'whatsapp_msg_status'])->name('whatsapp_chat.status');
    // WhatsApp Chat Routes
    Route::get('all-whatsapp-chats', [WhatsappMsgController::class, 'all_whatsapp_chats_index'])->name('admin.all_whatsapp_chats.index');
    Route::get('admin/all-whatsapp-chats/numbers', [WhatsappMsgController::class, 'get_all_whatsapp_numbers'])->name('admin.all_whatsapp_chats.numbers');
    Route::get('admin/all-whatsapp-chats/messages/{number}', [WhatsappMsgController::class, 'get_messages_for_number'])->name('admin.all_whatsapp_chats.messages');
    Route::get('admin/all-whatsapp-chats/executives/{number}', [App\Http\Controllers\WhatsappMsgController::class, 'get_executives_for_number'])->name('admin.all_whatsapp_chats.executives');
    Route::post('whatsapp-group/add-executive', [App\Http\Controllers\WhatsappMsgController::class, 'addExecutiveToWhatsappGroup'])->name('whatsapp_group.add_executive');
    Route::post('whatsapp-group/remove-executive', [App\Http\Controllers\WhatsappMsgController::class, 'removeExecutiveFromWhatsappGroup'])->name('whatsapp_group.remove_executive');
    Route::post('admin/all-whatsapp-chats/mark-read/{number}', [App\Http\Controllers\WhatsappMsgController::class, 'markMessagesAsRead'])->name('admin.all_whatsapp_chats.mark_read');


// Authentication Routes
Route::post('/login', [Controllers\AuthController::class, 'login'])->name('login');
Route::post('/login/role-select', [Controllers\AuthController::class, 'selectRole'])->name('login.role.select');
Route::get('/logout', [Controllers\AuthController::class, 'logout'])->name('logout');


Route::post('/update-profile-image/{member_id?}', [Controllers\AuthController::class, 'update_profile_image'])->name('updateProfileImage');

// Routes for Admin Role
Route::prefix('/admin')->middleware(['auth', 'role:Admin'])->group(function () {
    Route::get('/dashboard', [Controllers\Admin\AdminController::class, 'dashboard'])->name('admin.dashboard');
    Route::get('/dashboard/sales-leads-stats', [Controllers\Admin\AdminController::class, 'getSalesLeadsStats'])->name('admin.dashboard.sales-leads-stats');
    Route::get('/dashboard/operation-leads-stats', [Controllers\Admin\AdminController::class, 'getOperationLeadsStats'])->name('admin.dashboard.operation-leads-stats');
    Route::get('/dashboard/users-stats', [Controllers\Admin\AdminController::class, 'getUsersStats'])->name('admin.dashboard.users-stats');
    Route::get('/dashboard/user-details/{userId}', [Controllers\Admin\AdminController::class, 'getUserDetails'])->name('admin.dashboard.user-details');
    Route::get('/dashboard/sales-executives', [Controllers\Admin\AdminController::class, 'getSalesExecutives'])->name('admin.dashboard.sales-executives');
    Route::get('/dashboard/operation-executives', [Controllers\Admin\AdminController::class, 'getOperationExecutives'])->name('admin.dashboard.operation-executives');
    Route::get('/dashboard/users', [Controllers\Admin\AdminController::class, 'getUsers'])->name('admin.dashboard.users');

    // Task Management Routes
    Route::get('/dashboard/tasks', [Controllers\Admin\AdminController::class, 'getTasks'])->name('admin.dashboard.tasks');
    Route::post('/dashboard/tasks', [Controllers\Admin\AdminController::class, 'storeTask'])->name('admin.dashboard.tasks.store');
    Route::get('/dashboard/tasks/{id}/edit', [Controllers\Admin\AdminController::class, 'editTask'])->name('admin.dashboard.tasks.edit');
    Route::put('/dashboard/tasks/{id}', [Controllers\Admin\AdminController::class, 'updateTask'])->name('admin.dashboard.tasks.update');
    Route::delete('/dashboard/tasks/{id}', [Controllers\Admin\AdminController::class, 'destroyTask'])->name('admin.dashboard.tasks.destroy');
    Route::get('/dashboard/task-history', [Controllers\Admin\AdminController::class, 'getTaskHistory'])->name('admin.dashboard.task-history');
    Route::get('/dashboard/calendar-data', [Controllers\Admin\AdminController::class, 'getCalendarData'])->name('admin.dashboard.calendar-data');
    Route::get('/dashboard/calendar-leads', [Controllers\Admin\AdminController::class, 'getCalendarLeads'])->name('admin.dashboard.calendar-leads');
    Route::get('/dashboard/revenue-stats', [Controllers\Admin\AdminController::class, 'getRevenueStats'])->name('admin.dashboard.revenue-stats');
    Route::get('/dashboard/pending-deployments', [Controllers\Admin\AdminController::class, 'getPendingDeployments'])->name('admin.dashboard.pending-deployments');
    Route::get('/dashboard/outstanding-amount-stats', [Controllers\Admin\AdminController::class, 'getOutstandingAmountStats'])->name('admin.dashboard.outstanding-amount-stats');
    Route::get('/dashboard/profile-pending-stats', [Controllers\Admin\AdminController::class, 'getProfilePendingStats'])->name('admin.dashboard.profile-pending-stats');
    Route::get('/dashboard/vendor-payment-stats', [Controllers\Admin\AdminController::class, 'getVendorPaymentStats'])->name('admin.dashboard.vendor-payment-stats');
    Route::get('/dashboard/unverified-deployment-payments-stats', [Controllers\Admin\AdminController::class, 'getUnverifiedDeploymentPaymentsStats'])->name('admin.dashboard.unverified-deployment-payments-stats');
    Route::get('/dashboard/pending-callbacks', [Controllers\Admin\AdminController::class, 'getPendingCallbacks'])->name('admin.dashboard.pending-callbacks');
    
    // Recent Calls Routes
    Route::get('/dashboard/recent-calls/sales', [Controllers\Admin\AdminController::class, 'getRecentCallsSales'])->name('admin.dashboard.recent-calls.sales');
    Route::get('/dashboard/recent-calls/operation', [Controllers\Admin\AdminController::class, 'getRecentCallsOperation'])->name('admin.dashboard.recent-calls.operation');
        Route::get('/operation-leads/vendor-payment-details/{vendorId}', [Controllers\Admin\OperationLeadController::class, 'getVendorPaymentDetails'])->name('admin.operation_leads.vendor_payment_details');
    Route::get('/dashboard/test-revenue', function() {
        $controller = new \App\Http\Controllers\Admin\AdminController();
        $reflection = new ReflectionClass($controller);
        $method = $reflection->getMethod('calculateTodayRevenue');
        $method->setAccessible(true);
        $revenue = $method->invoke($controller);
        return response()->json(['today_revenue' => $revenue]);
    });
    // User Management
    Route::get('users', [Controllers\Admin\UserController::class, 'index'])->name('admin.users.index');

    // Location Management
    Route::resource('locations', Controllers\Admin\LocationController::class)->names('admin.locations');
    Route::get('locations/service-rates', [Controllers\Admin\LocationController::class, 'getServiceRates'])->name('admin.locations.service-rates');

    // Service Management
    Route::resource('services', ServiceController::class)->names('admin.services');

    Route::get('users/getUsers', [Controllers\Admin\UserController::class, 'getUsers'])->name('admin.users.getUsers');
    Route::get('users/manage/{id?}', [Controllers\Admin\UserController::class, 'manage'])->name('admin.users.manage');
    Route::post('users/manage/{id?}', [Controllers\Admin\UserController::class, 'manage_process'])->name('admin.users.manage_process');
    Route::post('users/{id}/create-agent', [Controllers\Admin\UserController::class, 'createAgent'])->name('admin.users.createAgent');
    Route::patch('users/{id}/update-agent', [Controllers\Admin\UserController::class, 'updateAgent'])->name('admin.users.updateAgent');
    Route::delete('users/{id}/delete-agent', [Controllers\Admin\UserController::class, 'deleteAgent'])->name('admin.users.deleteAgent');
    // Delete user
    Route::get('users/{id}', [Controllers\Admin\UserController::class, 'destroy'])->name('admin.users.destroy');
    Route::get('/leads', [App\Http\Controllers\Admin\LeadController::class, 'index'])->name('admin.leads.index');
    Route::get('/leads/getLeads', [App\Http\Controllers\Admin\LeadController::class, 'getLeads'])->name('admin.leads.getLeads');
    Route::get('/leads/{id}', [App\Http\Controllers\Admin\LeadController::class, 'show'])->name('admin.leads.show');
    Route::delete('/leads/{id}', [App\Http\Controllers\Admin\LeadController::class, 'destroy'])->name('admin.leads.destroy');
    Route::post('/leads', [App\Http\Controllers\Admin\LeadController::class, 'store'])->name('admin.leads.store');
    Route::get('/leads/{id}/edit', [App\Http\Controllers\Admin\LeadController::class, 'edit'])->name('admin.leads.edit');
    Route::put('/leads/{id}', [App\Http\Controllers\Admin\LeadController::class, 'update'])->name('admin.leads.update');
    Route::get('/operation-leads', [App\Http\Controllers\Admin\OperationLeadController::class, 'index'])->name('admin.operation_leads.index');
    Route::post('/operation-leads', [App\Http\Controllers\Admin\OperationLeadController::class, 'store'])->name('admin.operation_leads.store');
    Route::get('/operation-leads/getLeads', [App\Http\Controllers\Admin\OperationLeadController::class, 'getLeads'])->name('admin.operation_leads.getLeads');

    // Vendor Filtering Routes (must come before {id} routes)
    Route::get('operation-leads/filtered-vendors', [App\Http\Controllers\Admin\OperationLeadController::class, 'getFilteredVendorsForDeployment'])->name('admin.operation_leads.filtered_vendors');
    Route::get('operation-leads/vendor-details', [App\Http\Controllers\Admin\OperationLeadController::class, 'getVendorDetails'])->name('admin.operation_leads.vendor_details');
    Route::post('operation-leads/update-freelancer-status', [App\Http\Controllers\Admin\OperationLeadController::class, 'updateFreelancerStatus'])->name('admin.operation_leads.update_freelancer_status');
    Route::get('operation-leads/updated-vendor-count', [App\Http\Controllers\Admin\OperationLeadController::class, 'getUpdatedVendorCount'])->name('admin.operation_leads.updated_vendor_count');

    Route::get('/operation-leads/{id}', [App\Http\Controllers\Admin\OperationLeadController::class, 'show'])->name('admin.operation_leads.show');
    Route::get('/operation-leads/{id}/edit', [App\Http\Controllers\Admin\OperationLeadController::class, 'edit'])->name('admin.operation_leads.edit');
    Route::put('/operation-leads/{id}', [App\Http\Controllers\Admin\OperationLeadController::class, 'update'])->name('admin.operation_leads.update');
    Route::delete('/operation-leads/{id}', [App\Http\Controllers\Admin\OperationLeadController::class, 'destroy'])->name('admin.operation_leads.destroy');

// Payment Details Routes
Route::post('operation-leads/{lead}/payment', [App\Http\Controllers\Admin\OperationLeadController::class, 'storePaymentDetail'])->name('admin.operation_leads.payment.store');
Route::get('operation-leads/payment/{id}/edit', [App\Http\Controllers\Admin\OperationLeadController::class, 'editPaymentDetail'])->name('admin.operation_leads.payment.edit');
Route::put('operation-leads/payment/{id}', [App\Http\Controllers\Admin\OperationLeadController::class, 'updatePaymentDetail'])->name('admin.operation_leads.payment.update');
Route::delete('operation-leads/payment/{id}', [App\Http\Controllers\Admin\OperationLeadController::class, 'destroyPaymentDetail'])->name('admin.operation_leads.payment.destroy');

    // Deployment Details Routes
    Route::post('operation-leads/{lead}/deployment', [App\Http\Controllers\Admin\OperationLeadController::class, 'storeDeploymentDetail'])->name('admin.operation_leads.deployment.store');
    Route::get('operation-leads/deployment/{id}/edit', [App\Http\Controllers\Admin\OperationLeadController::class, 'editDeploymentDetail'])->name('admin.operation_leads.deployment.edit');
    Route::put('operation-leads/deployment/{id}', [App\Http\Controllers\Admin\OperationLeadController::class, 'updateDeploymentDetail'])->name('admin.operation_leads.deployment.update');
    Route::delete('operation-leads/deployment/{id}', [App\Http\Controllers\Admin\OperationLeadController::class, 'destroyDeploymentDetail'])->name('admin.operation_leads.deployment.destroy');
    Route::post('operation-leads/deployment/{id}/toggle-verify-payment', [App\Http\Controllers\Admin\OperationLeadController::class, 'toggleVerifyPayment'])->name('admin.operation_leads.deployment.toggle-verify-payment');

    // Payment Invoice and Received Payment Routes
    Route::post('operation-leads/invoice/{invoiceId}/received-payment', [App\Http\Controllers\Admin\OperationLeadController::class, 'storeReceivedPayment'])->name('admin.operation_leads.received_payment.store');
    Route::get('operation-leads/received-payment/{id}/edit', [App\Http\Controllers\Admin\OperationLeadController::class, 'editReceivedPayment'])->name('admin.operation_leads.received_payment.edit');
    Route::put('operation-leads/received-payment/{id}', [App\Http\Controllers\Admin\OperationLeadController::class, 'updateReceivedPayment'])->name('admin.operation_leads.received_payment.update');
    Route::delete('operation-leads/received-payment/{id}', [App\Http\Controllers\Admin\OperationLeadController::class, 'destroyReceivedPayment'])->name('admin.operation_leads.received_payment.destroy');

    Route::get('/chat', [App\Http\Controllers\Admin\ChatController::class, 'index'])->name('admin.chat.index');
    Route::get('/chat/messages/{userId}', [App\Http\Controllers\Admin\ChatController::class, 'getMessages'])->name('admin.chat.messages');
    Route::post('/chat/send', [App\Http\Controllers\Admin\ChatController::class, 'sendMessage'])->name('admin.chat.send');
    Route::post('/chat/mark-read/{userId}', [App\Http\Controllers\Admin\ChatController::class, 'markAsRead'])->name('admin.chat.mark-read');
    Route::get('/chat/unread-counts', [App\Http\Controllers\Admin\ChatController::class, 'getUnreadCounts'])->name('admin.chat.unread-counts');
    Route::post('chat/favorite/{userId}', [App\Http\Controllers\Admin\ChatController::class, 'toggleFavorite'])->name('admin.chat.favorite');
    Route::get('chat/favorites', [App\Http\Controllers\Admin\ChatController::class, 'getFavorites'])->name('admin.chat.favorites');
    Route::get('/chat/users', [App\Http\Controllers\Admin\ChatController::class, 'getUsers']);

    Route::post('chat/send-attachment', [App\Http\Controllers\Admin\ChatController::class, 'sendAttachment'])->name('admin.chat.send.attachment');

    // Staff Chats Routes
    Route::get('/staff/chats', [App\Http\Controllers\Admin\StaffChatController::class, 'index'])->name('admin.staff.chats');
    Route::get('/staff/chats/messages/{salesId}/{participantId}', [App\Http\Controllers\Admin\StaffChatController::class, 'getMessages'])->name('admin.staff.chats.messages');
    Route::post('/staff/chats/send', [App\Http\Controllers\Admin\StaffChatController::class, 'sendMessage'])->name('admin.staff.chats.send');
    Route::post('/staff/chats/mark-read/{userId}', [App\Http\Controllers\Admin\StaffChatController::class, 'markAsRead'])->name('admin.staff.chats.mark-read');
    Route::get('/staff/chats/unread-counts', [App\Http\Controllers\Admin\StaffChatController::class, 'getUnreadCounts'])->name('admin.staff.chats.unread-counts');

    // Vendor Routes
    Route::get('/vendors', [App\Http\Controllers\Admin\VendorController::class, 'index'])->name('admin.vendors.index');
    Route::post('/vendors', [App\Http\Controllers\Admin\VendorController::class, 'store'])->name('admin.vendors.store');
    Route::get('/vendors/edit/{id?}', [App\Http\Controllers\Admin\VendorController::class, 'edit'])->name('admin.vendors.edit');
    Route::put('/vendors/{id?}', [App\Http\Controllers\Admin\VendorController::class, 'update'])->name('admin.vendors.update');
    Route::delete('/vendors/{id?}', [App\Http\Controllers\Admin\VendorController::class, 'destroy'])->name('admin.vendors.destroy');

    // Vendor Payment Routes
    Route::get('/vendor-payments', [App\Http\Controllers\Admin\VendorPaymentController::class, 'index'])->name('admin.vendor_payments.index');
    Route::get('/vendor-payments/history', [App\Http\Controllers\Admin\VendorPaymentController::class, 'vendorHistory'])->name('admin.vendor_payments.history');
    Route::post('/vendor-payments', [App\Http\Controllers\Admin\VendorPaymentController::class, 'store'])->name('admin.vendor_payments.store');
    Route::get('/vendor-payments/{id}', [App\Http\Controllers\Admin\VendorPaymentController::class, 'show'])->name('admin.vendor_payments.show');
    Route::get('/vendor-payments/{id}/edit', [App\Http\Controllers\Admin\VendorPaymentController::class, 'edit'])->name('admin.vendor_payments.edit');
    Route::put('/vendor-payments/{id}', [App\Http\Controllers\Admin\VendorPaymentController::class, 'update'])->name('admin.vendor_payments.update');
    Route::delete('/vendor-payments/{id}', [App\Http\Controllers\Admin\VendorPaymentController::class, 'destroy'])->name('admin.vendor_payments.destroy');
    Route::get('/vendor-payments/vendor/{vendorId}', [App\Http\Controllers\Admin\VendorPaymentController::class, 'getVendorPayments'])->name('admin.vendor_payments.vendor');
    Route::get('/vendor-payments/stats', [App\Http\Controllers\Admin\VendorPaymentController::class, 'getPaymentStats'])->name('admin.vendor_payments.stats');
    Route::get('/vendor-payments/{id}/screenshot', [App\Http\Controllers\Admin\VendorPaymentController::class, 'downloadScreenshot'])->name('admin.vendor_payments.screenshot');

    Route::get('/jobproc', [App\Http\Controllers\Admin\JobProcController::class, 'index'])->name('admin.jobproc.index');
    Route::get('/jobproc/jobrequests', [App\Http\Controllers\Admin\JobProcController::class, 'getJobRequests'])->name('admin.jobproc.jobrequests');
    Route::get('/jobproc/prospects', [App\Http\Controllers\Admin\JobProcController::class, 'getProspects'])->name('admin.jobproc.prospects');
    Route::post('/jobproc/store', [App\Http\Controllers\Admin\JobProcController::class, 'store'])->name('admin.jobproc.store');
    Route::post('/jobproc/import', [App\Http\Controllers\Admin\JobProcController::class, 'import'])->name('admin.jobproc.import');
    Route::get('/jobproc/{id}', [App\Http\Controllers\Admin\JobProcController::class, 'show'])->name('admin.jobproc.show');
    Route::get('/jobproc/{id}/edit', [App\Http\Controllers\Admin\JobProcController::class, 'edit'])->name('admin.jobproc.edit');
    Route::put('/jobproc/{id}', [App\Http\Controllers\Admin\JobProcController::class, 'update'])->name('admin.jobproc.update');
    Route::delete('/jobproc/{id}', [App\Http\Controllers\Admin\JobProcController::class, 'destroy'])->name('admin.jobproc.destroy');

    // Break Logs Routes
    Route::get('/break-logs', function() {
        $breakLogs = BreakLog::with('user')->orderBy('created_at', 'desc')->paginate(20);
        return view('admin.break_logs.index', compact('breakLogs'));
    })->name('admin.break_logs.index');

    Route::get('/break-logs/user/{userId}', function($userId) {
        $user = User::findOrFail($userId);
        $breakLogs = BreakLog::where('user_id', $userId)->orderBy('created_at', 'desc')->paginate(20);
        return view('admin.break_logs.user_logs', compact('breakLogs', 'user'));
    })->name('admin.break_logs.user');

    // Duty Logs Routes
    Route::get('/duty-logs', function() {
        $dutyLogs = DutyLogs::with('user')->orderBy('created_at', 'desc')->paginate(20);
        return view('admin.duty_logs.index', compact('dutyLogs'));
    })->name('admin.duty_logs.index');

    Route::get('/duty-logs/user/{userId}', function($userId) {
        $user = User::findOrFail($userId);
        $dutyLogs = DutyLogs::where('user_id', $userId)->orderBy('created_at', 'desc')->paginate(20);
        return view('admin.duty_logs.user_logs', compact('dutyLogs', 'user'));
    })->name('admin.duty_logs.user');

    Route::get('technical-support', function () {
        return view('admin.technical_support.index');
    })->name('admin.technical-support.index');

});


// Routes for Manager Role
Route::prefix('/manager')->middleware(['auth', 'role:Sales Manager'])->group(function () {
    Route::get('/dashboard', [Controllers\Manager\ManagerController::class, 'dashboard'])->name('manager.dashboard');
    Route::get('/dashboard/leads-stats', [Controllers\Manager\ManagerController::class, 'getLeadsStats'])->name('manager.dashboard.leads-stats');

    // Calendar Routes
    Route::get('/dashboard/calendar-data', [Controllers\Manager\ManagerController::class, 'getCalendarData'])->name('manager.dashboard.calendar-data');
    Route::get('/dashboard/date-leads', [Controllers\Manager\ManagerController::class, 'getDateLeads'])->name('manager.dashboard.date-leads');
    Route::get('/dashboard/lead/{id}', [Controllers\Manager\ManagerController::class, 'getLeadDetails'])->name('manager.dashboard.lead-details');

    // Recent Leads Route
    Route::get('/dashboard/recent-leads', [Controllers\Manager\ManagerController::class, 'getRecentLeads'])->name('manager.dashboard.recent-leads');
    
    // Recent Calls Route
    Route::get('/dashboard/recent-calls', [Controllers\Manager\ManagerController::class, 'getRecentCalls'])->name('manager.dashboard.recent-calls');
    
    // Active Calls Route
    Route::get('/dashboard/active-calls', [Controllers\Manager\ManagerController::class, 'getActiveCalls'])->name('manager.dashboard.active-calls');
    
    // Pending Callbacks Route
    Route::get('/dashboard/pending-callbacks', [Controllers\Manager\ManagerController::class, 'getPendingCallbacks'])->name('manager.dashboard.pending-callbacks');

    Route::get('/leads/{id}/edit', [App\Http\Controllers\Manager\LeadController::class, 'edit'])->name('manager.leads.edit'); // Added for viewLead modal

    // Task Management Routes
    Route::get('/dashboard/tasks', [Controllers\Manager\ManagerController::class, 'getTasks'])->name('manager.dashboard.tasks');
    Route::post('/dashboard/tasks', [Controllers\Manager\ManagerController::class, 'storeTask'])->name('manager.dashboard.tasks.store');
    Route::get('/dashboard/tasks/{id}/edit', [Controllers\Manager\ManagerController::class, 'editTask'])->name('manager.dashboard.tasks.edit');
    Route::post('/dashboard/tasks/{id}/update', [Controllers\Manager\ManagerController::class, 'updateTask'])->name('manager.dashboard.tasks.update');
    Route::delete('/dashboard/tasks/{id}', [Controllers\Manager\ManagerController::class, 'destroyTask'])->name('manager.dashboard.tasks.destroy');
});

// Routes for Sales Role
Route::prefix('/sales')->middleware(['auth', 'role:Sales'])->group(function () {
    Route::get('/dashboard', [Controllers\Sales\SalesController::class, 'dashboard'])->name('sales.dashboard');
    Route::get('/dashboard/leads-stats', [Controllers\Sales\SalesController::class, 'getLeadsStats'])->name('sales.dashboard.leads-stats');

    // Task Management Routes
    Route::get('/dashboard/tasks', [Controllers\Sales\SalesController::class, 'getTasks'])->name('sales.dashboard.tasks');
    Route::post('/dashboard/tasks', [Controllers\Sales\SalesController::class, 'storeTask'])->name('sales.dashboard.tasks.store');
    Route::put('/dashboard/tasks/{id}/status', [Controllers\Sales\SalesController::class, 'updateTaskStatus'])->name('sales.dashboard.tasks.update-status');
    Route::put('/dashboard/tasks/{id}', [Controllers\Sales\SalesController::class, 'updateTask'])->name('sales.dashboard.tasks.update');

    // Calendar Routes
    Route::get('/dashboard/calendar-data', [Controllers\Sales\SalesController::class, 'getCalendarData'])->name('sales.dashboard.calendar-data');
    Route::get('/dashboard/date-leads', [Controllers\Sales\SalesController::class, 'getDateLeads'])->name('sales.dashboard.date-leads');
    Route::get('/dashboard/lead/{id}', [Controllers\Sales\SalesController::class, 'getLeadDetails'])->name('sales.dashboard.lead-details');

    // Call Logs Routes
    Route::get('/dashboard/call-logs-count', [Controllers\Sales\SalesController::class, 'callLogsCount'])->name('sales.dashboard.call-logs-count');
    Route::get('/dashboard/call-logs', [Controllers\Sales\SalesController::class, 'callLogs'])->name('sales.dashboard.call-logs');
    Route::put('/dashboard/call-logs/{id}/mark-processed', [Controllers\Sales\SalesController::class, 'markCallLogProcessed'])->name('sales.dashboard.call-logs.mark-processed');

    // Recent Leads Route
    Route::get('/dashboard/recent-leads', [Controllers\Sales\SalesController::class, 'getRecentLeads'])->name('sales.dashboard.recent-leads');
    
    // Active Calls Route
    Route::get('/dashboard/active-calls', [Controllers\Sales\SalesController::class, 'getActiveCalls'])->name('sales.dashboard.active-calls');
    
    // Pending Callbacks Route
    Route::get('/dashboard/pending-callbacks', [Controllers\Sales\SalesController::class, 'getPendingCallbacks'])->name('sales.dashboard.pending-callbacks');
    
    // Recent Calls Route
    Route::get('/dashboard/recent-calls', [Controllers\Sales\SalesController::class, 'getRecentCalls'])->name('sales.dashboard.recent-calls');

    // Leads Management
    Route::get('/leads', [App\Http\Controllers\Sales\LeadController::class, 'index'])->name('sales.leads.index');
    Route::get('/leads/getLeads', [App\Http\Controllers\Sales\LeadController::class, 'getLeads'])->name('sales.leads.getLeads');
    Route::post('/leads', [App\Http\Controllers\Sales\LeadController::class, 'store'])->name('sales.leads.store');
    Route::get('/leads/{id}', [App\Http\Controllers\Sales\LeadController::class, 'show'])->name('sales.leads.show');
    Route::get('/leads/{id}/edit', [App\Http\Controllers\Sales\LeadController::class, 'edit'])->name('sales.leads.edit');
    Route::put('/leads/{id}', [App\Http\Controllers\Sales\LeadController::class, 'update'])->name('sales.leads.update');

    Route::get('/chat', [App\Http\Controllers\Sales\ChatController::class, 'index'])->name('sales.chat.index');
    Route::get('/chat/messages/{userId}', [App\Http\Controllers\Sales\ChatController::class, 'getMessages'])->name('sales.chat.messages');
    Route::post('/chat/send', [App\Http\Controllers\Sales\ChatController::class, 'sendMessage'])->name('sales.chat.send');
    Route::post('/chat/mark-read/{userId}', [App\Http\Controllers\Sales\ChatController::class, 'markAsRead'])->name('sales.chat.mark-read');
    Route::get('/chat/unread-counts', [App\Http\Controllers\Sales\ChatController::class, 'getUnreadCounts'])->name('sales.chat.unread-counts');
    Route::post('/chat/favorite/{userId}', [App\Http\Controllers\Sales\ChatController::class, 'toggleFavorite'])->name('sales.chat.favorite');
    Route::get('/chat/favorites', [App\Http\Controllers\Sales\ChatController::class, 'getFavorites'])->name('sales.chat.favorites');
    Route::post('/chat/send-attachment', [App\Http\Controllers\Sales\ChatController::class, 'sendAttachment'])->name('sales.chat.send.attachment');

    Route::get('technical-support', function () {
        return view('sales.technical_support.index');
    })->name('sales.technical-support.index');

});

// Routes for Manager Role
Route::prefix('/manager')->middleware(['auth', 'role:Sales Manager'])->group(function () {
    Route::get('/dashboard', [Controllers\Manager\ManagerController::class, 'dashboard'])->name('manager.dashboard');
    Route::get('/leads', [App\Http\Controllers\Manager\LeadController::class, 'index'])->name('manager.leads.index');
    Route::get('/leads/getLeads', [App\Http\Controllers\Manager\LeadController::class, 'getLeads'])->name('manager.leads.getLeads');
    Route::get('/leads/{id}', [App\Http\Controllers\Manager\LeadController::class, 'show'])->name('manager.leads.show');
    Route::delete('/leads/{id}', [App\Http\Controllers\Manager\LeadController::class, 'destroy'])->name('manager.leads.destroy');
    Route::post('/leads', [App\Http\Controllers\Manager\LeadController::class, 'store'])->name('manager.leads.store');
    Route::get('/leads/{id}/edit', [App\Http\Controllers\Manager\LeadController::class, 'edit'])->name('manager.leads.edit');
    Route::put('/leads/{id}', [App\Http\Controllers\Manager\LeadController::class, 'update'])->name('manager.leads.update');
    Route::get('/manager/leads/executives', [App\Http\Controllers\Manager\LeadController::class, 'getExecutives'])->name('manager.leads.getExecutives');

    // Staff Chats Routes
    Route::get('/staff/chats', [App\Http\Controllers\Manager\StaffChatController::class, 'index'])->name('manager.staff.chats');
    Route::get('/staff/chats/messages/{salesId}/{participantId}', [App\Http\Controllers\Manager\StaffChatController::class, 'getMessages'])->name('manager.staff.chats.messages');
    Route::post('/staff/chats/send', [App\Http\Controllers\Manager\StaffChatController::class, 'sendMessage'])->name('manager.staff.chats.send');
    Route::post('/staff/chats/mark-read/{userId}', [App\Http\Controllers\Manager\StaffChatController::class, 'markAsRead'])->name('manager.staff.chats.mark-read');
    Route::get('/staff/chats/unread-counts', [App\Http\Controllers\Manager\StaffChatController::class, 'getUnreadCounts'])->name('manager.staff.chats.unread-counts');

    Route::get('/operation-leads', [App\Http\Controllers\Manager\OperationLeadController::class, 'index'])->name('manager.operation_leads.index');
    Route::post('/operation-leads', [App\Http\Controllers\Manager\OperationLeadController::class, 'store'])->name('manager.operation_leads.store');
    Route::get('/operation-leads/getLeads', [App\Http\Controllers\Manager\OperationLeadController::class, 'getLeads'])->name('manager.operation_leads.getLeads');
    Route::get('/operation-leads/{id}', [App\Http\Controllers\Manager\OperationLeadController::class, 'show'])->name('manager.operation_leads.show');
    Route::get('/operation-leads/{id}/edit', [App\Http\Controllers\Manager\OperationLeadController::class, 'edit'])->name('manager.operation_leads.edit');
    Route::put('/operation-leads/{id}', [App\Http\Controllers\Manager\OperationLeadController::class, 'update'])->name('manager.operation_leads.update');
    Route::delete('/operation-leads/{id}', [App\Http\Controllers\Manager\OperationLeadController::class, 'destroy'])->name('manager.operation_leads.destroy');


    Route::get('/chat', [App\Http\Controllers\Manager\ChatController::class, 'index'])->name('manager.chat.index');
    Route::get('/chat/messages/{userId}', [App\Http\Controllers\Manager\ChatController::class, 'getMessages'])->name('manager.chat.messages');
    Route::post('/chat/send', [App\Http\Controllers\Manager\ChatController::class, 'sendMessage'])->name('manager.chat.send');
    Route::post('/chat/mark-read/{userId}', [App\Http\Controllers\Manager\ChatController::class, 'markAsRead'])->name('manager.chat.mark-read');
    Route::get('/chat/unread-counts', [App\Http\Controllers\Manager\ChatController::class, 'getUnreadCounts'])->name('manager.chat.unread-counts');
    Route::post('/chat/favorite/{userId}', [App\Http\Controllers\Manager\ChatController::class, 'toggleFavorite'])->name('manager.chat.favorite');
    Route::get('/chat/favorites', [App\Http\Controllers\Manager\ChatController::class, 'getFavorites'])->name('manager.chat.favorites');
    Route::post('/chat/send-attachment', [App\Http\Controllers\Manager\ChatController::class, 'sendAttachment'])->name('manager.chat.send.attachment');

    Route::get('users', [Controllers\Manager\UserController::class, 'index'])->name('manager.users.index');

    Route::get('users/getUsers', [Controllers\Manager\UserController::class, 'getUsers'])->name('manager.users.getUsers');
    Route::get('users/manage/{id?}', [Controllers\Manager\UserController::class, 'manage'])->name('manager.users.manage');
    Route::post('users/manage/{id?}', [Controllers\Manager\UserController::class, 'manage_process'])->name('manager.users.manage_process');
    // Delete user
    Route::get('users/{id}', [Controllers\Manager\UserController::class, 'destroy'])->name('manager.users.destroy');

    // Break Logs Routes
    Route::get('/break-logs', function() {
        $breakLogs = BreakLog::with('user')->orderBy('created_at', 'desc')->paginate(20);
        return view('manager.break_logs.index', compact('breakLogs'));
    })->name('manager.break_logs.index');

    Route::get('/break-logs/user/{userId}', function($userId) {
        $user = User::findOrFail($userId);
        $breakLogs = BreakLog::where('user_id', $userId)->orderBy('created_at', 'desc')->paginate(20);
        return view('manager.break_logs.user_logs', compact('breakLogs', 'user'));
    })->name('manager.break_logs.user');

    // Duty Logs Routes
    Route::get('/duty-logs', function() {
        $dutyLogs = DutyLogs::with('user')->orderBy('created_at', 'desc')->paginate(20);
        return view('manager.duty_logs.index', compact('dutyLogs'));
    })->name('manager.duty_logs.index');

    Route::get('/duty-logs/user/{userId}', function($userId) {
        $user = User::findOrFail($userId);
        $dutyLogs = DutyLogs::where('user_id', $userId)->orderBy('created_at', 'desc')->paginate(20);
        return view('manager.duty_logs.user_logs', compact('dutyLogs', 'user'));
    })->name('manager.duty_logs.user');

    Route::get('technical-support', function () {
        return view('manager.technical_support.index');
    })->name('manager.technical-support.index');

});

Route::prefix('/operation')->middleware(['auth', 'role:Operation'])->group(function () {
    Route::get('/dashboard', [Controllers\Operation\OperationController::class, 'dashboard'])->name('operation.dashboard');
    Route::get('/dashboard/leads-stats', [Controllers\Operation\OperationController::class, 'getLeadsStats'])->name('operation.dashboard.leads-stats');
    Route::get('/dashboard/outstanding-payments-stats', [Controllers\Operation\OperationController::class, 'getOutstandingPaymentsStats'])->name('operation.dashboard.outstanding-payments-stats');
    Route::get('/dashboard/deployment-pending-stats', [Controllers\Operation\OperationController::class, 'getDeploymentPendingStats'])->name('operation.dashboard.deployment-pending-stats');
    Route::get('/dashboard/profile-pending-stats', [Controllers\Operation\OperationController::class, 'getProfilePendingStats'])->name('operation.dashboard.profile-pending-stats');
    Route::get('/dashboard/ongoing-stopped-stats', [Controllers\Operation\OperationController::class, 'getOngoingStoppedStats'])->name('operation.dashboard.ongoing-stopped-stats');
    Route::get('/dashboard/payment-due-stats', [Controllers\Operation\OperationController::class, 'getPaymentDueStats'])->name('operation.dashboard.payment-due-stats');
    Route::get('/dashboard/job-request-stats', [Controllers\Operation\OperationController::class, 'getJobRequestStats'])->name('operation.dashboard.job-request-stats');
    Route::get('/dashboard/vendor-freelancer-payment-stats', [Controllers\Operation\OperationController::class, 'getVendorFreelancerPaymentStats'])->name('operation.dashboard.vendor-freelancer-payment-stats');
    Route::get('/dashboard/unverified-deployment-payments-stats', [Controllers\Operation\OperationController::class, 'getUnverifiedDeploymentPaymentsStats'])->name('operation.dashboard.unverified-deployment-payments-stats');
    Route::get('/dashboard/recent-leads-stats', [Controllers\Operation\OperationController::class, 'getRecentLeadsStats'])->name('operation.dashboard.recent-leads-stats');
    Route::get('/dashboard/active-calls', [Controllers\Operation\OperationController::class, 'getActiveCalls'])->name('operation.dashboard.active-calls');
    Route::get('/dashboard/pending-callbacks', [Controllers\Operation\OperationController::class, 'getPendingCallbacks'])->name('operation.dashboard.pending-callbacks');
    Route::get('/dashboard/recent-calls', [Controllers\Operation\OperationController::class, 'getRecentCalls'])->name('operation.dashboard.recent-calls');

    // Calendar Routes
    Route::get('/dashboard/calendar-data', [Controllers\Operation\OperationController::class, 'getCalendarData'])->name('operation.dashboard.calendar-data');
    Route::get('/dashboard/date-leads', [Controllers\Operation\OperationController::class, 'getDateLeads'])->name('operation.dashboard.date-leads');

    // Task Management Routes
    Route::get('/dashboard/tasks', [Controllers\Operation\OperationController::class, 'getTasks'])->name('operation.dashboard.tasks');
    Route::post('/dashboard/tasks', [Controllers\Operation\OperationController::class, 'storeTask'])->name('operation.dashboard.tasks.store');
    Route::get('/dashboard/tasks/{id}/edit', [Controllers\Operation\OperationController::class, 'editTask'])->name('operation.dashboard.tasks.edit');
    Route::put('/dashboard/tasks/{id}', [Controllers\Operation\OperationController::class, 'updateTask'])->name('operation.dashboard.tasks.update');
    Route::put('/dashboard/tasks/{id}/status', [Controllers\Operation\OperationController::class, 'updateTaskStatus'])->name('operation.dashboard.tasks.status');
    Route::delete('/dashboard/tasks/{id}', [Controllers\Operation\OperationController::class, 'destroyTask'])->name('operation.dashboard.tasks.destroy');

    // JobProc Routes - Moving these before other routes to prevent conflicts
    Route::get('/jobproc/all_job_req', [App\Http\Controllers\Operation\JobProcController::class, 'all_index'])->name('operation.jobproc.all_job_req.index');
    Route::get('/jobproc/all_job_req/jobrequests', [App\Http\Controllers\Operation\JobProcController::class, 'allGetJobRequests'])->name('operation.jobproc.all_job_req.jobrequests');

    Route::get('/jobproc', [App\Http\Controllers\Operation\JobProcController::class, 'index'])->name('operation.jobproc.index');
    Route::get('/jobproc/jobrequests', [App\Http\Controllers\Operation\JobProcController::class, 'getJobRequests'])->name('operation.jobproc.jobrequests');
    Route::get('/jobproc/prospects', [App\Http\Controllers\Operation\JobProcController::class, 'getProspects'])->name('operation.jobproc.prospects');
    Route::post('/jobproc/store', [App\Http\Controllers\Operation\JobProcController::class, 'store'])->name('operation.jobproc.store');
    Route::get('/jobproc/{id}', [App\Http\Controllers\Operation\JobProcController::class, 'show'])->name('operation.jobproc.show');
    Route::get('/jobproc/{id}/edit', [App\Http\Controllers\Operation\JobProcController::class, 'edit'])->name('operation.jobproc.edit');
    Route::put('/jobproc/{id}', [App\Http\Controllers\Operation\JobProcController::class, 'update'])->name('operation.jobproc.update');
    Route::delete('/jobproc/{id}', [App\Http\Controllers\Operation\JobProcController::class, 'destroy'])->name('operation.jobproc.destroy');

    Route::get('/operation-leads', [App\Http\Controllers\Operation\OperationLeadController::class, 'index'])->name('operation.operation_leads.index');
    Route::post('/operation-leads', [App\Http\Controllers\Operation\OperationLeadController::class, 'store'])->name('operation.operation_leads.store');
    Route::get('/operation-leads/getLeads', [App\Http\Controllers\Operation\OperationLeadController::class, 'getLeads'])->name('operation.operation_leads.getLeads');

    // Vendor Filtering Routes (must come before {id} routes)
    Route::get('/operation-leads/filtered-vendors', [App\Http\Controllers\Operation\OperationLeadController::class, 'getFilteredVendorsForDeployment'])->name('operation.operation_leads.filtered_vendors');
    Route::get('/operation-leads/vendor-details', [App\Http\Controllers\Operation\OperationLeadController::class, 'getVendorDetails'])->name('operation.operation_leads.vendor_details');
    Route::post('/operation-leads/update-freelancer-status', [App\Http\Controllers\Operation\OperationLeadController::class, 'updateFreelancerStatus'])->name('operation.operation_leads.update_freelancer_status');
    Route::get('/operation-leads/updated-vendor-count', [App\Http\Controllers\Operation\OperationLeadController::class, 'getUpdatedVendorCount'])->name('operation.operation_leads.updated_vendor_count');

    Route::get('/operation-leads/{id}', [App\Http\Controllers\Operation\OperationLeadController::class, 'show'])->name('operation.operation_leads.show');
    Route::get('/operation-leads/{id}/edit', [App\Http\Controllers\Operation\OperationLeadController::class, 'edit'])->name('operation.operation_leads.edit');
    Route::put('/operation-leads/{id}', [App\Http\Controllers\Operation\OperationLeadController::class, 'update'])->name('operation.operation_leads.update');
    Route::delete('/operation-leads/{id}', [App\Http\Controllers\Operation\OperationLeadController::class, 'destroy'])->name('operation.operation_leads.destroy');

    Route::get('/chat', [App\Http\Controllers\Operation\ChatController::class, 'index'])->name('operation.chat.index');
    Route::get('/chat/messages/{userId}', [App\Http\Controllers\Operation\ChatController::class, 'getMessages'])->name('operation.chat.messages');
    Route::post('/chat/send', [App\Http\Controllers\Operation\ChatController::class, 'sendMessage'])->name('operation.chat.send');
    Route::post('/chat/mark-read/{userId}', [App\Http\Controllers\Operation\ChatController::class, 'markAsRead'])->name('operation.chat.mark-read');
    Route::get('/chat/unread-counts', [App\Http\Controllers\Operation\ChatController::class, 'getUnreadCounts'])->name('operation.chat.unread-counts');
    Route::post('/chat/favorite/{userId}', [App\Http\Controllers\Operation\ChatController::class, 'toggleFavorite'])->name('operation.chat.favorite');
    Route::get('/chat/favorites', [App\Http\Controllers\Operation\ChatController::class, 'getFavorites'])->name('operation.chat.favorites');
    Route::post('/chat/send-attachment', [App\Http\Controllers\Operation\ChatController::class, 'sendAttachment'])->name('operation.chat.send.attachment');

    // Payment Details Routes
    Route::post('operation-leads/{lead}/payment', [App\Http\Controllers\Operation\OperationLeadController::class, 'storePaymentDetail'])->name('operation.operation_leads.payment.store');
    Route::get('operation-leads/payment/{id}/edit', [App\Http\Controllers\Operation\OperationLeadController::class, 'editPaymentDetail'])->name('operation.operation_leads.payment.edit');
    Route::put('operation-leads/payment/{id}', [App\Http\Controllers\Operation\OperationLeadController::class, 'updatePaymentDetail'])->name('operation.operation_leads.payment.update');
    Route::delete('operation-leads/payment/{id}', [App\Http\Controllers\Operation\OperationLeadController::class, 'destroyPaymentDetail'])->name('operation.operation_leads.payment.destroy');

    // Deployment Details Routes
    Route::post('operation-leads/{lead}/deployment', [App\Http\Controllers\Operation\OperationLeadController::class, 'storeDeploymentDetail'])->name('operation.operation_leads.deployment.store');
    Route::get('operation-leads/deployment/{id}/edit', [App\Http\Controllers\Operation\OperationLeadController::class, 'editDeploymentDetail'])->name('operation.operation_leads.deployment.edit');
    Route::put('operation-leads/deployment/{id}', [App\Http\Controllers\Operation\OperationLeadController::class, 'updateDeploymentDetail'])->name('operation.operation_leads.deployment.update');
    Route::delete('operation-leads/deployment/{id}', [App\Http\Controllers\Operation\OperationLeadController::class, 'destroyDeploymentDetail'])->name('operation.operation_leads.deployment.destroy');
    Route::post('operation-leads/deployment/{id}/toggle-verify-payment', [App\Http\Controllers\Operation\OperationLeadController::class, 'toggleVerifyPayment'])->name('operation.operation_leads.deployment.toggle-verify-payment');

    // Payment Invoice and Received Payment Routes
    Route::post('operation-leads/invoice/{invoiceId}/received-payment', [App\Http\Controllers\Operation\OperationLeadController::class, 'storeReceivedPayment'])->name('operation.operation_leads.received_payment.store');
    Route::get('operation-leads/received-payment/{id}/edit', [App\Http\Controllers\Operation\OperationLeadController::class, 'editReceivedPayment'])->name('operation.operation_leads.received_payment.edit');
    Route::put('operation-leads/received-payment/{id}', [App\Http\Controllers\Operation\OperationLeadController::class, 'updateReceivedPayment'])->name('operation.operation_leads.received_payment.update');
    Route::delete('operation-leads/received-payment/{id}', [App\Http\Controllers\Operation\OperationLeadController::class, 'destroyReceivedPayment'])->name('operation.operation_leads.received_payment.destroy');

    Route::get('technical-support', function () {
        return view('operation.technical_support.index');
    })->name('operation.technical-support.index');

});

// Operation Manager Routes
Route::middleware(['auth', 'role:Operation Manager'])->prefix('operation-manager')->name('operation-manager.')->group(function () {
    Route::get('/dashboard', [App\Http\Controllers\OperationManager\DashboardController::class, 'dashboard'])->name('dashboard');
            Route::get('/dashboard/leads-stats', [App\Http\Controllers\OperationManager\DashboardController::class, 'getLeadsStats'])->name('dashboard.leads-stats');
        Route::get('/dashboard/users-stats', [App\Http\Controllers\OperationManager\DashboardController::class, 'getUsersStats'])->name('dashboard.users-stats');
        Route::get('/dashboard/tasks', [App\Http\Controllers\OperationManager\DashboardController::class, 'getTasks'])->name('dashboard.tasks');
        Route::post('/dashboard/tasks', [App\Http\Controllers\OperationManager\DashboardController::class, 'storeTask'])->name('dashboard.tasks.store');
        Route::put('/dashboard/tasks/{id}/status', [App\Http\Controllers\OperationManager\DashboardController::class, 'updateTaskStatus'])->name('dashboard.tasks.update-status');
        Route::get('/dashboard/team-members', [App\Http\Controllers\OperationManager\DashboardController::class, 'getTeamMembers'])->name('dashboard.team-members');
        Route::get('/dashboard/task-history', [App\Http\Controllers\OperationManager\DashboardController::class, 'getTaskHistory'])->name('dashboard.task-history');
        Route::get('/dashboard/tasks/{id}', [App\Http\Controllers\OperationManager\DashboardController::class, 'getTaskDetails'])->name('dashboard.tasks.show');
        Route::put('/dashboard/tasks/{id}', [App\Http\Controllers\OperationManager\DashboardController::class, 'updateTask'])->name('dashboard.tasks.update');
        Route::delete('/dashboard/tasks/{id}', [App\Http\Controllers\OperationManager\DashboardController::class, 'deleteTask'])->name('dashboard.tasks.delete');

        // Calendar Routes
        Route::get('/dashboard/calendar-data', [App\Http\Controllers\OperationManager\DashboardController::class, 'getCalendarData'])->name('dashboard.calendar-data');
        Route::get('/dashboard/date-leads', [App\Http\Controllers\OperationManager\DashboardController::class, 'getDateLeads'])->name('dashboard.date-leads');
        Route::get('/dashboard/outstanding-amount-stats', [App\Http\Controllers\OperationManager\DashboardController::class, 'getOutstandingAmountStats'])->name('dashboard.outstanding-amount-stats');
        Route::get('/dashboard/deployment-pending-stats', [App\Http\Controllers\OperationManager\DashboardController::class, 'getDeploymentPendingStats'])->name('dashboard.deployment-pending-stats');
        Route::get('/dashboard/profile-pending-stats', [App\Http\Controllers\OperationManager\DashboardController::class, 'getProfilePendingStats'])->name('dashboard.profile-pending-stats');
        Route::get('/dashboard/ongoing-stats', [App\Http\Controllers\OperationManager\DashboardController::class, 'getOngoingStats'])->name('dashboard.ongoing-stats');
        Route::get('/dashboard/payment-due-stats', [App\Http\Controllers\OperationManager\DashboardController::class, 'getPaymentDueStats'])->name('dashboard.payment-due-stats');
        Route::get('/dashboard/job-request-stats', [App\Http\Controllers\OperationManager\DashboardController::class, 'getJobRequestStats'])->name('dashboard.job-request-stats');
        Route::get('/dashboard/vendor-freelancer-payment-stats', [App\Http\Controllers\OperationManager\DashboardController::class, 'getVendorFreelancerPaymentStats'])->name('dashboard.vendor-freelancer-payment-stats');
        Route::get('/dashboard/unverified-deployment-payments-stats', [App\Http\Controllers\OperationManager\DashboardController::class, 'getUnverifiedDeploymentPaymentsStats'])->name('dashboard.unverified-deployment-payments-stats');
        Route::get('/dashboard/recent-leads-stats', [App\Http\Controllers\OperationManager\DashboardController::class, 'getRecentLeadsStats'])->name('dashboard.recent-leads-stats');
        Route::get('/dashboard/pending-callbacks', [App\Http\Controllers\OperationManager\DashboardController::class, 'getPendingCallbacks'])->name('dashboard.pending-callbacks');
        Route::get('/dashboard/recent-calls', [App\Http\Controllers\OperationManager\DashboardController::class, 'getRecentCalls'])->name('dashboard.recent-calls');
    Route::get('/operation-leads', [App\Http\Controllers\OperationManager\OperationLeadController::class, 'index'])->name('operation_leads.index');
    Route::get('/operation-leads/get-leads', [App\Http\Controllers\OperationManager\OperationLeadController::class, 'getLeads'])->name('operation_leads.getLeads');

    // Vendor filtering routes for Operation Manager (must come before {id} routes)
    Route::get('operation-leads/filtered-vendors', [App\Http\Controllers\OperationManager\OperationLeadController::class, 'getFilteredVendorsForDeployment'])->name('operation_manager.operation_leads.filtered_vendors');
    Route::get('operation-leads/vendor-details', [App\Http\Controllers\OperationManager\OperationLeadController::class, 'getVendorDetails'])->name('operation_manager.operation_leads.vendor_details');
    Route::post('operation-leads/update-freelancer-status', [App\Http\Controllers\OperationManager\OperationLeadController::class, 'updateFreelancerStatus'])->name('operation_manager.operation_leads.update_freelancer_status');
    Route::get('operation-leads/updated-vendor-count', [App\Http\Controllers\OperationManager\OperationLeadController::class, 'getUpdatedVendorCount'])->name('operation_manager.operation_leads.updated_vendor_count');

    Route::get('/operation-leads/{id}', [App\Http\Controllers\OperationManager\OperationLeadController::class, 'show'])->name('operation_leads.show');
    Route::get('/operation-leads/{id}/edit', [App\Http\Controllers\OperationManager\OperationLeadController::class, 'edit'])->name('operation_leads.edit');
    Route::put('/operation-leads/{id}', [App\Http\Controllers\OperationManager\OperationLeadController::class, 'update'])->name('operation_leads.update');
    Route::get('/operation-leads/{id}', [App\Http\Controllers\OperationManager\OperationLeadController::class, 'show'])->name('operation_leads.show');
    Route::post('/operation-leads', [App\Http\Controllers\OperationManager\OperationLeadController::class, 'store'])->name('operation_leads.store');
    Route::delete('/operation-leads/{id}', [App\Http\Controllers\OperationManager\OperationLeadController::class, 'destroy'])->name('operation_leads.destroy');

    // Chat Routes
    Route::get('/chat', [App\Http\Controllers\OperationManager\ChatController::class, 'index'])->name('chat.index');
    Route::get('/chat/messages/{userId}', [App\Http\Controllers\OperationManager\ChatController::class, 'getMessages'])->name('chat.messages');
    Route::post('/chat/send', [App\Http\Controllers\OperationManager\ChatController::class, 'sendMessage'])->name('chat.send');
    Route::post('/chat/mark-read/{userId}', [App\Http\Controllers\OperationManager\ChatController::class, 'markAsRead'])->name('chat.mark-read');
    Route::get('/chat/unread-counts', [App\Http\Controllers\OperationManager\ChatController::class, 'getUnreadCounts'])->name('chat.unread-counts');
    Route::post('/chat/favorite/{userId}', [App\Http\Controllers\OperationManager\ChatController::class, 'toggleFavorite'])->name('chat.favorite');
    Route::get('/chat/favorites', [App\Http\Controllers\OperationManager\ChatController::class, 'getFavorites'])->name('chat.favorites');
    Route::post('/chat/send-attachment', [App\Http\Controllers\OperationManager\ChatController::class, 'sendAttachment'])->name('chat.send.attachment');

    // Coordinator Chats Routes
    Route::get('/coordinator/chats', [App\Http\Controllers\OperationManager\CoordinatorChatController::class, 'index'])->name('coordinator.chats');
    Route::get('/coordinator/chats/messages/{coordinatorId}/{participantId}', [App\Http\Controllers\OperationManager\CoordinatorChatController::class, 'getMessages'])->name('coordinator.chats.messages');
    Route::post('/coordinator/chats/send', [App\Http\Controllers\OperationManager\CoordinatorChatController::class, 'sendMessage'])->name('coordinator.chats.send');
    Route::post('/coordinator/chats/mark-read/{userId}', [App\Http\Controllers\OperationManager\CoordinatorChatController::class, 'markAsRead'])->name('coordinator.chats.mark-read');
    Route::get('/coordinator/chats/unread-counts', [App\Http\Controllers\OperationManager\CoordinatorChatController::class, 'getUnreadCounts'])->name('coordinator.chats.unread-counts');


    // Payment Details Routes
    Route::post('operation-leads/{lead}/payment', [App\Http\Controllers\OperationManager\OperationLeadController::class, 'storePaymentDetail'])->name('operation_leads.payment.store');
    Route::get('operation-leads/payment/{id}/edit', [App\Http\Controllers\OperationManager\OperationLeadController::class, 'editPaymentDetail'])->name('operation_leads.payment.edit');
    Route::put('operation-leads/payment/{id}', [App\Http\Controllers\OperationManager\OperationLeadController::class, 'updatePaymentDetail'])->name('operation_leads.payment.update');
    Route::delete('operation-leads/payment/{id}', [App\Http\Controllers\OperationManager\OperationLeadController::class, 'destroyPaymentDetail'])->name('operation_leads.payment.destroy');

    // Deployment Details Routes
    Route::post('operation-leads/{lead}/deployment', [App\Http\Controllers\OperationManager\OperationLeadController::class, 'storeDeploymentDetail'])->name('operation_leads.deployment.store');
    Route::get('operation-leads/deployment/{id}/edit', [App\Http\Controllers\OperationManager\OperationLeadController::class, 'editDeploymentDetail'])->name('operation_leads.deployment.edit');
    Route::put('operation-leads/deployment/{id}', [App\Http\Controllers\OperationManager\OperationLeadController::class, 'updateDeploymentDetail'])->name('operation_leads.deployment.update');
    Route::delete('operation-leads/deployment/{id}', [App\Http\Controllers\OperationManager\OperationLeadController::class, 'destroyDeploymentDetail'])->name('operation_leads.deployment.destroy');
    Route::post('operation-leads/deployment/{id}/toggle-verify-payment', [App\Http\Controllers\OperationManager\OperationLeadController::class, 'toggleVerifyPayment'])->name('operation_leads.deployment.toggle-verify-payment');

    // Payment Invoice and Received Payment Routes
    Route::post('operation-leads/invoice/{invoiceId}/received-payment', [App\Http\Controllers\OperationManager\OperationLeadController::class, 'storeReceivedPayment'])->name('operation_manager.operation_leads.received_payment.store');
    Route::get('operation-leads/received-payment/{id}/edit', [App\Http\Controllers\OperationManager\OperationLeadController::class, 'editReceivedPayment'])->name('operation_manager.operation_leads.received_payment.edit');
    Route::put('operation-leads/received-payment/{id}', [App\Http\Controllers\OperationManager\OperationLeadController::class, 'updateReceivedPayment'])->name('operation_manager.operation_leads.received_payment.update');
    Route::delete('operation-leads/received-payment/{id}', [App\Http\Controllers\OperationManager\OperationLeadController::class, 'destroyReceivedPayment'])->name('operation_manager.operation_leads.received_payment.destroy');

    // jobproc routes
    Route::get('/jobproc', [App\Http\Controllers\OperationManager\JobProcController::class, 'index'])->name('jobproc.index');
    Route::get('/jobproc/jobrequests', [App\Http\Controllers\OperationManager\JobProcController::class, 'getJobRequests'])->name('jobproc.jobrequests');
    Route::get('/jobproc/prospects', [App\Http\Controllers\OperationManager\JobProcController::class, 'getProspects'])->name('jobproc.prospects');
    Route::post('/jobproc/store', [App\Http\Controllers\OperationManager\JobProcController::class, 'store'])->name('jobproc.store');
    Route::get('/jobproc/{id}', [App\Http\Controllers\OperationManager\JobProcController::class, 'show'])->name('jobproc.show');
    Route::get('/jobproc/{id}/edit', [App\Http\Controllers\OperationManager\JobProcController::class, 'edit'])->name('jobproc.edit');
    Route::put('/jobproc/{id}', [App\Http\Controllers\OperationManager\JobProcController::class, 'update'])->name('jobproc.update');
    Route::delete('/jobproc/{id}', [App\Http\Controllers\OperationManager\JobProcController::class, 'destroy'])->name('jobproc.destroy');

    Route::get('users', [Controllers\OperationManager\UserController::class, 'index'])->name('users.index');

    Route::get('users/getUsers', [Controllers\OperationManager\UserController::class, 'getUsers'])->name('users.getUsers');
    Route::get('users/manage/{id?}', [Controllers\OperationManager\UserController::class, 'manage'])->name('users.manage');
    Route::post('users/manage/{id?}', [Controllers\OperationManager\UserController::class, 'manage_process'])->name('users.manage_process');
    // Delete user
    Route::get('users/{id}', [Controllers\OperationManager\UserController::class, 'destroy'])->name('users.destroy');

    // Break Logs Routes
    Route::get('/break-logs', function() {
        $breakLogs = BreakLog::with('user')->orderBy('created_at', 'desc')->paginate(20);
        return view('operation_manager.break_logs.index', compact('breakLogs'));
    })->name('break_logs.index');

    Route::get('/break-logs/user/{userId}', function($userId) {
        $user = User::findOrFail($userId);
        $breakLogs = BreakLog::where('user_id', $userId)->orderBy('created_at', 'desc')->paginate(20);
        return view('operation_manager.break_logs.user_logs', compact('breakLogs', 'user'));
    })->name('break_logs.user');

    // Duty Logs Routes
    Route::get('/duty-logs', function() {
        $dutyLogs = DutyLogs::with('user')->orderBy('created_at', 'desc')->paginate(20);
        return view('operation_manager.duty_logs.index', compact('dutyLogs'));
    })->name('duty_logs.index');

    Route::get('/duty-logs/user/{userId}', function($userId) {
        $user = User::findOrFail($userId);
        $dutyLogs = DutyLogs::where('user_id', $userId)->orderBy('created_at', 'desc')->paginate(20);
        return view('operation_manager.duty_logs.user_logs', compact('dutyLogs', 'user'));
    })->name('duty_logs.user');

    Route::get('technical-support', function () {
        return view('operation_manager.technical_support.index');
    })->name('technical-support.index');

});

Route::get('/api/all-users', function () {
    return \App\Models\User::select('id','f_name','l_name')
        ->whereRaw("FIND_IN_SET(?, role_id) OR FIND_IN_SET(?, role_id)", [2, 4])
        ->get();
});

// Service rates API - accessible to all authenticated users
Route::middleware(['auth'])->group(function() {
    Route::get('/api/locations/service-rates', [Controllers\Admin\LocationController::class, 'getServiceRates'])->name('api.locations.service-rates');
});

// Debug route to test service lookup
Route::get('/debug/services', function() {
    $services = \App\Models\Service::all();
    return response()->json([
        'services' => $services->pluck('name'),
        'locations' => \App\Models\Location::all()->pluck('name')
    ]);
});

Route::middleware(['auth'])->group(function() {
    Route::get('/group-chat/groups', [App\Http\Controllers\GroupChatController::class, 'listGroups']);
    Route::post('/group-chat/create', [App\Http\Controllers\GroupChatController::class, 'createGroup']);
    Route::get('/group-chat/messages/{groupId}', [App\Http\Controllers\GroupChatController::class, 'getMessages']);
    Route::post('/group-chat/send/{groupId}', [App\Http\Controllers\GroupChatController::class, 'sendMessage']);
    Route::post('/group-chat/add-user/{groupId}', [App\Http\Controllers\GroupChatController::class, 'addUser']);
    Route::post('/group-chat/remove-user/{groupId}', [App\Http\Controllers\GroupChatController::class, 'removeUser']);
    Route::delete('/group-chat/delete/{groupId}', [App\Http\Controllers\GroupChatController::class, 'deleteGroup']);
    Route::post('/group-chat/send-attachment/{group_id}', [App\Http\Controllers\GroupChatController::class, 'sendAttachment']);
    Route::post('/group-chat/mark-read/{groupId}', [\App\Http\Controllers\GroupChatController::class, 'markGroupMessagesAsRead'])->name('group-chat.mark-read');
});



