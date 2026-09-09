<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\CustomerChatMessage;
use App\Models\CustomerChatCall;
use App\Models\OperationDeploymentDetails;
use App\Models\OperationLead;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use App\Services\GeminiService;

class ChatController extends Controller
{
    public function index()
    {
        $vendorId = Session::get('vendor_id');
        
        if (!$vendorId) {
            return redirect()->route('home')->with('error', 'Please login to access vendor dashboard.');
        }

        // Get all customers assigned to this vendor via OperationDeploymentDetails
        $deployments = OperationDeploymentDetails::where('vendor_id', $vendorId)
            ->with('operationLead')
            ->get();

        // Get unique customers
        $customers = $deployments->map(function($deployment) {
            return $deployment->operationLead;
        })->filter()->unique('id')->values();

        // Enrich customers with unread count and last message
        $enrichedCustomers = $customers->map(function($customer) use ($vendorId) {
            // Unread count (messages from customer to vendor)
            $unread = CustomerChatMessage::where('sender_type', 'customer')
                ->where('sender_id', $customer->id)
                ->where('receiver_type', 'vendor')
                ->where('receiver_id', $vendorId)
                ->where('is_read', false)
                ->count();

            // Last message (either direction)
            $lastMsg = CustomerChatMessage::where(function($q) use ($customer, $vendorId) {
                $q->where('sender_type', 'customer')
                  ->where('sender_id', $customer->id)
                  ->where('receiver_type', 'vendor')
                  ->where('receiver_id', $vendorId);
            })->orWhere(function($q) use ($customer, $vendorId) {
                $q->where('sender_type', 'vendor')
                  ->where('sender_id', $vendorId)
                  ->where('receiver_type', 'customer')
                  ->where('receiver_id', $customer->id);
            })->orderByDesc('created_at')->first();

            $customer->unread_count = $unread;
            $customer->last_message = $lastMsg;
            return $customer;
        });

        // Sort: unread first, then by last message time desc
        $sortedCustomers = $enrichedCustomers->sort(function($a, $b) {
            if ($a->unread_count > 0 && $b->unread_count == 0) return -1;
            if ($a->unread_count == 0 && $b->unread_count > 0) return 1;
            $aTime = $a->last_message ? strtotime($a->last_message->created_at) : 0;
            $bTime = $b->last_message ? strtotime($b->last_message->created_at) : 0;
            return $bTime <=> $aTime;
        })->values();

        return view('vendor.customer-chats', ['customers' => $sortedCustomers]);
    }

    public function getMessages($customerId, Request $request)
    {
        $vendorId = Session::get('vendor_id');
        
        if (!$vendorId) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Verify customer is assigned to this vendor
        $isAssigned = OperationDeploymentDetails::where('vendor_id', $vendorId)
            ->where('operation_lead_id', $customerId)
            ->exists();

        if (!$isAssigned) {
            return response()->json(['error' => 'Customer not assigned to you'], 403);
        }

        // Get the customer's contact_no to find all related operation lead IDs
        $customer = OperationLead::find($customerId);
        $customerContactNo = $customer ? $customer->contact_no : null;
        
        // Get all operation lead IDs with the same contact_no (in case customer has multiple leads)
        $operationLeadIds = [$customerId]; // Always include the current customer ID
        if ($customerContactNo) {
            $relatedLeadIds = OperationLead::where('contact_no', $customerContactNo)
                ->pluck('id')
                ->toArray();
            $operationLeadIds = array_unique(array_merge($operationLeadIds, $relatedLeadIds));
        }

        // Build query for messages between vendor and customer
        $query = CustomerChatMessage::where(function($q) use ($operationLeadIds, $vendorId) {
            // Vendor to Customer messages
            $q->where(function($subQ) use ($operationLeadIds, $vendorId) {
                $subQ->where('sender_type', 'vendor')
                     ->where('sender_id', $vendorId)
                     ->where('receiver_type', 'customer')
                     ->whereIn('receiver_id', $operationLeadIds);
            })
            // Customer to Vendor messages
            ->orWhere(function($subQ) use ($operationLeadIds, $vendorId) {
                $subQ->where('sender_type', 'customer')
                     ->whereIn('sender_id', $operationLeadIds)
                     ->where('receiver_type', 'vendor')
                     ->where('receiver_id', $vendorId);
            });
        });

        // Apply last_message_id filter if provided (applies to entire query)
        if ($request->has('last_message_id') && $request->last_message_id) {
            $query->where('id', '>', $request->last_message_id);
        }

        $messages = $query->with('repliedTo')->orderBy('created_at', 'asc')->get();

        // Get call history for this conversation (use same operationLeadIds)
        $callsQuery = CustomerChatCall::where(function($q) use ($operationLeadIds, $vendorId) {
            $q->where('caller_type', 'vendor')
              ->where('caller_id', $vendorId)
              ->where('receiver_type', 'customer')
              ->whereIn('receiver_id', $operationLeadIds);
        })->orWhere(function($q) use ($operationLeadIds, $vendorId) {
            $q->where('caller_type', 'customer')
              ->whereIn('caller_id', $operationLeadIds)
              ->where('receiver_type', 'vendor')
              ->where('receiver_id', $vendorId);
        });

        // If last_message_id is provided, filter calls after that message's timestamp
        if ($request->has('last_message_id') && $request->last_message_id) {
            $lastMessage = CustomerChatMessage::find($request->last_message_id);
            if ($lastMessage) {
                $callsQuery->where('created_at', '>', $lastMessage->created_at);
            }
        }

        $calls = $callsQuery->orderBy('created_at', 'asc')->get();

        // Combine messages and calls, sort by created_at
        $combined = collect();
        
        // Add messages
        foreach ($messages as $msg) {
            $combined->push([
                'type' => 'message',
                'id' => $msg->id,
                'created_at' => $msg->created_at,
                'data' => $msg
            ]);
        }
        
        // Add calls
        foreach ($calls as $call) {
            $combined->push([
                'type' => 'call',
                'id' => 'call_' . $call->id,
                'created_at' => $call->call_started_at ?? $call->created_at,
                'data' => $call
            ]);
        }
        
        // Sort by created_at
        $sorted = $combined->sortBy('created_at')->values();
        
        // Manually load sender for repliedTo messages (handle polymorphic relationship)
        foreach ($sorted as $item) {
            if ($item['type'] === 'message' && $item['data']->repliedTo && $item['data']->repliedTo->sender_type) {
                try {
                    // Dynamically load the sender based on sender_type
                    if ($item['data']->repliedTo->sender_type === 'vendor') {
                        $item['data']->repliedTo->setRelation('sender', \App\Models\Vendor::find($item['data']->repliedTo->sender_id));
                    } elseif ($item['data']->repliedTo->sender_type === 'freelancer') {
                        $item['data']->repliedTo->setRelation('sender', \App\Models\JobRequest::find($item['data']->repliedTo->sender_id));
                    } elseif ($item['data']->repliedTo->sender_type === 'customer') {
                        $item['data']->repliedTo->setRelation('sender', \App\Models\OperationLead::find($item['data']->repliedTo->sender_id));
                    }
                } catch (\Exception $e) {
                    \Log::error("Failed to load sender for repliedTo message ID: {$item['data']->repliedTo->id}. Error: {$e->getMessage()}");
                    $item['data']->repliedTo->setRelation('sender', null);
                }
            }
        }

        return response()->json($sorted);
    }

    public function sendMessage(Request $request)
    {
        $vendorId = Session::get('vendor_id');
        
        if (!$vendorId) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $request->validate([
            'receiver_id' => 'required|exists:operation_leads,id',
            'message' => 'required_without:attachment|string|nullable',
            'attachment' => 'nullable|file|max:10240',
            'reply_to_id' => 'nullable|exists:customer_chat_messages,id'
        ]);

        // Verify customer is assigned to this vendor
        $isAssigned = OperationDeploymentDetails::where('vendor_id', $vendorId)
            ->where('operation_lead_id', $request->receiver_id)
            ->exists();

        if (!$isAssigned) {
            return response()->json(['error' => 'Customer not assigned to you'], 403);
        }

        $data = [
            'sender_type' => 'vendor',
            'sender_id' => $vendorId,
            'receiver_type' => 'customer',
            'receiver_id' => $request->receiver_id,
            'message' => $request->message,
            'is_read' => false,
            'reply_to_id' => $request->reply_to_id
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
        $vendorId = Session::get('vendor_id');
        
        if (!$vendorId) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Get the customer's contact_no to find all related operation lead IDs
        $customer = OperationLead::find($customerId);
        $customerContactNo = $customer ? $customer->contact_no : null;
        
        // Get all operation lead IDs with the same contact_no (in case customer has multiple leads)
        $operationLeadIds = [$customerId]; // Always include the current customer ID
        if ($customerContactNo) {
            $relatedLeadIds = OperationLead::where('contact_no', $customerContactNo)
                ->pluck('id')
                ->toArray();
            $operationLeadIds = array_unique(array_merge($operationLeadIds, $relatedLeadIds));
        }

        CustomerChatMessage::where('sender_type', 'customer')
            ->whereIn('sender_id', $operationLeadIds)
            ->where('receiver_type', 'vendor')
            ->where('receiver_id', $vendorId)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return response()->json(['success' => true]);
    }

    public function getUnreadCounts()
    {
        $vendorId = Session::get('vendor_id');
        
        if (!$vendorId) {
            return response()->json([]);
        }

        // Get assigned customers
        $deployments = OperationDeploymentDetails::where('vendor_id', $vendorId)
            ->pluck('operation_lead_id');

        $counts = [];
        foreach ($deployments as $customerId) {
            $counts[$customerId] = CustomerChatMessage::where('sender_type', 'customer')
                ->where('sender_id', $customerId)
                ->where('receiver_type', 'vendor')
                ->where('receiver_id', $vendorId)
                ->where('is_read', false)
                ->count();
        }

        return response()->json($counts);
    }

    public function getCallOffer(Request $request)
    {
        $vendorId = Session::get('vendor_id');
        if (!$vendorId) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $customerId = $request->customer_id;

        // Check for incoming call offer
        $cacheKey = "call_offer_customer_to_vendor_{$vendorId}_{$customerId}";
        $offer = \Illuminate\Support\Facades\Cache::get($cacheKey);

        if ($offer) {
            \Illuminate\Support\Facades\Cache::forget($cacheKey);
            
            // Log incoming call
            $callLog = CustomerChatCall::create([
                'caller_type' => 'customer',
                'caller_id' => $customerId,
                'receiver_type' => 'vendor',
                'receiver_id' => $vendorId,
                'call_status' => 'initiated',
                'call_started_at' => now(),
            ]);

            // Store call ID in cache for later update
            $callIdCacheKey = "call_id_customer_{$customerId}_vendor_{$vendorId}";
            \Illuminate\Support\Facades\Cache::put($callIdCacheKey, $callLog->id, 300);
            
            return response()->json(['offer' => $offer, 'customer_id' => $customerId]);
        }

        return response()->json(['offer' => null]);
    }

    public function sendCallOffer(Request $request)
    {
        $vendorId = Session::get('vendor_id');
        if (!$vendorId) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $customerId = $request->customer_id;
        $offer = $request->offer;

        $callLog = CustomerChatCall::create([
            'caller_type' => 'vendor',
            'caller_id' => $vendorId,
            'receiver_type' => 'customer',
            'receiver_id' => $customerId,
            'call_status' => 'initiated',
            'call_started_at' => now(),
        ]);

        $callIdCacheKey = "call_id_vendor_{$vendorId}_{$customerId}";
        \Illuminate\Support\Facades\Cache::put($callIdCacheKey, $callLog->id, 300);

        $cacheKey = "call_offer_vendor_to_customer_{$vendorId}_{$customerId}";
        \Illuminate\Support\Facades\Cache::put($cacheKey, $offer, 60);

        $answerCacheKey = "call_answer_customer_to_vendor_{$vendorId}_{$customerId}";
        $answer = \Illuminate\Support\Facades\Cache::get($answerCacheKey);

        if ($answer) {
            \Illuminate\Support\Facades\Cache::forget($answerCacheKey);
            return response()->json(['answer' => $answer]);
        }

        return response()->json(['success' => true, 'call_id' => $callLog->id]);
    }

    public function getCallAnswer(Request $request)
    {
        $vendorId = Session::get('vendor_id');
        if (!$vendorId) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $customerId = $request->customer_id;

        $cacheKey = "call_answer_customer_to_vendor_{$vendorId}_{$customerId}";
        $answer = \Illuminate\Support\Facades\Cache::get($cacheKey);

        if ($answer) {
            \Illuminate\Support\Facades\Cache::forget($cacheKey);
            return response()->json(['answer' => $answer]);
        }

        return response()->json(['answer' => null]);
    }

    public function callAnswer(Request $request)
    {
        $vendorId = Session::get('vendor_id');
        if (!$vendorId) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $customerId = $request->customer_id;
        $answer = $request->answer;

        // Update call status to connected if call was initiated by vendor
        $callIdCacheKey = "call_id_vendor_{$vendorId}_{$customerId}";
        $callId = \Illuminate\Support\Facades\Cache::get($callIdCacheKey);
        if ($callId) {
            CustomerChatCall::where('id', $callId)
                ->where('caller_type', 'vendor')
                ->where('caller_id', $vendorId)
                ->where('receiver_type', 'customer')
                ->where('receiver_id', $customerId)
                ->update(['call_status' => 'connected']);
        }

        // Also check for incoming call from customer
        $incomingCallIdCacheKey = "call_id_customer_{$customerId}_vendor_{$vendorId}";
        $incomingCallId = \Illuminate\Support\Facades\Cache::get($incomingCallIdCacheKey);
        if ($incomingCallId) {
            CustomerChatCall::where('id', $incomingCallId)
                ->where('caller_type', 'customer')
                ->where('caller_id', $customerId)
                ->where('receiver_type', 'vendor')
                ->where('receiver_id', $vendorId)
                ->update(['call_status' => 'connected']);
        }

        // Store answer in cache for customer to pick up
        $cacheKey = "call_answer_vendor_{$vendorId}_{$customerId}";
        \Illuminate\Support\Facades\Cache::put($cacheKey, $answer, 60);

        return response()->json(['success' => true]);
    }

    public function getCallIce(Request $request)
    {
        $vendorId = Session::get('vendor_id');
        if (!$vendorId) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $customerId = $request->customer_id;

        // Get ICE candidates from customer
        $cacheKey = "call_ice_customer_to_vendor_{$vendorId}_{$customerId}";
        $candidates = \Illuminate\Support\Facades\Cache::get($cacheKey, []);

        if (!empty($candidates)) {
            \Illuminate\Support\Facades\Cache::forget($cacheKey);
            return response()->json(['candidates' => $candidates]);
        }

        return response()->json(['candidates' => []]);
    }

    public function sendCallIce(Request $request)
    {
        $vendorId = Session::get('vendor_id');
        if (!$vendorId) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $customerId = $request->customer_id;
        $candidate = $request->candidate;

        // Store ICE candidate for customer to retrieve (format: call_ice_vendor_{vendorId}_{customerId})
        $cacheKey = "call_ice_vendor_{$vendorId}_{$customerId}";
        $candidates = \Illuminate\Support\Facades\Cache::get($cacheKey, []);
        $candidates[] = $candidate;
        \Illuminate\Support\Facades\Cache::put($cacheKey, $candidates, 60);

        return response()->json(['success' => true]);
    }

    public function callEnd(Request $request)
    {
        $vendorId = Session::get('vendor_id');
        if (!$vendorId) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $customerId = $request->customer_id;

        // Update call status to ended and calculate duration
        $callIdCacheKey = "call_id_vendor_{$vendorId}_{$customerId}";
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

        // Also check for incoming call from customer
        $incomingCallIdCacheKey = "call_id_customer_{$customerId}_vendor_{$vendorId}";
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

        // Clear all call-related cache
        $keys = [
            "call_offer_vendor_{$vendorId}_{$customerId}",
            "call_offer_customer_to_vendor_{$vendorId}_{$customerId}",
            "call_answer_vendor_{$vendorId}_{$customerId}",
            "call_ice_vendor_{$vendorId}_{$customerId}",
            "call_ice_customer_to_vendor_{$vendorId}_{$customerId}"
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
        $gemini = new GeminiService();
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

