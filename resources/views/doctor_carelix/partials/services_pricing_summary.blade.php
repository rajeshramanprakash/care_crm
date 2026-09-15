@php
    /** @var \App\Models\DoctorRequest $doctor */
    use App\Models\DoctorConsultationService;
    use App\Models\DoctorConsultationPriceChangeRequest;
    use App\Models\LocationDoctorConsultationPrice;
    use App\Services\ConsultationLocationPricingService;

    $modeLabels = LocationDoctorConsultationPrice::MODE_LABELS;
    $pricingService = app(ConsultationLocationPricingService::class);

    $enabledModes = is_array($doctor->consultation_modes ?? null)
        ? array_values(array_filter($doctor->consultation_modes, fn ($m) => array_key_exists((string) $m, $modeLabels)))
        : [];
    $subServices = is_array($doctor->consultation_sub_services ?? null) ? $doctor->consultation_sub_services : [];
    $pricingRows = is_array($doctor->consultation_pricing ?? null) ? $doctor->consultation_pricing : [];

    $serviceName = trim((string) ($doctor->job_title ?? ''));
    $serviceRow = $serviceName !== ''
        ? DoctorConsultationService::query()->where('name', $serviceName)->first()
        : null;
    $serviceId = $serviceRow ? (int) $serviceRow->id : 0;
    $locationName = trim((string) ($doctor->city ?? $doctor->location ?? ''));

    $locationMatrix = $serviceId > 0 && $locationName !== ''
        ? $pricingService->locationPricesMatrix($locationName, $serviceId)
        : [];

    $pricingBySub = [];
    foreach ($pricingRows as $row) {
        if (! is_array($row)) {
            continue;
        }
        $sid = (int) ($row['sub_service_id'] ?? 0);
        $pricingBySub[$sid] = $row;
    }

    $requestsByKey = [];
    $recentRequests = [];
    foreach ($doctor->relationLoaded('priceChangeRequests') ? $doctor->priceChangeRequests : $doctor->priceChangeRequests()->orderByDesc('created_at')->limit(200)->get() as $req) {
        $key = (int) $req->sub_service_id.'|'.(string) $req->consultation_mode;
        if (! isset($requestsByKey[$key])) {
            $requestsByKey[$key] = $req;
        }
        if (count($recentRequests) < 30) {
            $recentRequests[] = $req;
        }
    }

    $fmtPrice = static function ($amount): string {
        if ($amount === null || $amount === '') {
            return '—';
        }
        if (! is_numeric($amount)) {
            return '—';
        }

        return '₹'.number_format((float) $amount, 0);
    };

    $displayItems = [];
    $seenSubIds = [];
    if (count($subServices) > 0) {
        foreach ($subServices as $subRow) {
            if (! is_array($subRow)) {
                continue;
            }
            $sid = (int) ($subRow['sub_service_id'] ?? 0);
            $seenSubIds[$sid] = true;
            $displayItems[] = [
                'sub_service_id' => $sid,
                'sub_service_name' => (string) ($subRow['sub_service_name'] ?? 'Sub-service'),
                'tags' => is_array($subRow['tags'] ?? null) ? $subRow['tags'] : [],
            ];
        }
    }

    foreach ($pricingBySub as $sid => $row) {
        $sid = (int) $sid;
        if ($sid > 0 && ! isset($seenSubIds[$sid])) {
            $seenSubIds[$sid] = true;
            $displayItems[] = [
                'sub_service_id' => $sid,
                'sub_service_name' => (string) ($row['sub_service_name'] ?? 'Sub-service'),
                'tags' => [],
            ];
        }
    }

    if ($displayItems === []) {
        $displayItems[] = [
            'sub_service_id' => 0,
            'sub_service_name' => null,
            'tags' => is_array($doctor->specializations ?? null) ? $doctor->specializations : [],
        ];
    }

    $activeTempRules = \App\Models\BulkPricingRule::where('status', 1)
        ->where('time_period', 'temporary')
        ->where('pricing_type', 'website_doctor')
        ->get();
        
    $tier1Cities = \App\Models\Location::where('tier', 'Tier 1')->pluck('name')->toArray();
    
    $normalizeMode = static function($mode) {
        if (!$mode) return null;
        return match (strtolower(trim($mode))) {
            'online' => 'online',
            'home visit' => 'home_visit',
            'clinic' => 'clinic_visit',
            default => strtolower(str_replace(' ', '_', trim($mode)))
        };
    };

    $getMatchingTempRule = static function($subId, $modeKey) use ($activeTempRules, $serviceId, $locationName, $normalizeMode, $tier1Cities) {
        foreach ($activeTempRules as $rule) {
            if ($rule->service_id && (int)$rule->service_id !== (int)$serviceId) continue;
            if ($rule->sub_service_id && (int)$rule->sub_service_id !== (int)$subId) continue;
            
            $ruleMode = $normalizeMode($rule->mode_type);
            if ($ruleMode && $ruleMode !== $modeKey) continue;
            
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

    $requestStatusLabel = static function (string $status): string {
        return match ($status) {
            DoctorConsultationPriceChangeRequest::STATUS_PENDING => 'Pending admin review',
            DoctorConsultationPriceChangeRequest::STATUS_APPROVED => 'Approved by admin',
            DoctorConsultationPriceChangeRequest::STATUS_REJECTED => 'Rejected by admin',
            default => ucfirst($status),
        };
    };
@endphp

@if($serviceName === '')
    <p class="dr-empty mb-0">No consultation service is assigned to your profile yet. Contact Carelix admin if this looks wrong.</p>
@else
    <div class="dr-portal-svc-head mb-3">
        <div class="dr-portal-svc-name">{{ $serviceName }}</div>
        @if($serviceRow && $serviceRow->category)
            <div class="small text-muted">Category: {{ $serviceRow->category }}</div>
        @endif
        @if($locationName !== '')
            <div class="small text-muted">Location: {{ $locationName }}</div>
        @endif
        @if(count($enabledModes) > 0)
            <div class="dr-portal-mode-badges mt-2">
                @foreach($enabledModes as $modeKey)
                    <span class="dr-chip dr-chip--info">{{ $modeLabels[(string) $modeKey] ?? $modeKey }}</span>
                @endforeach
            </div>
        @else
            <p class="small text-warning mb-0 mt-2"><i class="fas fa-exclamation-circle"></i> No consultation modes are enabled on your profile.</p>
        @endif
        <p class="small text-muted mb-0 mt-2">To change your charge, enter the new amount and send it to admin for verification.</p>
    </div>

    @if(count($displayItems) === 0)
        <p class="dr-empty mb-0">No sub-services are linked to your profile.</p>
    @else
        @foreach($displayItems as $item)
            @php
                $subId = (int) ($item['sub_service_id'] ?? 0);
                $subName = $item['sub_service_name'] ?? null;
                $tags = $item['tags'] ?? [];
                $hasDoctorOverride = isset($pricingBySub[$subId]);
            @endphp
            <div class="dr-portal-sub-block mb-3">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-1">
                    <div class="dr-portal-sub-title mb-0">
                        @if($subName)
                            {{ $subName }}
                        @else
                            Service-level pricing
                        @endif
                    </div>
                    @if($hasDoctorOverride)
                        <span class="dr-portal-price-badge dr-portal-price-badge--personal">Your profile pricing</span>
                    @elseif($locationName !== '' && (isset($locationMatrix[$subId]) || ($subId > 0 && isset($locationMatrix[0]))))
                        <span class="dr-portal-price-badge dr-portal-price-badge--location">Location default pricing</span>
                    @endif
                </div>
                @if(is_array($tags) && count($tags) > 0)
                    <div class="dr-portal-sub-tags mb-2">
                        @foreach($tags as $tag)
                            <span class="dr-chip">{{ $tag }}</span>
                        @endforeach
                    </div>
                @else
                    <p class="small text-muted mb-2">No specialization tags selected.</p>
                @endif

                @if(count($enabledModes) === 0)
                    <p class="dr-empty mb-0">Enable consultation modes to see pricing.</p>
                @else
                    <div class="dr-table-wrap">
                        <table class="table table-sm dr-table-pro mb-0 dr-portal-pricing-table">
                            <thead>
                                <tr>
                                    <th>Consultation mode</th>
                                    <th>Your charge</th>
                                    <th>Temporary price</th>
                                    <th>Request new price (₹)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($enabledModes as $modeKey)
                                    @php
                                        $modeKey = (string) $modeKey;
                                        $prices = $pricingService->portalDisplayPricesForSubMode(
                                            $doctor,
                                            $locationMatrix,
                                            $subId,
                                            $modeKey
                                        );
                                        $reqKey = $subId.'|'.$modeKey;
                                        $latestReq = $requestsByKey[$reqKey] ?? null;
                                        $isPending = $latestReq && $latestReq->status === DoctorConsultationPriceChangeRequest::STATUS_PENDING;
                                    @endphp
                                    <tr>
                                        <td>{{ $modeLabels[$modeKey] ?? $modeKey }}</td>
                                        @php
                                            $tempRule = $getMatchingTempRule($subId, $modeKey);
                                            $now = \Carbon\Carbon::now();
                                            $isActive = false;
                                            $isFuture = false;
                                            $start = null;
                                            $end = null;
                                            if ($tempRule) {
                                                $start = \Carbon\Carbon::parse($tempRule->time_period_start_date);
                                                $end = $tempRule->time_period_end_date ? \Carbon\Carbon::parse($tempRule->time_period_end_date) : null;
                                                $isActive = $now->gte($start) && (!$end || $now->lte($end));
                                                $isFuture = $now->lt($start);
                                            }
                                            
                                            $currentDoctorPrice = $prices['doctor'];
                                            if ($tempRule && $tempRule->pricing_type === 'doctor_payout') {
                                                if ((int)$subId === 0) {
                                                    $colMap = [
                                                        'online' => 'online_charges',
                                                        'home_visit' => 'home_visit_charges',
                                                        'clinic_visit' => 'clinic_consultation_charges'
                                                    ];
                                                    if (isset($colMap[$modeKey])) {
                                                        $origCol = 'original_' . $colMap[$modeKey];
                                                        if (isset($doctor->{$origCol}) && $doctor->{$origCol} !== null) {
                                                            $currentDoctorPrice = $doctor->{$origCol};
                                                        }
                                                    }
                                                } else {
                                                    $pricingArr = $doctor->consultation_pricing;
                                                    if (is_array($pricingArr)) {
                                                        foreach ($pricingArr as $row) {
                                                            if ((int)($row['sub_service_id'] ?? 0) === (int)$subId) {
                                                                if (isset($row['modes'][$modeKey]['original_doctor_price'])) {
                                                                    $currentDoctorPrice = $row['modes'][$modeKey]['original_doctor_price'];
                                                                }
                                                                break;
                                                            }
                                                        }
                                                    }
                                                }
                                            }
                                        @endphp
                                        <td><strong>{{ $fmtPrice($currentDoctorPrice) }}</strong></td>
                                        <td>
                                            @if($tempRule && ($isActive || $isFuture))
                                                @php
                                                    $displayPrice = $tempRule->pricing_type === 'website_doctor' ? $prices['website'] : $prices['doctor'];
                                                    if (!$displayPrice) {
                                                        $displayPrice = $tempRule->value; // fallback if it hasn't run yet or is 0
                                                    }
                                                @endphp
                                                <div class="dr-temp-badge {{ $isActive ? 'dr-temp-badge--active' : 'dr-temp-badge--future' }}">
                                                    <strong>{{ $fmtPrice($displayPrice) }}</strong>
                                                    <small class="d-block text-muted">
                                                        From: {{ $start->format('d M, y, h:i A') }}<br>
                                                        To: {{ $end ? $end->format('d M, y, h:i A') : 'Ongoing' }}
                                                    </small>
                                                </div>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td class="dr-portal-request-cell">
                                            @if($isPending)
                                                <div class="dr-portal-req-pending">
                                                    <span class="dr-portal-req-badge dr-portal-req-badge--pending">Pending admin review</span>
                                                    <span class="small text-muted d-block">Requested: {{ $fmtPrice($latestReq->requested_price) }}</span>
                                                    <span class="small text-info d-block mt-1"><i class="fas fa-clock"></i> Please wait for admin response.</span>
                                                </div>
                                            @else
                                                <div class="dr-portal-req-form d-flex flex-wrap align-items-center gap-2"
                                                     data-sub-id="{{ $subId }}"
                                                     data-mode="{{ $modeKey }}">
                                                    <input type="number" class="form-control form-control-sm dr-portal-req-input"
                                                           min="1" step="1" placeholder="New price"
                                                           aria-label="Request new price for {{ $modeLabels[$modeKey] ?? $modeKey }}">
                                                    <button type="button" class="btn btn-sm btn-warning dr-portal-req-submit">
                                                        Send to admin
                                                    </button>
                                                </div>
                                            @endif
                                            @if($latestReq && ! $isPending)
                                                <div class="dr-portal-req-outcome mt-1">
                                                    <span class="dr-portal-req-badge dr-portal-req-badge--{{ $latestReq->status }}">
                                                        {{ $requestStatusLabel($latestReq->status) }}
                                                    </span>
                                                    <span class="small d-block text-muted">
                                                        {{ $fmtPrice($latestReq->current_price) }} → {{ $fmtPrice($latestReq->requested_price) }}
                                                        @if($latestReq->reviewed_at)
                                                            · {{ $latestReq->reviewed_at->format('d M Y') }}
                                                        @endif
                                                    </span>
                                                    @if($latestReq->status === DoctorConsultationPriceChangeRequest::STATUS_REJECTED && $latestReq->admin_note)
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
                @endif
            </div>
        @endforeach
    @endif

    @if(count($recentRequests) > 0)
        <div class="dr-portal-req-history mt-3 pt-3 border-top">
            <div class="small font-weight-bold text-muted mb-2">Recent price change requests</div>
            <div class="dr-table-wrap">
                <table class="table table-sm dr-table-pro mb-0">
                    <thead>
                        <tr>
                            <th>When</th>
                            <th>Sub-service</th>
                            <th>Mode</th>
                            <th>Was</th>
                            <th>Requested</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($recentRequests as $hist)
                            <tr>
                                <td class="text-nowrap">{{ $hist->created_at?->format('d M Y') ?? '—' }}</td>
                                <td>{{ $hist->sub_service_name ?: 'Service-level' }}</td>
                                <td>{{ $hist->modeLabel() }}</td>
                                <td>{{ $fmtPrice($hist->current_price) }}</td>
                                <td>{{ $fmtPrice($hist->requested_price) }}</td>
                                <td>
                                    <span class="dr-portal-req-badge dr-portal-req-badge--{{ $hist->status }}">
                                        {{ $requestStatusLabel($hist->status) }}
                                    </span>
                                    @if($hist->status === DoctorConsultationPriceChangeRequest::STATUS_REJECTED && $hist->admin_note)
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

    @if($doctor->coverage_radius_km !== null || $doctor->clinic_name || $doctor->base_location_address)
        <div class="dr-portal-loc-note mt-2 pt-2 border-top">
            <div class="small font-weight-bold text-muted mb-1">Location details</div>
            @if(in_array('home_visit', $enabledModes, true))
                @if($doctor->coverage_radius_km !== null)
                    <div class="small"><strong>Home visit radius:</strong> {{ $doctor->coverage_radius_km }} km</div>
                @endif
                @if($doctor->base_location_address)
                    <div class="small"><strong>Base location:</strong> {{ $doctor->base_location_address }}</div>
                @endif
            @endif
            @if(in_array('clinic_visit', $enabledModes, true))
                @if($doctor->clinic_name)
                    <div class="small"><strong>Clinic:</strong> {{ $doctor->clinic_name }}</div>
                @endif
                @if($doctor->clinic_address)
                    <div class="small"><strong>Clinic address:</strong> {{ $doctor->clinic_address }}</div>
                @endif
            @endif
        </div>
    @endif
@endif

<style>
    .dr-portal-svc-name {
        font-size: 1.05rem;
        font-weight: 700;
        color: #1a2340;
    }
    .dr-portal-mode-badges {
        display: flex;
        flex-wrap: wrap;
        gap: 0.35rem;
    }
    .dr-portal-sub-block {
        border: 1px solid #e8ecf1;
        border-radius: 10px;
        background: #fff;
        padding: 0.85rem 0.95rem;
    }
    .dr-portal-sub-title {
        font-weight: 700;
        color: #1a2340;
        font-size: 0.92rem;
        margin-bottom: 0.35rem;
    }
    .dr-portal-sub-tags {
        display: flex;
        flex-wrap: wrap;
        gap: 0.35rem;
    }
    .dr-portal-price-badge {
        font-size: 0.68rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.03em;
        padding: 0.2rem 0.45rem;
        border-radius: 999px;
        line-height: 1.2;
    }
    .dr-portal-price-badge--personal {
        background: rgba(234, 138, 43, 0.14);
        color: #b45309;
        border: 1px solid rgba(234, 138, 43, 0.35);
    }
    .dr-portal-price-badge--location {
        background: rgba(14, 165, 233, 0.1);
        color: #0369a1;
        border: 1px solid rgba(14, 165, 233, 0.25);
    }
    .dr-portal-pricing-table thead th {
        white-space: nowrap;
    }
    .dr-portal-pricing-table tbody td {
        vertical-align: middle;
    }
    .dr-portal-req-input {
        max-width: 120px;
        min-width: 90px;
    }
    .dr-portal-req-badge {
        display: inline-block;
        font-size: 0.72rem;
        font-weight: 700;
        padding: 0.15rem 0.45rem;
        border-radius: 999px;
    }
    .dr-portal-req-badge--pending {
        background: #fef3c7;
        color: #92400e;
    }
    .dr-portal-req-badge--approved {
        background: #dcfce7;
        color: #166534;
    }
    .dr-portal-req-badge--rejected {
        background: #fee2e2;
        color: #991b1b;
    }
    .dr-temp-badge { display: inline-block; padding: 0.25rem 0.5rem; border-radius: 6px; }
    .dr-temp-badge--future { background: #fff5f5; border: 1px solid #fed7d7; }
    .dr-temp-badge--future strong { color: #c53030; font-size: 0.95rem; }
    .dr-temp-badge--active { background: #f0fdf4; border: 1px solid #bbf7d0; }
    .dr-temp-badge--active strong { color: #166534; font-size: 0.95rem; }
    .dr-temp-badge small { font-size: 0.7rem; margin-top: 2px; }
</style>

@push('scripts')
<script>
$(function () {
    $(document).on('click', '.dr-portal-req-submit', function () {
        var $form = $(this).closest('.dr-portal-req-form');
        var subId = parseInt($form.data('sub-id') || '0', 10);
        var mode = String($form.data('mode') || '');
        var price = parseFloat(String($form.find('.dr-portal-req-input').val() || '').trim());
        var $btn = $(this);
        var $cell = $form.closest('.dr-portal-request-cell');

        if (!mode || isNaN(price) || price <= 0) {
            if (typeof toastr !== 'undefined') {
                toastr.error('Please enter a valid new price.');
            } else {
                alert('Please enter a valid new price.');
            }
            return;
        }

        $btn.prop('disabled', true).text('Sending…');

        $.ajax({
            url: @json(route('doctor_portal.personal-details.price-change-request')),
            type: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                'Accept': 'application/json'
            },
            data: {
                _token: $('meta[name="csrf-token"]').attr('content'),
                sub_service_id: subId,
                consultation_mode: mode,
                requested_price: price
            },
            success: function (res) {
                if (res.success) {
                    var priceFmt = '₹' + Math.round(price).toLocaleString('en-IN');
                    $cell.html(
                        '<div class="dr-portal-req-pending">' +
                        '<span class="dr-portal-req-badge dr-portal-req-badge--pending">Pending admin review</span>' +
                        '<span class="small text-muted d-block">Requested: ' + priceFmt + '</span>' +
                        '<span class="small text-info d-block mt-1"><i class="fas fa-clock"></i> Please wait for admin response.</span>' +
                        '</div>'
                    );
                    if (typeof toastr !== 'undefined') {
                        toastr.success(res.message || 'Request sent to admin.');
                    }
                } else {
                    if (typeof toastr !== 'undefined') {
                        toastr.error(res.message || 'Could not send request.');
                    } else {
                        alert(res.message || 'Could not send request.');
                    }
                    $btn.prop('disabled', false).text('Send to admin');
                }
            },
            error: function (xhr) {
                var msg = (xhr.responseJSON && xhr.responseJSON.message)
                    ? xhr.responseJSON.message
                    : 'Could not send request.';
                if (typeof toastr !== 'undefined') {
                    toastr.error(msg);
                } else {
                    alert(msg);
                }
                $btn.prop('disabled', false).text('Send to admin');
            }
        });
    });
});
</script>
@endpush
