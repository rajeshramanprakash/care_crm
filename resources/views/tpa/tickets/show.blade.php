@extends('tpa.layouts.app')

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
                        <li class="breadcrumb-item"><a href="{{ route('tpa.dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('tpa.tickets.index') }}">Tickets</a></li>
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

                            <!-- TPA Actions -->
                            @if($ticket->status === 'pending')
                            <div class="mt-3">
                                <h6>Approve Submission</h6>
                                <form id="approveForm" onsubmit="approveSubmission(event)">
                                    @csrf
                                    <div class="form-group">
                                        <label for="submission_time">Select Submission Time:</label>
                                        <input type="datetime-local" class="form-control" id="submission_time" name="submission_time"
                                               min="{{ date('Y-m-d\TH:i') }}" required>
                                    </div>
                                    <button type="submit" class="btn btn-success btn-block">
                                        <i class="fas fa-check"></i> Approve Submission
                                    </button>
                                </form>
                            </div>
                            @endif

                            @if($ticket->status === 'submitted' && $ticket->is_active)
                            <form action="{{ route('tpa.tickets.close', $ticket->id) }}" method="POST" class="mt-3">
                                @csrf
                                <button type="submit" class="btn btn-success btn-block" onclick="return confirm('Are you sure you want to process this ticket?')">
                                    <i class="fas fa-check"></i> Process Ticket
                                </button>
                            </form>
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

<style>
.message-item {
    background-color: #f8f9fa;
    border-left: 4px solid #007bff !important;
}

.message-item:hover {
    background-color: #e9ecef;
}
</style>
<script>
    $(document).ready(function() {
        // Set minimum datetime to current time
        var now = new Date();
        var year = now.getFullYear();
        var month = String(now.getMonth() + 1).padStart(2, '0');
        var day = String(now.getDate()).padStart(2, '0');
        var hours = String(now.getHours()).padStart(2, '0');
        var minutes = String(now.getMinutes()).padStart(2, '0');
    
        var minDateTime = year + '-' + month + '-' + day + 'T' + hours + ':' + minutes;
        $('#submission_time').attr('min', minDateTime);
    });
    
    function approveSubmission(event) {
        event.preventDefault();
    
        var form = document.getElementById('approveForm');
        var formData = new FormData(form);
    
        $.ajax({
            url: '{{ route("tpa.tickets.approve", $ticket->id) }}',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    alert('Submission approved successfully!');
                    location.reload();
                } else {
                    alert('Error: ' + response.message);
                }
            },
            error: function(xhr, status, error) {
                alert('An error occurred while approving the submission.');
            }
        });
    }
    </script>
@endsection


