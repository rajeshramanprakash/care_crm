@extends('vendor.layouts.app')
@section('title', $page_heading ?? 'Dashboard | Vendor')

@section('header-css')
<style>
    .vd-stat-card {
        border: 0;
        border-radius: 14px;
        overflow: hidden;
        box-shadow: 0 8px 24px rgba(15, 23, 42, 0.08);
        transition: transform 0.2s ease;
    }
    .vd-stat-card:hover { transform: translateY(-2px); }
    .vd-stat-card .inner h3 {
        font-size: 1.65rem;
        font-weight: 700;
    }
    .vd-welcome-card {
        border: 0;
        border-radius: 14px;
        background: linear-gradient(90deg, #f7941d 0%, #ff7a18 100%);
        color: #fff;
        box-shadow: 0 8px 24px rgba(247, 148, 29, 0.25);
    }
    .vd-welcome-card .card-body { padding: 1.25rem 1.5rem; }
    .vd-quick-link {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.85rem 1rem;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        background: #fff;
        color: #1f2937;
        text-decoration: none;
        transition: border-color 0.2s, box-shadow 0.2s;
        height: 100%;
    }
    .vd-quick-link:hover {
        border-color: #f7941d;
        box-shadow: 0 4px 14px rgba(247, 148, 29, 0.15);
        color: #1f2937;
        text-decoration: none;
    }
    .vd-quick-link i {
        width: 38px;
        height: 38px;
        border-radius: 10px;
        background: rgba(247, 148, 29, 0.12);
        color: #e8850f;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1rem;
    }
    .vd-table thead th {
        background: #f8fafc;
        font-size: 0.82rem;
        text-transform: uppercase;
        letter-spacing: 0.25px;
        color: #64748b;
        border-bottom: 1px solid #e5e7eb;
    }
</style>
@endsection

@section('main')
<div class="content-wrapper pb-5">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Dashboard</h1>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <div class="row mb-3">
                <div class="col-12">
                    <div class="card vd-welcome-card">
                        <div class="card-body">
                            <h4 class="mb-1">Welcome, {{ $vendorName ?? 'Vendor' }}</h4>
                            <p class="mb-0 opacity-90">
                                @if(isset($activeDeployments) && $activeDeployments->isNotEmpty())
                                    You have {{ $activeDeployments->count() }} active deployment(s). Manage leads, payments and chats from the menu.
                                @else
                                    No active deployments at the moment. Update your profile and check assigned leads when work is available.
                                @endif
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mb-4">
                <div class="col-lg-4 col-md-4 col-sm-6 mb-3 mb-md-0">
                    <div class="small-box vd-stat-card bg-info">
                        <div class="inner">
                            <h3>₹{{ number_format($totalPayments ?? 0, 0) }}</h3>
                            <p>Total Payments</p>
                        </div>
                        <div class="icon"><i class="fas fa-rupee-sign"></i></div>
                        <a href="{{ route('vendor.payment-details') }}" class="small-box-footer">View details <i class="fas fa-arrow-circle-right"></i></a>
                    </div>
                </div>
                <div class="col-lg-4 col-md-4 col-sm-6 mb-3 mb-md-0">
                    <div class="small-box vd-stat-card bg-success">
                        <div class="inner">
                            <h3>₹{{ number_format($completedPayments ?? 0, 0) }}</h3>
                            <p>Completed</p>
                        </div>
                        <div class="icon"><i class="fas fa-check-circle"></i></div>
                        <a href="{{ route('vendor.payment-details') }}" class="small-box-footer">View details <i class="fas fa-arrow-circle-right"></i></a>
                    </div>
                </div>
                <div class="col-lg-4 col-md-4 col-sm-12">
                    <div class="small-box vd-stat-card bg-warning">
                        <div class="inner">
                            <h3>₹{{ number_format($pendingPayments ?? 0, 0) }}</h3>
                            <p>Pending</p>
                        </div>
                        <div class="icon"><i class="fas fa-clock"></i></div>
                        <a href="{{ route('vendor.payment-details') }}" class="small-box-footer">View details <i class="fas fa-arrow-circle-right"></i></a>
                    </div>
                </div>
            </div>

            <div class="row mb-4">
                <div class="col-12">
                    <h5 class="mb-3 text-muted">Quick links</h5>
                </div>
                <div class="col-lg-3 col-md-4 col-6 mb-3">
                    <a href="{{ route('vendor.personal-details') }}" class="vd-quick-link">
                        <i class="fas fa-user"></i><span>Personal Details</span>
                    </a>
                </div>
                <div class="col-lg-3 col-md-4 col-6 mb-3">
                    <a href="{{ route('vendor.assigned-leads') }}" class="vd-quick-link">
                        <i class="fas fa-list-alt"></i><span>Assigned Leads</span>
                    </a>
                </div>
                <div class="col-lg-3 col-md-4 col-6 mb-3">
                    <a href="{{ route('vendor.customer-chats') }}" class="vd-quick-link">
                        <i class="fas fa-comments"></i><span>Customer Chats</span>
                    </a>
                </div>
                <div class="col-lg-3 col-md-4 col-6 mb-3">
                    <a href="{{ route('vendor.bank-details') }}" class="vd-quick-link">
                        <i class="fas fa-university"></i><span>Bank Details</span>
                    </a>
                </div>
            </div>

            @if(isset($activeDeployments) && $activeDeployments->isNotEmpty())
            <div class="card border-0 shadow-sm mb-4" style="border-radius: 14px;">
                <div class="card-header bg-white border-bottom">
                    <h3 class="card-title mb-0"><i class="fas fa-user-check text-warning mr-2"></i>Active Deployments</h3>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0 vd-table">
                            <thead>
                                <tr>
                                    <th>Lead</th>
                                    <th>From Date</th>
                                    <th>To Date</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($activeDeployments as $d)
                                <tr>
                                    <td>{{ optional($d->operationLead)->lead_id ?? 'N/A' }}</td>
                                    <td>{{ $d->deployment_from_date ? \Carbon\Carbon::parse($d->deployment_from_date)->format('d M Y') : 'N/A' }}</td>
                                    <td>{{ $d->deployment_to_date ? \Carbon\Carbon::parse($d->deployment_to_date)->format('d M Y') : 'N/A' }}</td>
                                    <td><span class="badge badge-primary">{{ $d->deployment_status ?? 'N/A' }}</span></td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            @endif

            @if(isset($deployments) && $deployments->isNotEmpty())
            <div class="card border-0 shadow-sm" style="border-radius: 14px;">
                <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
                    <h3 class="card-title mb-0"><i class="fas fa-briefcase text-warning mr-2"></i>Recent Deployments</h3>
                    <a href="{{ route('vendor.assigned-leads') }}" class="btn btn-sm btn-outline-warning">View all</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0 vd-table">
                            <thead>
                                <tr>
                                    <th>Lead</th>
                                    <th>Customer</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($deployments->take(8) as $d)
                                <tr>
                                    <td>{{ optional($d->operationLead)->lead_id ?? 'N/A' }}</td>
                                    <td>{{ optional($d->operationLead)->customer_name ?? 'N/A' }}</td>
                                    <td><span class="badge badge-secondary">{{ $d->deployment_status ?? 'N/A' }}</span></td>
                                    <td>{{ $d->created_at ? $d->created_at->format('d M Y') : 'N/A' }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            @endif
        </div>
    </section>
</div>
@endsection
