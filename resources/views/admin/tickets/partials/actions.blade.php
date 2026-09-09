<div class="btn-group" role="group">
    <a href="{{ route('admin.tickets.show', $ticket->id) }}" class="btn btn-sm btn-info" title="View Details">
        <i class="fas fa-eye"></i>
    </a>

    @if($ticket->is_active)
    <form action="{{ route('admin.tickets.close', $ticket->id) }}" method="POST" style="display: inline;">
        @csrf
        <button type="submit" class="btn btn-sm btn-danger" title="Close Ticket"
                onclick="return confirm('Are you sure you want to close this ticket?')">
            <i class="fas fa-times"></i>
        </button>
    </form>
    @endif
</div>
