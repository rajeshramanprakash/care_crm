<div class="btn-group" role="group">
    <a href="{{ route('tpa.tickets.show', $ticket->id) }}" class="btn btn-sm btn-info" title="View Details">
        <i class="fas fa-eye"></i> View
    </a>

    @if($ticket->status === 'pending' && $ticket->is_active)
        <button type="button" class="btn btn-sm btn-success" title="Approve Submission"
                onclick="approveSubmission({{ $ticket->id }})">
            <i class="fas fa-check"></i> Approve
        </button>
    @endif

    @if($ticket->status === 'approved' && $ticket->is_active)
        <button type="button" class="btn btn-sm btn-success" title="Process Ticket"
                onclick="processTicket({{ $ticket->id }})">
            <i class="fas fa-check"></i> Process
        </button>
    @endif
</div>

<script>
function approveSubmission(ticketId) {
    var submissionTime = prompt('Please enter the submission time (YYYY-MM-DD HH:MM):');
    if (submissionTime) {
        $.post('{{ route("tpa.tickets.approve", ["id" => ":id"]) }}'.replace(':id', ticketId), {
            submission_time: submissionTime,
            _token: '{{ csrf_token() }}'
        }, function(response) {
            if (response.success) {
                alert('Submission approved successfully!');
                location.reload();
            } else {
                alert('Error: ' + response.message);
            }
        }).fail(function() {
            alert('An error occurred while approving the submission.');
        });
    }
}

function processTicket(ticketId) {
    if (confirm('Are you sure you want to process this ticket?')) {
        $.post('{{ route("tpa.tickets.close", ["id" => ":id"]) }}'.replace(':id', ticketId), {
            _token: '{{ csrf_token() }}'
        }, function(response) {
            if (response.success) {
                alert('Ticket processed successfully!');
                location.reload();
            } else {
                alert('Error: ' + response.message);
            }
        }).fail(function() {
            alert('An error occurred while processing the ticket.');
        });
    }
}
</script>
