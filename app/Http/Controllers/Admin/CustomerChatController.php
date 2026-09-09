<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CustomerChatMessage;
use App\Models\OperationLead;
use App\Models\Vendor;
use App\Models\JobRequest;
use App\Models\OperationDeploymentDetails;
use Illuminate\Http\Request;

class CustomerChatController extends Controller
{
    public function index(Request $request)
    {
        // Get filter parameters
        $customerId = $request->get('customer_id');
        $vendorId = $request->get('vendor_id');
        $freelancerId = $request->get('freelancer_id');
        $search = $request->get('search');

        // Get all unique customer-vendor/freelancer chat pairs
        $query = CustomerChatMessage::query();

        // Apply filters
        if ($customerId) {
            $query->where(function($q) use ($customerId) {
                $q->where('sender_type', 'customer')
                  ->where('sender_id', $customerId)
                  ->orWhere(function($q2) use ($customerId) {
                      $q2->where('receiver_type', 'customer')
                         ->where('receiver_id', $customerId);
                  });
            });
        }

        if ($vendorId) {
            $query->where(function($q) use ($vendorId) {
                $q->where('sender_type', 'vendor')
                  ->where('sender_id', $vendorId)
                  ->orWhere(function($q2) use ($vendorId) {
                      $q2->where('receiver_type', 'vendor')
                         ->where('receiver_id', $vendorId);
                  });
            });
        }

        if ($freelancerId) {
            $query->where(function($q) use ($freelancerId) {
                $q->where('sender_type', 'freelancer')
                  ->where('sender_id', $freelancerId)
                  ->orWhere(function($q2) use ($freelancerId) {
                      $q2->where('receiver_type', 'freelancer')
                         ->where('receiver_id', $freelancerId);
                  });
            });
        }

        // Get all messages without eager loading (will load manually)
        $messages = $query->orderBy('created_at', 'desc')->get();

        // Bulk load all related models to avoid N+1 queries
        $customerIds = $messages->where('sender_type', 'customer')->pluck('sender_id')
            ->merge($messages->where('receiver_type', 'customer')->pluck('receiver_id'))
            ->unique();
        $vendorIds = $messages->where('sender_type', 'vendor')->pluck('sender_id')
            ->merge($messages->where('receiver_type', 'vendor')->pluck('receiver_id'))
            ->unique();
        $freelancerIds = $messages->where('sender_type', 'freelancer')->pluck('sender_id')
            ->merge($messages->where('receiver_type', 'freelancer')->pluck('receiver_id'))
            ->unique();

        $customers = OperationLead::whereIn('id', $customerIds)->get()->keyBy('id');
        $vendors = Vendor::whereIn('id', $vendorIds)->get()->keyBy('id');
        $freelancers = JobRequest::whereIn('id', $freelancerIds)->get()->keyBy('id');

        // Group messages by customer-vendor/freelancer pairs
        $chatPairs = [];
        
        foreach ($messages as $message) {
            $customer = null;
            $vendor = null;
            $freelancer = null;
            
            // Get sender and receiver from pre-loaded collections
            $sender = null;
            $receiver = null;
            
            if ($message->sender_type === 'customer' && isset($customers[$message->sender_id])) {
                $sender = $customers[$message->sender_id];
            } elseif ($message->sender_type === 'vendor' && isset($vendors[$message->sender_id])) {
                $sender = $vendors[$message->sender_id];
            } elseif ($message->sender_type === 'freelancer' && isset($freelancers[$message->sender_id])) {
                $sender = $freelancers[$message->sender_id];
            }
            
            if ($message->receiver_type === 'customer' && isset($customers[$message->receiver_id])) {
                $receiver = $customers[$message->receiver_id];
            } elseif ($message->receiver_type === 'vendor' && isset($vendors[$message->receiver_id])) {
                $receiver = $vendors[$message->receiver_id];
            } elseif ($message->receiver_type === 'freelancer' && isset($freelancers[$message->receiver_id])) {
                $receiver = $freelancers[$message->receiver_id];
            }
            
            // Determine customer and vendor/freelancer
            if ($message->sender_type === 'customer' && $sender) {
                $customer = $sender;
                if ($message->receiver_type === 'vendor' && $receiver) {
                    $vendor = $receiver;
                } elseif ($message->receiver_type === 'freelancer' && $receiver) {
                    $freelancer = $receiver;
                }
            } elseif ($message->receiver_type === 'customer' && $receiver) {
                $customer = $receiver;
                if ($message->sender_type === 'vendor' && $sender) {
                    $vendor = $sender;
                } elseif ($message->sender_type === 'freelancer' && $sender) {
                    $freelancer = $sender;
                }
            }

            if (!$customer) continue;

            // Create unique key for chat pair
            $key = 'customer_' . $customer->id . '_';
            if ($vendor) {
                $key .= 'vendor_' . $vendor->id;
            } elseif ($freelancer) {
                $key .= 'freelancer_' . $freelancer->id;
            } else {
                continue;
            }

            // Initialize chat pair if not exists
            if (!isset($chatPairs[$key])) {
                $chatPairs[$key] = [
                    'customer' => $customer,
                    'vendor' => $vendor,
                    'freelancer' => $freelancer,
                    'messages' => [],
                    'last_message' => null,
                    'message_count' => 0,
                    'unread_count' => 0
                ];
            }

            // Add message to pair
            $chatPairs[$key]['messages'][] = $message;
            $chatPairs[$key]['message_count']++;
            
            // Track last message
            if (!$chatPairs[$key]['last_message'] || 
                $message->created_at > $chatPairs[$key]['last_message']->created_at) {
                $chatPairs[$key]['last_message'] = $message;
            }

            // Count unread messages (from customer to vendor/freelancer)
            if ($message->sender_type === 'customer' && !$message->is_read) {
                $chatPairs[$key]['unread_count']++;
            }
        }

        // Apply search filter
        if ($search) {
            $chatPairs = array_filter($chatPairs, function($pair) use ($search) {
                $customerName = strtolower($pair['customer']->customer_name ?? '');
                $vendorName = $pair['vendor'] ? strtolower($pair['vendor']->name ?? '') : '';
                $freelancerName = $pair['freelancer'] ? strtolower($pair['freelancer']->name ?? '') : '';
                $searchLower = strtolower($search);
                
                return strpos($customerName, $searchLower) !== false ||
                       strpos($vendorName, $searchLower) !== false ||
                       strpos($freelancerName, $searchLower) !== false;
            });
        }

        // Sort by last message time (most recent first)
        usort($chatPairs, function($a, $b) {
            $aTime = $a['last_message'] ? strtotime($a['last_message']->created_at) : 0;
            $bTime = $b['last_message'] ? strtotime($b['last_message']->created_at) : 0;
            return $bTime <=> $aTime;
        });

        // Get all customers, vendors, and freelancers for filters
        $customers = OperationLead::select('id', 'customer_name', 'contact_no')
            ->orderBy('customer_name', 'asc')
            ->get();
        
        $vendors = Vendor::select('id', 'name', 'contact_no')
            ->orderBy('name', 'asc')
            ->get();
        
        $freelancers = JobRequest::select('id', 'name', 'contact_no')
            ->orderBy('name', 'asc')
            ->get();

        // If API request, return JSON data
        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'chat_pairs' => array_values($chatPairs),
                'customers' => $customers,
                'vendors' => $vendors,
                'freelancers' => $freelancers,
            ]);
        }

        return view('admin.customer_chats.index', compact(
            'chatPairs',
            'customers',
            'vendors',
            'freelancers',
            'customerId',
            'vendorId',
            'freelancerId',
            'search'
        ));
    }

    public function indexApi(Request $request)
    {
        // Same logic as index but always return JSON
        return $this->index($request);
    }

    public function getMessages(Request $request)
    {
        $customerId = $request->get('customer_id');
        $vendorId = $request->get('vendor_id');
        $freelancerId = $request->get('freelancer_id');

        if (!$customerId || (!$vendorId && !$freelancerId)) {
            return response()->json(['error' => 'Invalid parameters'], 400);
        }

        $query = CustomerChatMessage::query();

        if ($vendorId) {
            $query->where(function($q) use ($customerId, $vendorId) {
                $q->where('sender_type', 'customer')
                  ->where('sender_id', $customerId)
                  ->where('receiver_type', 'vendor')
                  ->where('receiver_id', $vendorId);
            })->orWhere(function($q) use ($customerId, $vendorId) {
                $q->where('sender_type', 'vendor')
                  ->where('sender_id', $vendorId)
                  ->where('receiver_type', 'customer')
                  ->where('receiver_id', $customerId);
            });
        } elseif ($freelancerId) {
            $query->where(function($q) use ($customerId, $freelancerId) {
                $q->where('sender_type', 'customer')
                  ->where('sender_id', $customerId)
                  ->where('receiver_type', 'freelancer')
                  ->where('receiver_id', $freelancerId);
            })->orWhere(function($q) use ($customerId, $freelancerId) {
                $q->where('sender_type', 'freelancer')
                  ->where('sender_id', $freelancerId)
                  ->where('receiver_type', 'customer')
                  ->where('receiver_id', $customerId);
            });
        }

        $messages = $query->orderBy('created_at', 'asc')->get();

        // Bulk load all related models to avoid N+1 queries
        $customerIds = $messages->where('sender_type', 'customer')->pluck('sender_id')
            ->merge($messages->where('receiver_type', 'customer')->pluck('receiver_id'))
            ->unique();
        $vendorIds = $messages->where('sender_type', 'vendor')->pluck('sender_id')
            ->merge($messages->where('receiver_type', 'vendor')->pluck('receiver_id'))
            ->unique();
        $freelancerIds = $messages->where('sender_type', 'freelancer')->pluck('sender_id')
            ->merge($messages->where('receiver_type', 'freelancer')->pluck('receiver_id'))
            ->unique();
        $replyToIds = $messages->whereNotNull('reply_to_id')->pluck('reply_to_id')->unique();

        $customers = OperationLead::whereIn('id', $customerIds)->get()->keyBy('id');
        $vendors = Vendor::whereIn('id', $vendorIds)->get()->keyBy('id');
        $freelancers = JobRequest::whereIn('id', $freelancerIds)->get()->keyBy('id');
        $repliedToMessages = CustomerChatMessage::whereIn('id', $replyToIds)->get()->keyBy('id');

        // Load relationships manually for each message
        $messages->each(function($message) use ($customers, $vendors, $freelancers, $repliedToMessages) {
            // Load sender
            if ($message->sender_type === 'customer' && isset($customers[$message->sender_id])) {
                $message->sender = $customers[$message->sender_id];
            } elseif ($message->sender_type === 'vendor' && isset($vendors[$message->sender_id])) {
                $message->sender = $vendors[$message->sender_id];
            } elseif ($message->sender_type === 'freelancer' && isset($freelancers[$message->sender_id])) {
                $message->sender = $freelancers[$message->sender_id];
            }
            
            // Load receiver
            if ($message->receiver_type === 'customer' && isset($customers[$message->receiver_id])) {
                $message->receiver = $customers[$message->receiver_id];
            } elseif ($message->receiver_type === 'vendor' && isset($vendors[$message->receiver_id])) {
                $message->receiver = $vendors[$message->receiver_id];
            } elseif ($message->receiver_type === 'freelancer' && isset($freelancers[$message->receiver_id])) {
                $message->receiver = $freelancers[$message->receiver_id];
            }
            
            // Load repliedTo if exists
            if ($message->reply_to_id && isset($repliedToMessages[$message->reply_to_id])) {
                $message->repliedTo = $repliedToMessages[$message->reply_to_id];
            }
        });

        return response()->json($messages);
    }
}

