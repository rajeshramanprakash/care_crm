<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Models\User;
use App\Services\TicketService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class TicketController extends Controller
{
    protected $ticketService;

    public function __construct(TicketService $ticketService)
    {
        $this->ticketService = $ticketService;
    }

    /**
     * Get tickets for the authenticated user
     */
    public function index(Request $request)
    {
        try {
            $user = Auth::user();

            // Check if user has ticket access
            // Admin (role_id=1), TPA (role_id=8), Vendor (role_id=10) have automatic access
            if (!in_array((int)$user->role_id, [1, 8, 10]) && !$user->is_ticket_enabled) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ticket access not enabled for this user'
                ], 403);
            }

            $status = $request->get('status', 'all');
            $page = $request->get('page', 1);
            $perPage = 10; // 10 tickets per page

            // Get tickets based on user role
            if ($user->role_id == 1) { // Admin
                $tickets = $this->ticketService->getAllTicketsPaginated($status === 'all' ? null : $status, $page, $perPage);
            } elseif ($user->role_id == 8) { // TPA
                $tickets = $this->ticketService->getUserTicketsPaginated($user, $status === 'all' ? null : $status, $page, $perPage);
            } elseif ($user->role_id == 10) { // Vendor
                $tickets = $this->ticketService->getUserTicketsPaginated($user, $status === 'all' ? null : $status, $page, $perPage);
            } else {
                // Other roles with ticket access
                $tickets = $this->ticketService->getAllTicketsPaginated($status === 'all' ? null : $status, $page, $perPage);
            }

            return response()->json([
                'success' => true,
                'data' => collect($tickets->items())->map(function ($ticket) {
                    return [
                        'id' => $ticket->id,
                        'ticket_number' => $ticket->ticket_number,
                        'case_code' => $ticket->case->case_code ?? null,
                        'claim_no' => $ticket->case->claim_no ?? null,
                        'patient_name' => $ticket->case->patient_name ?? null,
                        'case_type' => $ticket->case_type,
                        'status' => $ticket->status,
                        'submission_time' => $ticket->submission_time,
                        'created_at' => $ticket->created_at,
                        'vendor_name' => $ticket->vendor->f_name . ' ' . $ticket->vendor->l_name,
                        'tpa_name' => $ticket->tpa->f_name . ' ' . $ticket->tpa->l_name,
                        'unread_messages' => $ticket->messages()->where('is_read', false)->count(),
                    ];
                })->toArray(),
                'pagination' => [
                    'current_page' => $tickets->currentPage(),
                    'last_page' => $tickets->lastPage(),
                    'per_page' => $tickets->perPage(),
                    'total' => $tickets->total(),
                    'has_more_pages' => $tickets->hasMorePages(),
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching tickets: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get specific ticket with messages
     */
    public function show($id)
    {
        try {
            $user = Auth::user();

            // Check if user has ticket access
            // Admin (role_id=1), TPA (role_id=8), Vendor (role_id=10) have automatic access
            if (!in_array((int)$user->role_id, [1, 8, 10]) && !$user->is_ticket_enabled) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ticket access not enabled for this user'
                ], 403);
            }

            // Get ticket based on user role
            if ($user->role_id == 1) { // Admin
                $ticket = Ticket::with(['case', 'queryData', 'vendor', 'tpa', 'messages.sender', 'messages.recipient'])
                    ->findOrFail($id);
            } elseif ($user->role_id == 8) { // TPA
                $ticket = Ticket::with(['case', 'queryData', 'vendor', 'tpa', 'messages.sender', 'messages.recipient'])
                    ->where('tpa_id', $user->id)
                    ->findOrFail($id);
            } elseif ($user->role_id == 10) { // Vendor
                $ticket = Ticket::with(['case', 'queryData', 'vendor', 'tpa', 'messages.sender', 'messages.recipient'])
                    ->where('vendor_id', $user->id)
                    ->findOrFail($id);
            } else {
                // Other roles with ticket access
                $ticket = Ticket::with(['case', 'queryData', 'vendor', 'tpa', 'messages.sender', 'messages.recipient'])
                    ->findOrFail($id);
            }

            // Mark messages as read for this user
            $ticket->messages()->where('recipient_id', $user->id)
                ->where('is_read', false)
                ->update(['is_read' => true]);

                        // Filter messages based on user role
            $messages = $this->ticketService->getRoleSpecificMessages($ticket->messages, $user);

            return response()->json([
                'success' => true,
                'data' => [
                    'ticket' => [
                        'id' => $ticket->id,
                        'ticket_number' => $ticket->ticket_number,
                        'case_code' => $ticket->case->case_code ?? null,
                        'claim_no' => $ticket->case->claim_no ?? null,
                        'patient_name' => $ticket->case->patient_name ?? null,
                        'case_type' => $ticket->case_type,
                        'status' => $ticket->status,
                        'submission_time' => $ticket->submission_time,
                        'created_at' => $ticket->created_at,
                        'vendor_name' => $ticket->vendor->f_name . ' ' . $ticket->vendor->l_name,
                        'tpa_name' => $ticket->tpa->f_name . ' ' . $ticket->tpa->l_name,
                        'query_pdf' => $ticket->queryData && $ticket->queryData->query_pdf 
                            ? asset('storage/' . $ticket->queryData->query_pdf) 
                            : null,
                    ],
                    'messages' => $messages->values()->map(function ($message) {
                        return [
                            'id' => $message->id,
                            'message' => $message->message,
                            'message_type' => $message->message_type,
                            'is_read' => $message->is_read,
                            'created_at' => $message->created_at,
                            'sender_name' => $message->sender ? $message->sender->f_name . ' ' . $message->sender->l_name : 'System',
                        ];
                    })->toArray()
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching ticket: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Approve submission (TPA only)
     */
    public function approveSubmission(Request $request, $id)
    {
        try {
            $user = Auth::user();

            if ($user->role_id != 8) { // TPA role_id = 8
                return response()->json([
                    'success' => false,
                    'message' => 'Only TPA users can approve submissions'
                ], 403);
            }

            $validator = Validator::make($request->all(), [
                'submission_time' => 'required|date|after:now',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $ticket = Ticket::where('tpa_id', $user->id)->findOrFail($id);

            if ($ticket->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'Ticket is not in pending status'
                ], 400);
            }

            $this->ticketService->approveSubmission($ticket, $request->submission_time);

            return response()->json([
                'success' => true,
                'message' => 'Submission approved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error approving submission: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Submit claim (Vendor only)
     */
    public function submitClaim($id)
    {
        try {
            $user = Auth::user();

            if ($user->role_id != 10) { // Vendor role_id = 10
                return response()->json([
                    'success' => false,
                    'message' => 'Only vendor users can submit claims'
                ], 403);
            }

            $ticket = Ticket::where('vendor_id', $user->id)->findOrFail($id);

            if ($ticket->status !== 'approved') {
                return response()->json([
                    'success' => false,
                    'message' => 'Ticket must be approved before submission'
                ], 400);
            }

            $this->ticketService->submitClaim($ticket);

            return response()->json([
                'success' => true,
                'message' => 'Claim submitted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error submitting claim: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Close ticket
     */
    public function closeTicket($id)
    {
        try {
            $user = Auth::user();

            // Check if user has ticket access
            // Admin (role_id=1), TPA (role_id=8), Vendor (role_id=10) have automatic access
            if (!in_array((int)$user->role_id, [1, 8, 10]) && !$user->is_ticket_enabled) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ticket access not enabled for this user'
                ], 403);
            }

            // Get ticket based on user role
            if ($user->role_id == 1) { // Admin
                $ticket = Ticket::findOrFail($id);
            } elseif ($user->role_id == 8) { // TPA
                $ticket = Ticket::where('tpa_id', $user->id)->findOrFail($id);
            } elseif ($user->role_id == 10) { // Vendor
                $ticket = Ticket::where('vendor_id', $user->id)->findOrFail($id);
            } else {
                // Other roles with ticket access
                $ticket = Ticket::findOrFail($id);
            }

            if ($ticket->status === 'closed') {
                return response()->json([
                    'success' => false,
                    'message' => 'Ticket is already closed'
                ], 400);
            }

            $this->ticketService->closeTicket($ticket);

            return response()->json([
                'success' => true,
                'message' => 'Ticket closed successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error closing ticket: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get unread message count
     */
    public function getUnreadCount()
    {
        try {
            $user = Auth::user();

            // Check if user has ticket access
            // Admin (role_id=1), TPA (role_id=8), Vendor (role_id=10) have automatic access
            if (!in_array((int)$user->role_id, [1, 8, 10]) && !$user->is_ticket_enabled) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ticket access not enabled for this user'
                ], 403);
            }

            $count = $this->ticketService->getUnreadCount($user);

            return response()->json([
                'success' => true,
                'data' => [
                    'unread_count' => $count
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching unread count: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get ticket statistics (Admin only)
     */
    public function getStatistics()
    {
        try {
            $user = Auth::user();

            if ($user->role_id != 1) { // Admin role_id = 1
                return response()->json([
                    'success' => false,
                    'message' => 'Only admin users can view statistics'
                ], 403);
            }

            $statistics = $this->ticketService->getStatistics();

            return response()->json([
                'success' => true,
                'data' => $statistics
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching statistics: ' . $e->getMessage()
            ], 500);
        }
    }
}
