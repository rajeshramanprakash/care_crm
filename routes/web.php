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
use App\Facades\UserAssignment;
use App\Services\OutboundCall;
use Illuminate\Http\Request;
use App\Http\Controllers\WhatsappMsgController;
use App\Http\Controllers\GroupChatController;
use App\Http\Controllers\Admin\ServiceController;
use App\Http\Controllers\Admin\DoctorConsultationServiceController;
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

Route::get('/test-freelancer-pdf', function () {
    $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.freelancer-service-agreement')
            ->setPaper('a4');
    return $pdf->stream('freelancer-agreement-preview.pdf');
});

Route::get('/test-vendor-pdf', function () {
    $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.vendor-service-agreement')
            ->setPaper('a4');
    return $pdf->stream('vendor-agreement-preview.pdf');
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
        "success" => true,
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
    return response()->json(['success' => true, 'status' => 'success', 'is_active' => $is_active]);
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
    Route::get('whatsapp/media-proxy/{id}', [WhatsappMsgController::class, 'mediaProxy'])->name('whatsapp.media_proxy');


// Authentication Routes
Route::match(['get','post'], '/send-otp', [Controllers\AuthController::class, 'sendOtp'])
    ->name('send.otp');
Route::post('/login', [Controllers\AuthController::class, 'login'])->name('login');
Route::get('/login/role-select', [Controllers\AuthController::class, 'showRoleSelect'])->name('login.role.select.show');
Route::post('/login/role-select', [Controllers\AuthController::class, 'selectRole'])->name('login.role.select');
Route::get('/logout', [Controllers\AuthController::class, 'logout'])->name('logout');

// Registration Routes
Route::get('/register', [App\Http\Controllers\Auth\RegisterController::class, 'showRegistrationForm'])->name('register');
Route::get('/register/languages', [App\Http\Controllers\Auth\RegisterController::class, 'registrationLanguages'])->name('register.languages');
Route::get('/register/translations/{code}', [App\Http\Controllers\Auth\RegisterController::class, 'registrationTranslations'])->name('register.translations');
Route::get('/register/location-provider-services', [App\Http\Controllers\Auth\RegisterController::class, 'locationProviderServices'])->name('register.location-provider-services');
Route::get('/register/{type}', [App\Http\Controllers\Auth\RegisterController::class, 'showFormByType'])->name('register.form');
Route::post('/register', [App\Http\Controllers\Auth\RegisterController::class, 'register'])->name('register.submit');
Route::post('/register/doctor/generate-about', [App\Http\Controllers\Api\DoctorRegistrationAssistController::class, 'generateAbout'])
    ->name('register.doctor.generate-about')
    ->middleware('throttle:20,1');
Route::post('/register/doctor/send-mobile-otp', [App\Http\Controllers\Auth\RegisterController::class, 'sendDoctorMobileOtp'])
    ->name('register.doctor.send-mobile-otp')
    ->middleware('throttle:10,1');
Route::post('/register/doctor/verify-mobile-otp', [App\Http\Controllers\Auth\RegisterController::class, 'verifyDoctorMobileOtp'])
    ->name('register.doctor.verify-mobile-otp')
    ->middleware('throttle:30,1');
Route::get('/register/doctor/otp-meta', [App\Http\Controllers\Auth\RegisterController::class, 'fetchDoctorMobileOtpMeta'])
    ->name('register.doctor.otp-meta')
    ->middleware('throttle:60,1');
Route::post('/register/vendor/send-mobile-otp', [App\Http\Controllers\Auth\RegisterController::class, 'sendVendorMobileOtp'])
    ->name('register.vendor.send-mobile-otp')
    ->middleware('throttle:10,1');
Route::post('/register/vendor/verify-mobile-otp', [App\Http\Controllers\Auth\RegisterController::class, 'verifyVendorMobileOtp'])
    ->name('register.vendor.verify-mobile-otp')
    ->middleware('throttle:30,1');
Route::post('/register/freelancer/send-mobile-otp', [App\Http\Controllers\Auth\RegisterController::class, 'sendFreelancerMobileOtp'])
    ->name('register.freelancer.send-mobile-otp')
    ->middleware('throttle:10,1');
Route::post('/register/freelancer/verify-mobile-otp', [App\Http\Controllers\Auth\RegisterController::class, 'verifyFreelancerMobileOtp'])
    ->name('register.freelancer.verify-mobile-otp')
    ->middleware('throttle:30,1');

// Freelancer Routes
Route::prefix('freelancer')->name('freelancer.')->group(function () {
    Route::get('/dashboard', [App\Http\Controllers\Freelancer\FreelancerController::class, 'dashboard'])->name('dashboard');
    Route::get('/personal-details', [App\Http\Controllers\Freelancer\FreelancerController::class, 'personalDetails'])->name('personal-details');
    Route::post('/personal-details/update-profile-image', [App\Http\Controllers\Freelancer\FreelancerController::class, 'updateProfileImage'])->name('personal-details.update-profile-image');
    Route::post('/personal-details/upload-document', [App\Http\Controllers\Freelancer\FreelancerController::class, 'uploadDocument'])->name('personal-details.upload-document');
    Route::post('/personal-details/update-field', [App\Http\Controllers\Freelancer\FreelancerController::class, 'updatePersonalDetails'])->name('personal-details.update-field');
    Route::post('/personal-details/price-change-request', [App\Http\Controllers\Freelancer\FreelancerController::class, 'submitPriceChangeRequest'])->name('personal-details.price-change-request');
    Route::get('/bank-details', [App\Http\Controllers\Freelancer\FreelancerController::class, 'bankDetails'])->name('bank-details');
    Route::post('/bank-details/update-field', [App\Http\Controllers\Freelancer\FreelancerController::class, 'updateBankDetails'])->name('bank-details.update-field');
    Route::post('/bank-details/upload-document', [App\Http\Controllers\Freelancer\FreelancerController::class, 'uploadBankDocument'])->name('bank-details.upload-document');
    Route::get('/emergency-details', [App\Http\Controllers\Freelancer\FreelancerController::class, 'emergencyDetails'])->name('emergency-details');
    Route::get('/assigned-leads', [App\Http\Controllers\Freelancer\FreelancerController::class, 'assignedLeads'])->name('assigned-leads');
    Route::post('/attendance/location', [App\Http\Controllers\Freelancer\FreelancerController::class, 'submitAttendanceLocation'])->name('attendance.location');
    Route::post('/attendance/statuses', [App\Http\Controllers\Freelancer\FreelancerController::class, 'attendanceStatuses'])->name('attendance.statuses');
    Route::get('/payment-details', [App\Http\Controllers\Freelancer\FreelancerController::class, 'paymentDetails'])->name('payment-details');
    Route::get('/statement', [App\Http\Controllers\Freelancer\FreelancerController::class, 'statement'])->name('statement');
    Route::get('/customer-chats', [App\Http\Controllers\Freelancer\ChatController::class, 'index'])->name('customer-chats');
    Route::get('/customer-chats/messages/{customerId}', [App\Http\Controllers\Freelancer\ChatController::class, 'getMessages'])->name('customer-chats.messages');
    Route::post('/customer-chats/send', [App\Http\Controllers\Freelancer\ChatController::class, 'sendMessage'])->name('customer-chats.send');
    Route::post('/customer-chats/mark-read/{customerId}', [App\Http\Controllers\Freelancer\ChatController::class, 'markAsRead'])->name('customer-chats.mark-read');
    Route::get('/customer-chats/unread-counts', [App\Http\Controllers\Freelancer\ChatController::class, 'getUnreadCounts'])->name('customer-chats.unread-counts');
    Route::get('/customer-chats/call/offer', [App\Http\Controllers\Freelancer\ChatController::class, 'getCallOffer'])->name('customer-chats.call.offer');
    Route::post('/customer-chats/call/offer', [App\Http\Controllers\Freelancer\ChatController::class, 'sendCallOffer'])->name('customer-chats.call.offer.send');
    Route::post('/customer-chats/call/answer', [App\Http\Controllers\Freelancer\ChatController::class, 'callAnswer'])->name('customer-chats.call.answer');
    Route::get('/customer-chats/call/answer', [App\Http\Controllers\Freelancer\ChatController::class, 'getCallAnswer'])->name('customer-chats.call.answer.get');
    Route::get('/customer-chats/call/ice', [App\Http\Controllers\Freelancer\ChatController::class, 'getCallIce'])->name('customer-chats.call.ice');
    Route::post('/customer-chats/call/ice', [App\Http\Controllers\Freelancer\ChatController::class, 'sendCallIce'])->name('customer-chats.call.ice.send');
    Route::post('/customer-chats/call/end', [App\Http\Controllers\Freelancer\ChatController::class, 'callEnd'])->name('customer-chats.call.end');
    Route::post('/customer-chats/translate', [App\Http\Controllers\Freelancer\ChatController::class, 'translate'])->name('customer-chats.translate');
});

// Registered doctors (Care provider portal — session login, separate from other doctor modules)
Route::prefix('doctor-portal')->name('doctor_portal.')->group(function () {
    Route::get('/dashboard', [App\Http\Controllers\DoctorPortal\DoctorPortalController::class, 'dashboard'])->name('dashboard');
    Route::get('/availability', [App\Http\Controllers\DoctorPortal\DoctorPortalController::class, 'calendarAvailability'])->name('availability');
    Route::get('/availability/calendar-slots', [App\Http\Controllers\DoctorPortal\DoctorPortalController::class, 'calendarAvailabilityData'])->name('availability.calendar_slots');
    Route::post('/availability/calendar-save', [App\Http\Controllers\DoctorPortal\DoctorPortalController::class, 'calendarAvailabilitySave'])->name('availability.calendar_save');
    Route::post('/availability/calendar-delete/{slot}', [App\Http\Controllers\DoctorPortal\DoctorPortalController::class, 'calendarAvailabilityDelete'])->name('availability.calendar_delete')->whereNumber('slot');
    Route::post('/availability/calendar-delete-by-date', [App\Http\Controllers\DoctorPortal\DoctorPortalController::class, 'calendarAvailabilityDeleteByDate'])->name('availability.calendar_delete_by_date');
    Route::get('/bookings', [App\Http\Controllers\DoctorPortal\DoctorPortalController::class, 'bookings'])->name('bookings');
    Route::get('/bookings/{bookingId}/join-meeting', [App\Http\Controllers\DoctorPortal\DoctorPortalController::class, 'joinBookingMeeting'])
        ->whereNumber('bookingId')
        ->name('bookings.join-meeting');
    Route::get('/personal-details', [App\Http\Controllers\DoctorPortal\DoctorPortalController::class, 'personalDetails'])->name('personal-details');
    Route::post('/personal-details/update-profile-image', [App\Http\Controllers\DoctorPortal\DoctorPortalController::class, 'updateProfileImage'])->name('personal-details.update-profile-image');
    Route::post('/personal-details/upload-document', [App\Http\Controllers\DoctorPortal\DoctorPortalController::class, 'uploadDocument'])->name('personal-details.upload-document');
    Route::post('/personal-details/update-field', [App\Http\Controllers\DoctorPortal\DoctorPortalController::class, 'updatePersonalDetails'])->name('personal-details.update-field');
    Route::post('/personal-details/price-change-request', [App\Http\Controllers\DoctorPortal\DoctorPortalController::class, 'submitPriceChangeRequest'])->name('personal-details.price-change-request');
    Route::get('/bank-details', [App\Http\Controllers\DoctorPortal\DoctorPortalController::class, 'bankDetails'])->name('bank-details');
    Route::post('/bank-details/update-field', [App\Http\Controllers\DoctorPortal\DoctorPortalController::class, 'updateBankDetails'])->name('bank-details.update-field');
    Route::post('/bank-details/upload-document', [App\Http\Controllers\DoctorPortal\DoctorPortalController::class, 'uploadBankDocument'])->name('bank-details.upload-document');
    Route::get('/customer-chats', [App\Http\Controllers\DoctorPortal\DoctorCustomerChatController::class, 'index'])->name('customer-chats');
    Route::get('/customer-chats/messages/{customerId}', [App\Http\Controllers\DoctorPortal\DoctorCustomerChatController::class, 'getMessages'])->name('customer-chats.messages');
    Route::post('/customer-chats/send', [App\Http\Controllers\DoctorPortal\DoctorCustomerChatController::class, 'sendMessage'])->name('customer-chats.send');
    Route::post('/customer-chats/mark-read/{customerId}', [App\Http\Controllers\DoctorPortal\DoctorCustomerChatController::class, 'markAsRead'])->name('customer-chats.mark-read');
    Route::get('/customer-chats/unread-counts', [App\Http\Controllers\DoctorPortal\DoctorCustomerChatController::class, 'getUnreadCounts'])->name('customer-chats.unread-counts');
    Route::get('/customer-chats/call/offer', [App\Http\Controllers\DoctorPortal\DoctorCustomerChatController::class, 'getCallOffer'])->name('customer-chats.call.offer');
    Route::post('/customer-chats/call/offer', [App\Http\Controllers\DoctorPortal\DoctorCustomerChatController::class, 'sendCallOffer'])->name('customer-chats.call.offer.send');
    Route::post('/customer-chats/call/answer', [App\Http\Controllers\DoctorPortal\DoctorCustomerChatController::class, 'callAnswer'])->name('customer-chats.call.answer');
    Route::get('/customer-chats/call/answer', [App\Http\Controllers\DoctorPortal\DoctorCustomerChatController::class, 'getCallAnswer'])->name('customer-chats.call.answer.get');
    Route::get('/customer-chats/call/ice', [App\Http\Controllers\DoctorPortal\DoctorCustomerChatController::class, 'getCallIce'])->name('customer-chats.call.ice');
    Route::post('/customer-chats/call/ice', [App\Http\Controllers\DoctorPortal\DoctorCustomerChatController::class, 'sendCallIce'])->name('customer-chats.call.ice.send');
    Route::post('/customer-chats/call/end', [App\Http\Controllers\DoctorPortal\DoctorCustomerChatController::class, 'callEnd'])->name('customer-chats.call.end');
    Route::post('/customer-chats/translate', [App\Http\Controllers\DoctorPortal\DoctorCustomerChatController::class, 'translate'])->name('customer-chats.translate');
    Route::get('/refer-leads', [App\Http\Controllers\DoctorPortal\DoctorPortalController::class, 'referLeads'])->name('refer-leads');
    Route::post('/refer-leads', [App\Http\Controllers\DoctorPortal\DoctorPortalController::class, 'storeReferLead'])->name('refer-leads.store');
});

// Vendor Routes
Route::prefix('vendor')->name('vendor.')->middleware(['vendor.auth'])->group(function () {
    Route::get('/dashboard', [App\Http\Controllers\Vendor\VendorController::class, 'dashboard'])->name('dashboard');
        Route::get('/personal-details', [App\Http\Controllers\Vendor\VendorController::class, 'personalDetails'])->name('personal-details');
        Route::post('/personal-details/update-profile-image', [App\Http\Controllers\Vendor\VendorController::class, 'updateProfileImage'])->name('personal-details.update-profile-image');
        Route::post('/personal-details/upload-document', [App\Http\Controllers\Vendor\VendorController::class, 'uploadDocument'])->name('personal-details.upload-document');
        Route::post('/personal-details/price-change-request', [App\Http\Controllers\Vendor\VendorController::class, 'submitPriceChangeRequest'])->name('personal-details.price-change-request');
    Route::get('/bank-details', [App\Http\Controllers\Vendor\VendorController::class, 'bankDetails'])->name('bank-details');
    Route::get('/emergency-details', [App\Http\Controllers\Vendor\VendorController::class, 'emergencyDetails'])->name('emergency-details');
    Route::get('/payment-details', [App\Http\Controllers\Vendor\VendorController::class, 'paymentDetails'])->name('payment-details');
    Route::get('/statement', [App\Http\Controllers\Vendor\VendorController::class, 'statement'])->name('statement');
    Route::get('/assigned-leads', [App\Http\Controllers\Vendor\VendorController::class, 'assignedLeads'])->name('assigned-leads');
    Route::post('/attendance/location', [App\Http\Controllers\Vendor\VendorController::class, 'submitAttendanceLocation'])->name('attendance.location');
    Route::post('/attendance/statuses', [App\Http\Controllers\Vendor\VendorController::class, 'attendanceStatuses'])->name('attendance.statuses');
    Route::get('/customer-chats', [App\Http\Controllers\Vendor\ChatController::class, 'index'])->name('customer-chats');
    Route::get('/customer-chats/messages/{customerId}', [App\Http\Controllers\Vendor\ChatController::class, 'getMessages'])->name('customer-chats.messages');
    Route::post('/customer-chats/send', [App\Http\Controllers\Vendor\ChatController::class, 'sendMessage'])->name('customer-chats.send');
    Route::post('/customer-chats/mark-read/{customerId}', [App\Http\Controllers\Vendor\ChatController::class, 'markAsRead'])->name('customer-chats.mark-read');
    Route::get('/customer-chats/unread-counts', [App\Http\Controllers\Vendor\ChatController::class, 'getUnreadCounts'])->name('customer-chats.unread-counts');
    Route::get('/customer-chats/call/offer', [App\Http\Controllers\Vendor\ChatController::class, 'getCallOffer'])->name('customer-chats.call.offer');
    Route::post('/customer-chats/call/offer', [App\Http\Controllers\Vendor\ChatController::class, 'sendCallOffer'])->name('customer-chats.call.offer.send');
    Route::post('/customer-chats/call/answer', [App\Http\Controllers\Vendor\ChatController::class, 'callAnswer'])->name('customer-chats.call.answer');
    Route::get('/customer-chats/call/answer', [App\Http\Controllers\Vendor\ChatController::class, 'getCallAnswer'])->name('customer-chats.call.answer.get');
    Route::get('/customer-chats/call/ice', [App\Http\Controllers\Vendor\ChatController::class, 'getCallIce'])->name('customer-chats.call.ice');
    Route::post('/customer-chats/call/ice', [App\Http\Controllers\Vendor\ChatController::class, 'sendCallIce'])->name('customer-chats.call.ice.send');
    Route::post('/customer-chats/call/end', [App\Http\Controllers\Vendor\ChatController::class, 'callEnd'])->name('customer-chats.call.end');
    Route::post('/customer-chats/translate', [App\Http\Controllers\Vendor\ChatController::class, 'translate'])->name('customer-chats.translate');
});

// Customer Routes
Route::prefix('customer')->name('customer.')->group(function () {
    Route::get('/dashboard', [App\Http\Controllers\Customer\CustomerController::class, 'dashboard'])->name('dashboard');
    Route::get('/consultation-bookings/{bookingId}/join-meeting', [App\Http\Controllers\Customer\CustomerController::class, 'joinConsultationMeeting'])
        ->whereNumber('bookingId')
        ->name('consultation-bookings.join-meeting');
    Route::get('/personal-details', [App\Http\Controllers\Customer\CustomerController::class, 'personalDetails'])->name('personal-details');
    Route::post('/personal-details/update-profile-image', [App\Http\Controllers\Customer\CustomerController::class, 'updateProfileImage'])->name('personal-details.update-profile-image');
    Route::get('/chats', [App\Http\Controllers\Customer\CustomerController::class, 'chats'])->name('chats');
    Route::get('/chats/messages/{chatType}/{chatId}', [App\Http\Controllers\Customer\CustomerController::class, 'getMessages'])->name('chats.messages');
    Route::post('/chats/send', [App\Http\Controllers\Customer\CustomerController::class, 'sendMessage'])->name('chats.send');
    Route::post('/chats/mark-read/{chatType}/{chatId}', [App\Http\Controllers\Customer\CustomerController::class, 'markAsRead'])->name('chats.mark-read');
    Route::post('/chats/call/offer', [App\Http\Controllers\Customer\CustomerController::class, 'callOffer'])->name('chats.call.offer');
    Route::get('/chats/call/answer', [App\Http\Controllers\Customer\CustomerController::class, 'callAnswer'])->name('chats.call.answer');
    Route::post('/chats/call/ice', [App\Http\Controllers\Customer\CustomerController::class, 'callIce'])->name('chats.call.ice');
    Route::post('/chats/call/end', [App\Http\Controllers\Customer\CustomerController::class, 'callEnd'])->name('chats.call.end');
    Route::post('/chats/translate', [App\Http\Controllers\Customer\CustomerController::class, 'translate'])->name('chats.translate');
    Route::post('/service-request/submit', [App\Http\Controllers\Customer\CustomerController::class, 'submitServiceRequest'])->name('service-request.submit');
    Route::post('/attendance/location', [App\Http\Controllers\Customer\CustomerController::class, 'submitAttendanceLocation'])->name('attendance.location');
    Route::post('/attendance/location-attendance-enabled', [App\Http\Controllers\Customer\CustomerController::class, 'setLocationAttendanceEnabled'])->name('attendance.location-attendance-enabled');
    Route::get('/payment-details', [App\Http\Controllers\Customer\CustomerController::class, 'paymentDetails'])->name('payment-details');
    Route::get('/payment-details/invoice/{id}', [App\Http\Controllers\Customer\CustomerController::class, 'showPaymentInvoice'])->name('payment_invoice.show');
});

// Legacy B2B portal (admin → B2B Users — separate from corporate/individual partners)
Route::prefix('b2b')->name('b2b.')->middleware(['b2b.auth'])->group(function () {
    Route::get('/dashboard', [App\Http\Controllers\B2B\B2BLeadController::class, 'dashboard'])->name('dashboard');
    Route::get('/leads', [App\Http\Controllers\B2B\B2BLeadController::class, 'index'])->name('leads.index');
    Route::get('/leads/{id}', [App\Http\Controllers\B2B\B2BLeadController::class, 'show'])->name('leads.show')->whereNumber('id');
    Route::post('/leads', [App\Http\Controllers\B2B\B2BLeadController::class, 'store'])->name('leads.store');
    Route::post('/leads/import', [App\Http\Controllers\B2B\B2BLeadController::class, 'import'])->name('leads.import');
    Route::get('/leads/template', [App\Http\Controllers\B2B\B2BLeadController::class, 'downloadTemplate'])->name('leads.template');
});

Route::prefix('b2b-corporate')->name('b2b.corporate.')->middleware(['b2b.corporate.auth'])->group(function () {
    Route::get('/dashboard', [App\Http\Controllers\B2B\B2BLeadController::class, 'dashboard'])->name('dashboard');
    Route::get('/leads', [App\Http\Controllers\B2B\B2BLeadController::class, 'index'])->name('leads.index');
    Route::get('/leads/{id}', [App\Http\Controllers\B2B\B2BLeadController::class, 'show'])->name('leads.show')->whereNumber('id');
    Route::post('/leads', [App\Http\Controllers\B2B\B2BLeadController::class, 'store'])->name('leads.store');
    Route::post('/leads/import', [App\Http\Controllers\B2B\B2BLeadController::class, 'import'])->name('leads.import');
    Route::get('/leads/template', [App\Http\Controllers\B2B\B2BLeadController::class, 'downloadTemplate'])->name('leads.template');
    Route::get('/chat', [App\Http\Controllers\B2B\B2BCorporateChatController::class, 'index'])->name('chat.index');
    Route::get('/chat/messages/group', [App\Http\Controllers\B2B\B2BCorporateChatController::class, 'getGroupMessages'])->name('chat.messages.group');
    Route::get('/chat/messages/{userId}', [App\Http\Controllers\B2B\B2BCorporateChatController::class, 'getDirectMessages'])->name('chat.messages.direct')->whereNumber('userId');
    Route::post('/chat/send', [App\Http\Controllers\B2B\B2BCorporateChatController::class, 'sendMessage'])->name('chat.send');
    Route::post('/chat/send-attachment', [App\Http\Controllers\B2B\B2BCorporateChatController::class, 'sendAttachment'])->name('chat.send_attachment');
    Route::post('/chat/mark-read/group', [App\Http\Controllers\B2B\B2BCorporateChatController::class, 'markGroupAsRead'])->name('chat.mark_read.group');
    Route::post('/chat/mark-read/{userId}', [App\Http\Controllers\B2B\B2BCorporateChatController::class, 'markDirectAsRead'])->name('chat.mark_read.direct')->whereNumber('userId');
    Route::get('/chat/call/{userId}', [App\Http\Controllers\B2B\B2BCorporateChatController::class, 'initiateCall'])->name('chat.call')->whereNumber('userId');
    Route::get('/chat/unread-counts', [App\Http\Controllers\B2B\B2BCorporateChatController::class, 'unreadCounts'])->name('chat.unread_counts');
});

Route::prefix('b2b-individual')->name('b2b.individual.')->middleware(['b2b.individual.auth'])->group(function () {
    Route::get('/dashboard', [App\Http\Controllers\B2B\B2BLeadController::class, 'dashboard'])->name('dashboard');
    Route::get('/leads', [App\Http\Controllers\B2B\B2BLeadController::class, 'index'])->name('leads.index');
    Route::get('/leads/{id}', [App\Http\Controllers\B2B\B2BLeadController::class, 'show'])->name('leads.show')->whereNumber('id');
    Route::post('/leads', [App\Http\Controllers\B2B\B2BLeadController::class, 'store'])->name('leads.store');
    Route::post('/leads/import', [App\Http\Controllers\B2B\B2BLeadController::class, 'import'])->name('leads.import');
    Route::get('/leads/template', [App\Http\Controllers\B2B\B2BLeadController::class, 'downloadTemplate'])->name('leads.template');
});

// B2B reference users (referrers) — OTP login, read-only aggregate leads
Route::prefix('b2b-reference')->name('b2b_reference.')->middleware(['b2b.reference.auth'])->group(function () {
    Route::get('/dashboard', [App\Http\Controllers\B2B\B2BReferenceLeadController::class, 'dashboard'])->name('dashboard');
    Route::get('/leads', [App\Http\Controllers\B2B\B2BReferenceLeadController::class, 'leads'])->name('leads');
});

// Doctor referral users (referrers) — OTP login; lists doctors assigned to them by admin
Route::get('/doctor-referral/login', [App\Http\Controllers\DoctorReferral\DoctorReferralPortalController::class, 'showLogin'])->name('doctor_referral.login');
Route::prefix('doctor-referral')->name('doctor_referral.')->middleware(['doctor.referral.auth'])->group(function () {
    Route::get('/dashboard', [App\Http\Controllers\DoctorReferral\DoctorReferralPortalController::class, 'dashboard'])->name('dashboard');
    Route::get('/leads', [App\Http\Controllers\DoctorReferral\DoctorReferralPortalController::class, 'leads'])->name('leads');
});

// Insurer portal — username/password login (credentials created by admin)
Route::prefix('insurer')->name('insurer.')->group(function () {
    Route::get('/login', [App\Http\Controllers\Insurer\InsurerAuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [App\Http\Controllers\Insurer\InsurerAuthController::class, 'login'])->name('login.submit');
    Route::post('/logout', [App\Http\Controllers\Insurer\InsurerAuthController::class, 'logout'])->name('logout');

    Route::middleware(['insurer.auth'])->group(function () {
        Route::get('/dashboard', [App\Http\Controllers\Insurer\InsurerPortalController::class, 'dashboard'])->name('dashboard');
        Route::get('/password', [App\Http\Controllers\Insurer\InsurerPortalController::class, 'showPasswordForm'])->name('password');
        Route::put('/password', [App\Http\Controllers\Insurer\InsurerPortalController::class, 'updatePassword'])->name('password.update');
        Route::resource('corporates', App\Http\Controllers\Insurer\InsurerCorporateController::class)->except(['show']);
        Route::get('corporates/{corporate}/employees', [App\Http\Controllers\Insurer\InsurerCorporateEmployeeController::class, 'index'])->name('corporates.employees.index');
    });
});

// Broker portal — username/password login (credentials created by admin)
Route::prefix('broker')->name('broker.')->group(function () {
    Route::get('/login', [App\Http\Controllers\Broker\BrokerAuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [App\Http\Controllers\Broker\BrokerAuthController::class, 'login'])->name('login.submit');
    Route::post('/logout', [App\Http\Controllers\Broker\BrokerAuthController::class, 'logout'])->name('logout');

    Route::middleware(['broker.auth'])->group(function () {
        Route::get('/dashboard', [App\Http\Controllers\Broker\BrokerPortalController::class, 'dashboard'])->name('dashboard');
        Route::get('/password', [App\Http\Controllers\Broker\BrokerPortalController::class, 'showPasswordForm'])->name('password');
        Route::put('/password', [App\Http\Controllers\Broker\BrokerPortalController::class, 'updatePassword'])->name('password.update');
        Route::resource('corporates', App\Http\Controllers\Broker\BrokerCorporateController::class)->except(['show']);
        Route::get('corporates/{corporate}/employees', [App\Http\Controllers\Broker\BrokerCorporateEmployeeController::class, 'index'])->name('corporates.employees.index');
    });
});

// Corporate portal — shared login for all corporates (credentials set by insurer or broker)
Route::prefix('corporate')->name('corporate.')->group(function () {
    Route::get('/login', [App\Http\Controllers\Corporate\CorporateAuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [App\Http\Controllers\Corporate\CorporateAuthController::class, 'login'])->name('login.submit');
    Route::post('/logout', [App\Http\Controllers\Corporate\CorporateAuthController::class, 'logout'])->name('logout');

    Route::middleware(['corporate.auth'])->group(function () {
        Route::get('/dashboard', [App\Http\Controllers\Corporate\CorporatePortalController::class, 'dashboard'])->name('dashboard');
        Route::get('/password', [App\Http\Controllers\Corporate\CorporatePortalController::class, 'showPasswordForm'])->name('password');
        Route::put('/password', [App\Http\Controllers\Corporate\CorporatePortalController::class, 'updatePassword'])->name('password.update');
        Route::get('/employees/bulk/template', [App\Http\Controllers\Corporate\CorporateEmployeeController::class, 'downloadTemplate'])->name('employees.template');
        Route::post('/employees/bulk/import', [App\Http\Controllers\Corporate\CorporateEmployeeController::class, 'importBulk'])->name('employees.import');
        Route::resource('employees', App\Http\Controllers\Corporate\CorporateEmployeeController::class)->except(['show']);
    });

    Route::prefix('employee')->name('employee.')->group(function () {
        Route::get('/login', [App\Http\Controllers\Corporate\CorporateEmployeeAuthController::class, 'showLogin'])->name('login');
        Route::post('/login', [App\Http\Controllers\Corporate\CorporateEmployeeAuthController::class, 'login'])->name('login.submit');
        Route::post('/logout', [App\Http\Controllers\Corporate\CorporateEmployeeAuthController::class, 'logout'])->name('logout');

        Route::middleware(['corporate.employee.auth'])->group(function () {
            Route::get('/dashboard', [App\Http\Controllers\Corporate\CorporateEmployeePortalController::class, 'dashboard'])->name('dashboard');
        });
    });
});

Route::post('/update-profile-image/{member_id?}', [Controllers\AuthController::class, 'update_profile_image'])->name('updateProfileImage');

// Routes for Admin Role
Route::prefix('/admin')->middleware(['auth', 'role:Admin'])->group(function () {
    Route::get('/dashboard', [Controllers\Admin\AdminController::class, 'dashboard'])->name('admin.dashboard');
    Route::get('/bulk-registration', [Controllers\Admin\BulkRegistrationController::class, 'index'])->name('admin.bulk_registration.index');
    Route::post('/bulk-registration', [Controllers\Admin\BulkRegistrationController::class, 'store'])->name('admin.bulk_registration.store');
    Route::get('/bulk-registration/cities-by-tier', [Controllers\Admin\BulkRegistrationController::class, 'getCitiesByTier'])->name('admin.bulk_registration.cities_by_tier');
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
    Route::get('/location-attendance', [Controllers\Admin\AdminController::class, 'locationAttendance'])->name('admin.location_attendance.index');
    Route::get('/location-attendance/deployments/{deployment}', [Controllers\Admin\AdminController::class, 'locationAttendanceDeploymentDetail'])->name('admin.location_attendance.deployment_detail');

    Route::get('/payments', [App\Http\Controllers\EasebuzzPaymentLinkController::class, 'index'])->name('admin.payments.index');
    Route::get('/payments/create', [App\Http\Controllers\EasebuzzPaymentLinkController::class, 'create'])->name('admin.payments.create');
    Route::post('/payments', [App\Http\Controllers\EasebuzzPaymentLinkController::class, 'store'])->name('admin.payments.store');
    Route::get('/payments/{payment}', [App\Http\Controllers\EasebuzzPaymentLinkController::class, 'show'])->name('admin.payments.show');
    Route::post('/payments/{payment}/verify', [App\Http\Controllers\EasebuzzPaymentLinkController::class, 'verify'])->name('admin.payments.verify');
        Route::get('/operation-leads/vendor-payment-details/{vendorId}', [Controllers\Admin\OperationLeadController::class, 'getVendorPaymentDetails'])->name('admin.operation_leads.vendor_payment_details');
        Route::get('/operation-leads/freelancer-payment-details/{freelancerId}', [Controllers\Admin\OperationLeadController::class, 'getFreelancerPaymentDetails'])->name('admin.operation_leads.freelancer_payment_details');
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
    Route::get('locations/bulk-template', [Controllers\Admin\LocationController::class, 'downloadBulkTemplate'])->name('admin.locations.bulk-template');
    Route::post('locations/bulk-import', [Controllers\Admin\LocationController::class, 'bulkImport'])->name('admin.locations.bulk-import');
    Route::get('locations/service-rates', [Controllers\Admin\LocationController::class, 'getServiceRates'])->name('admin.locations.service-rates');
    Route::resource('locations', Controllers\Admin\LocationController::class)->names('admin.locations');

    // Service Management
    Route::resource('services', ServiceController::class)->names('admin.services');

    // Registration languages (form i18n)
    Route::resource('languages', App\Http\Controllers\Admin\LanguageController::class)->names('admin.languages')->except(['show']);
    Route::get('languages/{language}/translations', [App\Http\Controllers\Admin\LanguageController::class, 'translations'])->name('admin.languages.translations');
    Route::put('languages/{language}/translations', [App\Http\Controllers\Admin\LanguageController::class, 'updateTranslations'])->name('admin.languages.translations.update');
    Route::post('languages/{language}/translations/reset', [App\Http\Controllers\Admin\LanguageController::class, 'resetTranslations'])->name('admin.languages.translations.reset');
    Route::post('languages/{language}/auto-translate', [App\Http\Controllers\Admin\LanguageController::class, 'autoTranslate'])->name('admin.languages.auto_translate');

    // Doctor registration: consultation service dropdown (separate from main Services)
    Route::resource('doctor-consultation-services', DoctorConsultationServiceController::class)
        ->names('admin.doctor_consultation_services')
        ->except(['show']);

    Route::get('users/getUsers', [Controllers\Admin\UserController::class, 'getUsers'])->name('admin.users.getUsers');
    Route::get('users/manage/{id?}', [Controllers\Admin\UserController::class, 'manage'])->name('admin.users.manage');
    Route::post('users/manage/{id?}', [Controllers\Admin\UserController::class, 'manage_process'])->name('admin.users.manage_process');
    Route::post('users/{id}/create-agent', [Controllers\Admin\UserController::class, 'createAgent'])->name('admin.users.createAgent');
    Route::patch('users/{id}/update-agent', [Controllers\Admin\UserController::class, 'updateAgent'])->name('admin.users.updateAgent');
    Route::delete('users/{id}/delete-agent', [Controllers\Admin\UserController::class, 'deleteAgent'])->name('admin.users.deleteAgent');
    // Delete user
    Route::get('users/{id}', [Controllers\Admin\UserController::class, 'destroy'])->name('admin.users.destroy');

    // B2B user management
    Route::get('b2b-users', [Controllers\Admin\B2BUserController::class, 'index'])->name('admin.b2b_users.index');
    Route::post('b2b-users', [Controllers\Admin\B2BUserController::class, 'store'])->name('admin.b2b_users.store');
    Route::post('b2b-users/{b2bUser}', [Controllers\Admin\B2BUserController::class, 'update'])->name('admin.b2b_users.update');
    Route::delete('b2b-users/{b2bUser}', [Controllers\Admin\B2BUserController::class, 'destroy'])->name('admin.b2b_users.destroy');
    Route::get('b2b-users/options', [Controllers\Admin\B2BUserController::class, 'apiIndex'])->name('admin.b2b_users.options');

    Route::get('corporate-individual-b2b', [App\Http\Controllers\Admin\CorporateIndividualHubController::class, 'index'])->name('admin.corporate_individual.hub');
    Route::get('b2b-corporate-partners', [App\Http\Controllers\Admin\B2BPartnerAccountController::class, 'indexCorporate'])->name('admin.b2b_corporate.index');
    Route::post('b2b-corporate-partners', [App\Http\Controllers\Admin\B2BPartnerAccountController::class, 'storeCorporate'])->name('admin.b2b_corporate.store');
    Route::post('b2b-corporate-partners/{b2bUser}', [App\Http\Controllers\Admin\B2BPartnerAccountController::class, 'updateCorporate'])->name('admin.b2b_corporate.update');
    Route::delete('b2b-corporate-partners/{b2bUser}', [App\Http\Controllers\Admin\B2BPartnerAccountController::class, 'destroyCorporate'])->name('admin.b2b_corporate.destroy');
    Route::get('b2b-individual-partners', [App\Http\Controllers\Admin\B2BPartnerAccountController::class, 'indexIndividual'])->name('admin.b2b_individual.index');
    Route::post('b2b-individual-partners', [App\Http\Controllers\Admin\B2BPartnerAccountController::class, 'storeIndividual'])->name('admin.b2b_individual.store');
    Route::post('b2b-individual-partners/{b2bUser}', [App\Http\Controllers\Admin\B2BPartnerAccountController::class, 'updateIndividual'])->name('admin.b2b_individual.update');
    Route::delete('b2b-individual-partners/{b2bUser}', [App\Http\Controllers\Admin\B2BPartnerAccountController::class, 'destroyIndividual'])->name('admin.b2b_individual.destroy');

    Route::post('b2b-reference-users', [Controllers\Admin\B2BReferenceUserController::class, 'store'])->name('admin.b2b_reference_users.store');
    Route::delete('b2b-reference-users/{b2bReferenceUser}', [Controllers\Admin\B2BReferenceUserController::class, 'destroy'])->name('admin.b2b_reference_users.destroy');

    Route::post('doctor-referral-users', [Controllers\Admin\DoctorReferralUserController::class, 'store'])->name('admin.doctor_referral_users.store');
    Route::delete('doctor-referral-users/{doctorReferralUser}', [Controllers\Admin\DoctorReferralUserController::class, 'destroy'])->name('admin.doctor_referral_users.destroy');

    // Insurer & Broker portal accounts (username/password login)
    Route::resource('insurers', App\Http\Controllers\Admin\InsurerUserController::class)->names('admin.insurers')->except(['show']);
    Route::resource('brokers', App\Http\Controllers\Admin\BrokerUserController::class)->names('admin.brokers')->except(['show']);
    Route::get('corporate-accounts', [App\Http\Controllers\Admin\CorporateUserController::class, 'index'])->name('admin.corporate-accounts.index');
    Route::get('corporate-employees', [App\Http\Controllers\Admin\CorporateEmployeeController::class, 'index'])->name('admin.corporate-employees.index');

    Route::get('/leads', [App\Http\Controllers\Admin\LeadController::class, 'index'])->name('admin.leads.index');
    Route::get('/leads/getLeads', [App\Http\Controllers\Admin\LeadController::class, 'getLeads'])->name('admin.leads.getLeads');
    Route::get('/leads/{id}', [App\Http\Controllers\Admin\LeadController::class, 'show'])->name('admin.leads.show');
    Route::delete('/leads/{id}', [App\Http\Controllers\Admin\LeadController::class, 'destroy'])->name('admin.leads.destroy');
    Route::post('/leads', [App\Http\Controllers\Admin\LeadController::class, 'store'])->name('admin.leads.store');
    Route::get('/leads/{id}/edit', [App\Http\Controllers\Admin\LeadController::class, 'edit'])->name('admin.leads.edit');
    Route::put('/leads/{id}', [App\Http\Controllers\Admin\LeadController::class, 'update'])->name('admin.leads.update');
    Route::put('/leads/{lead}/status-remarks/{remark}', [App\Http\Controllers\Admin\LeadController::class, 'updateStatusRemark'])->name('admin.leads.status-remarks.update');

    Route::get('/referral-leads', [App\Http\Controllers\Admin\ReferralLeadController::class, 'index'])->name('admin.referral_leads.index');
    Route::post('/referral-leads/commission', [App\Http\Controllers\Admin\ReferralLeadController::class, 'updateCommission'])->name('admin.referral_leads.commission');
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
    Route::post('operation-leads/deployment/{id}/dates', [App\Http\Controllers\Admin\OperationLeadController::class, 'getDeploymentDates'])->name('admin.operation_leads.deployment.dates');
    Route::post('operation-leads/deployment/{id}/toggle-absent', [App\Http\Controllers\Admin\OperationLeadController::class, 'toggleDeploymentAbsentDate'])->name('admin.operation_leads.deployment.toggle-absent');
    Route::post('operation-leads/deployment/{id}/save-absent', [App\Http\Controllers\Admin\OperationLeadController::class, 'saveDeploymentAbsentDates'])->name('admin.operation_leads.deployment.save-absent');

    // Payment Invoice and Received Payment Routes
    Route::get('operation-leads/payment-invoice/{id}', [App\Http\Controllers\Admin\OperationLeadController::class, 'showPaymentInvoice'])->name('admin.operation_leads.payment_invoice.show');
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
    Route::post('chat/translate', [App\Http\Controllers\Admin\ChatController::class, 'translate'])->name('admin.chat.translate');

    // Staff Chats Routes
    Route::get('/staff/chats', [App\Http\Controllers\Admin\StaffChatController::class, 'index'])->name('admin.staff.chats');
    Route::get('/staff/chats/messages/{salesId}/{participantId}', [App\Http\Controllers\Admin\StaffChatController::class, 'getMessages'])->name('admin.staff.chats.messages');
    Route::post('/staff/chats/send', [App\Http\Controllers\Admin\StaffChatController::class, 'sendMessage'])->name('admin.staff.chats.send');
    Route::post('/staff/chats/mark-read/{userId}', [App\Http\Controllers\Admin\StaffChatController::class, 'markAsRead'])->name('admin.staff.chats.mark-read');
    Route::get('/staff/chats/unread-counts', [App\Http\Controllers\Admin\StaffChatController::class, 'getUnreadCounts'])->name('admin.staff.chats.unread-counts');

    // Customer Chats Routes
    Route::get('/customer-chats', [App\Http\Controllers\Admin\CustomerChatController::class, 'index'])->name('admin.customer_chats.index');
    Route::get('/customer-chats/messages', [App\Http\Controllers\Admin\CustomerChatController::class, 'getMessages'])->name('admin.customer_chats.messages');

    // Vendor Routes
    Route::get('/vendors', [App\Http\Controllers\Admin\VendorController::class, 'index'])->name('admin.vendors.index');
    Route::post('/vendors', [App\Http\Controllers\Admin\VendorController::class, 'store'])->name('admin.vendors.store');
    Route::get('/vendors/edit/{id?}', [App\Http\Controllers\Admin\VendorController::class, 'edit'])->name('admin.vendors.edit');
    Route::put('/vendors/{id?}', [App\Http\Controllers\Admin\VendorController::class, 'update'])->name('admin.vendors.update');
    Route::delete('/vendors/{id?}', [App\Http\Controllers\Admin\VendorController::class, 'destroy'])->name('admin.vendors.destroy');
    Route::get('/vendors/{vendor}/price-change-requests', [App\Http\Controllers\Admin\VendorController::class, 'priceChangeRequests'])->name('admin.vendors.price_change_requests');
    Route::post('/vendors/price-change-requests/{price_change_request}/approve', [App\Http\Controllers\Admin\VendorController::class, 'approvePriceChangeRequest'])->name('admin.vendors.price_change_approve');
    Route::get('/vendors/{vendor}/leegality/preview-agreement', [App\Http\Controllers\Admin\VendorLeegalitySignatureController::class, 'previewAgreement'])->name('admin.vendors.leegality_preview_agreement');
    Route::post('/vendors/{vendor}/leegality/send', [App\Http\Controllers\Admin\VendorLeegalitySignatureController::class, 'send'])->name('admin.vendors.leegality_send');
    Route::post('/vendors/{vendor}/leegality/refresh', [App\Http\Controllers\Admin\VendorLeegalitySignatureController::class, 'refresh'])->name('admin.vendors.leegality_refresh');
    Route::post('/vendors/{vendor}/leegality/add-my-signature', [App\Http\Controllers\Admin\VendorLeegalitySignatureController::class, 'addMySignature'])->name('admin.vendors.leegality_add_my_signature');
    Route::get('/vendors/{vendor}/leegality/test-add-my-signature', [App\Http\Controllers\Admin\VendorLeegalitySignatureController::class, 'testAddMySignature'])->name('admin.vendors.leegality_test_add_my_signature');
    Route::get('/vendors/{vendor}/leegality/{signature}/view-signed', [App\Http\Controllers\Admin\VendorLeegalitySignatureController::class, 'viewSigned'])->name('admin.vendors.leegality_view_signed');
    Route::get('/vendors/{vendor}/leegality/{signature}/download-signed', [App\Http\Controllers\Admin\VendorLeegalitySignatureController::class, 'downloadSigned'])->name('admin.vendors.leegality_download_signed');
    Route::get('/vendors/{vendor}/leegality/{signature}/download-audit', [App\Http\Controllers\Admin\VendorLeegalitySignatureController::class, 'downloadAudit'])->name('admin.vendors.leegality_download_audit');

    // Vendor Payment Routes
    Route::get('/website-consultation-payments', [App\Http\Controllers\Admin\ConsultationWebsitePaymentHistoryController::class, 'index'])->name('admin.website_consultation_payments.index');
    Route::get('/website-consultation-payments/{booking}/view-modal', [App\Http\Controllers\Admin\ConsultationWebsitePaymentHistoryController::class, 'viewModal'])->name('admin.website_consultation_payments.view_modal');

    Route::get('/vendor-payments', [App\Http\Controllers\Admin\VendorPaymentController::class, 'index'])->name('admin.vendor_payments.index');
    Route::get('/vendor-payments/history', [App\Http\Controllers\Admin\VendorPaymentController::class, 'vendorHistory'])->name('admin.vendor_payments.history');
    Route::post('/vendor-payments', [App\Http\Controllers\Admin\VendorPaymentController::class, 'store'])->name('admin.vendor_payments.store');
    Route::get('/vendor-payments/{id}', [App\Http\Controllers\Admin\VendorPaymentController::class, 'show'])->name('admin.vendor_payments.show');
    Route::get('/vendor-payments/{id}/edit', [App\Http\Controllers\Admin\VendorPaymentController::class, 'edit'])->name('admin.vendor_payments.edit');
    Route::put('/vendor-payments/{id}', [App\Http\Controllers\Admin\VendorPaymentController::class, 'update'])->name('admin.vendor_payments.update');
    Route::delete('/vendor-payments/{id}', [App\Http\Controllers\Admin\VendorPaymentController::class, 'destroy'])->name('admin.vendor_payments.destroy');
    Route::get('/vendor-payments/vendor/{vendorId}', [App\Http\Controllers\Admin\VendorPaymentController::class, 'getVendorPayments'])->name('admin.vendor_payments.vendor');
    Route::get('/vendor-payments/vendor/{vendorId}/statement', [App\Http\Controllers\Admin\VendorPaymentController::class, 'statement'])->name('admin.vendor_payments.statement');
    Route::get('/vendor-payments/stats', [App\Http\Controllers\Admin\VendorPaymentController::class, 'getPaymentStats'])->name('admin.vendor_payments.stats');
    Route::get('/vendor-payments/{id}/screenshot', [App\Http\Controllers\Admin\VendorPaymentController::class, 'downloadScreenshot'])->name('admin.vendor_payments.screenshot');
    Route::get('/vendor-payments/{id}/invoice', [App\Http\Controllers\Admin\VendorPaymentController::class, 'invoice'])->name('admin.vendor_payments.invoice');
    
    // Freelancer Payment Routes
    Route::get('/freelancer-payments', [App\Http\Controllers\Admin\FreelancerPaymentController::class, 'index'])->name('admin.freelancer_payments.index');
    Route::post('/freelancer-payments', [App\Http\Controllers\Admin\FreelancerPaymentController::class, 'store'])->name('admin.freelancer_payments.store');
    Route::get('/freelancer-payments/{id}', [App\Http\Controllers\Admin\FreelancerPaymentController::class, 'show'])->name('admin.freelancer_payments.show');
    Route::get('/freelancer-payments/{id}/edit', [App\Http\Controllers\Admin\FreelancerPaymentController::class, 'edit'])->name('admin.freelancer_payments.edit');
    Route::put('/freelancer-payments/{id}', [App\Http\Controllers\Admin\FreelancerPaymentController::class, 'update'])->name('admin.freelancer_payments.update');
    Route::delete('/freelancer-payments/{id}', [App\Http\Controllers\Admin\FreelancerPaymentController::class, 'destroy'])->name('admin.freelancer_payments.destroy');
    Route::get('/freelancer-payments/{id}/invoice', [App\Http\Controllers\Admin\FreelancerPaymentController::class, 'invoice'])->name('admin.freelancer_payments.invoice');
    Route::get('/freelancer-payments/statement/{freelancerId}', [App\Http\Controllers\Admin\FreelancerPaymentController::class, 'statement'])->name('admin.freelancer_payments.statement');
    Route::get('/freelancer-payments/freelancer/{freelancerId}', [App\Http\Controllers\Admin\FreelancerPaymentController::class, 'getFreelancerPayments'])->name('admin.freelancer_payments.freelancer');

    Route::get('/jobproc', [App\Http\Controllers\Admin\JobProcController::class, 'index'])->name('admin.jobproc.index');
    Route::get('/jobproc/jobrequests', [App\Http\Controllers\Admin\JobProcController::class, 'getJobRequests'])->name('admin.jobproc.jobrequests');
    Route::get('/jobproc/prospects', [App\Http\Controllers\Admin\JobProcController::class, 'getProspects'])->name('admin.jobproc.prospects');
    Route::post('/jobproc/store', [App\Http\Controllers\Admin\JobProcController::class, 'store'])->name('admin.jobproc.store');
    Route::post('/jobproc/import', [App\Http\Controllers\Admin\JobProcController::class, 'import'])->name('admin.jobproc.import');
    Route::post('/jobproc/{job_request}/profile-image/generate-uniform', [App\Http\Controllers\Admin\JobProcController::class, 'generateProfileImageUniform'])->name('admin.jobproc.profile_image_generate');
    Route::post('/jobproc/{job_request}/profile-image/approve', [App\Http\Controllers\Admin\JobProcController::class, 'approveProfileImage'])->name('admin.jobproc.profile_image_approve');
    Route::get('/jobproc/{job_request}/price-change-requests', [App\Http\Controllers\Admin\JobProcController::class, 'priceChangeRequests'])->name('admin.jobproc.price_change_requests');
    Route::post('/jobproc/price-change-requests/{price_change_request}/approve', [App\Http\Controllers\Admin\JobProcController::class, 'approvePriceChangeRequest'])->name('admin.jobproc.price_change_approve');
    Route::post('/jobproc/price-change-requests/{price_change_request}/reject', [App\Http\Controllers\Admin\JobProcController::class, 'rejectPriceChangeRequest'])->name('admin.jobproc.price_change_reject');
    Route::get('/jobproc/{id}', [App\Http\Controllers\Admin\JobProcController::class, 'show'])->name('admin.jobproc.show');
    Route::get('/jobproc/{id}/edit', [App\Http\Controllers\Admin\JobProcController::class, 'edit'])->name('admin.jobproc.edit');
    Route::put('/jobproc/{id}', [App\Http\Controllers\Admin\JobProcController::class, 'update'])->name('admin.jobproc.update');
    Route::delete('/jobproc/{id}', [App\Http\Controllers\Admin\JobProcController::class, 'destroy'])->name('admin.jobproc.destroy');

    Route::get('/jobproc/{job_request}/leegality/preview-agreement', [App\Http\Controllers\Admin\FreelancerLeegalitySignatureController::class, 'previewAgreement'])->name('admin.jobproc.leegality_preview_agreement');
    Route::post('/jobproc/{job_request}/leegality/send', [App\Http\Controllers\Admin\FreelancerLeegalitySignatureController::class, 'send'])->name('admin.jobproc.leegality_send');
    Route::post('/jobproc/{job_request}/leegality/refresh', [App\Http\Controllers\Admin\FreelancerLeegalitySignatureController::class, 'refresh'])->name('admin.jobproc.leegality_refresh');
    Route::post('/jobproc/{job_request}/leegality/add-my-signature', [App\Http\Controllers\Admin\FreelancerLeegalitySignatureController::class, 'addMySignature'])->name('admin.jobproc.leegality_add_my_signature');
    Route::get('/jobproc/{job_request}/leegality/test-add-my-signature', [App\Http\Controllers\Admin\FreelancerLeegalitySignatureController::class, 'testAddMySignature'])->name('admin.jobproc.leegality_test_add_my_signature');
    Route::get('/jobproc/{job_request}/leegality/{signature}/view-signed', [App\Http\Controllers\Admin\FreelancerLeegalitySignatureController::class, 'viewSigned'])->name('admin.jobproc.leegality_view_signed');
    Route::get('/jobproc/{job_request}/leegality/{signature}/download-signed', [App\Http\Controllers\Admin\FreelancerLeegalitySignatureController::class, 'downloadSigned'])->name('admin.jobproc.leegality_download_signed');
    Route::get('/jobproc/{job_request}/leegality/{signature}/download-audit', [App\Http\Controllers\Admin\FreelancerLeegalitySignatureController::class, 'downloadAudit'])->name('admin.jobproc.leegality_download_audit');

    Route::get('/doctor-registration-otp-logs', [App\Http\Controllers\Admin\RegistrationOtpLogController::class, 'index'])
        ->defaults('registrationType', 'doctor')
        ->name('admin.doctor_registration_otp_logs.index');
    Route::get('/vendor-registration-otp-logs', [App\Http\Controllers\Admin\RegistrationOtpLogController::class, 'index'])
        ->defaults('registrationType', 'vendor')
        ->name('admin.vendor_registration_otp_logs.index');
    Route::get('/freelancer-registration-otp-logs', [App\Http\Controllers\Admin\RegistrationOtpLogController::class, 'index'])
        ->defaults('registrationType', 'freelancer')
        ->name('admin.freelancer_registration_otp_logs.index');

    Route::get('/agreements', [App\Http\Controllers\Admin\AgreementMasterAdminController::class, 'index'])->name('admin.agreements.index');
    Route::get('/agreements/data', [App\Http\Controllers\Admin\AgreementMasterAdminController::class, 'data'])->name('admin.agreements.data');

    Route::get('/doctor-requests', [App\Http\Controllers\Admin\DoctorRequestAdminController::class, 'index'])->name('admin.doctor_requests.index');
    Route::get('/doctor-requests/data', [App\Http\Controllers\Admin\DoctorRequestAdminController::class, 'data'])->name('admin.doctor_requests.data');
    Route::get('/doctor-requests/{doctor_request}/view-modal', [App\Http\Controllers\Admin\DoctorRequestAdminController::class, 'viewModal'])->name('admin.doctor_requests.view_modal');
    Route::put('/doctor-requests/{doctor_request}/registration-profile', [App\Http\Controllers\Admin\DoctorRequestAdminController::class, 'updateRegistrationProfile'])->name('admin.doctor_requests.registration_profile_update');
    Route::post('/doctor-requests/{doctor_request}/generate-about', [App\Http\Controllers\Admin\DoctorRequestAdminController::class, 'generateRegistrationAbout'])->name('admin.doctor_requests.generate_about');
    Route::post('/doctor-requests/{doctor_request}/profile-image/generate-coat', [App\Http\Controllers\Admin\DoctorRequestAdminController::class, 'generateProfileImageCoat'])->name('admin.doctor_requests.profile_image_generate');
    Route::post('/doctor-requests/{doctor_request}/profile-image/approve', [App\Http\Controllers\Admin\DoctorRequestAdminController::class, 'approveProfileImage'])->name('admin.doctor_requests.profile_image_approve');
    Route::get('/doctor-requests/{doctor_request}/website-reviews/panel', [App\Http\Controllers\Admin\DoctorRequestAdminController::class, 'websiteReviewsPanel'])->name('admin.doctor_requests.website_reviews_panel');
    Route::post('/doctor-requests/{doctor_request}/website-reviews', [App\Http\Controllers\Admin\DoctorRequestAdminController::class, 'websiteReviewsStore'])->name('admin.doctor_requests.website_reviews_store');
    Route::delete('/doctor-requests/{doctor_request}/website-reviews/{review}', [App\Http\Controllers\Admin\DoctorRequestAdminController::class, 'websiteReviewsDestroy'])->name('admin.doctor_requests.website_reviews_destroy');
    Route::get('/doctor-requests/{doctor_request}/leegality/preview-agreement', [App\Http\Controllers\Admin\DoctorLeegalitySignatureController::class, 'previewAgreement'])->name('admin.doctor_requests.leegality_preview_agreement');
    Route::post('/doctor-requests/{doctor_request}/leegality/send', [App\Http\Controllers\Admin\DoctorLeegalitySignatureController::class, 'send'])->name('admin.doctor_requests.leegality_send');
    Route::post('/doctor-requests/{doctor_request}/leegality/refresh', [App\Http\Controllers\Admin\DoctorLeegalitySignatureController::class, 'refresh'])->name('admin.doctor_requests.leegality_refresh');
    Route::post('/doctor-requests/{doctor_request}/leegality/add-my-signature', [App\Http\Controllers\Admin\DoctorLeegalitySignatureController::class, 'addMySignature'])->name('admin.doctor_requests.leegality_add_my_signature');

    Route::get('/doctor-requests/{doctor_request}/leegality/{signature}/view-signed', [App\Http\Controllers\Admin\DoctorLeegalitySignatureController::class, 'viewSigned'])->name('admin.doctor_requests.leegality_view_signed');
    Route::get('/doctor-requests/{doctor_request}/leegality/{signature}/download-signed', [App\Http\Controllers\Admin\DoctorLeegalitySignatureController::class, 'downloadSigned'])->name('admin.doctor_requests.leegality_download_signed');
    Route::get('/doctor-requests/{doctor_request}/leegality/{signature}/download-audit', [App\Http\Controllers\Admin\DoctorLeegalitySignatureController::class, 'downloadAudit'])->name('admin.doctor_requests.leegality_download_audit');
    Route::get('/doctor-requests/{doctor_request}', [App\Http\Controllers\Admin\DoctorRequestAdminController::class, 'show'])->name('admin.doctor_requests.show');
    Route::get('/doctor-requests/{doctor_request}/pricing-edit', [App\Http\Controllers\Admin\DoctorRequestAdminController::class, 'pricingEdit'])->name('admin.doctor_requests.pricing_edit');
    Route::put('/doctor-requests/{doctor_request}/pricing', [App\Http\Controllers\Admin\DoctorRequestAdminController::class, 'updatePricing'])->name('admin.doctor_requests.pricing_update');
    Route::get('/doctor-requests/{doctor_request}/price-change-requests', [App\Http\Controllers\Admin\DoctorRequestAdminController::class, 'priceChangeRequests'])->name('admin.doctor_requests.price_change_requests');
    Route::post('/doctor-requests/price-change-requests/{price_change_request}/approve', [App\Http\Controllers\Admin\DoctorRequestAdminController::class, 'approvePriceChangeRequest'])->name('admin.doctor_requests.price_change_approve');
    Route::post('/doctor-requests/price-change-requests/{price_change_request}/reject', [App\Http\Controllers\Admin\DoctorRequestAdminController::class, 'rejectPriceChangeRequest'])->name('admin.doctor_requests.price_change_reject');
    Route::get('/doctor-requests/{doctor_request}/pricing-logs', [App\Http\Controllers\Admin\DoctorRequestAdminController::class, 'pricingLogs'])->name('admin.doctor_requests.pricing_logs');
    Route::post('/doctor-requests/{doctor_request}/status', [App\Http\Controllers\Admin\DoctorRequestAdminController::class, 'updateStatus'])->name('admin.doctor_requests.update_status');
    Route::post('/doctor-requests/regenerate-online-meetings', [App\Http\Controllers\Admin\DoctorRequestAdminController::class, 'regenerateOnlineMeetingLinks'])
        ->name('admin.doctor_requests.regenerate_online_meetings');
    Route::post('/doctor-requests/portal-lead-commission', [App\Http\Controllers\Admin\DoctorRequestAdminController::class, 'updatePortalLeadCommission'])
        ->name('admin.doctor_requests.portal_lead_commission');

    // Break Logs Routes
    Route::get('/break-logs', function() {
        $breakLogs = BreakLog::with('user')->orderBy('created_at', 'desc')->paginate(20);
        return view('admin.break_logs.index', compact('breakLogs'));
    })->name('admin.break_logs.index')->middleware('can:view_break_logs');

    Route::get('/break-logs/user/{userId}', function($userId) {
        $user = User::findOrFail($userId);
        $breakLogs = BreakLog::where('user_id', $userId)->orderBy('created_at', 'desc')->paginate(20);
        return view('admin.break_logs.user_logs', compact('breakLogs', 'user'));
    })->name('admin.break_logs.user')->middleware('can:view_break_logs');

    // Duty Logs Routes
    Route::get('/duty-logs', function() {
        $dutyLogs = DutyLogs::with('user')->orderBy('created_at', 'desc')->paginate(20);
        return view('admin.duty_logs.index', compact('dutyLogs'));
    })->name('admin.duty_logs.index')->middleware('can:view_duty_logs');

    Route::get('/duty-logs/user/{userId}', function($userId) {
        $user = User::findOrFail($userId);
        $dutyLogs = DutyLogs::where('user_id', $userId)->orderBy('created_at', 'desc')->paginate(20);
        return view('admin.duty_logs.user_logs', compact('dutyLogs', 'user'));
    })->name('admin.duty_logs.user')->middleware('can:view_duty_logs');

    Route::get('technical-support', function () {
        return view('admin.technical_support.index');
    })->name('admin.technical-support.index')->middleware('can:view_technical_support');

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

    Route::get('/payments', [App\Http\Controllers\EasebuzzPaymentLinkController::class, 'index'])->name('sales.payments.index');
    Route::get('/payments/create', [App\Http\Controllers\EasebuzzPaymentLinkController::class, 'create'])->name('sales.payments.create');
    Route::post('/payments', [App\Http\Controllers\EasebuzzPaymentLinkController::class, 'store'])->name('sales.payments.store');
    Route::get('/payments/{payment}', [App\Http\Controllers\EasebuzzPaymentLinkController::class, 'show'])->name('sales.payments.show');
    Route::post('/payments/{payment}/verify', [App\Http\Controllers\EasebuzzPaymentLinkController::class, 'verify'])->name('sales.payments.verify');

    // Leads Management (literal paths before /leads/{id})
    Route::get('/leads', [App\Http\Controllers\Sales\LeadController::class, 'index'])->name('sales.leads.index');
    Route::get('/leads/getLeads', [App\Http\Controllers\Sales\LeadController::class, 'getLeads'])->name('sales.leads.getLeads');
    Route::post('/leads/run-future-prospect-reminder-check', [App\Http\Controllers\Sales\LeadController::class, 'runFutureProspectReminderCheck'])->name('sales.leads.runFpReminderCheck');
    Route::post('/leads', [App\Http\Controllers\Sales\LeadController::class, 'store'])->name('sales.leads.store');
    Route::get('/leads/{id}', [App\Http\Controllers\Sales\LeadController::class, 'show'])->name('sales.leads.show');
    Route::get('/leads/{id}/edit', [App\Http\Controllers\Sales\LeadController::class, 'edit'])->name('sales.leads.edit');
    Route::put('/leads/{id}', [App\Http\Controllers\Sales\LeadController::class, 'update'])->name('sales.leads.update');
    Route::post('/leads/{id}/status-remark/generate-ai', [App\Http\Controllers\LeadStatusRemarkAiController::class, 'generateForSales'])->name('sales.leads.status-remark.generate-ai');

    Route::get('/referral-leads', [App\Http\Controllers\Sales\ReferralLeadController::class, 'index'])->name('sales.referral_leads.index');
    Route::post('/referral-leads', [App\Http\Controllers\Sales\ReferralLeadController::class, 'store'])->name('sales.referral_leads.store');

    Route::get('/chat', [App\Http\Controllers\Sales\ChatController::class, 'index'])->name('sales.chat.index');
    Route::get('/chat/messages/{userId}', [App\Http\Controllers\Sales\ChatController::class, 'getMessages'])->name('sales.chat.messages');
    Route::post('/chat/send', [App\Http\Controllers\Sales\ChatController::class, 'sendMessage'])->name('sales.chat.send');
    Route::post('/chat/mark-read/{userId}', [App\Http\Controllers\Sales\ChatController::class, 'markAsRead'])->name('sales.chat.mark-read');
    Route::get('/chat/unread-counts', [App\Http\Controllers\Sales\ChatController::class, 'getUnreadCounts'])->name('sales.chat.unread-counts');
    Route::post('/chat/favorite/{userId}', [App\Http\Controllers\Sales\ChatController::class, 'toggleFavorite'])->name('sales.chat.favorite');
    Route::get('/chat/favorites', [App\Http\Controllers\Sales\ChatController::class, 'getFavorites'])->name('sales.chat.favorites');
    Route::post('/chat/send-attachment', [App\Http\Controllers\Sales\ChatController::class, 'sendAttachment'])->name('sales.chat.send.attachment');
    Route::post('/chat/translate', [App\Http\Controllers\Sales\ChatController::class, 'translate'])->name('sales.chat.translate');

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
    Route::post('/leads/{id}/status-remark/generate-ai', [App\Http\Controllers\LeadStatusRemarkAiController::class, 'generateForManager'])->name('manager.leads.status-remark.generate-ai');
    Route::get('/leads/executives', [App\Http\Controllers\Manager\LeadController::class, 'getExecutives'])->name('manager.leads.getExecutives');

    Route::get('/referral-leads', [App\Http\Controllers\Manager\ReferralLeadController::class, 'index'])->name('manager.referral_leads.index');
    Route::post('/referral-leads/{referralLead}/approve', [App\Http\Controllers\Manager\ReferralLeadController::class, 'approve'])->name('manager.referral_leads.approve');
    Route::post('/referral-leads/{referralLead}/reject', [App\Http\Controllers\Manager\ReferralLeadController::class, 'reject'])->name('manager.referral_leads.reject');

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
    Route::post('/chat/translate', [App\Http\Controllers\Manager\ChatController::class, 'translate'])->name('manager.chat.translate');

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
    Route::post('/operation-leads/run-future-prospect-reminder-check', [App\Http\Controllers\Operation\OperationLeadController::class, 'runFutureProspectReminderCheck'])->name('operation.operation_leads.runFpReminderCheck');

    Route::get('/referral-leads', [App\Http\Controllers\Operation\ReferralLeadController::class, 'index'])->name('operation.referral_leads.index');
    Route::post('/referral-leads', [App\Http\Controllers\Operation\ReferralLeadController::class, 'store'])->name('operation.referral_leads.store');

    // Vendor Filtering Routes (must come before {id} routes)
    Route::get('/operation-leads/filtered-vendors', [App\Http\Controllers\Operation\OperationLeadController::class, 'getFilteredVendorsForDeployment'])->name('operation.operation_leads.filtered_vendors');
    Route::get('/operation-leads/vendor-details', [App\Http\Controllers\Operation\OperationLeadController::class, 'getVendorDetails'])->name('operation.operation_leads.vendor_details');
    Route::post('/operation-leads/update-freelancer-status', [App\Http\Controllers\Operation\OperationLeadController::class, 'updateFreelancerStatus'])->name('operation.operation_leads.update_freelancer_status');
    Route::get('/operation-leads/updated-vendor-count', [App\Http\Controllers\Operation\OperationLeadController::class, 'getUpdatedVendorCount'])->name('operation.operation_leads.updated_vendor_count');

    Route::get('/operation-leads/{id}', [App\Http\Controllers\Operation\OperationLeadController::class, 'show'])->name('operation.operation_leads.show');
    Route::get('/operation-leads/{id}/edit', [App\Http\Controllers\Operation\OperationLeadController::class, 'edit'])->name('operation.operation_leads.edit');
    Route::put('/operation-leads/{id}', [App\Http\Controllers\Operation\OperationLeadController::class, 'update'])->name('operation.operation_leads.update');
    Route::post('/operation-leads/{id}/status-remark/generate-ai', [App\Http\Controllers\LeadStatusRemarkAiController::class, 'generateForOperation'])->name('operation.operation_leads.status-remark.generate-ai');
    Route::delete('/operation-leads/{id}', [App\Http\Controllers\Operation\OperationLeadController::class, 'destroy'])->name('operation.operation_leads.destroy');

    Route::get('/chat', [App\Http\Controllers\Operation\ChatController::class, 'index'])->name('operation.chat.index');
    Route::get('/chat/messages/{userId}', [App\Http\Controllers\Operation\ChatController::class, 'getMessages'])->name('operation.chat.messages');
    Route::post('/chat/send', [App\Http\Controllers\Operation\ChatController::class, 'sendMessage'])->name('operation.chat.send');
    Route::post('/chat/mark-read/{userId}', [App\Http\Controllers\Operation\ChatController::class, 'markAsRead'])->name('operation.chat.mark-read');
    Route::get('/chat/unread-counts', [App\Http\Controllers\Operation\ChatController::class, 'getUnreadCounts'])->name('operation.chat.unread-counts');
    Route::post('/chat/favorite/{userId}', [App\Http\Controllers\Operation\ChatController::class, 'toggleFavorite'])->name('operation.chat.favorite');
    Route::get('/chat/favorites', [App\Http\Controllers\Operation\ChatController::class, 'getFavorites'])->name('operation.chat.favorites');
    Route::post('/chat/send-attachment', [App\Http\Controllers\Operation\ChatController::class, 'sendAttachment'])->name('operation.chat.send.attachment');
    Route::post('/chat/translate', [App\Http\Controllers\Operation\ChatController::class, 'translate'])->name('operation.chat.translate');

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
    Route::post('operation-leads/deployment/{id}/dates', [App\Http\Controllers\Operation\OperationLeadController::class, 'getDeploymentDates'])->name('operation.operation_leads.deployment.dates');
    Route::post('operation-leads/deployment/{id}/toggle-absent', [App\Http\Controllers\Operation\OperationLeadController::class, 'toggleDeploymentAbsentDate'])->name('operation.operation_leads.deployment.toggle-absent');
    Route::post('operation-leads/deployment/{id}/save-absent', [App\Http\Controllers\Operation\OperationLeadController::class, 'saveDeploymentAbsentDates'])->name('operation.operation_leads.deployment.save-absent');

    // Payment Invoice and Received Payment Routes
    Route::get('operation-leads/payment-invoice/{id}', [App\Http\Controllers\Operation\OperationLeadController::class, 'showPaymentInvoice'])->name('operation.operation_leads.payment_invoice.show');
    Route::post('operation-leads/invoice/{invoiceId}/received-payment', [App\Http\Controllers\Operation\OperationLeadController::class, 'storeReceivedPayment'])->name('operation.operation_leads.received_payment.store');
    Route::get('operation-leads/received-payment/{id}/edit', [App\Http\Controllers\Operation\OperationLeadController::class, 'editReceivedPayment'])->name('operation.operation_leads.received_payment.edit');
    Route::put('operation-leads/received-payment/{id}', [App\Http\Controllers\Operation\OperationLeadController::class, 'updateReceivedPayment'])->name('operation.operation_leads.received_payment.update');
    Route::delete('operation-leads/received-payment/{id}', [App\Http\Controllers\Operation\OperationLeadController::class, 'destroyReceivedPayment'])->name('operation.operation_leads.received_payment.destroy');

    Route::get('/payments', [App\Http\Controllers\EasebuzzPaymentLinkController::class, 'index'])->name('operation.payments.index');
    Route::get('/payments/create', [App\Http\Controllers\EasebuzzPaymentLinkController::class, 'create'])->name('operation.payments.create');
    Route::post('/payments', [App\Http\Controllers\EasebuzzPaymentLinkController::class, 'store'])->name('operation.payments.store');
    Route::get('/payments/{payment}', [App\Http\Controllers\EasebuzzPaymentLinkController::class, 'show'])->name('operation.payments.show');
    Route::post('/payments/{payment}/verify', [App\Http\Controllers\EasebuzzPaymentLinkController::class, 'verify'])->name('operation.payments.verify');

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

    Route::get('/referral-leads', [App\Http\Controllers\OperationManager\ReferralLeadController::class, 'index'])->name('referral_leads.index');
    Route::post('/referral-leads/{referralLead}/approve', [App\Http\Controllers\OperationManager\ReferralLeadController::class, 'approve'])->name('referral_leads.approve');
    Route::post('/referral-leads/{referralLead}/reject', [App\Http\Controllers\OperationManager\ReferralLeadController::class, 'reject'])->name('referral_leads.reject');

    // Vendor filtering routes for Operation Manager (must come before {id} routes)
    Route::get('operation-leads/filtered-vendors', [App\Http\Controllers\OperationManager\OperationLeadController::class, 'getFilteredVendorsForDeployment'])->name('operation_manager.operation_leads.filtered_vendors');
    Route::get('operation-leads/vendor-details', [App\Http\Controllers\OperationManager\OperationLeadController::class, 'getVendorDetails'])->name('operation_manager.operation_leads.vendor_details');
    Route::post('operation-leads/update-freelancer-status', [App\Http\Controllers\OperationManager\OperationLeadController::class, 'updateFreelancerStatus'])->name('operation_manager.operation_leads.update_freelancer_status');
    Route::get('operation-leads/updated-vendor-count', [App\Http\Controllers\OperationManager\OperationLeadController::class, 'getUpdatedVendorCount'])->name('operation_manager.operation_leads.updated_vendor_count');

    Route::get('/operation-leads/{id}', [App\Http\Controllers\OperationManager\OperationLeadController::class, 'show'])->name('operation_leads.show');
    Route::get('/operation-leads/{id}/edit', [App\Http\Controllers\OperationManager\OperationLeadController::class, 'edit'])->name('operation_leads.edit');
    Route::put('/operation-leads/{id}', [App\Http\Controllers\OperationManager\OperationLeadController::class, 'update'])->name('operation_leads.update');
    Route::post('/operation-leads/{id}/status-remark/generate-ai', [App\Http\Controllers\LeadStatusRemarkAiController::class, 'generateForOperationManager'])->name('operation_manager.operation_leads.status-remark.generate-ai');
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
    Route::post('/chat/translate', [App\Http\Controllers\OperationManager\ChatController::class, 'translate'])->name('chat.translate');

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
    Route::post('operation-leads/deployment/{id}/dates', [App\Http\Controllers\OperationManager\OperationLeadController::class, 'getDeploymentDates'])->name('operation_leads.deployment.dates');
    Route::post('operation-leads/deployment/{id}/toggle-absent', [App\Http\Controllers\OperationManager\OperationLeadController::class, 'toggleDeploymentAbsentDate'])->name('operation_leads.deployment.toggle-absent');
    Route::post('operation-leads/deployment/{id}/save-absent', [App\Http\Controllers\OperationManager\OperationLeadController::class, 'saveDeploymentAbsentDates'])->name('operation_leads.deployment.save-absent');

    // Payment Invoice and Received Payment Routes
    Route::get('operation-leads/payment-invoice/{id}', [App\Http\Controllers\OperationManager\OperationLeadController::class, 'showPaymentInvoice'])->name('operation_leads.payment_invoice.show');
    Route::post('operation-leads/invoice/{invoiceId}/received-payment', [App\Http\Controllers\OperationManager\OperationLeadController::class, 'storeReceivedPayment'])->name('operation_manager.operation_leads.received_payment.store');
    Route::get('operation-leads/received-payment/{id}/edit', [App\Http\Controllers\OperationManager\OperationLeadController::class, 'editReceivedPayment'])->name('operation_manager.operation_leads.received_payment.edit');
    Route::put('operation-leads/received-payment/{id}', [App\Http\Controllers\OperationManager\OperationLeadController::class, 'updateReceivedPayment'])->name('operation_manager.operation_leads.received_payment.update');
    Route::delete('operation-leads/received-payment/{id}', [App\Http\Controllers\OperationManager\OperationLeadController::class, 'destroyReceivedPayment'])->name('operation_manager.operation_leads.received_payment.destroy');

    // All Job Requests (view-only listing for operation manager)
    Route::get('/jobproc/all_job_req', [App\Http\Controllers\OperationManager\JobProcController::class, 'all_index'])->name('jobproc.all_job_req.index');
    Route::get('/jobproc/all_job_req/jobrequests', [App\Http\Controllers\OperationManager\JobProcController::class, 'allGetJobRequests'])->name('jobproc.all_job_req.jobrequests');
    Route::get('/jobproc/{id}', [App\Http\Controllers\OperationManager\JobProcController::class, 'show'])->name('jobproc.show');

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

    Route::prefix('crm-b2b-corporate-chat')->name('crm.b2b_corporate_chat.')->group(function () {
        Route::get('/', [App\Http\Controllers\Crm\B2BCorporateChatController::class, 'index'])->name('index');
        Route::get('/messages/{b2bUserId}/group', [App\Http\Controllers\Crm\B2BCorporateChatController::class, 'getGroupMessages'])->name('messages.group')->whereNumber('b2bUserId');
        Route::get('/messages/{b2bUserId}/direct', [App\Http\Controllers\Crm\B2BCorporateChatController::class, 'getDirectMessages'])->name('messages.direct')->whereNumber('b2bUserId');
        Route::post('/send', [App\Http\Controllers\Crm\B2BCorporateChatController::class, 'sendMessage'])->name('send');
        Route::post('/send-attachment', [App\Http\Controllers\Crm\B2BCorporateChatController::class, 'sendAttachment'])->name('send_attachment');
        Route::post('/mark-read/{b2bUserId}/group', [App\Http\Controllers\Crm\B2BCorporateChatController::class, 'markGroupAsRead'])->name('mark_read.group')->whereNumber('b2bUserId');
        Route::post('/mark-read/{b2bUserId}/direct', [App\Http\Controllers\Crm\B2BCorporateChatController::class, 'markDirectAsRead'])->name('mark_read.direct')->whereNumber('b2bUserId');
        Route::get('/call/{b2bUserId}', [App\Http\Controllers\Crm\B2BCorporateChatController::class, 'initiateCall'])->name('call')->whereNumber('b2bUserId');
        Route::get('/unread-counts', [App\Http\Controllers\Crm\B2BCorporateChatController::class, 'unreadCounts'])->name('unread_counts');
    });
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





// Routes for Sub Admin Role
Route::prefix('/subadmin')->middleware(['auth', 'role:Sub Admin'])->group(function () {
    Route::get('/dashboard', [Controllers\SubAdmin\SubAdminController::class, 'dashboard'])->name('subadmin.dashboard');
    Route::get('/dashboard/sales-leads-stats', [App\Http\Controllers\SubAdmin\SubAdminController::class, 'getSalesLeadsStats'])->name('subadmin.dashboard.sales-leads-stats');
    Route::get('/dashboard/operation-leads-stats', [App\Http\Controllers\SubAdmin\SubAdminController::class, 'getOperationLeadsStats'])->name('subadmin.dashboard.operation-leads-stats');
    Route::get('/dashboard/users-stats', [App\Http\Controllers\SubAdmin\SubAdminController::class, 'getUsersStats'])->name('subadmin.dashboard.users-stats');
    Route::get('/dashboard/user-details/{userId}', [App\Http\Controllers\SubAdmin\SubAdminController::class, 'getUserDetails'])->name('subadmin.dashboard.user-details');
    Route::get('/dashboard/sales-executives', [App\Http\Controllers\SubAdmin\SubAdminController::class, 'getSalesExecutives'])->name('subadmin.dashboard.sales-executives');
    Route::get('/dashboard/operation-executives', [App\Http\Controllers\SubAdmin\SubAdminController::class, 'getOperationExecutives'])->name('subadmin.dashboard.operation-executives');
    Route::get('/dashboard/users', [App\Http\Controllers\SubAdmin\SubAdminController::class, 'getUsers'])->name('subadmin.dashboard.users');

    // Task Management Routes
    Route::get('/dashboard/tasks', [App\Http\Controllers\SubAdmin\SubAdminController::class, 'getTasks'])->name('subadmin.dashboard.tasks');
    Route::post('/dashboard/tasks', [App\Http\Controllers\SubAdmin\SubAdminController::class, 'storeTask'])->name('subadmin.dashboard.tasks.store');
    Route::get('/dashboard/tasks/{id}/edit', [App\Http\Controllers\SubAdmin\SubAdminController::class, 'editTask'])->name('subadmin.dashboard.tasks.edit');
    Route::put('/dashboard/tasks/{id}', [App\Http\Controllers\SubAdmin\SubAdminController::class, 'updateTask'])->name('subadmin.dashboard.tasks.update');
    Route::delete('/dashboard/tasks/{id}', [App\Http\Controllers\SubAdmin\SubAdminController::class, 'destroyTask'])->name('subadmin.dashboard.tasks.destroy');
    Route::get('/dashboard/task-history', [App\Http\Controllers\SubAdmin\SubAdminController::class, 'getTaskHistory'])->name('subadmin.dashboard.task-history');
    Route::get('/dashboard/calendar-data', [App\Http\Controllers\SubAdmin\SubAdminController::class, 'getCalendarData'])->name('subadmin.dashboard.calendar-data');
    Route::get('/dashboard/calendar-leads', [App\Http\Controllers\SubAdmin\SubAdminController::class, 'getCalendarLeads'])->name('subadmin.dashboard.calendar-leads');
    Route::get('/dashboard/revenue-stats', [App\Http\Controllers\SubAdmin\SubAdminController::class, 'getRevenueStats'])->name('subadmin.dashboard.revenue-stats');
    Route::get('/dashboard/pending-deployments', [App\Http\Controllers\SubAdmin\SubAdminController::class, 'getPendingDeployments'])->name('subadmin.dashboard.pending-deployments');
    Route::get('/dashboard/outstanding-amount-stats', [App\Http\Controllers\SubAdmin\SubAdminController::class, 'getOutstandingAmountStats'])->name('subadmin.dashboard.outstanding-amount-stats');
    Route::get('/dashboard/profile-pending-stats', [App\Http\Controllers\SubAdmin\SubAdminController::class, 'getProfilePendingStats'])->name('subadmin.dashboard.profile-pending-stats');
    Route::get('/dashboard/vendor-payment-stats', [App\Http\Controllers\SubAdmin\SubAdminController::class, 'getVendorPaymentStats'])->name('subadmin.dashboard.vendor-payment-stats');
    Route::get('/dashboard/unverified-deployment-payments-stats', [App\Http\Controllers\SubAdmin\SubAdminController::class, 'getUnverifiedDeploymentPaymentsStats'])->name('subadmin.dashboard.unverified-deployment-payments-stats');
    Route::get('/dashboard/pending-callbacks', [App\Http\Controllers\SubAdmin\SubAdminController::class, 'getPendingCallbacks'])->name('subadmin.dashboard.pending-callbacks');
    
    // Recent Calls Routes
    Route::get('/dashboard/recent-calls/sales', [App\Http\Controllers\SubAdmin\SubAdminController::class, 'getRecentCallsSales'])->name('subadmin.dashboard.recent-calls.sales');
    Route::get('/dashboard/recent-calls/operation', [App\Http\Controllers\SubAdmin\SubAdminController::class, 'getRecentCallsOperation'])->name('subadmin.dashboard.recent-calls.operation');
    

    Route::get('/payments', [App\Http\Controllers\EasebuzzPaymentLinkController::class, 'index'])->name('subadmin.payments.index');
    Route::get('/payments/create', [App\Http\Controllers\EasebuzzPaymentLinkController::class, 'create'])->name('subadmin.payments.create');
    Route::post('/payments', [App\Http\Controllers\EasebuzzPaymentLinkController::class, 'store'])->name('subadmin.payments.store');
    Route::get('/payments/{payment}', [App\Http\Controllers\EasebuzzPaymentLinkController::class, 'show'])->name('subadmin.payments.show');
    Route::post('/payments/{payment}/verify', [App\Http\Controllers\EasebuzzPaymentLinkController::class, 'verify'])->name('subadmin.payments.verify');

    // Sub Admin Leads Routes
    Route::get('/leads', [App\Http\Controllers\SubAdmin\SubAdminLeadController::class, 'index'])->name('subadmin.leads.index');
    Route::get('/leads/getLeads', [App\Http\Controllers\SubAdmin\SubAdminLeadController::class, 'getLeads'])->name('subadmin.leads.getLeads');
    Route::get('/leads/{id}', [App\Http\Controllers\SubAdmin\SubAdminLeadController::class, 'show'])->name('subadmin.leads.show');
    Route::delete('/leads/{id}', [App\Http\Controllers\SubAdmin\SubAdminLeadController::class, 'destroy'])->name('subadmin.leads.destroy');
    Route::post('/leads', [App\Http\Controllers\SubAdmin\SubAdminLeadController::class, 'store'])->name('subadmin.leads.store');
    Route::get('/leads/{id}/edit', [App\Http\Controllers\SubAdmin\SubAdminLeadController::class, 'edit'])->name('subadmin.leads.edit');
    Route::put('/leads/{id}', [App\Http\Controllers\SubAdmin\SubAdminLeadController::class, 'update'])->name('subadmin.leads.update');
    Route::put('/leads/{lead}/status-remarks/{remark}', [App\Http\Controllers\SubAdmin\SubAdminLeadController::class, 'updateStatusRemark'])->name('subadmin.leads.status-remarks.update');

    // Sub Admin Operation Leads Routes
    Route::get('/operation-leads', [App\Http\Controllers\SubAdmin\SubAdminOperationLeadController::class, 'index'])->name('subadmin.operation_leads.index');
    Route::post('/operation-leads', [App\Http\Controllers\SubAdmin\SubAdminOperationLeadController::class, 'store'])->name('subadmin.operation_leads.store');
    Route::get('/operation-leads/getLeads', [App\Http\Controllers\SubAdmin\SubAdminOperationLeadController::class, 'getLeads'])->name('subadmin.operation_leads.getLeads');
    Route::get('operation-leads/filtered-vendors', [App\Http\Controllers\SubAdmin\SubAdminOperationLeadController::class, 'getFilteredVendorsForDeployment'])->name('subadmin.operation_leads.filtered_vendors');
    Route::get('operation-leads/vendor-details', [App\Http\Controllers\SubAdmin\SubAdminOperationLeadController::class, 'getVendorDetails'])->name('subadmin.operation_leads.vendor_details');
    Route::post('operation-leads/update-freelancer-status', [App\Http\Controllers\SubAdmin\SubAdminOperationLeadController::class, 'updateFreelancerStatus'])->name('subadmin.operation_leads.update_freelancer_status');
    Route::get('operation-leads/updated-vendor-count', [App\Http\Controllers\SubAdmin\SubAdminOperationLeadController::class, 'getUpdatedVendorCount'])->name('subadmin.operation_leads.updated_vendor_count');
    Route::get('/operation-leads/{id}', [App\Http\Controllers\SubAdmin\SubAdminOperationLeadController::class, 'show'])->name('subadmin.operation_leads.show');
    Route::get('/operation-leads/{id}/edit', [App\Http\Controllers\SubAdmin\SubAdminOperationLeadController::class, 'edit'])->name('subadmin.operation_leads.edit');
    Route::put('/operation-leads/{id}', [App\Http\Controllers\SubAdmin\SubAdminOperationLeadController::class, 'update'])->name('subadmin.operation_leads.update');
    Route::delete('/operation-leads/{id}', [App\Http\Controllers\SubAdmin\SubAdminOperationLeadController::class, 'destroy'])->name('subadmin.operation_leads.destroy');
    Route::post('operation-leads/{lead}/payment', [App\Http\Controllers\SubAdmin\SubAdminOperationLeadController::class, 'storePaymentDetail'])->name('subadmin.operation_leads.payment.store');
    Route::get('operation-leads/payment/{id}/edit', [App\Http\Controllers\SubAdmin\SubAdminOperationLeadController::class, 'editPaymentDetail'])->name('subadmin.operation_leads.payment.edit');
    Route::put('operation-leads/payment/{id}', [App\Http\Controllers\SubAdmin\SubAdminOperationLeadController::class, 'updatePaymentDetail'])->name('subadmin.operation_leads.payment.update');
    Route::delete('operation-leads/payment/{id}', [App\Http\Controllers\SubAdmin\SubAdminOperationLeadController::class, 'destroyPaymentDetail'])->name('subadmin.operation_leads.payment.destroy');
    Route::post('operation-leads/{lead}/deployment', [App\Http\Controllers\SubAdmin\SubAdminOperationLeadController::class, 'storeDeploymentDetail'])->name('subadmin.operation_leads.deployment.store');
    Route::get('operation-leads/deployment/{id}/edit', [App\Http\Controllers\SubAdmin\SubAdminOperationLeadController::class, 'editDeploymentDetail'])->name('subadmin.operation_leads.deployment.edit');
    Route::put('operation-leads/deployment/{id}', [App\Http\Controllers\SubAdmin\SubAdminOperationLeadController::class, 'updateDeploymentDetail'])->name('subadmin.operation_leads.deployment.update');
    Route::delete('operation-leads/deployment/{id}', [App\Http\Controllers\SubAdmin\SubAdminOperationLeadController::class, 'destroyDeploymentDetail'])->name('subadmin.operation_leads.deployment.destroy');
    Route::post('operation-leads/deployment/{id}/toggle-verify-payment', [App\Http\Controllers\SubAdmin\SubAdminOperationLeadController::class, 'toggleVerifyPayment'])->name('subadmin.operation_leads.deployment.toggle-verify-payment');
    Route::post('operation-leads/deployment/{id}/dates', [App\Http\Controllers\SubAdmin\SubAdminOperationLeadController::class, 'getDeploymentDates'])->name('subadmin.operation_leads.deployment.dates');
    Route::post('operation-leads/deployment/{id}/toggle-absent', [App\Http\Controllers\SubAdmin\SubAdminOperationLeadController::class, 'toggleDeploymentAbsentDate'])->name('subadmin.operation_leads.deployment.toggle-absent');
    Route::post('operation-leads/deployment/{id}/save-absent', [App\Http\Controllers\SubAdmin\SubAdminOperationLeadController::class, 'saveDeploymentAbsentDates'])->name('subadmin.operation_leads.deployment.save-absent');
    Route::get('operation-leads/payment-invoice/{id}', [App\Http\Controllers\SubAdmin\SubAdminOperationLeadController::class, 'showPaymentInvoice'])->name('subadmin.operation_leads.payment_invoice.show');
    Route::post('operation-leads/invoice/{invoiceId}/received-payment', [App\Http\Controllers\SubAdmin\SubAdminOperationLeadController::class, 'storeReceivedPayment'])->name('subadmin.operation_leads.received_payment.store');
    Route::get('operation-leads/received-payment/{id}/edit', [App\Http\Controllers\SubAdmin\SubAdminOperationLeadController::class, 'editReceivedPayment'])->name('subadmin.operation_leads.received_payment.edit');
    Route::put('operation-leads/received-payment/{id}', [App\Http\Controllers\SubAdmin\SubAdminOperationLeadController::class, 'updateReceivedPayment'])->name('subadmin.operation_leads.received_payment.update');
    Route::delete('operation-leads/received-payment/{id}', [App\Http\Controllers\SubAdmin\SubAdminOperationLeadController::class, 'destroyReceivedPayment'])->name('subadmin.operation_leads.received_payment.destroy');

    // B2B & Reference Users
    Route::get('b2b-users', [Controllers\SubAdmin\SubAdminB2BUserController::class, 'index'])->name('subadmin.b2b_users.index');
    Route::post('b2b-users', [Controllers\SubAdmin\SubAdminB2BUserController::class, 'store'])->name('subadmin.b2b_users.store');
    Route::post('b2b-users/{b2bUser}', [Controllers\SubAdmin\SubAdminB2BUserController::class, 'update'])->name('subadmin.b2b_users.update');
    Route::delete('b2b-users/{b2bUser}', [Controllers\SubAdmin\SubAdminB2BUserController::class, 'destroy'])->name('subadmin.b2b_users.destroy');
    Route::get('b2b-users/options', [Controllers\SubAdmin\SubAdminB2BUserController::class, 'apiIndex'])->name('subadmin.b2b_users.options');

    Route::post('b2b-reference-users', [Controllers\SubAdmin\SubAdminB2BReferenceUserController::class, 'store'])->name('subadmin.b2b_reference_users.store');
    Route::delete('b2b-reference-users/{b2bReferenceUser}', [Controllers\SubAdmin\SubAdminB2BReferenceUserController::class, 'destroy'])->name('subadmin.b2b_reference_users.destroy');

    Route::get('b2b-corporate-partners', [Controllers\SubAdmin\SubAdminB2BPartnerAccountController::class, 'indexCorporate'])->name('subadmin.b2b_corporate.index');
    Route::post('b2b-corporate-partners', [Controllers\SubAdmin\SubAdminB2BPartnerAccountController::class, 'storeCorporate'])->name('subadmin.b2b_corporate.store');
    Route::post('b2b-corporate-partners/{b2bUser}', [Controllers\SubAdmin\SubAdminB2BPartnerAccountController::class, 'updateCorporate'])->name('subadmin.b2b_corporate.update');
    Route::delete('b2b-corporate-partners/{b2bUser}', [Controllers\SubAdmin\SubAdminB2BPartnerAccountController::class, 'destroyCorporate'])->name('subadmin.b2b_corporate.destroy');
    Route::get('b2b-individual-partners', [Controllers\SubAdmin\SubAdminB2BPartnerAccountController::class, 'indexIndividual'])->name('subadmin.b2b_individual.index');
    Route::post('b2b-individual-partners', [Controllers\SubAdmin\SubAdminB2BPartnerAccountController::class, 'storeIndividual'])->name('subadmin.b2b_individual.store');
    Route::post('b2b-individual-partners/{b2bUser}', [Controllers\SubAdmin\SubAdminB2BPartnerAccountController::class, 'updateIndividual'])->name('subadmin.b2b_individual.update');
    Route::delete('b2b-individual-partners/{b2bUser}', [Controllers\SubAdmin\SubAdminB2BPartnerAccountController::class, 'destroyIndividual'])->name('subadmin.b2b_individual.destroy');

    Route::get('corporate-individual-b2b', [Controllers\SubAdmin\SubAdminCorporateIndividualHubController::class, 'index'])->name('subadmin.corporate_individual.hub');

    // Users
    Route::resource('users', Controllers\SubAdmin\SubAdminUserController::class)->names('subadmin.users');

    // Insurers & Brokers
    Route::resource('insurers', Controllers\SubAdmin\SubAdminInsurerUserController::class)->names('subadmin.insurers')->except(['show']);
    Route::resource('brokers', Controllers\SubAdmin\SubAdminBrokerUserController::class)->names('subadmin.brokers')->except(['show']);
    // Services
    Route::resource('services', Controllers\SubAdmin\SubAdminServiceController::class)->names('subadmin.services');

    // Break Logs
    Route::get('/break-logs', function() {
        $breakLogs = \App\Models\BreakLog::with('user')->orderBy('created_at', 'desc')->paginate(20);
        return view('subadmin.break_logs.index', compact('breakLogs'));
    })->name('subadmin.break_logs.index')->middleware('can:view_break_logs');

    Route::get('/break-logs/user/{userId}', function($userId) {
        $user = \App\Models\User::findOrFail($userId);
        $breakLogs = \App\Models\BreakLog::where('user_id', $userId)->orderBy('created_at', 'desc')->paginate(20);
        return view('subadmin.break_logs.user_logs', compact('breakLogs', 'user'));
    })->name('subadmin.break_logs.user_logs');

    // Duty Logs
    Route::get('/duty-logs', function() {
        $dutyLogs = \App\Models\DutyLogs::with('user')->orderBy('created_at', 'desc')->paginate(20);
        return view('subadmin.duty_logs.index', compact('dutyLogs'));
    })->name('subadmin.duty_logs.index')->middleware('can:view_duty_logs');

    Route::get('/duty-logs/user/{userId}', function($userId) {
        $user = \App\Models\User::findOrFail($userId);
        $dutyLogs = \App\Models\DutyLogs::where('user_id', $userId)->orderBy('created_at', 'desc')->paginate(20);
        return view('subadmin.duty_logs.user_logs', compact('dutyLogs', 'user'));
    })->name('subadmin.duty_logs.user_logs');
    Route::get('corporate-accounts', [Controllers\SubAdmin\SubAdminCorporateUserController::class, 'index'])->name('subadmin.corporate-accounts.index');
    Route::get('corporate-employees', [Controllers\SubAdmin\SubAdminCorporateEmployeeController::class, 'index'])->name('subadmin.corporate-employees.index');
    // --- BATCH 4 ROUTES ---
    
    // Technical Support
    Route::get('technical-support', function () {
        return view('subadmin.technical_support.index');
    })->name('subadmin.technical-support.index')->middleware('can:view_technical_support');

    // Bulk Registration
    Route::get('/bulk-registration', [Controllers\SubAdmin\SubAdminBulkRegistrationController::class, 'index'])->name('subadmin.bulk_registration.index');
    Route::post('/bulk-registration', [Controllers\SubAdmin\SubAdminBulkRegistrationController::class, 'store'])->name('subadmin.bulk_registration.store');
    Route::get('/bulk-registration/cities-by-tier', [Controllers\SubAdmin\SubAdminBulkRegistrationController::class, 'getCitiesByTier'])->name('subadmin.bulk_registration.cities_by_tier');

    // Vendor and Freelancer (Registration OTP Logs & Vendor CRUD)
    Route::get('/vendor-registration-otp-logs', [Controllers\SubAdmin\SubAdminRegistrationOtpLogController::class, 'index'])
        ->defaults('registrationType', 'vendor')
        ->name('subadmin.vendor_registration_otp_logs.index');
    Route::get('/freelancer-registration-otp-logs', [Controllers\SubAdmin\SubAdminRegistrationOtpLogController::class, 'index'])
        ->defaults('registrationType', 'freelancer')
        ->name('subadmin.freelancer_registration_otp_logs.index');

    Route::get('/vendors', [Controllers\SubAdmin\SubAdminVendorController::class, 'index'])->name('subadmin.vendors.index');
    Route::post('/vendors', [Controllers\SubAdmin\SubAdminVendorController::class, 'store'])->name('subadmin.vendors.store');
    Route::get('/vendors/edit/{id?}', [Controllers\SubAdmin\SubAdminVendorController::class, 'edit'])->name('subadmin.vendors.edit');
    Route::put('/vendors/{id?}', [Controllers\SubAdmin\SubAdminVendorController::class, 'update'])->name('subadmin.vendors.update');
    Route::delete('/vendors/{id?}', [Controllers\SubAdmin\SubAdminVendorController::class, 'destroy'])->name('subadmin.vendors.destroy');
    Route::get('/vendors/{vendor}/price-change-requests', [Controllers\SubAdmin\SubAdminVendorController::class, 'priceChangeRequests'])->name('subadmin.vendors.price_change_requests');

    // Location Attendance
    Route::get('/location-attendance', [Controllers\SubAdmin\SubAdminController::class, 'locationAttendance'])->name('subadmin.location_attendance.index');
    Route::get('/location-attendance/deployments/{deployment}', [Controllers\SubAdmin\SubAdminController::class, 'locationAttendanceDeploymentDetail'])->name('subadmin.location_attendance.deployment_detail');

    // Job Proc
    Route::get('/jobproc', [Controllers\SubAdmin\SubAdminJobProcController::class, 'index'])->name('subadmin.jobproc.index');
    Route::get('/jobproc/jobrequests', [Controllers\SubAdmin\SubAdminJobProcController::class, 'getJobRequests'])->name('subadmin.jobproc.jobrequests');
    Route::get('/jobproc/prospects', [Controllers\SubAdmin\SubAdminJobProcController::class, 'getProspects'])->name('subadmin.jobproc.prospects');
    Route::post('/jobproc/store', [Controllers\SubAdmin\SubAdminJobProcController::class, 'store'])->name('subadmin.jobproc.store');
    Route::post('/jobproc/import', [Controllers\SubAdmin\SubAdminJobProcController::class, 'import'])->name('subadmin.jobproc.import');
    Route::post('/jobproc/{job_request}/profile-image/generate-uniform', [Controllers\SubAdmin\SubAdminJobProcController::class, 'generateProfileImageUniform'])->name('subadmin.jobproc.profile_image_generate');
    Route::post('/jobproc/{job_request}/profile-image/approve', [Controllers\SubAdmin\SubAdminJobProcController::class, 'approveProfileImage'])->name('subadmin.jobproc.profile_image_approve');
    Route::get('/jobproc/{job_request}/price-change-requests', [Controllers\SubAdmin\SubAdminJobProcController::class, 'priceChangeRequests'])->name('subadmin.jobproc.price_change_requests');
    Route::post('/jobproc/price-change-requests/{price_change_request}/approve', [Controllers\SubAdmin\SubAdminJobProcController::class, 'approvePriceChangeRequest'])->name('subadmin.jobproc.price_change_approve');
    Route::post('/jobproc/price-change-requests/{price_change_request}/reject', [Controllers\SubAdmin\SubAdminJobProcController::class, 'rejectPriceChangeRequest'])->name('subadmin.jobproc.price_change_reject');
    Route::get('/jobproc/{id}', [Controllers\SubAdmin\SubAdminJobProcController::class, 'show'])->name('subadmin.jobproc.show');
    Route::get('/jobproc/{id}/edit', [Controllers\SubAdmin\SubAdminJobProcController::class, 'edit'])->name('subadmin.jobproc.edit');
    Route::put('/jobproc/{id}', [Controllers\SubAdmin\SubAdminJobProcController::class, 'update'])->name('subadmin.jobproc.update');
    Route::delete('/jobproc/{id}', [Controllers\SubAdmin\SubAdminJobProcController::class, 'destroy'])->name('subadmin.jobproc.destroy');
    
    // Leegality routes for JobProc
    Route::get('/jobproc/{job_request}/leegality/preview-agreement', [Controllers\SubAdmin\SubAdminFreelancerLeegalitySignatureController::class, 'previewAgreement'])->name('subadmin.jobproc.leegality_preview_agreement');
    Route::post('/jobproc/{job_request}/leegality/send', [Controllers\SubAdmin\SubAdminFreelancerLeegalitySignatureController::class, 'send'])->name('subadmin.jobproc.leegality_send');
    Route::post('/jobproc/{job_request}/leegality/refresh', [Controllers\SubAdmin\SubAdminFreelancerLeegalitySignatureController::class, 'refresh'])->name('subadmin.jobproc.leegality_refresh');
    Route::post('/jobproc/{job_request}/leegality/add-my-signature', [Controllers\SubAdmin\SubAdminFreelancerLeegalitySignatureController::class, 'addMySignature'])->name('subadmin.jobproc.leegality_add_my_signature');
    Route::get('/jobproc/{job_request}/leegality/test-add-my-signature', [Controllers\SubAdmin\SubAdminFreelancerLeegalitySignatureController::class, 'testAddMySignature'])->name('subadmin.jobproc.leegality_test_add_my_signature');
    Route::get('/jobproc/{job_request}/leegality/{signature}/view-signed', [Controllers\SubAdmin\SubAdminFreelancerLeegalitySignatureController::class, 'viewSigned'])->name('subadmin.jobproc.leegality_view_signed');
    Route::get('/jobproc/{job_request}/leegality/{signature}/download-signed', [Controllers\SubAdmin\SubAdminFreelancerLeegalitySignatureController::class, 'downloadSigned'])->name('subadmin.jobproc.leegality_download_signed');
    Route::get('/jobproc/{job_request}/leegality/{signature}/download-audit', [Controllers\SubAdmin\SubAdminFreelancerLeegalitySignatureController::class, 'downloadAudit'])->name('subadmin.jobproc.leegality_download_audit');

    // Doctor
    Route::get('/doctor-registration-otp-logs', [Controllers\SubAdmin\SubAdminDoctorRegistrationOtpLogController::class, 'index'])->name('subadmin.doctor_registration_otp_logs.index');
    Route::get('/doctor-requests', [Controllers\SubAdmin\SubAdminDoctorRequestAdminController::class, 'index'])->name('subadmin.doctor_requests.index');
    Route::get('/doctor-requests/data', [Controllers\SubAdmin\SubAdminDoctorRequestAdminController::class, 'data'])->name('subadmin.doctor_requests.data');
    Route::get('/doctor-requests/{doctor_request}/view-modal', [Controllers\SubAdmin\SubAdminDoctorRequestAdminController::class, 'viewModal'])->name('subadmin.doctor_requests.view_modal');
    Route::put('/doctor-requests/{doctor_request}/registration-profile', [Controllers\SubAdmin\SubAdminDoctorRequestAdminController::class, 'updateRegistrationProfile'])->name('subadmin.doctor_requests.registration_profile_update');
    Route::post('/doctor-requests/{doctor_request}/generate-about', [Controllers\SubAdmin\SubAdminDoctorRequestAdminController::class, 'generateRegistrationAbout'])->name('subadmin.doctor_requests.generate_about');
    Route::post('/doctor-requests/{doctor_request}/profile-image/generate-coat', [Controllers\SubAdmin\SubAdminDoctorRequestAdminController::class, 'generateProfileImageCoat'])->name('subadmin.doctor_requests.profile_image_generate');
    
    Route::get('/doctor-consultation-services', [Controllers\SubAdmin\SubAdminDoctorConsultationServiceController::class, 'index'])->name('subadmin.doctor_consultation_services.index');
    Route::get('/doctor-consultation-services/data', [Controllers\SubAdmin\SubAdminDoctorConsultationServiceController::class, 'data'])->name('subadmin.doctor_consultation_services.data');
    Route::get('/doctor-consultation-services/{service}/view-modal', [Controllers\SubAdmin\SubAdminDoctorConsultationServiceController::class, 'viewModal'])->name('subadmin.doctor_consultation_services.view_modal');
    Route::put('/doctor-consultation-services/{service}/approve', [Controllers\SubAdmin\SubAdminDoctorConsultationServiceController::class, 'approve'])->name('subadmin.doctor_consultation_services.approve');
    Route::put('/doctor-consultation-services/{service}/reject', [Controllers\SubAdmin\SubAdminDoctorConsultationServiceController::class, 'reject'])->name('subadmin.doctor_consultation_services.reject');

    // Whatsapp
    Route::get('all-whatsapp-chats', [Controllers\WhatsappMsgController::class, 'all_whatsapp_chats_index'])->name('subadmin.all_whatsapp_chats.index');
    Route::get('subadmin/all-whatsapp-chats/numbers', [Controllers\WhatsappMsgController::class, 'get_all_whatsapp_numbers'])->name('subadmin.all_whatsapp_chats.numbers');
    Route::get('subadmin/all-whatsapp-chats/messages/{number}', [Controllers\WhatsappMsgController::class, 'get_messages_for_number'])->name('subadmin.all_whatsapp_chats.messages');
    Route::get('subadmin/all-whatsapp-chats/executives/{number}', [Controllers\WhatsappMsgController::class, 'get_executives_for_number'])->name('subadmin.all_whatsapp_chats.executives');
    Route::post('subadmin/all-whatsapp-chats/mark-read/{number}', [Controllers\WhatsappMsgController::class, 'markMessagesAsRead'])->name('subadmin.all_whatsapp_chats.mark_read');

    // Referral Leads
    Route::get('/referral-leads', [Controllers\SubAdmin\SubAdminReferralLeadController::class, 'index'])->name('subadmin.referral_leads.index');
    Route::post('/referral-leads/commission', [Controllers\SubAdmin\SubAdminReferralLeadController::class, 'updateCommission'])->name('subadmin.referral_leads.commission');

    // Vendor Payments
    Route::get('/vendor-payments', [Controllers\SubAdmin\SubAdminVendorPaymentController::class, 'index'])->name('subadmin.vendor_payments.index');
    Route::get('/vendor-payments/history', [Controllers\SubAdmin\SubAdminVendorPaymentController::class, 'vendorHistory'])->name('subadmin.vendor_payments.history');
    Route::post('/vendor-payments', [Controllers\SubAdmin\SubAdminVendorPaymentController::class, 'store'])->name('subadmin.vendor_payments.store');
    Route::get('/vendor-payments/{id}', [Controllers\SubAdmin\SubAdminVendorPaymentController::class, 'show'])->name('subadmin.vendor_payments.show');
    Route::get('/vendor-payments/{id}/edit', [Controllers\SubAdmin\SubAdminVendorPaymentController::class, 'edit'])->name('subadmin.vendor_payments.edit');
    Route::put('/vendor-payments/{id}', [Controllers\SubAdmin\SubAdminVendorPaymentController::class, 'update'])->name('subadmin.vendor_payments.update');
    Route::delete('/vendor-payments/{id}', [Controllers\SubAdmin\SubAdminVendorPaymentController::class, 'destroy'])->name('subadmin.vendor_payments.destroy');
    Route::get('/vendor-payments/vendor/{vendorId}', [Controllers\SubAdmin\SubAdminVendorPaymentController::class, 'getVendorPayments'])->name('subadmin.vendor_payments.vendor');
    Route::get('/vendor-payments/vendor/{vendorId}/statement', [Controllers\SubAdmin\SubAdminVendorPaymentController::class, 'statement'])->name('subadmin.vendor_payments.statement');
    Route::get('/vendor-payments/stats', [Controllers\SubAdmin\SubAdminVendorPaymentController::class, 'getPaymentStats'])->name('subadmin.vendor_payments.stats');
    Route::get('/vendor-payments/{id}/screenshot', [Controllers\SubAdmin\SubAdminVendorPaymentController::class, 'downloadScreenshot'])->name('subadmin.vendor_payments.screenshot');
    Route::get('/vendor-payments/{id}/invoice', [Controllers\SubAdmin\SubAdminVendorPaymentController::class, 'invoice'])->name('subadmin.vendor_payments.invoice');

    // Freelancer Payments
    Route::get('/freelancer-payments', [Controllers\SubAdmin\SubAdminFreelancerPaymentController::class, 'index'])->name('subadmin.freelancer_payments.index');
    Route::post('/freelancer-payments', [Controllers\SubAdmin\SubAdminFreelancerPaymentController::class, 'store'])->name('subadmin.freelancer_payments.store');
    Route::get('/freelancer-payments/{id}', [Controllers\SubAdmin\SubAdminFreelancerPaymentController::class, 'show'])->name('subadmin.freelancer_payments.show');
    Route::get('/freelancer-payments/{id}/edit', [Controllers\SubAdmin\SubAdminFreelancerPaymentController::class, 'edit'])->name('subadmin.freelancer_payments.edit');
    Route::put('/freelancer-payments/{id}', [Controllers\SubAdmin\SubAdminFreelancerPaymentController::class, 'update'])->name('subadmin.freelancer_payments.update');
    Route::delete('/freelancer-payments/{id}', [Controllers\SubAdmin\SubAdminFreelancerPaymentController::class, 'destroy'])->name('subadmin.freelancer_payments.destroy');
    Route::get('/freelancer-payments/{id}/invoice', [Controllers\SubAdmin\SubAdminFreelancerPaymentController::class, 'invoice'])->name('subadmin.freelancer_payments.invoice');
    Route::get('/freelancer-payments/statement/{freelancerId}', [Controllers\SubAdmin\SubAdminFreelancerPaymentController::class, 'statement'])->name('subadmin.freelancer_payments.statement');
    Route::get('/freelancer-payments/freelancer/{freelancerId}', [Controllers\SubAdmin\SubAdminFreelancerPaymentController::class, 'getFreelancerPayments'])->name('subadmin.freelancer_payments.freelancer');

    // Locations
    Route::get('/locations/bulk-template', [Controllers\SubAdmin\SubAdminLocationController::class, 'downloadBulkTemplate'])->name('subadmin.locations.bulk-template');
    Route::post('/locations/bulk-import', [Controllers\SubAdmin\SubAdminLocationController::class, 'bulkImport'])->name('subadmin.locations.bulk-import');
    Route::get('/locations/service-rates', [Controllers\SubAdmin\SubAdminLocationController::class, 'getServiceRates'])->name('subadmin.locations.service-rates');
    Route::resource('locations', Controllers\SubAdmin\SubAdminLocationController::class)->names('subadmin.locations');

});
