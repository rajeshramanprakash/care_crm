<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Lead;
use App\Models\WhatsAppMessage;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use App\Events\NewWhatsAppMessage;
use Illuminate\Support\Facades\Session;
use App\Models\WhatsappMsgGroup;
use App\Models\User;
use App\Services\ExpoNotificationService;

class WhatsappMsgController extends Controller
{

    public function whatsapp_msg_get($id)
    {
        $perPage = 5;
        $messages = WhatsAppMessage::where('msg_from', $id)
            ->orderBy('time', 'desc')
            ->paginate($perPage);
        // foreach ($messages as $message) {
        //     if (!empty($message->msg_id)) {
        //         $mediaUrl = $this->get_whatsapp_doc($message->msg_id);
        //         $message->doc = $mediaUrl;
        //     }
        // }
        return response()->json($messages);
    }

    public function whatsapp_msg_get_new($id, Request $request)
    {
        $lastTimestamp = $request->input('lastTimestamp');

        $messages = WhatsAppMessage::where('msg_from', $id)
            ->where('time', '>', $lastTimestamp)
            ->orderBy('time', 'asc')
            ->get();

        // foreach ($messages as $message) {
        //     if (!empty($message->msg_id)) {
        //         $mediaUrl = $this->get_whatsapp_doc($message->msg_id);
        //         $message->doc = $mediaUrl;
        //     }
        // }

        return response()->json($messages);
    }

    public function whatsapp_msg_send(Request $request)
    {
        try {
            Log::info('Starting whatsapp_msg_send', ['recipient' => $request->recipient, 'message' => $request->message]);
            $whatsappStatus = config('services.whatsapp.status', env('WHATSAPP_MSG_STATUS'));
            $authKey = config('services.whatsapp.auth_key', env('WHATSAPP_AUTH_KEY'));
            if ($whatsappStatus !== true) {
                Log::warning('WhatsApp messaging is disabled');
                return response()->json(['error' => 'WhatsApp messaging is disabled'], 403);
            }
            if (empty($authKey)) {
                Log::error('WhatsApp configuration error: authKey missing');
                return response()->json(['error' => 'WhatsApp configuration error'], 500);
            }
            $url = "https://rengage.mcube.com/api/wpbox/sendmessage";
            $payload = [
                "phone" => $request->recipient,
                "message" => $request->message
            ];
            Log::info('Sending WhatsApp API request', ['url' => $url, 'payload' => $payload]);
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $authKey
            ])->post($url, $payload);
            Log::info('WhatsApp API response', ['status' => $response->status(), 'body' => $response->body()]);
            if ($response->successful()) {
                $responseData = $response->json();
                Log::info('WhatsApp API responseData', $responseData);
                if (isset($responseData['status']) && $responseData['status'] === 'success') {
                    $currentTimestamp = Carbon::now();
                    $newWaMsg = new WhatsAppMessage();
                    $newWaMsg->msg_id = $responseData['message_wamid'] ?? $responseData['message_id'] ?? $request->recipient;
                    $newWaMsg->msg_from = "$request->recipient";
                    $newWaMsg->time = $currentTimestamp;
                    $newWaMsg->type = 'text';
                    $newWaMsg->is_sent = "1";
                    $newWaMsg->sent_by = Auth::id();
                    $newWaMsg->body = "$request->message";
                    try {
                        $newWaMsg->save();
                        Log::info('WhatsAppMessage saved', ['id' => $newWaMsg->id, 'msg_id' => $newWaMsg->msg_id]);
                    } catch (\Throwable $e) {
                        Log::error('Error saving WhatsAppMessage', [
                            'exception' => $e->getMessage(),
                            'trace' => $e->getTraceAsString(),
                            'wa_msg' => $newWaMsg->toArray(),
                        ]);
                    }

                    // Handle WhatsApp group creation
                    $number = $request->recipient;
                    $execId = auth()->id();
                    $group = WhatsappMsgGroup::where('whatsapp_number', $number)->first();
                    if ($group) {
                        $ids = array_filter(explode(',', $group->executive_ids));
                        if (!in_array($execId, $ids)) {
                            $ids[] = $execId;
                            $group->executive_ids = implode(',', array_unique($ids));
                            $group->save();
                            Log::info('Updated WhatsappMsgGroup executive_ids', ['whatsapp_number' => $number, 'executive_ids' => $group->executive_ids]);
                        } else {
                            Log::info('Exec ID already in WhatsappMsgGroup', ['whatsapp_number' => $number, 'executive_ids' => $group->executive_ids]);
                        }
                        // Send Expo notification to all group executives except sender
                        $notifyIds = array_diff($ids, [$execId]);
                        if (!empty($notifyIds)) {
                            ExpoNotificationService::send(
                                $notifyIds,
                                'New WhatsApp Message',
                                $request->message
                            );
                        }
                    } else {
                        $executiveIds = [$execId ?: 1, 1];
                        $executiveIds = array_unique($executiveIds); // Remove duplicates if execId is 1
                        $group = WhatsappMsgGroup::create([
                            'whatsapp_number' => $number,
                            'executive_ids' => implode(',', $executiveIds),
                        ]);
                        Log::info('Created new WhatsappMsgGroup', ['whatsapp_number' => $number, 'executive_ids' => implode(',', $executiveIds)]);
                        // Send Expo notification to all group executives except sender
                        $notifyIds = array_diff($executiveIds, [$execId]);
                        if (!empty($notifyIds)) {
                            ExpoNotificationService::send(
                                $notifyIds,
                                'New WhatsApp Message',
                                $request->message
                            );
                        }
                    }

                    return response()->json([
                        'message' => 'Message sent successfully.',
                        'message_id' => $responseData['message_id'] ?? null,
                        'message_wamid' => $responseData['message_wamid'] ?? null
                    ], 200);
                } else {
                    Log::error('WhatsApp API did not return success', ['responseData' => $responseData]);
                    return response()->json([
                        'error' => 'Failed to send message',
                        'details' => $responseData
                    ], 400);
                }
            } else {
                Log::error('WhatsApp API request failed', ['status' => $response->status(), 'body' => $response->body()]);
                return response()->json([
                    'error' => 'Failed to send message.',
                    'details' => $response->json()
                ], $response->status());
            }
        } catch (\Exception $e) {
            Log::error('Exception in whatsapp_msg_send', [
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);
            return response()->json([
                'error' => 'An error occurred while sending the message',
                'message' => $e->getMessage()
            ], 500);
        }
    }


    public function get_whatsapp_doc($message_id)
    {
        $authKey = config('services.whatsapp.auth_key', env('WHATSAPP_AUTH_KEY'));
        $url = 'https://rengage.mcube.com/api/wpbox/getMedia?message_id=' . urlencode($message_id);
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
            curl_close($curl);
            return null;
        }
        curl_close($curl);
        if ($httpcode >= 200 && $httpcode < 300) {
            $data = json_decode($response, true);
            if (isset($data['status']) && $data['status'] === 'success' && !empty($data['data'][0]['header_image'])) {
                return $data['data'][0]['header_image'];
            }
            return null;
        } else {
            return null;
        }
    }


    public function whatsapp_msg_send_hi(Request $request)
    {
        if (env('TATA_WHATSAPP_MSG_STATUS') !== true) {
            return false;
        }
        $url = "https://wb.omni.tatatelebusiness.com/whatsapp-cloud/messages";
        $authKey = env('TATA_AUTH_KEY');
        $latestMessage = WhatsAppMessage::where('msg_from', $request->recipient)->where('is_sent', 0)->orderBy('created_at', 'desc')->first();
        $now = Carbon::now();
        $sendTemplateMessage = false;
        if ($latestMessage) {
            $createdAt = new Carbon($latestMessage->created_at);
            $hoursDiff = $now->diffInHours($createdAt);
            if ($hoursDiff <= 24) {
                $sendTemplateMessage = true;
            }
        }
        $response = Http::withHeaders([
            'Authorization' => "Bearer $authKey",
            'Content-Type' => 'application/json'
        ])->post($url, $sendTemplateMessage ? [
            "messaging_product" => "whatsapp",
            "recipient_type" => "individual",
            "to" => "91$request->recipient",
            "type" => "text",
            "text" => [
                "body" => "hi"
            ]
        ] : [
            "to" => "91$request->recipient",
            "type" => "template",
            "template" => [
                "name" => "hi_msg",
                "language" => [
                    "code" => "en"
                ],
                "components" => [],
            ]
        ]);
        if ($response->successful()) {
            $currentTimestamp = Carbon::now();
            $newWaMsg = new WhatsAppMessage();
            $newWaMsg->msg_id = $request->recipient;
            $newWaMsg->msg_from = $request->recipient;
            $newWaMsg->time = $currentTimestamp;
            $newWaMsg->type = 'text';
            $newWaMsg->is_sent = "1";
            $newWaMsg->body = $sendTemplateMessage ? "*Hi*" : "Hi";
            $newWaMsg->save();
            return response()->json(['message' => 'Message sent successfully.'], 200);
        } else {
            return response()->json(['error' => 'Failed to send message.'], $response->status());
        }
    }

    public function whatsapp_msg_status(Request $request)
    {
        $guards = ['team'];
        $auth_user = null;
        foreach ($guards as $guard) {
            if (Auth::guard($guard)->check()) {
                $auth_user = Auth::guard($guard)->user();
                break;
            }
        }
        if ($auth_user) {
            $getlead = Lead::where('mobile', $request->mobile)->first();

            if (!$getlead) {
                return response()->json(['message' => 'Lead not found.'], 404);
            }

            if ($getlead->assign_id == $auth_user->id) {
                $getlead->is_whatsapp_msg = 0;
                if ($getlead->save()) {
                    return response()->json(['message' => 'WhatsApp message status updated successfully.'], 200);
                } else {
                    return response()->json(['message' => 'Failed to update the WhatsApp message status.'], 500);
                }
            } else {
                return response()->json(['message' => 'Unauthorized to update this lead.'], 403);
            }
        } else {
            return response()->json(['message' => 'Unauthorized access. No authenticated user found.'], 403);
        }
    }

    public function all_whatsapp_chats_index()
    {
        $logged_role = Session::get('logged_role');
        switch ($logged_role) {
            case 1:
                return view('admin.whatsapp.index');
            case 3:
                return view('manager.whatsapp.index');
            case 4:
                return view('operation.whatsapp.index');
            case 5:
                return view('operation_manager.whatsapp.index');
            case 2:
                return view('sales.whatsapp.index');
            default:
                return redirect('/login');
        }
    }

    public function get_all_whatsapp_numbers()
    {
        $user = Auth::user();
        $logged_role = $user->role_id ?? 1;
        $userId = $user->id;
        $executiveIds = [];

        if ($logged_role == 1) {
            $executiveIds = User::pluck('id')->toArray();
        } elseif ($logged_role == 3 || $logged_role == 5) {
            $subordinateIds = User::where('parent_id', $userId)->pluck('id')->toArray();
            $executiveIds = array_merge([$userId], $subordinateIds);
        } else {
            $executiveIds = [$userId];
        }

        $groups = WhatsappMsgGroup::where(function($query) use ($executiveIds) {
            foreach ($executiveIds as $id) {
                $query->orWhereRaw("FIND_IN_SET(?, executive_ids)", [$id]);
            }
        })->get();

        $numbers = $groups->map(function($group) {
            // Latest message (if any)
            $latestMsg = WhatsAppMessage::where('msg_from', $group->whatsapp_number)
                ->orderBy('time', 'desc')
                ->first();

            // Unread count
            $unread_count = WhatsAppMessage::where('msg_from', $group->whatsapp_number)
                ->where('is_sent', 0)
                ->where(function($q) {
                    $q->whereNull('status')->orWhere('status', '!=', 'read');
                })
                ->count();

            return [
                'msg_from' => $group->whatsapp_number,
                'executive_ids' => $group->executive_ids,
                'body' => $latestMsg ? $latestMsg->body : null,
                'time' => $latestMsg ? $latestMsg->time : null,
                'unread_count' => $unread_count,
            ];
        });

        return response()->json($numbers->values());
    }

    public function get_messages_for_number(Request $request, $number)
    {
        $perPage = (int)($request->query('per_page', 20));
        $page = (int)($request->query('page', 1));

        $query = WhatsAppMessage::where('msg_from', $number)
            ->orderBy('id', 'asc'); // oldest first

        $total = $query->count();
        $messages = $query
            ->skip(($page - 1) * $perPage)
            ->take($perPage)
            ->get();

        foreach ($messages as $message) {
            // Only fetch media URL for media messages
            if (!empty($message->msg_id) && in_array($message->type, ['image', 'audio', 'video', 'document'])) {
                $mediaUrl = $this->get_whatsapp_doc($message->msg_id);
                $message->doc = $mediaUrl;
            } else {
                $message->doc = null;
            }
        }

        return response()->json([
            'messages' => $messages,
            'has_more' => ($page * $perPage) < $total,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
        ]);
    }

    /**
     * Get the list of executives (id, name) who are allowed for a WhatsApp number.
     */
    public function get_executives_for_number($number)
    {
        $group = WhatsappMsgGroup::where('whatsapp_number', $number)->first();
        if (!$group) {
            return response()->json([]);
        }
        $ids = array_filter(explode(',', $group->executive_ids));
        $executives = User::whereIn('id', $ids)->get(['id', 'f_name', 'l_name']);
        // Optionally, add a 'name' field for full name
        $executives->map(function($user) {
            $user->name = trim($user->f_name . ' ' . ($user->l_name ?? ''));
            return $user;
        });
        return response()->json($executives);
    }

    /**
     * Add an executive ID to a WhatsApp group (by number), or create if not exists.
     * Request: whatsapp_number, executive_id
     */
    public function addExecutiveToWhatsappGroup(Request $request)
    {
        $request->validate([
            'whatsapp_number' => 'required|string',
            'executive_id' => 'required|integer',
        ]);

        $number = $request->whatsapp_number;
        $execId = $request->executive_id;

        $group = WhatsappMsgGroup::where('whatsapp_number', $number)->first();
        if ($group) {
            $ids = array_filter(explode(',', $group->executive_ids));
            if (!in_array($execId, $ids)) {
                $ids[] = $execId;
                $group->executive_ids = implode(',', $ids);
                $group->save();
            }
        } else {
            WhatsappMsgGroup::create([
                'whatsapp_number' => $number,
                'executive_ids' => $execId,
            ]);
        }
        return response()->json(['success' => true]);
    }

    /**
     * Remove an executive ID from a WhatsApp group (by number).
     * Request: whatsapp_number, executive_id
     */
    public function removeExecutiveFromWhatsappGroup(Request $request)
    {
        $request->validate([
            'whatsapp_number' => 'required|string',
            'executive_id' => 'required|integer',
        ]);

        $number = $request->whatsapp_number;
        $execId = $request->executive_id;

        $group = WhatsappMsgGroup::where('whatsapp_number', $number)->first();
        if ($group) {
            $ids = array_filter(explode(',', $group->executive_ids));
            $ids = array_diff($ids, [$execId]);
            if (count($ids) > 0) {
                $group->executive_ids = implode(',', $ids);
                $group->save();
            } else {
                $group->delete();
            }
        }
        return response()->json(['success' => true]);
    }

    /**
     * Mark all unread messages for a number as read
     */
    public function markMessagesAsRead(Request $request, $number)
    {
        WhatsAppMessage::where('msg_from', $number)
            ->where('is_sent', 0)
            ->where(function($q) {
                $q->whereNull('status')->orWhere('status', '!=', 'read');
            })
            ->update(['status' => 'read']);
        return response()->json(['success' => true]);
    }

    public function make_whatsapp_grp_from_all_leads() {
        $sevenDaysAgo = now()->subDays(365);

        // LEADS
        foreach (\App\Models\Lead::where('updated_at', '>=', $sevenDaysAgo)->get() as $lead) {
            if ($lead->contact_no && $lead->executive) {
                $group = \App\Models\WhatsappMsgGroup::firstOrNew(['whatsapp_number' => $lead->contact_no]);
                $ids = $group->executive_ids ? explode(',', $group->executive_ids) : [];
                if (!in_array($lead->executive, $ids)) {
                    $ids[] = $lead->executive;
                }
                $group->executive_ids = implode(',', array_unique(array_filter($ids)));
                $group->save();
            }
        }

        // OPERATION LEADS
        foreach (\App\Models\OperationLead::where('updated_at', '>=', $sevenDaysAgo)->get() as $opLead) {
            if (isset($opLead->contact_no) && isset($opLead->executive)) {
                $group = \App\Models\WhatsappMsgGroup::firstOrNew(['whatsapp_number' => $opLead->contact_no]);
                $ids = $group->executive_ids ? explode(',', $group->executive_ids) : [];
                if (!in_array($opLead->executive, $ids)) {
                    $ids[] = $opLead->executive;
                }
                $group->executive_ids = implode(',', array_unique(array_filter($ids)));
                $group->save();
            }
        }

        // JOB REQUESTS (assumes contact_no field exists)
        foreach (\App\Models\JobRequest::where('updated_at', '>=', $sevenDaysAgo)->get() as $job) {
            if (isset($job->contact_no) && isset($job->executive_id)) {
                $group = \App\Models\WhatsappMsgGroup::firstOrNew(['whatsapp_number' => $job->contact_no]);
                $ids = $group->executive_ids ? explode(',', $group->executive_ids) : [];
                if (!in_array($job->executive_id, $ids)) {
                    $ids[] = $job->executive_id;
                }
                $group->executive_ids = implode(',', array_unique(array_filter($ids)));
                $group->save();
            }
        }

        return response()->json(['success' => true, 'message' => 'WhatsApp groups created/updated from all leads updated in last 7 days.']);
    }

}
