@extends($layout)

@section('title', 'Payment #'.$payment->id)

@section('header-css')
@include('easebuzz_payments.partials.scroll-fix')
<style>
    .ebp-show {
        --ebp-accent: #F07F28;
        --ebp-accent-soft: #fff4eb;
        --ebp-border: #e8ecf1;
        --ebp-text: #1a2340;
        --ebp-muted: #6c757d;
    }
    .ebp-show .ebp-hero {
        background: linear-gradient(135deg, #fff 0%, #fff8f3 100%);
        border: 1px solid var(--ebp-border);
        border-radius: 14px;
        padding: 1.35rem 1.5rem;
        margin-bottom: 1.25rem;
        box-shadow: 0 4px 20px rgba(26, 35, 64, 0.06);
    }
    .ebp-show .ebp-hero-top {
        display: flex;
        flex-wrap: wrap;
        align-items: flex-start;
        justify-content: space-between;
        gap: 1rem;
    }
    .ebp-show .ebp-hero-title {
        font-size: 1.35rem;
        font-weight: 700;
        color: var(--ebp-text);
        margin: 0 0 0.35rem;
    }
    .ebp-show .ebp-hero-meta {
        font-size: 0.85rem;
        color: var(--ebp-muted);
    }
    .ebp-show .ebp-amount {
        font-size: 2rem;
        font-weight: 800;
        color: var(--ebp-accent);
        line-height: 1.1;
        text-align: right;
    }
    .ebp-show .ebp-amount small {
        display: block;
        font-size: 0.75rem;
        font-weight: 600;
        color: var(--ebp-muted);
        margin-top: 0.2rem;
    }
    .ebp-show .ebp-status {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        padding: 0.35rem 0.75rem;
        border-radius: 999px;
        font-size: 0.8rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.04em;
    }
    .ebp-show .ebp-status-pending { background: #fff3cd; color: #856404; }
    .ebp-show .ebp-status-paid { background: #d4edda; color: #155724; }
    .ebp-show .ebp-status-failed { background: #f8d7da; color: #721c24; }
    .ebp-show .ebp-status-expired { background: #e9ecef; color: #495057; }
    .ebp-show .ebp-card {
        background: #fff;
        border: 1px solid var(--ebp-border);
        border-radius: 14px;
        box-shadow: 0 2px 14px rgba(0,0,0,0.04);
        margin-bottom: 1.25rem;
        overflow: hidden;
    }
    .ebp-show .ebp-card-head {
        padding: 0.9rem 1.25rem;
        border-bottom: 1px solid var(--ebp-border);
        font-weight: 600;
        color: var(--ebp-text);
        font-size: 0.95rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    .ebp-show .ebp-card-head i { color: var(--ebp-accent); width: 1.1rem; text-align: center; }
    .ebp-show .ebp-card-body { padding: 1.1rem 1.25rem; }
    .ebp-show .ebp-detail-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 0.85rem 1.25rem;
    }
    @media (max-width: 576px) {
        .ebp-show .ebp-detail-grid { grid-template-columns: 1fr; }
        .ebp-show .ebp-amount { text-align: left; }
    }
    .ebp-show .ebp-detail-item label {
        display: block;
        font-size: 0.72rem;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        color: var(--ebp-muted);
        margin-bottom: 0.15rem;
        font-weight: 600;
    }
    .ebp-show .ebp-detail-item span,
    .ebp-show .ebp-detail-item a {
        font-size: 0.95rem;
        color: var(--ebp-text);
        word-break: break-word;
    }
    .ebp-show .ebp-detail-item.full { grid-column: 1 / -1; }
    .ebp-show .ebp-txn {
        font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
        font-size: 0.82rem;
        background: #f4f6f9;
        padding: 0.35rem 0.55rem;
        border-radius: 6px;
        display: inline-block;
    }
    .ebp-show .ebp-link-box {
        display: flex;
        gap: 0.5rem;
        flex-wrap: wrap;
    }
    .ebp-show .ebp-link-box input {
        flex: 1;
        min-width: 200px;
        font-size: 0.82rem;
        border-radius: 8px;
        border: 1px solid var(--ebp-border);
        background: #f8f9fb;
    }
    .ebp-show .ebp-btn-accent {
        background: var(--ebp-accent);
        border-color: var(--ebp-accent);
        color: #fff;
        border-radius: 8px;
        font-weight: 600;
    }
    .ebp-show .ebp-btn-accent:hover {
        background: #e8892a;
        border-color: #e8892a;
        color: #fff;
    }
    .ebp-show .ebp-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
        align-items: center;
    }
    .ebp-show .ebp-verify-card {
        background: var(--ebp-accent-soft);
        border: 1px dashed #f5c99a;
        border-radius: 12px;
        padding: 1rem 1.15rem;
    }
    .ebp-show .ebp-verify-card p {
        font-size: 0.82rem;
        color: var(--ebp-muted);
        margin: 0.5rem 0 0;
    }
    .ebp-show .ebp-api-toggle {
        cursor: pointer;
        user-select: none;
    }
    .ebp-show .ebp-api-toggle:hover { color: var(--ebp-accent); }
    .ebp-show pre.ebp-api-pre {
        background: #1e2533;
        color: #e2e8f0;
        border-radius: 0 0 12px 12px;
        margin: 0;
        padding: 1rem;
        font-size: 0.75rem;
        max-height: 280px;
        overflow: auto;
    }
    .ebp-show .ebp-back-btn {
        border-radius: 8px;
        font-weight: 500;
    }
</style>
@endsection

@section('main')
@php
    $statusClass = match($payment->status) {
        'paid' => 'ebp-status-paid',
        'failed' => 'ebp-status-failed',
        'expired' => 'ebp-status-expired',
        default => $payment->isExpired() ? 'ebp-status-expired' : 'ebp-status-pending',
    };
    $easepayId = data_get($payment->easebuzz_verify_response, 'msg.easepayid')
        ?? data_get($payment->easebuzz_verify_response, 'data.easepayid');
@endphp
<div class="content-wrapper ebp-scroll-outer ebp-show">
    <div class="ebp-scroll-inner">
    <section class="content pt-3 mb-0">
        <div class="container-fluid">

            @foreach(['success' => 'success', 'error' => 'danger', 'warning' => 'warning', 'info' => 'info'] as $key => $class)
                @if(session($key))
                    <div class="alert alert-{{ $class }} alert-dismissible fade show shadow-sm">
                        {{ session($key) }}
                        <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                    </div>
                @endif
            @endforeach

            <div class="ebp-hero">
                <div class="ebp-hero-top">
                    <div>
                        <a href="{{ route($route_prefix.'.payments.index') }}" class="text-muted small d-inline-block mb-2 ebp-back-btn btn btn-sm btn-light border">
                            <i class="fas fa-arrow-left mr-1"></i> All payments
                        </a>
                        <h1 class="ebp-hero-title">Payment #{{ $payment->id }}</h1>
                        <div class="ebp-hero-meta d-flex flex-wrap align-items-center gap-2">
                            <span class="ebp-status {{ $statusClass }}">
                                <i class="fas fa-circle" style="font-size:0.45rem;"></i>
                                {{ $payment->statusLabel() }}
                            </span>
                            @if(!empty($easebuzz_demo_mode))
                                <span class="badge badge-info">Demo</span>
                            @endif
                            <span>Created {{ $payment->created_at->format('d M Y, h:i A') }}</span>
                        </div>
                    </div>
                    <div>
                        <div class="ebp-amount">
                            ₹{{ number_format($payment->amount, 2) }}
                            <small>INR</small>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-8">
                    <div class="ebp-card">
                        <div class="ebp-card-head">
                            <i class="fas fa-user"></i> Customer details
                        </div>
                        <div class="ebp-card-body">
                            <div class="ebp-detail-grid">
                                <div class="ebp-detail-item">
                                    <label>Name</label>
                                    <span>{{ $payment->customer_name }}</span>
                                </div>
                                <div class="ebp-detail-item">
                                    <label>Phone</label>
                                    <span><a href="tel:{{ $payment->phone }}">{{ $payment->phone }}</a></span>
                                </div>
                                <div class="ebp-detail-item">
                                    <label>Email</label>
                                    <span><a href="mailto:{{ $payment->email }}">{{ $payment->email }}</a></span>
                                </div>
                                @if($payment->message)
                                    <div class="ebp-detail-item full">
                                        <label>Message</label>
                                        <span>{{ $payment->message }}</span>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    @if($payment->payment_url)
                        <div class="ebp-card">
                            <div class="ebp-card-head">
                                <i class="fas fa-link"></i> Share payment link
                            </div>
                            <div class="ebp-card-body">
                                <p class="text-muted small mb-2">Send this link to the customer. Valid until {{ $payment->expire_at?->format('d M Y, h:i A') ?? '—' }}.</p>
                                <div class="ebp-link-box mb-3">
                                    <input type="text" class="form-control" id="paymentUrlInput" value="{{ $payment->payment_url }}" readonly>
                                    <button type="button" class="btn btn-outline-secondary" id="copyPaymentUrl">
                                        <i class="far fa-copy"></i> Copy
                                    </button>
                                    <a href="{{ $payment->payment_url }}" target="_blank" rel="noopener" class="btn btn-success">
                                        <i class="fas fa-external-link-alt"></i> Open
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>

                <div class="col-lg-4">
                    <div class="ebp-card">
                        <div class="ebp-card-head">
                            <i class="fas fa-receipt"></i> Transaction
                        </div>
                        <div class="ebp-card-body">
                            <div class="ebp-detail-item mb-3">
                                <label>Merchant txn ID</label>
                                <span class="ebp-txn">{{ $payment->merchant_txn }}</span>
                            </div>
                            @if($easepayId)
                                <div class="ebp-detail-item mb-3">
                                    <label>Easebuzz ID</label>
                                    <span class="ebp-txn">{{ $easepayId }}</span>
                                </div>
                            @endif
                            <div class="ebp-detail-item mb-3">
                                <label>Expires</label>
                                <span>{{ $payment->expire_at?->format('d M Y, h:i A') ?? '—' }}</span>
                            </div>
                            @if($is_admin && $payment->createdBy)
                                <div class="ebp-detail-item mb-3">
                                    <label>Created by</label>
                                    <span>{{ $payment->createdBy->name }}</span>
                                </div>
                            @endif
                            @if($payment->paid_at)
                                <div class="ebp-detail-item mb-3">
                                    <label>Paid at</label>
                                    <span class="text-success font-weight-bold">{{ $payment->paid_at->format('d M Y, h:i A') }}</span>
                                </div>
                            @endif
                            @if($payment->verified_at)
                                <div class="ebp-detail-item">
                                    <label>Last verified</label>
                                    <span>{{ $payment->verified_at->format('d M Y, h:i A') }}</span>
                                </div>
                            @endif
                        </div>
                    </div>

                    @if($payment->status !== \App\Models\EasebuzzPaymentLink::STATUS_PAID)
                        <div class="ebp-verify-card">
                            <strong class="d-block mb-1"><i class="fas fa-sync-alt text-warning"></i> Check payment</strong>
                            <form method="post" action="{{ route($route_prefix.'.payments.verify', $payment) }}">
                                @csrf
                                <button type="submit" class="btn ebp-btn-accent btn-block">
                                    Verify payment status
                                </button>
                            </form>
                            <p>Syncs status from Easebuzz after the customer pays.</p>
                        </div>
                    @else
                        <div class="alert alert-success border-0 shadow-sm mb-0">
                            <i class="fas fa-check-circle mr-1"></i>
                            Payment received successfully.
                        </div>
                    @endif
                </div>
            </div>

            @if($payment->easebuzz_create_response || $payment->easebuzz_verify_response)
                <div class="ebp-card mb-3">
                    <div class="ebp-card-head ebp-api-toggle mb-0"
                        data-toggle="collapse"
                        data-target="#apiResponseCollapse"
                        data-bs-toggle="collapse"
                        data-bs-target="#apiResponseCollapse"
                        aria-expanded="false"
                        role="button"
                        tabindex="0">
                        <i class="fas fa-code"></i> Developer — API response
                        <i class="fas fa-chevron-down ml-auto small"></i>
                    </div>
                    <div id="apiResponseCollapse" class="collapse">
                        @if($payment->easebuzz_verify_response)
                            <p class="px-3 pt-2 mb-0 small text-muted bg-light">Last verify response</p>
                            <pre class="ebp-api-pre">{{ json_encode($payment->easebuzz_verify_response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                        @endif
                        @if($payment->easebuzz_create_response)
                            <p class="px-3 pt-2 mb-0 small text-muted bg-light">Create link response</p>
                            <pre class="ebp-api-pre" style="border-radius:0;">{{ json_encode($payment->easebuzz_create_response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                        @endif
                    </div>
                </div>
            @endif

        </div>
    </section>
    </div>
</div>
@endsection

@section('footer-script')
<script>
document.getElementById('copyPaymentUrl')?.addEventListener('click', function () {
    var input = document.getElementById('paymentUrlInput');
    if (!input) return;
    var text = input.value;
    var btn = this;
    var done = function () {
        btn.innerHTML = '<i class="fas fa-check"></i> Copied';
        setTimeout(function () { btn.innerHTML = '<i class="far fa-copy"></i> Copy'; }, 2000);
    };
    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(text).then(done).catch(function () {
            input.select();
            document.execCommand('copy');
            done();
        });
    } else {
        input.select();
        document.execCommand('copy');
        done();
    }
});

var apiToggle = document.querySelector('.ebp-api-toggle');
var apiCollapse = document.getElementById('apiResponseCollapse');
if (apiToggle && apiCollapse) {
    var toggleApiSection = function () {
        var isOpen = apiCollapse.classList.contains('show');
        apiCollapse.classList.toggle('show', !isOpen);
        apiToggle.setAttribute('aria-expanded', isOpen ? 'false' : 'true');
    };
    apiToggle.addEventListener('click', function (e) {
        e.preventDefault();
        toggleApiSection();
    });
    apiToggle.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            toggleApiSection();
        }
    });
}
</script>
@endsection
