@extends('doctor_carelix.layouts.app')
@section('title', 'Assigned Leads')

@section('main')
<div class="content-wrapper">
    <section class="content">
        <div class="container-fluid pt-3">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Assigned Leads History</h3>
                        </div>
                        <div class="card-body">
                            <!-- Filters Section -->
                            <div class="card mb-3">
                                <div class="card-header">
                                    <h5 class="mb-0"><i class="fas fa-filter"></i> Filters</h5>
                                </div>
                                <div class="card-body">
                                    <form method="GET" action="{{ route('doctor_portal.assigned-leads') }}" id="filterForm">
                                        <div class="row">
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label for="status">Deployment Status</label>
                                                    <select class="form-control" id="status" name="status">
                                                        <option value="">All Status</option>
                                                        <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                                                        <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completed</option>
                                                        <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                                                        <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                                                        <option value="In Progress" {{ request('status') == 'In Progress' ? 'selected' : '' }}>In Progress</option>
                                                        <option value="Ongoing" {{ request('status') == 'Ongoing' ? 'selected' : '' }}>Ongoing</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label for="from_date">From Date</label>
                                                    <input type="date" class="form-control" id="from_date" name="from_date" value="{{ request('from_date') }}">
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label for="to_date">To Date</label>
                                                    <input type="date" class="form-control" id="to_date" name="to_date" value="{{ request('to_date') }}">
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label for="customer_name">Customer Name</label>
                                                    <input type="text" class="form-control" id="customer_name" name="customer_name" value="{{ request('customer_name') }}" placeholder="Search by name">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label for="contact_no">Contact Number</label>
                                                    <input type="text" class="form-control" id="contact_no" name="contact_no" value="{{ request('contact_no') }}" placeholder="Search by contact">
                                                </div>
                                            </div>
                                            <div class="col-md-9">
                                                <div class="form-group" style="margin-top: 32px;">
                                                    <button type="submit" class="btn btn-primary">
                                                        <i class="fas fa-search"></i> Apply Filters
                                                    </button>
                                                    <a href="{{ route('doctor_portal.assigned-leads') }}" class="btn btn-secondary">
                                                        <i class="fas fa-redo"></i> Reset
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>

                            <!-- Leads Table -->
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th>Lead ID</th>
                                            <th>Customer Name</th>
                                            <th>Contact No</th>
                                            <th>Address</th>
                                            <th>Location</th>
                                            <th>Query/Service</th>
                                            <th>Deployment Date</th>
                                            <th>From Date</th>
                                            <th>To Date</th>
                                            <th>Status</th>
                                            <th>Duty Hours</th>
                                            <th>Staff Name</th>
                                            <th>Rate/Day</th>
                                            <th>Payment</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($deployments as $deployment)
                                            @php
                                                $lead = $deployment->operationLead;
                                            @endphp
                                            <tr>
                                                <td>{{ $lead->lead_id ?? 'N/A' }}</td>
                                                <td>{{ $lead->customer_name ?? 'N/A' }}</td>
                                                <td>{{ $lead->contact_no ?? 'N/A' }}</td>
                                                <td>{{ $lead->address ?? 'N/A' }}</td>
                                                <td>{{ $lead->location ?? 'N/A' }}</td>
                                                <td>{{ $lead->query ?? 'N/A' }}</td>
                                                <td>{{ $deployment->deployment_date ? \Carbon\Carbon::parse($deployment->deployment_date)->format('d M Y') : 'N/A' }}</td>
                                                <td>{{ $deployment->deployment_from_date ? \Carbon\Carbon::parse($deployment->deployment_from_date)->format('d M Y') : 'N/A' }}</td>
                                                <td>{{ $deployment->deployment_to_date ? \Carbon\Carbon::parse($deployment->deployment_to_date)->format('d M Y') : 'N/A' }}</td>
                                                <td>
                                                    @if($deployment->deployment_status == 'active' || $deployment->deployment_status == 'Active')
                                                        <span class="badge bg-success">Active</span>
                                                    @elseif($deployment->deployment_status == 'completed' || $deployment->deployment_status == 'Completed')
                                                        <span class="badge bg-primary">Completed</span>
                                                    @elseif($deployment->deployment_status == 'cancelled' || $deployment->deployment_status == 'Cancelled')
                                                        <span class="badge bg-danger">Cancelled</span>
                                                    @elseif($deployment->deployment_status == 'pending' || $deployment->deployment_status == 'Pending')
                                                        <span class="badge bg-warning">Pending</span>
                                                    @elseif($deployment->deployment_status == 'In Progress')
                                                        <span class="badge bg-info">In Progress</span>
                                                    @elseif($deployment->deployment_status == 'Ongoing')
                                                        <span class="badge bg-success">Ongoing</span>
                                                    @else
                                                        <span class="badge bg-secondary">{{ ucfirst($deployment->deployment_status ?? 'N/A') }}</span>
                                                    @endif
                                                </td>
                                                <td>{{ $deployment->duty_hours ?? 'N/A' }}</td>
                                                <td>{{ $deployment->staff_name ?? 'N/A' }}</td>
                                                <td>{{ $deployment->vendor_rate_per_day ? '₹' . number_format($deployment->vendor_rate_per_day, 2) : 'N/A' }}</td>
                                                <td>{{ $deployment->vendor_payment ? '₹' . number_format($deployment->vendor_payment, 2) : 'N/A' }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="14" class="text-center text-muted">
                                                    <i class="fas fa-inbox"></i> No leads assigned yet.
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>

                            <!-- Pagination -->
                            @if($deployments->hasPages())
                                <div class="d-flex justify-content-center mt-3">
                                    {{ $deployments->links() }}
                                </div>
                            @endif

                            <!-- Summary -->
                            <div class="mt-3">
                                <div class="alert alert-info">
                                    <strong>Total Assigned Leads:</strong> {{ $deployments->total() }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
@endsection

