<?php

namespace App\Http\Controllers\DoctorPortal;

use App\Http\Controllers\Controller;
use App\Models\ConsultationWebsiteBooking;
use App\Models\CustomerChatCall;
use App\Models\CustomerChatMessage;
use App\Models\OperationLead;
use App\Services\GeminiService;
use App\Support\DoctorCustomerChatRealtime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class DoctorCustomerChatController extends Controller
{
    private function doctorId(): ?int
    {
        return Session::get('doctor_reg_id') ? (int) Session::get('doctor_reg_id') : null;
    }

    private function requireDoctorJson(): ?\Illuminate\Http\JsonResponse
    {
        if (!$this->doctorId()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        return null;
    }

    public function index()
    {
        $doctorId = $this->doctorId();
        if (!$doctorId) {
            return redirect()->route('home')->with('error', 'Please login to access your doctor account.');
        }

        $bookings = ConsultationWebsiteBooking::query()
            ->where('doctor_request_id', $doctorId)
            ->with(['operationLead'])
            ->orderByDesc('created_at')
            ->get();

        $byContact = [];
        foreach ($bookings as $booking) {
            $lead = $booking->operationLead;
            if (!$lead && $booking->contact_no) {
                $lead = OperationLead::where('contact_no', $booking->contact_no)->first();
            }
            if (!$lead || !$lead->contact_no) {
                continue;
            }
            $cn = $lead->contact_no;
            if (!isset($byContact[$cn])) {
                $byContact[$cn] = [
                    'primary' => $lead,
                    'ids' => OperationLead::where('contact_no', $cn)->pluck('id')->toArray(),
                ];
            }
        }

        $enrichedCustomers = collect($byContact)->map(function ($row) use ($doctorId) {
            /** @var OperationLead $primary */
            $primary = $row['primary'];
            $ids = $row['ids'];

            $threadSignatures = array_values(array_unique(array_filter(array_map(
                fn ($id) => DoctorCustomerChatRealtime::threadSignature($doctorId, (int) $id),
                $ids
            ))));
            $primary->doctor_customer_thread_signatures_json = json_encode($threadSignatures);
            $primary->doctor_customer_linked_lead_ids_json = json_encode(array_values(array_map('intval', $ids)));

            $unread = CustomerChatMessage::where('sender_type', 'customer')
                ->whereIn('sender_id', $ids)
                ->where('receiver_type', 'doctor')
                ->where('receiver_id', $doctorId)
                ->where('is_read', false)
                ->count();

            $lastMsg = CustomerChatMessage::where(function ($q) use ($ids, $doctorId) {
                $q->where(function ($q2) use ($ids, $doctorId) {
                    $q2->where('sender_type', 'customer')
                        ->whereIn('sender_id', $ids)
                        ->where('receiver_type', 'doctor')
                        ->where('receiver_id', $doctorId);
                })->orWhere(function ($q2) use ($ids, $doctorId) {
                    $q2->where('sender_type', 'doctor')
                        ->where('sender_id', $doctorId)
                        ->where('receiver_type', 'customer')
                        ->whereIn('receiver_id', $ids);
                });
            })->orderByDesc('created_at')->first();

            $primary->unread_count = $unread;
            $primary->last_message = $lastMsg;

            return $primary;
        })->values();

        $sortedCustomers = $enrichedCustomers->sort(function ($a, $b) {
            if ($a->unread_count > 0 && $b->unread_count == 0) {
                return -1;
            }
            if ($a->unread_count == 0 && $b->unread_count > 0) {
                return 1;
            }
            $aTime = $a->last_message ? strtotime($a->last_message->created_at) : 0;
            $bTime = $b->last_message ? strtotime($b->last_message->created_at) : 0;

            return $bTime <=> $aTime;
        })->values();

        $allSignatures = [];
        foreach ($sortedCustomers as $cust) {
            foreach (json_decode($cust->doctor_customer_thread_signatures_json ?? '[]', true) ?: [] as $s) {
                if ($s !== null && $s !== '') {
                    $allSignatures[$s] = true;
                }
            }
        }

        return view('doctor_carelix.customer-chats', [
            'customers' => $sortedCustomers,
            'doctorCustomerRealtimeSignatures' => array_keys($allSignatures),
        ]);
    }

    public function getMessages($customerId, Request $request)
    {
        if ($r = $this->requireDoctorJson()) {
            return $r;
        }
        $doctorId = $this->doctorId();

        $operationLeadIds = ConsultationWebsiteBooking::operationLeadIdsForDoctorCustomerChatOrDeny(
            $doctorId,
            (int) $customerId
        );
        if ($operationLeadIds === null) {
            return response()->json(['error' => 'No booking with this customer'], 403);
        }

        $query = CustomerChatMessage::where(function ($q) use ($operationLeadIds, $doctorId) {
            $q->where(function ($subQ) use ($operationLeadIds, $doctorId) {
                $subQ->where('sender_type', 'doctor')
                    ->where('sender_id', $doctorId)
                    ->where('receiver_type', 'customer')
                    ->whereIn('receiver_id', $operationLeadIds);
            })
                ->orWhere(function ($subQ) use ($operationLeadIds, $doctorId) {
                    $subQ->where('sender_type', 'customer')
                        ->whereIn('sender_id', $operationLeadIds)
                        ->where('receiver_type', 'doctor')
                        ->where('receiver_id', $doctorId);
                });
        });

        if ($request->has('last_message_id') && $request->last_message_id) {
            $query->where('id', '>', $request->last_message_id);
        }

        $messages = $query->with('repliedTo')->orderBy('created_at', 'asc')->get();

        $calls = CustomerChatCall::where(function ($q) use ($operationLeadIds, $doctorId) {
            $q->where('caller_type', 'doctor')
                ->where('caller_id', $doctorId)
                ->where('receiver_type', 'customer')
                ->whereIn('receiver_id', $operationLeadIds);
        })->orWhere(function ($q) use ($operationLeadIds, $doctorId) {
            $q->where('caller_type', 'customer')
                ->whereIn('caller_id', $operationLeadIds)
                ->where('receiver_type', 'doctor')
                ->where('receiver_id', $doctorId);
        })->orderBy('created_at', 'asc')->get();

        $combined = collect();
        foreach ($messages as $msg) {
            $combined->push([
                'type' => 'message',
                'id' => $msg->id,
                'created_at' => $msg->created_at,
                'data' => $msg,
            ]);
        }
        foreach ($calls as $call) {
            $combined->push([
                'type' => 'call',
                'id' => 'call_'.$call->id,
                'created_at' => $call->call_started_at ?? $call->created_at,
                'data' => $call,
            ]);
        }

        $sorted = $combined->sortBy('created_at')->values();

        if ($request->has('last_message_id') && $request->last_message_id) {
            $lastMsg = $messages->where('id', $request->last_message_id)->first();
            if ($lastMsg) {
                $sorted = $sorted->filter(function ($item) use ($lastMsg) {
                    if ($item['type'] === 'message') {
                        return $item['data']->id > $lastMsg->id;
                    }

                    return $item['created_at'] > $lastMsg->created_at;
                })->values();
            }
        }

        $chatSchedule = ConsultationWebsiteBooking::doctorCustomerChatScheduleStatus(
            (int) $doctorId,
            (int) $customerId
        );

        return response()->json([
            'items' => $sorted,
            'chat_schedule' => $chatSchedule,
        ]);
    }

    public function sendMessage(Request $request)
    {
        if ($r = $this->requireDoctorJson()) {
            return $r;
        }
        $doctorId = $this->doctorId();

        $request->validate([
            'receiver_id' => 'required|exists:operation_leads,id',
            'message' => 'required_without:attachment|string|nullable',
            'attachment' => 'nullable|file|max:10240',
            'reply_to_id' => 'nullable|exists:customer_chat_messages,id',
        ]);

        if (!ConsultationWebsiteBooking::doctorHasBookedAnyLead($doctorId, (int) $request->receiver_id)) {
            return response()->json(['error' => 'No booking with this customer'], 403);
        }

        $chatSchedule = ConsultationWebsiteBooking::doctorCustomerChatScheduleStatus(
            (int) $doctorId,
            (int) $request->receiver_id
        );
        if (! $chatSchedule['allowed']) {
            return response()->json([
                'error' => $chatSchedule['user_message'] ?? 'Chat is not available outside your appointment window.',
                'chat_schedule' => $chatSchedule,
            ], 403);
        }

        $data = [
            'sender_type' => 'doctor',
            'sender_id' => $doctorId,
            'receiver_type' => 'customer',
            'receiver_id' => $request->receiver_id,
            'message' => $request->message,
            'is_read' => false,
            'reply_to_id' => $request->reply_to_id,
        ];

        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $path = $file->store('customer_chat_attachments', 'public');
            $data['attachment'] = $path;
            $data['attachment_type'] = $file->getMimeType();
        }

        $message = CustomerChatMessage::create($data);

        return response()->json($message);
    }

    public function markAsRead($customerId)
    {
        if ($r = $this->requireDoctorJson()) {
            return $r;
        }
        $doctorId = $this->doctorId();

        $customer = OperationLead::find($customerId);
        $operationLeadIds = [(int) $customerId];
        if ($customer && $customer->contact_no) {
            $operationLeadIds = array_unique(array_merge(
                $operationLeadIds,
                OperationLead::where('contact_no', $customer->contact_no)->pluck('id')->toArray()
            ));
        }

        CustomerChatMessage::where('sender_type', 'customer')
            ->whereIn('sender_id', $operationLeadIds)
            ->where('receiver_type', 'doctor')
            ->where('receiver_id', $doctorId)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        DoctorCustomerChatRealtime::broadcastUnreadRefresh($doctorId, (int) $customerId, 'mark_read');

        return response()->json(['success' => true]);
    }

    public function getUnreadCounts()
    {
        $doctorId = $this->doctorId();
        if (!$doctorId) {
            return response()->json([]);
        }

        $bookings = ConsultationWebsiteBooking::query()
            ->where('doctor_request_id', $doctorId)
            ->get();

        $leadIds = [];
        foreach ($bookings as $booking) {
            if ($booking->operation_lead_id) {
                $leadIds[] = (int) $booking->operation_lead_id;
            } elseif ($booking->contact_no) {
                $found = OperationLead::where('contact_no', $booking->contact_no)->first();
                if ($found) {
                    $leadIds[] = (int) $found->id;
                }
            }
        }
        $leadIds = array_unique($leadIds);

        $counts = [];
        foreach ($leadIds as $lid) {
            $customer = OperationLead::find($lid);
            $operationLeadIds = [$lid];
            if ($customer && $customer->contact_no) {
                $operationLeadIds = array_unique(array_merge(
                    $operationLeadIds,
                    OperationLead::where('contact_no', $customer->contact_no)->pluck('id')->toArray()
                ));
            }
            $counts[$lid] = CustomerChatMessage::where('sender_type', 'customer')
                ->whereIn('sender_id', $operationLeadIds)
                ->where('receiver_type', 'doctor')
                ->where('receiver_id', $doctorId)
                ->where('is_read', false)
                ->count();
        }

        return response()->json($counts);
    }

    public function getCallOffer(Request $request)
    {
        if ($r = $this->requireDoctorJson()) {
            return $r;
        }
        $doctorId = $this->doctorId();
        $customerId = $request->customer_id;

        $cacheKey = "call_offer_customer_to_doctor_{$doctorId}_{$customerId}";
        $offer = \Illuminate\Support\Facades\Cache::get($cacheKey);

        if ($offer) {
            \Illuminate\Support\Facades\Cache::forget($cacheKey);

            $callLog = CustomerChatCall::create([
                'caller_type' => 'customer',
                'caller_id' => $customerId,
                'receiver_type' => 'doctor',
                'receiver_id' => $doctorId,
                'call_status' => 'initiated',
                'call_started_at' => now(),
            ]);

            $callIdCacheKey = "call_id_customer_{$customerId}_doctor_{$doctorId}";
            \Illuminate\Support\Facades\Cache::put($callIdCacheKey, $callLog->id, 300);

            return response()->json(['offer' => $offer, 'customer_id' => $customerId]);
        }

        return response()->json(['offer' => null]);
    }

    public function sendCallOffer(Request $request)
    {
        if ($r = $this->requireDoctorJson()) {
            return $r;
        }
        $doctorId = $this->doctorId();
        $customerId = $request->customer_id;
        $offer = $request->offer;

        $callLog = CustomerChatCall::create([
            'caller_type' => 'doctor',
            'caller_id' => $doctorId,
            'receiver_type' => 'customer',
            'receiver_id' => $customerId,
            'call_status' => 'initiated',
            'call_started_at' => now(),
        ]);

        $callIdCacheKey = "call_id_doctor_{$doctorId}_{$customerId}";
        \Illuminate\Support\Facades\Cache::put($callIdCacheKey, $callLog->id, 300);

        $cacheKey = "call_offer_doctor_to_customer_{$doctorId}_{$customerId}";
        \Illuminate\Support\Facades\Cache::put($cacheKey, $offer, 60);

        $answerCacheKey = "call_answer_customer_to_doctor_{$doctorId}_{$customerId}";
        $answer = \Illuminate\Support\Facades\Cache::get($answerCacheKey);

        if ($answer) {
            \Illuminate\Support\Facades\Cache::forget($answerCacheKey);

            return response()->json(['answer' => $answer]);
        }

        return response()->json(['success' => true, 'call_id' => $callLog->id]);
    }

    public function getCallAnswer(Request $request)
    {
        if ($r = $this->requireDoctorJson()) {
            return $r;
        }
        $doctorId = $this->doctorId();
        $customerId = $request->customer_id;

        $cacheKey = "call_answer_customer_to_doctor_{$doctorId}_{$customerId}";
        $answer = \Illuminate\Support\Facades\Cache::get($cacheKey);

        if ($answer) {
            \Illuminate\Support\Facades\Cache::forget($cacheKey);

            return response()->json(['answer' => $answer]);
        }

        return response()->json(['answer' => null]);
    }

    public function callAnswer(Request $request)
    {
        if ($r = $this->requireDoctorJson()) {
            return $r;
        }
        $doctorId = $this->doctorId();
        $customerId = $request->customer_id;
        $answer = $request->answer;

        $callIdCacheKey = "call_id_doctor_{$doctorId}_{$customerId}";
        $callId = \Illuminate\Support\Facades\Cache::get($callIdCacheKey);
        if ($callId) {
            CustomerChatCall::where('id', $callId)
                ->where('caller_type', 'doctor')
                ->where('caller_id', $doctorId)
                ->where('receiver_type', 'customer')
                ->where('receiver_id', $customerId)
                ->update(['call_status' => 'connected']);
        }

        $incomingCallIdCacheKey = "call_id_customer_{$customerId}_doctor_{$doctorId}";
        $incomingCallId = \Illuminate\Support\Facades\Cache::get($incomingCallIdCacheKey);
        if ($incomingCallId) {
            CustomerChatCall::where('id', $incomingCallId)
                ->where('caller_type', 'customer')
                ->where('caller_id', $customerId)
                ->where('receiver_type', 'doctor')
                ->where('receiver_id', $doctorId)
                ->update(['call_status' => 'connected']);
        }

        $cacheKey = "call_answer_doctor_{$doctorId}_{$customerId}";
        \Illuminate\Support\Facades\Cache::put($cacheKey, $answer, 60);

        return response()->json(['success' => true]);
    }

    public function getCallIce(Request $request)
    {
        if ($r = $this->requireDoctorJson()) {
            return $r;
        }
        $doctorId = $this->doctorId();
        $customerId = $request->customer_id;

        $cacheKey = "call_ice_customer_to_doctor_{$doctorId}_{$customerId}";
        $candidates = \Illuminate\Support\Facades\Cache::get($cacheKey, []);

        if (!empty($candidates)) {
            \Illuminate\Support\Facades\Cache::forget($cacheKey);

            return response()->json(['candidates' => $candidates]);
        }

        return response()->json(['candidates' => []]);
    }

    public function sendCallIce(Request $request)
    {
        if ($r = $this->requireDoctorJson()) {
            return $r;
        }
        $doctorId = $this->doctorId();
        $customerId = $request->customer_id;
        $candidate = $request->candidate;

        $cacheKey = "call_ice_doctor_{$doctorId}_{$customerId}";
        $candidates = \Illuminate\Support\Facades\Cache::get($cacheKey, []);
        $candidates[] = $candidate;
        \Illuminate\Support\Facades\Cache::put($cacheKey, $candidates, 60);

        return response()->json(['success' => true]);
    }

    public function callEnd(Request $request)
    {
        if ($r = $this->requireDoctorJson()) {
            return $r;
        }
        $doctorId = $this->doctorId();
        $customerId = $request->customer_id;

        $callIdCacheKey = "call_id_doctor_{$doctorId}_{$customerId}";
        $callId = \Illuminate\Support\Facades\Cache::get($callIdCacheKey);
        if ($callId) {
            $call = CustomerChatCall::find($callId);
            if ($call) {
                $call->call_status = 'ended';
                $call->call_ended_at = now();
                if ($call->call_started_at) {
                    $call->call_duration = $call->call_started_at->diffInSeconds(now());
                }
                $call->save();
                \Illuminate\Support\Facades\Cache::forget($callIdCacheKey);
            }
        }

        $incomingCallIdCacheKey = "call_id_customer_{$customerId}_doctor_{$doctorId}";
        $incomingCallId = \Illuminate\Support\Facades\Cache::get($incomingCallIdCacheKey);
        if ($incomingCallId) {
            $call = CustomerChatCall::find($incomingCallId);
            if ($call) {
                $call->call_status = 'ended';
                $call->call_ended_at = now();
                if ($call->call_started_at) {
                    $call->call_duration = $call->call_started_at->diffInSeconds(now());
                }
                $call->save();
                \Illuminate\Support\Facades\Cache::forget($incomingCallIdCacheKey);
            }
        }

        $keys = [
            "call_offer_doctor_{$doctorId}_{$customerId}",
            "call_offer_customer_to_doctor_{$doctorId}_{$customerId}",
            "call_offer_doctor_to_customer_{$doctorId}_{$customerId}",
            "call_answer_doctor_{$doctorId}_{$customerId}",
            "call_answer_customer_to_doctor_{$doctorId}_{$customerId}",
            "call_ice_doctor_{$doctorId}_{$customerId}",
            "call_ice_customer_to_doctor_{$doctorId}_{$customerId}",
        ];
        foreach ($keys as $key) {
            \Illuminate\Support\Facades\Cache::forget($key);
        }

        return response()->json(['success' => true]);
    }

    public function translate(Request $request)
    {
        $request->validate([
            'text' => 'required|string|max:8000',
            'target_language' => 'required|string|max:80',
        ]);
        $gemini = new GeminiService;
        $translated = $gemini->translate((string) $request->input('text'), (string) $request->input('target_language'));
        if ($translated === null) {
            return response()->json([
                'success' => false,
                'message' => filled(config('services.gemini.api_key'))
                    ? 'Translation service unavailable. Try GEMINI_MODEL in .env or check server logs.'
                    : 'GEMINI_API_KEY is not set in .env.',
            ], 503);
        }

        return response()->json(['success' => true, 'translated' => $translated]);
    }
}
