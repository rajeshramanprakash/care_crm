<?php

namespace App\Http\Controllers\TPA;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Services\TicketService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TicketController extends Controller
{
    protected $ticketService;

    public function __construct(TicketService $ticketService)
    {
        $this->ticketService = $ticketService;
    }

    /**
     * Display TPA's tickets
     */
    public function index(Request $request)
    {
        $status = $request->get('status', 'all');
        $user = Auth::user();
        $tickets = $this->ticketService->getUserTickets($user, $status === 'all' ? null : $status);

        return view('tpa.tickets.index', compact('tickets', 'status'));
    }

    /**
     * Display specific ticket with messages
     */
    public function show($id)
    {
        $user = Auth::user();
        $ticket = Ticket::with(['case', 'queryData', 'vendor', 'tpa', 'messages.sender', 'messages.recipient'])
            ->where('tpa_id', $user->id)
            ->findOrFail($id);

                // Filter messages based on user role
        $ticket->messages = $this->ticketService->getRoleSpecificMessages($ticket->messages, $user);

        // Mark role-specific messages as read for TPA
        $roleSpecificMessages = $this->ticketService->getRoleSpecificMessages($ticket->messages, $user);
        $roleSpecificMessageIds = $roleSpecificMessages->pluck('id')->toArray();

        if (!empty($roleSpecificMessageIds)) {
            $ticket->messages()->whereIn('id', $roleSpecificMessageIds)
                ->where('recipient_id', $user->id)
                ->where('is_read', false)
                ->update(['is_read' => true]);
        }

        return view('tpa.tickets.show', compact('ticket'));
    }

    /**
     * Approve submission with time selection
     */
    public function approveSubmission(Request $request, $id)
    {
        try {
            $request->validate([
                'submission_time' => 'required|date|after:now',
            ]);

            $user = Auth::user();
            $ticket = Ticket::where('tpa_id', $user->id)
                ->where('status', 'pending')
                ->findOrFail($id);

            $this->ticketService->approveSubmission($ticket, $request->submission_time);

            return response()->json([
                'success' => true,
                'message' => 'Submission approved with time selected.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Close ticket (TPA can close tickets)
     */
    public function close($id)
    {
        try {
            $user = Auth::user();
            $ticket = Ticket::where('tpa_id', $user->id)
                ->findOrFail($id);

            $this->ticketService->closeTicket($ticket);

            return response()->json([
                'success' => true,
                'message' => 'Ticket closed successfully.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get tickets for AJAX
     */
    public function ajaxList(Request $request)
    {
        try {
            $user = Auth::user();
            $status = $request->get('status', 'all');
            $tickets = $this->ticketService->getUserTickets($user, $status === 'all' ? null : $status);

            \Log::info('TPA ticket AJAX called. User ID: ' . $user->id . ', Status: ' . $status . ', Tickets count: ' . $tickets->count());

            $data = $tickets->map(function ($ticket) {
                try {
                    return [
                        'id' => $ticket->id,
                        'ticket_number' => $ticket->ticket_number,
                        'case_code' => $ticket->case->case_code ?? 'N/A',
                        'claim_no' => $ticket->case->claim_no ?? 'N/A',
                        'patient_name' => $ticket->case->name ?? 'N/A',
                        'vendor_name' => $ticket->vendor->f_name ?? 'N/A',
                        'case_type' => $ticket->case_type_text,
                        'status' => $ticket->status,
                        'is_active' => $ticket->is_active ? 'Active' : 'Closed',
                        'created_at' => $ticket->created_at->format('d/m/Y H:i'),
                        'actions' => view('tpa.tickets.partials.actions', compact('ticket'))->render(),
                    ];
                } catch (\Exception $e) {
                    \Log::error('Error processing TPA ticket ' . $ticket->id . ': ' . $e->getMessage());
                    return [
                        'id' => $ticket->id,
                        'ticket_number' => $ticket->ticket_number ?? 'N/A',
                        'case_code' => 'Error',
                        'claim_no' => 'Error',
                        'patient_name' => 'Error',
                        'vendor_name' => 'Error',
                        'case_type' => 'Error',
                        'status' => 'Error',
                        'is_active' => 'Error',
                        'created_at' => 'Error',
                        'actions' => 'Error',
                    ];
                }
            });

            \Log::info('TPA ticket AJAX response prepared. Data count: ' . $data->count());

            return response()->json([
                'data' => $data
            ]);
        } catch (\Exception $e) {
            \Log::error('TPA ticket AJAX error: ' . $e->getMessage());
            return response()->json([
                'error' => $e->getMessage(),
                'data' => []
            ], 500);
        }
    }

    /**
     * Get unread message count
     */
    public function unreadCount()
    {
        $user = Auth::user();
        $count = $this->ticketService->getUnreadCount($user);

        return response()->json(['unread_count' => $count]);
    }
}
