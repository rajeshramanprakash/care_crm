@extends('b2b.layouts.app')
@section('page_title', 'Dashboard')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card mb-4">
            <div class="card-body">
                <h4 class="mb-3 font-weight-bold">Welcome, {{ $b2bUser->name }}</h4>
                <p class="mb-1 text-muted"><strong class="text-dark">Mobile:</strong> {{ $b2bUser->mobile }}</p>
                <p class="mb-0 text-muted"><strong class="text-dark">Service Requirement:</strong> {{ $b2bUser->service_requirement }}</p>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="small-box" style="background: linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%); border-radius: 14px;">
            <div class="inner text-white">
                <h3>{{ $totalLeads }}</h3>
                <p class="mb-0">Total Leads</p>
            </div>
            <div class="icon">
                <i class="fas fa-users"></i>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="small-box" style="background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%); border-radius: 14px;">
            <div class="inner text-white">
                <h3>{{ $todayLeads }}</h3>
                <p class="mb-0">Today's Leads</p>
            </div>
            <div class="icon">
                <i class="fas fa-user-check"></i>
            </div>
        </div>
    </div>
</div>
@endsection

