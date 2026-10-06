@extends('customer.layouts.app')
@section('title', 'Support')

@section('content')
<div class="content-wrapper">
    <section class="content">
        <div class="container-fluid pt-3">
            <div class="card" style="border-radius:12px;border:0;box-shadow:0 2px 12px rgba(0,0,0,.06);">
                <div class="card-header d-flex align-items-center" style="background: linear-gradient(90deg, #ff8a00 0%, #ff7a18 100%); color:#fff; border-radius:12px 12px 0 0;">
                    <h3 class="card-title mb-0"><i class="fas fa-life-ring mr-2"></i>Help &amp; Support</h3>
                    <a href="{{ route('customer.support.create') }}" class="btn btn-light btn-sm ml-auto"><i class="fas fa-plus mr-1"></i> New Ticket</a>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <a href="{{ route('customer.support.index') }}" class="btn btn-sm {{ !$status ? 'btn-dark' : 'btn-outline-dark' }}">All</a>
                        <a href="{{ route('customer.support.index', ['status' => 'active']) }}" class="btn btn-sm {{ $status === 'active' ? 'btn-dark' : 'btn-outline-dark' }}">Open</a>
                        <a href="{{ route('customer.support.index', ['status' => 'done']) }}" class="btn btn-sm {{ $status === 'done' ? 'btn-dark' : 'btn-outline-dark' }}">Resolved / Closed</a>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover table-sm">
                            <thead><tr><th>Ticket</th><th>Category</th><th>Subject</th><th>Status</th><th>Last update</th><th></th></tr></thead>
                            <tbody>
                            @forelse ($tickets as $ticket)
                                <tr>
                                    <td class="text-nowrap">
                                        {{ $ticket->ticket_no }}
                                        @if ($ticket->unread_for_customer)<span class="badge badge-danger ml-1">New reply</span>@endif
                                    </td>
                                    <td>{{ $ticket->category_label }}</td>
                                    <td>{{ \Illuminate\Support\Str::limit($ticket->subject, 60) }}</td>
                                    <td>@include('customer.support._status_badge', ['ticket' => $ticket])</td>
                                    <td class="text-nowrap">{{ ($ticket->last_reply_at ?? $ticket->updated_at)->format('d M Y, h:i A') }}</td>
                                    <td><a href="{{ route('customer.support.show', $ticket->id) }}" class="btn btn-sm btn-outline-primary">Open</a></td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-center text-muted py-4">No tickets yet. Need help? Click "New Ticket".</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                    {{ $tickets->links('pagination::bootstrap-4') }}
                </div>
            </div>
        </div>
    </section>
</div>
@endsection
