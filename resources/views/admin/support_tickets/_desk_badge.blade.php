@if ($ticket->desk_ticket_id)
    <span class="badge bg-success" title="Sent to Ashniva Desk {{ optional($ticket->desk_sent_at)->format('d M Y, h:i A') }}">Desk{{ $ticket->desk_status ? ': ' . str_replace('_', ' ', $ticket->desk_status) : '' }}</span>
@elseif ($ticket->desk_error)
    <span class="badge bg-danger" title="{{ $ticket->desk_error }}">Desk: not sent</span>
@else
    <span class="badge bg-secondary">Desk: pending</span>
@endif
