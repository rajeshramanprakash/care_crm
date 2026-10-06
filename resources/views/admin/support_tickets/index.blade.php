@extends('admin.layouts.app')
@section('title', 'Support Tickets')

@section('main')
<div class="content-wrapper">
    <section class="content">
        <div class="container-fluid pt-3">
            <h4 class="fw-bold mb-3"><i class="fas fa-life-ring"></i> Support Tickets @if($unreadCount)<span class="badge bg-danger">{{ $unreadCount }} unread</span>@endif</h4>
            <div class="card" style="border:0;border-radius:12px;box-shadow:0 2px 12px rgba(0,0,0,.06);">
                <div class="card-body">
                    <div class="d-flex flex-wrap mb-3" style="gap:6px;">
                        <a href="{{ route($rp . '.support_tickets.index') }}" class="btn btn-sm {{ !$status ? 'btn-primary' : 'btn-outline-primary' }}">All ({{ $counts->sum() }})</a>
                        <a href="{{ route($rp . '.support_tickets.index', ['status' => 'unread']) }}" class="btn btn-sm {{ $status === 'unread' ? 'btn-danger' : 'btn-outline-danger' }}">Unread ({{ $unreadCount }})</a>
                        @foreach (\App\Models\SupportTicket::STATUSES as $key => $label)
                            <a href="{{ route($rp . '.support_tickets.index', ['status' => $key]) }}" class="btn btn-sm {{ $status === $key ? 'btn-primary' : 'btn-outline-primary' }}">{{ $label }} ({{ $counts[$key] ?? 0 }})</a>
                        @endforeach
                    </div>
                    <form method="GET" class="row g-2 mb-3">
                        <input type="hidden" name="status" value="{{ $status }}">
                        <div class="col-md-2"><select name="type" class="form-control form-select form-select-sm"><option value="">All types</option>@foreach (\App\Models\SupportTicket::TYPES as $k => $l)<option value="{{ $k }}" @selected(request('type') === $k)>{{ $l }}</option>@endforeach</select></div>
                        <div class="col-md-2"><select name="category" class="form-control form-select form-select-sm"><option value="">All categories</option>@foreach (\App\Models\SupportTicket::CATEGORIES as $k => $l)<option value="{{ $k }}" @selected(request('category') === $k)>{{ $l }}</option>@endforeach</select></div>
                        <div class="col-md-6"><input type="text" name="q" value="{{ request('q') }}" class="form-control form-control-sm" placeholder="Ticket no, subject, customer name or mobile"></div>
                        <div class="col-md-2"><button class="btn btn-sm btn-dark w-100">Filter</button></div>
                    </form>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle">
                            <thead><tr><th>Ticket</th><th>Customer</th><th>Category</th><th>Subject</th><th>Priority</th><th>Assigned</th><th>Status</th><th>Last update</th><th></th></tr></thead>
                            <tbody>
                            @forelse ($tickets as $ticket)
                                <tr class="{{ $ticket->unread_for_staff ? 'fw-bold' : '' }}">
                                    <td class="text-nowrap">{{ $ticket->ticket_no }} @if($ticket->unread_for_staff)<span class="badge bg-danger">New</span>@endif</td>
                                    <td>{{ $ticket->customer_name }}<br><small class="text-muted">{{ $ticket->customer_contact_no }}</small></td>
                                    <td>{{ $ticket->category_label }}
                                        @if ($ticket->isBug())<br>@include('admin.support_tickets._desk_badge', ['ticket' => $ticket])@endif
                                    </td>
                                    <td>{{ \Illuminate\Support\Str::limit($ticket->subject, 50) }}</td>
                                    <td>{{ $ticket->priority_label }}</td>
                                    <td>{{ $ticket->assignee ? trim($ticket->assignee->f_name . ' ' . $ticket->assignee->l_name) : '—' }}</td>
                                    <td>@include('customer.support._status_badge', ['ticket' => $ticket])</td>
                                    <td class="text-nowrap">{{ ($ticket->last_reply_at ?? $ticket->updated_at)->format('d M Y, h:i A') }}<br><small class="text-muted">by {{ $ticket->last_reply_by ?? '—' }}</small></td>
                                    <td><a href="{{ route($rp . '.support_tickets.show', $ticket->id) }}" class="btn btn-sm btn-outline-dark">Open</a></td>
                                </tr>
                            @empty
                                <tr><td colspan="9" class="text-center text-muted py-4">No tickets.</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                    {{ $tickets->links('pagination::bootstrap-5') }}
                </div>
            </div>
        </div>
    </section>
</div>
@endsection
