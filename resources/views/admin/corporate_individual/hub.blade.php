@extends('admin.layouts.app')

@section('header-css')
<style>
    .ci-hub-card {
        border: 1px solid #e5e7eb;
        border-radius: 14px;
        padding: 1.5rem;
        height: 100%;
        background: #fff;
        transition: box-shadow .2s, transform .2s;
        text-decoration: none;
        color: inherit;
        display: block;
    }
    .ci-hub-card:hover {
        box-shadow: 0 8px 24px rgba(15, 23, 42, .08);
        transform: translateY(-2px);
        color: inherit;
        text-decoration: none;
    }
    .ci-hub-card .ci-icon {
        width: 52px;
        height: 52px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.35rem;
        margin-bottom: 1rem;
    }
    .ci-hub-card.corporate .ci-icon { background: #e0f2fe; color: #0369a1; }
    .ci-hub-card.individual .ci-icon { background: #fff3e8; color: #F07F28; }
</style>
@endsection

@section('main')
<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <h1 class="m-0">{{ $page_heading }}</h1>
            <p class="text-muted mb-0">Separate from legacy <strong>B2B Users</strong> — corporate &amp; individual partners login with mobile OTP.</p>
        </div>
    </section>
    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <a href="{{ route('admin.b2b_corporate.index') }}" class="ci-hub-card corporate">
                        <div class="ci-icon"><i class="fas fa-building"></i></div>
                        <h5 class="font-weight-bold mb-2">B2B Corporate</h5>
                        <p class="text-muted mb-0 small">Create corporate partners, optional mobile, chat access with selected CRM users.</p>
                    </a>
                </div>
                <div class="col-md-6 mb-3">
                    <a href="{{ route('admin.b2b_individual.index') }}" class="ci-hub-card individual">
                        <div class="ci-icon"><i class="fas fa-user-tie"></i></div>
                        <h5 class="font-weight-bold mb-2">Individual</h5>
                        <p class="text-muted mb-0 small">Individual partner accounts — mobile mandatory (OTP login).</p>
                    </a>
                </div>
            </div>
        </div>
    </section>
</div>
@endsection
