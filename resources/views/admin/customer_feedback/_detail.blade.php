<h5 class="fw-bold mb-1">{{ $feedback->customer_name }} <small class="text-muted">{{ $feedback->customer_contact_no }}</small></h5>
<div class="small text-muted mb-3">
    {{ $feedback->created_at->format('d M Y, h:i A') }} ·
    {{ $feedback->operation_lead_id ? 'Service #' . $feedback->operation_lead_id . ($feedback->operationLead ? ' — ' . \Illuminate\Support\Str::limit($feedback->operationLead->query ?: '', 60) : '') : 'General feedback' }}
</div>
<table class="table table-sm w-auto">
    @foreach (\App\Models\CustomerFeedback::RATING_FIELDS as $field => $label)
        <tr><th class="pe-4">{{ $label }}</th><td style="color:#f5a623">@if ($feedback->$field)@for ($i = 1; $i <= 5; $i++)<span style="{{ $i <= $feedback->$field ? '' : 'color:#d8d8d8' }}">&#9733;</span>@endfor @else <span class="text-muted">—</span>@endif</td></tr>
    @endforeach
</table>
<h6 class="fw-bold">Suggestions</h6>
<p style="white-space:pre-wrap">{{ $feedback->suggestions ?: '—' }}</p>
<h6 class="fw-bold">Complaint</h6>
<p style="white-space:pre-wrap" class="{{ $feedback->complaint ? 'text-danger' : '' }}">{{ $feedback->complaint ?: '—' }}</p>
