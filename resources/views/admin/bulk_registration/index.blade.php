@extends('admin.layouts.app')

@section('title', 'Bulk Price Increase / Decrease')

@php
    $routePrefix = $routePrefix ?? 'admin';
    $stateBadges = [
        'scheduled' => ['Scheduled', 'bulk-badge--scheduled'],
        'active' => ['Active', 'bulk-badge--active'],
        'completed' => ['Applied', 'bulk-badge--completed'],
        'reverted' => ['Reverted', 'bulk-badge--reverted'],
        'failed' => ['Failed', 'bulk-badge--failed'],
    ];
    $fmtDate = fn ($d) => $d ? \Illuminate\Support\Carbon::parse($d)->format('d M Y, h:i A') : '—';
    $dtLocal = fn ($v) => $v ? \Illuminate\Support\Carbon::parse($v)->format('Y-m-d\TH:i') : '';
@endphp

@section('header-css')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
    .bulk-card { background:#fff; border-radius:18px; padding:24px; border:1px solid #e5e7eb; box-shadow:0 10px 30px rgba(0,0,0,0.05); font-family:Inter, Arial, sans-serif; color:#1f2933; }
    .bulk-title { font-size:24px; font-weight:800; margin-bottom:20px; color:#28392b; }
    .bulk-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:18px; margin-bottom:18px; }
    .bulk-field label { display:block; margin-bottom:8px; font-size:14px; font-weight:700; color:#243b35; }
    .bulk-field select, .bulk-field input { width:100%; height:46px; border:1px solid #cfd8dc; border-radius:12px; padding:0 14px; font-size:15px; outline:none; background:#fff; }
    .bulk-field select:focus, .bulk-field input:focus { border-color:#e39460; box-shadow:0 0 0 3px rgba(227,148,96,0.15); }
    .bulk-field small { display:block; margin-top:6px; color:#6b7280; font-size:12px; }
    .bulk-dates { display:grid; grid-template-columns:1fr 1fr; gap:10px; margin-top:10px; }
    .bulk-dates label { font-size:12px; margin-bottom:4px; }
    .bulk-dates input { height:40px; font-size:13px; }
    .bulk-span-2 { grid-column:span 2; }
    .bulk-buttons { display:flex; gap:14px; margin-top:24px; }
    .bulk-buttons button { border:0; border-radius:12px; padding:14px 20px; font-size:14px; font-weight:800; cursor:pointer; }
    .bulk-btn-primary { background:#e39460; color:#fff; }
    .bulk-btn-secondary { background:#eef2f4; color:#28392b; }
    .bulk-info-box { margin-top:12px; background:#fff7ed; border:1px solid #fed7aa; color:#7c2d12; padding:14px; border-radius:12px; font-size:14px; font-weight:600; }
    .bulk-preview { margin-top:12px; background:#f0fdf4; border:1px solid #bbf7d0; color:#14532d; padding:12px 14px; border-radius:12px; font-size:14px; font-weight:600; display:none; }
    .bulk-city-actions { display:flex; gap:8px; margin-top:8px; }
    .bulk-city-actions button { border:1px solid #cfd8dc; background:#fff; border-radius:8px; padding:4px 12px; font-size:12px; font-weight:700; }
    .select2-container .select2-selection--multiple { border:1px solid #cfd8dc; border-radius:12px; min-height:46px; padding:5px 10px; }
    .select2-container--default.select2-container--focus .select2-selection--multiple { border-color:#e39460; }
    .bulk-badge { display:inline-block; padding:3px 10px; border-radius:999px; font-size:12px; font-weight:700; }
    .bulk-badge--scheduled { background:#e0f2fe; color:#075985; }
    .bulk-badge--active { background:#dcfce7; color:#166534; }
    .bulk-badge--completed { background:#ede9fe; color:#5b21b6; }
    .bulk-badge--reverted { background:#f3f4f6; color:#374151; }
    .bulk-badge--failed { background:#fee2e2; color:#991b1b; }
    .bulk-history td, .bulk-history th { vertical-align:middle !important; }
    @media(max-width:1000px){ .bulk-grid { grid-template-columns:1fr; } .bulk-span-2 { grid-column:auto; } }
</style>
@endsection

@section('main')
<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Bulk Price Increase / Decrease</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route($routePrefix.'.dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item active">Bulk Price Increase</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif
            @if($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="bulk-card">
                <div class="bulk-title">Bulk Price Increase / Decrease</div>

                <form action="{{ route($routePrefix.'.bulk_registration.store') }}" method="POST" id="bulkPricingForm">
                    @csrf
                    <div class="bulk-grid">
                        <div class="bulk-field">
                            <label>Pricing Type</label>
                            <select id="pricingType" name="pricing_type" required>
                                <option value="">Select Type</option>
                                @foreach(\App\Models\BulkPricingRule::PRICING_TYPE_LABELS as $key => $label)
                                    <option value="{{ $key }}" @selected(old('pricing_type') === $key)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="bulk-field">
                            <label>Service</label>
                            <select id="serviceSelect" name="service_id">
                                <option value="">All Services</option>
                            </select>
                        </div>

                        <div class="bulk-field" id="subServiceWrapper" style="display:none;">
                            <label>Sub Service</label>
                            <select id="subServiceSelect" name="sub_service_id">
                                <option value="">All Sub Services</option>
                            </select>
                        </div>

                        <div class="bulk-field">
                            <label>Mode / Duty Type</label>
                            <select id="modeType" name="mode_type">
                                <option value="">All</option>
                            </select>
                        </div>
                    </div>

                    <div class="bulk-grid">
                        <div class="bulk-field">
                            <label>Change Type</label>
                            <select id="changeType" name="change_type" required>
                                @foreach(\App\Models\BulkPricingRule::CHANGE_TYPE_LABELS as $key => $label)
                                    <option value="{{ $key }}" @selected(old('change_type', 'increase_fixed') === $key)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="bulk-field">
                            <label>Value <span id="valueUnit">(₹)</span></label>
                            <input type="number" step="0.01" min="0.01" id="valueInput" name="value" value="{{ old('value') }}" placeholder="Enter Value" required>
                        </div>

                        <div class="bulk-field" id="applyToWrapper">
                            <label>Apply To</label>
                            <select id="applyTo" name="apply_to" required>
                                @foreach(\App\Models\BulkPricingRule::APPLY_TO_LABELS as $key => $label)
                                    <option value="{{ $key }}" @selected(old('apply_to', 'all') === $key)>{{ $label }}</option>
                                @endforeach
                            </select>
                            <div class="bulk-dates" id="applyToDates" style="display:none;">
                                <div>
                                    <label>Registered From</label>
                                    <input type="datetime-local" name="apply_from_date" value="{{ $dtLocal(old('apply_from_date')) }}">
                                </div>
                                <div>
                                    <label>Registered To</label>
                                    <input type="datetime-local" name="apply_to_date" value="{{ $dtLocal(old('apply_to_date')) }}">
                                </div>
                            </div>
                            <small id="applyToHint"></small>
                        </div>

                        <div class="bulk-field">
                            <label>City Filter</label>
                            <select id="cityFilterSelect" name="city_filter">
                                <option value="all" @selected(old('city_filter', 'all') === 'all')>All Cities</option>
                                <option value="tier_1" @selected(old('city_filter') === 'tier_1')>Tier 1 Cities</option>
                                <option value="tier_2" @selected(old('city_filter') === 'tier_2')>Tier 2 Cities</option>
                                <option value="tier_3" @selected(old('city_filter') === 'tier_3')>Tier 3 Cities</option>
                            </select>
                        </div>
                    </div>

                    <div class="bulk-grid">
                        <div class="bulk-field bulk-span-2" id="selectedCitiesWrapper" style="display:none;">
                            <label>Select Specific Cities (Leave empty to apply to all in Tier)</label>
                            <select id="selectedCitiesSelect" name="selected_cities[]" multiple="multiple" style="width:100%;"></select>
                            <div class="bulk-city-actions">
                                <button type="button" id="selectAllCities">Select All</button>
                                <button type="button" id="clearAllCities">Clear</button>
                                <span id="cityCount" class="small text-muted align-self-center"></span>
                            </div>
                        </div>

                        <div class="bulk-field">
                            <label>Time Period</label>
                            <select id="timePeriod" name="time_period" required>
                                <option value="permanent" @selected(old('time_period', 'permanent') === 'permanent')>Permanent Price Change</option>
                                <option value="temporary" @selected(old('time_period') === 'temporary')>Temporary Price Change</option>
                            </select>
                            <div class="bulk-dates" id="timePeriodDates" style="display:none;">
                                <div>
                                    <label>From Date</label>
                                    <input type="datetime-local" name="time_period_start_date" value="{{ $dtLocal(old('time_period_start_date')) }}">
                                </div>
                                <div>
                                    <label>To Date</label>
                                    <input type="datetime-local" name="time_period_end_date" value="{{ $dtLocal(old('time_period_end_date')) }}">
                                </div>
                            </div>
                            <small id="timePeriodHint"></small>
                        </div>
                    </div>

                    <div class="bulk-info-box">
                        Location ka price base hota hai. <strong>Increase</strong>: naya price = location price + value — jinka price isse kam hai woh naye price par aa jayenge, jinka pehle se zyada hai unka price same rahega.
                        <strong>Decrease</strong>: naya price = location price − value — jinka price isse zyada hai woh naye price par aa jayenge, jinka pehle se kam hai unka price same rahega.
                    </div>
                    <div class="bulk-preview" id="bulkPreview"></div>

                    <div class="bulk-buttons">
                        <button type="submit" class="bulk-btn-primary">Apply Bulk Update</button>
                        <button type="reset" class="bulk-btn-secondary" id="bulkResetBtn">Cancel</button>
                    </div>
                </form>
            </div>

            <div class="bulk-card" style="margin-top: 30px;">
                <div class="bulk-title">Active Bulk Pricing Rules &amp; History</div>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped bulk-history" style="font-size:13px;">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Created</th>
                                <th>Target</th>
                                <th>Service</th>
                                <th>Mode</th>
                                <th>Change</th>
                                <th>Apply To</th>
                                <th>City</th>
                                <th>Period</th>
                                <th>Status</th>
                                <th>Prices Changed</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($rules as $rule)
                                @php [$stateLabel, $stateClass] = $stateBadges[$rule->state] ?? [ucfirst($rule->state), 'bulk-badge--reverted']; @endphp
                                <tr>
                                    <td>{{ $rule->id }}</td>
                                    <td>
                                        {{ $fmtDate($rule->created_at) }}
                                        @if($rule->created_by_name)<br><small class="text-muted">by {{ $rule->created_by_name }}</small>@endif
                                    </td>
                                    <td>{{ \App\Models\BulkPricingRule::PRICING_TYPE_LABELS[$rule->pricing_type] ?? $rule->pricing_type }}</td>
                                    <td>{{ $rule->serviceLabel() }}</td>
                                    <td>{{ $rule->modeLabel() }}</td>
                                    <td>
                                        <span class="{{ $rule->isIncrease() ? 'text-success' : 'text-danger' }}" style="font-weight:700;">
                                            <i class="fas fa-arrow-{{ $rule->isIncrease() ? 'up' : 'down' }}"></i> {{ $rule->changeLabel() }}
                                        </span>
                                    </td>
                                    <td>
                                        {{ \App\Models\BulkPricingRule::APPLY_TO_LABELS[$rule->apply_to] ?? $rule->apply_to }}
                                        @if($rule->apply_from_date)
                                            <br><small class="text-muted">{{ $fmtDate($rule->apply_from_date) }} → {{ $fmtDate($rule->apply_to_date) }}</small>
                                        @endif
                                    </td>
                                    <td>{{ $rule->cityLabel() }}</td>
                                    <td>
                                        {{ ucfirst($rule->time_period) }}
                                        @if($rule->isTemporary())
                                            <br><small class="text-muted">{{ $fmtDate($rule->time_period_start_date) }} → {{ $fmtDate($rule->time_period_end_date) }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="bulk-badge {{ $stateClass }}">{{ $stateLabel }}</span>
                                        @if($rule->applied_at)<br><small class="text-muted">Applied: {{ $fmtDate($rule->applied_at) }}</small>@endif
                                        @if($rule->reverted_at)<br><small class="text-muted">Reverted: {{ $fmtDate($rule->reverted_at) }}</small>@endif
                                        @if($rule->error_message)<br><small class="text-danger">{{ \Illuminate\Support\Str::limit($rule->error_message, 120) }}</small>@endif
                                    </td>
                                    <td>
                                        {{ $rule->affected_count }}
                                        @if($rule->affected_count > 0)
                                            <br><a href="#" class="small bulk-view-changes" data-url="{{ route($routePrefix.'.bulk_registration.changes', $rule) }}" data-rule="{{ $rule->id }}">View details</a>
                                        @endif
                                    </td>
                                    <td>
                                        @if(in_array($rule->state, ['scheduled', 'active'], true))
                                            <form method="POST" action="{{ route($routePrefix.'.bulk_registration.stop', $rule) }}"
                                                  onsubmit="return confirm('{{ $rule->isTemporary() && $rule->applied_at ? 'Rule band karke purane price wapas lagane hain?' : 'Rule band karna hai?' }}');">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline-danger">{{ $rule->isTemporary() && $rule->applied_at ? 'Stop & Revert' : 'Stop' }}</button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="12" class="text-center">No bulk pricing rules found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                {{ $rules->links('pagination::bootstrap-4') }}
            </div>
        </div>
    </section>
</div>

<div class="modal fade" id="bulkChangesModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="bulkChangesTitle">Price changes</h5>
                <button type="button" class="close btn-close" data-bs-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table table-sm table-bordered" style="font-size:13px;">
                        <thead>
                            <tr><th>Record</th><th>Old</th><th>New</th><th>Changed at</th><th>Reverted at</th></tr>
                        </thead>
                        <tbody id="bulkChangesBody"></tbody>
                    </table>
                </div>
                <small class="text-muted" id="bulkChangesNote"></small>
            </div>
        </div>
    </div>
</div>
@endsection

@section('footer-script')
@php
    $serviceOptions = fn ($list) => $list->map(fn ($s) => [
        'id' => $s->id,
        'name' => $s->name,
        'sub_services' => $s->subServices->map(fn ($x) => ['id' => $x->id, 'name' => $x->name])->values(),
    ])->values();
    $servicesJson = $serviceOptions($services);
    $doctorServicesJson = $serviceOptions($doctorServices);
@endphp
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
(function () {
    const servicesData = @json($servicesJson);
    const doctorServicesData = @json($doctorServicesJson);
    const oldValues = {
        service: @json((string) old('service_id', '')),
        sub: @json((string) old('sub_service_id', '')),
        mode: @json((string) old('mode_type', '')),
        cities: @json(array_map('strval', (array) old('selected_cities', []))),
    };
    const doctorModes = [['online', 'Online'], ['home_visit', 'Home Visit'], ['clinic_visit', 'Clinic']];
    const providerModes = [['12_hours', '12 Hours'], ['24_hours', '24 Hours'], ['both', '12 + 24 Hours'], ['one_time', 'One-time']];

    const $ = window.jQuery;
    const pricingType = document.getElementById('pricingType');
    const serviceSelect = document.getElementById('serviceSelect');
    const subServiceSelect = document.getElementById('subServiceSelect');
    const subServiceWrapper = document.getElementById('subServiceWrapper');
    const modeType = document.getElementById('modeType');
    const changeType = document.getElementById('changeType');
    const valueInput = document.getElementById('valueInput');
    const applyTo = document.getElementById('applyTo');
    const timePeriod = document.getElementById('timePeriod');
    const cityFilter = document.getElementById('cityFilterSelect');

    let activeServices = servicesData;

    function escapeHtml(t) {
        const d = document.createElement('div');
        d.textContent = t == null ? '' : String(t);
        return d.innerHTML;
    }

    function isDoctorType() {
        return pricingType.value === 'doctor_payout' || pricingType.value === 'website_doctor';
    }

    function fillServices(selected) {
        activeServices = isDoctorType() ? doctorServicesData : servicesData;
        let html = '<option value="">All Services</option>';
        activeServices.forEach(s => {
            html += `<option value="${s.id}" ${String(s.id) === String(selected) ? 'selected' : ''}>${escapeHtml(s.name)}</option>`;
        });
        serviceSelect.innerHTML = html;
    }

    function fillSubServices(selected) {
        const svc = activeServices.find(s => String(s.id) === String(serviceSelect.value));
        const subs = svc && svc.sub_services ? svc.sub_services : [];
        let html = '<option value="">All Sub Services</option>';
        subs.forEach(sub => {
            html += `<option value="${sub.id}" ${String(sub.id) === String(selected) ? 'selected' : ''}>${escapeHtml(sub.name)}</option>`;
        });
        subServiceSelect.innerHTML = html;
        subServiceWrapper.style.display = subs.length > 0 ? '' : 'none';
        subServiceSelect.disabled = subs.length === 0;
    }

    function fillModes(selected) {
        const list = pricingType.value === '' ? [] : (isDoctorType() ? doctorModes : providerModes);
        let html = '<option value="">All</option>';
        list.forEach(([v, l]) => {
            html += `<option value="${v}" ${v === selected ? 'selected' : ''}>${l}</option>`;
        });
        modeType.innerHTML = html;
    }

    function syncApplyTo() {
        const websiteGeneral = pricingType.value === 'website_general';
        document.getElementById('applyToWrapper').style.display = websiteGeneral ? 'none' : '';
        if (websiteGeneral) applyTo.value = 'all';
        const needsDates = applyTo.value === 'new' || applyTo.value === 'old';
        document.getElementById('applyToDates').style.display = needsDates ? '' : 'none';
        document.querySelectorAll('#applyToDates input').forEach(i => { i.required = needsDates; i.disabled = !needsDates; });
        const hints = {
            all: 'Location price bhi change hoga — purane aur naye sab users par lagega.',
            new: 'Jo users is date range mein register hue / honge sirf unke price change honge.',
            old: 'Is date range mein register hue purane users ke price change honge.',
        };
        document.getElementById('applyToHint').textContent = hints[applyTo.value] || '';
    }

    function syncTimePeriod() {
        const temporary = timePeriod.value === 'temporary';
        document.getElementById('timePeriodDates').style.display = temporary ? '' : 'none';
        document.querySelectorAll('#timePeriodDates input').forEach(i => { i.required = temporary; i.disabled = !temporary; });
        document.getElementById('timePeriodHint').textContent = temporary
            ? 'From Date par price change honge aur To Date par purane price apne aap wapas aa jayenge.'
            : 'Price permanently change honge.';
    }

    function syncValueUnit() {
        const percent = changeType.value.endsWith('_percent');
        document.getElementById('valueUnit').textContent = percent ? '(%)' : '(₹)';
        valueInput.max = changeType.value === 'decrease_percent' ? '100' : '';
        renderPreview();
    }

    function renderPreview() {
        const box = document.getElementById('bulkPreview');
        const v = parseFloat(valueInput.value);
        if (!v || v <= 0) { box.style.display = 'none'; return; }
        const base = 100;
        let target = base;
        switch (changeType.value) {
            case 'increase_fixed': target = base + v; break;
            case 'increase_percent': target = base + base * v / 100; break;
            case 'decrease_fixed': target = Math.max(0, base - v); break;
            case 'decrease_percent': target = Math.max(0, base - base * v / 100); break;
        }
        target = Math.round(target * 100) / 100;
        const increase = changeType.value.startsWith('increase');
        box.innerHTML = increase
            ? `Example: location price ₹${base} → naya price ₹${target}. Jinka ₹${target} se kam hai woh ₹${target} ho jayenge; jinka ₹${target} ya zyada hai unka same rahega.`
            : `Example: location price ₹${base} → naya price ₹${target}. Jinka ₹${target} se zyada hai woh ₹${target} ho jayenge; jinka ₹${target} ya kam hai unka same rahega.`;
        box.style.display = '';
    }

    pricingType.addEventListener('change', function () {
        fillServices('');
        fillSubServices('');
        fillModes('');
        syncApplyTo();
    });
    serviceSelect.addEventListener('change', () => fillSubServices(''));
    applyTo.addEventListener('change', syncApplyTo);
    timePeriod.addEventListener('change', syncTimePeriod);
    changeType.addEventListener('change', syncValueUnit);
    valueInput.addEventListener('input', renderPreview);

    // City filter
    const $cities = $('#selectedCitiesSelect');
    let totalCities = 0;

    function updateCityCount() {
        const n = ($cities.val() || []).length;
        document.getElementById('cityCount').textContent = n === 0
            ? `Koi city select nahi — tier ki sabhi ${totalCities} cities par lagega`
            : `${n} / ${totalCities} cities selected`;
    }

    function loadCities(tier, preselect) {
        $cities.html('').trigger('change');
        $.get('{{ route($routePrefix.'.bulk_registration.cities_by_tier') }}', { tier: tier }, function (res) {
            if (!res.success) return;
            totalCities = res.cities.length;
            const selected = new Set(preselect || []);
            const frag = document.createDocumentFragment();
            res.cities.forEach(c => {
                const opt = document.createElement('option');
                opt.value = c.id;
                opt.textContent = c.state ? `${c.name}, ${c.state}` : c.name;
                if (selected.has(String(c.id))) opt.selected = true;
                frag.appendChild(opt);
            });
            $cities[0].appendChild(frag);
            $cities.trigger('change');
            updateCityCount();
        });
    }

    function syncCityFilter(preselect) {
        const tier = cityFilter.value;
        const isTier = ['tier_1', 'tier_2', 'tier_3'].includes(tier);
        document.getElementById('selectedCitiesWrapper').style.display = isTier ? '' : 'none';
        if (isTier) loadCities(tier, preselect); else { $cities.html('').trigger('change'); totalCities = 0; }
    }

    $cities.select2({ placeholder: 'Select cities...', allowClear: true, width: '100%' });
    $cities.on('change', updateCityCount);
    cityFilter.addEventListener('change', () => syncCityFilter([]));
    document.getElementById('selectAllCities').addEventListener('click', function () {
        $cities.find('option').prop('selected', true);
        $cities.trigger('change');
    });
    document.getElementById('clearAllCities').addEventListener('click', function () {
        $cities.val(null).trigger('change');
    });

    document.getElementById('bulkPricingForm').addEventListener('submit', function () {
        // All cities selected = whole tier; send nothing to stay under PHP max_input_vars.
        const n = ($cities.val() || []).length;
        if (totalCities > 0 && n === totalCities) {
            $cities.val(null);
        }
    });

    document.getElementById('bulkResetBtn').addEventListener('click', function () {
        setTimeout(function () {
            fillServices('');
            fillSubServices('');
            fillModes('');
            syncApplyTo();
            syncTimePeriod();
            syncValueUnit();
            syncCityFilter([]);
        }, 0);
    });

    // Changes modal
    document.querySelectorAll('.bulk-view-changes').forEach(function (link) {
        link.addEventListener('click', function (e) {
            e.preventDefault();
            const body = document.getElementById('bulkChangesBody');
            document.getElementById('bulkChangesTitle').textContent = `Rule #${link.dataset.rule} — price changes`;
            body.innerHTML = '<tr><td colspan="5" class="text-center">Loading...</td></tr>';
            document.getElementById('bulkChangesNote').textContent = '';
            bootstrap.Modal.getOrCreateInstance(document.getElementById('bulkChangesModal')).show();
            $.get(link.dataset.url, function (res) {
                const fmt = v => v === null || v === undefined ? '—' : '₹' + Number(v).toFixed(2);
                body.innerHTML = res.changes.length ? res.changes.map(c => `
                    <tr>
                        <td>${escapeHtml(c.label)}</td>
                        <td>${fmt(c.old_value)}</td>
                        <td><strong>${fmt(c.new_value)}</strong></td>
                        <td>${escapeHtml(c.changed_at || '—')}</td>
                        <td>${escapeHtml(c.reverted_at || '—')}</td>
                    </tr>`).join('') : '<tr><td colspan="5" class="text-center">No changes.</td></tr>';
                if (res.total > res.changes.length) {
                    document.getElementById('bulkChangesNote').textContent = `Showing first ${res.changes.length} of ${res.total} changes.`;
                }
            });
        });
    });

    // Initial state (keeps values after a validation error)
    fillServices(oldValues.service);
    fillSubServices(oldValues.sub);
    fillModes(oldValues.mode);
    syncApplyTo();
    syncTimePeriod();
    syncValueUnit();
    syncCityFilter(oldValues.cities);
})();
</script>
@endsection
