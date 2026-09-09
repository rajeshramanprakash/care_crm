<div class="btn-group" role="group">
    <a href="{{ route('vendor.tickets.show', $ticket->id) }}" class="btn btn-sm btn-info" title="View Details">
        <i class="fas fa-eye"></i> View
    </a>

    @if($ticket->status === 'approved' && $ticket->is_active)
        <button type="button" class="btn btn-sm btn-success" title="Submit Claim"
                onclick="submitClaim({{ $ticket->id }})">
            <i class="fas fa-check"></i> Submit
        </button>
    @endif
</div>

<script>
function submitClaim(ticketId) {
    if (confirm('Are you sure you want to submit this claim?')) {
        $.post('{{ route("vendor.tickets.submit", ["id" => ":id"]) }}'.replace(':id', ticketId), {
            _token: '{{ csrf_token() }}'
        }, function(response) {
            if (response.success) {
                alert('Claim submitted successfully!');
                location.reload();
            } else {
                alert('Error: ' + response.message);
            }
        }).fail(function() {
            alert('An error occurred while submitting the claim.');
        });
    }
}
</script>
