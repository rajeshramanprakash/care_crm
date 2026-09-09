<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Models\User;
use App\Models\WhatsAppMessage;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

class WhatsappTemplateMsgController extends Controller
{
    public function get_name_from_number($number)
    {
        $lead = Lead::where('contact_no', $number)->first();
        $name = $lead->name ?? 'Sir/Madam';
        Log::info("Getting name for number: $number", ['name' => $name]);
        return $name;
    }

    public function whatsapp_template_msg_send_reconnect_greeting(Request $request)
    {
        Log::info('Starting reconnect_greeting template send', ['request_data' => $request->all()]);

        try {
            $authKey = config('services.whatsapp.auth_key', env('WHATSAPP_AUTH_KEY'));
            $number = $request->number;
            $name = $this->get_name_from_number($number);

            Log::info('Template parameters', [
                'number' => $number,
                'name' => $name,
                'template' => 'reconnect_greeting'
            ]);

            $url = 'https://rengage.mcube.com/api/wpbox/sendtemplatemessage';
            $payload = [
                'phone' => $number,
                'template_name' => 'reconnect_greeting',
                'template_language' => 'en_GB',
                'components' => [
                    [
                        'type' => 'header',
                        'parameters' => [
                            ['type' => 'text', 'text' => "$name"],
                        ]
                    ]
                ]
            ];

            Log::info('Sending API request', ['url' => $url, 'payload' => $payload]);

            $response = \Illuminate\Support\Facades\Http::withHeaders([
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $authKey
            ])->post($url, $payload);

            Log::info('API response received', [
                'status_code' => $response->status(),
                'response_body' => $response->body()
            ]);

            if ($response->successful()) {
                $responseData = $response->json();
                Log::info('API response parsed', ['response_data' => $responseData]);

                if (isset($responseData['status']) && $responseData['status'] === 'success') {
                    $currentTimestamp = \Carbon\Carbon::now();

                    // Extract the actual message ID from the complex response structure
                    $messageId = $number;  // Default fallback
                    if (isset($responseData['message_id'])) {
                        if (is_array($responseData['message_id']) && isset($responseData['message_id']['messages'][0]['id'])) {
                            $messageId = $responseData['message_id']['messages'][0]['id'];
                        } elseif (is_string($responseData['message_id'])) {
                            $messageId = $responseData['message_id'];
                        }
                    }

                    Log::info('Creating WhatsApp message record', [
                        'msg_id' => $messageId,
                        'msg_from' => $number,
                        'type' => 'template'
                    ]);

                    $newWaMsg = WhatsAppMessage::create([
                        'msg_id' => $messageId,
                        'msg_from' => "$number",
                        'time' => $currentTimestamp,
                        'type' => 'template',
                        'is_sent' => '1',
                        'body' => "*Hi $name* \n We hope you're doing well. Please let us know if there's any support needed with your ongoing healthcare service."
                    ]);

                    Log::info('WhatsApp message created successfully', ['message_id' => $newWaMsg->id]);
                } else {
                    Log::warning("API returned success but status is not 'success'", ['response_data' => $responseData]);
                }
            } else {
                Log::error('API request failed', [
                    'status_code' => $response->status(),
                    'response_body' => $response->body()
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Error in reconnect_greeting template', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }

        Log::info('reconnect_greeting template send completed');
        return response()->json(['success' => true]);
    }

    public function whatsapp_template_msg_send_post_call_followup_healthcare(Request $request)
    {
        Log::info('Starting post_call_followup_healthcare template send', ['request_data' => $request->all()]);

        try {
            $authKey = config('services.whatsapp.auth_key', env('WHATSAPP_AUTH_KEY'));
            $number = $request->number;
            $name = $this->get_name_from_number($number);

            Log::info('Template parameters', [
                'number' => $number,
                'name' => $name,
                'template' => 'post_call_followup_healthcare'
            ]);

            $url = 'https://rengage.mcube.com/api/wpbox/sendtemplatemessage';
            $payload = [
                'phone' => $number,
                'template_name' => 'post_call_followup_healthcare',
                'template_language' => 'en_GB',
                'components' => [
                    [
                        'type' => 'header',
                        'parameters' => [
                            ['type' => 'text', 'text' => "$name"],
                        ]
                    ]
                ]
            ];

            Log::info('Sending API request', ['url' => $url, 'payload' => $payload]);

            $response = \Illuminate\Support\Facades\Http::withHeaders([
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $authKey
            ])->post($url, $payload);

            Log::info('API response received', [
                'status_code' => $response->status(),
                'response_body' => $response->body()
            ]);

            if ($response->successful()) {
                $responseData = $response->json();
                Log::info('API response parsed', ['response_data' => $responseData]);

                if (isset($responseData['status']) && $responseData['status'] === 'success') {
                    $currentTimestamp = \Carbon\Carbon::now();

                    // Extract the actual message ID from the complex response structure
                    $messageId = $number;  // Default fallback
                    if (isset($responseData['message_id'])) {
                        if (is_array($responseData['message_id']) && isset($responseData['message_id']['messages'][0]['id'])) {
                            $messageId = $responseData['message_id']['messages'][0]['id'];
                        } elseif (is_string($responseData['message_id'])) {
                            $messageId = $responseData['message_id'];
                        }
                    }

                    Log::info('Creating WhatsApp message record', [
                        'msg_id' => $messageId,
                        'msg_from' => $number,
                        'type' => 'template'
                    ]);

                    $newWaMsg = WhatsAppMessage::create([
                        'msg_id' => $messageId,
                        'msg_from' => "$number",
                        'time' => $currentTimestamp,
                        'type' => 'template',
                        'is_sent' => '1',
                        'body' => "*Hi $name* \n Thank you for speaking with us at Carelix Healthcare. We're here to assist you with home healthcare services. Feel free to share your requirement or any questions here."
                    ]);

                    Log::info('WhatsApp message created successfully', ['message_id' => $newWaMsg->id]);
                } else {
                    Log::warning("API returned success but status is not 'success'", ['response_data' => $responseData]);
                }
            } else {
                Log::error('API request failed', [
                    'status_code' => $response->status(),
                    'response_body' => $response->body()
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Error in post_call_followup_healthcare template', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }

        Log::info('post_call_followup_healthcare template send completed');
        return response()->json(['success' => true]);
    }

    public function whatsapp_template_msg_send_payment_followup_healthcare(Request $request)
    {
        Log::info('Starting payment_followup_healthcare template send', ['request_data' => $request->all()]);

        try {
            $authKey = config('services.whatsapp.auth_key', env('WHATSAPP_AUTH_KEY'));
            $number = $request->number;
            $name = $this->get_name_from_number($number);

            Log::info('Template parameters', [
                'number' => $number,
                'name' => $name,
                'template' => 'payment_followup_healthcare'
            ]);

            $url = 'https://rengage.mcube.com/api/wpbox/sendtemplatemessage';
            $payload = [
                'phone' => $number,
                'template_name' => 'payment_followup_healthcare',
                'template_language' => 'en_GB',
                'components' => [
                    [
                        'type' => 'header',
                        'parameters' => [
                            ['type' => 'text', 'text' => "$name"],
                        ]
                    ]
                ]
            ];

            Log::info('Sending API request', ['url' => $url, 'payload' => $payload]);

            $response = \Illuminate\Support\Facades\Http::withHeaders([
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $authKey
            ])->post($url, $payload);

            Log::info('API response received', [
                'status_code' => $response->status(),
                'response_body' => $response->body()
            ]);

            if ($response->successful()) {
                $responseData = $response->json();
                Log::info('API response parsed', ['response_data' => $responseData]);

                if (isset($responseData['status']) && $responseData['status'] === 'success') {
                    $currentTimestamp = \Carbon\Carbon::now();

                    // Extract the actual message ID from the complex response structure
                    $messageId = $number;  // Default fallback
                    if (isset($responseData['message_id'])) {
                        if (is_array($responseData['message_id']) && isset($responseData['message_id']['messages'][0]['id'])) {
                            $messageId = $responseData['message_id']['messages'][0]['id'];
                        } elseif (is_string($responseData['message_id'])) {
                            $messageId = $responseData['message_id'];
                        }
                    }

                    Log::info('Creating WhatsApp message record', [
                        'msg_id' => $messageId,
                        'msg_from' => $number,
                        'type' => 'template'
                    ]);

                    $newWaMsg = WhatsAppMessage::create([
                        'msg_id' => $messageId,
                        'msg_from' => "$number",
                        'time' => $currentTimestamp,
                        'type' => 'template',
                        'is_sent' => '1',
                        'body' => "*Hi $name* \n This is a gentle reminder from Carelix Healthcare regarding your pending payment for home care services provided. Kindly let us know if you've already made the payment or need any assistance with the payment process."
                    ]);

                    Log::info('WhatsApp message created successfully', ['message_id' => $newWaMsg->id]);
                } else {
                    Log::warning("API returned success but status is not 'success'", ['response_data' => $responseData]);
                }
            } else {
                Log::error('API request failed', [
                    'status_code' => $response->status(),
                    'response_body' => $response->body()
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Error in payment_followup_healthcare template', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }

        Log::info('payment_followup_healthcare template send completed');
        return response()->json(['success' => true]);
    }

    public function whatsapp_template_msg_send_renewal_reminder_healthcare(Request $request)
    {
        Log::info('Starting renewal_reminder_healthcare template send', ['request_data' => $request->all()]);

        try {
            $authKey = config('services.whatsapp.auth_key', env('WHATSAPP_AUTH_KEY'));
            $number = $request->number;
            $name = $this->get_name_from_number($number);

            Log::info('Template parameters', [
                'number' => $number,
                'name' => $name,
                'template' => 'renewal_reminder_healthcare'
            ]);

            $url = 'https://rengage.mcube.com/api/wpbox/sendtemplatemessage';
            $payload = [
                'phone' => $number,
                'template_name' => 'renewal_reminder_healthcare',
                'template_language' => 'en_GB',
                'components' => [
                    [
                        'type' => 'header',
                        'parameters' => [
                            ['type' => 'text', 'text' => "$name"],
                        ]
                    ]
                ]
            ];

            Log::info('Sending API request', ['url' => $url, 'payload' => $payload]);

            $response = \Illuminate\Support\Facades\Http::withHeaders([
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $authKey
            ])->post($url, $payload);

            Log::info('API response received', [
                'status_code' => $response->status(),
                'response_body' => $response->body()
            ]);

            if ($response->successful()) {
                $responseData = $response->json();
                Log::info('API response parsed', ['response_data' => $responseData]);

                if (isset($responseData['status']) && $responseData['status'] === 'success') {
                    $currentTimestamp = \Carbon\Carbon::now();

                    // Extract the actual message ID from the complex response structure
                    $messageId = $number;  // Default fallback
                    if (isset($responseData['message_id'])) {
                        if (is_array($responseData['message_id']) && isset($responseData['message_id']['messages'][0]['id'])) {
                            $messageId = $responseData['message_id']['messages'][0]['id'];
                        } elseif (is_string($responseData['message_id'])) {
                            $messageId = $responseData['message_id'];
                        }
                    }

                    Log::info('Creating WhatsApp message record', [
                        'msg_id' => $messageId,
                        'msg_from' => $number,
                        'type' => 'template'
                    ]);

                    $newWaMsg = WhatsAppMessage::create([
                        'msg_id' => $messageId,
                        'msg_from' => "$number",
                        'time' => $currentTimestamp,
                        'type' => 'template',
                        'is_sent' => '1',
                        'body' => "*Hi $name* \n This is a gentle reminder from Carelix Healthcare. Your current home care service is nearing the end of its schedule. Please let us know if you'd like to extend or renew the service."
                    ]);

                    Log::info('WhatsApp message created successfully', ['message_id' => $newWaMsg->id]);
                } else {
                    Log::warning("API returned success but status is not 'success'", ['response_data' => $responseData]);
                }
            } else {
                Log::error('API request failed', [
                    'status_code' => $response->status(),
                    'response_body' => $response->body()
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Error in renewal_reminder_healthcare template', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }

        Log::info('renewal_reminder_healthcare template send completed');
        return response()->json(['success' => true]);
    }

    public function whatsapp_template_msg_send_morning_greeting_update(Request $request)
    {
        Log::info('Starting morning_greeting_update template send', ['request_data' => $request->all()]);

        try {
            $authKey = config('services.whatsapp.auth_key', env('WHATSAPP_AUTH_KEY'));
            $number = $request->number;
            $name = $this->get_name_from_number($number);

            Log::info('Template parameters', [
                'number' => $number,
                'name' => $name,
                'template' => 'morning_greeting_update'
            ]);

            $url = 'https://rengage.mcube.com/api/wpbox/sendtemplatemessage';
            $payload = [
                'phone' => $number,
                'template_name' => 'morning_greeting_update',
                'template_language' => 'en_GB',
                'components' => [
                    [
                        'type' => 'header',
                        'parameters' => [
                            ['type' => 'text', 'text' => "$name"],
                        ]
                    ]
                ]
            ];

            Log::info('Sending API request', ['url' => $url, 'payload' => $payload]);

            $response = \Illuminate\Support\Facades\Http::withHeaders([
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $authKey
            ])->post($url, $payload);

            Log::info('API response received', [
                'status_code' => $response->status(),
                'response_body' => $response->body()
            ]);

            if ($response->successful()) {
                $responseData = $response->json();
                Log::info('API response parsed', ['response_data' => $responseData]);

                if (isset($responseData['status']) && $responseData['status'] === 'success') {
                    $currentTimestamp = \Carbon\Carbon::now();

                    // Extract the actual message ID from the complex response structure
                    $messageId = $number;  // Default fallback
                    if (isset($responseData['message_id'])) {
                        if (is_array($responseData['message_id']) && isset($responseData['message_id']['messages'][0]['id'])) {
                            $messageId = $responseData['message_id']['messages'][0]['id'];
                        } elseif (is_string($responseData['message_id'])) {
                            $messageId = $responseData['message_id'];
                        }
                    }

                    Log::info('Creating WhatsApp message record', [
                        'msg_id' => $messageId,
                        'msg_from' => $number,
                        'type' => 'template'
                    ]);

                    $newWaMsg = WhatsAppMessage::create([
                        'msg_id' => $messageId,
                        'msg_from' => "$number",
                        'time' => $currentTimestamp,
                        'type' => 'template',
                        'is_sent' => '1',
                        'body' => "*Good morning $name* \n Wishing you a healthy day from Carelix Healthcare. Let us know if you need any assistance with your ongoing service."
                    ]);

                    Log::info('WhatsApp message created successfully', ['message_id' => $newWaMsg->id]);
                } else {
                    Log::warning("API returned success but status is not 'success'", ['response_data' => $responseData]);
                }
            } else {
                Log::error('API request failed', [
                    'status_code' => $response->status(),
                    'response_body' => $response->body()
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Error in morning_greeting_update template', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }

        Log::info('morning_greeting_update template send completed');
        return response()->json(['success' => true]);
    }

    public function whatsapp_template_msg_send_evening_greeting_update(Request $request)
    {
        Log::info('Starting evening_greeting_update template send', ['request_data' => $request->all()]);

        try {
            $authKey = config('services.whatsapp.auth_key', env('WHATSAPP_AUTH_KEY'));
            $number = $request->number;
            $name = $this->get_name_from_number($number);

            Log::info('Template parameters', [
                'number' => $number,
                'name' => $name,
                'template' => 'evening_greeting_update'
            ]);

            $url = 'https://rengage.mcube.com/api/wpbox/sendtemplatemessage';
            $payload = [
                'phone' => $number,
                'template_name' => 'evening_greeting_update',
                'template_language' => 'en_GB',
                'components' => [
                    [
                        'type' => 'header',
                        'parameters' => [
                            ['type' => 'text', 'text' => "$name"],
                        ]
                    ]
                ]
            ];

            Log::info('Sending API request', ['url' => $url, 'payload' => $payload]);

            $response = \Illuminate\Support\Facades\Http::withHeaders([
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $authKey
            ])->post($url, $payload);

            Log::info('API response received', [
                'status_code' => $response->status(),
                'response_body' => $response->body()
            ]);

            if ($response->successful()) {
                $responseData = $response->json();
                Log::info('API response parsed', ['response_data' => $responseData]);

                if (isset($responseData['status']) && $responseData['status'] === 'success') {
                    $currentTimestamp = \Carbon\Carbon::now();

                    // Extract the actual message ID from the complex response structure
                    $messageId = $number;  // Default fallback
                    if (isset($responseData['message_id'])) {
                        if (is_array($responseData['message_id']) && isset($responseData['message_id']['messages'][0]['id'])) {
                            $messageId = $responseData['message_id']['messages'][0]['id'];
                        } elseif (is_string($responseData['message_id'])) {
                            $messageId = $responseData['message_id'];
                        }
                    }

                    Log::info('Creating WhatsApp message record', [
                        'msg_id' => $messageId,
                        'msg_from' => $number,
                        'type' => 'template'
                    ]);

                    $newWaMsg = WhatsAppMessage::create([
                        'msg_id' => $messageId,
                        'msg_from' => "$number",
                        'time' => $currentTimestamp,
                        'type' => 'template',
                        'is_sent' => '1',
                        'body' => "*Good evening $name* \n Hope everything is going well with your care. If you need anything from Carelix Healthcare, feel free to message us here."
                    ]);

                    Log::info('WhatsApp message created successfully', ['message_id' => $newWaMsg->id]);
                } else {
                    Log::warning("API returned success but status is not 'success'", ['response_data' => $responseData]);
                }
            } else {
                Log::error('API request failed', [
                    'status_code' => $response->status(),
                    'response_body' => $response->body()
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Error in evening_greeting_update template', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }

        Log::info('evening_greeting_update template send completed');
        return response()->json(['success' => true]);
    }

    public function whatsapp_template_msg_send_basic_greeting_service_checkin(Request $request)
    {
        Log::info('Starting basic_greeting_service_checkin template send', ['request_data' => $request->all()]);

        try {
            $authKey = config('services.whatsapp.auth_key', env('WHATSAPP_AUTH_KEY'));
            $number = $request->number;
            $name = $this->get_name_from_number($number);

            Log::info('Template parameters', [
                'number' => $number,
                'name' => $name,
                'template' => 'basic_greeting_service_checkin'
            ]);

            $url = 'https://rengage.mcube.com/api/wpbox/sendtemplatemessage';
            $payload = [
                'phone' => $number,
                'template_name' => 'basic_greeting_service_checkin',
                'template_language' => 'en_GB',
                'components' => [
                    [
                        'type' => 'header',
                        'parameters' => [
                            ['type' => 'text', 'text' => "$name"],
                        ]
                    ]
                ]
            ];

            Log::info('Sending API request', ['url' => $url, 'payload' => $payload]);

            $response = \Illuminate\Support\Facades\Http::withHeaders([
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $authKey
            ])->post($url, $payload);

            Log::info('API response received', [
                'status_code' => $response->status(),
                'response_body' => $response->body()
            ]);

            if ($response->successful()) {
                $responseData = $response->json();
                Log::info('API response parsed', ['response_data' => $responseData]);

                if (isset($responseData['status']) && $responseData['status'] === 'success') {
                    $currentTimestamp = \Carbon\Carbon::now();

                    // Extract the actual message ID from the complex response structure
                    $messageId = $number;  // Default fallback
                    if (isset($responseData['message_id'])) {
                        if (is_array($responseData['message_id']) && isset($responseData['message_id']['messages'][0]['id'])) {
                            $messageId = $responseData['message_id']['messages'][0]['id'];
                        } elseif (is_string($responseData['message_id'])) {
                            $messageId = $responseData['message_id'];
                        }
                    }

                    Log::info('Creating WhatsApp message record', [
                        'msg_id' => $messageId,
                        'msg_from' => $number,
                        'type' => 'template'
                    ]);

                    $newWaMsg = WhatsAppMessage::create([
                        'msg_id' => $messageId,
                        'msg_from' => "$number",
                        'time' => $currentTimestamp,
                        'type' => 'template',
                        'is_sent' => '1',
                        'body' => "*Hi $name* \n Just checking in from Carelix Healthcare. Let us know if there's anything you need regarding your current service."
                    ]);

                    Log::info('WhatsApp message created successfully', ['message_id' => $newWaMsg->id]);
                } else {
                    Log::warning("API returned success but status is not 'success'", ['response_data' => $responseData]);
                }
            } else {
                Log::error('API request failed', [
                    'status_code' => $response->status(),
                    'response_body' => $response->body()
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Error in basic_greeting_service_checkin template', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }

        Log::info('basic_greeting_service_checkin template send completed');
        return response()->json(['success' => true]);
    }

    public function whatsapp_template_msg_send_service_status_checkin(Request $request)
    {
        try {
            $authKey = config('services.whatsapp.auth_key', env('WHATSAPP_AUTH_KEY'));
            $number = $request->number;
            $name = $this->get_name_from_number($number);

            $url = 'https://rengage.mcube.com/api/wpbox/sendtemplatemessage';
            $payload = [
                'phone' => $number,
                'template_name' => 'service_status_checkin',
                'template_language' => 'en_GB',
                'components' => [
                    [
                        'type' => 'header',
                        'parameters' => [
                            ['type' => 'text', 'text' => "$name"],
                        ]
                    ]
                ]
            ];
            $response = \Illuminate\Support\Facades\Http::withHeaders([
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $authKey
            ])->post($url, $payload);
            if ($response->successful()) {
                $responseData = $response->json();
                if (isset($responseData['status']) && $responseData['status'] === 'success') {
                    $currentTimestamp = \Carbon\Carbon::now();
                    $messageId = $number;
                    if (isset($responseData['message_id'])) {
                        $messageId = $responseData['message_id'];
                        $newWaMsg = WhatsAppMessage::create([
                            'msg_id' => $messageId,
                            'msg_from' => "$number",
                            'time' => $currentTimestamp,
                            'type' => 'template',
                            'is_sent' => '1',
                            'body' => "*Hi $name* \n Hope everything is going well with our healthcare services. \n Just checking in — is everything going smoothly with the staff? Please share any feedback or support needed."
                        ]);
                    } else {
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error('Error in service_status_checkin template', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
        return response()->json(['success' => true]);
    }
    public function whatsapp_template_msg_send_job_request_followup(Request $request)
    {
        try {
            $authKey = config('services.whatsapp.auth_key', env('WHATSAPP_AUTH_KEY'));
            $number = $request->number;
            $name = $this->get_name_from_number($number);

            $url = 'https://rengage.mcube.com/api/wpbox/sendtemplatemessage';
            $payload = [
                'phone' => $number,
                'template_name' => 'job_request_followup',
                'template_language' => 'en_GB',
                'components' => [
                    [
                        'type' => 'header',
                        'parameters' => [
                            ['type' => 'text', 'text' => "$name"],
                        ]
                    ]
                ]
            ];
            $response = \Illuminate\Support\Facades\Http::withHeaders([
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $authKey
            ])->post($url, $payload);
            if ($response->successful()) {
                $responseData = $response->json();
                if (isset($responseData['status']) && $responseData['status'] === 'success') {
                    $currentTimestamp = \Carbon\Carbon::now();
                    $messageId = $number;
                    if (isset($responseData['message_id'])) {
                        $messageId = $responseData['message_id'];
                        $newWaMsg = WhatsAppMessage::create([
                            'msg_id' => $messageId,
                            'msg_from' => "$number",
                            'time' => $currentTimestamp,
                            'type' => 'template',
                            'is_sent' => '1',
                            'body' => "*Hi $name* \n Thank you for your interest in joining Carelix Healthcare. \n To proceed, please share the following details: \n – Your current address \n – City \n – Comfortable working hours (12hrs / 24hrs) \n – Salary expectations \n – Preferred role (Attendant / Nursing) \n – Education \n – Any certificate (if available) \n – Total experience in this field"
                        ]);
                    } else {
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error('Error in service_status_checkin template', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
        return response()->json(['success' => true]);
    }

    public function whatsapp_template_msg_send_patient_care_details_request(Request $request)
    {
        try {
            $authKey = config('services.whatsapp.auth_key', env('WHATSAPP_AUTH_KEY'));
            $number = $request->number;
            $name = $this->get_name_from_number($number);

            $url = 'https://rengage.mcube.com/api/wpbox/sendtemplatemessage';
            $payload = [
                'phone' => $number,
                'template_name' => 'patient_care_details_request',
                'template_language' => 'en_GB',
                'components' => [
                    [
                        'type' => 'header',
                        'parameters' => [
                            ['type' => 'text', 'text' => "$name"],
                        ]
                    ]
                ]
            ];
            $response = \Illuminate\Support\Facades\Http::withHeaders([
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $authKey
            ])->post($url, $payload);
            if ($response->successful()) {
                $responseData = $response->json();
                if (isset($responseData['status']) && $responseData['status'] === 'success') {
                    $currentTimestamp = \Carbon\Carbon::now();
                    $messageId = $number;
                    if (isset($responseData['message_id'])) {
                        $messageId = $responseData['message_id'];
                        $newWaMsg = WhatsAppMessage::create([
                            'msg_id' => $messageId,
                            'msg_from' => "$number",
                            'time' => $currentTimestamp,
                            'type' => 'template',
                            'is_sent' => '1',
                            'body' => "*Hi $name* \n Thank you for speaking with Carelix Healthcare. To assist you better, please share the patient details: \n – Patient Name \n – Age / Sex \n – Weight \n – Required care hours (12hrs / 24hrs) \n – Full Address \n – City \n – Patient condition \n – Bedridden or non-bedridden"
                        ]);
                    } else {
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error('Error in service_status_checkin template', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
        return response()->json(['success' => true]);
    }

    public function whatsapp_template_msg_send_payment_confirmation_healthcare(Request $request)
    {
        try {
            $authKey = config('services.whatsapp.auth_key', env('WHATSAPP_AUTH_KEY'));
            $number = $request->number;
            $name = $this->get_name_from_number($number);

            $url = 'https://rengage.mcube.com/api/wpbox/sendtemplatemessage';
            $payload = [
                'phone' => $number,
                'template_name' => 'payment_confirmation_healthcare',
                'template_language' => 'en_GB',
                'components' => [
                    [
                        'type' => 'header',
                        'parameters' => [
                            ['type' => 'text', 'text' => "$name"],
                        ]
                    ]
                ]
            ];
            $response = \Illuminate\Support\Facades\Http::withHeaders([
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $authKey
            ])->post($url, $payload);
            if ($response->successful()) {
                $responseData = $response->json();
                if (isset($responseData['status']) && $responseData['status'] === 'success') {
                    $currentTimestamp = \Carbon\Carbon::now();
                    $messageId = $number;
                    if (isset($responseData['message_id'])) {
                        $messageId = $responseData['message_id'];
                        $newWaMsg = WhatsAppMessage::create([
                            'msg_id' => $messageId,
                            'msg_from' => "$number",
                            'time' => $currentTimestamp,
                            'type' => 'template',
                            'is_sent' => '1',
                            'body' => "*Hi $name* \n We’ve received your payment for Carelix Healthcare services. \nThank you for your trust. Please let us know if you need any support."
                        ]);
                    } else {
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error('Error in service_status_checkin template', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
        return response()->json(['success' => true]);
    }

   





    public function whatsapp_template_msg_send_service_status_checkin_cron(Request $request)
    {
        try {
            $authKey = config('services.whatsapp.auth_key', env('WHATSAPP_AUTH_KEY'));

            $leads = OperationLead::where('ongoing_stopped', 'ongoing')->get();
            foreach ($leads as $lead) {
                $number = $lead->contact_no;
                $name = $lead->customer_name;

                $url = 'https://rengage.mcube.com/api/wpbox/sendtemplatemessage';
                $payload = [
                    'phone' => $number,
                    'template_name' => 'service_status_checkin',
                    'template_language' => 'en_GB',
                    'components' => [
                        [
                            'type' => 'header',
                            'parameters' => [
                                ['type' => 'text', 'text' => "$name"],
                            ]
                        ]
                    ]
                ];
                $response = \Illuminate\Support\Facades\Http::withHeaders([
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $authKey
                ])->post($url, $payload);
                if ($response->successful()) {
                    $responseData = $response->json();
                    if (isset($responseData['status']) && $responseData['status'] === 'success') {
                        $currentTimestamp = \Carbon\Carbon::now();
                        $messageId = $number;
                        if (isset($responseData['message_id'])) {
                            $messageId = $responseData['message_id'];
                            $newWaMsg = WhatsAppMessage::create([
                                'msg_id' => $messageId,
                                'msg_from' => "$number",
                                'time' => $currentTimestamp,
                                'type' => 'template',
                                'is_sent' => '1',
                                'body' => "*Hi $name* \n Hope everything is going well with our healthcare services. \n Just checking in — is everything going smoothly with the staff? Please share any feedback or support needed."
                            ]);
                        } else {
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error('Error in service_status_checkin template', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
        return response()->json(['success' => true]);
    }
}
