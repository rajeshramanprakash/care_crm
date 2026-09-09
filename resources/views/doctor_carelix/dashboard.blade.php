@extends('doctor_carelix.layouts.app')
@section('title', 'Dashboard | Doctor')

@section('main')
<div class="content-wrapper pb-5 dr-dashboard-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-0">
                <div class="col-12">
                    <h1 class="m-0 dr-page-title">Doctor Dashboard</h1>
                </div>
            </div>
        </div>
    </section>
    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-lg-8 mb-3 mb-lg-0">
                    <div class="card dr-hero-card h-100">
                        <div class="card-body">
                            <p class="dr-welcome mb-2">Welcome, {{ $doctorName }}</p>
                            <p class="dr-welcome-subtitle mb-0">Your registration is approved. Use <strong>Personal Details</strong> and <strong>Bank Account Details</strong> from sidebar to keep your profile complete and booking-ready.</p>
                            <div class="dr-quick-links mt-3">
                                <a href="{{ route('doctor_portal.personal-details') }}" class="btn btn-sm dr-btn-light">
                                    <i class="fas fa-user mr-1"></i>Personal Details
                                </a>
                                <a href="{{ route('doctor_portal.bank-details') }}" class="btn btn-sm dr-btn-light">
                                    <i class="fas fa-university mr-1"></i>Bank Account Details
                                </a>
                                <a href="{{ route('doctor_portal.availability') }}" class="btn btn-sm dr-btn-light">
                                    <i class="fas fa-calendar-alt mr-1"></i>Availability
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="card dr-account-card h-100">
                        <div class="card-header dr-section-title"><strong>Account Summary</strong></div>
                        <div class="card-body">
                            <div class="dr-meta-item">
                                <span class="dr-meta-label">Mobile</span>
                                <span class="dr-meta-value">{{ $doctor->mobile ?? $doctor->contact_no ?? '—' }}</span>
                            </div>
                            <div class="dr-meta-item">
                                <span class="dr-meta-label">Refer lead commission</span>
                                <span class="dr-meta-value">{{ number_format($portalLeadCommissionPercent ?? $doctor->portalLeadCommissionPercent(), 2) }}% <span class="dr-meta-hint">per lead</span></span>
                            </div>
                            <div class="dr-meta-item mb-0">
                                <span class="dr-meta-label">Status</span>
                                <span class="badge dr-status-badge">{{ ucfirst($doctor->approval_status ?? 'approved') }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

@push('styles')
<style>
    .dr-dashboard-wrapper {
        background: #f5f7fb;
    }
    .dr-page-title {
        font-size: 1.45rem;
        font-weight: 700;
        color: #1f2937;
    }
    .dr-hero-card,
    .dr-account-card {
        border: 0;
        border-radius: 14px;
        overflow: hidden;
        box-shadow: 0 10px 28px rgba(15, 23, 42, 0.08);
    }
    .dr-hero-card {
        background: linear-gradient(105deg, #f7941d 0%, #ff7a18 100%);
        color: #fff;
    }
    .dr-hero-card .card-body {
        padding: 1.25rem;
    }
    .dr-welcome {
        font-size: 1.35rem;
        font-weight: 700;
        line-height: 1.35;
    }
    .dr-welcome-subtitle {
        opacity: 0.95;
        line-height: 1.55;
        max-width: 92%;
    }
    .dr-quick-links {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
    }
    .dr-btn-light {
        background: rgba(255, 255, 255, 0.18);
        color: #fff;
        border: 1px solid rgba(255, 255, 255, 0.3);
        border-radius: 8px;
        font-weight: 600;
        padding: 0.36rem 0.7rem;
    }
    .dr-btn-light:hover {
        color: #fff;
        background: rgba(255, 255, 255, 0.26);
    }
    .dr-account-card .card-body {
        padding: 1.1rem 1rem;
        background: #fff;
    }
    .dr-section-title {
        border-bottom: 1px solid #e5e7eb;
        background: #f8fafc;
        color: #334155;
        font-size: 0.95rem;
        font-weight: 700;
        padding: 0.8rem 1rem;
    }
    .dr-meta-item {
        display: flex;
        flex-direction: column;
        gap: 0.28rem;
        padding-bottom: 0.85rem;
        margin-bottom: 0.85rem;
        border-bottom: 1px solid #f1f5f9;
    }
    .dr-meta-label {
        font-size: 0.76rem;
        text-transform: uppercase;
        letter-spacing: 0.28px;
        color: #64748b;
        font-weight: 700;
    }
    .dr-meta-value {
        color: #0f172a;
        font-weight: 600;
    }
    .dr-meta-hint {
        font-size: 0.78rem;
        font-weight: 500;
        color: #64748b;
    }
    .dr-status-badge {
        width: fit-content;
        background: rgba(34, 197, 94, 0.16);
        color: #15803d;
        border-radius: 999px;
        font-size: 0.78rem;
        font-weight: 700;
        padding: 0.42rem 0.75rem;
    }
</style>
@endpush
@endsection
