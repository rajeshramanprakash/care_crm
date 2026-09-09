<?php

namespace App\Http\Controllers\Doctor;

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
     * Display all tickets
     */
    public function index(Request $request)
    {
        $status = $request->get('status', 'all');
        $tickets = $this->ticketService->getAllTickets($status === 'all' ? null : $status);

        return view('doctor.tickets.index', compact('tickets', 'status'));
    }

    /**
     * Display specific ticket with messages
     */
    public function show($id)
    {
        $ticket = Ticket::with(['case', 'queryData', 'vendor', 'tpa', 'messages.sender', 'messages.recipient'])
            ->findOrFail($id);

        // Mark messages as read for doctor user
        $ticket->messages()->where('is_read', false)->update(['is_read' => true]);

        return view('doctor.tickets.show', compact('ticket'));
    }

    /**
     * Close a ticket
     */
    public function close($id)
    {
        $ticket = Ticket::findOrFail($id);
        $this->ticketService->closeTicket($ticket);

        return redirect()->route('doctor.tickets.index')
            ->with('success', 'Ticket closed successfully.');
    }

    /**
     * Get tickets for AJAX
     */
    public function ajaxList(Request $request)
    {
        try {
            $status = $request->get('status', 'all');
            $tickets = $this->ticketService->getAllTickets($status === 'all' ? null : $status);

            \Log::info('Doctor ticket AJAX called. Status: ' . $status . ', Tickets count: ' . $tickets->count());

            $data = $tickets->map(function ($ticket) {
                try {
                    return [
                        'id' => $ticket->id,
                        'ticket_number' => $ticket->ticket_number,
                        'case_code' => $ticket->case->case_code ?? 'N/A',
                        'claim_no' => $ticket->case->claim_no ?? 'N/A',
                        'patient_name' => $ticket->case->name ?? 'N/A',
                        'vendor_name' => $ticket->vendor->f_name ?? 'N/A',
                        'tpa_name' => $ticket->tpa->f_name ?? 'N/A',
                        'case_type' => $ticket->case_type_text,
                        'status' => $ticket->status,
                        'is_active' => $ticket->is_active ? 'Active' : 'Closed',
                        'created_at' => $ticket->created_at->format('d/m/Y H:i'),
                        'actions' => view('doctor.tickets.partials.actions', compact('ticket'))->render(),
                    ];
                } catch (\Exception $e) {
                    \Log::error('Error processing ticket ' . $ticket->id . ': ' . $e->getMessage());
                    return [
                        'id' => $ticket->id,
                        'ticket_number' => $ticket->ticket_number ?? 'N/A',
                        'case_code' => 'Error',
                        'claim_no' => 'Error',
                        'patient_name' => 'Error',
                        'vendor_name' => 'Error',
                        'tpa_name' => 'Error',
                        'case_type' => 'Error',
                        'status' => 'Error',
                        'is_active' => 'Error',
                        'created_at' => 'Error',
                        'actions' => 'Error',
                    ];
                }
            });

            \Log::info('Doctor ticket AJAX response prepared. Data count: ' . $data->count());

            return response()->json([
                'data' => $data
            ]);
        } catch (\Exception $e) {
            \Log::error('Doctor ticket AJAX error: ' . $e->getMessage());
            return response()->json([
                'error' => $e->getMessage(),
                'data' => []
            ], 500);
        }
    }

    /**
     * Get ticket statistics
     */
    public function statistics()
    {
        $stats = [
            'total' => Ticket::count(),
            'active' => Ticket::where('is_active', true)->count(),
            'closed' => Ticket::where('is_active', false)->count(),
            'pending' => Ticket::where('status', 'pending')->count(),
            'approved' => Ticket::where('status', 'approved')->count(),
            'submitted' => Ticket::where('status', 'submitted')->count(),
        ];

        return response()->json($stats);
    }
}
