@extends('vendor.layouts.app')

@section('title', 'Ticket Details')

@section('main')
<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Ticket #{{ $ticket->ticket_number }}</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('vendor.dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('vendor.tickets.index') }}">Tickets</a></li>
                        <li class="breadcrumb-item active">Details</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <!-- Ticket Information -->
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Ticket Information</h3>
                        </div>
                        <div class="card-body">
                            <table class="table table-borderless">
                                <tr>
                                    <td><strong>Ticket #:</strong></td>
                                    <td>{{ $ticket->ticket_number }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Status:</strong></td>
                                    <td>
                                        <span class="badge badge-{{ $ticket->status === 'pending' ? 'warning' : ($ticket->status === 'approved' ? 'success' : ($ticket->status === 'submitted' ? 'info' : 'danger')) }}">
                                            {{ ucfirst($ticket->status) }}
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Case Code:</strong></td>
                                    <td>{{ $ticket->case->case_code ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Claim No:</strong></td>
                                    <td>{{ $ticket->case->claim_no ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Patient Name:</strong></td>
                                    <td>{{ $ticket->case->patient_name ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Case Type:</strong></td>
                                    <td>{{ $ticket->case_type_text }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Created:</strong></td>
                                    <td>{{ $ticket->created_at->format('d/m/Y H:i') }}</td>
                                </tr>
                                @if($ticket->submission_time)
                                <tr>
                                    <td><strong>Submission Time:</strong></td>
                                    <td>{{ $ticket->submission_time->format('d/m/Y H:i') }}</td>
                                </tr>
                                @endif
                            </table>

                            <!-- Vendor Actions -->
                            @if($ticket->status === 'approved')
                            <div class="alert alert-success">
                                <i class="fas fa-info-circle"></i>
                                <strong>TPA has approved submission!</strong><br>
                                Please submit the claim before the scheduled time.
                            </div>
                            <form id="submitForm" onsubmit="submitClaim(event)" class="mt-3">
                                @csrf
                                <button type="submit" class="btn btn-success btn-block">
                                    <i class="fas fa-paper-plane"></i> Submit Claim
                                </button>
                            </form>
                            @elseif($ticket->status === 'pending')
                            <div class="alert alert-warning">
                                <i class="fas fa-clock"></i>
                                <strong>Waiting for TPA approval</strong><br>
                                You will be notified when TPA approves the submission.
                            </div>
                            @elseif($ticket->status === 'submitted')
                            <div class="alert alert-info">
                                <i class="fas fa-check-circle"></i>
                                <strong>Claim submitted successfully!</strong><br>
                                TPA has been notified and will process further.
                            </div>
                            @endif
                        </div>
                    </div>

                    <!-- Query Information -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Query Details</h3>
                        </div>
                        <div class="card-body">
                            @if($ticket->queryData->query_pdf)
                            <p><strong>Query PDF:</strong></p>
                            <a href="{{ asset('storage/' . $ticket->queryData->query_pdf) }}" target="_blank" class="btn btn-sm btn-primary">
                                <i class="fas fa-file-pdf"></i> View PDF
                            </a>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Messages -->
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Ticket Messages</h3>
                        </div>
                        <div class="card-body" style="max-height: 600px; overflow-y: auto;">
                            @forelse($ticket->messages as $message)
                            <div class="message-item mb-3 p-3 border rounded">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <strong>{{ $message->sender ? $message->sender->name : 'System' }}</strong>
                                        <small class="text-muted ml-2">{{ $message->created_at->format('d/m/Y H:i') }}</small>
                                        <span class="badge badge-info ml-2">{{ $message->message_type_text }}</span>
                                    </div>
                                    @if($message->is_read)
                                    <span class="badge badge-success">Read</span>
                                    @else
                                    <span class="badge badge-warning">Unread</span>
                                    @endif
                                </div>
                                <div class="mt-2">
                                    {!! nl2br(e($message->message)) !!}
                                </div>
                            </div>
                            @empty
                            <div class="text-center text-muted">
                                <p>No messages found for this ticket.</p>
                            </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
@endsection

@push('styles')
<style>
.message-item {
    background-color: #f8f9fa;
    border-left: 4px solid #007bff !important;
}

.message-item:hover {
    background-color: #e9ecef;
}

.alert {
    border-radius: 8px;
    border: none;
}

.alert-success {
    background-color: #d4edda;
    color: #155724;
}

.alert-warning {
    background-color: #fff3cd;
    color: #856404;
}

.alert-info {
    background-color: #d1ecf1;
    color: #0c5460;
}
</style>
@endpush

@push('scripts')
<script>
function submitClaim(event) {
    event.preventDefault();

    if (confirm('Are you sure you want to submit this claim?')) {
        var form = document.getElementById('submitForm');
        var formData = new FormData(form);

        $.ajax({
            url: '{{ route("vendor.tickets.submit", $ticket->id) }}',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    alert('Claim submitted successfully!');
                    location.reload();
                } else {
                    alert('Error: ' + response.message);
                }
            },
            error: function(xhr, status, error) {
                alert('An error occurred while submitting the claim.');
            }
        });
    }
}
</script>
@endpush
