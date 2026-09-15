@php
    use App\Models\FreelancerServicePriceChangeRequest;

    $fmtPrice = fn ($v) => ($v !== null && $v !== '') ? '₹'.number_format((float) $v, 0) : '—';
    $requestStatusLabel = fn ($s) => match ($s) {
        FreelancerServicePriceChangeRequest::STATUS_PENDING => 'Pending admin review',
        FreelancerServicePriceChangeRequest::STATUS_APPROVED => 'Approved by admin',
        FreelancerServicePriceChangeRequest::STATUS_REJECTED => 'Rejected by admin',
        default => ucfirst((string) $s),
    };
    $typeLabels = [
        FreelancerServicePriceChangeRequest::TYPE_12HR => '12 Hours',
        FreelancerServicePriceChangeRequest::TYPE_24HR => '24 Hours',
        FreelancerServicePriceChangeRequest::TYPE_ONETIME => 'One-time',
    ];
    $serviceId = $serviceRow ? (int) $serviceRow->id : 0;

    $activeTempRules = \App\Models\BulkPricingRule::where('status', 1)
        ->where('time_period', 'temporary')
        ->where('pricing_type', 'freelancer')
        ->get();
        
    $tier1Cities = \App\Models\Location::where('tier', 'Tier 1')->pluck('name')->toArray();
    $locationName = $freelancer->location ?? '';

    $getMatchingTempRule = static function($serviceId, $subId, $modeKey) use ($activeTempRules, $locationName, $tier1Cities) {
        foreach ($activeTempRules as $rule) {
            if ($rule->service_id && (int)$rule->service_id !== (int)$serviceId) continue;
            if ($rule->sub_service_id && (int)$rule->sub_service_id !== (int)$subId) continue;
            
            $mappedMode = match ($modeKey) {
                '12hr' => '12_hours',
                '24hr' => '24_hours',
                'onetime' => 'one_time',
                default => $modeKey
            };
            if (!empty($rule->mode_type)) {
                if ($rule->mode_type === 'both' && in_array($mappedMode, ['12_hours', '24_hours'])) {
                    // Matches both 12 and 24 hours
                } else if ($rule->mode_type !== $mappedMode) {
                    continue;
                }
            }
            
            if ($rule->city_filter && $rule->city_filter !== 'all') {
                if ($rule->city_filter === 'tier_1') {
                    if (!in_array($locationName, $tier1Cities)) continue;
                } else {
                    if (strtolower($locationName) !== strtolower($rule->city_filter)) continue;
                }
            }
            
            if ($rule->time_period_start_date) {
                return $rule;
            }
        }
        return null;
    };
@endphp

@if(!$freelancer->job_title || !$serviceRow || count($pricingItems) === 0)
    <div class="fl-pricing-empty">
        <i class="fas fa-info-circle text-muted mr-2"></i>
        <span class="text-muted">Services aur pricing tab dikhegi jab aapki profile par job title aur service set ho.</span>
    </div>
@else
    @foreach($pricingItems as $item)
        @php
            $subId = (int) $item['service_sub_service_id'];
            $tags = $item['tags'] ?? [];
        @endphp
        <div class="fl-pricing-block mb-3">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
                <strong class="fl-pricing-title">{{ $item['label'] }}</strong>
                @if($subId === 0)
                    <span class="fl-price-badge fl-price-badge--service">Service</span>
                @else
                    <span class="fl-price-badge fl-price-badge--sub">Sub-service</span>
                @endif
            </div>
            @if(count($tags))
                <div class="mb-2">
                    @foreach($tags as $tag)
                        <span class="badge badge-light border mr-1">{{ $tag }}</span>
                    @endforeach
                </div>
            @endif

            <div class="table-responsive">
                <table class="table table-sm table-bordered mb-0 fl-pricing-table">
                    <thead>
                        <tr>
                            <th>Price type</th>
                            <th>Current price</th>
                            <th>Temporary price</th>
                            <th>Request new price</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($priceTypes as $typeKey)
                            @php
                                $current = $pricingService->currentPrice($freelancer, $serviceId, $subId, $typeKey);
                                $reqKey = $subId.'|'.$typeKey;
                                $latestReq = $requestsByKey[$reqKey] ?? null;
                                $isPending = $latestReq && $latestReq->status === FreelancerServicePriceChangeRequest::STATUS_PENDING;
                                $tempRule = $getMatchingTempRule($serviceId, $subId, $typeKey);
                                
                                if ($tempRule) {
                                    $now = \Carbon\Carbon::now();
                                    $start = \Carbon\Carbon::parse($tempRule->time_period_start_date);
                                    $end = $tempRule->time_period_end_date ? \Carbon\Carbon::parse($tempRule->time_period_end_date) : null;
                                    $isActive = $now->gte($start) && (!$end || $now->lte($end));

                                    $priceRecord = \App\Models\JobRequestServicePrice::where('job_request_id', $freelancer->id)
                                        ->where('service_id', $serviceId)
                                        ->where('service_sub_service_id', $subId)
                                        ->first();
                                    if ($priceRecord) {
                                        $col = null;
                                        if (str_contains($typeKey, '12hr')) $col = 'original_price_12hr';
                                        elseif (str_contains($typeKey, '24hr')) $col = 'original_price_24hr';
                                        elseif (str_contains($typeKey, 'onetime')) $col = 'original_price_onetime';
                                        
                                        if ($col && isset($priceRecord->{$col}) && $priceRecord->{$col} !== null) {
                                            $current = $priceRecord->{$col};
                                        }
                                    }
                                }
                            @endphp
                            <tr>
                                <td>{{ $typeLabels[$typeKey] ?? $typeKey }}</td>
                                <td><strong>{{ $fmtPrice($current) }}</strong></td>
                                <td>
                                    @if($tempRule)
                                        <div class="fl-temp-badge {{ $isActive ? 'fl-temp-badge--active' : 'fl-temp-badge--future' }}">
                                            <strong>₹{{ number_format($tempRule->value, 2) }}</strong>
                                                    <small class="d-block text-muted">
                                                        {{ \Carbon\Carbon::parse($tempRule->time_period_start_date)->format('M d, h:i A') }} - 
                                                        {{ $tempRule->time_period_end_date ? \Carbon\Carbon::parse($tempRule->time_period_end_date)->format('M d, h:i A') : 'Ongoing' }}
                                                    </small>
                                        </div>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td class="fl-request-cell">
                                    @if($isPending)
                                        <div class="fl-req-pending">
                                            <span class="fl-req-badge fl-req-badge--pending">Pending admin review</span>
                                            <span class="small text-muted d-block">Requested: {{ $fmtPrice($latestReq->requested_price) }}</span>
                                            <span class="small text-info d-block mt-1"><i class="fas fa-clock"></i> Admin response ka wait karein.</span>
                                        </div>
                                    @else
                                        <div class="fl-req-form d-flex flex-wrap align-items-center gap-2"
                                             data-sub-id="{{ $subId }}"
                                             data-price-type="{{ $typeKey }}">
                                            <input type="number" class="form-control form-control-sm fl-req-input"
                                                   min="1" step="0.01" placeholder="New price (₹)">
                                            <button type="button" class="btn btn-sm btn-warning fl-req-submit">
                                                Request to admin
                                            </button>
                                        </div>
                                    @endif
                                    @if($latestReq && ! $isPending)
                                        <div class="fl-req-outcome mt-1">
                                            <span class="fl-req-badge fl-req-badge--{{ $latestReq->status }}">
                                                {{ $requestStatusLabel($latestReq->status) }}
                                            </span>
                                            <span class="small d-block text-muted">
                                                {{ $fmtPrice($latestReq->current_price) }} → {{ $fmtPrice($latestReq->requested_price) }}
                                                @if($latestReq->reviewed_at)
                                                    · {{ $latestReq->reviewed_at->format('d M Y') }}
                                                @endif
                                            </span>
                                            @if($latestReq->status === FreelancerServicePriceChangeRequest::STATUS_REJECTED && $latestReq->admin_note)
                                                <span class="small d-block text-danger mt-1"><strong>Admin note:</strong> {{ $latestReq->admin_note }}</span>
                                            @endif
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endforeach

    @php $recentRequests = $freelancer->priceChangeRequests->take(10); @endphp
    @if($recentRequests->count())
        <div class="fl-req-history mt-3 pt-3 border-top">
            <div class="small font-weight-bold text-muted mb-2">Recent price change requests</div>
            <div class="table-responsive">
                <table class="table table-sm table-bordered mb-0">
                    <thead>
                        <tr>
                            <th>When</th>
                            <th>Service / Sub-service</th>
                            <th>Type</th>
                            <th>Was</th>
                            <th>Requested</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($recentRequests as $hist)
                            <tr>
                                <td class="text-nowrap">{{ $hist->created_at?->format('d M Y') ?? '—' }}</td>
                                <td>{{ $hist->sub_service_name ?: ($hist->service_name ?: 'Service') }}</td>
                                <td>{{ $hist->priceTypeLabel() }}</td>
                                <td>{{ $fmtPrice($hist->current_price) }}</td>
                                <td>{{ $fmtPrice($hist->requested_price) }}</td>
                                <td>
                                    <span class="fl-req-badge fl-req-badge--{{ $hist->status }}">{{ $requestStatusLabel($hist->status) }}</span>
                                    @if($hist->status === FreelancerServicePriceChangeRequest::STATUS_REJECTED && $hist->admin_note)
                                        <div class="small text-danger mt-1">{{ $hist->admin_note }}</div>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
@endif

<style>
    .fl-pricing-empty {
        display: flex;
        align-items: flex-start;
        padding: 0.75rem 0.85rem;
        background: #f8fafc;
        border: 1px dashed #cbd5e1;
        border-radius: 10px;
        font-size: 0.88rem;
    }
    .fl-pricing-block {
        border: 1px solid #e8ecf1;
        border-radius: 10px;
        background: #fff;
        padding: 0.85rem 0.95rem;
    }
    .fl-pricing-title { color: #1a2340; font-size: 0.95rem; }
    .fl-price-badge {
        font-size: 0.68rem; font-weight: 700; text-transform: uppercase;
        padding: 0.2rem 0.45rem; border-radius: 999px;
    }
    .fl-price-badge--service { background: rgba(234,138,43,0.14); color: #b45309; border: 1px solid rgba(234,138,43,0.35); }
    .fl-price-badge--sub { background: rgba(14,165,233,0.1); color: #0369a1; border: 1px solid rgba(14,165,233,0.25); }
    .fl-pricing-table thead th { background: #f9fafb; font-size: 0.82rem; white-space: nowrap; }
    .fl-pricing-table tbody td { vertical-align: middle; font-size: 0.88rem; }
    .fl-temp-badge { display: inline-block; padding: 0.25rem 0.5rem; border-radius: 6px; }
    .fl-temp-badge--future { background: #fff5f5; border: 1px solid #fed7d7; }
    .fl-temp-badge--future strong { color: #c53030; font-size: 0.95rem; }
    .fl-temp-badge--active { background: #f0fdf4; border: 1px solid #bbf7d0; }
    .fl-temp-badge--active strong { color: #166534; font-size: 0.95rem; }
    .fl-temp-badge small { font-size: 0.7rem; margin-top: 2px; }
    .fl-req-input { max-width: 130px; min-width: 100px; }
    .fl-req-badge {
        display: inline-block; font-size: 0.72rem; font-weight: 700;
        padding: 0.15rem 0.45rem; border-radius: 999px;
    }
    .fl-req-badge--pending { background: #fef3c7; color: #92400e; }
    .fl-req-badge--approved { background: #dcfce7; color: #166534; }
    .fl-req-badge--rejected { background: #fee2e2; color: #991b1b; }
</style>

@push('scripts')
<script>
$(function () {
    $(document).on('click', '.fl-req-submit', function () {
        var $form = $(this).closest('.fl-req-form');
        var subId = parseInt($form.data('sub-id') || '0', 10);
        var priceType = String($form.data('price-type') || '');
        var price = parseFloat(String($form.find('.fl-req-input').val() || '').trim());
        var $btn = $(this);
        var $cell = $form.closest('.fl-request-cell');

        if (!priceType || isNaN(price) || price <= 0) {
            toastr.error('Valid new price enter karein.');
            return;
        }

        $btn.prop('disabled', true).text('Sending…');

        $.ajax({
            url: @json(route('freelancer.personal-details.price-change-request')),
            type: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                'Accept': 'application/json'
            },
            data: {
                _token: $('meta[name="csrf-token"]').attr('content'),
                service_sub_service_id: subId,
                price_type: priceType,
                requested_price: price
            },
            success: function (res) {
                if (res.success) {
                    var priceFmt = '₹' + Number(price).toLocaleString('en-IN');
                    $cell.html(
                        '<div class="fl-req-pending">' +
                        '<span class="fl-req-badge fl-req-badge--pending">Pending admin review</span>' +
                        '<span class="small text-muted d-block">Requested: ' + priceFmt + '</span>' +
                        '<span class="small text-info d-block mt-1"><i class="fas fa-clock"></i> Admin response ka wait karein.</span>' +
                        '</div>'
                    );
                    toastr.success(res.message || 'Request sent to admin.');
                } else {
                    toastr.error(res.message || 'Request failed.');
                    $btn.prop('disabled', false).text('Request to admin');
                }
            },
            error: function (xhr) {
                var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Request failed.';
                toastr.error(msg);
                $btn.prop('disabled', false).text('Request to admin');
            }
        });
    });
});
</script>
@endpush
