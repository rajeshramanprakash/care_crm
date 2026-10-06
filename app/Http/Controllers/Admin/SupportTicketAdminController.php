<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use App\Models\SupportTicketMessage;
use App\Models\User;
use App\Support\DeskClient;
use App\Support\SupportTicketService;
use Illuminate\Http\Request;

/**
 * Admin > Feedback & Support Center > Support Tickets (also mounted under subadmin.* with permissions).
 */
class SupportTicketAdminController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status');
        $tickets = SupportTicket::query()
            ->with('assignee:id,f_name,l_name')
            ->when($status === 'unread', fn ($q) => $q->where('unread_for_staff', true))
            ->when(array_key_exists((string) $status, SupportTicket::STATUSES), fn ($q) => $q->where('status', $status))
            ->when($request->filled('category'), fn ($q) => $q->where('category', $request->query('category')))
            ->when(array_key_exists((string) $request->query('type'), SupportTicket::TYPES), fn ($q) => $q->where('type', $request->query('type')))
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = '%' . $request->query('q') . '%';
                $q->where(fn ($w) => $w->where('ticket_no', 'like', $term)->orWhere('subject', 'like', $term)
                    ->orWhere('customer_name', 'like', $term)->orWhere('customer_contact_no', 'like', $term));
            })
            ->orderByDesc('unread_for_staff')
            ->orderByDesc('updated_at')
            ->paginate(20)
            ->withQueryString();

        return view('admin.support_tickets.index', [
            'rp' => $this->prefix($request),
            'tickets' => $tickets,
            'status' => $status,
            'counts' => SupportTicket::query()->selectRaw('status, count(*) c')->groupBy('status')->pluck('c', 'status'),
            'unreadCount' => SupportTicket::where('unread_for_staff', true)->count(),
        ]);
    }

    public function show(Request $request, int $id)
    {
        $ticket = SupportTicket::with(['messages', 'assignee:id,f_name,l_name'])->findOrFail($id);
        if ($ticket->unread_for_staff) {
            $ticket->forceFill(['unread_for_staff' => false])->saveQuietly();
        }

        return view('admin.support_tickets.show', [
            'rp' => $this->prefix($request),
            'ticket' => $ticket,
            'assignableUsers' => $this->assignableUsers(),
        ]);
    }

    public function reply(Request $request, int $id)
    {
        $ticket = SupportTicket::findOrFail($id);
        $data = $request->validate([
            'message' => 'required|string|max:5000',
            'status' => 'nullable|in:' . implode(',', array_keys(SupportTicket::STATUSES)),
            'attachment' => SupportTicketService::ATTACHMENT_RULE,
        ]);
        SupportTicketService::staffReply($ticket, $request->user(), $data['message'], $request->file('attachment'), $data['status'] ?? null);

        return redirect()->route($this->prefix($request) . '.support_tickets.show', $ticket->id)
            ->with('status', ['alert_type' => 'success', 'message' => 'Reply sent to customer.']);
    }

    public function update(Request $request, int $id)
    {
        $ticket = SupportTicket::findOrFail($id);
        $data = $request->validate([
            'status' => 'required|in:' . implode(',', array_keys(SupportTicket::STATUSES)),
            'priority' => 'required|in:' . implode(',', array_keys(SupportTicket::PRIORITIES)),
            'assigned_to' => 'nullable|integer|in:' . $this->assignableUsers()->pluck('id')->implode(','),
        ]);

        SupportTicketService::applyStatus($ticket, $data['status']);
        $ticket->priority = $data['priority'];
        $ticket->assigned_to = $data['assigned_to'] ?? null;
        $ticket->save();

        return redirect()->route($this->prefix($request) . '.support_tickets.show', $ticket->id)
            ->with('status', ['alert_type' => 'success', 'message' => 'Ticket updated.']);
    }

    /** Send a bug ticket to Ashniva Desk (if it never went) or pull its latest status and replies. */
    public function deskSync(Request $request, int $id)
    {
        $ticket = SupportTicket::findOrFail($id);
        abort_unless($ticket->isBug(), 404);

        if (! DeskClient::configured()) {
            $result = ['error', 'Desk is not connected. Add DESK_API_KEY to the server .env first.'];
        } elseif (! $ticket->desk_ticket_id) {
            $result = DeskClient::push($ticket)
                ? ['success', 'Ticket sent to Ashniva Desk.']
                : ['error', 'Could not send to Desk: ' . $ticket->fresh()->desk_error];
        } else {
            $result = DeskClient::refresh($ticket)
                ? ['success', 'Latest status and replies loaded from Desk.']
                : ['error', 'Could not reach Desk right now. Try again in a minute.'];
        }

        return redirect()->route($this->prefix($request) . '.support_tickets.show', $ticket->id)
            ->with('status', ['alert_type' => $result[0], 'message' => $result[1]]);
    }

    public function attachment(int $messageId)
    {
        return SupportTicketService::download(SupportTicketMessage::findOrFail($messageId));
    }

    private function assignableUsers()
    {
        return User::query()
            ->where(fn ($q) => $q->whereRaw('FIND_IN_SET(1, role_id)')->orWhereRaw('FIND_IN_SET(7, role_id)'))
            ->orderBy('f_name')
            ->get(['id', 'f_name', 'l_name']);
    }

    private function prefix(Request $request): string
    {
        return $request->routeIs('subadmin.*') ? 'subadmin' : 'admin';
    }
}
