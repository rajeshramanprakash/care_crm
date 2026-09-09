@extends('b2b_reference.layouts.app')
@section('page_title', 'Dashboard')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card mb-4">
            <div class="card-body">
                <h4 class="mb-3 font-weight-bold">Welcome, {{ $refUser->name }}</h4>
                <p class="mb-0 text-muted"><strong class="text-dark">Mobile:</strong> {{ $refUser->mobile }}</p>
                <p class="mb-0 text-muted small mt-2">Leads listed here are uploaded by B2B partners who were referred under your name.</p>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="small-box" style="background: linear-gradient(135deg, #fb923c 0%, #f07f28 100%); border-radius: 14px;">
            <div class="inner text-white">
                <h3>{{ $totalLeads }}</h3>
                <p class="mb-0">Total referred leads</p>
            </div>
            <div class="icon">
                <i class="fas fa-users"></i>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="small-box" style="background: linear-gradient(135deg, #fbbf24 0%, #d97706 100%); border-radius: 14px;">
            <div class="inner text-white">
                <h3>{{ $todayLeads }}</h3>
                <p class="mb-0">Today's leads</p>
            </div>
            <div class="icon">
                <i class="fas fa-user-check"></i>
            </div>
        </div>
    </div>
</div>
@endsection
