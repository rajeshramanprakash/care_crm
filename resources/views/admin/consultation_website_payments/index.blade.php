@extends('admin.layouts.app')

@section('title', 'Website consultation payments')

@section('header-css')
<style>
    .cwp-page {
        --cwp-accent: #fe992e;
        --cwp-accent-dark: #e8892a;
        --cwp-surface: #ffffff;
        --cwp-border: #e8ecf1;
        --cwp-muted: #6c757d;
        --cwp-heading: #1a2340;
    }
    /*
     * Global layout uses body { overflow: hidden }. This page must scroll inside
     * .content-wrapper; cap height to viewport minus approximate main header row.
     */
    body.sidebar-mini.layout-fixed .wrapper .content-wrapper.cwp-page {
        max-height: calc(100vh - 3.5rem);
        max-height: calc(100dvh - 3.5rem);
        overflow-y: auto !important;
        overflow-x: hidden;
        -webkit-overflow-scrolling: touch;
    }
    .cwp-page .cwp-stat-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 1rem;
        margin-bottom: 1.25rem;
    }
    @media (max-width: 991px) {
        .cwp-page .cwp-stat-grid { grid-template-columns: 1fr; }
    }
    .cwp-page .cwp-stat-card {
        background: var(--cwp-surface);
        border-radius: 14px;
        border: 1px solid var(--cwp-border);
        box-shadow: 0 4px 18px rgba(26, 35, 64, 0.06);
        padding: 1.15rem 1.15rem 1.15rem 1.25rem;
        display: flex;
        align-items: flex-start;
        gap: 1rem;
        min-height: 108px;
        position: relative;
        overflow: hidden;
    }
    .cwp-page .cwp-stat-card::before {
        content: '';
        position: absolute;
        left: 0;
        top: 0;
        bottom: 0;
        width: 4px;
        background: linear-gradient(180deg, var(--cwp-accent) 0%, var(--cwp-accent-dark) 100%);
    }
    .cwp-page .cwp-stat-icon {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
        flex-shrink: 0;
    }
    .cwp-page .cwp-stat-card--month .cwp-stat-icon {
        background: rgba(254, 153, 46, 0.12);
        color: var(--cwp-accent-dark);
    }
    .cwp-page .cwp-stat-card--total .cwp-stat-icon {
        background: rgba(23, 162, 184, 0.12);
        color: #138496;
    }
    .cwp-page .cwp-stat-card--pending .cwp-stat-icon {
        background: rgba(255, 193, 7, 0.15);
        color: #b8860b;
    }
    .cwp-page .cwp-stat-value {
        font-size: 1.55rem;
        font-weight: 700;
        color: var(--cwp-heading);
        line-height: 1.15;
        letter-spacing: -0.02em;
    }
    .cwp-page .cwp-stat-label {
        font-size: 0.82rem;
        color: var(--cwp-muted);
        margin-top: 0.35rem;
        line-height: 1.35;
        max-width: 220px;
    }
    .cwp-page .cwp-filter-card {
        background: #fafbfc;
        border: 1px solid var(--cwp-border);
        border-radius: 12px;
        padding: 1rem 1.1rem;
        margin-bottom: 1rem;
    }
    .cwp-page .cwp-table-card {
        border-radius: 14px;
        border: 1px solid var(--cwp-border);
        box-shadow: 0 4px 22px rgba(26, 35, 64, 0.06);
    }
    .cwp-page .cwp-table-card .card-header {
        background: linear-gradient(135deg, var(--cwp-accent) 0%, var(--cwp-accent-dark) 100%);
        color: #fff;
        border: none;
        padding: 1rem 1.25rem;
    }
    .cwp-page .cwp-table-card .card-header .card-title {
        color: #fff;
        font-weight: 700;
        font-size: 1.15rem;
    }
    .cwp-page .cwp-table-card .card-header .cwp-sub {
        color: rgba(255, 255, 255, 0.9);
        font-size: 0.875rem;
        margin-top: 0.25rem;
    }
    .cwp-page .cwp-table-wrap {
        overflow-x: auto;
        overflow-y: visible;
    }
    .cwp-page .cwp-table-wrap table {
        margin-bottom: 0;
        font-size: 0.875rem;
    }
    .cwp-page .cwp-table-wrap thead th {
        position: sticky;
        top: 0;
        z-index: 2;
        background: #f8f9fb !important;
        color: var(--cwp-heading) !important;
        font-weight: 700;
        font-size: 0.78rem;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        border-bottom: 2px solid var(--cwp-border) !important;
        white-space: nowrap;
        padding: 0.65rem 0.5rem !important;
    }
    .cwp-page .cwp-table-wrap tbody td {
        vertical-align: middle;
        padding: 0.65rem 0.5rem;
        border-color: #eef1f4;
    }
    .cwp-page .cwp-table-wrap tbody tr:hover {
        background: #fffbf5;
    }
    .cwp-page .cwp-table-footer {
        border-top: 1px solid var(--cwp-border);
        background: #fafbfc;
        padding: 1rem 1.25rem 1.15rem;
    }
    .cwp-page .cwp-table-footer-summary {
        font-size: 0.875rem;
        color: var(--cwp-muted);
        margin-bottom: 0.85rem;
    }
    .cwp-page .cwp-table-footer-summary strong {
        color: var(--cwp-heading);
    }
    .cwp-page .cwp-pagination-nav {
        display: flex;
        justify-content: center;
        width: 100%;
    }
    .cwp-page .cwp-pagination-nav nav {
        width: 100%;
        display: flex;
        justify-content: center;
    }
    .cwp-page .cwp-pagination-nav .pagination {
        margin: 0;
        flex-wrap: wrap;
        justify-content: center;
        gap: 2px;
        padding: 6px 8px;
        background: #fff;
        border: 1px solid var(--cwp-border);
        border-radius: 10px;
        box-shadow: 0 2px 8px rgba(26, 35, 64, 0.06);
    }
    .cwp-page .cwp-pagination-nav .page-item:first-child .page-link {
        border-top-left-radius: 6px;
        border-bottom-left-radius: 6px;
    }
    .cwp-page .cwp-pagination-nav .page-item:last-child .page-link {
        border-top-right-radius: 6px;
        border-bottom-right-radius: 6px;
    }
    .cwp-page .cwp-pagination-nav .page-link {
        color: var(--cwp-heading);
        border-color: var(--cwp-border);
        padding: 0.45rem 0.75rem;
        font-size: 0.875rem;
        margin-left: 0;
        border-radius: 6px !important;
        margin: 0 1px;
    }
    .cwp-page .cwp-pagination-nav .page-item.active .page-link {
        background: var(--cwp-accent);
        border-color: var(--cwp-accent-dark);
        color: #fff;
        z-index: 1;
    }
    .cwp-page .cwp-pagination-nav .page-link:hover {
        background: #fff5eb;
        border-color: var(--cwp-accent);
        color: var(--cwp-accent-dark);
    }
    .cwp-page .cwp-pagination-nav .page-item.disabled .page-link {
        color: #adb5bd;
        background: #f8f9fa;
    }
    .cwp-page .cwp-detail-grid {
        display: grid;
        grid-template-columns: minmax(120px, 34%) 1fr;
        gap: 0.55rem 1rem;
        font-size: 0.9rem;
    }
    .cwp-page .cwp-detail-grid dt {
        margin: 0;
        font-weight: 700;
        color: var(--cwp-muted);
    }
    .cwp-page .cwp-detail-grid dd {
        margin: 0;
        color: var(--cwp-heading);
        word-break: break-word;
    }
</style>
@endsection

@section('content')
<div class="content-wrapper cwp-page">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0" style="font-size:1.5rem;font-weight:700;color:var(--cwp-heading);">Website consultation payments</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right bg-transparent px-0 mb-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item active">Website payments</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <div class="cwp-stat-grid">
                <div class="cwp-stat-card cwp-stat-card--month">
                    <div class="cwp-stat-icon"><i class="fas fa-calendar-check"></i></div>
                    <div>
                        <div class="cwp-stat-value">₹{{ number_format($paidThisMonth, 2) }}</div>
                        <div class="cwp-stat-label">Paid this month (careweb consultation bookings)</div>
                    </div>
                </div>
                <div class="cwp-stat-card cwp-stat-card--total">
                    <div class="cwp-stat-icon"><i class="fas fa-rupee-sign"></i></div>
                    <div>
                        <div class="cwp-stat-value">₹{{ number_format($paidSum, 2) }}</div>
                        <div class="cwp-stat-label">All-time paid total (recorded booking fees)</div>
                    </div>
                </div>
                <div class="cwp-stat-card cwp-stat-card--pending">
                    <div class="cwp-stat-icon"><i class="fas fa-hourglass-half"></i></div>
                    <div>
                        <div class="cwp-stat-value">{{ $pendingCount }}</div>
                        <div class="cwp-stat-label">Pending payment (Easebuzz not completed)</div>
                    </div>
                </div>
            </div>

            <div class="card cwp-table-card">
                <div class="card-header">
                    <h3 class="card-title mb-0">Payment history</h3>
                    <p class="cwp-sub mb-0">Doctor, consultation mode, fee, appointment slot, and gateway status from the public website.</p>
                </div>
                <div class="card-body pb-0">
                    <div class="cwp-filter-card">
                        <form method="get" action="{{ route('admin.website_consultation_payments.index') }}" class="row align-items-end">
                            <div class="col-sm-6 col-md-3 mb-2 mb-md-0">
                                <label for="payment_status" class="small font-weight-bold text-muted mb-1 d-block">Payment status</label>
                                <select name="payment_status" id="payment_status" class="form-control form-control-sm">
                                    <option value="" {{ $filterPaymentStatus === '' ? 'selected' : '' }}>All</option>
                                    <option value="paid" {{ $filterPaymentStatus === 'paid' ? 'selected' : '' }}>Paid</option>
                                    <option value="pending_payment" {{ $filterPaymentStatus === 'pending_payment' ? 'selected' : '' }}>Pending payment</option>
                                    <option value="with_fee" {{ $filterPaymentStatus === 'with_fee' ? 'selected' : '' }}>Has booking fee</option>
                                </select>
                            </div>
                            <div class="col-sm-6 col-md-3 mb-2 mb-md-0">
                                <label for="per_page" class="small font-weight-bold text-muted mb-1 d-block">Rows per page</label>
                                <select name="per_page" id="per_page" class="form-control form-control-sm">
                                    @foreach([10, 25, 50, 100] as $n)
                                        <option value="{{ $n }}" {{ (int) $perPage === $n ? 'selected' : '' }}>{{ $n }} per page</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4 mb-2 mb-md-0">
                                <label for="q" class="small font-weight-bold text-muted mb-1 d-block">Search</label>
                                <input type="text" name="q" id="q" class="form-control form-control-sm" placeholder="Customer, phone, doctor, txn id, booking #" value="{{ $searchQ }}">
                            </div>
                            <div class="col-md-2 text-md-right">
                                <button type="submit" class="btn btn-sm btn-primary btn-block mt-3 mt-md-4"><i class="fas fa-filter mr-1"></i>Apply</button>
                                @if($filterPaymentStatus !== '' || $searchQ !== '' || (int) $perPage !== 10)
                                    <a href="{{ route('admin.website_consultation_payments.index') }}" class="btn btn-sm btn-outline-secondary btn-block mt-1">Reset</a>
                                @endif
                            </div>
                        </form>
                    </div>

                    <div class="cwp-table-wrap border-top">
                        <table class="table table-hover table-sm mb-0">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Booked at</th>
                                    <th>Customer</th>
                                    <th>Patient location</th>
                                    <th>Doctor</th>
                                    <th>Service</th>
                                    <th>Mode</th>
                                    <th>Appointment</th>
                                    <th>Time</th>
                                    <th>Amount</th>
                                    <th>Payment</th>
                                    <th>Paid at</th>
                                    <th>Txn ref</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($bookings as $b)
                                    @php
                                        $rowTags = is_array($b->selected_tags) ? array_values(array_filter(array_map('strval', $b->selected_tags))) : [];
                                        $rowSub = trim((string) ($b->sub_service_name ?? ''));
                                        if ($rowSub === '') {
                                            $rowSub = trim((string) ($b->consultationSubService?->name ?? ''));
                                        }
                                    @endphp
                                    <tr>
                                        <td><span class="font-weight-bold text-muted">#{{ $b->id }}</span></td>
                                        <td><span class="text-nowrap">{{ $b->created_at?->timezone(config('app.timezone'))->format('d M Y') }}</span><br><span class="small text-muted">{{ $b->created_at?->timezone(config('app.timezone'))->format('H:i') }}</span></td>
                                        <td>
                                            <span class="font-weight-bold">{{ $b->customer_name }}</span><br>
                                            <span class="text-muted small">{{ $b->contact_no }}</span>
                                        </td>
                                        <td class="small" style="max-width:220px;">
                                            @if($b->customer_address || $b->customer_city)
                                                <span class="d-block">{{ $b->customer_address ?: '—' }}</span>
                                                <span class="text-muted">{{ $b->customer_city ?: '' }}</span>
                                                @php
                                                    $mapUrl = \App\Models\ConsultationWebsiteBooking::googleMapsOpenUrl(
                                                        $b->customer_address_lat !== null ? (float) $b->customer_address_lat : null,
                                                        $b->customer_address_lng !== null ? (float) $b->customer_address_lng : null
                                                    );
                                                @endphp
                                                @if($mapUrl)
                                                    <a href="{{ $mapUrl }}" class="d-inline-block mt-1" target="_blank" rel="noopener">Open map</a>
                                                @endif
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td>{{ $b->doctorRequest?->name ?? '—' }}</td>
                                        <td>
                                            <span class="d-inline-block font-weight-bold" style="max-width:160px;">{{ $b->consultationService?->name ?? '—' }}</span>
                                            @if($rowSub !== '')
                                                <br><span class="small text-muted">Sub: {{ $rowSub }}</span>
                                            @endif
                                            @if(count($rowTags))
                                                <br><span class="small text-muted">Tags: {{ implode(', ', array_slice($rowTags, 0, 3)) }}@if(count($rowTags) > 3)…@endif</span>
                                            @endif
                                        </td>
                                        <td class="small" style="max-width:140px;">{{ \App\Models\ConsultationWebsiteBooking::modeLabel((string) ($b->consultation_mode ?? '')) }}</td>
                                        <td>{{ $b->appointment_date ? $b->appointment_date->format('d M Y') : '—' }}</td>
                                        <td class="small text-nowrap">{{ \App\Models\ConsultationWebsiteBooking::timeRangeLabel($b->appointment_start_time, $b->appointment_end_time) }}</td>
                                        <td class="font-weight-bold">
                                            @if($b->booking_fee_amount !== null && (float) $b->booking_fee_amount > 0)
                                                ₹{{ number_format((float) $b->booking_fee_amount, 2) }}
                                            @else
                                                <span class="text-muted font-weight-normal">—</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if(($b->payment_status ?? '') === 'paid')
                                                <span class="badge badge-success">{{ \App\Models\ConsultationWebsiteBooking::paymentStatusLabel('paid') }}</span>
                                            @elseif(($b->payment_status ?? '') === 'pending_payment')
                                                <span class="badge badge-warning">{{ \App\Models\ConsultationWebsiteBooking::paymentStatusLabel('pending_payment') }}</span>
                                            @else
                                                <span class="badge badge-secondary">{{ \App\Models\ConsultationWebsiteBooking::paymentStatusLabel($b->payment_status) }}</span>
                                            @endif
                                        </td>
                                        <td class="small">
                                            @if($b->paid_at)
                                                <span class="text-nowrap">{{ $b->paid_at->timezone(config('app.timezone'))->format('d M Y') }}</span><br><span class="text-muted">{{ $b->paid_at->timezone(config('app.timezone'))->format('H:i') }}</span>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td class="small text-break" style="max-width: 120px;">
                                            @if($b->easebuzz_txnid)
                                                <code class="small bg-light px-1 py-0 rounded">{{ $b->easebuzz_txnid }}</code>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td class="text-nowrap">
                                            <button type="button" class="btn btn-xs btn-outline-primary btn-sm js-cwp-view-booking" data-booking-id="{{ $b->id }}">
                                                <i class="fas fa-eye mr-1"></i>View
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="14" class="text-center text-muted py-5">
                                            <i class="fas fa-inbox fa-2x mb-2 d-block opacity-50"></i>
                                            No records match your filters.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if($bookings->total() > 0)
                        <div class="cwp-table-footer">
                            <div class="cwp-table-footer-summary">
                                Showing <strong>{{ $bookings->firstItem() }}</strong>–<strong>{{ $bookings->lastItem() }}</strong> of <strong>{{ $bookings->total() }}</strong> booking(s)
                                @if($bookings->hasPages())
                                    <span class="mx-1">·</span>
                                    Page <strong>{{ $bookings->currentPage() }}</strong> of <strong>{{ $bookings->lastPage() }}</strong>
                                @endif
                            </div>
                            @if($bookings->hasPages())
                                <div class="cwp-pagination-nav" role="navigation" aria-label="Payment history pagination">
                                    {{ $bookings->onEachSide(2)->links('pagination::bootstrap-4') }}
                                </div>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </section>
</div>

<div class="modal fade" id="cwpBookingDetailModal" tabindex="-1" role="dialog" aria-labelledby="cwpBookingDetailModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title font-weight-bold" id="cwpBookingDetailModalTitle">Booking details</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body" id="cwpBookingDetailModalBody">
                <div class="text-center text-muted py-4"><i class="fas fa-spinner fa-spin"></i> Loading…</div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('footer-script')
<script>
(function () {
    var modalUrlBase = @json(url('admin/website-consultation-payments'));
    $(document).on('click', '.js-cwp-view-booking', function () {
        var id = parseInt($(this).data('booking-id'), 10) || 0;
        if (!id) return;
        var $body = $('#cwpBookingDetailModalBody');
        $body.html('<div class="text-center text-muted py-4"><i class="fas fa-spinner fa-spin"></i> Loading…</div>');
        $('#cwpBookingDetailModal').modal('show');
        $.ajax({
            url: modalUrlBase + '/' + id + '/view-modal',
            method: 'GET',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        }).done(function (html) {
            $body.html(html);
        }).fail(function () {
            $body.html('<div class="alert alert-danger mb-0">Could not load booking details.</div>');
        });
    });
})();
</script>
@endsection
