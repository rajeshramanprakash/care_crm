@extends('b2b_individual.layouts.app')
@section('page_title', 'Individual Partner Dashboard')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card mb-4 border-0" style="background: linear-gradient(135deg, #F7941D 0%, #e67e22 100%); color: #fff; border-radius: 14px;">
            <div class="card-body">
                <h4 class="font-weight-bold mb-2">Welcome, {{ $b2bUser->name }}</h4>
                <p class="mb-1 opacity-90"><strong>Company:</strong> {{ $b2bUser->company_name }}</p>
                <p class="mb-1 opacity-90"><strong>Mobile:</strong> {{ $b2bUser->mobile }}</p>
                <p class="mb-1 opacity-90"><strong>Service:</strong> {{ $b2bUser->service_requirement }}</p>
                @if($b2bUser->bulk_requirement_qty)<p class="mb-0 opacity-90"><strong>Bulk requirement qty:</strong> {{ $b2bUser->bulk_requirement_qty }}</p>@endif
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="small-box" style="background: linear-gradient(135deg, #F7941D, #e67e22); border-radius: 14px;">
            <div class="inner text-white"><h3>{{ $totalLeads }}</h3><p>Total Leads</p></div>
            <div class="icon"><i class="fas fa-users"></i></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="small-box" style="background: linear-gradient(135deg, #22c55e, #16a34a); border-radius: 14px;">
            <div class="inner text-white"><h3>{{ $todayLeads }}</h3><p>Today's Leads</p></div>
            <div class="icon"><i class="fas fa-calendar-day"></i></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="small-box" style="background: linear-gradient(135deg, #f59e0b, #d97706); border-radius: 14px;">
            <div class="inner text-white">
                <h3>{{ $b2bUser->commission_percent !== null ? $b2bUser->commission_percent.'%' : '—' }}</h3>
                <p>Commission</p>
            </div>
            <div class="icon"><i class="fas fa-percent"></i></div>
        </div>
    </div>
    <div class="col-12 mt-2">
        <a href="{{ route('b2b.individual.leads.index') }}" class="btn btn-primary">Manage leads</a>
    </div>
</div>
@endsection
