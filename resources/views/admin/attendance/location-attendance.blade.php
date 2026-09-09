@extends('admin.layouts.app')

@section('title', 'Freelancer & Vendor Attendance')

@section('header-css')
<style>
    /* Body uses overflow:hidden in admin layout; scroll this main area vertically */
    .content-wrapper.location-attendance-scroll {
        height: calc(100vh - 120px);
        max-height: calc(100vh - 120px);
        overflow-y: auto !important;
        overflow-x: hidden !important;
        -webkit-overflow-scrolling: touch;
    }
    @media (max-width: 991.98px) {
        .content-wrapper.location-attendance-scroll {
            height: calc(100vh - 88px);
            max-height: calc(100vh - 88px);
        }
    }
    .attendance-filter-card .form-label {
        font-size: 0.8125rem;
        font-weight: 600;
        color: #374151;
        margin-bottom: 0.35rem;
    }
    .attendance-page-title {
        font-size: 1.35rem;
        font-weight: 700;
        color: #111827;
    }
    .attendance-legend-dot {
        width: 11px;
        height: 11px;
        border-radius: 50%;
        display: inline-block;
        vertical-align: middle;
        margin-right: 4px;
    }
    .attendance-dot-row {
        min-width: 120px;
    }
    .clear-filters-link {
        font-size: 0.875rem;
    }
    .la-day-card {
        border: 1px solid #e5e7eb;
        border-radius: 10px;
        padding: 12px 14px;
        margin-bottom: 12px;
        background: #fafafa;
    }
    .la-day-card h6 {
        font-size: 0.95rem;
        font-weight: 700;
        color: #111827;
        margin-bottom: 8px;
    }
    .la-selfie-thumb {
        max-width: 220px;
        max-height: 260px;
        border-radius: 8px;
        border: 1px solid #e5e7eb;
        object-fit: cover;
    }
    .la-detail-row {
        font-size: 0.8125rem;
        margin-bottom: 4px;
        color: #374151;
    }
    .la-detail-row strong {
        color: #111827;
        min-width: 140px;
        display: inline-block;
    }
    /* Pagination — AdminLTE/Bootstrap 4 friendly + Carelix accent */
    .location-attendance-pagination {
        display: flex;
        justify-content: center;
        flex-wrap: wrap;
        width: 100%;
    }
    .location-attendance-pagination .pagination {
        margin-bottom: 0;
        flex-wrap: wrap;
        justify-content: center;
        gap: 4px;
    }
    .location-attendance-pagination .page-item .page-link {
        border-radius: 8px;
        margin: 0;
        padding: 0.45rem 0.85rem;
        min-width: 2.35rem;
        text-align: center;
        font-size: 0.875rem;
        font-weight: 500;
        color: #374151;
        background: #fff;
        border: 1px solid #e5e7eb;
        line-height: 1.35;
        transition: background 0.15s, border-color 0.15s, color 0.15s;
    }
    .location-attendance-pagination .page-item:not(.disabled):not(.active) .page-link:hover {
        background: #fff7ed;
        border-color: #F7941D;
        color: #c2610c;
        z-index: 1;
    }
    .location-attendance-pagination .page-item.active .page-link {
        background: #F7941D;
        border-color: #e8890b;
        color: #fff;
        cursor: default;
        box-shadow: 0 1px 3px rgba(247, 148, 29, 0.35);
    }
    .location-attendance-pagination .page-item.disabled .page-link {
        background: #f9fafb;
        border-color: #e5e7eb;
        color: #9ca3af;
        cursor: not-allowed;
        opacity: 0.95;
    }
</style>
@endsection

@section('main')
<div class="content-wrapper location-attendance-scroll pb-4">
    <section class="content-header pb-2">
        <div class="container-fluid">
            <div class="row align-items-center mb-1">
                <div class="col">
                    <h1 class="attendance-page-title m-0">Freelancer & Vendor Attendance</h1>
                    <p class="text-muted mb-0 small mt-1">Deployments with GPS attendance (day-wise). Use filters to find by customer, lead, provider, or executive.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid pt-2">
            <div class="card attendance-filter-card shadow-sm">
                <div class="card-header bg-white border-bottom py-3">
                    <strong class="text-dark">Filters</strong>
                </div>
                <div class="card-body">
                    <form method="GET" action="{{ route('admin.location_attendance.index') }}" class="row g-3">
                        <div class="col-md-6 col-lg-3">
                            <label class="form-label">Attendance date (highlight)</label>
                            <input type="date" class="form-control" name="attendance_date" value="{{ $date }}">
                        </div>
                        <div class="col-md-6 col-lg-2">
                            <label class="form-label">Operation lead # (DB id)</label>
                            <input type="number" class="form-control" name="operation_lead_id" value="{{ request('operation_lead_id') }}" placeholder="e.g. 1024" min="1">
                        </div>
                        <div class="col-md-6 col-lg-3">
                            <label class="form-label">Customer / lead search</label>
                            <input type="text" class="form-control" name="customer_q" value="{{ request('customer_q') }}" placeholder="Name, phone, or CRM lead id">
                        </div>
                        <div class="col-md-6 col-lg-2">
                            <label class="form-label">Assigned to (provider)</label>
                            <input type="text" class="form-control" name="provider_q" value="{{ request('provider_q') }}" placeholder="Vendor / freelancer">
                        </div>
                        <div class="col-md-6 col-lg-2">
                            <label class="form-label">Lead executive</label>
                            <input type="text" class="form-control" name="executive_q" value="{{ request('executive_q') }}" placeholder="Ops executive name">
                        </div>
                        <div class="col-md-6 col-lg-2">
                            <label class="form-label">Assignment type</label>
                            <select class="form-control" name="assignment_type">
                                <option value="" @selected(request('assignment_type') === null || request('assignment_type') === '')>All</option>
                                <option value="vendor" @selected(request('assignment_type') === 'vendor')>Vendor</option>
                                <option value="freelancer" @selected(request('assignment_type') === 'freelancer')>Freelancer</option>
                            </select>
                        </div>
                        <div class="col-12 d-flex flex-wrap align-items-end gap-2 pt-1">
                            <button class="btn btn-primary" type="submit">
                                <i class="fas fa-search mr-1"></i> Apply filters
                            </button>
                            <a href="{{ route('admin.location_attendance.index') }}" class="btn btn-outline-secondary">Reset</a>
                            <span class="text-muted small ml-md-2">
                                <span class="attendance-legend-dot" style="background:#28a745;"></span> Matched attendance
                                <span class="attendance-legend-dot ml-2" style="background:#c9ced6;border:1px solid #9fa6b2;"></span> Pending / not matched
                                <span class="ml-2">· Dark ring = selected date</span>
                            </span>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card shadow-sm mt-3">
                <div class="card-header bg-white border-bottom d-flex flex-wrap justify-content-between align-items-center py-3">
                    <strong class="text-dark">Results</strong>
                    @if(method_exists($rows, 'total'))
                        <span class="text-muted small">{{ $rows->total() }} total · showing {{ $rows->count() }} on this page</span>
                    @endif
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-striped table-bordered mb-0">
                            <thead class="thead-light">
                                <tr>
                                    <th>Lead ref</th>
                                    <th>Customer</th>
                                    <th>Lead executive</th>
                                    <th>Type</th>
                                    <th>Assigned to</th>
                                    <th>From</th>
                                    <th>To</th>
                                    <th class="attendance-dot-row">Day status</th>
                                    <th class="text-nowrap">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($rows as $row)
                                    @php
                                        $isVendor = !empty($row->vendor_id);
                                        $providerName = $isVendor ? ($row->vendor->name ?? 'N/A') : ($row->freelanceStaff->name ?? 'N/A');
                                        $dayStatuses = $row->attendance_day_statuses ?? [];
                                        $exec = $row->operationLead->executive ?? null;
                                        $execName = $exec ? trim(($exec->f_name ?? '') . ' ' . ($exec->l_name ?? '')) : '';
                                    @endphp
                                    <tr>
                                        <td class="text-nowrap">{{ $row->operationLead->lead_id ?? ('#'.$row->operation_lead_id) }}</td>
                                        <td>
                                            <div>{{ $row->operationLead->customer_name ?? 'N/A' }}</div>
                                            @if(!empty($row->operationLead->contact_no))
                                                <small class="text-muted">{{ $row->operationLead->contact_no }}</small>
                                            @endif
                                        </td>
                                        <td>{{ $execName !== '' ? $execName : '—' }}</td>
                                        <td><span class="badge badge-{{ $isVendor ? 'info' : 'secondary' }}">{{ $isVendor ? 'Vendor' : 'Freelancer' }}</span></td>
                                        <td>{{ $providerName }}</td>
                                        <td class="text-nowrap small">
                                            {{ $row->deployment_from_date ? \Carbon\Carbon::parse($row->deployment_from_date)->format('d M Y, h:i A') : 'N/A' }}
                                        </td>
                                        <td class="text-nowrap small">
                                            {{ $row->deployment_to_date ? \Carbon\Carbon::parse($row->deployment_to_date)->format('d M Y, h:i A') : 'N/A' }}
                                        </td>
                                        <td>
                                            @if(count($dayStatuses))
                                                <div class="d-flex flex-wrap align-items-center" style="gap: 7px;">
                                                    @foreach($dayStatuses as $day)
                                                        @php
                                                            $isSelectedDate = ($day['date'] ?? '') === $date;
                                                        @endphp
                                                        <span
                                                            title="{{ \Carbon\Carbon::parse($day['date'])->format('d M Y') }} — {{ $day['is_marked'] ? 'Attendance matched' : 'Pending / not matched' }}"
                                                            style="
                                                                width: 12px;
                                                                height: 12px;
                                                                border-radius: 50%;
                                                                display: inline-block;
                                                                background: {{ $day['is_marked'] ? '#28a745' : '#c9ced6' }};
                                                                border: {{ $isSelectedDate ? '2px solid #1f2d3d' : '1px solid #9fa6b2' }};
                                                            "
                                                        ></span>
                                                    @endforeach
                                                </div>
                                            @else
                                                <span class="text-muted small">No range</span>
                                            @endif
                                        </td>
                                        <td class="text-nowrap">
                                            <button
                                                type="button"
                                                class="btn btn-sm btn-outline-primary la-attendance-view-btn"
                                                data-detail-url="{{ route('admin.location_attendance.deployment_detail', $row->id) }}"
                                                title="Selfie, GPS times & locations (day by day)"
                                            >
                                                <i class="fas fa-eye"></i> View
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="text-center text-muted py-4">No deployment records match your filters.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if(method_exists($rows, 'links'))
                        <div class="p-3 border-top bg-light location-attendance-pagination">
                            {{ $rows->links('pagination::bootstrap-4') }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </section>
</div>

<div class="modal fade" id="laAttendanceDetailModal" tabindex="-1" aria-labelledby="laAttendanceDetailModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="laAttendanceDetailModalTitle">Attendance detail</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="laAttendanceDetailModalBody">
                <p class="text-muted mb-0">Loading…</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('footer-script')
<script>
(function () {
    function esc(s) {
        if (s === null || s === undefined) return '';
        return String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }
    function linkMaps(label, url) {
        if (!url) return '<span class="text-muted">—</span>';
        return '<a href="' + esc(url) + '" target="_blank" rel="noopener noreferrer">' + esc(label) + '</a>';
    }
    function showAttendanceModal() {
        var el = document.getElementById('laAttendanceDetailModal');
        if (!el || typeof bootstrap === 'undefined') return;
        bootstrap.Modal.getOrCreateInstance(el).show();
    }
    document.querySelectorAll('.la-attendance-view-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var url = this.getAttribute('data-detail-url');
            var body = document.getElementById('laAttendanceDetailModalBody');
            body.innerHTML = '<p class="text-muted mb-0"><i class="fas fa-spinner fa-spin mr-1"></i> Loading…</p>';
            showAttendanceModal();
            fetch(url, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin'
            }).then(function (r) {
                return r.text().then(function (text) {
                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        throw new Error('Invalid response from server.');
                    }
                });
            }).then(function (data) {
                if (!data.success) {
                    body.innerHTML = '<div class="alert alert-danger">' + esc(data.message || 'Failed to load.') + '</div>';
                    return;
                }
                var d = data.deployment || {};
                var days = data.days || [];
                var typeLabel = d.assignment_type === 'vendor' ? 'Vendor' : 'Freelancer';
                var head =
                    '<div class="mb-3 pb-2 border-bottom">' +
                    '<div class="font-weight-bold text-dark">' + esc(d.lead_ref || '') + ' · ' + esc(d.customer_name || '') + '</div>' +
                    '<div class="small text-muted">' + esc(d.contact_no || '') + '</div>' +
                    '<div class="mt-2 small"><strong>' + esc(typeLabel) + ':</strong> ' + esc(d.provider_name || '') + '</div>' +
                    '<div class="small mt-1"><strong>Deployment:</strong> ' + esc(d.deployment_from || '—') + ' → ' + esc(d.deployment_to || '—') + '</div>' +
                    '</div>';
                if (!days.length) {
                    body.innerHTML = head + '<p class="text-muted mb-0">Is deployment ke liye date range set nahi hai.</p>';
                    return;
                }
                var blocks = days.map(function (day) {
                    if (!day.has_record) {
                        return '<div class="la-day-card"><h6>' + esc(day.date_label) + '</h6><span class="badge badge-light border">No attendance record</span></div>';
                    }
                    var provCoords = (day.provider_latitude != null && day.provider_longitude != null)
                        ? (Number(day.provider_latitude).toFixed(5) + ', ' + Number(day.provider_longitude).toFixed(5))
                        : '—';
                    var custCoords = (day.customer_latitude != null && day.customer_longitude != null)
                        ? (Number(day.customer_latitude).toFixed(5) + ', ' + Number(day.customer_longitude).toFixed(5))
                        : '—';
                    var statusBadge = day.is_location_matched
                        ? '<span class="badge badge-success">Matched</span>'
                        : '<span class="badge badge-secondary">' + esc(day.attendance_status || 'pending') + '</span>';
                    var dist = (day.distance_meters != null && day.distance_meters !== '')
                        ? (Math.round(Number(day.distance_meters)) + ' m')
                        : '—';
                    var selfieBlock = '';
                    if (d.assignment_type === 'freelancer') {
                        if (day.freelancer_selfie_url) {
                            selfieBlock =
                                '<div class="mt-2">' +
                                '<div class="la-detail-row"><strong>Selfie</strong> <span class="text-muted">' + esc(day.freelancer_selfie_at || '') + '</span></div>' +
                                '<a href="' + esc(day.freelancer_selfie_url) + '" target="_blank" rel="noopener noreferrer">' +
                                '<img class="la-selfie-thumb mt-1" src="' + esc(day.freelancer_selfie_url) + '" alt="Selfie"></a>' +
                                '</div>';
                        } else {
                            selfieBlock = '<div class="la-detail-row mt-2"><strong>Selfie</strong> <span class="text-muted">—</span></div>';
                        }
                    } else {
                        selfieBlock = '<div class="la-detail-row mt-2"><strong>Selfie</strong> <span class="text-muted">Vendor flow (GPS only)</span></div>';
                    }
                    return (
                        '<div class="la-day-card">' +
                        '<h6>' + esc(day.date_label) + ' ' + statusBadge + '</h6>' +
                        '<div class="la-detail-row"><strong>Provider GPS time</strong> ' + esc(day.provider_location_at || '—') + '</div>' +
                        '<div class="la-detail-row"><strong>Provider location</strong> ' + esc(provCoords) + ' · ' + linkMaps('Map', day.provider_maps_url) + '</div>' +
                        selfieBlock +
                        '<div class="la-detail-row mt-2"><strong>Customer GPS time</strong> ' + esc(day.customer_location_at || '—') + '</div>' +
                        '<div class="la-detail-row"><strong>Customer location</strong> ' + esc(custCoords) + ' · ' + linkMaps('Map', day.customer_maps_url) + '</div>' +
                        '<div class="la-detail-row"><strong>Distance</strong> ' + esc(dist) + '</div>' +
                        '<div class="la-detail-row"><strong>Attendance marked</strong> ' + esc(day.attendance_marked_at || '—') + '</div>' +
                        '</div>'
                    );
                }).join('');
                body.innerHTML = head + blocks;
            }).catch(function (err) {
                body.innerHTML = '<div class="alert alert-danger">' + esc(err.message || 'Request failed.') + '</div>';
            });
        });
    });
})();
</script>
@endsection
