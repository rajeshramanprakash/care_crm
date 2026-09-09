@extends('doctor_referral.layouts.app')

@section('page_title', 'Doctor leads')

@push('styles')
<style>
    .drp-leads-head {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 0.75rem 1rem;
        margin-bottom: 1rem;
        padding-bottom: 0.85rem;
        border-bottom: 1px solid #e2e8f0;
    }
    .drp-leads-title {
        font-size: 1.05rem;
        font-weight: 800;
        color: var(--drp-text);
        margin: 0 0 0.25rem 0;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    .drp-leads-title i { color: var(--drp-primary); }
    .drp-leads-caption {
        margin: 0;
        font-size: 0.8rem;
        color: var(--drp-muted);
        max-width: 42rem;
        line-height: 1.45;
    }
    .drp-leads-stats {
        display: flex;
        flex-wrap: wrap;
        gap: 0.45rem;
        margin-bottom: 0.85rem;
    }
    .drp-leads-pill {
        font-weight: 700;
        font-size: 0.76rem;
        padding: 0.35rem 0.65rem;
        border-radius: 999px;
        background: var(--drp-primary-soft);
        color: var(--drp-primary-dark);
        border: 1px solid var(--drp-border, #fed7aa);
        white-space: nowrap;
    }
    .drp-leads-pill--paid {
        background: #ecfdf5;
        color: #047857;
        border-color: #a7f3d0;
    }
    .drp-leads-pill--pending {
        background: #fffbeb;
        color: #b45309;
        border-color: #fde68a;
    }
    .drp-search-icon {
        position: absolute;
        left: 12px;
        top: 50%;
        transform: translateY(-50%);
        color: #94a3b8;
        font-size: 0.85rem;
        pointer-events: none;
    }
    .drp-search-input {
        padding-left: 2rem;
        border-radius: 10px;
        height: 40px;
        border: 1px solid #e2e8f0;
    }
    .drp-search-input:focus {
        border-color: #fdba74;
        box-shadow: 0 0 0 3px rgba(234, 138, 43, 0.15);
    }
    .drp-leads-table-wrap {
        border-radius: 12px;
        border: 1px solid #e2e8f0;
        overflow: auto;
        -webkit-overflow-scrolling: touch;
        max-height: min(72vh, 640px);
        background: #fff;
    }
    .drp-leads-table {
        border-collapse: separate;
        border-spacing: 0;
        font-size: 0.813rem;
        margin-bottom: 0;
    }
    .drp-leads-table thead th {
        position: sticky;
        top: 0;
        z-index: 2;
        background: linear-gradient(180deg, #fffaf5 0%, var(--drp-primary-soft) 100%);
        color: var(--drp-primary-dark);
        font-weight: 700;
        font-size: 0.7rem;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        white-space: nowrap;
        border-bottom: 2px solid var(--drp-border, #fed7aa) !important;
        border-top: none !important;
        vertical-align: middle;
        padding: 0.65rem 0.75rem !important;
    }
    .drp-leads-table tbody td {
        padding: 0.55rem 0.75rem !important;
        vertical-align: middle;
        border-top: none !important;
        border-bottom: 1px solid #f1f5f9 !important;
        color: #334155;
    }
    .drp-leads-table tbody tr:last-child td { border-bottom: none !important; }
    .drp-leads-table tbody tr:hover td { background: #f8fafc; }
    .drp-lead-strong { font-weight: 700; color: #0f172a; }
    .drp-lead-muted { color: #64748b; font-size: 0.78rem; line-height: 1.35; }
    .drp-pay-badge {
        display: inline-block;
        font-size: 0.68rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.03em;
        padding: 0.2rem 0.45rem;
        border-radius: 999px;
    }
    .drp-pay-badge--paid {
        background: #ecfdf5;
        color: #047857;
        border: 1px solid #a7f3d0;
    }
    .drp-pay-badge--pending {
        background: #fffbeb;
        color: #b45309;
        border: 1px solid #fde68a;
    }
    .drp-comm-earned {
        font-weight: 800;
        color: var(--drp-primary-dark);
        font-variant-numeric: tabular-nums;
        white-space: nowrap;
    }
    .drp-view-btn {
        font-size: 0.74rem;
        font-weight: 700;
        padding: 0.28rem 0.55rem;
        border-radius: 8px;
    }
    .drp-detail-table th {
        width: 38%;
        background: #f8fafc;
        color: #64748b;
        font-weight: 700;
        font-size: 0.78rem;
        vertical-align: top;
    }
    .drp-detail-table td {
        font-size: 0.84rem;
        color: #111827;
        vertical-align: top;
    }
    .drp-detail-section {
        font-size: 0.72rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        color: var(--drp-primary-dark);
        margin: 0.75rem 0 0.35rem;
        padding-bottom: 0.25rem;
        border-bottom: 1px solid #e2e8f0;
    }
    .drp-detail-section:first-child { margin-top: 0; }
    .drp-empty-box {
        border: 1px dashed #cbd5e1;
        border-radius: 10px;
        padding: 1rem;
        color: var(--drp-muted);
        font-size: 0.86rem;
        background: #f8fafc;
    }
    .drp-comm-type-badge {
        display: inline-block;
        font-size: 0.68rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.03em;
        padding: 0.18rem 0.42rem;
        border-radius: 999px;
        margin-right: 0.25rem;
    }
    .drp-comm-type-badge--fixed {
        background: #ecfdf5;
        color: #047857;
        border: 1px solid #a7f3d0;
    }
    .drp-comm-type-badge--percent {
        background: #fff5eb;
        color: #cf6413;
        border: 1px solid #fed7aa;
    }
    .drp-comm-rate {
        font-weight: 700;
        color: var(--drp-primary-dark);
        white-space: nowrap;
    }
</style>
@endpush

@section('content')
<div class="drp-leads-head">
    <div>
        <h2 class="drp-leads-title">
            <i class="fas fa-list-alt" aria-hidden="true"></i>
            Doctor consultation leads
        </h2>
        <p class="drp-leads-caption">
            All website consultation bookings for doctors assigned to you — patient details, service, booking amount,
            payment status, and your commission (fixed ₹ or % of booking).
        </p>
    </div>
    <span class="drp-leads-pill">{{ count($leadRows) }} total leads</span>
</div>

<div class="drp-leads-stats">
    <span class="drp-leads-pill">{{ $assignedDoctorCount ?? 0 }} doctors</span>
    <span class="drp-leads-pill drp-leads-pill--paid">{{ $paidLeadsCount ?? 0 }} paid</span>
    <span class="drp-leads-pill drp-leads-pill--pending">{{ $pendingLeadsCount ?? 0 }} pending</span>
    <span class="drp-leads-pill">Commission earned: ₹{{ number_format($totalCommission ?? 0, 2) }}</span>
</div>

@if(($assignedDoctorCount ?? 0) === 0)
    <div class="drp-empty-box">No doctors assigned to you yet. Leads will appear here once doctors are linked and patients book consultations.</div>
@else
    <div class="mb-2 position-relative">
        <i class="fas fa-search drp-search-icon" aria-hidden="true"></i>
        <input type="text" id="drpLeadsSearch" class="form-control drp-search-input"
               placeholder="Search lead no., doctor, patient, service, city, payment…" autocomplete="off">
        <small class="text-muted d-block mt-1" id="drpLeadsSearchMeta">Showing {{ count($leadRows) }} of {{ count($leadRows) }}</small>
    </div>

    <div class="drp-leads-table-wrap">
        <table class="table table-hover drp-leads-table mb-0">
            <thead>
                <tr>
                    <th>Lead no.</th>
                    <th>Doctor</th>
                    <th>Patient</th>
                    <th>Service</th>
                    <th>Date / mode</th>
                    <th>City</th>
                    <th class="text-right">Booking (₹)</th>
                    <th>Payment</th>
                    <th>Commission rate</th>
                    <th class="text-right">You earn (₹)</th>
                    <th></th>
                </tr>
            </thead>
            <tbody id="drpLeadsTbody">
                @forelse($leadRows as $row)
                    <tr class="drp-lead-row" data-search="{{ e($row['search_blob'] ?? '') }}">
                        <td><span class="portal-lead-pill">{{ $row['lead_no'] }}</span></td>
                        <td>
                            <div class="drp-lead-strong">{{ $row['doctor_name'] }}</div>
                            <div class="drp-lead-muted">{{ $row['doctor_lead_id'] }}</div>
                        </td>
                        <td>
                            <div class="drp-lead-strong">{{ $row['customer_name'] }}</div>
                            <div class="drp-lead-muted">{{ $row['customer_mobile'] }}</div>
                        </td>
                        <td>
                            <div>{{ \Illuminate\Support\Str::limit($row['service'], 28) }}</div>
                            @if(!empty($row['sub_service']))
                                <div class="drp-lead-muted">{{ \Illuminate\Support\Str::limit($row['sub_service'], 30) }}</div>
                            @endif
                        </td>
                        <td class="drp-lead-muted text-nowrap">
                            {{ $row['appointment_date'] }}<br>
                            <span class="portal-accent-badge">{{ $row['mode_short'] }}</span>
                        </td>
                        <td>{{ \Illuminate\Support\Str::limit($row['customer_city'], 18) }}</td>
                        <td class="text-right text-nowrap">{{ $row['booking_amount_label'] }}</td>
                        <td>
                            <span class="drp-pay-badge drp-pay-badge--{{ $row['payment_status'] === 'paid' ? 'paid' : 'pending' }}">
                                {{ $row['payment_status_label'] }}
                            </span>
                        </td>
                        <td>
                            @if(($row['commission_rate'] ?? '—') === '—')
                                <span class="text-muted">—</span>
                            @else
                                <span class="drp-comm-type-badge drp-comm-type-badge--{{ ($row['commission_type'] ?? 'fixed') === 'percent' ? 'percent' : 'fixed' }}">
                                    {{ ($row['commission_type'] ?? 'fixed') === 'percent' ? '%' : '₹ Fix' }}
                                </span>
                                <span class="drp-comm-rate">{{ $row['commission_rate'] }}</span>
                            @endif
                        </td>
                        <td class="text-right">
                            <span class="drp-comm-earned">{{ $row['commission_earned_label'] }}</span>
                        </td>
                        <td class="text-nowrap">
                            <button type="button" class="btn btn-sm btn-primary drp-view-btn"
                                    data-toggle="modal" data-target="#drpLeadDetailModal"
                                    data-lead="{{ e(json_encode($row)) }}">
                                View
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr id="drpLeadsEmptyRow">
                        <td colspan="11" class="text-center py-5 text-muted">No consultation leads yet for your assigned doctors.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endif

<div class="modal fade" id="drpLeadDetailModal" tabindex="-1" role="dialog" aria-labelledby="drpLeadDetailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="drpLeadDetailModalLabel">
                    Lead <span id="drpModalLeadNo" class="portal-lead-pill ml-1"></span>
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="drp-detail-section">Lead &amp; booking</div>
                <table class="table table-sm table-bordered drp-detail-table mb-2">
                    <tbody>
                        <tr><th>Lead reference</th><td id="drpDLeadNo"></td></tr>
                        <tr><th>Booking ID</th><td id="drpDBookingId"></td></tr>
                        <tr><th>Booked on</th><td id="drpDBookedAt"></td></tr>
                        <tr><th>Payment status</th><td id="drpDPayment"></td></tr>
                        <tr><th>Paid at</th><td id="drpDPaidAt"></td></tr>
                    </tbody>
                </table>

                <div class="drp-detail-section">Doctor</div>
                <table class="table table-sm table-bordered drp-detail-table mb-2">
                    <tbody>
                        <tr><th>Doctor name</th><td id="drpDDoctorName"></td></tr>
                        <tr><th>Doctor lead ID</th><td id="drpDDoctorLead"></td></tr>
                        <tr><th>Doctor city</th><td id="drpDDoctorCity"></td></tr>
                    </tbody>
                </table>

                <div class="drp-detail-section">Patient</div>
                <table class="table table-sm table-bordered drp-detail-table mb-2">
                    <tbody>
                        <tr><th>Patient name</th><td id="drpDPatientName"></td></tr>
                        <tr><th>Mobile</th><td id="drpDPatientMobile"></td></tr>
                        <tr><th>City</th><td id="drpDPatientCity"></td></tr>
                        <tr><th>Address</th><td id="drpDPatientAddress"></td></tr>
                    </tbody>
                </table>

                <div class="drp-detail-section">Consultation</div>
                <table class="table table-sm table-bordered drp-detail-table mb-2">
                    <tbody>
                        <tr><th>Service</th><td id="drpDService"></td></tr>
                        <tr><th>Sub-service</th><td id="drpDSubService"></td></tr>
                        <tr><th>Tags</th><td id="drpDTags"></td></tr>
                        <tr><th>Mode</th><td id="drpDMode"></td></tr>
                        <tr><th>Appointment date</th><td id="drpDApptDate"></td></tr>
                        <tr><th>Time</th><td id="drpDApptTime"></td></tr>
                        <tr><th>Duration</th><td id="drpDDuration"></td></tr>
                    </tbody>
                </table>

                <div class="drp-detail-section">Amount &amp; your commission</div>
                <table class="table table-sm table-bordered drp-detail-table mb-0">
                    <tbody>
                        <tr><th>Booking amount (₹)</th><td id="drpDBookingAmt"></td></tr>
                        <tr><th>Your commission rate</th><td id="drpDCommRate"></td></tr>
                        <tr><th>Commission calculation</th><td id="drpDCommCalc"></td></tr>
                        <tr><th>You earn (₹)</th><td id="drpDCommEarned" class="font-weight-bold text-drp-accent"></td></tr>
                    </tbody>
                </table>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(function () {
    var $search = $('#drpLeadsSearch');
    var $rows = $('.drp-lead-row');
    var $meta = $('#drpLeadsSearchMeta');
    var total = $rows.length;

    function filterRows() {
        var q = ($search.val() || '').toLowerCase().trim();
        var shown = 0;
        $rows.each(function () {
            var blob = ($(this).data('search') || '').toString().toLowerCase();
            var match = !q || blob.indexOf(q) !== -1;
            $(this).toggle(match);
            if (match) shown++;
        });
        if ($meta.length) {
            $meta.text('Showing ' + shown + ' of ' + total);
        }
    }

    $search.on('input', filterRows);

    $('#drpLeadDetailModal').on('show.bs.modal', function (e) {
        var raw = $(e.relatedTarget).attr('data-lead');
        if (!raw) return;
        var row;
        try { row = JSON.parse(raw); } catch (err) { return; }

        $('#drpModalLeadNo').text(row.lead_no || '—');
        $('#drpDLeadNo').text(row.lead_no || '—');
        $('#drpDBookingId').text(row.id ? ('#' + row.id) : '—');
        $('#drpDBookedAt').text(row.booked_at || '—');
        $('#drpDPayment').text(row.payment_status_label || '—');
        $('#drpDPaidAt').text(row.paid_at || '—');

        $('#drpDDoctorName').text(row.doctor_name || '—');
        $('#drpDDoctorLead').text(row.doctor_lead_id || '—');
        $('#drpDDoctorCity').text(row.doctor_city || '—');

        $('#drpDPatientName').text(row.customer_name || '—');
        $('#drpDPatientMobile').text(row.customer_mobile || '—');
        $('#drpDPatientCity').text(row.customer_city || '—');
        $('#drpDPatientAddress').text(row.customer_address || '—');

        $('#drpDService').text(row.service || '—');
        $('#drpDSubService').text(row.sub_service || '—');
        $('#drpDTags').text(row.tags_label || '—');
        $('#drpDMode').text(row.mode_label || row.mode_short || '—');
        $('#drpDApptDate').text(row.appointment_date || '—');
        $('#drpDApptTime').text(row.appointment_time || '—');
        $('#drpDDuration').text(row.duration || '—');

        $('#drpDBookingAmt').text(row.booking_amount_label || '—');
        var rateHtml = '—';
        if (row.commission_rate && row.commission_rate !== '—') {
            var typeBadge = (row.commission_type === 'percent') ? '%' : '₹ Fix';
            rateHtml = '<span class="drp-comm-type-badge drp-comm-type-badge--' + (row.commission_type === 'percent' ? 'percent' : 'fixed') + '">' + typeBadge + '</span> ' + row.commission_rate;
        }
        $('#drpDCommRate').html(rateHtml);
        $('#drpDCommCalc').text(row.commission_calc || '—');
        $('#drpDCommEarned').text(row.commission_earned_label || '—');
    });
});
</script>
@endpush
