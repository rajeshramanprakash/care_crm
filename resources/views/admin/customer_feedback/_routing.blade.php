@php $name = fn ($u) => $u ? trim($u->f_name . ' ' . $u->l_name) : '—'; @endphp
<h6 class="fw-bold">Service details</h6>
<p class="small mb-1"><b>Staff:</b> {{ $feedback->staff_name ?: '—' }}
    @if ($feedback->freelancer_id)<span class="badge bg-info">Freelancer</span>@elseif ($feedback->vendor_id)<span class="badge bg-secondary">Vendor</span>@endif
</p>
<p class="small mb-1"><b>Operation (assigned):</b> {{ $name($feedback->operationUser) }}
    @if ($feedback->operation_seen_at)<span class="text-success">· seen {{ $feedback->operation_seen_at->format('d M, h:i A') }}</span>@else <span class="text-muted">· not seen</span>@endif
</p>
<p class="small mb-0"><b>Operation Manager:</b> {{ $name($feedback->operationManager) }}
    @if ($feedback->manager_seen_at)<span class="text-success">· seen {{ $feedback->manager_seen_at->format('d M, h:i A') }}</span>@else <span class="text-muted">· not seen</span>@endif
</p>
