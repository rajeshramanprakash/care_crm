@extends($layout)

@section('title', 'Payments')

@section('header-css')
@include('easebuzz_payments.partials.scroll-fix')
<style>
    .ebp-page { --ebp-accent: #fe992e; --ebp-border: #e8ecf1; }
    .ebp-page .ebp-stat-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 1rem;
        margin-bottom: 1.25rem;
    }
    @media (max-width: 768px) {
        .ebp-page .ebp-stat-grid { grid-template-columns: 1fr; }
    }
    .ebp-page .ebp-stat-card {
        background: #fff;
        border-radius: 12px;
        border: 1px solid var(--ebp-border);
        padding: 1rem 1.25rem;
        box-shadow: 0 2px 12px rgba(0,0,0,0.04);
    }
    .ebp-page .ebp-stat-label { font-size: 0.8rem; color: #6c757d; text-transform: uppercase; letter-spacing: 0.03em; }
    .ebp-page .ebp-stat-value { font-size: 1.5rem; font-weight: 700; color: #1a2340; }
    .ebp-page .table th { border-top: none; font-size: 0.8rem; text-transform: uppercase; color: #6c757d; }
    .ebp-page .badge-pending { background: #ffc107; color: #212529; }
    .ebp-page .badge-paid { background: #28a745; }
    .ebp-page .badge-failed { background: #dc3545; }
    .ebp-page .badge-expired { background: #6c757d; }
</style>
@endsection

@section('main')
<div class="content-wrapper ebp-scroll-outer ebp-page">
    <div class="ebp-scroll-inner">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2 align-items-center">
                <div class="col-sm-6">
                    <h1 class="m-0">Payments</h1>
                    <p class="text-muted mb-0 small">
                        Easebuzz payment links (valid 7 days)
                        @if(!empty($easebuzz_demo_mode))
                            <span class="badge badge-info ml-1">Demo</span>
                        @else
                            <span class="badge badge-success ml-1">Live</span>
                        @endif
                    </p>
                </div>
                <div class="col-sm-6 text-sm-right">
                    <a href="{{ route($route_prefix.'.payments.create') }}" class="btn btn-primary" style="background:#F07F28;border-color:#F07F28;">
                        <i class="fas fa-plus mr-1"></i> New payment
                    </a>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}
                    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                </div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}
                    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                </div>
            @endif

            <div class="ebp-stat-grid">
                <div class="ebp-stat-card">
                    <div class="ebp-stat-label">Pending</div>
                    <div class="ebp-stat-value">{{ $stats['pending'] }}</div>
                </div>
                <div class="ebp-stat-card">
                    <div class="ebp-stat-label">Paid</div>
                    <div class="ebp-stat-value">{{ $stats['paid'] }}</div>
                </div>
                <div class="ebp-stat-card">
                    <div class="ebp-stat-label">Total links</div>
                    <div class="ebp-stat-value">{{ $stats['total'] }}</div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <form method="get" class="form-inline flex-wrap">
                        <input type="text" name="q" class="form-control form-control-sm mr-2 mb-1" placeholder="Search name, email, txn…" value="{{ $q ?? '' }}">
                        <select name="status" class="form-control form-control-sm mr-2 mb-1">
                            <option value="">All statuses</option>
                            <option value="pending" @selected(($status ?? '') === 'pending')>Pending</option>
                            <option value="paid" @selected(($status ?? '') === 'paid')>Paid</option>
                            <option value="failed" @selected(($status ?? '') === 'failed')>Failed</option>
                            <option value="expired" @selected(($status ?? '') === 'expired')>Expired</option>
                        </select>
                        <button type="submit" class="btn btn-sm btn-outline-secondary mb-1">Filter</button>
                    </form>
                </div>
                <div class="card-body table-responsive p-0">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Customer</th>
                                <th>Amount</th>
                                <th>Status</th>
                                @if($is_admin)<th>Created by</th>@endif
                                <th>Expires</th>
                                <th>Created</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($payments as $p)
                                <tr>
                                    <td>{{ $p->id }}</td>
                                    <td>
                                        <strong>{{ $p->customer_name }}</strong><br>
                                        <small class="text-muted">{{ $p->phone }} · {{ $p->email }}</small>
                                    </td>
                                    <td>₹{{ number_format($p->amount, 2) }}</td>
                                    <td><span class="badge {{ $p->statusBadgeClass() }}">{{ $p->statusLabel() }}</span></td>
                                    @if($is_admin)
                                        <td>{{ $p->createdBy?->name ?? '—' }}</td>
                                    @endif
                                    <td>{{ $p->expire_at?->format('d M Y') ?? '—' }}</td>
                                    <td>{{ $p->created_at->format('d M Y H:i') }}</td>
                                    <td>
                                        <a href="{{ route($route_prefix.'.payments.show', $p) }}" class="btn btn-sm btn-outline-primary">View</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ $is_admin ? 8 : 7 }}" class="text-center text-muted py-4">No payment links yet. Create one to get started.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($payments->hasPages())
                    <div class="card-footer">{{ $payments->links() }}</div>
                @endif
            </div>
        </div>
    </section>
    </div>
</div>
@endsection
