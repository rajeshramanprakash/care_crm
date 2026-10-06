@extends('admin.layouts.app')
@section('title', 'Speak Up Access Log')

@section('main')
<div class="content-wrapper">
    <section class="content">
        <div class="container-fluid pt-3">
            @include('speak_up.review._nav')
            <div class="card" style="border:0;border-radius:12px;box-shadow:0 2px 12px rgba(0,0,0,.06);">
                <div class="card-body">
                    <p class="small text-muted">Every opening of the secret URL, password attempt and action is recorded here. Entries cannot be edited or deleted.</p>
                    <form method="GET" class="row g-2 mb-3">
                        <div class="col-md-4 mb-2">
                            <select name="event" class="form-control form-select form-select-sm">
                                <option value="">All events</option>
                                @foreach (\App\Models\SpeakUpAccessLog::EVENTS as $key => $label)
                                    <option value="{{ $key }}" @selected(request('event') === $key)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3 mb-2"><input type="date" name="from" value="{{ request('from') }}" class="form-control form-control-sm"></div>
                        <div class="col-md-3 mb-2"><input type="date" name="to" value="{{ request('to') }}" class="form-control form-control-sm"></div>
                        <div class="col-md-2 mb-2"><button class="btn btn-sm btn-dark w-100 btn-block">Filter</button></div>
                    </form>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered align-middle" style="font-size:13px;">
                            <thead class="table-light"><tr><th>Date &amp; time</th><th>Event</th><th>User</th><th>IP</th><th>Browser / OS / Device</th><th>Details</th></tr></thead>
                            <tbody>
                            @forelse ($logs as $log)
                                <tr class="{{ $log->success ? '' : 'table-danger' }}">
                                    <td class="text-nowrap">{{ $log->created_at->format('d M Y') }}<br><b>{{ $log->created_at->format('h:i:s A') }}</b></td>
                                    <td>{{ $log->event_label }}</td>
                                    <td>{{ $log->user_name ?: 'Guest / not logged in' }}@if($log->user_id)<br><small class="text-muted">#{{ $log->user_id }}</small>@endif</td>
                                    <td class="text-nowrap">{{ $log->ip_address }}@if($log->forwarded_for)<br><small class="text-muted">XFF: {{ $log->forwarded_for }}</small>@endif</td>
                                    <td>{{ $log->browser }} · {{ $log->platform }} · {{ $log->device }}</td>
                                    <td style="min-width:240px;">
                                        @if ($log->details)<div>{{ $log->details }}</div>@endif
                                        <details>
                                            <summary class="small text-primary" style="cursor:pointer">More</summary>
                                            <div class="small text-break">
                                                <div><b>Method / URL:</b> {{ $log->method }} {{ $log->url }}</div>
                                                @if($log->referrer)<div><b>Referrer:</b> {{ $log->referrer }}</div>@endif
                                                @if($log->accept_language)<div><b>Language:</b> {{ $log->accept_language }}</div>@endif
                                                @if($log->session_hash)<div><b>Session:</b> {{ $log->session_hash }}</div>@endif
                                                <div><b>User agent:</b> {{ $log->user_agent }}</div>
                                                @foreach ((array) $log->client_info as $key => $value)
                                                    <div><b>{{ ucfirst(str_replace('_', ' ', $key)) }}:</b> {{ $value }}</div>
                                                @endforeach
                                            </div>
                                        </details>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-center text-muted py-4">No entries.</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                    {{ $logs->links('pagination::bootstrap-5') }}
                </div>
            </div>
        </div>
    </section>
</div>
@endsection
