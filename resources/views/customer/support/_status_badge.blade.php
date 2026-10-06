@php
    $supportBadge = [
        'open' => 'primary',
        'in_progress' => 'info',
        'waiting_customer' => 'warning',
        'resolved' => 'success',
        'closed' => 'secondary',
    ][$ticket->status] ?? 'secondary';
@endphp
<span class="badge badge-{{ $supportBadge }} bg-{{ $supportBadge }}">{{ $ticket->status_label }}</span>
