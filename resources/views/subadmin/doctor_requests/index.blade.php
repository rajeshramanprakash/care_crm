@extends('admin.layouts.app')
@section('title', 'Doctor requests')

@section('header-css')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.1/css/jquery.dataTables.min.css">
<style>
    /* Admin layout sets body { overflow: hidden }; scroll this page vertically */
    .content-wrapper.doctor-requests-page {
        height: calc(100vh - 120px);
        max-height: calc(100vh - 120px);
        overflow-y: auto !important;
        overflow-x: hidden !important;
        -webkit-overflow-scrolling: touch;
        padding-bottom: 1.5rem;
    }
    @media (max-width: 991.98px) {
        .content-wrapper.doctor-requests-page {
            height: calc(100vh - 88px);
            max-height: calc(100vh - 88px);
        }
    }
    .doctor-requests-page .doctor-requests-card {
        border: 1px solid #eef0f2;
        border-radius: 16px;
        box-shadow: 0 8px 32px rgba(15, 23, 42, 0.06);
        overflow: hidden;
        background: #fff;
    }
    /* Main DataTable card: avoid clipping horizontal scrollbars / dropdowns */
    .doctor-requests-page .dr-requests-main-card.doctor-requests-card {
        overflow: visible;
    }
    .doctor-requests-page .doctor-requests-card .card-header {
        background: linear-gradient(180deg, #fafbfc 0%, #fff 100%);
        border-bottom: 1px solid #eef0f2;
        padding: 1rem 1.35rem;
        font-weight: 600;
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 0.75rem 1rem;
    }
    .doctor-requests-page .dr-requests-main-card .card-header.dr-requests-toolbar {
        display: flex !important;
        flex-wrap: nowrap !important;
        align-items: center !important;
        gap: 0.5rem;
    }
    .doctor-requests-page .dr-requests-toolbar .dr-main-card-title {
        min-width: 0;
        flex: 1 1 auto;
        max-width: min(42%, 158px);
        margin-right: 0.2rem;
        font-size: 0.9rem;
        line-height: 1.25;
        padding: 0.1rem 0.35rem 0.1rem 0.1rem;
    }
    .doctor-requests-page .dr-requests-toolbar-right {
        flex-wrap: nowrap !important;
        align-items: center;
        flex-shrink: 0;
        white-space: nowrap;
    }
    .doctor-requests-page #filterStatus.dr-filter-status-select {
        width: auto;
        min-width: 5rem;
        max-width: 128px;
        flex: 0 0 auto;
    }
    @media (max-width: 575.98px) {
        .doctor-requests-page .dr-requests-main-card .card-header.dr-requests-toolbar {
            overflow-x: auto;
            flex-wrap: nowrap !important;
            -webkit-overflow-scrolling: touch;
        }
    }
    .doctor-requests-page .doctor-requests-card .card-header label {
        font-size: 0.8rem;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        color: #6c757d;
        font-weight: 600;
    }
    .doctor-requests-page .doctor-requests-card .card-header .form-control-sm {
        border-color: #dee2e6;
        font-weight: 500;
    }
    .doctor-requests-page .doctor-requests-card .card-body {
        padding: 1rem 1.1rem 1.2rem;
    }
    .doctor-requests-page .dr-requests-main-card .card-body {
        padding-top: 0.85rem;
    }
    .doctor-requests-page .dr-main-datatable-body .table-responsive {
        max-height: calc(100vh - 290px);
        overflow-y: auto;
    }
    .doctor-requests-page .dr-main-datatable-body .table-responsive::-webkit-scrollbar {
        height: 8px;
        width: 8px;
    }
    .doctor-requests-page .dr-main-datatable-body .table-responsive::-webkit-scrollbar-thumb {
        background: #c5cdd8;
        border-radius: 4px;
    }
    .doctor-requests-page .dr-main-datatable-body .table-responsive::-webkit-scrollbar-track {
        background: #f1f5f9;
        border-radius: 4px;
    }
    .doctor-requests-page #doctor-requests-table_wrapper .dataTables_length,
    .doctor-requests-page #doctor-requests-table_wrapper .dataTables_filter {
        margin-bottom: 0.75rem;
    }
    .doctor-requests-page #doctor-requests-table_wrapper > .row:first-child {
        align-items: center;
        margin-bottom: 0.25rem;
    }
    .doctor-requests-page #doctor-requests-table_wrapper .dataTables_scroll {
        clear: both;
        border: 1px solid #eef0f2;
        border-radius: 12px;
        overflow-x: hidden;
        overflow-y: visible;
        background: #fafbfc;
        margin-bottom: 0.5rem;
    }
    .doctor-requests-page #doctor-requests-table_wrapper .dataTables_scrollHead {
        background: #fafbfc;
    }
    .doctor-requests-page #doctor-requests-table_wrapper .dataTables_scrollBody {
        background: #fff;
        overflow-x: auto !important;
        overflow-y: auto !important;
        max-height: calc(100vh - 280px);
        -webkit-overflow-scrolling: touch;
    }
    .doctor-requests-page #doctor-requests-table_wrapper .dataTables_scrollBody::-webkit-scrollbar,
    .doctor-requests-page .dr-referral-table-wrap::-webkit-scrollbar {
        height: 10px;
        width: 10px;
    }
    .doctor-requests-page #doctor-requests-table_wrapper .dataTables_scrollBody::-webkit-scrollbar-thumb,
    .doctor-requests-page .dr-referral-table-wrap::-webkit-scrollbar-thumb {
        background: #c5cdd8;
        border-radius: 6px;
    }
    .doctor-requests-page #doctor-requests-table_wrapper .dataTables_scrollBody::-webkit-scrollbar-track,
    .doctor-requests-page .dr-referral-table-wrap::-webkit-scrollbar-track {
        background: #f1f5f9;
        border-radius: 6px;
    }
    .doctor-requests-page #doctor-requests-table_wrapper .dataTables_filter input {
        border-radius: 999px;
        border: 1px solid #dee2e6;
        padding: 0.4rem 1rem;
        min-width: 220px;
        margin-left: 0.5rem;
    }
    .doctor-requests-page #doctor-requests-table_wrapper .dataTables_length select {
        border-radius: 8px;
        border: 1px solid #dee2e6;
        padding: 0.25rem 2rem 0.25rem 0.5rem;
    }
    .doctor-requests-page table.dataTable.doctor-requests-dt thead th {
        font-size: 0.68rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        font-weight: 600;
        color: #6c757d;
        border-bottom: 2px solid #eef0f2 !important;
        padding: 0.62rem 0.55rem !important;
        vertical-align: middle;
        background: #fafbfc;
        white-space: nowrap;
        position: sticky;
        top: 0;
        z-index: 10;
    }
    .doctor-requests-page table.dataTable.doctor-requests-dt tbody td {
        padding: 0.01rem 0.55rem !important;
        vertical-align: middle;
        font-size: 0.875rem;
        color: #2c3e50;
        border-bottom: 1px solid #f0f2f4 !important;
    }
    .doctor-requests-page table.dataTable.doctor-requests-dt tbody td.dr-actions-cell {
        white-space: nowrap;
        width: 1%;
        padding-left: 0.5rem !important;
    }
    .doctor-requests-page table.dataTable.doctor-requests-dt tbody tr:hover {
        background-color: #f8fafb !important;
    }
    .doctor-requests-page table.dataTable.doctor-requests-dt .dr-lead {
        font-family: ui-monospace, monospace;
        font-size: 0.85rem;
        color: #495057;
    }
    .doctor-requests-page table.dataTable.doctor-requests-dt .dr-name {
        font-weight: 600;
        color: #1a1a1a;
    }
    .doctor-requests-page table.dataTable.doctor-requests-dt .dr-mobile {
        font-variant-numeric: tabular-nums;
        color: #495057;
    }
    .doctor-requests-page table.dataTable.doctor-requests-dt .dr-service {
        color: #495057;
        max-width: 140px;
        display: inline-block;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        vertical-align: middle;
    }
    .doctor-requests-page table.dataTable.doctor-requests-dt .dr-date {
        font-size: 0.88rem;
        color: #6c757d;
        white-space: nowrap;
    }
    .doctor-requests-page .doctor-req-actions {
        gap: 5px;
    }
    .doctor-requests-page .doctor-req-icon-btn {
        width: 2.25rem;
        height: 2.25rem;
        padding: 0 !important;
        display: inline-flex !important;
        align-items: center;
        justify-content: center;
        border-radius: 10px !important;
        font-size: 0.85rem;
        line-height: 1;
        box-shadow: none;
        flex-shrink: 0;
    }
    .doctor-requests-page .doctor-req-icon-btn i {
        margin: 0 !important;
        opacity: 0.92;
    }
    .doctor-requests-page .doctor-req-icon-btn:hover {
        transform: translateY(-1px);
        transition: transform 0.12s ease, box-shadow 0.12s ease;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
    }
    .doctor-requests-page .doctor-req-dd-btn {
        width: auto;
        min-width: 2.95rem;
        height: 2.25rem;
        padding: 0 0.45rem !important;
        border-radius: 10px !important;
        display: inline-flex !important;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        box-shadow: none;
    }
    .doctor-requests-page .doctor-req-dd-inner {
        display: inline-flex;
        align-items: center;
        gap: 5px;
    }
    .doctor-requests-page .doctor-req-dd-chevron {
        font-size: 0.55rem;
        opacity: 0.72;
    }
    .doctor-requests-page .doctor-req-dd-toggle::after {
        display: none;
    }
    .doctor-requests-page .doctor-req-decision-dd .dropdown-item:disabled {
        opacity: 0.55;
        cursor: not-allowed;
        pointer-events: none;
    }
    .doctor-requests-page .doctor-req-decision-dd .dropdown-menu {
        z-index: 1055;
        min-width: 12rem;
        border-radius: 12px;
        padding: 0.35rem;
        box-shadow: 0 12px 40px rgba(15, 23, 42, 0.12) !important;
    }
    .doctor-requests-page .doctor-req-decision-dd .dropdown-item {
        font-size: 0.875rem;
        cursor: pointer;
        border-radius: 8px;
        padding: 0.45rem 0.65rem;
    }
    .doctor-requests-page .doctor-req-decision-dd .dropdown-item:active {
        color: inherit;
    }
    .doctor-requests-page #doctor-requests-table_wrapper .dataTables_info {
        padding-top: 1rem;
        font-size: 0.9rem;
        color: #6c757d;
    }
    .doctor-requests-page #doctor-requests-table_wrapper .dataTables_paginate {
        padding-top: 1rem;
    }
    .doctor-requests-page #doctor-requests-table_wrapper .dataTables_paginate .paginate_button {
        border-radius: 8px !important;
        margin: 0 2px;
        border: 1px solid #e9ecef !important;
    }
    .doctor-requests-page #doctor-requests-table_wrapper .dataTables_paginate .paginate_button.current {
        background: #ea8a2b !important;
        color: #fff !important;
        border-color: #ea8a2b !important;
    }
    .doctor-requests-page .dr-status-badge {
        font-weight: 600;
        font-size: 0.72rem;
        padding: 0.42em 0.9em;
        letter-spacing: 0.03em;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04);
    }
    .doctor-requests-page .content-header h1 {
        font-weight: 700;
        letter-spacing: -0.02em;
        color: #1a1a1a;
    }
    .doctor-requests-page .content-header .text-muted {
        font-size: 0.95rem;
        max-width: 42rem;
    }
    .doctor-requests-page #doctor-requests-table_wrapper .dataTables_length label,
    .doctor-requests-page #doctor-requests-table_wrapper .dataTables_filter label {
        font-size: 0.875rem;
        color: #6c757d;
    }
    .doctor-requests-page .dr-two-col > [class*="col-"] > .dr-referral-side-card {
        height: 100%;
    }
    .doctor-requests-page .dr-two-col .dr-referral-side-card {
        display: flex;
        flex-direction: column;
        min-height: 280px;
    }
    .doctor-requests-page .dr-two-col .dr-referral-side-card .card-body {
        flex: 1;
        min-height: 0;
    }
    .doctor-requests-page .dr-referral-body-inner {
        display: flex;
        flex-direction: column;
        min-height: 0;
        flex: 1 1 auto;
    }
    .doctor-requests-page .dr-referral-table-wrap {
        flex: 1;
        min-height: 240px;
        max-height: min(65vh, 620px);
        overflow-x: auto;
        overflow-y: auto;
        -webkit-overflow-scrolling: touch;
        border: 1px solid #e8ecf1;
        border-radius: 12px;
        background: #fff;
        scrollbar-gutter: stable;
    }
    .doctor-requests-page .dr-referral-side-card .table {
        margin-bottom: 0;
    }
    .doctor-requests-page .dr-referral-side-card .table thead th {
        font-size: 0.68rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: #6c757d;
        font-weight: 600;
        border-bottom: 2px solid #eef0f2;
        background: #f8fafc;
        white-space: nowrap;
        padding: 0.55rem 0.65rem !important;
        position: sticky;
        top: 0;
        z-index: 4;
        box-shadow: 0 1px 0 rgba(15, 23, 42, 0.06);
    }
    .doctor-requests-page .dr-referral-side-card .table tbody td {
        font-size: 0.84rem;
        vertical-align: middle;
        border-bottom: 1px solid #f0f2f4;
        padding: 0.5rem 0.65rem !important;
    }
    .doctor-requests-page .dr-referral-chip {
        font-size: 0.7rem;
        font-weight: 600;
        background: #ecfdf5;
        color: #047857;
        border-radius: 999px;
        padding: 0.15rem 0.5rem;
        margin-left: 0.35rem;
    }
    .doctor-requests-page .dr-main-card-title {
        color: #1d4ed8;
        letter-spacing: -0.01em;
        font-size: 1.05rem;
        padding: 0.2rem 0.55rem 0.2rem 0.15rem;
        border-radius: 8px;
        background: linear-gradient(135deg, #eff6ff 0%, #f8fafc 100%);
        border: 1px solid #dbeafe;
    }
    .doctor-requests-page .dr-main-datatable-body {
        min-height: 0;
    }
    .doctor-requests-page .dr-two-col .dr-referral-side-card .card-body.d-flex {
        flex: 1;
        min-height: 340px;
    }
    .doctor-requests-page tr.dr-row-price-pending {
        animation: drPriceRowPulse 1.6s ease-in-out infinite;
        background: rgba(245, 158, 11, 0.08) !important;
    }
    .doctor-requests-page tr.dr-row-price-pending .dr-name {
        font-weight: 700;
        color: #b45309;
    }
    @keyframes drPriceRowPulse {
        0%, 100% { box-shadow: inset 0 0 0 0 rgba(245, 158, 11, 0); }
        50% { box-shadow: inset 4px 0 0 0 rgba(245, 158, 11, 0.85); }
    }
    .doctor-req-price-alert-btn {
        animation: drPriceBtnPulse 1.4s ease-in-out infinite;
    }
    .doctor-req-price-count {
        font-size: 0.65rem;
        font-weight: 800;
        margin-left: 2px;
    }
    @keyframes drPriceBtnPulse {
        0%, 100% { transform: scale(1); }
        50% { transform: scale(1.06); }
    }
    .dr-price-change-alert {
        border-left: 4px solid #f59e0b;
        background: #fffbeb;
    }
</style>
@endsection

@section('content')
<div class="content-wrapper doctor-requests-page">
    <section class="content-header">
        <div class="container-fluid">
            <h1>Doctor requests</h1>
            <p class="text-muted">New doctor registrations. Approve to allow portal login.</p>
        </div>
    </section>
    <section class="content">
        <div class="container-fluid">
            @php
                $drReferralList = $doctorReferralUsers ?? collect();
                $pendingPriceChangeCount = (int) ($pendingPriceChangeCount ?? 0);
            @endphp
            @if($pendingPriceChangeCount > 0)
                <div class="alert dr-price-change-alert d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                    <div>
                        <strong><i class="fas fa-rupee-sign mr-1"></i>{{ $pendingPriceChangeCount }} doctor price change request{{ $pendingPriceChangeCount === 1 ? '' : 's' }} pending</strong>
                        <span class="d-block small text-muted mb-0">Doctors with pending requests are highlighted in the list. Click the ₹ button to review.</span>
                    </div>
                </div>
            @endif
            <div class="row dr-two-col align-items-start">
                {{-- Left: Doctor requests (main DataTable) --}}
                <div class="col-xl-7 col-lg-12 mb-3 mb-xl-0">
                    <div class="card doctor-requests-card dr-requests-main-card">
                        <div class="card-header dr-requests-toolbar align-items-center flex-nowrap overflow-hidden">
                            <span class="dr-main-card-title font-weight-bold flex-shrink-1 text-truncate">Doctor requests</span>
                            <div class="dr-requests-toolbar-right d-inline-flex align-items-center flex-shrink-0">
                                <label class="mb-0 text-uppercase small font-weight-bold text-secondary mr-2" for="filterStatus" style="letter-spacing:.04em;">Status</label>
                                <select id="filterStatus" class="form-control form-control-sm rounded-pill border mr-2 dr-filter-status-select">
                                    <option value="">All</option>
                                    <option value="pending">Pending</option>
                                    <option value="approved">Approved</option>
                                    <option value="rejected">Rejected</option>
                                </select>
                                <button type="button" class="btn btn-sm btn-outline-warning text-nowrap flex-shrink-0 mr-2" data-bs-toggle="modal" data-bs-target="#setPortalLeadCommissionModal">
                                    <i class="fas fa-percent mr-1"></i>Set doctor referral charges
                                </button>
                                <button type="button" id="regenerateOnlineMeetingsBtn" class="btn btn-sm btn-outline-primary text-nowrap flex-shrink-0">
                                    <i class="fas fa-sync-alt mr-1"></i>Generate links
                                </button>
                            </div>
                        </div>
                        <div class="card-body dr-main-datatable-body">
                            <div class="table-responsive">
                                <table id="doctor-requests-table" class="table table-hover align-middle doctor-requests-dt text-nowrap" style="width:100%;">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Lead</th>
                                            <th>Name</th>
                                            <th>Mobile</th>
                                            <th>Service</th>
                                            <th>Status</th>
                                            <th>Agreement Number</th>
                                            <th>Agreement Status</th>
                                            <th>Registered</th>
                                            <th class="text-end">Actions</th>
                                        </tr>
                                    </thead>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Right: Doctor referral users --}}
                <div class="col-xl-5 col-lg-12">
                    <div class="card doctor-requests-card dr-referral-side-card">
                        <div class="card-header d-flex flex-wrap align-items-center gap-2">
                            <span class="font-weight-bold text-dark">
                                Doctor referral users
                                <span class="dr-referral-chip">{{ $drReferralList->count() }}</span>
                            </span>
                            <button type="button" class="btn btn-sm btn-outline-success ml-auto" data-bs-toggle="modal" data-bs-target="#createDoctorReferralUserModal">
                                <i class="fas fa-user-md mr-1"></i> Create referral
                            </button>
                        </div>
                        <div class="card-body d-flex flex-column">
                            <p class="small text-muted mb-2 flex-shrink-0">OTP login: same home page as other portals.</p>
                            @if($drReferralList->isEmpty())
                                <p class="text-muted mb-0 flex-shrink-0">No referral users yet. Use <strong>Create referral</strong>.</p>
                            @else
                                <div class="dr-referral-body-inner">
                                <div class="dr-referral-table-wrap">
                                    <table class="table table-hover mb-0 align-middle">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Name</th>
                                                <th>Mobile</th>
                                                <th>Doctors</th>
                                                <th class="text-right">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($drReferralList as $drv)
                                                <tr>
                                                    <td class="font-weight-bold text-muted">{{ $drv->id }}</td>
                                                    <td>{{ $drv->name }}</td>
                                                    <td class="text-nowrap">{{ $drv->mobile }}</td>
                                                    <td>{{ $drv->doctor_requests_count ?? 0 }}</td>
                                                    <td class="text-right text-nowrap">
                                                        <form method="POST" action="{{ route('subadmin.doctor_referral_users.destroy', $drv) }}" class="d-inline" onsubmit="return confirm('Remove this referral user? Doctors linked to them will be unassigned.');">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="btn btn-sm btn-outline-danger py-1">Delete</button>
                                                        </form>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<div class="modal fade" id="createDoctorReferralUserModal" tabindex="-1" aria-labelledby="createDoctorReferralUserModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createDoctorReferralUserModalLabel">Create doctor referral user</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="{{ route('subadmin.doctor_referral_users.store') }}">
                @csrf
                <div class="modal-body">
                    <p class="small text-muted">They log in via OTP using this mobile number. Commission is set per doctor and consultation mode in doctor registration details.</p>
                    <div class="form-group">
                        <label>Name</label>
                        <input type="text" name="name" class="form-control" required maxlength="255" placeholder="Referrer full name">
                    </div>
                    <div class="form-group">
                        <label>Mobile (10 digits)</label>
                        <input type="text" name="mobile" class="form-control" required maxlength="10" pattern="[0-9]{10}" inputmode="numeric" placeholder="9876543210">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

@php $portalCommissionDoctors = $portalCommissionDoctors ?? collect(); @endphp
<div class="modal fade" id="setPortalLeadCommissionModal" tabindex="-1" aria-labelledby="setPortalLeadCommissionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="setPortalLeadCommissionModalLabel">
                    <i class="fas fa-percent text-warning mr-2"></i>Set doctor referral charges
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="{{ route('subadmin.doctor_requests.portal_lead_commission') }}" id="portalLeadCommissionForm">
                @csrf
                <div class="modal-body">
                    <p class="small text-muted mb-3">
                        Commission for leads submitted from <strong>Doctor Portal → Refer Lead</strong>.
                        Default is <strong>10%</strong> per lead. Select doctors and set a percentage (can differ per doctor by saving again).
                    </p>
                    <div class="form-group">
                        <label class="font-weight-bold">Commission % <span class="text-danger">*</span></label>
                        <input type="number" name="commission_percent" class="form-control" min="0" max="100" step="0.01" value="10" required placeholder="e.g. 10">
                        <small class="text-muted">Applied to all selected doctors below.</small>
                    </div>
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <label class="font-weight-bold mb-0">Doctors</label>
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" id="portalLeadCommissionSelectAll">
                            <label class="custom-control-label" for="portalLeadCommissionSelectAll">Select all</label>
                        </div>
                    </div>
                    @if($portalCommissionDoctors->isEmpty())
                        <p class="text-muted mb-0">No approved doctors found.</p>
                    @else
                        <div class="border rounded" style="max-height: 320px; overflow-y: auto;">
                            <table class="table table-sm table-hover mb-0">
                                <thead class="thead-light" style="position: sticky; top: 0; z-index: 1;">
                                    <tr>
                                        <th style="width:42px;"></th>
                                        <th>Doctor</th>
                                        <th>Mobile</th>
                                        <th class="text-right">Current %</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($portalCommissionDoctors as $doc)
                                        @php
                                            $currentPct = $doc->portal_lead_commission_percent !== null
                                                ? (float) $doc->portal_lead_commission_percent
                                                : 10.0;
                                        @endphp
                                        <tr>
                                            <td>
                                                <input type="checkbox" class="portal-lead-doctor-check" name="doctor_ids[]" value="{{ $doc->id }}">
                                            </td>
                                            <td class="font-weight-600">{{ $doc->name }}</td>
                                            <td class="text-nowrap">{{ $doc->mobile ?: ($doc->contact_no ?: '—') }}</td>
                                            <td class="text-right text-nowrap">{{ number_format($currentPct, 2) }}%</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <small class="text-muted d-block mt-2"><span id="portalLeadCommissionSelectedCount">0</span> doctor(s) selected</small>
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning text-dark font-weight-bold" id="portalLeadCommissionSaveBtn" {{ $portalCommissionDoctors->isEmpty() ? 'disabled' : '' }}>
                        Save commission
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="doctorViewModal" tabindex="-1" role="dialog" aria-labelledby="doctorViewModalTitle">
    <div class="modal-dialog modal-xl modal-dialog-scrollable" role="document" style="max-width:1040px;">
        <div class="modal-content border-0 shadow-lg" style="border-radius:14px;overflow:hidden;">
            <div class="modal-header border-0 py-3" style="background:linear-gradient(135deg,#fff8f0,#fff);">
                <h5 class="modal-title font-weight-bold" id="doctorViewModalTitle">Doctor registration details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0" id="doctorViewModalBody" style="max-height:78vh; overflow-y:auto;">
                <div class="text-center p-5 text-muted"><i class="fas fa-spinner fa-spin fa-2x"></i><p class="mt-2 mb-0">Loading…</p></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="doctorWebsiteReviewsModal" tabindex="-1" aria-labelledby="doctorWebsiteReviewsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="doctorWebsiteReviewsModalLabel">Website profile reviews (CareWeb)</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0" id="doctorWebsiteReviewsModalBody" style="max-height:75vh; overflow-y:auto;">
                <div class="text-center p-4 text-muted"><i class="fas fa-spinner fa-spin fa-2x"></i><p class="mt-2 mb-0">Loading…</p></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="pricingModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Update consultation charges</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="pricingDoctorId">
                <p class="text-muted mb-2" id="pricingDoctorName"></p>
                <div class="form-group">
                    <label>Doctor referral user</label>
                    <select class="form-control" id="pricing_doctor_referral_user_id">
                        <option value="">— None —</option>
                    </select>
                    <small class="text-muted">Shown in this list after you create referrers above. Set commission per mode below.</small>
                </div>
                <div id="pricingReferralCommissionPanel" class="border rounded px-3 py-2 mb-3 bg-light" style="display:none;">
                    <div class="font-weight-bold mb-2 small text-uppercase text-muted">Referral commission (per paid booking)</div>
                    @include('admin.doctor_requests.partials.referral_commission_inputs', [
                        'doctor' => new \App\Models\DoctorRequest(),
                        'inputSize' => 'default',
                        'valueClassOnline' => 'pricing_ref_comm_online dr-ref-comm-val-online',
                        'typeClassOnline' => 'pricing_ref_comm_online_type dr-ref-comm-type-online',
                        'valueClassHome' => 'pricing_ref_comm_home dr-ref-comm-val-home',
                        'typeClassHome' => 'pricing_ref_comm_home_type dr-ref-comm-type-home',
                        'valueClassClinic' => 'pricing_ref_comm_clinic dr-ref-comm-val-clinic',
                        'typeClassClinic' => 'pricing_ref_comm_clinic_type dr-ref-comm-type-clinic',
                        'valueIdOnline' => 'pricing_ref_comm_online',
                        'typeIdOnline' => 'pricing_ref_comm_online_type',
                        'valueIdHome' => 'pricing_ref_comm_home',
                        'typeIdHome' => 'pricing_ref_comm_home_type',
                        'valueIdClinic' => 'pricing_ref_comm_clinic',
                        'typeIdClinic' => 'pricing_ref_comm_clinic_type',
                    ])
                </div>
                <div class="border rounded px-3 py-2 mb-3 bg-light">
                    <div class="font-weight-bold mb-2 small text-uppercase text-muted">Per sub-service charges (this doctor)</div>
                    <p class="small text-muted mb-2">Doctor charge and CareWeb card price for each sub-service. Blank = use location default.</p>
                    <div id="pricingSubServiceBlocks"></div>
                </div>
                <div class="border rounded px-3 py-2 mb-3 bg-light" id="pricingFlatFallbackWrap">
                    <div class="font-weight-bold mb-2 small text-uppercase text-muted">General fallback charges</div>
                    <p class="small text-muted mb-2">Used when no sub-service is selected on CareWeb, or when per sub-service charge is blank.</p>
                    <div id="pricingFieldsOnline" class="form-group mb-2">
                        <label class="mb-0">Online (₹)</label>
                        <input type="number" class="form-control" id="pricing_online" min="0" step="0.01">
                    </div>
                    <div id="pricingFieldsHome" class="form-group mb-2">
                        <label class="mb-0">Home visit (₹)</label>
                        <input type="number" class="form-control" id="pricing_home" min="0" step="0.01">
                    </div>
                    <div id="pricingFieldsClinic" class="form-group mb-0">
                        <label class="mb-0">Clinic (₹)</label>
                        <input type="number" class="form-control" id="pricing_clinic" min="0" step="0.01">
                    </div>
                </div>
                <div class="border rounded px-3 py-2 mb-2">
                    <div class="font-weight-bold mb-2 small text-uppercase text-muted">General fallback — CareWeb patient fee (₹)</div>
                    <p class="small text-muted mb-2">Shown on doctor card when per sub-service CareWeb charge is empty.</p>
                    <div id="pricingFieldsCustOnline" class="form-group mb-2">
                        <label class="mb-0">Website fee — Online (₹)</label>
                        <input type="number" class="form-control" id="pricing_wcust_online" min="0" step="0.01" placeholder="Same as doctor if empty">
                    </div>
                    <div id="pricingFieldsCustHome" class="form-group mb-2">
                        <label class="mb-0">Website fee — Home visit (₹)</label>
                        <input type="number" class="form-control" id="pricing_wcust_home" min="0" step="0.01" placeholder="Same as doctor if empty">
                    </div>
                    <div id="pricingFieldsCustClinic" class="form-group mb-0">
                        <label class="mb-0">Website fee — Clinic (₹)</label>
                        <input type="number" class="form-control" id="pricing_wcust_clinic" min="0" step="0.01" placeholder="Same as doctor if empty">
                    </div>
                </div>
                <div class="form-group">
                    <label>Note (optional, stored on each price log)</label>
                    <textarea class="form-control" id="pricing_note" rows="2" maxlength="2000"></textarea>
                </div>
                <h6 class="mt-3">Price change history</h6>
                <div class="table-responsive" style="max-height:220px;overflow:auto;">
                    <table class="table table-sm table-bordered" id="pricingLogsTable">
                        <thead><tr><th>When</th><th>Mode</th><th>Old</th><th>New</th><th>By</th><th>Note</th></tr></thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="pricingSaveBtn">Save &amp; log</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="doctorPriceChangeModal" tabindex="-1" aria-labelledby="doctorPriceChangeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-warning bg-opacity-10">
                <h5 class="modal-title" id="doctorPriceChangeModalLabel">Doctor price change requests</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="doctorPriceChangeModalBody">
                <div class="text-center p-4 text-muted"><i class="fas fa-spinner fa-spin fa-2x"></i><p class="mt-2 mb-0">Loading…</p></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="remarkModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Remark (optional)</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="remarkDoctorId">
                <input type="hidden" id="remarkNextStatus">
                <textarea id="remarkText" class="form-control" rows="3" placeholder="Note for doctor (e.g. rejection reason)"></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="remarkSubmit">Save</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('footer-script')
<script src="https://cdn.datatables.net/1.13.1/js/jquery.dataTables.min.js"></script>
<script>
(function() {
    function bsModalShow(id) {
        var el = document.getElementById(id);
        if (el && typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            bootstrap.Modal.getOrCreateInstance(el).show();
        }
    }
    function bsModalHide(id) {
        var el = document.getElementById(id);
        if (el && typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            var m = bootstrap.Modal.getInstance(el);
            if (m) m.hide();
        }
    }
    window._doctorReqBsModalShow = bsModalShow;
    window._doctorReqBsModalHide = bsModalHide;
})();
$(function() {
    function updatePortalLeadCommissionCount() {
        var n = $('.portal-lead-doctor-check:checked').length;
        $('#portalLeadCommissionSelectedCount').text(n);
        var all = $('.portal-lead-doctor-check');
        var selectAll = $('#portalLeadCommissionSelectAll');
        if (all.length) {
            selectAll.prop('checked', n === all.length);
            selectAll.prop('indeterminate', n > 0 && n < all.length);
        }
    }

    $('#portalLeadCommissionSelectAll').on('change', function() {
        $('.portal-lead-doctor-check').prop('checked', this.checked);
        updatePortalLeadCommissionCount();
    });

    $(document).on('change', '.portal-lead-doctor-check', updatePortalLeadCommissionCount);

    $('#portalLeadCommissionForm').on('submit', function(e) {
        if ($('.portal-lead-doctor-check:checked').length < 1) {
            e.preventDefault();
            alert('Please select at least one doctor.');
            return false;
        }
    });

    var table = $('#doctor-requests-table').DataTable({
        processing: true,
        ajax: {
            url: '{{ route('subadmin.doctor_requests.data') }}',
            data: function(d) {
                d.approval_status = $('#filterStatus').val();
            },
            dataSrc: 'data'
        },
        columns: [
            { data: 'id', name: 'id', className: 'text-muted' },
            { data: 'lead_id', name: 'lead_id', render: function(d) { return '<span class="dr-lead">' + (d || '—') + '</span>'; } },
            {
                data: 'name',
                name: 'name',
                render: function(d, type, row) {
                    var html = '<span class="dr-name">' + (d || '—') + '</span>';
                    if (row.pending_price_change_count > 0) {
                        html += ' <span class="badge badge-warning text-dark ml-1">Price request</span>';
                    }
                    return html;
                }
            },
            { data: 'mobile', name: 'mobile', defaultContent: '', render: function(d) { return '<span class="dr-mobile">' + (d || '—') + '</span>'; } },
            { data: 'job_title', name: 'job_title', defaultContent: '', render: function(d) { return '<span class="dr-service">' + (d || '—') + '</span>'; } },
            {
                data: 'approval_status',
                name: 'approval_status',
                render: function(data) {
                    if (!data) return '—';
                    var key = String(data).toLowerCase();
                    var map = {
                        pending: { cls: 'badge-warning text-dark', label: 'Pending' },
                        approved: { cls: 'badge-success', label: 'Approved' },
                        rejected: { cls: 'badge-danger', label: 'Rejected' }
                    };
                    var m = map[key] || { cls: 'badge-secondary', label: data };
                    return '<span class="badge badge-pill dr-status-badge ' + m.cls + '">' + m.label + '</span>';
                }
            },
            {
                data: 'agreement_number',
                name: 'agreement_number',
                render: function(d) {
                    if (!d || d === '—') return '<span class="text-muted">—</span>';
                    return '<span style="color: #1a1a1a; background-color: #f1f5f9; padding: 3px 6px; border-radius: 4px; font-family: ui-monospace, monospace;">' + d + '</span>';
                }
            },
            {
                data: 'agreement_status_label',
                name: 'agreement_status',
                orderable: false,
                render: function(d, type, row) {
                    var st = String(row.agreement_status || '').toUpperCase();
                    if (!st) return '<span class="text-muted">—</span>';
                    var cls = 'badge-secondary';
                    if (st === 'SENT') cls = 'badge-info';
                    if (st === 'SIGNED' || st === 'APPROVED' || st === 'COMPLETED') cls = 'badge-success';
                    if (st === 'REJECTED' || st === 'FAILED') cls = 'badge-danger';
                    return '<span class="badge badge-pill ' + cls + '">' + (d || st) + '</span>';
                }
            },
            { data: 'created_at', name: 'created_at', render: function(d) { return '<span class="dr-date">' + (d || '—') + '</span>'; } },
            { data: 'actions', name: 'actions', orderable: false, searchable: false, className: 'text-end dr-actions-cell' }
        ],
        createdRow: function(row, data) {
            if (data.pending_price_change_count > 0) {
                $(row).addClass('dr-row-price-pending');
            }
        },
        initComplete: function() {
            $('#doctor-requests-table_wrapper .dataTables_filter input').attr('placeholder', 'Search doctors…');
            var api = this.api();
            requestAnimationFrame(function() { api.columns.adjust(); });
        }
    });

    var _drDtResizeTimer;
    $(window).on('resize', function() {
        clearTimeout(_drDtResizeTimer);
        _drDtResizeTimer = setTimeout(function() {
            if ($.fn.dataTable.isDataTable('#doctor-requests-table')) {
                $('#doctor-requests-table').DataTable().columns.adjust();
            }
        }, 250);
    });

    function bindDoctorRequestStatusDropdowns() {
        if (typeof bootstrap === 'undefined' || !bootstrap.Dropdown) return;
        document.querySelectorAll('#doctor-requests-table .doctor-req-decision-dd [data-bs-toggle="dropdown"]').forEach(function(el) {
            bootstrap.Dropdown.getOrCreateInstance(el, {
                popperConfig: {
                    strategy: 'fixed',
                    modifiers: [
                        { name: 'preventOverflow', options: { boundary: document.body } }
                    ]
                }
            });
        });
    }
    table.on('draw.dt', function() {
        bindDoctorRequestStatusDropdowns();
        table.columns.adjust();
    });

    $('#filterStatus').on('change', function() { table.ajax.reload(); });

    function applyDoctorPricingModes(modes) {
        modes = modes || [];
        if (!Array.isArray(modes) || modes.length === 0) {
            $('#pricingFieldsOnline,#pricingFieldsHome,#pricingFieldsClinic,#pricingFieldsCustOnline,#pricingFieldsCustHome,#pricingFieldsCustClinic').show();
            return;
        }
        var has = function(m) { return modes.indexOf(m) !== -1; };
        $('#pricingFieldsOnline').toggle(has('online'));
        $('#pricingFieldsHome').toggle(has('home_visit'));
        $('#pricingFieldsClinic').toggle(has('clinic_visit'));
        $('#pricingFieldsCustOnline').toggle(has('online'));
        $('#pricingFieldsCustHome').toggle(has('home_visit'));
        $('#pricingFieldsCustClinic').toggle(has('clinic_visit'));
    }

    $('#regenerateOnlineMeetingsBtn').on('click', function() {
        if (!confirm('This will regenerate Zoom links for all ONLINE consultation bookings. Continue?')) {
            return;
        }
        var $btn = $(this);
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Regenerating...');
        $.ajax({
            url: '{{ route('subadmin.doctor_requests.regenerate_online_meetings') }}',
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                scope: 'all'
            },
            success: function(res) {
                if (res && res.success) {
                    toastr.success(res.message || 'Zoom links regenerated successfully.');
                } else {
                    toastr.error((res && res.message) ? res.message : 'Failed to regenerate Zoom links.');
                }
            },
            error: function(xhr) {
                toastr.error(xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Regeneration request failed.');
            },
            complete: function() {
                $btn.prop('disabled', false).html('<i class="fas fa-sync-alt mr-1"></i>Generate links');
            }
        });
    });

    window.openDoctorViewModal = function(id) {
        var $body = $('#doctorViewModalBody');
        $body.html('<div class="text-center p-5 text-muted"><i class="fas fa-spinner fa-spin fa-2x"></i><p class="mt-2 mb-0">Loading…</p></div>');
        window._doctorReqBsModalShow('doctorViewModal');
        $.ajax({
            url: '{{ url('subadmin/doctor-requests') }}/' + id + '/view-modal',
            method: 'GET',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        }).done(function(html) {
            $body.html(html);
            if (typeof window.drRegInitAllServiceEditors === 'function') {
                window.drRegInitAllServiceEditors($body);
            }
        }).fail(function() {
            $body.html('<div class="p-4"><div class="alert alert-danger mb-0">Could not load doctor details. Please try again.</div></div>');
        });
    };

    window.openDoctorWebsiteReviewsModal = function(id) {
        var $body = $('#doctorWebsiteReviewsModalBody');
        $body.html('<div class="text-center p-4 text-muted"><i class="fas fa-spinner fa-spin fa-2x"></i><p class="mt-2 mb-0">Loading…</p></div>');
        window._doctorReqBsModalShow('doctorWebsiteReviewsModal');
        $.ajax({
            url: '{{ url('subadmin/doctor-requests') }}/' + id + '/website-reviews/panel',
            method: 'GET',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        }).done(function(html) {
            $body.html(html);
        }).fail(function() {
            $body.html('<div class="p-3"><div class="alert alert-danger mb-0">Could not load reviews panel.</div></div>');
        });
    };

    $(document).on('click', '.dr-wr-delete', function(e) {
        e.preventDefault();
        var url = $(this).data('url');
        if (!url || !confirm('Delete this review from the website?')) return;
        var $btn = $(this);
        $btn.prop('disabled', true);
        $.ajax({
            url: url,
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                'X-Requested-With': 'XMLHttpRequest'
            },
            success: function(res) {
                if (res && res.success) {
                    toastr.success(res.message || 'Removed');
                    if (res.html) $('#doctorWebsiteReviewsModalBody').html(res.html);
                } else {
                    toastr.error((res && res.message) ? res.message : 'Failed');
                }
            },
            error: function(xhr) {
                toastr.error(xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Request failed');
            },
            complete: function() {
                $btn.prop('disabled', false);
            }
        });
    });

    $(document).on('submit', '#drWebsiteReviewAddForm', function(e) {
        e.preventDefault();
        var $f = $(this);
        var $msg = $f.find('.dr-wr-form-msg');
        $msg.text('Saving…').removeClass('text-danger text-success');
        var fd = new FormData(this);
        var url = $f.data('store-url');
        $.ajax({
            url: url,
            method: 'POST',
            data: fd,
            processData: false,
            contentType: false,
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            success: function(res) {
                if (res && res.success) {
                    toastr.success(res.message || 'Saved');
                    if (res.html) $('#doctorWebsiteReviewsModalBody').html(res.html);
                } else {
                    toastr.error((res && res.message) ? res.message : 'Failed');
                    $msg.text('').addClass('text-danger');
                }
            },
            error: function(xhr) {
                var m = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Request failed';
                if (xhr.responseJSON && xhr.responseJSON.errors) {
                    m = Object.values(xhr.responseJSON.errors).map(function(a) { return a.join(' '); }).join(' ');
                }
                toastr.error(m);
                $msg.text(m).addClass('text-danger');
            }
        });
    });

    window.setDoctorStatus = function(id, status) {
        $('#remarkDoctorId').val(id);
        $('#remarkNextStatus').val(status);
        $('#remarkText').val('');
        if (status === 'rejected') {
            window._doctorReqBsModalShow('remarkModal');
        } else {
            submitDoctorStatus();
        }
    };

    function submitDoctorStatus() {
        var id = $('#remarkDoctorId').val();
        var status = $('#remarkNextStatus').val();
        var remark = $('#remarkText').val();
        $.ajax({
            url: '{{ url('subadmin/doctor-requests') }}/' + id + '/status',
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                approval_status: status,
                admin_remark: remark
            },
            success: function(res) {
                if (res.success) {
                    toastr.success(res.message || 'Updated');
                    window._doctorReqBsModalHide('remarkModal');
                    table.ajax.reload();
                } else {
                    toastr.error(res.message || 'Failed');
                }
            },
            error: function(xhr) {
                toastr.error(xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Request failed');
            }
        });
    }

    $('#remarkSubmit').on('click', function() { submitDoctorStatus(); });

    function togglePricingReferralCommissionPanel() {
        var hasRef = !!$('#pricing_doctor_referral_user_id').val();
        $('#pricingReferralCommissionPanel').toggle(hasRef);
    }
    function toggleInlineReferralCommissionPanel($w) {
        var hasRef = !!$w.find('.dr-inline-p-referral').val();
        $w.find('.dr-referral-commission-panel').toggle(hasRef);
    }
    function syncReferralCommInputGroup($group) {
        var type = $group.find('.dr-ref-comm-type').val() || 'fixed';
        var $input = $group.find('.dr-ref-comm-val');
        var $hint = $group.closest('.form-group').find('.dr-ref-comm-hint');
        $input.attr('data-comm-type', type);
        if (type === 'percent') {
            $input.attr('placeholder', 'e.g. 10').attr('max', '100');
            $hint.text('% of paid booking amount');
        } else {
            $input.attr('placeholder', 'e.g. 500').removeAttr('max');
            $hint.text('Fixed ₹ per paid booking');
        }
    }
    function initReferralCommHints($scope) {
        ($scope || $(document)).find('.dr-ref-comm-input-group').each(function() {
            syncReferralCommInputGroup($(this));
        });
    }
    function referralCommissionPayloadFromScope($scope) {
        return {
            referral_commission_online: $scope.find('.dr-ref-comm-val-online, .dr-inline-p-ref-comm-online, #pricing_ref_comm_online').first().val(),
            referral_commission_online_type: $scope.find('.dr-ref-comm-type-online, .dr-inline-p-ref-type-online, #pricing_ref_comm_online_type').first().val(),
            referral_commission_home_visit: $scope.find('.dr-ref-comm-val-home, .dr-inline-p-ref-comm-home, #pricing_ref_comm_home').first().val(),
            referral_commission_home_visit_type: $scope.find('.dr-ref-comm-type-home, .dr-inline-p-ref-type-home, #pricing_ref_comm_home_type').first().val(),
            referral_commission_clinic: $scope.find('.dr-ref-comm-val-clinic, .dr-inline-p-ref-comm-clinic, #pricing_ref_comm_clinic').first().val(),
            referral_commission_clinic_type: $scope.find('.dr-ref-comm-type-clinic, .dr-inline-p-ref-type-clinic, #pricing_ref_comm_clinic_type').first().val()
        };
    }
    $(document).on('change', '.dr-ref-comm-type', function() {
        syncReferralCommInputGroup($(this).closest('.dr-ref-comm-input-group'));
    });
    $('#pricing_doctor_referral_user_id').on('change', togglePricingReferralCommissionPanel);
    $(document).on('change', '.dr-inline-p-referral', function() {
        toggleInlineReferralCommissionPanel($(this).closest('.dr-inline-pricing-wrap'));
    });

    $(document).on('click', '.dr-inline-pricing-save', function() {
        var $w = $(this).closest('.dr-inline-pricing-wrap');
        var id = $w.data('dr-id');
        var $msg = $w.find('.dr-inline-pricing-msg');
        $msg.text('Saving…').removeClass('text-danger text-success');
        var payload = {
            _token: '{{ csrf_token() }}',
            note: $w.find('.dr-inline-p-note').val()
        };
        var $refSel = $w.find('.dr-inline-p-referral');
        if ($refSel.length) {
            payload.doctor_referral_user_id = $refSel.val();
            $.extend(payload, referralCommissionPayloadFromScope($w));
        }
        var $subsWrap = $w.find('.dr-inline-pricing-subs');
        if ($subsWrap.length && typeof window.drConsultPricingCollect === 'function') {
            var items = window.drConsultPricingItemsFromDom($subsWrap);
            var modes = window.drConsultPricingModesFromDom($subsWrap);
            var serviceId = parseInt($subsWrap.data('service-id') || '0', 10);
            payload.consultation_pricing = JSON.stringify(
                window.drConsultPricingCollect($subsWrap, serviceId, items, modes)
            );
        }
        $.ajax({
            url: '{{ url('subadmin/doctor-requests') }}/' + id + '/pricing',
            method: 'PUT',
            data: payload,
            success: function(res) {
                if (res.success) {
                    toastr.success(res.message || 'Charges saved');
                    $msg.text('Saved.').addClass('text-success');
                    $w.find('.dr-inline-p-note').val('');
                    table.ajax.reload(null, false);
                    $.ajax({
                        url: '{{ url('subadmin/doctor-requests') }}/' + id + '/view-modal',
                        method: 'GET',
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    }).done(function(html) {
                        var $modalBody = $('#doctorViewModalBody');
                        $modalBody.html(html);
                        if (typeof window.drRegInitAllServiceEditors === 'function') {
                            window.drRegInitAllServiceEditors($modalBody);
                        }
                        if (typeof window.drRegInitAllModesEditors === 'function') {
                            window.drRegInitAllModesEditors($modalBody);
                        }
                        if (typeof window.drConsultPricingInitInline === 'function') {
                            window.drConsultPricingInitInline($modalBody);
                        }
                        initReferralCommHints($modalBody);
                    });
                } else {
                    toastr.error(res.message || 'Failed');
                    $msg.text('');
                }
            },
            error: function(xhr) {
                toastr.error(xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Request failed');
                $msg.text('');
            }
        });
    });

    window.openDoctorPricing = function(id) {
        $('#pricingDoctorId').val(id).removeData('service-id');
        $('#pricing_note').val('');
        $('#pricingSubServiceBlocks').html('<p class="small text-muted mb-0">Loading…</p>');
        $.get('{{ url('subadmin/doctor-requests') }}/' + id + '/pricing-edit', function(res) {
            $('#pricingDoctorId').data('service-id', res.service_id || 0);
            $('#pricingDoctorName').text(res.name || '');
            applyDoctorPricingModes(res.modes);
            $('#pricing_online').val(res.online_charges != null ? res.online_charges : '');
            $('#pricing_home').val(res.home_visit_charges != null ? res.home_visit_charges : '');
            $('#pricing_clinic').val(res.clinic_consultation_charges != null ? res.clinic_consultation_charges : '');
            $('#pricing_wcust_online').val(res.website_customer_fee_online != null ? res.website_customer_fee_online : '');
            $('#pricing_wcust_home').val(res.website_customer_fee_home_visit != null ? res.website_customer_fee_home_visit : '');
            $('#pricing_wcust_clinic').val(res.website_customer_fee_clinic != null ? res.website_customer_fee_clinic : '');
            var $psr = $('#pricing_doctor_referral_user_id');
            $psr.empty().append('<option value="">— None —</option>');
            (res.referral_users || []).forEach(function(r) {
                $psr.append($('<option></option>').attr('value', r.id).text(r.label));
            });
            $psr.val(res.doctor_referral_user_id != null && res.doctor_referral_user_id !== ''
                ? String(res.doctor_referral_user_id) : '');
            $('#pricing_ref_comm_online').val(res.referral_commission_online != null ? res.referral_commission_online : '');
            $('#pricing_ref_comm_home').val(res.referral_commission_home_visit != null ? res.referral_commission_home_visit : '');
            $('#pricing_ref_comm_clinic').val(res.referral_commission_clinic != null ? res.referral_commission_clinic : '');
            $('#pricing_ref_comm_online_type').val(res.referral_commission_online_type || 'fixed');
            $('#pricing_ref_comm_home_type').val(res.referral_commission_home_visit_type || 'fixed');
            $('#pricing_ref_comm_clinic_type').val(res.referral_commission_clinic_type || 'fixed');
            togglePricingReferralCommissionPanel();
            initReferralCommHints($('#pricingReferralCommissionPanel'));
            if (typeof window.drConsultPricingRenderModalBlocks === 'function') {
                window.drConsultPricingRenderModalBlocks($('#pricingSubServiceBlocks'), res);
            }
        });
        $.get('{{ url('subadmin/doctor-requests') }}/' + id + '/pricing-logs', function(res) {
            var $tb = $('#pricingLogsTable tbody');
            $tb.empty();
            (res.data || []).forEach(function(row) {
                $tb.append('<tr><td>' + (row.created_at || '') + '</td><td>' + (row.mode || '') + '</td><td>' + (row.old_amount != null ? row.old_amount : '—') + '</td><td>' + (row.new_amount != null ? row.new_amount : '—') + '</td><td>' + (row.updated_by || '') + '</td><td><small>' + (row.note || '') + '</small></td></tr>');
            });
        });
        window._doctorReqBsModalShow('pricingModal');
    };

    $('#pricingSaveBtn').on('click', function() {
        var id = $('#pricingDoctorId').val();
        var payload = {
            _token: '{{ csrf_token() }}',
            note: $('#pricing_note').val(),
            online_charges: $('#pricing_online').val(),
            home_visit_charges: $('#pricing_home').val(),
            clinic_consultation_charges: $('#pricing_clinic').val(),
            website_customer_fee_online: $('#pricing_wcust_online').val(),
            website_customer_fee_home_visit: $('#pricing_wcust_home').val(),
            website_customer_fee_clinic: $('#pricing_wcust_clinic').val(),
            doctor_referral_user_id: $('#pricing_doctor_referral_user_id').val()
        };
        $.extend(payload, referralCommissionPayloadFromScope($('#pricingModal')));
        var $modalSubs = $('#pricingSubServiceBlocks');
        if ($modalSubs.length && typeof window.drConsultPricingCollect === 'function') {
            var mItems = window.drConsultPricingItemsFromDom($modalSubs);
            var mModes = window.drConsultPricingModesFromDom($modalSubs);
            var mServiceId = parseInt($('#pricingDoctorId').data('service-id') || '0', 10);
            payload.consultation_pricing = JSON.stringify(
                window.drConsultPricingCollect($modalSubs, mServiceId, mItems, mModes)
            );
        }
        $.ajax({
            url: '{{ url('subadmin/doctor-requests') }}/' + id + '/pricing',
            method: 'PUT',
            data: payload,
            success: function(res) {
                if (res.success) {
                    toastr.success(res.message || 'Saved');
                    table.ajax.reload(null, false);
                    openDoctorPricing(id);
                } else {
                    toastr.error(res.message || 'Failed');
                }
            },
            error: function(xhr) {
                toastr.error(xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Request failed');
            }
        });
    });

    function renderPriceChangeRequestsModal(data) {
        var doctor = data.doctor || {};
        var requests = data.requests || [];
        var html = '<p class="mb-2"><strong>' + (doctor.name || 'Doctor') + '</strong>';
        if (doctor.job_title) {
            html += ' · <span class="text-muted">' + doctor.job_title + '</span>';
        }
        html += '</p>';
        if (!requests.length) {
            html += '<p class="text-muted mb-0">No pending price change requests.</p>';
            $('#doctorPriceChangeModalBody').html(html);
            return;
        }
        html += '<div class="table-responsive"><table class="table table-sm table-bordered mb-0"><thead><tr>';
        html += '<th>Sub-service</th><th>Mode</th><th>Current</th><th>Requested</th><th>Submitted</th><th class="text-end">Action</th>';
        html += '</tr></thead><tbody>';
        requests.forEach(function (req) {
            var cur = req.current_price != null ? ('₹' + Number(req.current_price).toLocaleString('en-IN')) : '—';
            var reqP = req.requested_price != null ? ('₹' + Number(req.requested_price).toLocaleString('en-IN')) : '—';
            html += '<tr data-req-id="' + req.id + '">';
            html += '<td>' + (req.sub_service_name || 'Service-level') + '</td>';
            html += '<td>' + (req.mode_label || req.consultation_mode) + '</td>';
            html += '<td>' + cur + '</td>';
            html += '<td><strong>' + reqP + '</strong></td>';
            html += '<td class="text-nowrap small">' + (req.created_at || '—') + '</td>';
            html += '<td class="text-end text-nowrap">';
            html += '<button type="button" class="btn btn-sm btn-success mr-1 dr-pcr-approve" data-id="' + req.id + '">Approve</button>';
            html += '<button type="button" class="btn btn-sm btn-outline-danger dr-pcr-reject" data-id="' + req.id + '">Reject</button>';
            html += '</td></tr>';
        });
        html += '</tbody></table></div>';
        $('#doctorPriceChangeModalBody').html(html);
    }

    window.openDoctorPriceChangeRequests = function (doctorId) {
        $('#doctorPriceChangeModal').data('doctor-id', doctorId);
        $('#doctorPriceChangeModalBody').html('<div class="text-center p-4 text-muted"><i class="fas fa-spinner fa-spin fa-2x"></i><p class="mt-2 mb-0">Loading…</p></div>');
        window._doctorReqBsModalShow('doctorPriceChangeModal');
        $.get('{{ url('subadmin/doctor-requests') }}/' + doctorId + '/price-change-requests', function (res) {
            if (res.success) {
                renderPriceChangeRequestsModal(res);
            } else {
                $('#doctorPriceChangeModalBody').html('<p class="text-danger mb-0">Could not load requests.</p>');
            }
        }).fail(function () {
            $('#doctorPriceChangeModalBody').html('<p class="text-danger mb-0">Could not load requests.</p>');
        });
    };

    $(document).on('click', '.dr-pcr-approve', function () {
        var id = $(this).data('id');
        var $btn = $(this);
        if (!confirm('Approve this price change and apply it to the doctor profile?')) return;
        $btn.prop('disabled', true);
        $.post('{{ url('subadmin/doctor-requests/price-change-requests') }}/' + id + '/approve', {
            _token: '{{ csrf_token() }}'
        }, function (res) {
            if (res.success) {
                toastr.success(res.message || 'Approved');
                table.ajax.reload(null, false);
                var doctorId = $('#doctorPriceChangeModal').data('doctor-id');
                if (doctorId) {
                    openDoctorPriceChangeRequests(doctorId);
                }
            } else {
                toastr.error(res.message || 'Failed');
                $btn.prop('disabled', false);
            }
        }).fail(function (xhr) {
            toastr.error(xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Failed');
            $btn.prop('disabled', false);
        });
    });

    $(document).on('click', '.dr-pcr-reject', function () {
        var id = $(this).data('id');
        var note = window.prompt('Reason for rejection (shown to doctor):');
        if (note === null) return;
        note = String(note).trim();
        if (!note) {
            toastr.error('Please enter a rejection reason.');
            return;
        }
        var $btn = $(this);
        $btn.prop('disabled', true);
        $.post('{{ url('subadmin/doctor-requests/price-change-requests') }}/' + id + '/reject', {
            _token: '{{ csrf_token() }}',
            admin_note: note
        }, function (res) {
            if (res.success) {
                toastr.success(res.message || 'Rejected');
                table.ajax.reload(null, false);
                var doctorId = $('#doctorPriceChangeModal').data('doctor-id');
                if (doctorId) {
                    openDoctorPriceChangeRequests(doctorId);
                }
            } else {
                toastr.error(res.message || 'Failed');
                $btn.prop('disabled', false);
            }
        }).fail(function (xhr) {
            toastr.error(xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Failed');
            $btn.prop('disabled', false);
        });
    });

    initReferralCommHints($(document));
});
</script>
@include('admin.doctor_requests.partials.doctor_consultation_pricing_editor_scripts')
@include('admin.doctor_requests.partials.registration_profile_editor_scripts')
@include('admin.doctor_requests.partials.registration_profile_image_admin_scripts')
@include('admin.doctor_requests.partials.leegality_agreement_scripts')
@endsection
