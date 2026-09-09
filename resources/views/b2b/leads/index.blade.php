@extends($portal_layout ?? 'b2b.layouts.app')
@section('page_title', 'Leads')

@php
    $portal_route_prefix = $portal_route_prefix ?? 'b2b';
    $portal_url_prefix = $portal_url_prefix ?? '/b2b';
@endphp

@section('content')
@php
    $isCorporatePortal = $isCorporatePortal ?? false;
    $isIndividualPortal = $isIndividualPortal ?? false;
    $isPartnerLeadPortal = $isCorporatePortal || $isIndividualPortal;
    $openLeadModalOnError = $errors->any();
    $openBulkTab = $errors->has('bulk_file');
@endphp

@include('b2b.leads.partials.lead-detail-modal')

{{-- Modal: single create + bulk upload --}}
<div class="modal fade" id="b2bCreateLeadModal" tabindex="-1" role="dialog" aria-labelledby="b2bCreateLeadModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable" role="document">
        <div class="modal-content b2b-lead-modal-content">
            <div class="modal-header b2b-lead-modal-header">
                <h5 class="modal-title font-weight-bold" id="b2bCreateLeadModalLabel">
                    <i class="fas fa-user-plus mr-2 text-warning"></i>Add leads
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body b2b-lead-modal-body">
                <ul class="nav nav-tabs b2b-lead-tabs" id="b2bLeadModalTabs" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" id="b2b-tab-create" data-toggle="tab" href="#b2b-pane-create" role="tab" aria-controls="b2b-pane-create" aria-selected="true">
                            <i class="fas fa-edit mr-1"></i>Create lead
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="b2b-tab-bulk" data-toggle="tab" href="#b2b-pane-bulk" role="tab" aria-controls="b2b-pane-bulk" aria-selected="false">
                            <i class="fas fa-file-upload mr-1"></i>Bulk upload leads
                        </a>
                    </li>
                </ul>
                <div class="tab-content pt-3" id="b2bLeadModalTabContent">
                    <div class="tab-pane fade show active" id="b2b-pane-create" role="tabpanel" aria-labelledby="b2b-tab-create">
                        <form method="POST" action="{{ route($portal_route_prefix . '.leads.store') }}" id="b2bFormCreateLead">
                            @csrf
                            @if($isCorporatePortal)
                                @include('b2b.leads.partials.corporate-lead-form')
                                <p class="text-muted small mb-3">Lead goes directly to operations and is assigned to an executive automatically.</p>
                                <button type="submit" class="btn btn-primary px-4 b2b-modal-submit">Submit to operations</button>
                            @elseif($isIndividualPortal)
                                @include('b2b.leads.partials.individual-lead-form')
                                <p class="text-muted small mb-3">Lead goes directly to sales and is assigned to an executive automatically.</p>
                                <button type="submit" class="btn btn-primary px-4 b2b-modal-submit">Submit to sales</button>
                            @else
                            <div class="form-group">
                                <label class="b2b-modal-label">Name</label>
                                <input type="text" name="name" class="form-control b2b-modal-input @error('name') is-invalid @enderror" value="{{ old('name') }}" placeholder="Enter client name" required>
                                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="form-group">
                                <label class="b2b-modal-label">Mobile Number</label>
                                <input type="text" name="mobile" class="form-control b2b-modal-input @error('mobile') is-invalid @enderror" value="{{ old('mobile') }}" maxlength="10" placeholder="Enter 10-digit number" required>
                                @error('mobile')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="form-group">
                                <label class="b2b-modal-label">Service Requirement</label>
                                <select name="service_requirement" class="form-control b2b-modal-input @error('service_requirement') is-invalid @enderror" required>
                                    <option value="">Select service</option>
                                    @foreach($serviceOptions as $opt)
                                        <option value="{{ $opt }}" {{ old('service_requirement') === $opt ? 'selected' : '' }}>{{ $opt }}</option>
                                    @endforeach
                                </select>
                                @error('service_requirement')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <button type="submit" class="btn btn-primary px-4 b2b-modal-submit">Create lead</button>
                            @endif
                        </form>
                    </div>
                    <div class="tab-pane fade" id="b2b-pane-bulk" role="tabpanel" aria-labelledby="b2b-tab-bulk">
                        @if($isCorporatePortal)
                        <p class="text-muted mb-2">Upload Excel (.xlsx / .xls) with columns: <code>number</code> (optional), <code>service</code>, <code>detail</code>, <code>bulk</code></p>
                        <a href="{{ route($portal_route_prefix . '.leads.template') }}" class="btn btn-outline-primary btn-sm mb-3 b2b-modal-outline-btn">Download Excel template</a>
                        @elseif($isIndividualPortal)
                        <p class="text-muted mb-2">Upload Excel (.xlsx / .xls) with columns: <code>number</code> (required), <code>service</code> (optional), <code>detail</code> (optional), <code>bulk</code></p>
                        <a href="{{ route($portal_route_prefix . '.leads.template') }}" class="btn btn-outline-primary btn-sm mb-3 b2b-modal-outline-btn">Download Excel template</a>
                        @else
                        <p class="text-muted mb-2">Upload CSV with headers: <code>name,mobile,service_requirement</code></p>
                        <a href="{{ route($portal_route_prefix . '.leads.template') }}" class="btn btn-outline-primary btn-sm mb-3 b2b-modal-outline-btn">Download format</a>
                        @endif
                        <form method="POST" action="{{ route($portal_route_prefix . '.leads.import') }}" enctype="multipart/form-data" id="b2bFormBulkLeads">
                            @csrf
                            <div class="form-group">
                                <label class="b2b-modal-label">{{ $isPartnerLeadPortal ? 'Excel / CSV file' : 'CSV file' }}</label>
                                <input type="file" name="bulk_file" class="form-control-file b2b-file-input @error('bulk_file') is-invalid d-block @enderror" accept="{{ $isPartnerLeadPortal ? '.xlsx,.xls,.csv,.txt' : '.csv,.txt' }}" required>
                                @error('bulk_file')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <button type="submit" class="btn btn-warning px-4 b2b-modal-upload-btn">Upload</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@if(session('status'))
<div class="alert alert-success alert-dismissible fade show">
    {{ session('status') }}
    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
</div>
@endif
@if(session('error'))
<div class="alert alert-danger alert-dismissible fade show">
    {{ session('error') }}
    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
</div>
@endif
@if(session('import_errors') && count(session('import_errors')))
<div class="alert alert-warning">
    <strong>Import notes:</strong>
    <ul class="mb-0 pl-3">@foreach(session('import_errors') as $err)<li>{{ $err }}</li>@endforeach</ul>
</div>
@endif

<div class="card b2b-leads-card">
    <div class="card-header b2b-leads-header d-flex align-items-center justify-content-between flex-wrap">
        <h3 class="card-title mb-0"><i class="fas fa-list-ul mr-2 text-warning"></i>Leads</h3>
        <div class="d-flex align-items-center flex-wrap mt-2 mt-md-0">
            <span class="badge b2b-record-count mr-2 mb-1 mb-md-0">{{ count($tableRows) }} records</span>
            <button type="button" class="btn btn-primary btn-sm mb-1 mb-md-0" data-toggle="modal" data-target="#b2bCreateLeadModal">
                <i class="fas fa-plus mr-1"></i>Create lead
            </button>
        </div>
    </div>
    <div class="card-body p-3 pt-2">
        <div class="b2b-leads-toolbar mb-2">
            <div class="b2b-search-wrap">
                <i class="fas fa-search b2b-search-icon"></i>
                <input
                    type="text"
                    id="b2bLeadsSearch"
                    class="form-control b2b-search-input"
                    placeholder="Search by Lead ID, Client Name, Service, City, Price/Date, Status"
                    autocomplete="off"
                >
            </div>
            <small class="text-muted" id="b2bLeadsSearchMeta">Showing {{ count($tableRows) }} of {{ count($tableRows) }} records</small>
        </div>
        <div class="table-responsive b2b-leads-table-wrap">
        <table class="table b2b-leads-table mb-0">
            <thead>
                <tr>
                    <th>Lead No.</th>
                    <th>Client Name</th>
                    <th>Service</th>
                    <th>City</th>
                    <th>Price / Date</th>
                    <th>Status (Remark if inactive)</th>
                    <th>From Date - To Date (if closed)</th>
                    <th>Total Amount (Monthly)</th>
                    <th>Total Earnings (Monthly commission)</th>
                    <th class="text-center">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($tableRows as $row)
                    <tr class="b2b-lead-row">
                        <td><span class="lead-no-chip">{{ $row['lead_no'] }}</span></td>
                        <td class="font-weight-600">{{ $row['client_name'] }}</td>
                        <td>{{ $row['service'] }}</td>
                        <td>{{ $row['city'] }}</td>
                        <td>{{ $row['price_date'] }}</td>
                        <td>{{ $row['status_with_remark'] }}</td>
                        <td>{{ $row['from_to'] }}</td>
                        <td>{{ $row['total_amount_monthly'] }}</td>
                        <td>{{ $row['total_earnings_monthly'] }}</td>
                        <td class="text-center">
                            <button type="button" class="btn btn-sm btn-outline-primary b2b-view-lead-btn" data-lead-id="{{ $row['id'] }}" title="View full journey">
                                <i class="fas fa-eye"></i> View
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr id="b2bNoLeadsRow">
                        <td colspan="10" class="text-center text-muted b2b-empty-row">
                            <i class="fas fa-inbox mr-1"></i>No leads yet.
                        </td>
                    </tr>
                @endforelse
                <tr id="b2bNoSearchResultRow" style="display:none;">
                    <td colspan="10" class="text-center text-muted b2b-empty-row">
                        <i class="fas fa-search mr-1"></i>No leads match your search.
                    </td>
                </tr>
            </tbody>
        </table>
        </div>
    </div>
</div>

<style>
    .b2b-lead-modal-content {
        border: 1px solid #e5e7eb;
        border-radius: 14px;
        overflow: hidden;
    }
    .b2b-lead-modal-header {
        background: linear-gradient(180deg, #fff 0%, var(--b2b-modal-header-end, #fff8f1) 100%);
        border-bottom: 1px solid #eef2f7;
        padding: 0.85rem 1rem;
    }
    .b2b-lead-modal-body {
        padding: 1rem 1rem 0.9rem;
    }
    .b2b-lead-tabs {
        border-bottom: 1px solid #e7edf4;
        gap: 0.35rem;
    }
    .b2b-lead-tabs .nav-link {
        border: 1px solid transparent;
        border-radius: 9px 9px 0 0;
        color: #6b7280;
        font-weight: 600;
        font-size: 0.9rem;
        padding: 0.5rem 0.8rem;
    }
    .b2b-lead-tabs .nav-link.active {
        color: var(--b2b-tab-active-color, #c25f14);
        background: var(--b2b-soft, #fff3e8);
        border-color: var(--b2b-soft-border, #ffd8b7) var(--b2b-soft-border, #ffd8b7) #fff;
    }
    .b2b-modal-label {
        font-size: 0.82rem;
        font-weight: 700;
        color: #475569;
        margin-bottom: 0.35rem;
    }
    .b2b-modal-input {
        height: 40px;
        border-radius: 10px;
        border: 1px solid #dbe2ea;
        font-size: 0.9rem;
        color: #1f2937;
    }
    .b2b-modal-input:focus {
        border-color: var(--b2b-primary, #f07f28);
        box-shadow: 0 0 0 0.2rem var(--b2b-focus-ring, rgba(240, 127, 40, 0.15));
    }
    .b2b-modal-submit,
    .b2b-modal-upload-btn {
        border-radius: 9px;
        font-weight: 700;
        min-width: 130px;
    }
    .b2b-modal-outline-btn {
        border-radius: 8px;
        font-weight: 600;
    }
    .b2b-file-input {
        border: 1px dashed #cbd5e1;
        border-radius: 10px;
        padding: 0.6rem 0.75rem;
        background: #f8fafc;
    }
    .b2b-leads-card {
        border-radius: 14px;
        border: 1px solid #e5e7eb;
        box-shadow: 0 10px 24px rgba(15, 23, 42, 0.05);
    }
    .b2b-leads-header {
        background: linear-gradient(180deg, #fff 0%, var(--b2b-modal-header-end, #fffaf5) 100%);
        border-bottom: 1px solid #eef2f7;
        padding: 0.85rem 1rem;
    }
    .b2b-record-count {
        background: var(--b2b-chip-bg, #fff3e8);
        border: 1px solid var(--b2b-soft-border, #ffd8b7);
        color: var(--b2b-chip-color, #c25f14);
        font-weight: 700;
        font-size: 0.76rem;
        padding: 0.38rem 0.55rem;
        border-radius: 999px;
    }
    .b2b-leads-table-wrap {
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        background: #fff;
    }
    .b2b-leads-toolbar {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 0.6rem;
    }
    .b2b-search-wrap {
        position: relative;
        width: 100%;
        max-width: 430px;
    }
    .b2b-search-icon {
        position: absolute;
        left: 12px;
        top: 50%;
        transform: translateY(-50%);
        color: #94a3b8;
        font-size: 0.82rem;
        pointer-events: none;
    }
    .b2b-search-input {
        border-radius: 10px;
        border: 1px solid #dbe2ea;
        padding-left: 2rem;
        font-size: 0.9rem;
        height: 38px;
    }
    .b2b-search-input:focus {
        border-color: var(--b2b-primary, #f07f28);
        box-shadow: 0 0 0 0.2rem var(--b2b-focus-ring, rgba(240, 127, 40, 0.15));
    }
    .b2b-leads-table thead th {
        background: #f8fafc;
        color: #475569;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        font-size: 0.75rem;
        font-weight: 700;
        border-bottom: 1px solid #e2e8f0;
        padding: 0.7rem 0.75rem;
        white-space: nowrap;
    }
    .b2b-leads-table td {
        font-size: 0.88rem;
        color: #1f2937;
        border-top: 1px solid #f1f5f9;
        padding: 0.72rem 0.75rem;
        vertical-align: middle;
        white-space: nowrap;
    }
    .b2b-leads-table tbody tr:hover {
        background: var(--b2b-row-hover, #fffbf6);
    }
    .lead-no-chip {
        display: inline-block;
        background: #eef2ff;
        color: #4338ca;
        border-radius: 999px;
        padding: 0.22rem 0.55rem;
        font-size: 0.76rem;
        font-weight: 700;
    }
    .font-weight-600 {
        font-weight: 600;
    }
    .b2b-empty-row {
        padding: 1rem 0.75rem !important;
        background: #fff;
    }
    .b2b-view-lead-btn {
        border-radius: 8px;
        font-weight: 600;
        font-size: 0.78rem;
        white-space: nowrap;
    }
    .b2b-detail-modal {
        border-radius: 14px;
        overflow: hidden;
    }
    .b2b-detail-modal__head {
        background: linear-gradient(180deg, #fff 0%, var(--b2b-modal-header-end, #fff8f1) 100%);
        border-bottom: 1px solid #eef2f7;
    }
    .b2b-detail-modal__body {
        background: #f8fafc;
        padding: 1rem 1.1rem;
    }
    .b2b-detail-section {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 10px;
        margin-bottom: 0.85rem;
        overflow: hidden;
    }
    .b2b-detail-section__head {
        padding: 0.55rem 0.75rem;
        font-size: 0.8rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        color: #475569;
        background: #f8fafc;
        border-bottom: 1px solid #eef2f6;
    }
    .b2b-detail-section__head i {
        color: var(--b2b-primary, #f07f28);
        margin-right: 0.35rem;
    }
    .b2b-detail-section__body {
        padding: 0.65rem 0.75rem;
    }
    .b2b-kv-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 0.45rem 0.75rem;
    }
    .b2b-kv {
        font-size: 0.82rem;
    }
    .b2b-kv__k {
        display: block;
        color: #64748b;
        font-size: 0.68rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.03em;
    }
    .b2b-kv__v {
        color: #1e293b;
        word-break: break-word;
    }
    .b2b-timeline-milestone {
        position: relative;
        padding-left: 1.25rem;
        padding-bottom: 0.65rem;
        font-size: 0.82rem;
    }
    .b2b-timeline-milestone::before {
        content: '';
        position: absolute;
        left: 0.15rem;
        top: 0.35rem;
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: var(--b2b-primary, #f07f28);
        border: 2px solid var(--b2b-soft, #fff7ed);
    }
    .b2b-timeline-milestone:not(:last-child)::after {
        content: '';
        position: absolute;
        left: 0.45rem;
        top: 1rem;
        bottom: 0;
        width: 2px;
        background: #e2e8f0;
    }
    .b2b-timeline-milestone__at {
        font-size: 0.7rem;
        color: #64748b;
    }
    .b2b-timeline-milestone__title {
        font-weight: 700;
        color: #1e293b;
    }
    .b2b-empty-section {
        font-size: 0.85rem;
        color: #94a3b8;
        font-style: italic;
    }
    @media (max-width: 767px) {
        .b2b-search-wrap {
            max-width: 100%;
        }
        .b2b-kv-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

@include('includes.lead-status-remarks-assets', ['part' => 'css'])

@endsection

@push('scripts')
<script>
(function () {
    document.addEventListener('DOMContentLoaded', function () {
        const searchInput = document.getElementById('b2bLeadsSearch');
        const meta = document.getElementById('b2bLeadsSearchMeta');
        const rows = Array.from(document.querySelectorAll('.b2b-lead-row'));
        const noSearchRow = document.getElementById('b2bNoSearchResultRow');
        const total = rows.length;

        if (!searchInput || !meta || total === 0) return;

        function searchableText(row) {
            const cells = row.querySelectorAll('td');
            // Lead ID, Client Name, Service, City, Price/Date, Status
            const indexes = [0, 1, 2, 3, 4, 5];
            return indexes
                .map(function (idx) {
                    return (cells[idx] && cells[idx].innerText ? cells[idx].innerText : '').trim().toLowerCase();
                })
                .join(' ');
        }

        function applyFilter() {
            const q = (searchInput.value || '').trim().toLowerCase();
            let visible = 0;

            rows.forEach(function (row) {
                const show = q === '' || searchableText(row).includes(q);
                row.style.display = show ? '' : 'none';
                if (show) visible++;
            });

            if (noSearchRow) {
                noSearchRow.style.display = visible === 0 ? '' : 'none';
            }

            meta.textContent = 'Showing ' + visible + ' of ' + total + ' records';
        }

        searchInput.addEventListener('input', applyFilter);
    });
})();
</script>

<script>
jQuery(function ($) {
    var currentLeadId = null;
    var refreshTimer = null;
    var showUrlBase = @json(url(rtrim($portal_url_prefix, '/') . '/leads'));

    function escapeHtml(str) {
        if (str == null) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function kvGrid(pairs) {
        var html = '<div class="b2b-kv-grid">';
        pairs.forEach(function (p) {
            if (p[1] == null || p[1] === '') return;
            html += '<div class="b2b-kv"><span class="b2b-kv__k">' + escapeHtml(p[0]) + '</span>'
                + '<span class="b2b-kv__v">' + escapeHtml(p[1]) + '</span></div>';
        });
        return html + '</div>';
    }

    function section(title, icon, bodyHtml) {
        return '<div class="b2b-detail-section">'
            + '<div class="b2b-detail-section__head"><i class="fas ' + icon + '"></i>' + escapeHtml(title) + '</div>'
            + '<div class="b2b-detail-section__body">' + bodyHtml + '</div></div>';
    }

    function renderRemarks(remarks) {
        if (!remarks || !remarks.length) {
            return '<p class="b2b-empty-section mb-0">No status remarks from sales yet.</p>';
        }
        var html = '<div class="clsr-panel"><div class="clsr-panel__body clsr-panel__body--scroll" style="max-height:220px;padding:0.5rem;">'
            + '<div class="clsr-timeline">';
        remarks.forEach(function (r) {
            var name = r.created_by_name || 'Unknown';
            var parts = name.trim().split(/\s+/);
            var initials = ((parts[0] || 'U').charAt(0) + (parts[1] || '').charAt(0)).toUpperCase();
            html += '<div class="clsr-timeline-item clsr-timeline-item--compact">'
                + '<div class="clsr-timeline-marker"></div>'
                + '<article class="clsr-remark-card clsr-remark-card--compact">'
                + '<header class="clsr-remark-card__head"><div class="clsr-remark-card__who">'
                + '<span class="clsr-avatar">' + escapeHtml(initials) + '</span>'
                + '<div class="clsr-who-text"><span class="clsr-name">' + escapeHtml(name) + '</span>'
                + '<time class="clsr-date">' + escapeHtml(r.created_at) + '</time></div></div>';
            if (r.status_at_remark) {
                html += '<span class="clsr-status-chip">' + escapeHtml(r.status_at_remark) + '</span>';
            }
            html += '</header><div class="clsr-remark-card__body">';
            if (r.is_ai_polished && r.original_remark) {
                html += '<div class="clsr-remark-blocks">'
                    + '<div class="clsr-remark-block clsr-remark-block--english"><span class="clsr-remark-block__label"><i class="fas fa-language"></i> English</span>'
                    + '<p class="clsr-remark-text">' + escapeHtml(r.remark) + '</p></div>'
                    + '<div class="clsr-remark-block clsr-remark-block--draft"><span class="clsr-remark-block__label"><i class="fas fa-pen-fancy"></i> Draft</span>'
                    + '<p class="clsr-remark-text clsr-remark-text--draft">' + escapeHtml(r.original_remark) + '</p></div></div>';
            } else {
                html += '<p class="clsr-remark-text">' + escapeHtml(r.remark) + '</p>';
            }
            html += '</div></article></div>';
        });
        return html + '</div></div></div>';
    }

    function renderTimeline(events) {
        if (!events || !events.length) {
            return '<p class="b2b-empty-section mb-0">No activity yet.</p>';
        }
        var html = '';
        events.forEach(function (e) {
            html += '<div class="b2b-timeline-milestone">'
                + '<div class="b2b-timeline-milestone__at">' + escapeHtml(e.at) + '</div>'
                + '<div class="b2b-timeline-milestone__title">' + escapeHtml(e.title) + '</div>'
                + '<div class="text-muted">' + escapeHtml(e.detail) + '</div></div>';
        });
        return html;
    }

    function renderDetail(d) {
        var html = '';
        var sub = d.submitted || {};
        var subRows = [
            ['Name', sub.name],
            ['Number', sub.mobile],
            ['Service', sub.service_requirement],
            ['Source', sub.source],
            ['Submitted', sub.submitted_at],
        ];
        if (sub.detail) subRows.splice(3, 0, ['Detail (Query Remark)', sub.detail]);
        if (sub.bulk_qty != null) subRows.push(['Bulk qty', sub.bulk_qty]);
        html += section('Your submission (B2B)', 'fa-upload', kvGrid(subRows));

        if (d.sales) {
            var s = d.sales;
            html += section('Sales team — current lead', 'fa-user-tie', kvGrid([
                ['Lead no.', s.lead_no],
                ['Customer', s.customer_name],
                ['Contact', s.contact_no],
                ['City', s.location],
                ['Query', s.query],
                ['Query remarks', s.query_remarks],
                ['Status', s.status],
                ['Stage', s.stage],
                ['Executive', s.executive_name],
                ['Lead source', s.lead_source],
                ['Follow-up', s.follow_up_date],
                ['Future prospect', s.future_prospect_date],
                ['Prospect rate', s.prospect_rate],
                ['Shift', s.shift_type],
                ['Inactive remark', s.inactive_stage_remark],
                ['Last call', s.last_call_status],
                ['Sales created', s.created_at],
                ['Last updated', s.updated_at],
            ]));
            html += section('Sales status remarks (all updates)', 'fa-comments', renderRemarks(d.status_remarks));
        } else {
            html += section('Sales team', 'fa-user-tie', '<p class="b2b-empty-section mb-0">Not yet linked to sales CRM.</p>');
        }

        if (d.operation) {
            var o = d.operation;
            html += section('Operation team (forwarded)', 'fa-cogs', kvGrid([
                ['Forwarded on', o.forwarded_at],
                ['Operation ID', o.operation_lead_id],
                ['Customer', o.customer_name],
                ['Contact', o.contact_no],
                ['City', o.location],
                ['Query', o.query],
                ['Query remark', o.query_remark],
                ['Status', o.status],
                ['Executive', o.executive_name],
                ['Status remark', o.status_remark],
                ['Price issue', o.price_issue_remark],
                ['Inactive', o.inactive_remark],
                ['Closed remark', o.closed_remark],
                ['Follow-up', o.follow_up_date],
                ['Future prospect', o.future_prospect_date],
                ['Closed rate', o.closed_rate],
                ['Last updated', o.updated_at],
            ]));
        } else if (d.sales && (d.sales.status || '').toLowerCase() === 'prospect' && (d.sales.stage || '').toLowerCase() === 'closed') {
            html += section('Operation team', 'fa-cogs', '<p class="b2b-empty-section mb-0">Eligible for operation — forwarding may be in progress.</p>');
        } else {
            html += section('Operation team', 'fa-cogs', '<p class="b2b-empty-section mb-0">Not forwarded to operation yet.</p>');
        }

        if (d.deployments && d.deployments.length) {
            var depHtml = '<div class="table-responsive"><table class="table table-sm table-bordered mb-0"><thead><tr>'
                + '<th>Vendor/Staff</th><th>Status</th><th>From</th><th>To</th><th>Amount</th><th>Added</th></tr></thead><tbody>';
            d.deployments.forEach(function (dep) {
                depHtml += '<tr><td>' + escapeHtml(dep.vendor) + '</td><td>' + escapeHtml(dep.deployment_status)
                    + '</td><td>' + escapeHtml(dep.from_date) + '</td><td>' + escapeHtml(dep.to_date)
                    + '</td><td>' + escapeHtml(dep.vendor_payment) + '</td><td>' + escapeHtml(dep.created_at) + '</td></tr>';
            });
            html += section('Deployments', 'fa-truck', depHtml + '</tbody></table></div>');
        }

        if (d.payments && d.payments.length) {
            var payHtml = '<ul class="mb-0 pl-3">';
            d.payments.forEach(function (p) {
                payHtml += '<li>₹' + escapeHtml(p.amount) + ' on ' + escapeHtml(p.received_date)
                    + (p.remark ? (' — ' + escapeHtml(p.remark)) : '') + '</li>';
            });
            html += section('Recent payments', 'fa-rupee-sign', payHtml + '</ul>');
        }

        html += section('Activity timeline', 'fa-history', renderTimeline(d.timeline));

        return html;
    }

    function loadLeadDetail(leadId, silent) {
        var $body = $('#b2bLeadDetailBody');
        if (!silent) {
            $body.html('<div class="text-center py-5 text-muted"><i class="fas fa-spinner fa-spin fa-2x mb-2"></i><p class="mb-0">Loading…</p></div>');
        }
        $.getJSON(showUrlBase + '/' + leadId)
            .done(function (data) {
                $('#b2bLeadDetailUpdated').text('Last refreshed: ' + (data.updated_at || '—'));
                var title = (data.sales && data.sales.lead_no) ? data.sales.lead_no : ('B2B #' + leadId);
                $('#b2bLeadDetailModalLabel').html('<i class="fas fa-route mr-2 text-warning"></i>Lead journey — ' + escapeHtml(title));
                $body.html(renderDetail(data));
            })
            .fail(function (xhr) {
                var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Could not load lead details.';
                $body.html('<div class="alert alert-danger mb-0">' + escapeHtml(msg) + '</div>');
            });
    }

    function stopRefresh() {
        if (refreshTimer) {
            clearInterval(refreshTimer);
            refreshTimer = null;
        }
    }

    function startRefresh() {
        stopRefresh();
        refreshTimer = setInterval(function () {
            if (currentLeadId && $('#b2bLeadDetailModal').hasClass('show')) {
                loadLeadDetail(currentLeadId, true);
            }
        }, 45000);
    }

    $(document).on('click', '.b2b-view-lead-btn', function () {
        currentLeadId = $(this).data('lead-id');
        if (!currentLeadId) return;
        loadLeadDetail(currentLeadId, false);
        $('#b2bLeadDetailModal').modal('show');
        startRefresh();
    });

    $('#b2bLeadDetailRefresh').on('click', function () {
        if (currentLeadId) {
            loadLeadDetail(currentLeadId, false);
        }
    });

    $('#b2bLeadDetailModal').on('hidden.bs.modal', function () {
        stopRefresh();
        currentLeadId = null;
    });
});
</script>

@if($openLeadModalOnError)
<script>
jQuery(function ($) {
    $('#b2bCreateLeadModal').modal('show');
    @if($openBulkTab)
    $('#b2b-tab-bulk').tab('show');
    @endif
});
</script>
@endif
@endpush
