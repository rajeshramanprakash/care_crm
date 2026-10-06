@extends('customer.layouts.app')
@section('title', 'Ticket ' . $ticket->ticket_no)

@section('content')
<style>
    .st-msg { max-width: 80%; border-radius: 10px; padding: 10px 12px; margin-bottom: 10px; word-break: break-word; }
    .st-text { white-space: pre-wrap; }
    .st-msg.mine { background: #e8f5e9; margin-left: auto; }
    .st-msg.staff { background: #f1f3f5; }
</style>
<div class="content-wrapper">
    <section class="content">
        <div class="container-fluid pt-3">
            <div class="row justify-content-center">
                <div class="col-lg-9">
                    <a href="{{ route('customer.support.index') }}" class="btn btn-link px-0 mb-2"><i class="fas fa-arrow-left mr-1"></i> All tickets</a>
                    <div class="card" style="border-radius:12px;border:0;box-shadow:0 2px 12px rgba(0,0,0,.06);">
                        <div class="card-header d-flex flex-wrap align-items-center" style="gap:8px;">
                            <div>
                                <h3 class="card-title mb-0 font-weight-bold">{{ $ticket->ticket_no }} — {{ $ticket->subject }}</h3>
                                <div class="small text-muted">{{ $ticket->category_label }} · Priority: {{ $ticket->priority_label }} · Created {{ $ticket->created_at->format('d M Y, h:i A') }}</div>
                                @if ($ticket->isBug())<div class="small text-muted"><i class="fas fa-tools mr-1"></i>Sent to our technical team{{ $ticket->desk_reference ? ' · Ref ' . $ticket->desk_reference : '' }}</div>@endif
                            </div>
                            <div class="ml-auto">@include('customer.support._status_badge', ['ticket' => $ticket])</div>
                        </div>
                        <div class="card-body">
                            @foreach ($ticket->messages as $message)
                                <div class="st-msg {{ $message->sender_type === 'customer' ? 'mine' : 'staff' }}">
                                    <div class="small font-weight-bold mb-1">
                                        {{ $message->sender_type === 'customer' ? 'You' : 'Carelix Support' }}
                                        <span class="text-muted font-weight-normal">· {{ $message->created_at->format('d M Y, h:i A') }}</span>
                                    </div>
                                    <div class="st-text">{{ $message->message }}</div>
                                    @if ($message->attachment_path)
                                        <div class="mt-1"><a href="{{ route('customer.support.attachment', $message->id) }}"><i class="fas fa-paperclip"></i> {{ $message->attachment_name }}</a></div>
                                    @endif
                                </div>
                            @endforeach

                            @if ($ticket->isClosed())
                                <div class="alert alert-secondary mb-0">This ticket is closed. <a href="{{ route('customer.support.create') }}">Create a new ticket</a> if you still need help.</div>
                            @else
                                @if ($errors->any())
                                    <div class="alert alert-danger py-2">@foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>
                                @endif
                                <form method="POST" action="{{ route('customer.support.reply', $ticket->id) }}" enctype="multipart/form-data" class="mt-3">
                                    @csrf
                                    <textarea name="message" class="form-control mb-2" rows="3" maxlength="5000" placeholder="Write a reply..." required></textarea>
                                    <div class="d-flex flex-wrap align-items-center" style="gap:8px;">
                                        <input type="file" name="attachment" class="form-control-file" style="max-width:280px" accept=".jpg,.jpeg,.png,.webp,.pdf,.doc,.docx,.txt">
                                        <button type="submit" class="btn btn-success ml-auto"><i class="fas fa-reply mr-1"></i> Send Reply</button>
                                    </div>
                                </form>
                                <form method="POST" action="{{ route('customer.support.close', $ticket->id) }}" class="mt-2 text-right" onsubmit="return confirm('Close this ticket?')">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-outline-secondary">Problem solved — close ticket</button>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
@endsection
