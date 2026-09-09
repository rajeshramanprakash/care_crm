@extends('freelancer.layouts.app')
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
                                    <form method="GET" action="{{ route('freelancer.assigned-leads') }}" id="filterForm">
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
                                                    <a href="{{ route('freelancer.assigned-leads') }}" class="btn btn-secondary">
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
                                            <th>Location Attendance</th>
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
                                                <td>
                                                    @php $att = $deployment->today_attendance; @endphp
                                                    <div class="mb-2 freelancer-attendance-status" data-role="freelancer-attendance-status" data-operation-lead-id="{{ $deployment->operation_lead_id }}">
                                                        @if($att && $att->attendance_status === 'present' && $att->is_location_matched)
                                                            <span class="badge bg-success"><span class="mr-1">🟢</span><i class="fas fa-check-circle"></i> Attendance Marked</span>
                                                        @elseif($att && $att->attendance_status === 'location_not_matched')
                                                            <span class="badge bg-danger">Location Not Matched</span>
                                                        @elseif($att && $att->attendance_status === 'waiting_for_customer_location')
                                                            <span class="badge bg-warning">Waiting for Customer</span>
                                                        @else
                                                            <span class="badge bg-secondary">Not Marked (Today)</span>
                                                        @endif
                                                    </div>
                                                    @php
                                                        $custAttnOn = $deployment->operationLead && ($deployment->operationLead->customer_location_attendance_enabled ?? false);
                                                    @endphp
                                                    <button
                                                        type="button"
                                                        class="btn btn-sm {{ $custAttnOn ? 'btn-primary' : 'btn-outline-secondary' }} freelancer-location-btn"
                                                        data-operation-lead-id="{{ $deployment->operation_lead_id }}"
                                                        data-customer-attendance-enabled="{{ $custAttnOn ? '1' : '0' }}"
                                                        title="{{ $custAttnOn ? 'Pehle location, phir camera se selfie' : 'Customer ne dashboard par Attendance mark ON nahi kiya — pehle customer ko enable karwana hoga.' }}"
                                                    >
                                                        Location + selfie
                                                    </button>
                                                    @if(!$custAttnOn)
                                                        <div class="small text-muted mt-1" style="max-width:220px;line-height:1.2;">Pehle customer: Attendance mark ON</div>
                                                    @endif
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="15" class="text-center text-muted">
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
<input type="file" id="freelancerAttendanceSelfieInput" class="d-none" accept="image/*" capture="user">

@endsection

@push('scripts')
<script>
let pendingFreelancerAttendance = null;

function warnCustomerAttendanceNotEnabled() {
    alert(
        'Attendance abhi start nahi ho sakti.\n\n' +
        'Customer ko apne CareCRM customer dashboard par jaa kar is lead wale card par ' +
        '"Attendance mark (location)" switch ON karna hoga.\n\n' +
        'Jab customer enable kar dega, tab aap location + selfie submit kar payenge.'
    );
}

function stopMediaStream(stream) {
    if (stream && stream.getTracks) {
        stream.getTracks().forEach(function (t) { t.stop(); });
    }
}

/**
 * Opens device camera (front-facing when possible) for selfie; falls back to file input.
 */
function openSelfieCapture(onFileReady) {
    if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
        navigator.mediaDevices.getUserMedia({
            video: { facingMode: { ideal: 'user' }, width: { ideal: 1280 }, height: { ideal: 720 } },
            audio: false
        }).then(function (stream) {
            var overlay = document.createElement('div');
            overlay.setAttribute('role', 'dialog');
            overlay.style.cssText = 'position:fixed;inset:0;background:rgba(0,0,0,0.92);z-index:10050;display:flex;align-items:center;justify-content:center;flex-direction:column;padding:16px;box-sizing:border-box;';
            var title = document.createElement('p');
            title.textContent = 'Selfie — camera se photo lo';
            title.style.cssText = 'color:#fff;font-weight:600;margin:0 0 10px;text-align:center;';
            var video = document.createElement('video');
            video.setAttribute('playsinline', '');
            video.setAttribute('webkit-playsinline', '');
            video.autoplay = true;
            video.muted = true;
            video.style.cssText = 'max-width:100%;max-height:55vh;border-radius:12px;background:#000;';
            video.srcObject = stream;
            video.play().catch(function () {});

            var row = document.createElement('div');
            row.style.cssText = 'margin-top:14px;display:flex;gap:10px;flex-wrap:wrap;justify-content:center;';
            var capBtn = document.createElement('button');
            capBtn.type = 'button';
            capBtn.className = 'btn btn-light btn-lg';
            capBtn.textContent = 'Photo capture karein';
            var cancelBtn = document.createElement('button');
            cancelBtn.type = 'button';
            cancelBtn.className = 'btn btn-secondary btn-lg';
            cancelBtn.textContent = 'Cancel';

            function cleanup() {
                stopMediaStream(stream);
                if (overlay.parentNode) {
                    overlay.parentNode.removeChild(overlay);
                }
            }

            cancelBtn.onclick = function () {
                cleanup();
                onFileReady(null);
            };

            capBtn.onclick = function () {
                try {
                    var w = video.videoWidth || 720;
                    var h = video.videoHeight || 1280;
                    var canvas = document.createElement('canvas');
                    canvas.width = w;
                    canvas.height = h;
                    var ctx = canvas.getContext('2d');
                    ctx.drawImage(video, 0, 0, w, h);
                    canvas.toBlob(function (blob) {
                        cleanup();
                        if (!blob) {
                            onFileReady(null);
                            return;
                        }
                        var selfieFile;
                        try {
                            selfieFile = new File([blob], 'selfie.jpg', { type: 'image/jpeg' });
                        } catch (e) {
                            selfieFile = blob;
                        }
                        onFileReady(selfieFile);
                    }, 'image/jpeg', 0.88);
                } catch (err) {
                    cleanup();
                    onFileReady(null);
                }
            };

            row.appendChild(capBtn);
            row.appendChild(cancelBtn);
            overlay.appendChild(title);
            overlay.appendChild(video);
            overlay.appendChild(row);
            document.body.appendChild(overlay);
        }).catch(function () {
            var input = document.getElementById('freelancerAttendanceSelfieInput');
            if (!input) {
                onFileReady(null);
                return;
            }
            input.value = '';
            input.setAttribute('capture', 'user');
            input.onchange = function (ev) {
                var f = ev.target.files && ev.target.files[0];
                ev.target.value = '';
                input.onchange = null;
                onFileReady(f || null);
            };
            input.click();
        });
    } else {
        var input2 = document.getElementById('freelancerAttendanceSelfieInput');
        if (!input2) {
            onFileReady(null);
            return;
        }
        input2.value = '';
        input2.setAttribute('capture', 'user');
        input2.onchange = function (ev) {
            var f = ev.target.files && ev.target.files[0];
            ev.target.value = '';
            input2.onchange = null;
            onFileReady(f || null);
        };
        input2.click();
    }
}

document.addEventListener('click', function (event) {
    const button = event.target.closest('.freelancer-location-btn');
    if (!button) return;

    var enabled = button.getAttribute('data-customer-attendance-enabled');
    if (enabled !== '1') {
        warnCustomerAttendanceNotEnabled();
        return;
    }

    if (!navigator.geolocation) {
        alert('Geolocation browser me supported nahi hai.');
        return;
    }

    const operationLeadId = button.getAttribute('data-operation-lead-id');
    button.disabled = true;
    const oldHtml = button.innerHTML;
    button.innerHTML = 'Location...';

    navigator.geolocation.getCurrentPosition(function (position) {
        pendingFreelancerAttendance = {
            operationLeadId: operationLeadId,
            lat: position.coords.latitude,
            lng: position.coords.longitude,
            button: button,
            oldHtml: oldHtml
        };
        button.innerHTML = 'Camera...';
        openSelfieCapture(function (file) {
            var pending = pendingFreelancerAttendance;
            pendingFreelancerAttendance = null;
            if (!pending) return;
            if (!file) {
                pending.button.disabled = false;
                pending.button.innerHTML = pending.oldHtml;
                return;
            }
            submitFreelancerAttendanceFile(pending, file);
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

function submitFreelancerAttendanceFile(pending, file) {
    var button = pending.button;
    button.innerHTML = 'Uploading...';
    const fd = new FormData();
    fd.append('_token', "{{ csrf_token() }}");
    fd.append('operation_lead_id', pending.operationLeadId);
    fd.append('latitude', String(pending.lat));
    fd.append('longitude', String(pending.lng));
    if (typeof File !== 'undefined' && file instanceof File) {
        fd.append('selfie', file);
    } else {
        fd.append('selfie', file, 'selfie.jpg');
    }
    fetch("{{ route('freelancer.attendance.location') }}", {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': "{{ csrf_token() }}",
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        credentials: 'same-origin',
        body: fd
    }).then(async function (response) {
        var text = await response.text();
        var data;
        try {
            data = text ? JSON.parse(text) : {};
        } catch (e) {
            throw new Error('Server ne HTML bheja (login/CSRF/session issue). Page refresh karke dubara try karein.');
        }
        if (!response.ok || !data.success) {
            var msg = data.message || 'Unable to submit attendance.';
            if (data.errors) {
                var ek = Object.keys(data.errors)[0];
                if (ek && data.errors[ek] && data.errors[ek][0]) {
                    msg = data.errors[ek][0];
                }
            }
            throw new Error(msg);
        }
        alert(data.message || 'Submitted successfully.');
        const statusHolder = button.parentElement.querySelector('[data-role="freelancer-attendance-status"]');
        if (statusHolder) {
            if (data.attendance_status === 'present' && data.is_location_matched) {
                statusHolder.innerHTML = '<span class="badge bg-success"><span class="mr-1">🟢</span><i class="fas fa-check-circle"></i> Attendance Marked</span>';
            } else if (data.attendance_status === 'location_not_matched') {
                statusHolder.innerHTML = '<span class="badge bg-danger">Location Not Matched</span>';
            } else {
                statusHolder.innerHTML = '<span class="badge bg-warning">Waiting for Customer</span>';
            }
        }
    }).catch(function (error) {
        alert(error.message || 'Unable to submit.');
    }).finally(function () {
        button.disabled = false;
        button.innerHTML = pending.oldHtml;
    });
}

function refreshFreelancerAttendanceStatuses() {
    const statusBlocks = Array.from(document.querySelectorAll('[data-role="freelancer-attendance-status"]'));
    if (!statusBlocks.length) return;
    const leadIds = statusBlocks
        .map((el) => parseInt(el.getAttribute('data-operation-lead-id'), 10))
        .filter((id) => Number.isFinite(id));
    if (!leadIds.length) return;

    fetch("{{ route('freelancer.attendance.statuses') }}", {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': "{{ csrf_token() }}",
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        credentials: 'same-origin',
        body: JSON.stringify({ operation_lead_ids: leadIds })
    })
    .then(async (response) => {
        var text = await response.text();
        var data;
        try {
            data = text ? JSON.parse(text) : {};
        } catch (e) {
            return;
        }
        if (!response.ok || !data.success) return;
        statusBlocks.forEach((block) => {
            const leadId = block.getAttribute('data-operation-lead-id');
            const row = data.statuses?.[leadId];
            if (!row) return;
            if (row.attendance_status === 'present' && row.is_location_matched) {
                block.innerHTML = '<span class="badge bg-success"><span class="mr-1">🟢</span><i class="fas fa-check-circle"></i> Attendance Marked</span>';
            } else if (row.attendance_status === 'location_not_matched') {
                block.innerHTML = '<span class="badge bg-danger">Location Not Matched</span>';
            } else if (row.attendance_status === 'waiting_for_customer_location') {
                block.innerHTML = '<span class="badge bg-warning">Waiting for Customer</span>';
            } else {
                block.innerHTML = '<span class="badge bg-secondary">Not Marked (Today)</span>';
            }
        });
    })
    .catch(() => {});
}

setInterval(refreshFreelancerAttendanceStatuses, 7000);
setTimeout(refreshFreelancerAttendanceStatuses, 500);
</script>
@endpush

