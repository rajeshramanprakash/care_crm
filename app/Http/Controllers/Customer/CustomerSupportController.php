<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use App\Models\SupportTicketMessage;
use App\Support\CustomerPortal;
use App\Support\DeskClient;
use App\Support\SupportTicketService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CustomerSupportController extends Controller
{
    public function index(Request $request)
    {
        $customer = CustomerPortal::current();
        $status = $request->query('status');

        $tickets = SupportTicket::query()
            ->where('customer_contact_no', $customer['contact_no'])
            ->when($status === 'active', fn ($q) => $q->whereNotIn('status', ['resolved', 'closed']))
            ->when($status === 'done', fn ($q) => $q->whereIn('status', ['resolved', 'closed']))
            ->orderByDesc('updated_at')
            ->paginate(15)
            ->withQueryString();

        return view('customer.support.index', compact('customer', 'tickets', 'status'));
    }

    public function create()
    {
        return view('customer.support.create', ['customer' => CustomerPortal::current()]);
    }

    public function store(Request $request)
    {
        $customer = CustomerPortal::current();
        $isBug = $request->input('type') === 'bug';
        $data = $request->validate([
            'type' => 'required|in:' . implode(',', array_keys(SupportTicket::TYPES)),
            'category' => $isBug ? 'nullable' : 'required|in:' . implode(',', array_keys(SupportTicket::SUPPORT_CATEGORIES)),
            'priority' => 'required|in:' . implode(',', array_keys(SupportTicket::CUSTOMER_PRIORITIES)),
            'subject' => 'required|string|max:150',
            'message' => 'required|string|max:5000',
            'attachment' => SupportTicketService::ATTACHMENT_RULE,
        ], [], ['subject' => 'title', 'message' => 'what happened']);
        if ($isBug) {
            $data['category'] = 'bug';
        }

        $ticket = SupportTicketService::open($customer, $data, $request->file('attachment'));

        return redirect()->route('customer.support.show', $ticket->id)
            ->with('status', ['alert_type' => 'success', 'message' => 'Ticket ' . $ticket->ticket_no . ' created. ' . ($isBug ? 'It has been sent to our technical team.' : 'Our team will reply soon.')]);
    }

    public function show(int $id)
    {
        $ticket = $this->ownedTicket($id);
        if ($ticket->desk_ticket_id && ! $ticket->isClosed() && (! $ticket->desk_synced_at || $ticket->desk_synced_at->lt(now()->subMinutes(5)))) {
            DeskClient::refresh($ticket);
            $ticket->refresh();
        }
        if ($ticket->unread_for_customer) {
            $ticket->forceFill(['unread_for_customer' => false])->saveQuietly();
        }
        $ticket->load('messages');

        return view('customer.support.show', ['customer' => CustomerPortal::current(), 'ticket' => $ticket]);
    }

    public function reply(Request $request, int $id)
    {
        $ticket = $this->ownedTicket($id);
        if ($ticket->isClosed()) {
            return back()->with('status', ['alert_type' => 'error', 'message' => 'This ticket is closed. Please create a new ticket.']);
        }

        $data = $request->validate([
            'message' => 'required|string|max:5000',
            'attachment' => SupportTicketService::ATTACHMENT_RULE,
        ]);
        $customer = CustomerPortal::current();

        DB::transaction(function () use ($ticket, $customer, $data, $request) {
            SupportTicketService::addMessage($ticket, 'customer', null, $customer['name'], $data['message'], $request->file('attachment'));
            $ticket->last_reply_by = 'customer';
            $ticket->last_reply_at = now();
            $ticket->unread_for_staff = true;
            if (in_array($ticket->status, ['waiting_customer', 'resolved'], true)) {
                SupportTicketService::applyStatus($ticket, 'open');
            }
            $ticket->save();
        });

        return redirect()->route('customer.support.show', $ticket->id)
            ->with('status', ['alert_type' => 'success', 'message' => 'Reply sent.']);
    }

    public function close(int $id)
    {
        $ticket = $this->ownedTicket($id);
        if (! $ticket->isClosed()) {
            SupportTicketService::applyStatus($ticket, 'closed');
            $ticket->save();
        }

        return redirect()->route('customer.support.show', $ticket->id)
            ->with('status', ['alert_type' => 'success', 'message' => 'Ticket closed.']);
    }

    public function attachment(int $messageId)
    {
        $message = SupportTicketMessage::findOrFail($messageId);
        $this->ownedTicket((int) $message->support_ticket_id);

        return SupportTicketService::download($message);
    }

    private function ownedTicket(int $id): SupportTicket
    {
        $customer = CustomerPortal::current();

        return SupportTicket::query()
            ->where('id', $id)
            ->where('customer_contact_no', $customer['contact_no'])
            ->firstOrFail();
    }
}
