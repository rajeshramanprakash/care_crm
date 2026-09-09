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
use App\Services\ApiRelayUrlResolver;
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

// User Status Routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user/status', [\App\Http\Controllers\Api\UserStatusController::class, 'getStatus']);
    Route::post('/user/status/toggle-duty', [\App\Http\Controllers\Api\UserStatusController::class, 'toggleDuty']);
    Route::post('/user/status/toggle-break', [\App\Http\Controllers\Api\UserStatusController::class, 'toggleBreak']);
});

Route::post('/mcube/webhook', [MCubeController::class, 'handleCallWebhook']);
Route::post('/leegality/webhook', App\Http\Controllers\Api\LeegalityWebhookController::class);

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
                $statusMessage = \App\Models\WhatsAppMessage::where('msg_id', $msgId)->first();
                if ($statusMessage) {
                    \App\Support\SafeBroadcast::toOthers(new \App\Events\WhatsappChatUpdated((string) $statusMessage->msg_from, 'status_update', [
                        'message_id' => $statusMessage->id,
                        'status' => $msgStatus,
                    ]));
                }
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
            $url = ApiRelayUrlResolver::resolve('https://rengage.mcube.com/api/wpbox/getMedia?message_id='.urlencode($msgId));
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
            \App\Support\SafeBroadcast::toOthers(new \App\Events\WhatsappChatUpdated((string) $phoneNumber, 'inbound_message', [
                'message_id' => $whatsappMessage->id,
            ]));
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
                        $url = ApiRelayUrlResolver::resolve('https://rengage.mcube.com/api/wpbox/sendtemplatemessage');
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
                        $url = ApiRelayUrlResolver::resolve('https://rengage.mcube.com/api/wpbox/sendmessage');
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

                // Send Expo push notification to ALL executives in the group AND Admins
                $group = WhatsappMsgGroup::where('whatsapp_number', $phoneNumber)->first();
                $ids = $group ? array_filter(explode(',', $group->executive_ids)) : [$getUser->id];
                
                // Add Admins (role_id = 1)
                $adminIds = User::where('role_id', 1)->pluck('id')->toArray();
                $allIds = array_unique(array_merge($ids, $adminIds));

                ExpoNotificationService::send(
                    $allIds,
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
            
            // Send Expo push notification to ALL executives in the group for existing lead AND Admins
            try {
                $group = WhatsappMsgGroup::where('whatsapp_number', $phoneNumber)->first();
                $ids = $group ? array_filter(explode(',', $group->executive_ids)) : [];
                
                // Add Admins (role_id = 1)
                $adminIds = User::where('role_id', 1)->pluck('id')->toArray();
                $allIds = array_unique(array_merge($ids, $adminIds));

                if (!empty($allIds)) {
                    ExpoNotificationService::send(
                        $allIds,
                        'New WhatsApp Message',
                        'New message from Lead ID: ' . ($existingLead->formatted_id ?? $existingLead->id)
                    );
                }
            } catch (\Exception $e) {
                Log::error('Error sending notification for existing lead: ' . $e->getMessage());
            }

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
                $url = ApiRelayUrlResolver::resolve('https://rengage.mcube.com/api/wpbox/sendtemplatemessage');
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
                $url = ApiRelayUrlResolver::resolve('https://rengage.mcube.com/api/wpbox/sendmessage');
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
                    \App\Models\CallDetails::findTataDialplanRingingForUpdate($data['call_id'] ?? null, $number)
                        ?->update([
                            'executive_id' => $operationExecutiveUser->id,
                            'agent_number' => $operationExecutiveUser->mobile,
                            'agent_name' => $operationExecutiveUser->f_name . ' ' . $operationExecutiveUser->l_name,
                            'customer_name' => $jobRequest->customer_name,
                            'lead_code' => 'JR' . str_pad($jobRequest->id, 8, '0', STR_PAD_LEFT),
                            'call_for' => 'job_request',
                            'call_notes' => 'JobRequest with active deployment - transferred to Operation Executive'
                        ]);
                    Log::info($res);
                    $res = [[
                        'transfer' => [
                            'type' => 'number',
                            'data' => [$operationExecutiveUser->mobile],
                            'ring_type' => 'order_by',
                            'skip_active' => true,
                        ],
                    ]];
                    Log::info($res);

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
            \App\Models\CallDetails::findTataDialplanRingingForUpdate($data['call_id'] ?? null, $number)
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
            Log::info($res);

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
            \App\Models\CallDetails::findTataDialplanRingingForUpdate($data['call_id'] ?? null, $number)
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
            
            Log::info($res);

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
            \App\Models\CallDetails::findTataDialplanRingingForUpdate($data['call_id'] ?? null, $number)
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
            Log::info($res);

            return response()->json($res, 200);
        }

        $getUser = UserAssignment::getAssigningUser(2, null, 'ivr');
        if ($getUser && $getUser->mobile) {
            // Update call record with assigned user information
            \App\Models\CallDetails::findTataDialplanRingingForUpdate($data['call_id'] ?? null, $number)
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
            Log::info($res);

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
        \App\Models\CallDetails::findTataDialplanRingingForUpdate($data['call_id'] ?? null, $number)
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
        Log::info($res);

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
// Some production proxies may incorrectly pass this as GET; allow both to keep OTP flow working.
Route::match(['GET', 'POST'], '/send-otp', [AuthController::class, 'sendOtp']);
Route::post('/portal/insurer/login', [App\Http\Controllers\Api\PortalAuthApiController::class, 'loginInsurer']);
Route::post('/portal/broker/login', [App\Http\Controllers\Api\PortalAuthApiController::class, 'loginBroker']);
Route::post('/portal/corporate-employee/login', [App\Http\Controllers\Api\PortalAuthApiController::class, 'loginCorporateEmployee']);
Route::post('/register', [App\Http\Controllers\Auth\RegisterController::class, 'register']);

// Public registration helpers (CareApp + web register forms)
Route::get('/public/register/languages', [App\Http\Controllers\Auth\RegisterController::class, 'registrationLanguages']);
Route::get('/public/register/translations/{code}', [App\Http\Controllers\Auth\RegisterController::class, 'registrationTranslations']);
Route::get('/public/register/location-provider-services', [App\Http\Controllers\Auth\RegisterController::class, 'locationProviderServices']);
Route::post('/public/register/doctor/send-mobile-otp', [App\Http\Controllers\Auth\RegisterController::class, 'sendDoctorMobileOtp'])->middleware('throttle:10,1');
Route::post('/public/register/doctor/verify-mobile-otp', [App\Http\Controllers\Auth\RegisterController::class, 'verifyDoctorMobileOtp'])->middleware('throttle:20,1');
Route::post('/public/register/vendor/send-mobile-otp', [App\Http\Controllers\Auth\RegisterController::class, 'sendVendorMobileOtp'])->middleware('throttle:10,1');
Route::post('/public/register/vendor/verify-mobile-otp', [App\Http\Controllers\Auth\RegisterController::class, 'verifyVendorMobileOtp'])->middleware('throttle:20,1');
Route::post('/public/register/freelancer/send-mobile-otp', [App\Http\Controllers\Auth\RegisterController::class, 'sendFreelancerMobileOtp'])->middleware('throttle:10,1');
Route::post('/public/register/freelancer/verify-mobile-otp', [App\Http\Controllers\Auth\RegisterController::class, 'verifyFreelancerMobileOtp'])->middleware('throttle:20,1');

// Public endpoints for registration
Route::get('/public/locations', function () {
    return response()->json([
        'success' => true,
        'locations' => \App\Models\Location::orderBy('name', 'asc')->get()
    ]);
});

Route::get('/public/services', function () {
    return response()->json([
        'success' => true,
        'services' => \App\Models\Service::orderBy('name', 'asc')->get()
    ]);
});

/** Doctor registration dropdown + CareWeb consultation filters (includes sub-services). */
Route::get('/public/doctor-consultation-services', function () {
    $services = \App\Models\DoctorConsultationService::query()
        ->where('is_active', true)
        ->with(['subServices' => fn ($q) => $q->where('is_active', true)->orderBy('sort_order')->orderBy('name')])
        ->orderBy('sort_order')
        ->orderBy('name')
        ->get();

    $payload = $services->map(function ($svc) {
        $parentTags = is_array($svc->specialization_options)
            ? array_values(array_filter(array_map('strval', $svc->specialization_options)))
            : [];

        return [
            'id' => (int) $svc->id,
            'name' => (string) $svc->name,
            'category' => (string) ($svc->category ?? ''),
            'icon_path' => $svc->icon_path,
            'consultation_duration_minutes' => $svc->consultation_duration_minutes,
            'sort_order' => (int) ($svc->sort_order ?? 0),
            'tags' => $parentTags,
            'sub_services' => $svc->subServices->map(fn ($sub) => [
                'id' => (int) $sub->id,
                'name' => (string) $sub->name,
                'tags' => is_array($sub->specialization_options)
                    ? array_values(array_filter(array_map('strval', $sub->specialization_options)))
                    : [],
            ])->values()->all(),
        ];
    })->values();

    return response()->json([
        'success' => true,
        'services' => $payload,
    ]);
});

Route::post('/public/doctor-registration/generate-about', [App\Http\Controllers\Api\DoctorRegistrationAssistController::class, 'generateAbout'])
    ->middleware('throttle:20,1');

Route::get('/public/doctor-registration/pricing', [App\Http\Controllers\Api\PublicDoctorRegistrationPricingController::class, 'show'])
    ->middleware('throttle:120,1');

Route::get('/public/google-maps-config', [App\Http\Controllers\Api\PublicConsultationWebsiteController::class, 'googleMapsConfig']);
Route::get('/public/address-autocomplete', [App\Http\Controllers\Api\PublicConsultationWebsiteController::class, 'addressAutocomplete'])
    ->middleware('throttle:60,1');
Route::get('/public/address-resolve', [App\Http\Controllers\Api\PublicConsultationWebsiteController::class, 'addressResolve'])
    ->middleware('throttle:40,1');
Route::get('/public/reverse-geocode', [App\Http\Controllers\Api\PublicConsultationWebsiteController::class, 'reverseGeocode'])
    ->middleware('throttle:40,1');
Route::get('/public/consultation-doctors/{serviceId}', [App\Http\Controllers\Api\PublicConsultationWebsiteController::class, 'doctors'])
    ->whereNumber('serviceId');
Route::get('/public/consultation-doctor-cities', [App\Http\Controllers\Api\PublicConsultationWebsiteController::class, 'doctorCities']);
Route::get('/public/consultation-search-index', [App\Http\Controllers\Api\PublicConsultationWebsiteController::class, 'searchIndex']);
Route::get('/public/operation-exec-contact', [App\Http\Controllers\Api\PublicConsultationWebsiteController::class, 'operationExecContact']);

Route::post('/public/consultation-booking', [App\Http\Controllers\Api\PublicConsultationBookingController::class, 'store'])
    ->middleware('throttle:40,1');
Route::post('/public/consultation-booking/easebuzz-init', [App\Http\Controllers\Api\PublicConsultationBookingController::class, 'easebuzzInit'])
    ->middleware('throttle:40,1');
Route::get('/public/consultation-booking/payment-poll/{txnid}', [App\Http\Controllers\Api\PublicConsultationBookingController::class, 'paymentPoll'])
    ->middleware('throttle:120,1')
    ->where('txnid', '[A-Za-z0-9]+');
Route::match(['get', 'post'], '/public/consultation-booking/easebuzz-return', [App\Http\Controllers\Api\PublicConsultationBookingController::class, 'easebuzzReturn'])
    ->middleware('throttle:120,1');

Route::prefix('/public/admin')
    ->middleware(['careweb.key', 'throttle:120,1'])
    ->group(function () {
        Route::get('/verified-doctors', [App\Http\Controllers\Api\AdminWebsiteVerifiedDoctorController::class, 'index']);
        Route::get('/verified-doctors/{id}', [App\Http\Controllers\Api\AdminWebsiteVerifiedDoctorController::class, 'show'])->whereNumber('id');
        Route::post('/verified-doctors/{id}/website-card', [App\Http\Controllers\Api\AdminWebsiteVerifiedDoctorController::class, 'updateWebsiteCard'])->whereNumber('id');
    });

Route::get('/public/doctor-booking-calendar/{doctorRequestId}', [App\Http\Controllers\Api\PublicDoctorBookingCalendarController::class, 'show'])
    ->whereNumber('doctorRequestId');
Route::get('/public/doctor-booking-times/{doctorRequestId}', [App\Http\Controllers\Api\PublicDoctorBookingCalendarController::class, 'times'])
    ->whereNumber('doctorRequestId');
Route::get('/public/b2b-leads-template', function () {
    $csv = "name,mobile,service_requirement\n";
    $csv .= "Rahul Sharma,9876543210,Doctor consultation\n";
    $csv .= "Anita Verma,9123456789,Nursing Care\n";
    return response($csv, 200, [
        'Content-Type' => 'text/csv',
        'Content-Disposition' => 'attachment; filename=\"b2b_leads_format.csv\"',
    ]);
});
Route::middleware('auth:sanctum')->get('/user-info', [AuthController::class, 'userInfo']);

// Customer API routes
Route::prefix('vendor')->middleware('auth:sanctum')->group(function () {
    Route::get('/dashboard', [App\Http\Controllers\Api\VendorController::class, 'dashboard']);
    Route::get('/personal-details', [App\Http\Controllers\Api\VendorController::class, 'personalDetails']);
    Route::get('/bank-details', [App\Http\Controllers\Api\VendorController::class, 'bankDetails']);
    Route::get('/assigned-leads', [App\Http\Controllers\Api\VendorController::class, 'assignedLeads']);
    Route::get('/payment-details', [App\Http\Controllers\Api\VendorController::class, 'paymentDetails']);
    Route::get('/chats', [App\Http\Controllers\Api\VendorController::class, 'chats']);
    Route::get('/chats/messages/{customerId}', [App\Http\Controllers\Api\VendorController::class, 'getMessages']);
    Route::post('/chats/send', [App\Http\Controllers\Api\VendorController::class, 'sendMessage']);
    Route::post('/chats/mark-read/{customerId}', [App\Http\Controllers\Api\VendorController::class, 'markAsRead']);
    Route::post('/personal-details/update-profile-image', [App\Http\Controllers\Api\VendorController::class, 'updateProfileImage']);
    Route::post('/personal-details/upload-document', [App\Http\Controllers\Api\VendorController::class, 'uploadDocument']);
    Route::post('/personal-details/price-change-request', [App\Http\Controllers\Api\VendorController::class, 'submitPriceChangeRequest']);
    Route::post('/attendance/location', [App\Http\Controllers\Api\VendorController::class, 'submitAttendanceLocation']);
    Route::post('/attendance/statuses', [App\Http\Controllers\Api\VendorController::class, 'attendanceStatuses']);
    Route::get('/statement', [App\Http\Controllers\Api\VendorController::class, 'statement']);
});

Route::prefix('freelancer')->middleware('auth:sanctum')->group(function () {
    Route::get('/dashboard', [App\Http\Controllers\Api\FreelancerController::class, 'dashboard']);
    Route::get('/personal-details', [App\Http\Controllers\Api\FreelancerController::class, 'personalDetails']);
    Route::get('/bank-details', [App\Http\Controllers\Api\FreelancerController::class, 'bankDetails']);
    Route::get('/assigned-leads', [App\Http\Controllers\Api\FreelancerController::class, 'assignedLeads']);
    Route::get('/payment-details', [App\Http\Controllers\Api\FreelancerController::class, 'paymentDetails']);
    Route::get('/chats', [App\Http\Controllers\Api\FreelancerController::class, 'chats']);
    Route::get('/chats/messages/{customerId}', [App\Http\Controllers\Api\FreelancerController::class, 'getMessages']);
    Route::post('/chats/send', [App\Http\Controllers\Api\FreelancerController::class, 'sendMessage']);
    Route::post('/chats/mark-read/{customerId}', [App\Http\Controllers\Api\FreelancerController::class, 'markAsRead']);
    Route::post('/personal-details/update-profile-image', [App\Http\Controllers\Api\FreelancerController::class, 'updateProfileImage']);
    Route::post('/personal-details/upload-document', [App\Http\Controllers\Api\FreelancerController::class, 'uploadDocument']);
    Route::post('/personal-details/update-field', [App\Http\Controllers\Api\FreelancerController::class, 'updatePersonalDetails']);
    Route::post('/personal-details/price-change-request', [App\Http\Controllers\Api\FreelancerController::class, 'submitPriceChangeRequest']);
    Route::post('/bank-details/update-field', [App\Http\Controllers\Api\FreelancerController::class, 'updateBankDetails']);
    Route::post('/bank-details/upload-document', [App\Http\Controllers\Api\FreelancerController::class, 'uploadBankDocument']);
    Route::post('/attendance/location', [App\Http\Controllers\Api\FreelancerController::class, 'submitAttendanceLocation']);
    Route::post('/attendance/statuses', [App\Http\Controllers\Api\FreelancerController::class, 'attendanceStatuses']);
    Route::get('/statement', [App\Http\Controllers\Api\FreelancerController::class, 'statement']);
});

Route::prefix('doctor-portal')->middleware('auth:sanctum')->group(function () {
    Route::get('/dashboard', [App\Http\Controllers\Api\DoctorPortalApiController::class, 'dashboard']);
    Route::get('/bookings', [App\Http\Controllers\Api\DoctorPortalApiController::class, 'bookings']);
    Route::get('/calendar-availability', [App\Http\Controllers\Api\DoctorCalendarAvailabilityApiController::class, 'index']);
    Route::post('/calendar-availability', [App\Http\Controllers\Api\DoctorCalendarAvailabilityApiController::class, 'store']);
    Route::delete('/calendar-availability/{slot}', [App\Http\Controllers\Api\DoctorCalendarAvailabilityApiController::class, 'destroy'])->whereNumber('slot');
    Route::post('/calendar-availability/delete-by-date', [App\Http\Controllers\Api\DoctorCalendarAvailabilityApiController::class, 'destroyByDate']);
    Route::get('/profile', [App\Http\Controllers\Api\DoctorPortalApiController::class, 'profile']);
    Route::get('/bank-details', [App\Http\Controllers\Api\DoctorPortalApiController::class, 'bankDetails']);
    Route::post('/personal-details/update-profile-image', [App\Http\Controllers\Api\DoctorPortalApiController::class, 'updateProfileImage']);
    Route::post('/personal-details/upload-document', [App\Http\Controllers\Api\DoctorPortalApiController::class, 'uploadDocument']);
    Route::post('/personal-details/update-field', [App\Http\Controllers\Api\DoctorPortalApiController::class, 'updatePersonalDetails']);
    Route::post('/personal-details/price-change-request', [App\Http\Controllers\Api\DoctorPortalApiController::class, 'submitPriceChangeRequest']);
    Route::post('/bank-details/update-field', [App\Http\Controllers\Api\DoctorPortalApiController::class, 'updateBankDetails']);
    Route::post('/bank-details/upload-document', [App\Http\Controllers\Api\DoctorPortalApiController::class, 'uploadBankDocument']);
    Route::get('/chats', [App\Http\Controllers\Api\DoctorPortalChatController::class, 'chats']);
    Route::get('/chats/messages/{customerId}', [App\Http\Controllers\Api\DoctorPortalChatController::class, 'getMessages'])->whereNumber('customerId');
    Route::post('/chats/send', [App\Http\Controllers\Api\DoctorPortalChatController::class, 'sendMessage']);
    Route::post('/chats/mark-read/{customerId}', [App\Http\Controllers\Api\DoctorPortalChatController::class, 'markAsRead'])->whereNumber('customerId');
    Route::post('/chats/translate', [App\Http\Controllers\Api\DoctorPortalChatController::class, 'translate']);
});

Route::prefix('customer')->middleware('auth:sanctum')->group(function () {
    Route::get('/dashboard', [App\Http\Controllers\Api\CustomerController::class, 'dashboard']);
    Route::get('/personal-details', [App\Http\Controllers\Api\CustomerController::class, 'personalDetails']);
    Route::post('/personal-details/update-profile-image', [App\Http\Controllers\Api\CustomerController::class, 'updateProfileImage']);
    Route::get('/chats', [App\Http\Controllers\Api\CustomerController::class, 'chats']);
    Route::get('/chats/messages/{chatType}/{chatId}', [App\Http\Controllers\Api\CustomerController::class, 'getMessages']);
    Route::post('/chats/send', [App\Http\Controllers\Api\CustomerController::class, 'sendMessage']);
    Route::post('/chats/mark-read/{chatType}/{chatId}', [App\Http\Controllers\Api\CustomerController::class, 'markAsRead']);
    Route::post('/chats/translate', [App\Http\Controllers\Api\CustomerController::class, 'translate']);
    Route::get('/payment-details', [App\Http\Controllers\Api\CustomerController::class, 'paymentDetails']);
    Route::post('/service-request/submit', [App\Http\Controllers\Api\CustomerController::class, 'submitServiceRequest']);
    Route::post('/attendance/location', [App\Http\Controllers\Api\CustomerController::class, 'submitAttendanceLocation']);
    Route::post('/attendance/location-attendance-enabled', [App\Http\Controllers\Api\CustomerController::class, 'setLocationAttendanceEnabled']);
    Route::get('/services', [App\Http\Controllers\Api\CustomerController::class, 'getServices']);
    Route::get('/locations', [App\Http\Controllers\Api\CustomerController::class, 'getLocations']);
});

// Screenshot route without auth middleware (handles auth in controller via token query param)
Route::get('/customer/payment-screenshot/{paymentId}', [App\Http\Controllers\Api\CustomerController::class, 'viewScreenshot']);
Route::get('/customer/payment-invoice/{id}', [App\Http\Controllers\Api\CustomerController::class, 'showPaymentInvoice'])
    ->whereNumber('id');

// Freelancer document route without auth middleware (handles auth in controller via token query param)
Route::get('/freelancer/document/{documentType}/{freelancerId}', [App\Http\Controllers\Api\FreelancerController::class, 'viewDocument']);
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
    Route::post('/chat/translate', [ChatController::class, 'translate']);
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

    // Admin Customer Chats API Routes
    Route::get('/admin/customer-chats', [App\Http\Controllers\Admin\CustomerChatController::class, 'indexApi']);
    Route::get('/admin/customer-chats/messages', [App\Http\Controllers\Admin\CustomerChatController::class, 'getMessages']);

    // Leads API Routes - order matters: specific routes before general ones
    Route::get('/admin/leads', [App\Http\Controllers\Admin\LeadController::class, 'index']);
    Route::post('/admin/leads', [App\Http\Controllers\Admin\LeadController::class, 'store']);
    Route::get('/admin/leads/executives', [App\Http\Controllers\Admin\LeadController::class, 'getExecutives']);
    Route::get('/admin/leads/locations', [App\Http\Controllers\Admin\LeadController::class, 'getLocations']);
    Route::get('/admin/leads/{id}/edit', [App\Http\Controllers\Admin\LeadController::class, 'edit']);
    Route::get('/admin/leads/{id}', [App\Http\Controllers\Admin\LeadController::class, 'show']);
    Route::put('/admin/leads/{id}', [App\Http\Controllers\Admin\LeadController::class, 'update']);
    Route::delete('/admin/leads/{id}', [App\Http\Controllers\Admin\LeadController::class, 'destroy']);

    // Services API Routes
    Route::get('/admin/services', function() {
        return \App\Models\Service::select('id', 'name')->get();
    });

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
    Route::post('/admin/users/{id}/create-agent', [App\Http\Controllers\Admin\UserController::class, 'createAgent']);
    Route::delete('/admin/users/{id}/delete-agent', [App\Http\Controllers\Admin\UserController::class, 'deleteAgent']);

    // B2B users (admin app)
    Route::get('/admin/b2b-users', [App\Http\Controllers\Admin\B2BUserController::class, 'apiIndex']);
    Route::post('/admin/b2b-users', [App\Http\Controllers\Admin\B2BUserController::class, 'apiStore']);
    Route::put('/admin/b2b-users/{b2bUser}', [App\Http\Controllers\Admin\B2BUserController::class, 'apiUpdate']);
    Route::post('/admin/b2b-users/{b2bUser}', [App\Http\Controllers\Admin\B2BUserController::class, 'apiUpdate']);
    Route::delete('/admin/b2b-users/{b2bUser}', [App\Http\Controllers\Admin\B2BUserController::class, 'apiDestroy']);
    Route::post('/admin/b2b-reference-users', [App\Http\Controllers\Admin\B2BReferenceUserController::class, 'apiStore']);
    Route::delete('/admin/b2b-reference-users/{b2bReferenceUser}', [App\Http\Controllers\Admin\B2BReferenceUserController::class, 'apiDestroy']);

    // B2B app APIs
    Route::get('/b2b/dashboard', [App\Http\Controllers\Api\B2BLeadApiController::class, 'dashboard']);
    Route::get('/b2b/leads/template', [App\Http\Controllers\Api\B2BLeadApiController::class, 'downloadTemplate']);
    Route::get('/b2b/leads', [App\Http\Controllers\Api\B2BLeadApiController::class, 'index']);
    Route::get('/b2b/leads/{id}', [App\Http\Controllers\Api\B2BLeadApiController::class, 'show'])->whereNumber('id');
    Route::post('/b2b/leads', [App\Http\Controllers\Api\B2BLeadApiController::class, 'store']);
    Route::post('/b2b/leads/import', [App\Http\Controllers\Api\B2BLeadApiController::class, 'import']);
    Route::get('/b2b/service-options', [App\Http\Controllers\Api\B2BLeadApiController::class, 'serviceOptions']);
    $b2bCorpPartnerChat = App\Http\Controllers\Api\B2BCorporateChatApiController::class;
    Route::get('/b2b/corporate-chat/access', [$b2bCorpPartnerChat, 'access']);
    Route::get('/b2b/corporate-chat', [$b2bCorpPartnerChat, 'index']);
    Route::get('/b2b/corporate-chat/messages/group', [$b2bCorpPartnerChat, 'getGroupMessages']);
    Route::get('/b2b/corporate-chat/messages/{userId}', [$b2bCorpPartnerChat, 'getDirectMessages'])->whereNumber('userId');
    Route::post('/b2b/corporate-chat/send', [$b2bCorpPartnerChat, 'sendMessage']);
    Route::post('/b2b/corporate-chat/send-attachment', [$b2bCorpPartnerChat, 'sendAttachment']);
    Route::post('/b2b/corporate-chat/mark-read/group', [$b2bCorpPartnerChat, 'markGroupAsRead']);
    Route::post('/b2b/corporate-chat/mark-read/{userId}', [$b2bCorpPartnerChat, 'markDirectAsRead'])->whereNumber('userId');
    Route::get('/b2b/corporate-chat/call/{userId}', [$b2bCorpPartnerChat, 'initiateCall'])->whereNumber('userId');
    Route::get('/b2b/corporate-chat/unread-counts', [$b2bCorpPartnerChat, 'unreadCounts']);
    Route::get('/b2b-reference/dashboard', [App\Http\Controllers\Api\B2BLeadApiController::class, 'referenceDashboard']);
    Route::get('/b2b-reference/leads', [App\Http\Controllers\Api\B2BLeadApiController::class, 'referenceLeads']);

    // Insurer portal app APIs
    $insurerPortal = App\Http\Controllers\Api\InsurerPortalApiController::class;
    Route::get('/insurer/dashboard', [$insurerPortal, 'dashboard']);
    Route::put('/insurer/password', [$insurerPortal, 'updatePassword']);
    Route::get('/insurer/corporates', [$insurerPortal, 'corporatesIndex']);
    Route::post('/insurer/corporates', [$insurerPortal, 'corporatesStore']);
    Route::get('/insurer/corporates/{corporate}', [$insurerPortal, 'corporatesShow'])->whereNumber('corporate');
    Route::put('/insurer/corporates/{corporate}', [$insurerPortal, 'corporatesUpdate'])->whereNumber('corporate');
    Route::delete('/insurer/corporates/{corporate}', [$insurerPortal, 'corporatesDestroy'])->whereNumber('corporate');
    Route::get('/insurer/corporates/{corporate}/employees', [$insurerPortal, 'corporateEmployees'])->whereNumber('corporate');
    Route::get('/insurer/corporates/{corporate}/employees/{employee}', [$insurerPortal, 'corporateEmployeeShow'])->whereNumber(['corporate', 'employee']);

    // Broker portal app APIs
    $brokerPortal = App\Http\Controllers\Api\BrokerPortalApiController::class;
    Route::get('/broker/dashboard', [$brokerPortal, 'dashboard']);
    Route::put('/broker/password', [$brokerPortal, 'updatePassword']);
    Route::get('/broker/corporates', [$brokerPortal, 'corporatesIndex']);
    Route::post('/broker/corporates', [$brokerPortal, 'corporatesStore']);
    Route::get('/broker/corporates/{corporate}', [$brokerPortal, 'corporatesShow'])->whereNumber('corporate');
    Route::put('/broker/corporates/{corporate}', [$brokerPortal, 'corporatesUpdate'])->whereNumber('corporate');
    Route::delete('/broker/corporates/{corporate}', [$brokerPortal, 'corporatesDestroy'])->whereNumber('corporate');
    Route::get('/broker/corporates/{corporate}/employees', [$brokerPortal, 'corporateEmployees'])->whereNumber('corporate');
    Route::get('/broker/corporates/{corporate}/employees/{employee}', [$brokerPortal, 'corporateEmployeeShow'])->whereNumber(['corporate', 'employee']);

    // Doctor referral portal app APIs
    $doctorReferralPortal = App\Http\Controllers\Api\DoctorReferralPortalApiController::class;
    Route::get('/doctor-referral/dashboard', [$doctorReferralPortal, 'dashboard']);
    Route::get('/doctor-referral/leads', [$doctorReferralPortal, 'leads']);

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
    Route::get('/admin/operation-leads/vendor-payment-details/{vendorId}', [App\Http\Controllers\Admin\OperationLeadController::class, 'getVendorPaymentDetails']);

    // Admin Payment Invoice and Received Payment Routes
    Route::get('/admin/operation-leads/payment-invoice/{id}/view', [App\Http\Controllers\Admin\OperationLeadController::class, 'showPaymentInvoiceHtml']);
    Route::get('/admin/operation-leads/payment-invoice/{id}/pdf', [App\Http\Controllers\Admin\OperationLeadController::class, 'showPaymentInvoicePdf']);
    Route::post('/admin/operation-leads/invoice/{invoiceId}/received-payment', [App\Http\Controllers\Admin\OperationLeadController::class, 'storeReceivedPayment']);
    Route::get('/admin/operation-leads/received-payment/{id}/edit', [App\Http\Controllers\Admin\OperationLeadController::class, 'editReceivedPayment']);
    Route::put('/admin/operation-leads/received-payment/{id}', [App\Http\Controllers\Admin\OperationLeadController::class, 'updateReceivedPayment']);
    Route::post('/admin/operation-leads/received-payment/{id}', [App\Http\Controllers\Admin\OperationLeadController::class, 'updateReceivedPayment']);
    Route::delete('/admin/operation-leads/received-payment/{id}', [App\Http\Controllers\Admin\OperationLeadController::class, 'destroyReceivedPayment']);

    // Admin Deployment Details Routes
    Route::post('/admin/operation-leads/{lead}/deployment', [App\Http\Controllers\Admin\OperationLeadController::class, 'storeDeploymentDetail']);
    Route::get('/admin/operation-leads/deployment/{id}/edit', [App\Http\Controllers\Admin\OperationLeadController::class, 'editDeploymentDetail']);
    Route::put('/admin/operation-leads/deployment/{id}', [App\Http\Controllers\Admin\OperationLeadController::class, 'updateDeploymentDetail']);
    Route::delete('/admin/operation-leads/deployment/{id}', [App\Http\Controllers\Admin\OperationLeadController::class, 'destroyDeploymentDetail']);
    Route::post('/admin/operation-leads/deployment/{id}/dates', [App\Http\Controllers\Admin\OperationLeadController::class, 'getDeploymentDates']);
    Route::post('/admin/operation-leads/deployment/{id}/save-absent', [App\Http\Controllers\Admin\OperationLeadController::class, 'saveDeploymentAbsentDates']);
    Route::post('/admin/operation-leads/deployment/{id}/toggle-verify-payment', [App\Http\Controllers\Admin\OperationLeadController::class, 'toggleVerifyPayment']);
    Route::get('/admin/operation-leads/vendor-details', [App\Http\Controllers\Admin\OperationLeadController::class, 'getVendorDetails']);
    Route::post('/admin/operation-leads/update-freelancer-status', [App\Http\Controllers\Admin\OperationLeadController::class, 'updateFreelancerStatus']);
    Route::get('/admin/operation-leads/updated-vendor-count', [App\Http\Controllers\Admin\OperationLeadController::class, 'getUpdatedVendorCount']);
    Route::get('/admin/operation-leads/filtered-vendors', [App\Http\Controllers\Admin\OperationLeadController::class, 'getFilteredVendorsForDeployment']);

    // Admin Executives and Vendors Routes
    Route::get('/admin/executives', [App\Http\Controllers\Admin\OperationLeadController::class, 'getExecutives']);

    // Vendors API Routes
    Route::get('/admin/vendors', [App\Http\Controllers\Admin\VendorController::class, 'index']);
    Route::post('/admin/vendors', [App\Http\Controllers\Admin\VendorController::class, 'store']);
    Route::get('/admin/vendors/{id}/edit', [App\Http\Controllers\Admin\VendorController::class, 'edit']);
    Route::put('/admin/vendors/{id}', [App\Http\Controllers\Admin\VendorController::class, 'update']);
    Route::delete('/admin/vendors/{id}', [App\Http\Controllers\Admin\VendorController::class, 'destroy']);
    Route::get('/admin/vendors/{vendor}/price-change-requests', [App\Http\Controllers\Admin\VendorController::class, 'priceChangeRequests']);
    Route::post('/admin/vendors/price-change-requests/{price_change_request}/approve', [App\Http\Controllers\Admin\VendorController::class, 'approvePriceChangeRequest']);
    Route::post('/admin/vendors/price-change-requests/{price_change_request}/reject', [App\Http\Controllers\Admin\VendorController::class, 'rejectPriceChangeRequest']);

    // Dashboard API Routes
    Route::get('/admin/dashboard', [App\Http\Controllers\Admin\AdminController::class, 'dashboardApi']);
    Route::get('/admin/dashboard/sales-leads-stats', [App\Http\Controllers\Admin\AdminController::class, 'getSalesLeadsStats']);
    Route::get('/admin/dashboard/operation-leads-stats', [App\Http\Controllers\Admin\AdminController::class, 'getOperationLeadsStats']);
    Route::get('/admin/dashboard/sales-executives', [App\Http\Controllers\Admin\AdminController::class, 'getSalesExecutives']);
    Route::get('/admin/dashboard/operation-executives', [App\Http\Controllers\Admin\AdminController::class, 'getOperationExecutives']);
    Route::get('/admin/dashboard/users-stats', [App\Http\Controllers\Admin\AdminController::class, 'getUsersStats']);
    Route::get('/admin/dashboard/users', [App\Http\Controllers\Admin\AdminController::class, 'getUsers']);
    Route::get('/admin/dashboard/user-details/{userId}', [App\Http\Controllers\Admin\AdminController::class, 'getUserDetails']);
    Route::get('/admin/dashboard/tasks', [App\Http\Controllers\Admin\AdminController::class, 'getTasks']);
    Route::post('/admin/dashboard/tasks', [App\Http\Controllers\Admin\AdminController::class, 'storeTask']);
    Route::get('/admin/dashboard/tasks/{id}/edit', [App\Http\Controllers\Admin\AdminController::class, 'editTask']);
    Route::put('/admin/dashboard/tasks/{id}', [App\Http\Controllers\Admin\AdminController::class, 'updateTask']);
    Route::delete('/admin/dashboard/tasks/{id}', [App\Http\Controllers\Admin\AdminController::class, 'destroyTask']);
    Route::get('/admin/dashboard/task-history', [App\Http\Controllers\Admin\AdminController::class, 'getTaskHistory']);
    Route::get('/admin/dashboard/revenue-stats', [App\Http\Controllers\Admin\AdminController::class, 'getRevenueStats']);
    Route::get('/admin/dashboard/pending-deployments', [App\Http\Controllers\Admin\AdminController::class, 'getPendingDeployments']);
    Route::get('/admin/dashboard/outstanding-amount-stats', [App\Http\Controllers\Admin\AdminController::class, 'getOutstandingAmountStats']);
    Route::get('/admin/dashboard/profile-pending-stats', [App\Http\Controllers\Admin\AdminController::class, 'getProfilePendingStats']);
    Route::get('/admin/dashboard/vendor-payment-stats', [App\Http\Controllers\Admin\AdminController::class, 'getVendorPaymentStats']);
    Route::get('/admin/dashboard/unverified-deployment-payments-stats', [App\Http\Controllers\Admin\AdminController::class, 'getUnverifiedDeploymentPaymentsStats']);
    Route::get('/admin/dashboard/pending-callbacks', [App\Http\Controllers\Admin\AdminController::class, 'getPendingCallbacks']);
    Route::get('/admin/dashboard/recent-calls/sales', [App\Http\Controllers\Admin\AdminController::class, 'getRecentCallsSales']);
    Route::get('/admin/dashboard/recent-calls/operation', [App\Http\Controllers\Admin\AdminController::class, 'getRecentCallsOperation']);
    Route::get('/admin/dashboard/calendar-data', [App\Http\Controllers\Admin\AdminController::class, 'getCalendarData']);
    Route::get('/admin/dashboard/calendar-leads', [App\Http\Controllers\Admin\AdminController::class, 'getCalendarLeads']);

    // Vendor Payment API Routes
    Route::get('/admin/vendor-payments', [App\Http\Controllers\Admin\VendorPaymentController::class, 'indexApi']);
    Route::post('/admin/vendor-payments', [App\Http\Controllers\Admin\VendorPaymentController::class, 'store']);
    Route::get('/admin/vendor-payments/{id}', [App\Http\Controllers\Admin\VendorPaymentController::class, 'show']);
    Route::get('/admin/vendor-payments/{id}/edit', [App\Http\Controllers\Admin\VendorPaymentController::class, 'edit']);
    Route::post('/admin/vendor-payments/{id}', [App\Http\Controllers\Admin\VendorPaymentController::class, 'update']);
    Route::put('/admin/vendor-payments/{id}', [App\Http\Controllers\Admin\VendorPaymentController::class, 'update']);
    Route::delete('/admin/vendor-payments/{id}', [App\Http\Controllers\Admin\VendorPaymentController::class, 'destroy']);
    Route::get('/admin/vendor-payments/vendor/{vendorId}', [App\Http\Controllers\Admin\VendorPaymentController::class, 'getVendorPayments']);
    Route::get('/admin/vendor-payments/stats', [App\Http\Controllers\Admin\VendorPaymentController::class, 'getPaymentStats']);
    Route::get('/admin/vendor-payments/{id}/screenshot', [App\Http\Controllers\Admin\VendorPaymentController::class, 'downloadScreenshot']);
    Route::get('/admin/vendor-payments/{id}/invoice', [App\Http\Controllers\Admin\VendorPaymentController::class, 'invoiceApi']);
    Route::get('/admin/vendor-payments/vendor/{vendorId}/statement', [App\Http\Controllers\Admin\VendorPaymentController::class, 'statementApi']);

    // Freelancer Payment API Routes
    Route::get('/admin/freelancer-payments', [App\Http\Controllers\Admin\FreelancerPaymentController::class, 'indexApi']);
    Route::post('/admin/freelancer-payments', [App\Http\Controllers\Admin\FreelancerPaymentController::class, 'store']);
    Route::get('/admin/freelancer-payments/{id}', [App\Http\Controllers\Admin\FreelancerPaymentController::class, 'show']);
    Route::get('/admin/freelancer-payments/{id}/edit', [App\Http\Controllers\Admin\FreelancerPaymentController::class, 'edit']);
    Route::post('/admin/freelancer-payments/{id}', [App\Http\Controllers\Admin\FreelancerPaymentController::class, 'update']);
    Route::put('/admin/freelancer-payments/{id}', [App\Http\Controllers\Admin\FreelancerPaymentController::class, 'update']);
    Route::delete('/admin/freelancer-payments/{id}', [App\Http\Controllers\Admin\FreelancerPaymentController::class, 'destroy']);
    Route::get('/admin/freelancer-payments/freelancer/{freelancerId}', [App\Http\Controllers\Admin\FreelancerPaymentController::class, 'getFreelancerPayments']);
    Route::get('/admin/freelancer-payments/{id}/invoice', [App\Http\Controllers\Admin\FreelancerPaymentController::class, 'invoiceApi']);
    Route::get('/admin/freelancer-payments/freelancer/{freelancerId}/statement', [App\Http\Controllers\Admin\FreelancerPaymentController::class, 'statementApi']);
    Route::get('/admin/operation-leads/freelancer-payment-details/{freelancerId}', [App\Http\Controllers\Admin\OperationLeadController::class, 'getFreelancerPaymentDetails']);

    // Job Requests API Routes
    Route::get('/admin/jobproc', [App\Http\Controllers\Admin\JobProcController::class, 'getJobRequests']);
    Route::post('/admin/jobproc', [App\Http\Controllers\Admin\JobProcController::class, 'store']);
    Route::post('/admin/jobproc/import', [App\Http\Controllers\Admin\JobProcController::class, 'import']);
    Route::get('/admin/jobproc/{id}', [App\Http\Controllers\Admin\JobProcController::class, 'show']);
    Route::get('/admin/jobproc/{id}/edit', [App\Http\Controllers\Admin\JobProcController::class, 'edit']);
    Route::put('/admin/jobproc/{id}', [App\Http\Controllers\Admin\JobProcController::class, 'update']);
    Route::delete('/admin/jobproc/{id}', [App\Http\Controllers\Admin\JobProcController::class, 'destroy']);
    Route::get('/admin/jobproc/{job_request}/price-change-requests', [App\Http\Controllers\Admin\JobProcController::class, 'priceChangeRequests']);
    Route::post('/admin/jobproc/price-change-requests/{price_change_request}/approve', [App\Http\Controllers\Admin\JobProcController::class, 'approvePriceChangeRequest']);
    Route::post('/admin/jobproc/price-change-requests/{price_change_request}/reject', [App\Http\Controllers\Admin\JobProcController::class, 'rejectPriceChangeRequest']);
    Route::post('/admin/jobproc/{job_request}/profile-image/generate-uniform', [App\Http\Controllers\Admin\JobProcController::class, 'generateProfileImageUniform']);
    Route::post('/admin/jobproc/{job_request}/profile-image/approve', [App\Http\Controllers\Admin\JobProcController::class, 'approveProfileImage']);

    // Doctor registration requests (admin app — same backend as CRM web)
    Route::get('/admin/doctor-requests', [App\Http\Controllers\Admin\DoctorRequestAdminController::class, 'apiIndex']);
    Route::get('/admin/doctor-requests/{doctor_request}', [App\Http\Controllers\Admin\DoctorRequestAdminController::class, 'apiShow']);
    Route::post('/admin/doctor-requests/{doctor_request}/status', [App\Http\Controllers\Admin\DoctorRequestAdminController::class, 'updateStatus']);
    Route::get('/admin/doctor-requests/{doctor_request}/pricing-edit', [App\Http\Controllers\Admin\DoctorRequestAdminController::class, 'pricingEdit']);
    Route::put('/admin/doctor-requests/{doctor_request}/pricing', [App\Http\Controllers\Admin\DoctorRequestAdminController::class, 'updatePricing']);
    Route::get('/admin/doctor-requests/{doctor_request}/pricing-logs', [App\Http\Controllers\Admin\DoctorRequestAdminController::class, 'pricingLogs']);

    Route::get('/admin/doctor-referral-users', [App\Http\Controllers\Admin\DoctorReferralUserController::class, 'apiIndex']);
    Route::post('/admin/doctor-referral-users', [App\Http\Controllers\Admin\DoctorReferralUserController::class, 'apiStore']);
    Route::delete('/admin/doctor-referral-users/{doctor_referral_user}', [App\Http\Controllers\Admin\DoctorReferralUserController::class, 'apiDestroy']);

    // Doctor consultation services (admin app — same data as CRM web)
    Route::get('/admin/doctor-consultation-services', [App\Http\Controllers\Admin\DoctorConsultationServiceController::class, 'apiIndex']);
    Route::post('/admin/doctor-consultation-services', [App\Http\Controllers\Admin\DoctorConsultationServiceController::class, 'apiStore']);
    Route::get('/admin/doctor-consultation-services/{doctor_consultation_service}', [App\Http\Controllers\Admin\DoctorConsultationServiceController::class, 'apiShow']);
    Route::put('/admin/doctor-consultation-services/{doctor_consultation_service}', [App\Http\Controllers\Admin\DoctorConsultationServiceController::class, 'apiUpdate']);
    Route::delete('/admin/doctor-consultation-services/{doctor_consultation_service}', [App\Http\Controllers\Admin\DoctorConsultationServiceController::class, 'apiDestroy']);

    // Admin portal modules (CareApp — CRM parity)
    $adminPortal = App\Http\Controllers\Api\AdminPortalModulesApiController::class;
    Route::get('/admin/easebuzz-payments', [$adminPortal, 'easebuzzPaymentsIndex']);
    Route::post('/admin/easebuzz-payments', [$adminPortal, 'easebuzzPaymentsStore']);
    Route::get('/admin/easebuzz-payments/{payment}', [$adminPortal, 'easebuzzPaymentsShow']);
    Route::post('/admin/easebuzz-payments/{payment}/verify', [$adminPortal, 'easebuzzPaymentsVerify']);
    Route::get('/admin/b2b-partners', [$adminPortal, 'b2bPartnersIndex']);
    Route::post('/admin/b2b-partners', [$adminPortal, 'b2bPartnersStore']);
    Route::put('/admin/b2b-partners/{b2bUser}', [$adminPortal, 'b2bPartnersUpdate']);
    Route::delete('/admin/b2b-partners/{b2bUser}', [$adminPortal, 'b2bPartnersDestroy']);
    Route::get('/admin/insurers', [$adminPortal, 'insurersIndex']);
    Route::get('/admin/insurers/{insurer}', [$adminPortal, 'insurersShow']);
    Route::post('/admin/insurers', [$adminPortal, 'insurersStore']);
    Route::put('/admin/insurers/{insurer}', [$adminPortal, 'insurersUpdate']);
    Route::delete('/admin/insurers/{insurer}', [$adminPortal, 'insurersDestroy']);
    Route::get('/admin/brokers', [$adminPortal, 'brokersIndex']);
    Route::get('/admin/brokers/{broker}', [$adminPortal, 'brokersShow']);
    Route::post('/admin/brokers', [$adminPortal, 'brokersStore']);
    Route::put('/admin/brokers/{broker}', [$adminPortal, 'brokersUpdate']);
    Route::delete('/admin/brokers/{broker}', [$adminPortal, 'brokersDestroy']);
    Route::get('/admin/corporate-accounts', [$adminPortal, 'corporateAccountsIndex']);
    Route::get('/admin/corporate-employees', [$adminPortal, 'corporateEmployeesIndex']);
    Route::get('/admin/corporate-employees/{corporateEmployee}', [$adminPortal, 'corporateEmployeesShow']);
    Route::get('/admin/locations-manage/form-options', [App\Http\Controllers\Admin\LocationController::class, 'apiFormOptions']);
    Route::get('/admin/locations-manage', [App\Http\Controllers\Admin\LocationController::class, 'apiIndex']);
    Route::post('/admin/locations-manage', [App\Http\Controllers\Admin\LocationController::class, 'apiStore']);
    Route::get('/admin/locations-manage/{location}', [App\Http\Controllers\Admin\LocationController::class, 'apiShow']);
    Route::put('/admin/locations-manage/{location}', [App\Http\Controllers\Admin\LocationController::class, 'apiUpdate']);
    Route::delete('/admin/locations-manage/{location}', [App\Http\Controllers\Admin\LocationController::class, 'apiDestroy']);
    Route::get('/admin/services-catalog', [App\Http\Controllers\Admin\ServiceController::class, 'apiIndex']);
    Route::post('/admin/services-catalog', [App\Http\Controllers\Admin\ServiceController::class, 'apiStore']);
    Route::get('/admin/services-catalog/{service}', [App\Http\Controllers\Admin\ServiceController::class, 'apiShow']);
    Route::put('/admin/services-catalog/{service}', [App\Http\Controllers\Admin\ServiceController::class, 'apiUpdate']);
    Route::delete('/admin/services-catalog/{service}', [App\Http\Controllers\Admin\ServiceController::class, 'apiDestroy']);
    Route::get('/admin/languages', [App\Http\Controllers\Admin\LanguageController::class, 'apiIndex']);
    Route::post('/admin/languages', [App\Http\Controllers\Admin\LanguageController::class, 'apiStore']);
    Route::get('/admin/languages/{language}', [App\Http\Controllers\Admin\LanguageController::class, 'apiShow']);
    Route::put('/admin/languages/{language}', [App\Http\Controllers\Admin\LanguageController::class, 'apiUpdate']);
    Route::delete('/admin/languages/{language}', [App\Http\Controllers\Admin\LanguageController::class, 'apiDestroy']);
    Route::post('/admin/languages/{language}/auto-translate', [App\Http\Controllers\Admin\LanguageController::class, 'apiAutoTranslate']);
    Route::get('/admin/languages/{language}/translations', [App\Http\Controllers\Admin\LanguageController::class, 'apiTranslations']);
    Route::put('/admin/languages/{language}/translations', [App\Http\Controllers\Admin\LanguageController::class, 'apiUpdateTranslations']);
    Route::post('/admin/languages/{language}/translations/reset', [App\Http\Controllers\Admin\LanguageController::class, 'apiResetTranslations']);
    Route::get('/admin/otp-logs/{registrationType}', [$adminPortal, 'otpLogsIndex']);
    Route::get('/admin/website-consultation-payments', [$adminPortal, 'websiteConsultationPaymentsIndex']);
    Route::get('/admin/website-consultation-payments/{booking}', [$adminPortal, 'websiteConsultationPaymentsShow']);
    Route::get('/admin/location-attendance', [$adminPortal, 'locationAttendanceIndex']);
    Route::get('/admin/location-attendance/deployments/{deployment}', [$adminPortal, 'locationAttendanceDeploymentDetail']);

    // B2B Corporate Chat (CareApp admin — CRM parity)
    $b2bCorpChat = App\Http\Controllers\Api\AdminB2BCorporateChatApiController::class;
    Route::get('/admin/b2b-corporate-chat/access', [$b2bCorpChat, 'access']);
    Route::get('/admin/b2b-corporate-chat', [$b2bCorpChat, 'index']);
    Route::get('/admin/b2b-corporate-chat/messages/{b2bUserId}/group', [$b2bCorpChat, 'getGroupMessages']);
    Route::get('/admin/b2b-corporate-chat/messages/{b2bUserId}/direct', [$b2bCorpChat, 'getDirectMessages']);
    Route::post('/admin/b2b-corporate-chat/send', [$b2bCorpChat, 'sendMessage']);
    Route::post('/admin/b2b-corporate-chat/send-attachment', [$b2bCorpChat, 'sendAttachment']);
    Route::post('/admin/b2b-corporate-chat/mark-read/{b2bUserId}/group', [$b2bCorpChat, 'markGroupAsRead']);
    Route::post('/admin/b2b-corporate-chat/mark-read/{b2bUserId}/direct', [$b2bCorpChat, 'markDirectAsRead']);
    Route::get('/admin/b2b-corporate-chat/call/{b2bUserId}', [$b2bCorpChat, 'initiateCall']);
    Route::get('/admin/b2b-corporate-chat/unread-counts', [$b2bCorpChat, 'unreadCounts']);

    // Sales Leads API Routes
    Route::get('/sales/leads', [App\Http\Controllers\Sales\LeadController::class, 'getLeads']);
    Route::get('/sales/services', function () {
        return response()->json([
            'services' => \App\Models\Service::orderBy('name', 'asc')->get(['id', 'name'])
        ]);
    });
    Route::get('/sales/locations', function () {
        return response()->json([
            'locations' => \App\Models\Location::orderBy('name', 'asc')->get(['id', 'name', 'state'])
                ->map(fn ($loc) => [
                    'id' => $loc->id,
                    'name' => $loc->name,
                    'state' => $loc->state,
                    'display_label' => $loc->display_label,
                ]),
        ]);
    });
    Route::post('/sales/leads', [App\Http\Controllers\Sales\LeadController::class, 'store']);
    Route::get('/sales/leads/{id}/edit', [App\Http\Controllers\Sales\LeadController::class, 'edit']);
    Route::post('/sales/leads/{id}/status-remark/generate-ai', [App\Http\Controllers\LeadStatusRemarkAiController::class, 'generateForSales']);
    Route::put('/sales/leads/{id}', [App\Http\Controllers\Sales\LeadController::class, 'update']);
    Route::get('/sales/leads/{id}', [App\Http\Controllers\Sales\LeadController::class, 'show']);
    Route::post('/sales/leads/run-future-prospect-reminder-check', [App\Http\Controllers\Sales\LeadController::class, 'runFutureProspectReminderCheck']);

    // Sales Dashboard API Routes
    Route::get('/sales/dashboard', [App\Http\Controllers\Sales\SalesController::class, 'dashboard']);
    Route::get('/sales/dashboard/leads-stats', [App\Http\Controllers\Sales\SalesController::class, 'getLeadsStats']);
    Route::get('/sales/dashboard/tasks', [App\Http\Controllers\Sales\SalesController::class, 'getTasks']);
    Route::post('/sales/dashboard/tasks', [App\Http\Controllers\Sales\SalesController::class, 'storeTask']);
    Route::put('/sales/dashboard/tasks/{id}/status', [App\Http\Controllers\Sales\SalesController::class, 'updateTaskStatus']);
    Route::put('/sales/dashboard/tasks/{id}', [App\Http\Controllers\Sales\SalesController::class, 'updateTask']);
    Route::get('/sales/dashboard/calendar-data', [App\Http\Controllers\Sales\SalesController::class, 'getCalendarData']);
    Route::get('/sales/dashboard/date-leads', [App\Http\Controllers\Sales\SalesController::class, 'getDateLeads']);
    Route::get('/sales/dashboard/lead/{id}', [App\Http\Controllers\Sales\SalesController::class, 'getLeadDetails']);
    Route::get('/sales/dashboard/call-logs-count', [App\Http\Controllers\Sales\SalesController::class, 'callLogsCount']);
    Route::get('/sales/dashboard/call-logs', [App\Http\Controllers\Sales\SalesController::class, 'callLogs']);
    Route::put('/sales/dashboard/call-logs/{id}/mark-processed', [App\Http\Controllers\Sales\SalesController::class, 'markCallLogProcessed']);
    Route::get('/sales/dashboard/recent-leads', [App\Http\Controllers\Sales\SalesController::class, 'getRecentLeads']);
    Route::get('/sales/dashboard/active-calls', [App\Http\Controllers\Sales\SalesController::class, 'getActiveCalls']);
    Route::get('/sales/dashboard/pending-callbacks', [App\Http\Controllers\Sales\SalesController::class, 'getPendingCallbacks']);
    Route::get('/sales/dashboard/recent-calls', [App\Http\Controllers\Sales\SalesController::class, 'getRecentCalls']);

    // Sales Easebuzz Payments API Routes
    Route::get('/sales/easebuzz-payments', [App\Http\Controllers\Api\SalesEasebuzzPaymentsApiController::class, 'index']);
    Route::post('/sales/easebuzz-payments', [App\Http\Controllers\Api\SalesEasebuzzPaymentsApiController::class, 'store']);
    Route::get('/sales/easebuzz-payments/{payment}', [App\Http\Controllers\Api\SalesEasebuzzPaymentsApiController::class, 'show']);
    Route::post('/sales/easebuzz-payments/{payment}/verify', [App\Http\Controllers\Api\SalesEasebuzzPaymentsApiController::class, 'verify']);

    // Manager Leads API Routes
    Route::get('/manager/leads', [App\Http\Controllers\Manager\LeadController::class, 'getLeads']);
    Route::get('/manager/leads/app', [App\Http\Controllers\Manager\LeadController::class, 'getLeadsForApp']);
    Route::post('/manager/leads', [App\Http\Controllers\Manager\LeadController::class, 'store']);
    Route::get('/manager/leads/executives', [App\Http\Controllers\Manager\LeadController::class, 'getExecutives']);
    Route::get('/manager/leads/locations', [App\Http\Controllers\Manager\LeadController::class, 'getLocations']);
    Route::get('/manager/leads/{id}/edit', [App\Http\Controllers\Manager\LeadController::class, 'edit']);
    Route::post('/manager/leads/{id}/status-remark/generate-ai', [App\Http\Controllers\LeadStatusRemarkAiController::class, 'generateForManager']);
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

    // Manager Dashboard API Routes
    Route::get('/manager/dashboard', [App\Http\Controllers\Manager\ManagerController::class, 'dashboard']);
    Route::get('/manager/dashboard/leads-stats', [App\Http\Controllers\Manager\ManagerController::class, 'getLeadsStats']);
    Route::get('/manager/dashboard/calendar-data', [App\Http\Controllers\Manager\ManagerController::class, 'getCalendarData']);
    Route::get('/manager/dashboard/date-leads', [App\Http\Controllers\Manager\ManagerController::class, 'getDateLeads']);
    Route::get('/manager/dashboard/lead/{id}', [App\Http\Controllers\Manager\ManagerController::class, 'getLeadDetails']);
    Route::get('/manager/dashboard/recent-leads', [App\Http\Controllers\Manager\ManagerController::class, 'getRecentLeads']);
    Route::get('/manager/dashboard/recent-calls', [App\Http\Controllers\Manager\ManagerController::class, 'getRecentCalls']);
    Route::get('/manager/dashboard/pending-callbacks', [App\Http\Controllers\Manager\ManagerController::class, 'getPendingCallbacks']);
    Route::get('/manager/dashboard/tasks', [App\Http\Controllers\Manager\ManagerController::class, 'getTasks']);
    Route::post('/manager/dashboard/tasks', [App\Http\Controllers\Manager\ManagerController::class, 'storeTask']);
    Route::get('/manager/dashboard/tasks/{id}/edit', [App\Http\Controllers\Manager\ManagerController::class, 'editTask']);
    Route::post('/manager/dashboard/tasks/{id}/update', [App\Http\Controllers\Manager\ManagerController::class, 'updateTask']);
    Route::put('/manager/dashboard/tasks/{id}/status', [App\Http\Controllers\Manager\ManagerController::class, 'updateTaskStatus']);
    Route::delete('/manager/dashboard/tasks/{id}', [App\Http\Controllers\Manager\ManagerController::class, 'destroyTask']);
    Route::get('/manager/dashboard/team-members', [App\Http\Controllers\Manager\ManagerController::class, 'getTeamMembers']);

    // Operation Leads API Routes
    Route::get('/operation/operation-leads', [App\Http\Controllers\Operation\OperationLeadController::class, 'getLeadsForApp']);
    Route::post('/operation/operation-leads/run-future-prospect-reminder-check', [App\Http\Controllers\Operation\OperationLeadController::class, 'runFutureProspectReminderCheck']);
    // Vendor Filtering Routes (must come before {id} routes)
    Route::get('/operation/operation-leads/filtered-vendors', [App\Http\Controllers\Operation\OperationLeadController::class, 'getFilteredVendorsForDeployment']);
    Route::get('/operation/operation-leads/vendor-details', [App\Http\Controllers\Operation\OperationLeadController::class, 'getVendorDetails']);
    Route::get('/operation/operation-leads/{id}', [App\Http\Controllers\Operation\OperationLeadController::class, 'show']);
    Route::get('/operation/operation-leads/{id}/edit', [App\Http\Controllers\Operation\OperationLeadController::class, 'edit']);
    Route::post('/operation/operation-leads/{id}/status-remark/generate-ai', [App\Http\Controllers\LeadStatusRemarkAiController::class, 'generateForOperation']);
    Route::post('/operation/operation-leads', [App\Http\Controllers\Operation\OperationLeadController::class, 'store']);
    Route::put('/operation/operation-leads/{id}', [App\Http\Controllers\Operation\OperationLeadController::class, 'update']);
    Route::delete('/operation/operation-leads/{id}', [App\Http\Controllers\Operation\OperationLeadController::class, 'destroy']);
    Route::post('/operation/operation-leads/{lead}/payment', [App\Http\Controllers\Operation\OperationLeadController::class, 'storePaymentDetail']);
    Route::put('/operation/operation-leads/payment/{id}', [App\Http\Controllers\Operation\OperationLeadController::class, 'updatePaymentDetail']);
    Route::get('/operation/operation-leads/payment-invoice/{id}/view', [App\Http\Controllers\Operation\OperationLeadController::class, 'showPaymentInvoiceHtml']);
    Route::get('/operation/operation-leads/payment-invoice/{id}/pdf', [App\Http\Controllers\Operation\OperationLeadController::class, 'showPaymentInvoicePdf']);
    Route::post('/operation/operation-leads/invoice/{invoiceId}/received-payment', [App\Http\Controllers\Operation\OperationLeadController::class, 'storeReceivedPayment']);
    Route::get('/operation/operation-leads/received-payment/{id}/edit', [App\Http\Controllers\Operation\OperationLeadController::class, 'editReceivedPayment']);
    Route::put('/operation/operation-leads/received-payment/{id}', [App\Http\Controllers\Operation\OperationLeadController::class, 'updateReceivedPayment']);
    Route::post('/operation/operation-leads/received-payment/{id}', [App\Http\Controllers\Operation\OperationLeadController::class, 'updateReceivedPayment']);
    Route::delete('/operation/operation-leads/received-payment/{id}', [App\Http\Controllers\Operation\OperationLeadController::class, 'destroyReceivedPayment']);

    // Deployment Details API Routes
    Route::post('/operation/operation-leads/{lead}/deployment', [App\Http\Controllers\Operation\OperationLeadController::class, 'storeDeploymentDetail']);
    Route::get('/operation/operation-leads/deployment/{id}/edit', [App\Http\Controllers\Operation\OperationLeadController::class, 'editDeploymentDetail']);
    Route::put('/operation/operation-leads/deployment/{id}', [App\Http\Controllers\Operation\OperationLeadController::class, 'updateDeploymentDetail']);
    Route::delete('/operation/operation-leads/deployment/{id}', [App\Http\Controllers\Operation\OperationLeadController::class, 'destroyDeploymentDetail']);
    Route::post('/operation/operation-leads/deployment/{id}/toggle-verify-payment', [App\Http\Controllers\Operation\OperationLeadController::class, 'toggleVerifyPayment']);
    Route::post('/operation/operation-leads/deployment/{id}/dates', [App\Http\Controllers\Operation\OperationLeadController::class, 'getDeploymentDates']);
    Route::post('/operation/operation-leads/deployment/{id}/save-absent', [App\Http\Controllers\Operation\OperationLeadController::class, 'saveDeploymentAbsentDates']);

    // Job Requests API Routes
    Route::get('/operation/jobproc/jobrequests', [App\Http\Controllers\Operation\JobProcController::class, 'getJobRequestsForApp']);
    Route::post('/operation/jobproc', [App\Http\Controllers\Operation\JobProcController::class, 'store']);
    Route::get('/operation/jobproc/{id}', [App\Http\Controllers\Operation\JobProcController::class, 'show']);
    Route::get('/operation/jobproc/{id}/edit', [App\Http\Controllers\Operation\JobProcController::class, 'edit']);
    Route::put('/operation/jobproc/{id}', [App\Http\Controllers\Operation\JobProcController::class, 'update']);
    Route::delete('/operation/jobproc/{id}', [App\Http\Controllers\Operation\JobProcController::class, 'destroy']);

    // Operation Easebuzz Payments API Routes
    Route::get('/operation/easebuzz-payments', [App\Http\Controllers\Api\OperationEasebuzzPaymentsApiController::class, 'index']);
    Route::post('/operation/easebuzz-payments', [App\Http\Controllers\Api\OperationEasebuzzPaymentsApiController::class, 'store']);
    Route::get('/operation/easebuzz-payments/{payment}', [App\Http\Controllers\Api\OperationEasebuzzPaymentsApiController::class, 'show']);
    Route::post('/operation/easebuzz-payments/{payment}/verify', [App\Http\Controllers\Api\OperationEasebuzzPaymentsApiController::class, 'verify']);

    // Operation Dashboard API Routes
    Route::get('/operation/dashboard', [App\Http\Controllers\Operation\OperationController::class, 'dashboard']);
    Route::get('/operation/dashboard/operation-executives', [App\Http\Controllers\Operation\OperationController::class, 'getOperationExecutives']);
    Route::get('/operation/dashboard/leads-stats', [App\Http\Controllers\Operation\OperationController::class, 'getLeadsStats']);
    Route::get('/operation/dashboard/outstanding-payments-stats', [App\Http\Controllers\Operation\OperationController::class, 'getOutstandingPaymentsStats']);
    Route::get('/operation/dashboard/deployment-pending-stats', [App\Http\Controllers\Operation\OperationController::class, 'getDeploymentPendingStats']);
    Route::get('/operation/dashboard/profile-pending-stats', [App\Http\Controllers\Operation\OperationController::class, 'getProfilePendingStats']);
    Route::get('/operation/dashboard/payment-due-stats', [App\Http\Controllers\Operation\OperationController::class, 'getPaymentDueStats']);
    Route::get('/operation/dashboard/ongoing-stopped-stats', [App\Http\Controllers\Operation\OperationController::class, 'getOngoingStoppedStats']);
    Route::get('/operation/dashboard/tasks', [App\Http\Controllers\Operation\OperationController::class, 'getTasks']);
    Route::post('/operation/dashboard/tasks', [App\Http\Controllers\Operation\OperationController::class, 'storeTask']);
    Route::put('/operation/dashboard/tasks/{id}', [App\Http\Controllers\Operation\OperationController::class, 'updateTask']);
    Route::delete('/operation/dashboard/tasks/{id}', [App\Http\Controllers\Operation\OperationController::class, 'destroyTask']);
    Route::get('/operation/dashboard/calendar-data', [App\Http\Controllers\Operation\OperationController::class, 'getCalendarData']);
    Route::get('/operation/dashboard/date-leads', [App\Http\Controllers\Operation\OperationController::class, 'getDateLeads']);
    Route::get('/operation/dashboard/job-request-stats', [App\Http\Controllers\Operation\OperationController::class, 'getJobRequestStats']);
    Route::get('/operation/dashboard/vendor-freelancer-payment-stats', [App\Http\Controllers\Operation\OperationController::class, 'getVendorFreelancerPaymentStats']);
    Route::get('/operation/dashboard/unverified-deployment-payments-stats', [App\Http\Controllers\Operation\OperationController::class, 'getUnverifiedDeploymentPaymentsStats']);
    Route::get('/operation/dashboard/pending-callbacks', [App\Http\Controllers\Operation\OperationController::class, 'getPendingCallbacks']);
    Route::get('/operation/dashboard/recent-calls', [App\Http\Controllers\Operation\OperationController::class, 'getRecentCalls']);
    Route::get('/operation/dashboard/recent-leads-stats', [App\Http\Controllers\Operation\OperationController::class, 'getRecentLeadsStats']);

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
    // Vendor Filtering Routes (must come before {id} routes)
    Route::get('/operation-manager/operation-leads/filtered-vendors', [App\Http\Controllers\OperationManager\OperationLeadController::class, 'getFilteredVendorsForDeployment']);
    Route::get('/operation-manager/operation-leads/vendor-details', [App\Http\Controllers\OperationManager\OperationLeadController::class, 'getVendorDetails']);
    Route::get('/operation-manager/operation-leads/{id}', [App\Http\Controllers\OperationManager\OperationLeadController::class, 'show']);
    Route::get('/operation-manager/operation-leads/{id}/edit', [App\Http\Controllers\OperationManager\OperationLeadController::class, 'edit']);
    Route::post('/operation-manager/operation-leads/{id}/status-remark/generate-ai', [App\Http\Controllers\LeadStatusRemarkAiController::class, 'generateForOperationManager']);
    Route::post('/operation-manager/operation-leads', [App\Http\Controllers\OperationManager\OperationLeadController::class, 'store']);
    Route::put('/operation-manager/operation-leads/{id}', [App\Http\Controllers\OperationManager\OperationLeadController::class, 'update']);
    Route::delete('/operation-manager/operation-leads/{id}', [App\Http\Controllers\OperationManager\OperationLeadController::class, 'destroy']);
    Route::post('/operation-manager/operation-leads/{lead}/payment', [App\Http\Controllers\OperationManager\OperationLeadController::class, 'storePaymentDetail']);
    Route::put('/operation-manager/operation-leads/payment/{id}', [App\Http\Controllers\OperationManager\OperationLeadController::class, 'updatePaymentDetail']);
    Route::get('/operation-manager/operation-leads/payment-invoice/{id}/view', [App\Http\Controllers\OperationManager\OperationLeadController::class, 'showPaymentInvoiceHtml']);
    Route::get('/operation-manager/operation-leads/payment-invoice/{id}/pdf', [App\Http\Controllers\OperationManager\OperationLeadController::class, 'showPaymentInvoicePdf']);
    Route::post('/operation-manager/operation-leads/invoice/{invoiceId}/received-payment', [App\Http\Controllers\OperationManager\OperationLeadController::class, 'storeReceivedPayment']);
    Route::get('/operation-manager/operation-leads/received-payment/{id}/edit', [App\Http\Controllers\OperationManager\OperationLeadController::class, 'editReceivedPayment']);
    Route::put('/operation-manager/operation-leads/received-payment/{id}', [App\Http\Controllers\OperationManager\OperationLeadController::class, 'updateReceivedPayment']);
    Route::post('/operation-manager/operation-leads/received-payment/{id}', [App\Http\Controllers\OperationManager\OperationLeadController::class, 'updateReceivedPayment']);
    Route::delete('/operation-manager/operation-leads/received-payment/{id}', [App\Http\Controllers\OperationManager\OperationLeadController::class, 'destroyReceivedPayment']);
    Route::post('/operation-manager/operation-leads/{lead}/deployment', [App\Http\Controllers\OperationManager\OperationLeadController::class, 'storeDeploymentDetail']);
    Route::get('/operation-manager/operation-leads/deployment/{id}/edit', [App\Http\Controllers\OperationManager\OperationLeadController::class, 'editDeploymentDetail']);
    Route::put('/operation-manager/operation-leads/deployment/{id}', [App\Http\Controllers\OperationManager\OperationLeadController::class, 'updateDeploymentDetail']);
    Route::delete('/operation-manager/operation-leads/deployment/{id}', [App\Http\Controllers\OperationManager\OperationLeadController::class, 'destroyDeploymentDetail']);
    Route::post('/operation-manager/operation-leads/deployment/{id}/toggle-verify-payment', [App\Http\Controllers\OperationManager\OperationLeadController::class, 'toggleVerifyPayment']);
    Route::post('/operation-manager/operation-leads/deployment/{id}/dates', [App\Http\Controllers\OperationManager\OperationLeadController::class, 'getDeploymentDates']);
    Route::post('/operation-manager/operation-leads/deployment/{id}/save-absent', [App\Http\Controllers\OperationManager\OperationLeadController::class, 'saveDeploymentAbsentDates']);
    Route::get('/operation-manager/executives', [App\Http\Controllers\OperationManager\OperationLeadController::class, 'getExecutives']);
    Route::get('/operation-manager/vendors', [App\Http\Controllers\OperationManager\OperationLeadController::class, 'getVendors']);

    // Operation Manager All Job Requests API Routes
    Route::get('/operation-manager/jobproc/all-jobrequests', [App\Http\Controllers\OperationManager\JobProcController::class, 'getAllJobRequestsForApp']);
    Route::get('/operation-manager/jobproc/{id}', [App\Http\Controllers\OperationManager\JobProcController::class, 'show']);

    // Operation Manager Coordinator Chats API Routes
    Route::get('/operation-manager/coordinator-chats', [App\Http\Controllers\OperationManager\CoordinatorChatController::class, 'getCoordinatorsForApp']);
    Route::get('/operation-manager/coordinator-chats/messages/{coordinatorId}/{participantId}', [App\Http\Controllers\OperationManager\CoordinatorChatController::class, 'getMessages']);
    Route::post('/operation-manager/coordinator-chats/send', [App\Http\Controllers\OperationManager\CoordinatorChatController::class, 'sendMessage']);
    Route::post('/operation-manager/coordinator-chats/mark-read/{userId}', [App\Http\Controllers\OperationManager\CoordinatorChatController::class, 'markAsRead']);
    Route::get('/operation-manager/coordinator-chats/unread-counts', [App\Http\Controllers\OperationManager\CoordinatorChatController::class, 'getUnreadCounts']);

    // Operation Manager Dashboard API Routes
    Route::get('/operation-manager/dashboard/stats', [App\Http\Controllers\OperationManager\DashboardController::class, 'getStats']);
    Route::get('/operation-manager/dashboard/leads-stats', [App\Http\Controllers\OperationManager\DashboardController::class, 'getLeadsStats']);
    Route::get('/operation-manager/dashboard/users-stats', [App\Http\Controllers\OperationManager\DashboardController::class, 'getUsersStats']);
    Route::get('/operation-manager/dashboard/team-members', [App\Http\Controllers\OperationManager\DashboardController::class, 'getTeamMembers']);
    Route::get('/operation-manager/dashboard/tasks', [App\Http\Controllers\OperationManager\DashboardController::class, 'getTasks']);
    Route::get('/operation-manager/dashboard/tasks/{id}', [App\Http\Controllers\OperationManager\DashboardController::class, 'getTaskDetails']);
    Route::post('/operation-manager/dashboard/tasks', [App\Http\Controllers\OperationManager\DashboardController::class, 'storeTask']);
    Route::put('/operation-manager/dashboard/tasks/{id}', [App\Http\Controllers\OperationManager\DashboardController::class, 'updateTask']);
    Route::put('/operation-manager/dashboard/tasks/{id}/status', [App\Http\Controllers\OperationManager\DashboardController::class, 'updateTaskStatus']);
    Route::delete('/operation-manager/dashboard/tasks/{id}', [App\Http\Controllers\OperationManager\DashboardController::class, 'deleteTask']);
    Route::get('/operation-manager/dashboard/task-history', [App\Http\Controllers\OperationManager\DashboardController::class, 'getTaskHistory']);
    Route::get('/operation-manager/dashboard/outstanding-amount-stats', [App\Http\Controllers\OperationManager\DashboardController::class, 'getOutstandingAmountStats']);
    Route::get('/operation-manager/dashboard/deployment-pending-stats', [App\Http\Controllers\OperationManager\DashboardController::class, 'getDeploymentPendingStats']);
    Route::get('/operation-manager/dashboard/profile-pending-stats', [App\Http\Controllers\OperationManager\DashboardController::class, 'getProfilePendingStats']);
    Route::get('/operation-manager/dashboard/ongoing-stats', [App\Http\Controllers\OperationManager\DashboardController::class, 'getOngoingStats']);
    Route::get('/operation-manager/dashboard/payment-due-stats', [App\Http\Controllers\OperationManager\DashboardController::class, 'getPaymentDueStats']);
    Route::get('/operation-manager/dashboard/job-request-stats', [App\Http\Controllers\OperationManager\DashboardController::class, 'getJobRequestStats']);
    Route::get('/operation-manager/dashboard/calendar-data', [App\Http\Controllers\OperationManager\DashboardController::class, 'getCalendarData']);
    Route::get('/operation-manager/dashboard/date-leads', [App\Http\Controllers\OperationManager\DashboardController::class, 'getDateLeads']);
    Route::get('/operation-manager/dashboard/unverified-deployment-payments-stats', [App\Http\Controllers\OperationManager\DashboardController::class, 'getUnverifiedDeploymentPaymentsStats']);
    Route::get('/operation-manager/dashboard/vendor-freelancer-payment-stats', [App\Http\Controllers\OperationManager\DashboardController::class, 'getVendorFreelancerPaymentStats']);
    Route::get('/operation-manager/dashboard/recent-leads-stats', [App\Http\Controllers\OperationManager\DashboardController::class, 'getRecentLeadsStats']);
    Route::get('/operation-manager/dashboard/pending-callbacks', [App\Http\Controllers\OperationManager\DashboardController::class, 'getPendingCallbacks']);
    Route::get('/operation-manager/dashboard/recent-calls', [App\Http\Controllers\OperationManager\DashboardController::class, 'getRecentCalls']);

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
