@extends('freelancer.layouts.app')
@section('title', 'Dashboard | Freelancer')
@php
use Illuminate\Support\Facades\Storage;
@endphp
@section('header-css')
<style>
    .bg-light-pink {
        background-color: #FFB6C1;
    }

    .bg-yellow {
        background-color: #FFFF00;
    }

    .bg-light-blue {
        background-color: #ADD8E6;
    }

    .bg-green {
        background-color: #00FF00;
    }

    .bg-red {
        background-color: #FF0000;
    }

    .bg-grey {
        background-color: #808080;
    }

    /* Active Leads Table Styling */
    .active-leads-table {
        margin: 0;
    }

    .active-leads-table thead th {
        background-color: #f8f9fa;
        color: #495057;
        font-weight: 600;
        font-size: 14px;
        padding: 16px 12px;
        border-bottom: 2px solid #dee2e6;
        text-align: left;
        white-space: nowrap;
    }

    .active-leads-table tbody td {
        padding: 16px 12px;
        vertical-align: middle;
        font-size: 14px;
        border-bottom: 1px solid #e9ecef;
    }

    .active-leads-table tbody tr:hover {
        background-color: #f8f9fa;
        transition: background-color 0.2s ease;
    }

    .active-leads-table tbody tr:last-child td {
        border-bottom: none;
    }

    .active-leads-table .customer-profile-img {
        width: 50px;
        height: 50px;
        object-fit: cover;
        border-radius: 50%;
        border: 2px solid #F7941D;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    .active-leads-table .text-muted-custom {
        color: #6c757d;
        font-size: 13px;
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
            <!-- Active Leads Card -->
            @if(isset($activeDeployments) && $activeDeployments->count() > 0)
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header bg-primary">
                            <h3 class="card-title text-white"><i class="fas fa-user-check mr-2"></i>Active Leads</h3>
                        </div>
                        <div class="card-body p-3">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0 active-leads-table">
                                    <thead>
                                        <tr>
                                            <th style="width: 80px;">Profile</th>
                                            <th style="width: 120px;">Customer No.</th>
                                            <th style="width: 150px;">Customer Name</th>
                                            <th style="width: 150px;">Query</th>
                                            <th style="min-width: 200px;">Address</th>
                                            <th style="width: 120px;">Location</th>
                                            <th style="width: 120px;">City</th>
                                            <th style="width: 100px;">Duty Time</th>
                                            <th style="width: 160px;">From Date & Time</th>
                                            <th style="width: 160px;">To Date & Time</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($activeDeployments as $deployment)
                                            @php
                                                $lead = $deployment->operationLead;
                                            @endphp
                                            @if($lead)
                                            <tr>
                                                <td>
                                                    @if($lead->profile_image)
                                                        <img src="{{ Storage::url($lead->profile_image) }}" alt="{{ $lead->customer_name }}" class="customer-profile-img" onerror="this.onerror=null; this.src='{{ asset('images/default-user.png') }}';">
                                                    @else
                                                        <img src="{{ asset('images/default-user.png') }}" alt="{{ $lead->customer_name }}" class="customer-profile-img">
                                                    @endif
                                                </td>
                                                <td>
                                                    <span class="font-weight-semibold">{{ $lead->contact_no ?? 'N/A' }}</span>
                                                </td>
                                                <td>
                                                    <span class="font-weight-semibold" style="color: #212529;">{{ $lead->customer_name ?? 'N/A' }}</span>
                                                </td>
                                                <td>
                                                    <span class="badge badge-info" style="background-color: #17a2b8; padding: 6px 12px; font-size: 12px;">{{ $lead->query ?? 'N/A' }}</span>
                                                </td>
                                                <td>
                                                    <span class="text-muted-custom" style="max-width: 200px; display: inline-block; word-wrap: break-word;">{{ $lead->address ?? 'N/A' }}</span>
                                                </td>
                                                <td>
                                                    <span class="text-muted-custom">{{ $lead->location ?? 'N/A' }}</span>
                                                </td>
                                                <td>
                                                    <span class="text-muted-custom">{{ $lead->city ?? 'N/A' }}</span>
                                                </td>
                                                <td>
                                                    @if($deployment->duty_hours)
                                                        <span class="badge badge-success" style="background-color: #28a745; padding: 6px 12px; font-size: 12px;">{{ $deployment->duty_hours == '12hr' ? '12 Hours' : '24 Hours' }}</span>
                                                    @else
                                                        <span class="text-muted-custom">N/A</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if($deployment->deployment_from_date)
                                                        <span class="text-muted-custom">{{ \Carbon\Carbon::parse($deployment->deployment_from_date)->format('d M Y, h:i A') }}</span>
                                                    @else
                                                        <span class="text-muted-custom">N/A</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if($deployment->deployment_to_date)
                                                        <span class="text-muted-custom">{{ \Carbon\Carbon::parse($deployment->deployment_to_date)->format('d M Y, h:i A') }}</span>
                                                    @else
                                                        <span class="text-muted-custom">N/A</span>
                                                    @endif
                                                </td>
                                            </tr>
                                            @endif
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            <!-- Your Deployments Section -->
            @if($deployments && $deployments->count() > 0)
            <div class="row mt-3">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Your Deployments</h3>
                        </div>
                        <div class="card-body">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Customer</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($deployments as $deployment)
                                    <tr>
                                        <td>{{ $deployment->created_at->format('d M Y') }}</td>
                                        <td>{{ $deployment->operationLead->customer_name ?? 'N/A' }}</td>
                                        <td>{{ $deployment->deployment_status ?? 'N/A' }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            @endif
        </div>
    </section>
</div>
@endsection
