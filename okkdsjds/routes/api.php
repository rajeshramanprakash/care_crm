<?php

use App\Facades\UserAssignment;
use App\Http\Controllers\Admin\ChatController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\GroupChatController;
use App\Http\Controllers\MCubeController;
use App\Http\Controllers\TataController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WhatsappMsgController;
use App\Models\JobRequest;
use App\Models\Lead;
use App\Models\OperationLead;
use App\Models\OperationDeploymentDetails;
use App\Models\User;
use App\Models\WhatsAppMessage;
use App\Models\WhatsappMsgGroup;
use App\Services\ExpoNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('/mcube/webhook', [MCubeController::class, 'handleCallWebhook']);

Route::post('/wa-submit', function (Request $request) {
    try {
        Log::info('WhatsApp webhook received:', $request->all());

        $data = $request->all();

        // Handle status updates (sent, delivered, read, etc.)
        if (isset($data['entry'][0]['changes'][0]['value']['statuses'][0])) {
            $statuses = $data['entry'][0]['changes'][0]['value']['statuses'];
            foreach ($statuses as $status) {
                $msgId = $status['id'];
                $msgStatus = $status['status']; // sent, delivered, read, etc.
                // Update the message in DB
                \App\Models\WhatsAppMessage::where('msg_id', $msgId)->update(['status' => $msgStatus]);
            }

            return response()->json(['status' => 'success', 'message' => 'Statuses updated']);
        }

        if (! isset($data['entry'][0]['changes'][0]['value']['messages'][0])) {
            Log::warning('No message found in webhook data');

            return response()->json(['status' => 'success', 'message' => 'No message to process']);
        }

        $messageData = $data['entry'][0]['changes'][0]['value']['messages'][0];
        $contactData = $data['entry'][0]['changes'][0]['value']['contacts'][0];
        $metadata = $data['entry'][0]['changes'][0]['value']['metadata'];

        $msgId = $messageData['id'];
        $msgFrom = $messageData['from'];
        $timestamp = $messageData['timestamp'];
        $messageType = $messageData['type'];
        $customerName = $contactData['profile']['name'] ?? 'Unknown';
        $phoneNumber = $msgFrom;

        if (strlen($phoneNumber) > 10) {
            if (substr($phoneNumber, 0, 2) == '91') {
                $phoneNumber = substr($phoneNumber, 2);
            }
        }

        $formattedTime = date('Y-m-d H:i:s', $timestamp);
        $textMsg = '';
        $docId = null;

        $whatsappMessage = new WhatsAppMessage;
        $whatsappMessage->msg_id = $msgId;
        $whatsappMessage->msg_from = $phoneNumber;
        $whatsappMessage->time = $formattedTime;
        $whatsappMessage->type = $messageType;
        $whatsappMessage->is_sent = 0;
        $whatsappMessage->sent_by = null;

        // Handle different message types
        if ($messageType == 'text') {
            $textMsg = $messageData['text']['body'] ?? '';
            $whatsappMessage->body = $textMsg;
        } elseif ($messageType == 'document') {
            $docId = $messageData['document']['id'] ?? null;
            $whatsappMessage->doc = $docId;
        } elseif ($messageType == 'audio') {
            $docId = $messageData['audio']['id'] ?? null;
            $whatsappMessage->doc = $docId;
        } elseif ($messageType == 'video') {
            $docId = $messageData['video']['id'] ?? null;
            $whatsappMessage->doc = $docId;
        } elseif ($messageType == 'image') {
            $docId = $messageData['image']['id'] ?? null;
            $whatsappMessage->doc = $docId;
        } elseif ($messageType == 'button') {
            $textMsg = $messageData['button']['text'] ?? '';
            $whatsappMessage->body = $textMsg;
        } elseif ($messageType == 'contacts') {
            if (isset($messageData['contacts'][0])) {
                $contact_name = $messageData['contacts'][0]['name']['formatted_name'] ?? '';
                $contact_number = $messageData['contacts'][0]['phones'][0]['phone'] ?? '';
                $contactData = json_encode([
                    'name' => $contact_name,
                    'mobile' => $contact_number,
                ]);
                $whatsappMessage->doc = $contactData;
            }
        } elseif ($messageType == 'location') {
            $latitude = $messageData['location']['latitude'] ?? '';
            $longitude = $messageData['location']['longitude'] ?? '';
            $locationUrl = "https://www.google.com/maps/@$latitude,$longitude,12z";
            $whatsappMessage->doc = $locationUrl;
        }

        // Inline cURL logic for media
        if (in_array($messageType, ['image', 'audio', 'video', 'document']) && $docId) {
            $authKey = config('services.whatsapp.auth_key', env('WHATSAPP_AUTH_KEY'));
            Log::info('authKey', ['authKey' => $authKey]);
            $url = 'https://rengage.mcube.com/api/wpbox/getMedia?message_id='.urlencode($msgId);
            Log::info("Fetching media for message type: $messageType, docId: $docId, msgId: $msgId, url: $url");
            $curl = curl_init();
            curl_setopt_array($curl, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'GET',
                CURLOPT_SSL_VERIFYHOST => 0,
                CURLOPT_SSL_VERIFYPEER => 0,
                CURLOPT_HTTPHEADER => [
                    "Authorization: Bearer $authKey",
                ],
            ]);
            $response = curl_exec($curl);
            $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            if (curl_errno($curl)) {
                Log::error("cURL error fetching media for msgId $msgId: ".curl_error($curl), [
                    'msgId' => $msgId,
                    'docId' => $docId,
                    'url' => $url,
                    'request_data' => $request->all(),
                ]);
                curl_close($curl);
                $whatsappMessage->doc = $docId;
                Log::info("Saved doc as ID (fallback): $docId");
            } else {
                curl_close($curl);
                Log::info("Engage API HTTP $httpcode response for msgId $msgId: $response");
                if ($httpcode >= 200 && $httpcode < 300) {
                    $data = json_decode($response, true);
                    Log::info("Engage API decoded response for msgId $msgId: ".json_encode($data));
                    if (isset($data['status']) && $data['status'] === 'success' && ! empty($data['data'][0]['header_image'])) {
                        $whatsappMessage->doc = $data['data'][0]['header_image'];
                        Log::info('Saved doc as header_image URL: '.$data['data'][0]['header_image']);
                    } elseif (isset($data['status']) && $data['status'] === 'success' && ! empty($data['data'][0]['header_document'])) {
                        $whatsappMessage->doc = $data['data'][0]['header_document'];
                        Log::info('Saved doc as header_document URL: '.$data['data'][0]['header_document']);
                    } elseif (isset($data['status']) && $data['status'] === 'success' && ! empty($data['data'][0]['header_audio'])) {
                        $whatsappMessage->doc = $data['data'][0]['header_audio'];
                        Log::info('Saved doc as header_audio URL: '.$data['data'][0]['header_audio']);
                    } elseif (isset($data['status']) && $data['status'] === 'success' && ! empty($data['data'][0]['header_video'])) {
                        $whatsappMessage->doc = $data['data'][0]['header_video'];
                        Log::info('Saved doc as header_video URL: '.$data['data'][0]['header_video']);
                    } else {
                        $whatsappMessage->doc = $docId;
                        Log::warning("No media URL found in Engage API response for msgId $msgId. Saved doc as ID: $docId", [
                            'response' => $response,
                            'decoded' => $data,
                            'msgId' => $msgId,
                            'docId' => $docId,
                        ]);
                    }
                } else {
                    $whatsappMessage->doc = $docId;
                    Log::error("Engage API returned HTTP $httpcode for msgId $msgId. Saved doc as ID: $docId", [
                        'response' => $response,
                        'msgId' => $msgId,
                        'docId' => $docId,
                        'url' => $url,
                        'request_data' => $request->all(),
                    ]);
                }
            }
        } else {
            $whatsappMessage->doc = $docId; // For non-media, or fallback
        }

        // Save the WhatsApp message
        try {
            $whatsappMessage->save();
            Log::info('WhatsApp message saved with ID: '.$whatsappMessage->id);
        } catch (\Throwable $e) {
            Log::error('Error saving WhatsApp message', [
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'msgId' => $msgId,
                'docId' => $docId,
                'whatsappMessage' => $whatsappMessage->toArray(),
                'request_data' => $request->all(),
            ]);
            throw $e;
        }

        // Check for existing lead
        Log::info('Checking for existing lead...', ['phoneNumber' => $phoneNumber]);
        $existingLead = Lead::where('contact_no', $phoneNumber)->first();
        Log::info('Existing lead: '.($existingLead ? $existingLead->id : 'none'));

        if (! $existingLead) {
            Log::info('No existing lead, checking user...', ['phoneNumber' => $phoneNumber]);
            $checkUser = User::where('mobile', $phoneNumber)->first();
            Log::info('Check user: '.($checkUser ? $checkUser->id : 'none'));
            $getUser = UserAssignment::getAssigningUser(2, null, 'whatsapp');
            Log::info('Assigning user: '.($getUser ? $getUser->id : 'none'));
            if (! $checkUser) {
                Log::info('Creating new lead...');
                $lead = Lead::create([
                    'date' => now(),
                    'executive' => $getUser->id,
                    'customer_name' => $customerName,
                    'contact_no' => $phoneNumber,
                    'lead_source' => 'whatsapp',
                    'status' => 'follow-up',
                    'stage' => 'active',
                ]);
                Log::info('New lead created with ID: '.$lead->id);
                try {
                    $whatsappStatus = config('services.whatsapp.status', env('WHATSAPP_MSG_STATUS'));
                    $authKey = config('services.whatsapp.auth_key', env('WHATSAPP_AUTH_KEY'));
                    if ($whatsappStatus !== true) {
                        return response()->json(['error' => 'WhatsApp messaging is disabled'], 403);
                    }
                    if (empty($authKey)) {
                        return response()->json(['error' => 'WhatsApp configuration error'], 500);
                    }
                    $lead_id = $lead->formatted_id;
                    // 24-hour logic
                    $lastMsg = \App\Models\WhatsAppMessage::where('msg_from', $getUser->mobile)
                        ->where('is_sent', 0)
                        ->orderByDesc('time')
                        ->first();
                    $now = \Carbon\Carbon::now();
                    $shouldSendTemplate = true;
                    if ($lastMsg && $lastMsg->time) {
                        $lastTime = \Carbon\Carbon::parse($lastMsg->time);
                        if ($now->diffInHours($lastTime) < 24) {
                            $shouldSendTemplate = false;
                        }
                    }
                    if ($shouldSendTemplate) {
                        // Send template message
                        $url = 'https://rengage.mcube.com/api/wpbox/sendtemplatemessage';
                        $payload = [
                            'phone' => $getUser->mobile,
                            'template_name' => 'lead_notify',
                            'template_language' => 'en_US',
                            'components' => [
                                [
                                    'type' => 'body',
                                    'parameters' => [
                                        ['type' => 'text', 'text' => 'WHATSAPP'],
                                        ['type' => 'text', 'text' => $lead_id],
                                    ],
                                ],
                            ],
                        ];
                        $response = \Illuminate\Support\Facades\Http::withHeaders([
                            'Content-Type' => 'application/json',
                            'Authorization' => 'Bearer '.$authKey,
                        ])->post($url, $payload);
                        if ($response->successful()) {
                            $responseData = $response->json();
                            if (isset($responseData['status']) && $responseData['status'] === 'success') {
                                $currentTimestamp = \Carbon\Carbon::now();
                                $newWaMsg = new WhatsAppMessage;
                                $newWaMsg->msg_id = $responseData['message_id'] ?? $getUser->mobile;
                                $newWaMsg->msg_from = "$getUser->mobile";
                                $newWaMsg->time = $currentTimestamp;
                                $newWaMsg->type = 'template';
                                $newWaMsg->is_sent = '1';
                                $newWaMsg->body = "Hi you have received new whatsapp lead. Lead Id $lead_id (TEMPLATE)";
                                $newWaMsg->save();

                            }
                        }
                    } else {
                        // Send normal message
                        $url = 'https://rengage.mcube.com/api/wpbox/sendmessage';
                        $payload = [
                            'phone' => $getUser->mobile,
                            'message' => 'Hi you have received new whatsapp lead. Lead id : '.$lead_id,
                        ];
                        $response = \Illuminate\Support\Facades\Http::withHeaders([
                            'Content-Type' => 'application/json',
                            'Authorization' => 'Bearer '.$authKey,
                        ])->post($url, $payload);
                        if ($response->successful()) {
                            $responseData = $response->json();
                            if (isset($responseData['status']) && $responseData['status'] === 'success') {
                                $currentTimestamp = \Carbon\Carbon::now();
                                $newWaMsg = new WhatsAppMessage;
                                $newWaMsg->msg_id = $responseData['message_id'] ?? $getUser->mobile;
                                $newWaMsg->msg_from = "$getUser->mobile";
                                $newWaMsg->time = $currentTimestamp;
                                $newWaMsg->type = 'text';
                                $newWaMsg->is_sent = '1';
                                $newWaMsg->body = "Hi you have received new whatsapp lead. Lead id : $lead_id";
                                $newWaMsg->save();
                            }
                        }
                    }
                    Log::info('About to save WhatsappMsgGroup', [
                        'whatsapp_number' => $phoneNumber,
                        'executive_ids' => $getUser->id,
                    ]);

                    $group = WhatsappMsgGroup::where('whatsapp_number', $phoneNumber)->first();
                    if ($group) {
                        $ids = array_filter(explode(',', $group->executive_ids));
                        if (! in_array($getUser->id, $ids)) {
                            $ids[] = $getUser->id;
                            $group->executive_ids = implode(',', $ids);
                            $group->save();
                        }
                    } else {
                        $executiveIds = [$getUser && $getUser->id ? $getUser->id : 1, 1];
                        $executiveIds = array_unique($executiveIds); // Remove duplicates if $getUser->id is 1
                        WhatsappMsgGroup::create([
                            'whatsapp_number' => $phoneNumber,
                            'executive_ids' => implode(',', $executiveIds),
                        ]);
                    }
                } catch (\Exception $e) {
                }

                Log::info('New lead created with ID: '.$lead->id.' for phone: '.$phoneNumber);

                // Send Expo push notification to executive
                ExpoNotificationService::send(
                    [$getUser->id],
                    'New WhatsApp Lead',
                    'A new WhatsApp lead has been created. Lead ID: '.$lead->formatted_id
                );

                return response()->json([
                    'status' => 'success',
                    'message' => 'WhatsApp message processed and new lead created',
                    'lead_id' => $lead->id,
                    'message_id' => $whatsappMessage->id,
                ]);
            } else {
                // User exists, skip lead creation, but send WhatsApp message to $getUser
                // ...send WhatsApp message to $getUser...
            }
        } else {
            Log::info('Lead already exists for phone: '.$phoneNumber.' (Lead ID: '.$existingLead->id.')');

            return response()->json([
                'status' => 'success',
                'message' => 'WhatsApp message processed, lead already exists',
                'lead_id' => $existingLead->id,
                'message_id' => $whatsappMessage->id,
            ]);
        }

    } catch (\Exception $e) {
        Log::error('WhatsApp webhook error: '.$e->getMessage(), [
            'trace' => $e->getTraceAsString(),
            'request_data' => $request->all(),
        ]);

        return response()->json([
            'status' => 'error',
            'message' => 'Failed to process WhatsApp webhook: '.$e->getMessage(),
        ], 500);
    }
});

Route::post('/form-submit', function (Request $request) {
    try {
        Log::info($request->all());
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'mobile' => 'required|string|max:20',
            'city' => 'nullable|string|max:255',
        ]);
        $validated['lead_source'] = 'web';

        // Clean mobile: keep only digits, last 10 digits
        $validated['mobile'] = substr(preg_replace('/\D/', '', $validated['mobile']), -10);

        $existingLead = Lead::where('contact_no', $validated['mobile'])->first();

        if ($existingLead) {
            $existingLead->date = now();
            $existingLead->save();

            return response()->json([
                'status' => 'success',
                'message' => 'Lead already exists',
                'lead_id' => $existingLead->id,
            ]);
        }

        $getUser = UserAssignment::getAssigningUser(2, null, 'web');

        $lead = Lead::create([
            'date' => now(),
            'executive' => $getUser->id,
            'customer_name' => $validated['name'],
            'contact_no' => $validated['mobile'],
            'location' => $validated['city'],
            'lead_source' => $validated['lead_source'],
            'status' => 'follow-up',
            'stage' => 'active',
        ]);

        try {
            $whatsappStatus = config('services.whatsapp.status', env('WHATSAPP_MSG_STATUS'));
            $authKey = config('services.whatsapp.auth_key', env('WHATSAPP_AUTH_KEY'));
            if ($whatsappStatus !== true) {
                return response()->json(['error' => 'WhatsApp messaging is disabled'], 403);
            }
            if (empty($authKey)) {
                return response()->json(['error' => 'WhatsApp configuration error'], 500);
            }
            $lead_id = $lead->formatted_id;
            // 24-hour logic
            $lastMsg = \App\Models\WhatsAppMessage::where('msg_from', $getUser->mobile)
                ->where('is_sent', 0)
                ->orderByDesc('time')
                ->first();
            $now = \Carbon\Carbon::now();
            $shouldSendTemplate = true;
            if ($lastMsg && $lastMsg->time) {
                $lastTime = \Carbon\Carbon::parse($lastMsg->time);
                if ($now->diffInHours($lastTime) < 24) {
                    $shouldSendTemplate = false;
                }
            }
            if ($shouldSendTemplate) {
                // Send template message
                $url = 'https://rengage.mcube.com/api/wpbox/sendtemplatemessage';
                $payload = [
                    'phone' => $getUser->mobile,
                    'template_name' => 'lead_notify',
                    'template_language' => 'en_US',
                    'components' => [
                        [
                            'type' => 'body',
                            'parameters' => [
                                ['type' => 'text', 'text' => 'WEB'],
                                ['type' => 'text', 'text' => $lead_id],
                            ],
                        ],
                    ],
                ];
                $response = \Illuminate\Support\Facades\Http::withHeaders([
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer '.$authKey,
                ])->post($url, $payload);
                if ($response->successful()) {
                    $responseData = $response->json();
                    if (isset($responseData['status']) && $responseData['status'] === 'success') {
                        $currentTimestamp = \Carbon\Carbon::now();
                        $newWaMsg = new WhatsAppMessage;
                        $newWaMsg->msg_id = $responseData['message_id'] ?? $getUser->mobile;
                        $newWaMsg->msg_from = "$getUser->mobile";
                        $newWaMsg->time = $currentTimestamp;
                        $newWaMsg->type = 'template';
                        $newWaMsg->is_sent = '1';
                        $newWaMsg->body = "Hi you have received new form lead. Lead Id $lead_id (TEMPLATE)";
                        $newWaMsg->save();
                        $new_whatsapp_grp = new WhatsappMsgGroup;
                        $new_whatsapp_grp->whatsapp_number = $validated['mobile'];
                        $new_whatsapp_grp->executive_ids = $getUser->id;
                        $new_whatsapp_grp->save();
                    }
                }
            } else {
                // Send normal message
                $url = 'https://rengage.mcube.com/api/wpbox/sendmessage';
                $payload = [
                    'phone' => $getUser->mobile,
                    'message' => 'Hi you have received new form lead. Lead id : '.$lead_id,
                ];
                $response = \Illuminate\Support\Facades\Http::withHeaders([
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer '.$authKey,
                ])->post($url, $payload);
                if ($response->successful()) {
                    $responseData = $response->json();
                    if (isset($responseData['status']) && $responseData['status'] === 'success') {
                        $currentTimestamp = \Carbon\Carbon::now();
                        $newWaMsg = new WhatsAppMessage;
                        $newWaMsg->msg_id = $responseData['message_id'] ?? $getUser->mobile;
                        $newWaMsg->msg_from = "$getUser->mobile";
                        $newWaMsg->time = $currentTimestamp;
                        $newWaMsg->type = 'text';
                        $newWaMsg->is_sent = '1';
                        $newWaMsg->body = "Hi you have received new form lead. Lead id : $lead_id";
                        $newWaMsg->save();
                        $new_whatsapp_grp = new WhatsappMsgGroup;
                        $new_whatsapp_grp->whatsapp_number = $validated['mobile'];
                        $new_whatsapp_grp->executive_ids = $getUser->id;
                        $new_whatsapp_grp->save();
                    }
                }
            }
        } catch (\Exception $e) {
        }

        // Send Expo push notification to executive
        ExpoNotificationService::send(
            [$getUser->id],
            'New Web Lead',
            'A new Web lead has been created. Lead ID: '.$lead->formatted_id
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Form data processed successfully',
            'lead_id' => $lead->id,
        ]);

    } catch (\Exception $e) {
        \Illuminate\Support\Facades\Log::error('Form submission error: '.$e->getMessage());

        return response()->json([
            'status' => 'error',
            'message' => 'Failed to process form data: '.$e->getMessage(),
        ], 500);
    }
});

Route::post('tatateleservices-webhook', [TataController::class, 'handleCallWebhook']);

Route::get('tatateleservices-dialplan', function (Request $request) {
    $data = $request->all();
    
    Log::info('📞 DIALPLAN: Call received', [
        'call_id' => $data['call_id'] ?? 'unknown',
        'caller_id_number' => $data['caller_id_number'] ?? 'unknown',
        'call_to_number' => $data['call_to_number'] ?? 'unknown',
        'start_stamp' => $data['start_stamp'] ?? 'unknown',
        'uuid' => $data['uuid'] ?? 'unknown'
    ]);
    
    $number = $data['caller_id_number']; // Always comes with 91 prefix
    $numberWithout91 = substr($number, 2); // Remove 91 prefix for checking old records
    $numberWith91 = $number; // Keep original with 91 for new leads
    
    Log::info('📞 DIALPLAN: Phone number formats', [
        'original' => $number,
        'with_91' => $numberWith91,
        'without_91' => $numberWithout91
    ]);

    // Create initial call record in call_details table for active tracking
    try {
        $callId = $data['call_id'] ?? uniqid('call_', true);
        $callStartTime = now();
        
        // Create initial call record with "ringing" status
        \App\Models\CallDetails::create([
            'lead_id' => null, // Will be updated when we find the lead
            'call_for' => null, // Will be updated when we find the record type (lead, operation_lead, job_request)
            'executive_id' => null, // Will be updated when we find the executive
            'caller_id_number' => $number,
            'agent_number' => null, // Will be updated when call is answered
            'agent_name' => null, // Will be updated when call is answered
            'call_status' => 'ringing', // Initial status
            'recording_url' => null, // Will be updated when call completes
            'call_received_datetime' => $callStartTime,
            'customer_name' => null, // Will be updated when we find the lead
            'lead_code' => null, // Will be updated when we find the lead
            'is_processed' => false,
            'call_duration' => null, // Will be updated when call completes
            'call_notes' => 'Call initiated via dialplan',
            'call_type' => 'inbound',
            'call_source' => 'tata_dialplan',
            'raw_data' => $data
        ]);
        
        Log::info('✅ DIALPLAN: Initial call record created for active tracking', [
            'call_id' => $callId,
            'caller_number' => $number,
            'status' => 'ringing'
        ]);
    } catch (\Exception $e) {
        Log::error('❌ DIALPLAN: Failed to create initial call record', [
            'error' => $e->getMessage(),
            'caller_number' => $number
        ]);
    }

    // PRIORITY 1: Try to find job request with both formats
    Log::info('🔍 DIALPLAN: Searching for JobRequest', [
        'with_91' => $number,
        'without_91' => $numberWithout91
    ]);
    
    $jobRequest = JobRequest::where('contact_no', $number)->first(); // With 91
    if (!$jobRequest) {
        $jobRequest = JobRequest::where('contact_no', $numberWithout91)->first(); // Without 91
    }
    if ($jobRequest) {
        Log::info('✅ DIALPLAN: JobRequest found (PRIORITY 1)', [
            'job_request_id' => $jobRequest->id,
            'matched_format' => $jobRequest->contact_no,
            'executive_id' => $jobRequest->executive_id
        ]);
        
        // Check if this freelancer has active deployment
        $activeDeployment = \App\Models\OperationDeploymentDetails::where('freelance_staff_id', $jobRequest->id)
            ->where('deployment_status', 'In Progress')
            ->first();
        
        if ($activeDeployment) {
            $operationLead = \App\Models\OperationLead::find($activeDeployment->operation_lead_id);
            
            if ($operationLead && $operationLead->executive) {
                $operationExecutiveUser = User::find($operationLead->executive);
                if ($operationExecutiveUser && $operationExecutiveUser->mobile) {
                    Log::info('✅ DIALPLAN: Transferring to Operation Executive (active deployment)', [
                        'executive_id' => $operationExecutiveUser->id,
                        'executive_mobile' => $operationExecutiveUser->mobile
                    ]);
                    
                    // Update call record with executive and lead information
                    \App\Models\CallDetails::where('caller_id_number', $number)
                        ->where('call_status', 'ringing')
                        ->where('call_source', 'tata_dialplan')
                        ->latest()
                        ->first()
                        ?->update([
                            'executive_id' => $operationExecutiveUser->id,
                            'agent_number' => $operationExecutiveUser->mobile,
                            'agent_name' => $operationExecutiveUser->f_name . ' ' . $operationExecutiveUser->l_name,
                            'customer_name' => $jobRequest->customer_name,
                            'lead_code' => 'JR' . str_pad($jobRequest->id, 8, '0', STR_PAD_LEFT),
                            'call_for' => 'job_request',
                            'call_notes' => 'JobRequest with active deployment - transferred to Operation Executive'
                        ]);
                    
                    $res = [[
                        'transfer' => [
                            'type' => 'number',
                            'data' => [$operationExecutiveUser->mobile],
                            'ring_type' => 'order_by',
                            'skip_active' => true,
                        ],
                    ]];

                    return response()->json($res, 200);
                }
            }
        }
        
        // If no active deployment, transfer to JobRequest executive
        $executiveUser = User::find($jobRequest->executive_id);
        if ($executiveUser && $executiveUser->mobile) {
            Log::info('✅ DIALPLAN: Transferring to JobRequest Executive', [
                'executive_id' => $executiveUser->id,
                'executive_mobile' => $executiveUser->mobile
            ]);
            
            // Update call record with executive and lead information
            \App\Models\CallDetails::where('caller_id_number', $number)
                ->where('call_status', 'ringing')
                ->where('call_source', 'tata_dialplan')
                ->latest()
                ->first()
                ?->update([
                    'executive_id' => $executiveUser->id,
                    'agent_number' => $executiveUser->mobile,
                    'agent_name' => $executiveUser->f_name . ' ' . $executiveUser->l_name,
                    'customer_name' => $jobRequest->customer_name,
                    'lead_code' => 'JR' . str_pad($jobRequest->id, 8, '0', STR_PAD_LEFT),
                    'call_for' => 'job_request',
                    'call_notes' => 'JobRequest - transferred to JobRequest Executive'
                ]);
            
            $res = [[
                'transfer' => [
                    'type' => 'number',
                    'data' => [$executiveUser->mobile],
                    'ring_type' => 'order_by',
                    'skip_active' => true,
                ],
            ]];

            return response()->json($res, 200);
        } else {
            return response()->json([
                'status' => 'OK',
                'message' => 'No need to forward the call',
            ], 200);
        }
    }

    // PRIORITY 2: Try to find operation lead with both formats
    Log::info('🔍 DIALPLAN: Searching for OperationLead', [
        'with_91' => $number,
        'without_91' => $numberWithout91
    ]);
    
    $operationLead = OperationLead::where('contact_no', $number)->first(); // With 91
    if (!$operationLead) {
        $operationLead = OperationLead::where('contact_no', $numberWithout91)->first(); // Without 91
    }
    if ($operationLead) {
        Log::info('✅ DIALPLAN: OperationLead found (PRIORITY 2)', [
            'operation_lead_id' => $operationLead->id,
            'matched_format' => $operationLead->contact_no,
            'executive' => $operationLead->executive,
            'lead_id' => $operationLead->lead_id
        ]);
        
        $executiveUser = User::find($operationLead->executive);
        if ($executiveUser) {
            Log::info('✅ DIALPLAN: Transferring to OperationLead Executive', [
                'executive_id' => $executiveUser->id,
                'executive_mobile' => $executiveUser->mobile
            ]);
            
            // Update call record with executive and lead information
            \App\Models\CallDetails::where('caller_id_number', $number)
                ->where('call_status', 'ringing')
                ->where('call_source', 'tata_dialplan')
                ->latest()
                ->first()
                ?->update([
                    'executive_id' => $executiveUser->id,
                    'agent_number' => $executiveUser->mobile,
                    'agent_name' => $executiveUser->f_name . ' ' . $executiveUser->l_name,
                    'customer_name' => $operationLead->customer_name,
                    'lead_code' => $operationLead->lead_id,
                    'call_for' => 'operation_lead',
                    'call_notes' => 'OperationLead - transferred to OperationLead Executive'
                ]);
            
            $res = [[
                'transfer' => [
                    'type' => 'number',
                    'data' => [$executiveUser->mobile],
                    'ring_type' => 'order_by',
                    'skip_active' => true,
                ],
            ]];

            return response()->json($res, 200);
        } else {
            return response()->json([
                'status' => 'OK',
                'message' => 'No need to forward the call',
            ], 200);
        }
    }

    // PRIORITY 3: Try to find normal lead with both formats
    Log::info('🔍 DIALPLAN: Searching for Lead', [
        'with_91' => $number,
        'without_91' => $numberWithout91
    ]);
    
    $lead = Lead::where('contact_no', $number)->first(); // With 91
    if (!$lead) {
        $lead = Lead::where('contact_no', $numberWithout91)->first(); // Without 91
    }
    if ($lead) {
        Log::info('✅ DIALPLAN: Lead found (PRIORITY 3)', [
            'lead_id' => $lead->id,
            'matched_format' => $lead->contact_no,
            'executive' => $lead->executive,
            'formatted_id' => $lead->formatted_id
        ]);
        
        $executiveUser = User::find($lead->executive);
        if ($executiveUser && $executiveUser->mobile && $executiveUser->is_active == 1 && $executiveUser->is_break == 0) {
            Log::info('✅ DIALPLAN: Transferring to Lead Executive', [
                'executive_id' => $executiveUser->id,
                'executive_mobile' => $executiveUser->mobile
            ]);
            
            // Update call record with executive and lead information
            \App\Models\CallDetails::where('caller_id_number', $number)
                ->where('call_status', 'ringing')
                ->where('call_source', 'tata_dialplan')
                ->latest()
                ->first()
                ?->update([
                    'lead_id' => $lead->id,
                    'executive_id' => $executiveUser->id,
                    'agent_number' => $executiveUser->mobile,
                    'agent_name' => $executiveUser->f_name . ' ' . $executiveUser->l_name,
                    'customer_name' => $lead->customer_name,
                    'lead_code' => $lead->formatted_id ?? '#' . $lead->id,
                    'call_for' => 'lead',
                    'call_notes' => 'Lead - transferred to Lead Executive'
                ]);
            
            $res = [[
                'transfer' => [
                    'type' => 'number',
                    'data' => [$executiveUser->mobile],
                    'ring_type' => 'order_by',
                    'skip_active' => true,
                ],
            ]];

            return response()->json($res, 200);
        }

        $getUser = UserAssignment::getAssigningUser(2, null, 'ivr');
        if ($getUser && $getUser->mobile) {
            // Update call record with assigned user information
            \App\Models\CallDetails::where('caller_id_number', $number)
                ->where('call_status', 'ringing')
                ->where('call_source', 'tata_dialplan')
                ->latest()
                ->first()
                ?->update([
                    'lead_id' => $lead->id,
                    'executive_id' => $getUser->id,
                    'agent_number' => $getUser->mobile,
                    'agent_name' => $getUser->f_name . ' ' . $getUser->l_name,
                    'customer_name' => $lead->customer_name,
                    'lead_code' => $lead->formatted_id ?? '#' . $lead->id,
                    'call_for' => 'lead',
                    'call_notes' => 'Lead - assigned to available user'
                ]);
            
            $res = [[
                'transfer' => [
                    'type' => 'number',
                    'data' => [$getUser->mobile],
                    'ring_type' => 'order_by',
                    'skip_active' => true,
                ],
            ]];

            return response()->json($res, 200);
        } else {
            return response()->json([
                'status' => 'OK',
                'message' => 'No need to forward the call',
            ], 200);
        }
    }

    // No existing record found - Create new Lead and assign to available user
    Log::info('⚠️ DIALPLAN: No existing record found, creating new Lead', [
        'caller_number' => $number,
        'tried_formats' => [
            'with_91' => $number,
            'without_91' => $numberWithout91
        ],
        'searched_in' => ['JobRequest', 'OperationLead', 'Lead']
    ]);
    
    $getUser = UserAssignment::getAssigningUser(2, null, 'ivr');
    if ($getUser) {
        $newLead = Lead::create([
            'date' => now(),
            'executive' => $getUser->id,
            'contact_no' => $numberWith91, // Use with 91 prefix for consistency
            'lead_source' => 'ivrs',
            'status' => 'follow-up',
            'stage' => 'active',
        ]);
        
        Log::info('✅ DIALPLAN: New Lead created and assigned', [
            'lead_id' => $newLead->id,
            'formatted_id' => $newLead->formatted_id,
            'executive_id' => $getUser->id,
            'executive_mobile' => $getUser->mobile,
            'contact_no' => $numberWith91
        ]);

        // Update call record with new lead and assigned user information
        \App\Models\CallDetails::where('caller_id_number', $number)
            ->where('call_status', 'ringing')
            ->where('call_source', 'tata_dialplan')
            ->latest()
            ->first()
            ?->update([
                'lead_id' => $newLead->id,
                'executive_id' => $getUser->id,
                'agent_number' => $getUser->mobile,
                'agent_name' => $getUser->f_name . ' ' . $getUser->l_name,
                'customer_name' => null, // New lead, no customer name yet
                'lead_code' => $newLead->formatted_id ?? '#' . $newLead->id,
                'call_for' => 'lead',
                'call_notes' => 'New Lead created and assigned to available user'
            ]);

        $res = [[
            'transfer' => [
                'type' => 'number',
                'data' => [$getUser->mobile],
                'ring_type' => 'order_by',
                'skip_active' => true,
            ],
        ]];

        return response()->json($res, 200);
    }

    $newLead = Lead::create([
        'date' => now(),
        'contact_no' => $numberWith91, // Use with 91 prefix for consistency
        'lead_source' => 'ivrs',
        'status' => 'follow-up',
        'stage' => 'active',
    ]);

    // Don't give any response
    return response()->json([
        'status' => 'OK',
        'message' => 'No need to forward the call',
    ], 200);
});

// app apis
Route::post('/login', [AuthController::class, 'login']);
Route::middleware('auth:sanctum')->get('/user-info', [AuthController::class, 'userInfo']);
Route::middleware('auth:sanctum')->post('/user/expo-token', [AuthController::class, 'saveExpoToken']);
Route::middleware('auth:sanctum')->post('/send-expo-notification', [AuthController::class, 'sendExpoNotification']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/chat/users', [ChatController::class, 'getUsers']);
    Route::get('/chat/messages/{userId}', [ChatController::class, 'getMessages']);
    Route::post('/chat/send', [ChatController::class, 'sendMessage']);
    Route::post('/chat/mark-read/{userId}', [ChatController::class, 'markAsRead']);
    Route::get('/chat/unread-counts', [ChatController::class, 'getUnreadCounts']);
    Route::post('/chat/favorite/{userId}', [ChatController::class, 'toggleFavorite']);
    Route::get('/chat/favorites', [ChatController::class, 'getFavorites']);
    Route::post('/chat/send-attachment', [ChatController::class, 'sendAttachment']);
    Route::get('/group-chat/groups', [GroupChatController::class, 'listGroups']);
    Route::get('/group-chat/messages/{groupId}', [GroupChatController::class, 'getMessages']);
    Route::post('/group-chat/create', [GroupChatController::class, 'createGroup']);
    Route::post('/group-chat/send/{groupId}', [GroupChatController::class, 'sendMessage']);
    Route::post('/group-chat/add-user/{groupId}', [GroupChatController::class, 'addUser']);
    Route::post('/group-chat/remove-user/{groupId}', [GroupChatController::class, 'removeUser']);
    Route::delete('/group-chat/delete/{groupId}', [GroupChatController::class, 'deleteGroup']);
    Route::post('group-chat/send-attachment/{group_id}', [GroupChatController::class, 'sendAttachment']);
    Route::post('/group-chat/mark-read/{groupId}', [GroupChatController::class, 'markGroupMessagesAsRead']);
    Route::get('/all-whatsapp-chats/numbers', [WhatsappMsgController::class, 'get_all_whatsapp_numbers']);
    Route::get('/all-whatsapp-chats/messages/{number}', [WhatsappMsgController::class, 'get_messages_for_number']);
    Route::post('/all-whatsapp-chats/mark-read/{number}', [WhatsappMsgController::class, 'markMessagesAsRead']);
    Route::post('/whatsapp-chat/send', [WhatsappMsgController::class, 'whatsapp_msg_send']);
    Route::get('/all-users', [UserController::class, 'allUsers']);
    Route::post('/whatsapp-group/add-executive', [App\Http\Controllers\WhatsappMsgController::class, 'addExecutiveToWhatsappGroup']);
    Route::post('/whatsapp-group/remove-executive', [WhatsappMsgController::class, 'removeExecutiveFromWhatsappGroup']);
    Route::get('/admin/all-whatsapp-chats/executives/{number}', [WhatsappMsgController::class, 'get_executives_for_number']);

    // Staff Chat API Routes
    Route::get('/admin/staff-chats', [App\Http\Controllers\Admin\StaffChatController::class, 'index']);
    Route::get('/admin/staff-chats/messages/{salesId}/{participantId}', [App\Http\Controllers\Admin\StaffChatController::class, 'getMessages']);
    Route::post('/admin/staff-chats/send', [App\Http\Controllers\Admin\StaffChatController::class, 'sendMessage']);
    Route::post('/admin/staff-chats/mark-read/{userId}', [App\Http\Controllers\Admin\StaffChatController::class, 'markAsRead']);
    Route::get('/admin/staff-chats/unread-counts', [App\Http\Controllers\Admin\StaffChatController::class, 'getUnreadCounts']);

    // Leads API Routes
    Route::get('/admin/leads', [App\Http\Controllers\Admin\LeadController::class, 'index']);
    Route::get('/admin/leads/executives', [App\Http\Controllers\Admin\LeadController::class, 'getExecutives']);
    Route::get('/admin/leads/locations', [App\Http\Controllers\Admin\LeadController::class, 'getLocations']);
    Route::post('/admin/leads', [App\Http\Controllers\Admin\LeadController::class, 'store']);
    Route::get('/admin/leads/{id}', [App\Http\Controllers\Admin\LeadController::class, 'show']);
    Route::put('/admin/leads/{id}', [App\Http\Controllers\Admin\LeadController::class, 'update']);
    Route::delete('/admin/leads/{id}', [App\Http\Controllers\Admin\LeadController::class, 'destroy']);

    // Users API Routes
    Route::get('/admin/users', [App\Http\Controllers\Admin\UserController::class, 'index']);
    Route::get('/admin/users/roles', [App\Http\Controllers\Admin\UserController::class, 'getRoles']);
    Route::get('/admin/users/locations', [App\Http\Controllers\Admin\UserController::class, 'getLocations']);
    Route::get('/admin/users/parent-users', [App\Http\Controllers\Admin\UserController::class, 'getParentUsers']);
    Route::get('/admin/users/lead-types', [App\Http\Controllers\Admin\UserController::class, 'getLeadTypes']);
    Route::post('/admin/users', [App\Http\Controllers\Admin\UserController::class, 'store']);
    Route::get('/admin/users/{id}', [App\Http\Controllers\Admin\UserController::class, 'show']);
    Route::put('/admin/users/{id}', [App\Http\Controllers\Admin\UserController::class, 'update']);
    Route::delete('/admin/users/{id}', [App\Http\Controllers\Admin\UserController::class, 'destroy']);
    Route::put('/admin/users/{id}/duty', [App\Http\Controllers\Admin\UserController::class, 'updateDutyStatus']);
    Route::put('/admin/users/{id}/break', [App\Http\Controllers\Admin\UserController::class, 'updateBreakStatus']);

    // Break Logs API Routes
    Route::get('/admin/break-logs', [App\Http\Controllers\Admin\BreakLogController::class, 'index']);
    Route::get('/admin/break-logs/user/{id}', [App\Http\Controllers\Admin\BreakLogController::class, 'userLogs']);

    // Duty Logs API Routes
    Route::get('/admin/duty-logs', [App\Http\Controllers\Admin\DutyLogController::class, 'index']);
    Route::get('/admin/duty-logs/user/{id}', [App\Http\Controllers\Admin\DutyLogController::class, 'userLogs']);

    // Operation Leads API Routes
    Route::get('/admin/test', function () {
        return response()->json(['message' => 'Admin API working']);
    });
    Route::get('/admin/operation-leads', [App\Http\Controllers\Admin\OperationLeadController::class, 'index']);
    Route::post('/admin/operation-leads', [App\Http\Controllers\Admin\OperationLeadController::class, 'store']);
    Route::get('/admin/operation-leads/{id}', [App\Http\Controllers\Admin\OperationLeadController::class, 'show']);
    Route::get('/admin/operation-leads/{id}/edit', [App\Http\Controllers\Admin\OperationLeadController::class, 'edit']);
    Route::put('/admin/operation-leads/{id}', [App\Http\Controllers\Admin\OperationLeadController::class, 'update']);
    Route::delete('/admin/operation-leads/{id}', [App\Http\Controllers\Admin\OperationLeadController::class, 'destroy']);

    // Admin Payment Details Routes
    Route::post('/admin/operation-leads/{lead}/payment', [App\Http\Controllers\Admin\OperationLeadController::class, 'storePaymentDetail']);
    Route::get('/admin/operation-leads/payment/{id}/edit', [App\Http\Controllers\Admin\OperationLeadController::class, 'editPaymentDetail']);
    Route::put('/admin/operation-leads/payment/{id}', [App\Http\Controllers\Admin\OperationLeadController::class, 'updatePaymentDetail']);
    Route::post('/admin/operation-leads/payment/{id}/update', [App\Http\Controllers\Admin\OperationLeadController::class, 'updatePaymentDetail']);
    Route::delete('/admin/operation-leads/payment/{id}', [App\Http\Controllers\Admin\OperationLeadController::class, 'destroyPaymentDetail']);

    // Admin Deployment Details Routes
    Route::post('/admin/operation-leads/{lead}/deployment', [App\Http\Controllers\Admin\OperationLeadController::class, 'storeDeploymentDetail']);
    Route::get('/admin/operation-leads/deployment/{id}/edit', [App\Http\Controllers\Admin\OperationLeadController::class, 'editDeploymentDetail']);
    Route::put('/admin/operation-leads/deployment/{id}', [App\Http\Controllers\Admin\OperationLeadController::class, 'updateDeploymentDetail']);
    Route::delete('/admin/operation-leads/deployment/{id}', [App\Http\Controllers\Admin\OperationLeadController::class, 'destroyDeploymentDetail']);

    // Admin Executives and Vendors Routes
    Route::get('/admin/executives', [App\Http\Controllers\Admin\OperationLeadController::class, 'getExecutives']);

    // Vendors API Routes
    Route::get('/admin/vendors', [App\Http\Controllers\Admin\VendorController::class, 'index']);
    Route::post('/admin/vendors', [App\Http\Controllers\Admin\VendorController::class, 'store']);
    Route::get('/admin/vendors/{id}/edit', [App\Http\Controllers\Admin\VendorController::class, 'edit']);
    Route::put('/admin/vendors/{id}', [App\Http\Controllers\Admin\VendorController::class, 'update']);
    Route::delete('/admin/vendors/{id}', [App\Http\Controllers\Admin\VendorController::class, 'destroy']);

    // Job Requests API Routes
    Route::get('/admin/jobproc', [App\Http\Controllers\Admin\JobProcController::class, 'getJobRequests']);
    Route::post('/admin/jobproc', [App\Http\Controllers\Admin\JobProcController::class, 'store']);
    Route::get('/admin/jobproc/{id}', [App\Http\Controllers\Admin\JobProcController::class, 'show']);
    Route::get('/admin/jobproc/{id}/edit', [App\Http\Controllers\Admin\JobProcController::class, 'edit']);
    Route::put('/admin/jobproc/{id}', [App\Http\Controllers\Admin\JobProcController::class, 'update']);
    Route::delete('/admin/jobproc/{id}', [App\Http\Controllers\Admin\JobProcController::class, 'destroy']);

    // Sales Leads API Routes
    Route::get('/sales/leads', [App\Http\Controllers\Sales\LeadController::class, 'getLeads']);
    Route::post('/sales/leads', [App\Http\Controllers\Sales\LeadController::class, 'store']);
    Route::get('/sales/leads/{id}/edit', [App\Http\Controllers\Sales\LeadController::class, 'edit']);
    Route::put('/sales/leads/{id}', [App\Http\Controllers\Sales\LeadController::class, 'update']);
    Route::get('/sales/leads/{id}', [App\Http\Controllers\Sales\LeadController::class, 'show']);

    // Manager Leads API Routes
    Route::get('/manager/leads', [App\Http\Controllers\Manager\LeadController::class, 'getLeads']);
    Route::get('/manager/leads/app', [App\Http\Controllers\Manager\LeadController::class, 'getLeadsForApp']);
    Route::post('/manager/leads', [App\Http\Controllers\Manager\LeadController::class, 'store']);
    Route::get('/manager/leads/executives', [App\Http\Controllers\Manager\LeadController::class, 'getExecutives']);
    Route::get('/manager/leads/locations', [App\Http\Controllers\Manager\LeadController::class, 'getLocations']);
    Route::get('/manager/leads/{id}/edit', [App\Http\Controllers\Manager\LeadController::class, 'edit']);
    Route::put('/manager/leads/{id}', [App\Http\Controllers\Manager\LeadController::class, 'update']);
    Route::get('/manager/leads/{id}', [App\Http\Controllers\Manager\LeadController::class, 'show']);

    // Manager Staff Chats API Routes
    Route::get('/manager/staff/chats', [App\Http\Controllers\Manager\StaffChatController::class, 'index']);
    Route::get('/manager/staff/chats/messages/{salesId}/{participantId}', [App\Http\Controllers\Manager\StaffChatController::class, 'getMessages']);
    Route::post('/manager/staff/chats/send', [App\Http\Controllers\Manager\StaffChatController::class, 'sendMessage']);
    Route::post('/manager/staff/chats/mark-read/{userId}', [App\Http\Controllers\Manager\StaffChatController::class, 'markAsRead']);
    Route::get('/manager/staff/chats/unread-counts', [App\Http\Controllers\Manager\StaffChatController::class, 'getUnreadCounts']);

    // Manager Duty Logs API Routes
    Route::get('/manager/duty-logs', [App\Http\Controllers\Manager\DutyLogController::class, 'index']);
    Route::get('/manager/duty-logs/user/{id}', [App\Http\Controllers\Manager\DutyLogController::class, 'userLogs']);
    Route::get('/manager/duty-logs/test', [App\Http\Controllers\Manager\DutyLogController::class, 'test']);
    Route::post('/manager/duty-logs/create-sample', [App\Http\Controllers\Manager\DutyLogController::class, 'createSampleData']);

    // Manager Break Logs API Routes
    Route::get('/manager/break-logs', [App\Http\Controllers\Manager\BreakLogController::class, 'index']);
    Route::get('/manager/break-logs/user/{id}', [App\Http\Controllers\Manager\BreakLogController::class, 'userLogs']);
    Route::get('/manager/break-logs/test', [App\Http\Controllers\Manager\BreakLogController::class, 'test']);
    Route::post('/manager/break-logs/create-sample', [App\Http\Controllers\Manager\BreakLogController::class, 'createSampleData']);

    // Operation Leads API Routes
    Route::get('/operation/operation-leads', [App\Http\Controllers\Operation\OperationLeadController::class, 'getLeadsForApp']);
    Route::get('/operation/operation-leads/{id}', [App\Http\Controllers\Operation\OperationLeadController::class, 'show']);
    Route::post('/operation/operation-leads', [App\Http\Controllers\Operation\OperationLeadController::class, 'store']);
    Route::put('/operation/operation-leads/{id}', [App\Http\Controllers\Operation\OperationLeadController::class, 'update']);
    Route::delete('/operation/operation-leads/{id}', [App\Http\Controllers\Operation\OperationLeadController::class, 'destroy']);
    Route::post('/operation/operation-leads/{lead}/payment', [App\Http\Controllers\Operation\OperationLeadController::class, 'storePaymentDetail']);
    Route::put('/operation/operation-leads/payment/{id}', [App\Http\Controllers\Operation\OperationLeadController::class, 'updatePaymentDetail']);

    // Deployment Details API Routes
    Route::post('/operation/operation-leads/{lead}/deployment', [App\Http\Controllers\Operation\OperationLeadController::class, 'storeDeploymentDetail']);
    Route::get('/operation/operation-leads/deployment/{id}/edit', [App\Http\Controllers\Operation\OperationLeadController::class, 'editDeploymentDetail']);
    Route::put('/operation/operation-leads/deployment/{id}', [App\Http\Controllers\Operation\OperationLeadController::class, 'updateDeploymentDetail']);
    Route::delete('/operation/operation-leads/deployment/{id}', [App\Http\Controllers\Operation\OperationLeadController::class, 'destroyDeploymentDetail']);

    // Job Requests API Routes
    Route::get('/operation/jobproc/jobrequests', [App\Http\Controllers\Operation\JobProcController::class, 'getJobRequestsForApp']);
    Route::get('/operation/jobproc/all-jobrequests', [App\Http\Controllers\Operation\JobProcController::class, 'getAllJobRequestsForApp']);
    Route::post('/operation/jobproc', [App\Http\Controllers\Operation\JobProcController::class, 'store']);
    Route::get('/operation/jobproc/{id}', [App\Http\Controllers\Operation\JobProcController::class, 'show']);
    Route::get('/operation/jobproc/{id}/edit', [App\Http\Controllers\Operation\JobProcController::class, 'edit']);
    Route::put('/operation/jobproc/{id}', [App\Http\Controllers\Operation\JobProcController::class, 'update']);
    Route::delete('/operation/jobproc/{id}', [App\Http\Controllers\Operation\JobProcController::class, 'destroy']);

    // Locations API Route
    Route::get('/locations', function () {
        return response()->json(\App\Models\Location::all());
    });

    // Duty and Break Status API Routes
    Route::get('/duty_active/{userId}/{status}/{reason?}', [\App\Http\Controllers\Operation\UserStatusController::class, 'updateDutyStatus']);
    Route::get('/break_active/{userId}/{status}/{reason?}', [\App\Http\Controllers\Operation\UserStatusController::class, 'updateBreakStatus']);

    // Manager Users API Routes
    Route::get('/manager/users', [App\Http\Controllers\Manager\UserController::class, 'getUsersForApp']);
    Route::post('/manager/users/duty-toggle/{id}', [App\Http\Controllers\Manager\UserController::class, 'toggleDutyStatus']);
    Route::post('/manager/users/break-toggle/{id}', [App\Http\Controllers\Manager\UserController::class, 'toggleBreakStatus']);

    // Operation Manager Leads API Routes
    Route::get('/operation-manager/operation-leads', [App\Http\Controllers\OperationManager\OperationLeadController::class, 'getLeadsForApp']);
    Route::get('/operation-manager/operation-leads/{id}', [App\Http\Controllers\OperationManager\OperationLeadController::class, 'show']);
    Route::post('/operation-manager/operation-leads', [App\Http\Controllers\OperationManager\OperationLeadController::class, 'store']);
    Route::put('/operation-manager/operation-leads/{id}', [App\Http\Controllers\OperationManager\OperationLeadController::class, 'update']);
    Route::delete('/operation-manager/operation-leads/{id}', [App\Http\Controllers\OperationManager\OperationLeadController::class, 'destroy']);
    Route::post('/operation-manager/operation-leads/{lead}/payment', [App\Http\Controllers\OperationManager\OperationLeadController::class, 'storePaymentDetail']);
    Route::put('/operation-manager/operation-leads/payment/{id}', [App\Http\Controllers\OperationManager\OperationLeadController::class, 'updatePaymentDetail']);
    Route::post('/operation-manager/operation-leads/{lead}/deployment', [App\Http\Controllers\OperationManager\OperationLeadController::class, 'storeDeploymentDetail']);
    Route::get('/operation-manager/operation-leads/deployment/{id}/edit', [App\Http\Controllers\OperationManager\OperationLeadController::class, 'editDeploymentDetail']);
    Route::put('/operation-manager/operation-leads/deployment/{id}', [App\Http\Controllers\OperationManager\OperationLeadController::class, 'updateDeploymentDetail']);
    Route::delete('/operation-manager/operation-leads/deployment/{id}', [App\Http\Controllers\OperationManager\OperationLeadController::class, 'destroyDeploymentDetail']);
    Route::get('/operation-manager/executives', [App\Http\Controllers\OperationManager\OperationLeadController::class, 'getExecutives']);
    Route::get('/operation-manager/vendors', [App\Http\Controllers\OperationManager\OperationLeadController::class, 'getVendors']);

    // Operation Manager Job Requests API Routes
    Route::get('/operation-manager/jobproc/jobrequests', [App\Http\Controllers\OperationManager\JobProcController::class, 'getJobRequests']);
    Route::post('/operation-manager/jobproc', [App\Http\Controllers\OperationManager\JobProcController::class, 'store']);
    Route::get('/operation-manager/jobproc/{id}', [App\Http\Controllers\OperationManager\JobProcController::class, 'show']);
    Route::get('/operation-manager/jobproc/{id}/edit', [App\Http\Controllers\OperationManager\JobProcController::class, 'edit']);
    Route::put('/operation-manager/jobproc/{id}', [App\Http\Controllers\OperationManager\JobProcController::class, 'update']);
    Route::delete('/operation-manager/jobproc/{id}', [App\Http\Controllers\OperationManager\JobProcController::class, 'destroy']);

    // Operation Manager Coordinator Chats API Routes
    Route::get('/operation-manager/coordinator-chats', [App\Http\Controllers\OperationManager\CoordinatorChatController::class, 'getCoordinatorsForApp']);
    Route::get('/operation-manager/coordinator-chats/messages/{coordinatorId}/{participantId}', [App\Http\Controllers\OperationManager\CoordinatorChatController::class, 'getMessages']);
    Route::post('/operation-manager/coordinator-chats/send', [App\Http\Controllers\OperationManager\CoordinatorChatController::class, 'sendMessage']);
    Route::post('/operation-manager/coordinator-chats/mark-read/{userId}', [App\Http\Controllers\OperationManager\CoordinatorChatController::class, 'markAsRead']);
    Route::get('/operation-manager/coordinator-chats/unread-counts', [App\Http\Controllers\OperationManager\CoordinatorChatController::class, 'getUnreadCounts']);

    // Operation Manager Team/Users API Routes
    Route::get('/operation-manager/users', [App\Http\Controllers\OperationManager\UserController::class, 'getUsersForApp']);
    Route::get('/operation-manager/roles', [App\Http\Controllers\OperationManager\UserController::class, 'getRoles']);

    // Operation Manager Duty/Break Logs API Routes
    Route::get('/operation-manager/duty-logs', [App\Http\Controllers\OperationManager\DutyLogController::class, 'getDutyLogsForApp']);
    Route::get('/operation-manager/break-logs', [App\Http\Controllers\OperationManager\BreakLogController::class, 'getBreakLogsForApp']);
    Route::get('/operation-manager/duty-logs/test', [App\Http\Controllers\OperationManager\DutyLogController::class, 'testBasicModel']);
});

Route::prefix('whatsapp-template')->group(function () {
    Route::post('/reconnect-greeting', [App\Http\Controllers\WhatsappTemplateMsgController::class, 'whatsapp_template_msg_send_reconnect_greeting']);
    Route::post('/post-call-followup-healthcare', [App\Http\Controllers\WhatsappTemplateMsgController::class, 'whatsapp_template_msg_send_post_call_followup_healthcare']);
    Route::post('/payment-followup-healthcare', [App\Http\Controllers\WhatsappTemplateMsgController::class, 'whatsapp_template_msg_send_payment_followup_healthcare']);
    Route::post('/renewal-reminder-healthcare', [App\Http\Controllers\WhatsappTemplateMsgController::class, 'whatsapp_template_msg_send_renewal_reminder_healthcare']);
    Route::post('/morning-greeting-update', [App\Http\Controllers\WhatsappTemplateMsgController::class, 'whatsapp_template_msg_send_morning_greeting_update']);
    Route::post('/evening-greeting-update', [App\Http\Controllers\WhatsappTemplateMsgController::class, 'whatsapp_template_msg_send_evening_greeting_update']);
    Route::post('/basic-greeting-service-checkin', [App\Http\Controllers\WhatsappTemplateMsgController::class, 'whatsapp_template_msg_send_basic_greeting_service_checkin']);
    Route::post('/service-status-checkin', [App\Http\Controllers\WhatsappTemplateMsgController::class, 'whatsapp_template_msg_send_service_status_checkin']);
    Route::post('/job-request-followup', [App\Http\Controllers\WhatsappTemplateMsgController::class, 'whatsapp_template_msg_send_job_request_followup']);
    Route::post('/patient-care-details-request', [App\Http\Controllers\WhatsappTemplateMsgController::class, 'whatsapp_template_msg_send_patient_care_details_request']);
    Route::post('/payment-confirmation-healthcare', [App\Http\Controllers\WhatsappTemplateMsgController::class, 'whatsapp_template_msg_send_payment_confirmation_healthcare']);

});
