@extends('doctor.layouts.app')

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
                        <li class="breadcrumb-item"><a href="{{ route('doctor.dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('doctor.tickets.index') }}">Tickets</a></li>
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
                                        <span class="badge badge-{{ $ticket->is_active ? 'success' : 'danger' }}">
                                            {{ $ticket->status_text }}
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
                                    <td><strong>Vendor:</strong></td>
                                    <td>{{ $ticket->vendor->name ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <td><strong>TPA:</strong></td>
                                    <td>{{ $ticket->tpa->name ?? 'N/A' }}</td>
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

                           
                        </div>
                    </div>

                    <!-- Query Information -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Query Details</h3>
                        </div>
                        <div class="card-body">
                            <p><strong>Query:</strong></p>
                            <p>{{ $ticket->queryData->query ?? 'N/A' }}</p>

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
</style>
@endpush
