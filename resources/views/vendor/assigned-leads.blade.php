@extends('vendor.layouts.app')
@section('title', $page_heading ?? 'Assigned Leads')

@section('main')
<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <h1 class="m-0">{{ $page_heading ?? 'Assigned Leads' }}</h1>
        </div>
    </section>
    <section class="content">
        <div class="container-fluid pt-3">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-filter"></i> Filters</h5>
                </div>
                <div class="card-body">
                    <form method="GET" action="{{ route('vendor.assigned-leads') }}" id="filterForm">
                        <div class="row">
                            <div class="col-md-3">
                                <label for="status">Deployment Status</label>
                                <select class="form-control" name="status">
                                    <option value="">All Status</option>
                                    <option value="In Progress" {{ request('status') == 'In Progress' ? 'selected' : '' }}>In Progress</option>
                                    <option value="Pending" {{ request('status') == 'Pending' ? 'selected' : '' }}>Pending</option>
                                    <option value="Active" {{ request('status') == 'Active' ? 'selected' : '' }}>Active</option>
                                    <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completed</option>
                                    <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="from_date">From Date</label>
                                <input type="date" class="form-control" name="from_date" value="{{ request('from_date') }}">
                            </div>
                            <div class="col-md-2">
                                <label for="to_date">To Date</label>
                                <input type="date" class="form-control" name="to_date" value="{{ request('to_date') }}">
                            </div>
                            <div class="col-md-2">
                                <label for="customer_name">Customer Name</label>
                                <input type="text" class="form-control" name="customer_name" value="{{ request('customer_name') }}" placeholder="Search">
                            </div>
                            <div class="col-md-2">
                                <label for="contact_no">Contact No</label>
                                <input type="text" class="form-control" name="contact_no" value="{{ request('contact_no') }}" placeholder="Search">
                            </div>
                            <div class="col-md-1 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary mr-1"><i class="fas fa-search"></i></button>
                                <a href="{{ route('vendor.assigned-leads') }}" class="btn btn-secondary"><i class="fas fa-redo"></i></a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            <div class="card">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped mb-0">
                            <thead>
                                <tr>
                                    <th>Lead ID</th>
                                    <th>Customer Name</th>
                                    <th>Contact No</th>
                                    <th>Location</th>
                                    <th>From Date</th>
                                    <th>To Date</th>
                                    <th>Status</th>
                                    <th>Duty Hours</th>
                                    <th>Vendor Payment</th>
                                    <th>Location Attendance</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($deployments as $deployment)
                                    @php $lead = $deployment->operationLead; @endphp
                                    <tr>
                                        <td>{{ $lead->lead_id ?? 'N/A' }}</td>
                                        <td>{{ $lead->customer_name ?? 'N/A' }}</td>
                                        <td>{{ $lead->contact_no ?? 'N/A' }}</td>
                                        <td>{{ $lead->location ?? 'N/A' }}</td>
                                        <td>{{ $deployment->deployment_from_date ? \Carbon\Carbon::parse($deployment->deployment_from_date)->format('d M Y') : 'N/A' }}</td>
                                        <td>{{ $deployment->deployment_to_date ? \Carbon\Carbon::parse($deployment->deployment_to_date)->format('d M Y') : 'N/A' }}</td>
                                        <td><span class="badge badge-secondary">{{ $deployment->deployment_status ?? 'N/A' }}</span></td>
                                        <td>{{ $deployment->duty_hours ?? 'N/A' }}</td>
                                        <td>{{ $deployment->vendor_payment ? '₹' . number_format($deployment->vendor_payment, 2) : 'N/A' }}</td>
                                        <td>
                                            @php $att = $deployment->today_attendance; @endphp
                                            <div class="mb-2 vendor-attendance-status" data-role="vendor-attendance-status" data-operation-lead-id="{{ $deployment->operation_lead_id }}">
                                                @if($att && $att->attendance_status === 'present' && $att->is_location_matched)
                                                    <span class="badge badge-success"><span class="mr-1">🟢</span><i class="fas fa-check-circle"></i> Attendance Marked</span>
                                                @elseif($att && $att->attendance_status === 'location_not_matched')
                                                    <span class="badge badge-danger">Location Not Matched</span>
                                                @elseif($att && $att->attendance_status === 'waiting_for_customer_location')
                                                    <span class="badge badge-warning">Waiting for Customer</span>
                                                @else
                                                    <span class="badge badge-secondary">Not Marked (Today)</span>
                                                @endif
                                            </div>
                                            <button
                                                type="button"
                                                class="btn btn-sm btn-primary vendor-location-btn"
                                                data-operation-lead-id="{{ $deployment->operation_lead_id }}"
                                            >
                                                Turn On Location
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="10" class="text-center text-muted">No leads assigned yet.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if(method_exists($deployments, 'links'))
                        <div class="p-3">{{ $deployments->links() }}</div>
                    @endif
                </div>
            </div>
        </div>
    </section>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('click', function (event) {
    const button = event.target.closest('.vendor-location-btn');
    if (!button) return;

    if (!navigator.geolocation) {
        alert('Geolocation browser me supported nahi hai.');
        return;
    }

    const operationLeadId = button.getAttribute('data-operation-lead-id');
    button.disabled = true;
    const oldHtml = button.innerHTML;
    button.innerHTML = 'Sharing...';

    navigator.geolocation.getCurrentPosition(function (position) {
        fetch("{{ route('vendor.attendance.location') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': "{{ csrf_token() }}"
            },
            body: JSON.stringify({
                operation_lead_id: operationLeadId,
                latitude: position.coords.latitude,
                longitude: position.coords.longitude
            })
        }).then(async function (response) {
            const data = await response.json();
            if (!response.ok || !data.success) {
                throw new Error(data.message || 'Unable to share location.');
            }
            alert(data.message || 'Location shared successfully.');
            const statusHolder = button.parentElement.querySelector('[data-role="vendor-attendance-status"]');
            if (statusHolder) {
                if (data.attendance_status === 'present' && data.is_location_matched) {
                    statusHolder.innerHTML = '<span class="badge badge-success"><span class="mr-1">🟢</span><i class="fas fa-check-circle"></i> Attendance Marked</span>';
                } else if (data.attendance_status === 'location_not_matched') {
                    statusHolder.innerHTML = '<span class="badge badge-danger">Location Not Matched</span>';
                } else {
                    statusHolder.innerHTML = '<span class="badge badge-warning">Waiting for Customer</span>';
                }
            }
        }).catch(function (error) {
            alert(error.message || 'Unable to share location.');
        }).finally(function () {
            button.disabled = false;
            button.innerHTML = oldHtml;
        });
    }, function () {
        alert('Location permission allow kijiye.');
        button.disabled = false;
        button.innerHTML = oldHtml;
    }, {
        enableHighAccuracy: true,
        timeout: 10000,
        maximumAge: 0
    });
});

function refreshVendorAttendanceStatuses() {
    const statusBlocks = Array.from(document.querySelectorAll('[data-role="vendor-attendance-status"]'));
    if (!statusBlocks.length) return;
    const leadIds = statusBlocks
        .map((el) => parseInt(el.getAttribute('data-operation-lead-id'), 10))
        .filter((id) => Number.isFinite(id));
    if (!leadIds.length) return;

    fetch("{{ route('vendor.attendance.statuses') }}", {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': "{{ csrf_token() }}"
        },
        body: JSON.stringify({ operation_lead_ids: leadIds })
    })
    .then(async (response) => {
        const data = await response.json();
        if (!response.ok || !data.success) return;
        statusBlocks.forEach((block) => {
            const leadId = block.getAttribute('data-operation-lead-id');
            const row = data.statuses?.[leadId];
            if (!row) return;
            if (row.attendance_status === 'present' && row.is_location_matched) {
                block.innerHTML = '<span class="badge badge-success"><span class="mr-1">🟢</span><i class="fas fa-check-circle"></i> Attendance Marked</span>';
            } else if (row.attendance_status === 'location_not_matched') {
                block.innerHTML = '<span class="badge badge-danger">Location Not Matched</span>';
            } else if (row.attendance_status === 'waiting_for_customer_location') {
                block.innerHTML = '<span class="badge badge-warning">Waiting for Customer</span>';
            } else {
                block.innerHTML = '<span class="badge badge-secondary">Not Marked (Today)</span>';
            }
        });
    })
    .catch(() => {});
}

setInterval(refreshVendorAttendanceStatuses, 7000);
setTimeout(refreshVendorAttendanceStatuses, 500);
</script>
@endpush
