@extends('admin.layouts.app')
@section('title', 'Ticket ' . $ticket->ticket_no)

@section('main')
<style>
    .st-msg { max-width: 85%; border-radius: 10px; padding: 10px 12px; margin-bottom: 10px; word-break: break-word; }
    .st-text { white-space: pre-wrap; }
    .st-msg.customer { background: #f1f3f5; }
    .st-msg.staff { background: #e3f2fd; margin-left: auto; }
    .st-msg.desk { background: #fff4e5; margin-left: auto; }
</style>
<div class="content-wrapper">
    <section class="content">
        <div class="container-fluid pt-3">
            <a href="{{ route($rp . '.support_tickets.index') }}" class="btn btn-link px-0 mb-2"><i class="fas fa-arrow-left"></i> All tickets</a>
            @if ($errors->any())
                <div class="alert alert-danger py-2">@foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>
            @endif
            <div class="row">
                <div class="col-lg-8 mb-3">
                    <div class="card" style="border:0;border-radius:12px;box-shadow:0 2px 12px rgba(0,0,0,.06);">
                        <div class="card-body">
                            <h5 class="fw-bold mb-1">{{ $ticket->ticket_no }} — {{ $ticket->subject }}</h5>
                            <div class="small text-muted mb-3">{{ $ticket->type_label }} · {{ $ticket->category_label }} · Priority: {{ $ticket->priority_label }} · Created {{ $ticket->created_at->format('d M Y, h:i A') }}</div>
                            @foreach ($ticket->messages as $message)
                                <div class="st-msg {{ $message->sender_type }}">
                                    <div class="small fw-bold mb-1">{{ $message->sender_name ?: ucfirst($message->sender_type) }} <span class="text-muted fw-normal">· {{ ['staff' => 'Staff', 'desk' => 'Desk reply', 'customer' => 'Customer'][$message->sender_type] ?? ucfirst($message->sender_type) }} · {{ $message->created_at->format('d M Y, h:i A') }}</span></div>
                                    <div class="st-text">{{ $message->message }}</div>
                                    @if ($message->attachment_path)
                                        <div class="mt-1"><a href="{{ route($rp . '.support_tickets.attachment', $message->id) }}"><i class="fas fa-paperclip"></i> {{ $message->attachment_name }}</a></div>
                                    @endif
                                </div>
                            @endforeach

                            @can('edit_support_tickets')
                                <form method="POST" action="{{ route($rp . '.support_tickets.reply', $ticket->id) }}" enctype="multipart/form-data" class="mt-3">
                                    @csrf
                                    <textarea name="message" class="form-control mb-2" rows="4" maxlength="5000" required placeholder="Reply to customer"></textarea>
                                    <div class="row g-2 align-items-center">
                                        <div class="col-md-5"><input type="file" name="attachment" class="form-control form-control-sm" accept=".jpg,.jpeg,.png,.webp,.pdf,.doc,.docx,.txt"></div>
                                        <div class="col-md-4">
                                            <select name="status" class="form-control form-select form-select-sm">
                                                <option value="">Status: keep / auto</option>
                                                @foreach (\App\Models\SupportTicket::STATUSES as $k => $l)<option value="{{ $k }}">Set: {{ $l }}</option>@endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-3"><button type="submit" class="btn btn-primary w-100"><i class="fas fa-reply"></i> Send</button></div>
                                    </div>
                                </form>
                            @endcan
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 mb-3">
                    <div class="card" style="border:0;border-radius:12px;box-shadow:0 2px 12px rgba(0,0,0,.06);">
                        <div class="card-body">
                            <h6 class="fw-bold">Customer</h6>
                            <p class="mb-3">{{ $ticket->customer_name }}<br><small class="text-muted">{{ $ticket->customer_contact_no }} · {{ str_replace('_', ' ', (string) $ticket->customer_type) }}</small></p>
                            <h6 class="fw-bold">Ticket</h6>
                            @can('edit_support_tickets')
                                <form method="POST" action="{{ route($rp . '.support_tickets.update', $ticket->id) }}">
                                    @csrf
                                    <label class="small">Status</label>
                                    <select name="status" class="form-control form-select form-select-sm mb-2">
                                        @foreach (\App\Models\SupportTicket::STATUSES as $k => $l)<option value="{{ $k }}" @selected($ticket->status === $k)>{{ $l }}</option>@endforeach
                                    </select>
                                    <label class="small">Priority</label>
                                    <select name="priority" class="form-control form-select form-select-sm mb-2">
                                        @foreach (\App\Models\SupportTicket::PRIORITIES as $p => $label)<option value="{{ $p }}" @selected($ticket->priority === $p)>{{ $label }}</option>@endforeach
                                    </select>
                                    <label class="small">Assigned to</label>
                                    <select name="assigned_to" class="form-control form-select form-select-sm mb-2">
                                        <option value="">— Not assigned —</option>
                                        @foreach ($assignableUsers as $u)<option value="{{ $u->id }}" @selected((int) $ticket->assigned_to === (int) $u->id)>{{ trim($u->f_name . ' ' . $u->l_name) }}</option>@endforeach
                                    </select>
                                    <button type="submit" class="btn btn-dark w-100 btn-sm">Update ticket</button>
                                </form>
                            @else
                                <p class="mb-1">@include('customer.support._status_badge', ['ticket' => $ticket])</p>
                                <p class="small mb-1">Priority: {{ $ticket->priority_label }}</p>
                                <p class="small">Assigned: {{ $ticket->assignee ? trim($ticket->assignee->f_name . ' ' . $ticket->assignee->l_name) : '—' }}</p>
                            @endcan

                            @if ($ticket->isBug())
                                <hr>
                                <h6 class="fw-bold"><i class="fas fa-tools"></i> Ashniva Desk</h6>
                                <p class="mb-1">@include('admin.support_tickets._desk_badge', ['ticket' => $ticket])</p>
                                @if ($ticket->desk_ticket_id)
                                    <p class="small mb-1">Desk ticket: <b>{{ $ticket->desk_reference ?: $ticket->desk_ticket_id }}</b></p>
                                    <p class="small mb-1">Sent: {{ optional($ticket->desk_sent_at)->format('d M Y, h:i A') }}</p>
                                    <p class="small mb-2">Last checked: {{ optional($ticket->desk_synced_at)->format('d M Y, h:i A') ?: '—' }}</p>
                                @elseif ($ticket->desk_error)
                                    <p class="small text-danger mb-2">{{ $ticket->desk_error }}</p>
                                @endif
                                @can('edit_support_tickets')
                                    <form method="POST" action="{{ route($rp . '.support_tickets.desk_sync', $ticket->id) }}">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-dark w-100">
                                            <i class="fas fa-sync-alt"></i> {{ $ticket->desk_ticket_id ? 'Refresh from Desk' : 'Send to Desk' }}
                                        </button>
                                    </form>
                                @endcan
                                <a href="https://desk.ashniva.com/" target="_blank" rel="noopener" class="btn btn-sm btn-link px-0">Open desk.ashniva.com</a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
@endsection
