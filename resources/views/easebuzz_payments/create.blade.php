@extends($layout)

@section('title', 'New payment link')

@section('header-css')
@include('easebuzz_payments.partials.scroll-fix')
@endsection

@section('main')
<div class="content-wrapper ebp-scroll-outer ebp-page">
    <div class="ebp-scroll-inner">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">New payment link</h1>
                </div>
                <div class="col-sm-6 text-sm-right">
                    <a href="{{ route($route_prefix.'.payments.index') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-arrow-left"></i> Back to list
                    </a>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <div class="row justify-content-center">
                <div class="col-lg-6">
                    @if(session('error'))
                        <div class="alert alert-danger">{{ session('error') }}</div>
                    @endif

                    <div class="card">
                        <div class="card-body">
                            <p class="text-muted small mb-3">Customer details and amount. A secure Easebuzz link will be generated (valid for 7 days).</p>
                            @if(!empty($easebuzz_demo_mode))
                                <div class="alert alert-info py-2 small mb-3">
                                    <strong>Demo mode</strong> — using <code>EASEBUZZ_KEY_DEMO</code> / test dashboard.
                                    Set <code>EASEBUZZ_EASYCOLLECT_DEMO=false</code> in .env for live payments.
                                </div>
                            @endif
                            <form method="post" action="{{ route($route_prefix.'.payments.store') }}">
                                @csrf
                                <div class="form-group">
                                    <label for="customer_name">Customer name <span class="text-danger">*</span></label>
                                    <input type="text" name="customer_name" id="customer_name" class="form-control @error('customer_name') is-invalid @enderror"
                                           value="{{ old('customer_name') }}" required maxlength="255">
                                    @error('customer_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="form-group">
                                    <label for="phone">Phone <span class="text-danger">*</span></label>
                                    <input type="text" name="phone" id="phone" class="form-control @error('phone') is-invalid @enderror"
                                           value="{{ old('phone') }}" required maxlength="20" placeholder="10-digit mobile">
                                    @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="form-group">
                                    <label for="email">Email <span class="text-danger">*</span></label>
                                    <input type="email" name="email" id="email" class="form-control @error('email') is-invalid @enderror"
                                           value="{{ old('email') }}" required maxlength="255">
                                    @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="form-group">
                                    <label for="message">Message / purpose</label>
                                    <textarea name="message" id="message" class="form-control @error('message') is-invalid @enderror" rows="2" maxlength="500">{{ old('message') }}</textarea>
                                    @error('message')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="form-group">
                                    <label for="amount">Amount (₹) <span class="text-danger">*</span></label>
                                    <input type="number" name="amount" id="amount" step="0.01" min="0.01" class="form-control @error('amount') is-invalid @enderror"
                                           value="{{ old('amount') }}" required placeholder="0.00">
                                    @error('amount')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <button type="submit" class="btn btn-primary btn-block" style="background:#F07F28;border-color:#F07F28;">
                                    Generate payment link
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    </div>
</div>
@endsection
