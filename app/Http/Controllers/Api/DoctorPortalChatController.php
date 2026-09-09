<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\ResolvesDoctorForApiUser;
use App\Http\Controllers\Controller;
use App\Models\ConsultationWebsiteBooking;
use App\Models\CustomerChatCall;
use App\Models\CustomerChatMessage;
use App\Models\OperationLead;
use App\Services\GeminiService;
use App\Support\DoctorCustomerChatRealtime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DoctorPortalChatController extends Controller
{
    use ResolvesDoctorForApiUser;

    private function absoluteProfileImageUrl(?string $profileImage, Request $request): ?string
    {
        if (!$profileImage) {
            return null;
        }
        if (strpos($profileImage, 'http') === 0) {
            return $profileImage;
        }
        $storagePath = Storage::disk('public')->url($profileImage);
        if (strpos($storagePath, 'http') === 0) {
            return $storagePath;
        }
        $scheme = $request->getScheme();
        $host = $request->getHost();
        $port = $request->getPort();
        $baseUrl = $scheme.'://'.$host.($port && $port != 80 && $port != 443 ? ':'.$port : '');

        return $baseUrl.$storagePath;
    }

    private function lastMessageForContactThread($doctor, array $operationLeadIds)
    {
        return CustomerChatMessage::where(function ($q) use ($doctor, $operationLeadIds) {
            $q->where(function ($q2) use ($doctor, $operationLeadIds) {
                $q2->where('sender_type', 'doctor')
                    ->where('sender_id', $doctor->id)
                    ->where('receiver_type', 'customer')
                    ->whereIn('receiver_id', $operationLeadIds);
            })->orWhere(function ($q2) use ($doctor, $operationLeadIds) {
                $q2->where('sender_type', 'customer')
                    ->whereIn('sender_id', $operationLeadIds)
                    ->where('receiver_type', 'doctor')
                    ->where('receiver_id', $doctor->id);
            });
        })->orderByDesc('created_at')->orderByDesc('id')->first();
    }

    /**
     * Customers (operation leads) who booked this doctor via consultation website.
     */
    public function chats(Request $request)
    {
        $doctor = $this->doctorForUser($request->user());
        if (!$doctor) {
            return response()->json(['success' => false, 'message' => 'Doctor profile not found'], 404);
        }

        $bookings = ConsultationWebsiteBooking::query()
            ->where('doctor_request_id', $doctor->id)
            ->with(['operationLead'])
            ->orderByDesc('created_at')
            ->get();

        /** @var array<string, \App\Models\OperationLead> representative lead from earliest booking hit */
        $representativeLeadByContact = [];
        foreach ($bookings as $booking) {
            $lead = $booking->operationLead;
            if (!$lead && $booking->contact_no) {
                $lead = OperationLead::where('contact_no', $booking->contact_no)->first();
            }
            if (!$lead || !$lead->contact_no) {
                continue;
            }
            $contactNo = $lead->contact_no;
            if (isset($representativeLeadByContact[$contactNo])) {
                continue;
            }
            $representativeLeadByContact[$contactNo] = $lead;
        }

        $contactNos = array_keys($representativeLeadByContact);
        if ($contactNos === []) {
            return response()->json([
                'success' => true,
                'customers' => [],
            ]);
        }

        $leadsGrouped = OperationLead::query()
            ->whereIn('contact_no', $contactNos)
            ->get(['id', 'contact_no', 'customer_name', 'profile_image'])
            ->groupBy('contact_no');

        $leadIdsByContact = [];
        $allLeadIds = [];
        foreach ($contactNos as $contactNo) {
            $group = $leadsGrouped->get($contactNo);
            if ($group && $group->isNotEmpty()) {
                $ids = $group->pluck('id')->unique()->sort()->values()->all();
            } else {
                $rep = $representativeLeadByContact[$contactNo];
                $ids = [$rep->id];
            }
            $leadIdsByContact[$contactNo] = $ids;
            foreach ($ids as $id) {
                $allLeadIds[] = $id;
            }
        }
        $allLeadIds = array_values(array_unique($allLeadIds));

        $unreadBySender = CustomerChatMessage::query()
            ->where('receiver_type', 'doctor')
            ->where('receiver_id', $doctor->id)
            ->where('sender_type', 'customer')
            ->whereIn('sender_id', $allLeadIds)
            ->where('is_read', false)
            ->selectRaw('sender_id, COUNT(*) as unread_total')
            ->groupBy('sender_id')
            ->pluck('unread_total', 'sender_id');

        $leadIdToContactNo = [];
        foreach ($leadIdsByContact as $contactNo => $ids) {
            foreach ($ids as $lid) {
                $leadIdToContactNo[(int) $lid] = $contactNo;
            }
        }

        $recentLimit = min(2500, max(350, count($contactNos) * 8));
        $recentMessages = CustomerChatMessage::query()
            ->where(function ($q) use ($doctor, $allLeadIds) {
                $q->where(function ($q2) use ($doctor, $allLeadIds) {
                    $q2->where('sender_type', 'doctor')
                        ->where('sender_id', $doctor->id)
                        ->where('receiver_type', 'customer')
                        ->whereIn('receiver_id', $allLeadIds);
                })->orWhere(function ($q2) use ($doctor, $allLeadIds) {
                    $q2->where('sender_type', 'customer')
                        ->whereIn('sender_id', $allLeadIds)
                        ->where('receiver_type', 'doctor')
                        ->where('receiver_id', $doctor->id);
                });
            })
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit($recentLimit)
            ->get();

        $lastMessageByContact = [];
        foreach ($recentMessages as $msg) {
            $contactNo = null;
            if ($msg->sender_type === 'customer') {
                $contactNo = $leadIdToContactNo[(int) $msg->sender_id] ?? null;
            } elseif ($msg->sender_type === 'doctor' && $msg->receiver_type === 'customer') {
                $contactNo = $leadIdToContactNo[(int) $msg->receiver_id] ?? null;
            }
            if ($contactNo !== null && ! array_key_exists($contactNo, $lastMessageByContact)) {
                $lastMessageByContact[$contactNo] = $msg;
            }
        }

        $customersByContact = collect();
        foreach ($contactNos as $contactNo) {
            $operationLeadIds = $leadIdsByContact[$contactNo];
            sort($operationLeadIds, SORT_NUMERIC);
            $primaryCustomerId = $operationLeadIds[0];

            $unreadCount = 0;
            foreach ($operationLeadIds as $lid) {
                $unreadCount += (int) ($unreadBySender[(int) $lid] ?? 0);
            }

            $lastMessage = $lastMessageByContact[$contactNo]
                ?? $this->lastMessageForContactThread($doctor, $operationLeadIds);

            $repGroup = $leadsGrouped->get($contactNo);
            /** @var \App\Models\OperationLead $lead */
            $lead = ($repGroup && $repGroup->isNotEmpty()) ? $repGroup->sortBy('id')->first() : $representativeLeadByContact[$contactNo];

            $profileImage = $lead->profile_image ?? null;
            $customersByContact->put($contactNo, [
                'id' => $primaryCustomerId,
                'customer_name' => $lead->customer_name ?? 'Customer',
                'contact_no' => $lead->contact_no ?? 'N/A',
                'profile_image' => $profileImage,
                'profile_image_url' => $this->absoluteProfileImageUrl($profileImage, $request),
                'unread_count' => $unreadCount,
                'last_message' => $lastMessage,
            ]);
        }

        return response()->json([
            'success' => true,
            'customers' => $customersByContact->values(),
        ]);
    }

    public function getMessages($customerId, Request $request)
    {
        $doctor = $this->doctorForUser($request->user());
        if (!$doctor) {
            return response()->json(['success' => false, 'message' => 'Doctor profile not found'], 404);
        }

        $operationLeadIds = ConsultationWebsiteBooking::operationLeadIdsForDoctorCustomerChatOrDeny(
            $doctor->id,
            (int) $customerId
        );
        if ($operationLeadIds === null) {
            return response()->json(['success' => false, 'message' => 'No booking with this customer'], 403);
        }

        $query = CustomerChatMessage::where(function ($q) use ($operationLeadIds, $doctor) {
            $q->where(function ($subQ) use ($operationLeadIds, $doctor) {
                $subQ->where('sender_type', 'doctor')
                    ->where('sender_id', $doctor->id)
                    ->where('receiver_type', 'customer')
                    ->whereIn('receiver_id', $operationLeadIds);
            })
                ->orWhere(function ($subQ) use ($operationLeadIds, $doctor) {
                    $subQ->where('sender_type', 'customer')
                        ->whereIn('sender_id', $operationLeadIds)
                        ->where('receiver_type', 'doctor')
                        ->where('receiver_id', $doctor->id);
                });
        });

        if ($request->has('last_message_id') && $request->last_message_id > 0) {
            $query->where('id', '>', $request->last_message_id);
        }

        $messages = $query->with('repliedTo')->orderBy('created_at', 'asc')->get();

        $callsQuery = CustomerChatCall::where(function ($q) use ($operationLeadIds, $doctor) {
            $q->where('caller_type', 'doctor')
                ->where('caller_id', $doctor->id)
                ->where('receiver_type', 'customer')
                ->whereIn('receiver_id', $operationLeadIds);
        })->orWhere(function ($q) use ($operationLeadIds, $doctor) {
            $q->where('caller_type', 'customer')
                ->whereIn('caller_id', $operationLeadIds)
                ->where('receiver_type', 'doctor')
                ->where('receiver_id', $doctor->id);
        });

        if ($request->has('last_message_id') && $request->last_message_id > 0) {
            $lastMessage = CustomerChatMessage::find($request->last_message_id);
            if ($lastMessage) {
                $callsQuery->where('created_at', '>', $lastMessage->created_at);
            }
        }

        $calls = $callsQuery->orderBy('created_at', 'asc')->get();

        $items = collect();
        foreach ($messages as $message) {
            $items->push([
                'type' => 'message',
                'id' => $message->id,
                'created_at' => $message->created_at->toISOString(),
                'data' => $message,
            ]);
        }
        foreach ($calls as $call) {
            $items->push([
                'type' => 'call',
                'id' => 'call_'.$call->id,
                'created_at' => ($call->call_started_at ?? $call->created_at)->toISOString(),
                'data' => $call,
            ]);
        }

        $chatSchedule = ConsultationWebsiteBooking::doctorCustomerChatScheduleStatus(
            (int) $doctor->id,
            (int) $customerId
        );

        return response()->json([
            'items' => $items->sortBy('created_at')->values(),
            'chat_schedule' => $chatSchedule,
        ]);
    }

    public function sendMessage(Request $request)
    {
        $doctor = $this->doctorForUser($request->user());
        if (!$doctor) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $request->validate([
            'receiver_id' => 'required|integer|exists:operation_leads,id',
            'message' => 'required_without:attachment|string|nullable',
            'attachment' => 'nullable|file|max:10240',
            'reply_to_id' => 'nullable|integer|exists:customer_chat_messages,id',
        ]);

        if (!ConsultationWebsiteBooking::doctorHasBookedAnyLead($doctor->id, (int) $request->receiver_id)) {
            return response()->json(['success' => false, 'message' => 'No booking with this customer'], 403);
        }

        $chatSchedule = ConsultationWebsiteBooking::doctorCustomerChatScheduleStatus(
            (int) $doctor->id,
            (int) $request->receiver_id
        );
        if (! $chatSchedule['allowed']) {
            return response()->json([
                'success' => false,
                'message' => $chatSchedule['user_message'] ?? 'Chat is not available outside your appointment window.',
                'chat_schedule' => $chatSchedule,
            ], 403);
        }

        $message = new CustomerChatMessage;
        $message->sender_type = 'doctor';
        $message->sender_id = $doctor->id;
        $message->receiver_type = 'customer';
        $message->receiver_id = $request->receiver_id;
        $message->message = $request->message;
        $message->is_read = false;

        if ($request->has('reply_to_id')) {
            $message->reply_to_id = $request->reply_to_id;
        }

        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $filename = time().'_'.uniqid().'.'.$file->getClientOriginalExtension();
            $path = $file->storeAs('attachments', $filename, 'public');
            $message->attachment = $path;
            $message->attachment_type = $file->getMimeType();
        }

        $message->save();

        return response()->json([
            'success' => true,
            'message' => $message,
        ]);
    }

    public function markAsRead($customerId, Request $request)
    {
        $doctor = $this->doctorForUser($request->user());
        if (!$doctor) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $customer = OperationLead::find($customerId);
        $operationLeadIds = [(int) $customerId];
        if ($customer && $customer->contact_no) {
            $operationLeadIds = array_unique(array_merge(
                $operationLeadIds,
                OperationLead::where('contact_no', $customer->contact_no)->pluck('id')->toArray()
            ));
        }

        CustomerChatMessage::where('receiver_type', 'doctor')
            ->where('receiver_id', $doctor->id)
            ->where('sender_type', 'customer')
            ->whereIn('sender_id', $operationLeadIds)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        DoctorCustomerChatRealtime::broadcastUnreadRefresh((int) $doctor->id, (int) $customerId, 'mark_read');

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
