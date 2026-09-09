<?php

namespace App\Services;

use App\Models\Lead;
use App\Models\OperationLead;
use App\Models\User;
use App\Models\WhatsappMsgGroup;
use App\Models\WhatsAppMessage;
use Illuminate\Support\Facades\Log;
use App\Facades\UserAssignment;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;
use App\Services\ExpoNotificationService;
use App\Services\ApiRelayUrlResolver;


class MCubeService
{
    public function handleCallData(array $data)
    {
        try {
            $callDirection = strtolower(trim((string)($data['direction'] ?? '')));

            // Create recording data in the required format
            $recordingData = [
                [
                    'url' => $data['filename'],
                    'metadata' => json_encode([
                        'datetime' => $data['starttime'],
                        'caller_agent' => $data['agentname'],
                        'dialstatus' => $data['dialstatus'] ?? null,
                        'emp_phone' => $data['emp_phone'] ?? null,
                        'call_direction' => $callDirection ?: null,
                    ])
                ]
            ];

            // Check if lead exists with this contact number
            $existingLead = Lead::where('contact_no', $data['callto'])->first();

            if ($existingLead) {
                // Get current recordings
                $recordings = [];
                if (!empty($existingLead->recording_url)) {
                    try {
                        $recordings = is_array($existingLead->recording_url)
                            ? $existingLead->recording_url
                            : json_decode($existingLead->recording_url, true);
                    } catch (\Exception $e) {
                        $recordings = [];
                    }
                }
                // Append new recording
                $recordings[] = [
                    'url' => $data['filename'],
                    'metadata' => json_encode([
                        'datetime' => $data['starttime'],
                        'caller_agent' => $data['agentname'],
                        'dialstatus' => $data['dialstatus'] ?? null,
                        'emp_phone' => $data['emp_phone'] ?? null,
                        'call_direction' => $callDirection ?: null,
                    ])
                ];
                $existingLead->update([
                    'date' => $data['starttime'],
                    'recording_url' => json_encode($recordings),
                    'last_call_status' => $data['dialstatus'] ?? null
                ]);

                // Also update OperationLead if exists with same contact number
                $operationLead = OperationLead::where('contact_no', $data['callto'])->first();
                if ($operationLead) {
                    $operationLead->update([
                        'last_call_status' => $data['dialstatus'] ?? null
                    ]);
                }
                try {
                    $whatsappStatus = config('services.whatsapp.status', env('WHATSAPP_MSG_STATUS'));
                    $authKey = config('services.whatsapp.auth_key', env('WHATSAPP_AUTH_KEY'));
                    if ($whatsappStatus !== true) {
                        return response()->json(['error' => 'WhatsApp messaging is disabled'], 403);
                    }
                    if (empty($authKey)) {
                        return response()->json(['error' => 'WhatsApp configuration error'], 500);
                    }
                    $getUser = User::where('mobile', $data['emp_phone'])->first();
                    $lead_id = $existingLead->formatted_id;
                    if ($getUser) {
                        // Check last WhatsApp message for this user/lead where is_sent=0
                        $lastMsg =
                            \App\Models\WhatsAppMessage::where('msg_from', $getUser->mobile)
                            ->where('is_sent', 0)
                            ->orderByDesc('time')
                            ->first();
                        $now = Carbon::now();
                        $shouldSendTemplate = true;
                        if ($lastMsg && $lastMsg->time) {
                            $lastTime = Carbon::parse($lastMsg->time);
                            if ($now->diffInHours($lastTime) < 24) {
                                $shouldSendTemplate = false;
                            }
                        }
                        if ($shouldSendTemplate) {
                            // Send template message
                            $url = ApiRelayUrlResolver::resolve("https://rengage.mcube.com/api/wpbox/sendtemplatemessage");
                            $payload = [
                                "phone" => $getUser->mobile,
                                "template_name" => "lead_notify",
                                "template_language" => "en_US",
                                "components" => [
                                    [
                                        "type" => "body",
                                        "parameters" => [
                                            ["type" => "text", "text" => "IVR"],
                                            ["type" => "text", "text" => $lead_id]
                                        ]
                                    ]
                                ]
                            ];
                            $response = Http::withHeaders([
                                'Content-Type' => 'application/json',
                                'Authorization' => 'Bearer ' . $authKey
                            ])->post($url, $payload);
                            if ($response->successful()) {
                                $responseData = $response->json();
                                if (isset($responseData['status']) && $responseData['status'] === 'success') {
                                    $currentTimestamp = Carbon::now();
                                    $newWaMsg = new WhatsAppMessage();
                                    $newWaMsg->msg_id = $responseData['message_id'] ?? $getUser->mobile;
                                    $newWaMsg->msg_from = "$getUser->mobile";
                                    $newWaMsg->time = $currentTimestamp;
                                    $newWaMsg->type = 'template';
                                    $newWaMsg->is_sent = "1";
                                    $newWaMsg->body = "Hi you have received new IVR lead. Lead Id $lead_id (TEMPLATE)";
                                    $newWaMsg->save();
                                }
                            }
                        } else {
                            // Send normal message
                            $url = ApiRelayUrlResolver::resolve("https://rengage.mcube.com/api/wpbox/sendmessage");
                            $payload = [
                                "phone" => $getUser->mobile,
                                "message" => 'Hi you recived ivr call. Lead id : ' . $lead_id
                            ];
                            $response = Http::withHeaders([
                                'Content-Type' => 'application/json',
                                'Authorization' => 'Bearer ' . $authKey
                            ])->post($url, $payload);
                            if ($response->successful()) {
                                $responseData = $response->json();
                                if (isset($responseData['status']) && $responseData['status'] === 'success') {
                                    $currentTimestamp = Carbon::now();
                                    $newWaMsg = new WhatsAppMessage();
                                    $newWaMsg->msg_id = $responseData['message_id'] ?? $getUser->mobile;
                                    $newWaMsg->msg_from = "$getUser->mobile";
                                    $newWaMsg->time = $currentTimestamp;
                                    $newWaMsg->type = 'text';
                                    $newWaMsg->is_sent = "1";
                                    $newWaMsg->body = "Hi you recived ivr call. Lead id : $lead_id";
                                    $newWaMsg->save();
                                }
                            }
                        }
                    }
                } catch (\Exception $e) {
                }
                // Send Expo push notification to executive with dialstatus
                if ($getUser = User::where('mobile', $data['emp_phone'])->first()) {
                    $dialStatus = $data['dialstatus'] ?? 'N/A';
                    ExpoNotificationService::send(
                        [$getUser->id],
                        'Lead Updated',
                        'Lead ID: ' . $existingLead->formatted_id . ' updated. Dial Status: ' . $dialStatus
                    );
                }
                return $existingLead;
            }

            // Create new lead if not exists
            $getUser = User::where('mobile', $data['emp_phone'])->first();

            if (!$getUser) {
                throw new \Exception('User not found for emp_phone: ' . $data['emp_phone']);
            }

            $lead = Lead::create([
                'date' => $data['starttime'],
                'executive' => $getUser->id,
                'contact_type' => 'call',
                'contact_no' => $data['callto'],
                'lead_source' => 'ivrs',
                'stage' => 'active',
                'recording_url' => json_encode($recordingData),
                'last_call_status' => $data['dialstatus'] ?? null
            ]);

            // Also update OperationLead if exists with same contact number
            $operationLead = OperationLead::where('contact_no', $data['callto'])->first();
            if ($operationLead) {
                $operationLead->update([
                    'last_call_status' => $data['dialstatus'] ?? null
                ]);
            }

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
                // Check last WhatsApp message for this user where is_sent=0
                $lastMsg =
                    \App\Models\WhatsAppMessage::where('msg_from', $getUser->mobile)
                    ->where('is_sent', 0)
                    ->orderByDesc('time')
                    ->first();
                $now = Carbon::now();
                $shouldSendTemplate = true;
                if ($lastMsg && $lastMsg->time) {
                    $lastTime = Carbon::parse($lastMsg->time);
                    if ($now->diffInHours($lastTime) < 24) {
                        $shouldSendTemplate = false;
                    }
                }
                if ($shouldSendTemplate) {
                    // Send template message
                    $url = ApiRelayUrlResolver::resolve("https://rengage.mcube.com/api/wpbox/sendtemplatemessage");
                    $payload = [
                        "phone" => $getUser->mobile,
                        "template_name" => "lead_notify",
                        "template_language" => "en_US",
                        "components" => [
                            [
                                "type" => "body",
                                "parameters" => [
                                    ["type" => "text", "text" => "IVR"],
                                    ["type" => "text", "text" => $lead_id]
                                ]
                            ]
                        ]
                    ];
                    $response = Http::withHeaders([
                        'Content-Type' => 'application/json',
                        'Authorization' => 'Bearer ' . $authKey
                    ])->post($url, $payload);
                    if ($response->successful()) {
                        $responseData = $response->json();
                        if (isset($responseData['status']) && $responseData['status'] === 'success') {
                            $currentTimestamp = Carbon::now();
                            $newWaMsg = new WhatsAppMessage();
                            $newWaMsg->msg_id = $responseData['message_id'] ?? $getUser->mobile;
                            $newWaMsg->msg_from = "$getUser->mobile";
                            $newWaMsg->time = $currentTimestamp;
                            $newWaMsg->type = 'template';
                            $newWaMsg->is_sent = "1";
                            $newWaMsg->body = "Hi you have received new IVR lead. Lead Id $lead_id (TEMPLATE)";
                            $newWaMsg->save();
                            $new_whatsapp_grp = new WhatsappMsgGroup();
                            $new_whatsapp_grp->whatsapp_number = $data['callto'];
                            $new_whatsapp_grp->executive_ids = $getUser->id;
                            $new_whatsapp_grp->save();
                        }
                    }
                } else {
                    // Send normal message
                    $url = ApiRelayUrlResolver::resolve("https://rengage.mcube.com/api/wpbox/sendmessage");
                    $payload = [
                        "phone" => $getUser->mobile,
                        "message" => 'Hi you have received new ivr lead. Lead id : ' . $lead_id
                    ];
                    $response = Http::withHeaders([
                        'Content-Type' => 'application/json',
                        'Authorization' => 'Bearer ' . $authKey
                    ])->post($url, $payload);
                    if ($response->successful()) {
                        $responseData = $response->json();
                        if (isset($responseData['status']) && $responseData['status'] === 'success') {
                            $currentTimestamp = Carbon::now();
                            $newWaMsg = new WhatsAppMessage();
                            $newWaMsg->msg_id = $responseData['message_id'] ?? $getUser->mobile;
                            $newWaMsg->msg_from = "$getUser->mobile";
                            $newWaMsg->time = $currentTimestamp;
                            $newWaMsg->type = 'text';
                            $newWaMsg->is_sent = "1";
                            $newWaMsg->body = "Hi you have received new ivr lead. Lead id : $lead_id";
                            $newWaMsg->save();
                            $new_whatsapp_grp = new WhatsappMsgGroup();
                            $new_whatsapp_grp->whatsapp_number = $data['callto'];
                            $new_whatsapp_grp->executive_ids = $getUser->id;
                            $new_whatsapp_grp->save();
                        }
                    }
                }
            } catch (\Exception $e) {
            }

            // Send Expo push notification to executive with dialstatus
            $dialStatus = $data['dialstatus'] ?? 'N/A';
            ExpoNotificationService::send(
                [$getUser->id],
                'New IVR Lead',
                'A new IVR lead has been created. Lead ID: ' . $lead->formatted_id . '. Dial Status: ' . $dialStatus
            );

            return response()->json([
                'status' => 'success',
                'message' => 'Lead created successfully',
            ]);
        } catch (\Exception $e) {
            Log::error('MCube call data processing error: ' . $e->getMessage());
            throw $e;
        }
    }
}
