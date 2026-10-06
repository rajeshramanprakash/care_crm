@extends('admin.layouts.app')
@section('title', 'Customer Feedback')

@section('main')
<style>
    .cf-stat { background:#fff; border-radius:12px; box-shadow:0 2px 12px rgba(0,0,0,.06); padding:12px 14px; height:100%; }
    .cf-stat .v { font-size:22px; font-weight:700; }
    .cf-stars { color:#f5a623; letter-spacing:1px; }
    .cf-stars .off { color:#d8d8d8; }
</style>
<div class="content-wrapper">
    <section class="content">
        <div class="container-fluid pt-3">
            <h4 class="fw-bold mb-3"><i class="fas fa-star"></i> Customer Feedback @if($newCount)<span class="badge bg-danger">{{ $newCount }} new</span>@endif</h4>

            <div class="row g-2 mb-3">
                <div class="col-6 col-md-2"><div class="cf-stat"><div class="small text-muted">Total</div><div class="v">{{ (int) $averages->total }}</div></div></div>
                @foreach (\App\Models\CustomerFeedback::RATING_FIELDS as $field => $label)
                    <div class="col-6 col-md-2"><div class="cf-stat"><div class="small text-muted">{{ $label }}</div><div class="v">{{ $averages->$field ? number_format($averages->$field, 1) : '—' }} <small class="cf-stars">&#9733;</small></div></div></div>
                @endforeach
            </div>

            <div class="card" style="border:0;border-radius:12px;box-shadow:0 2px 12px rgba(0,0,0,.06);">
                <div class="card-body">
                    <div class="d-flex flex-wrap mb-3" style="gap:6px;">
                        @foreach (['all' => 'All', 'complaints' => 'Complaints', 'suggestions' => 'Suggestions', 'low' => 'Low rating (1-2★)'] as $key => $label)
                            <a href="{{ route($rp . '.customer_feedback.index', ['tab' => $key]) }}" class="btn btn-sm {{ $tab === $key ? 'btn-primary' : 'btn-outline-primary' }}">{{ $label }}</a>
                        @endforeach
                    </div>
                    <form method="GET" class="row g-2 mb-3">
                        <input type="hidden" name="tab" value="{{ $tab }}">
                        <div class="col-md-2"><select name="status" class="form-control form-select form-select-sm"><option value="">All status</option>@foreach (\App\Models\CustomerFeedback::STATUSES as $k => $l)<option value="{{ $k }}" @selected(request('status') === $k)>{{ $l }}</option>@endforeach</select></div>
                        <div class="col-md-2"><input type="date" name="from" value="{{ request('from') }}" class="form-control form-control-sm"></div>
                        <div class="col-md-2"><input type="date" name="to" value="{{ request('to') }}" class="form-control form-control-sm"></div>
                        <div class="col-md-4"><input type="text" name="q" value="{{ request('q') }}" class="form-control form-control-sm" placeholder="Customer name or mobile"></div>
                        <div class="col-md-2"><button class="btn btn-sm btn-dark w-100">Filter</button></div>
                    </form>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle">
                            <thead><tr><th>Date</th><th>Customer</th><th>Service</th><th>Staff</th><th>Operation / Manager</th><th>Rating</th><th>Suggestion / Complaint</th><th>Status</th><th></th></tr></thead>
                            <tbody>
                            @forelse ($feedbacks as $feedback)
                                <tr>
                                    <td class="text-nowrap">{{ $feedback->created_at->format('d M Y, h:i A') }}</td>
                                    <td>{{ $feedback->customer_name }}<br><small class="text-muted">{{ $feedback->customer_contact_no }}</small></td>
                                    <td>{{ $feedback->operation_lead_id ? '#' . $feedback->operation_lead_id : 'General' }}</td>
                                    <td>{{ $feedback->staff_name ?: '—' }}</td>
                                    <td class="small">{{ $feedback->operationUser ? trim($feedback->operationUser->f_name . ' ' . $feedback->operationUser->l_name) : '—' }}<br><span class="text-muted">{{ $feedback->operationManager ? trim($feedback->operationManager->f_name . ' ' . $feedback->operationManager->l_name) : '' }}</span></td>
                                    <td class="cf-stars text-nowrap">@for ($i = 1; $i <= 5; $i++)<span class="{{ $i <= $feedback->overall_rating ? '' : 'off' }}">&#9733;</span>@endfor</td>
                                    <td style="max-width:320px;">
                                        @if ($feedback->complaint)<div class="text-danger small"><b>Complaint:</b> {{ \Illuminate\Support\Str::limit($feedback->complaint, 90) }}</div>@endif
                                        @if ($feedback->suggestions)<div class="small"><b>Suggestion:</b> {{ \Illuminate\Support\Str::limit($feedback->suggestions, 90) }}</div>@endif
                                    </td>
                                    <td><span class="badge bg-{{ $feedback->status === 'resolved' ? 'success' : ($feedback->status === 'reviewed' ? 'info' : 'secondary') }}">{{ \App\Models\CustomerFeedback::STATUSES[$feedback->status] ?? $feedback->status }}</span></td>
                                    <td><a href="{{ route($rp . '.customer_feedback.show', $feedback->id) }}" class="btn btn-sm btn-outline-dark">Open</a></td>
                                </tr>
                            @empty
                                <tr><td colspan="9" class="text-center text-muted py-4">No feedback yet.</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                    {{ $feedbacks->links('pagination::bootstrap-5') }}
                </div>
            </div>
        </div>
    </section>
</div>
@endsection
