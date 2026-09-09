@extends('doctor_referral.layouts.app')

@section('page_title', 'Dashboard')

@push('styles')
<style>
    .drp-stat-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 0.75rem;
        margin-bottom: 1.25rem;
    }
    .drp-stat-card {
        background: linear-gradient(180deg, #fff 0%, var(--drp-primary-soft) 100%);
        border: 1px solid var(--drp-border, #fed7aa);
        border-radius: 12px;
        padding: 0.85rem 1rem;
    }
    .drp-stat-label {
        font-size: 0.72rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        color: var(--drp-muted);
        margin-bottom: 0.25rem;
    }
    .drp-stat-value {
        font-size: 1.2rem;
        font-weight: 800;
        color: var(--drp-primary-dark);
        line-height: 1.2;
    }
    .drp-welcome-card {
        background: linear-gradient(135deg, var(--drp-primary-soft) 0%, #fff 55%);
        border: 1px solid var(--drp-border, #fed7aa);
        border-radius: 12px;
        padding: 1rem 1.1rem;
        margin-bottom: 1.25rem;
    }
    .drp-welcome-title {
        font-size: 1.1rem;
        font-weight: 800;
        color: var(--drp-text);
        margin-bottom: 0.35rem;
    }
    .drp-welcome-meta {
        color: var(--drp-muted);
        font-size: 0.84rem;
        margin-bottom: 0;
    }
    .drp-welcome-meta strong { color: var(--drp-text); }
    .drp-section-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
        margin-bottom: 0.85rem;
    }
    .drp-section-title {
        color: var(--drp-text);
        border-bottom: 2px solid var(--drp-border, #fed7aa);
        padding-bottom: 0.45rem;
        margin-bottom: 0;
        font-weight: 800;
    }
    .drp-section-hint {
        font-size: 0.78rem;
        color: var(--drp-muted);
        margin-bottom: 0.85rem;
        line-height: 1.45;
    }
    .drp-doctor-card {
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        background: #fff;
        margin-bottom: 0.85rem;
        overflow: hidden;
    }
    .drp-doctor-head {
        background: linear-gradient(180deg, #fffaf5 0%, #fff 100%);
        border-bottom: 1px solid #e2e8f0;
        padding: 0.85rem 1rem;
    }
    .drp-doctor-name {
        font-size: 0.98rem;
        font-weight: 800;
        color: var(--drp-text);
        margin-bottom: 0.2rem;
    }
    .drp-doctor-sub {
        font-size: 0.78rem;
        color: var(--drp-muted);
    }
    .drp-doctor-sub strong { color: var(--drp-primary-dark); }
    .drp-mode-chips {
        display: flex;
        flex-wrap: wrap;
        gap: 0.35rem;
        margin-top: 0.45rem;
    }
    .drp-service-table thead th {
        font-size: 0.7rem;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        white-space: nowrap;
        background: #f8fafc;
        color: var(--drp-primary-dark);
        border-bottom: 1px solid #e2e8f0 !important;
    }
    .drp-service-table tbody td {
        font-size: 0.82rem;
        vertical-align: middle;
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
    .drp-comm-est {
        font-weight: 800;
        color: #047857;
        white-space: nowrap;
    }
    .drp-comm-est-muted {
        color: var(--drp-muted);
        font-size: 0.75rem;
    }
    .drp-tag {
        display: inline-block;
        font-size: 0.68rem;
        padding: 0.12rem 0.38rem;
        border-radius: 999px;
        background: #f1f5f9;
        color: #475569;
        margin-right: 0.2rem;
        margin-bottom: 0.15rem;
    }
    .drp-empty-box {
        border: 1px dashed #cbd5e1;
        border-radius: 10px;
        padding: 1rem;
        color: var(--drp-muted);
        font-size: 0.86rem;
        background: #f8fafc;
    }
    .drp-earn-calc {
        font-size: 0.74rem;
        color: var(--drp-muted);
        line-height: 1.35;
        max-width: 180px;
    }
    .drp-earn-amount {
        font-weight: 800;
        color: var(--drp-primary-dark);
        font-size: 0.92rem;
        white-space: nowrap;
    }
    @media (max-width: 767.98px) {
        .drp-hide-sm { display: none !important; }
        .drp-service-table-wrap,
        .drp-dash-table-wrap {
            max-height: none;
        }
    }
</style>
@endpush

@section('content')
<div class="drp-welcome-card">
    <div class="drp-welcome-title">Welcome, {{ $refUser->name }}</div>
    <p class="drp-welcome-meta">
        <strong>Mobile:</strong> {{ $refUser->mobile }}
        <span class="mx-1">·</span>
        <strong>Role:</strong> Doctor referral partner
    </p>
    <p class="drp-section-hint mt-2 mb-0">
        Below you can see every doctor and service linked to you, your commission rate (fixed ₹ or % of booking),
        and each paid consultation with the amount you earned.
    </p>
</div>

<div class="drp-stat-grid">
    <div class="drp-stat-card">
        <div class="drp-stat-label">Doctors assigned</div>
        <div class="drp-stat-value">{{ $assignedDoctorCount ?? 0 }}</div>
    </div>
    <div class="drp-stat-card">
        <div class="drp-stat-label">Paid bookings</div>
        <div class="drp-stat-value">{{ $paidBookingsCount ?? 0 }}</div>
    </div>
    <div class="drp-stat-card">
        <div class="drp-stat-label">Total commission earned</div>
        <div class="drp-stat-value">₹{{ number_format($totalCommission ?? 0, 2) }}</div>
    </div>
</div>

<div class="drp-section-head">
    <h2 class="h6 drp-section-title flex-grow-1">Your assigned doctors &amp; services</h2>
</div>
<p class="drp-section-hint">
    For each doctor assigned to you, all active services and consultation modes are listed with the patient booking fee
    and your commission (fixed amount or percentage set by admin).
</p>

@if(($assignedDoctorCount ?? 0) === 0)
    <div class="drp-empty-box mb-4">No doctors assigned to you yet. Contact Carelix admin to link doctors to your account.</div>
@else
    @foreach($assignedDoctorCards as $doctorCard)
        <div class="drp-doctor-card">
            <div class="drp-doctor-head">
                <div class="drp-doctor-name">{{ $doctorCard['name'] }}</div>
                <div class="drp-doctor-sub">
                    <strong>Lead:</strong> {{ $doctorCard['lead_id'] }}
                    @if(!empty($doctorCard['main_service']))
                        <span class="mx-1">·</span> <strong>Service:</strong> {{ $doctorCard['main_service'] }}
                    @endif
                    @if(!empty($doctorCard['city']))
                        <span class="mx-1">·</span> <strong>City:</strong> {{ $doctorCard['city'] }}
                    @endif
                </div>
                @if(!empty($doctorCard['enabled_modes']))
                    <div class="drp-mode-chips">
                        @foreach($doctorCard['enabled_modes'] as $modeLabel)
                            <span class="portal-accent-badge">{{ $modeLabel }}</span>
                        @endforeach
                    </div>
                @endif
            </div>

            @if(empty($doctorCard['service_rows']))
                <div class="p-3">
                    <p class="text-muted small mb-0">No consultation services or modes configured for this doctor yet.</p>
                </div>
            @else
                <div class="table-responsive drp-service-table-wrap px-2 pb-2 pt-1">
                    <table class="table table-sm table-hover mb-0 drp-service-table">
                        <thead>
                            <tr>
                                <th>Service</th>
                                <th>Mode</th>
                                <th class="text-right">Patient pays (₹)</th>
                                <th>Your commission rate</th>
                                <th class="text-right">Est. commission (₹)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($doctorCard['service_rows'] as $svc)
                                <tr>
                                    <td>
                                        <div class="font-weight-bold">{{ \Illuminate\Support\Str::limit($svc['service_name'], 40) }}</div>
                                        @if(!empty($svc['tags']))
                                            <div class="mt-1">
                                                @foreach(array_slice($svc['tags'], 0, 3) as $tag)
                                                    <span class="drp-tag">{{ $tag }}</span>
                                                @endforeach
                                            </div>
                                        @endif
                                    </td>
                                    <td><span class="portal-accent-badge">{{ $svc['mode_label'] }}</span></td>
                                    <td class="text-right">
                                        {{ $svc['patient_fee'] !== null ? number_format((float) $svc['patient_fee'], 2) : '—' }}
                                    </td>
                                    <td>
                                        @if(($svc['commission_label'] ?? '—') === '—')
                                            <span class="text-muted">Not set</span>
                                        @else
                                            <span class="drp-comm-type-badge drp-comm-type-badge--{{ ($svc['commission_type'] ?? 'fixed') === 'percent' ? 'percent' : 'fixed' }}">
                                                {{ ($svc['commission_type'] ?? 'fixed') === 'percent' ? '%' : '₹ Fix' }}
                                            </span>
                                            <span class="drp-comm-rate">{{ $svc['commission_label'] }}</span>
                                            @if(($svc['commission_type'] ?? 'fixed') === 'percent')
                                                <div class="drp-comm-est-muted">of paid booking amount</div>
                                            @else
                                                <div class="drp-comm-est-muted">per paid booking</div>
                                            @endif
                                        @endif
                                    </td>
                                    <td class="text-right">
                                        @if($svc['estimated_commission'] !== null && ($svc['commission_label'] ?? '—') !== '—')
                                            <span class="drp-comm-est">₹{{ number_format((float) $svc['estimated_commission'], 2) }}</span>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            @if(empty($doctorCard['has_commission_config']))
                <div class="px-3 pb-3">
                    <div class="alert alert-info py-2 small mb-0">
                        <i class="fas fa-info-circle mr-1"></i>
                        Commission rates are not configured for this doctor yet. Ask admin to set fixed ₹ or % per mode.
                    </div>
                </div>
            @endif
        </div>
    @endforeach
@endif

<div class="drp-section-head mt-2">
    <h2 class="h6 drp-section-title flex-grow-1">Commission earnings</h2>
    <a href="{{ route('doctor_referral.leads') }}" class="btn btn-sm btn-primary">
        <i class="fas fa-list-alt mr-1"></i> View all leads
    </a>
</div>
<p class="drp-section-hint">
    Each row is a <strong>paid</strong> website consultation for a doctor linked to you.
    Commission is calculated from your rate (fixed ₹ or % of booking) when the patient completes payment.
</p>

@if(($assignedDoctorCount ?? 0) === 0)
    <div class="drp-empty-box">No doctors assigned — earnings will appear here after assignments and paid bookings.</div>
@elseif(empty($referredRows))
    <div class="drp-empty-box">No paid bookings yet. Commission will appear here after patients complete payment.</div>
@else
    <div class="table-responsive drp-dash-table-wrap border rounded mb-0 drp-table-border">
        <table class="table table-hover table-sm mb-0">
            <thead>
                <tr>
                    <th>Lead no.</th>
                    <th>Doctor</th>
                    <th>Service</th>
                    <th class="drp-hide-sm">Date</th>
                    <th class="drp-hide-sm">City</th>
                    <th>Mode</th>
                    <th class="text-right">Booking (₹)</th>
                    <th>Commission rate</th>
                    <th class="text-right">You earned (₹)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($referredRows as $row)
                    <tr>
                        <td><span class="portal-lead-pill">{{ $row['lead_no'] }}</span></td>
                        <td>
                            <div class="font-weight-bold">{{ $row['dr_name'] }}</div>
                            <div class="small text-muted drp-hide-sm">{{ $row['service_date'] }} · {{ \Illuminate\Support\Str::limit($row['city'], 18) }}</div>
                        </td>
                        <td title="{{ $row['service'] }}">{{ \Illuminate\Support\Str::limit($row['service'], 32) }}</td>
                        <td class="small text-muted drp-hide-sm">{{ $row['service_date'] }}</td>
                        <td class="drp-hide-sm">{{ \Illuminate\Support\Str::limit($row['city'], 20) }}</td>
                        <td><span class="portal-accent-badge">{{ $row['visit_type'] }}</span></td>
                        <td class="text-right">{{ $row['amt'] !== null ? number_format($row['amt'], 2) : '—' }}</td>
                        <td>
                            @if(($row['commission_rate'] ?? '—') === '—')
                                <span class="text-muted">—</span>
                            @else
                                <span class="drp-comm-type-badge drp-comm-type-badge--{{ ($row['commission_type'] ?? 'fixed') === 'percent' ? 'percent' : 'fixed' }}">
                                    {{ ($row['commission_type'] ?? 'fixed') === 'percent' ? '%' : '₹ Fix' }}
                                </span>
                                <span class="drp-comm-rate">{{ $row['commission_rate'] }}</span>
                                @if(!empty($row['commission_calc']) && $row['commission_calc'] !== '—')
                                    <div class="drp-earn-calc">{{ $row['commission_calc'] }}</div>
                                @endif
                            @endif
                        </td>
                        <td class="text-right">
                            <span class="drp-earn-amount">₹{{ number_format($row['commission'], 2) }}</span>
                        </td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="bg-light">
                    <th colspan="8" class="text-right font-weight-bold">Total earned</th>
                    <th class="text-right font-weight-bold text-drp-accent">₹{{ number_format($totalCommission ?? 0, 2) }}</th>
                </tr>
            </tfoot>
        </table>
    </div>
@endif
@endsection
