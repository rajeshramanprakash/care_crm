@extends('customer.layouts.app')
@section('title', 'Dashboard')

@section('content')
<div class="content-wrapper">
    <section class="content">
        <div class="container-fluid pt-3">
            @php
            use Illuminate\Support\Facades\Storage;
            $displayName = trim($customerName ?? ($customer->customer_name ?? 'Customer'));
            if ($displayName === '' || $displayName === 'N/A') {
                $displayName = 'Customer';
            }
            $welcomeInitial = strtoupper(substr($displayName, 0, 1));
            @endphp

            <div class="row mb-3">
                <div class="col-12">
                    <div class="cust-welcome-card">
                        <div class="cust-welcome-bg-shape cust-welcome-bg-shape--1"></div>
                        <div class="cust-welcome-bg-shape cust-welcome-bg-shape--2"></div>
                        <div class="cust-welcome-inner">
                            <div class="cust-welcome-avatar">{{ $welcomeInitial }}</div>
                            <div class="cust-welcome-text">
                                <p class="cust-welcome-label mb-1">Welcome</p>
                                <h2 class="cust-welcome-name mb-1">{{ $displayName }}</h2>
                                <p class="cust-welcome-sub mb-0">Manage your requirements, appointments, and services from here.</p>
                            </div>
                            <div class="cust-welcome-badge d-none d-md-flex">
                                <i class="fas fa-heartbeat mr-2"></i>
                                Carelix Customer Portal
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Your Leads Cards Section -->
            @php
                // Debug: Check if data exists
                $hasLeads = isset($activeLeadsWithAssignments) && $activeLeadsWithAssignments->count() > 0;
                $hasConsultations = isset($consultationWebsiteBookings) && $consultationWebsiteBookings->count() > 0;
            @endphp

            @if($hasConsultations)
            <div class="row mt-3">
                <div class="col-12">
                    <div class="card consultation-card border-0 shadow-sm">
                        <div class="card-header consultation-card-header border-0">
                            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                                <h3 class="card-title mb-0 consultation-title">
                                    <i class="fas fa-video mr-2"></i> Doctor consultation appointments
                                </h3>
                                <span class="consultation-count-pill">
                                    {{ $consultationWebsiteBookings->count() }} booked
                                </span>
                            </div>
                        </div>
                        <div class="card-body consultation-card-body p-0">
                            <div class="table-responsive consultation-table-wrap">
                                <table class="table consultation-table mb-0">
                                    <thead>
                                        <tr>
                                            <th>Date &amp; time</th>
                                            <th>Doctor</th>
                                            <th>Mode</th>
                                            <th>Meeting</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($consultationWebsiteBookings as $cwb)
                                        <tr>
                                            <td>
                                                <div class="consultation-date-main">
                                                    {{ $cwb->appointment_date ? $cwb->appointment_date->format('d M Y') : '—' }}
                                                </div>
                                                <div class="consultation-date-sub">
                                                    @if($cwb->appointment_start_time && $cwb->appointment_end_time)
                                                        {{ $cwb->appointment_start_time }} – {{ $cwb->appointment_end_time }}
                                                    @else
                                                        Time pending
                                                    @endif
                                                </div>
                                            </td>
                                            <td>
                                                <div class="consultation-doctor-name">
                                                    {{ optional($cwb->doctorRequest)->name ?? 'Doctor not assigned' }}
                                                </div>
                                            </td>
                                            <td>
                                                @if(($cwb->consultation_mode ?? '') === 'online')
                                                    <span class="badge consultation-mode-badge consultation-mode-online">
                                                        <i class="fas fa-broadcast-tower mr-1"></i>
                                                        {{ \App\Models\ConsultationWebsiteBooking::modeLabel($cwb->consultation_mode ?? '') }}
                                                    </span>
                                                @else
                                                    <span class="badge consultation-mode-badge consultation-mode-other">
                                                        {{ \App\Models\ConsultationWebsiteBooking::modeLabel($cwb->consultation_mode ?? '') }}
                                                    </span>
                                                @endif
                                            </td>
                                            <td>
                                                @if(($cwb->consultation_mode ?? '') === 'online' && !empty($cwb->online_meeting_link))
                                                    <a href="{{ route('customer.consultation-bookings.join-meeting', ['bookingId' => $cwb->id]) }}" class="btn consultation-join-btn">
                                                        <i class="fas fa-external-link-alt mr-1"></i> Join online meeting
                                                    </a>
                                                @elseif(($cwb->consultation_mode ?? '') === 'online')
                                                    <span class="consultation-muted-note">Link will appear here once scheduled.</span>
                                                @else
                                                    <span class="consultation-muted-note">No online meeting needed</span>
                                                @endif
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endif
            
            @if($hasLeads)
            <div class="row mt-3">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title"> Your Requirement</h3>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                @foreach($activeLeadsWithAssignments as $assignment)
                                <div class="col-lg-3 col-md-4 col-sm-6 mb-4">
                                    <div class="lead-assignment-card" style="background: linear-gradient(135deg, #F7941D 0%, #ea8a2b 100%); border-radius: 15px; padding: 20px; position: relative; box-shadow: 0 4px 15px rgba(0,0,0,0.1); min-height: 320px; display: flex; flex-direction: column; overflow: hidden;">
                                        <!-- Background Pattern -->
                                        <div style="position: absolute; top: -50px; right: -50px; width: 200px; height: 200px; background: rgba(255,255,255,0.1); border-radius: 50%;"></div>
                                        <div style="position: absolute; bottom: -30px; left: -30px; width: 150px; height: 150px; background: rgba(255,255,255,0.08); border-radius: 50%;"></div>
                                        
                                        <!-- Profile Image and Name -->
                                        <div class="d-flex align-items-start mb-3" style="position: relative; z-index: 1;">
                                            <div style="position: relative; margin-right: 15px;">
                                                @if($assignment['profile_image'])
                                                    <img src="{{ Storage::url($assignment['profile_image']) }}" alt="{{ $assignment['name'] }}" class="rounded-circle" style="width: 80px; height: 80px; object-fit: cover; border: 4px solid #fff; box-shadow: 0 4px 12px rgba(0,0,0,0.2);" onerror="this.onerror=null; this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                                    <div class="user-initial-avatar" style="display: none; background: #fff; width: 80px; height: 80px; border-radius: 50%; color: #F7941D; font-weight: 700; font-size: 2em; align-items: center; justify-content: center; position: absolute; top: 0; left: 0; border: 4px solid #fff; box-shadow: 0 4px 12px rgba(0,0,0,0.2);">
                                                        {{ strtoupper(substr($assignment['name'], 0, 1)) }}
                                                    </div>
                                                @else
                                                    <div class="user-initial-avatar" style="background: #fff; width: 80px; height: 80px; border-radius: 50%; color: #F7941D; font-weight: 700; font-size: 2em; display: flex; align-items: center; justify-content: center; border: 4px solid #fff; box-shadow: 0 4px 12px rgba(0,0,0,0.2);">
                                                        {{ strtoupper(substr($assignment['name'], 0, 1)) }}
                                                    </div>
                                                @endif
                                            </div>
                                            <div class="flex-grow-1" style="position: relative; z-index: 1;">
                                                <h5 class="mb-1 text-white" style="font-weight: 700; font-size: 1.2em; line-height: 1.3;">{{ $assignment['name'] }}</h5>
                                                <div class="mt-2 customer-attendance-status" data-role="customer-attendance-status">
                                                    @php $att = $assignment['today_attendance'] ?? null; @endphp
                                                    @if($att && $att->attendance_status === 'present' && $att->is_location_matched)
                                                        <span class="badge badge-success"><i class="fas fa-check-circle"></i> Attendance Marked</span>
                                                    @elseif($att && $att->attendance_status === 'location_not_matched')
                                                        <span class="badge badge-danger">Location Not Matched</span>
                                                    @elseif($att && str_starts_with($att->attendance_status, 'waiting_for_'))
                                                        <span class="badge badge-warning">Waiting for {{ $assignment['assignment_type'] === 'vendor' ? 'Vendor' : 'Freelancer' }}</span>
                                                    @else
                                                        <span class="badge badge-secondary">Not Marked (Today)</span>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Query -->
                                        <div class="mb-3" style="background: rgba(255,255,255,0.25); padding: 12px 15px; border-radius: 10px; position: relative; z-index: 1; backdrop-filter: blur(5px);">
                                            <p class="mb-0 text-white" style="font-size: 0.95em; font-weight: 600;">
                                                <i class="fas fa-tag mr-2"></i>{{ $assignment['query'] }}
                                            </p>
                                        </div>
                                        
                                        <!-- Date -->
                                        <div class="mb-3" style="position: relative; z-index: 1;">
                                            <div style="background: rgba(0,0,0,0.2); padding: 10px 15px; border-radius: 10px; display: inline-flex; align-items: center;">
                                                <i class="far fa-calendar-alt text-white mr-2" style="font-size: 1.1em;"></i>
                                                <p class="mb-0 text-white" style="font-size: 0.9em; font-weight: 500;">
                                                    {{ \Carbon\Carbon::parse($assignment['date'])->format('d M Y, h:i A') }}
                                                </p>
                                            </div>
                                        </div>
                                        
                                        <!-- Action Buttons -->
                                        <div class="mt-auto" style="position: relative; z-index: 1; margin-top: 15px;">
                                            @if($assignment['assignment_type'] && $assignment['assignment_id'])
                                            <div class="d-flex" style="gap: 10px;">
                                                <a href="{{ route('customer.chats') }}?chatType={{ $assignment['assignment_type'] }}&chatId={{ $assignment['assignment_id'] }}" class="btn btn-light flex-fill" style="border-radius: 12px; font-weight: 600; color: #F7941D; padding: 12px 16px; box-shadow: 0 2px 8px rgba(0,0,0,0.15); transition: all 0.3s; border: none; display: flex; align-items: center; justify-content: center; gap: 8px; font-size: 14px;">
                                                    <i class="fas fa-comments" style="font-size: 16px;"></i> 
                                                    <span>Chat</span>
                                                </a>
                                                <button type="button" class="btn btn-light flex-fill call-btn" data-chat-type="{{ $assignment['assignment_type'] }}" data-chat-id="{{ $assignment['assignment_id'] }}" style="border-radius: 12px; font-weight: 600; color: #F7941D; padding: 12px 16px; box-shadow: 0 2px 8px rgba(0,0,0,0.15); transition: all 0.3s; border: none; display: flex; align-items: center; justify-content: center; gap: 8px; font-size: 14px;">
                                                    <i class="fas fa-phone" style="font-size: 16px;"></i> 
                                                    <span>Call</span>
                                                </button>
                                            </div>
                                            <div class="mt-2">
                                                @php
                                                    $att = $assignment['today_attendance'] ?? null;
                                                    $customerAttendanceEnabled = (bool) ($assignment['customer_location_attendance_enabled'] ?? false);
                                                    $providerLocationReady = false;
                                                    $isAttendanceMarked = false;
                                                    if ($att) {
                                                        $providerLocationReady = $assignment['assignment_type'] === 'vendor'
                                                            ? !empty($att->vendor_location_captured_at)
                                                            : (!empty($att->freelancer_location_captured_at) && !empty($att->freelancer_selfie_path));
                                                        $isAttendanceMarked = ($att->attendance_status ?? null) === 'present' && (bool) ($att->is_location_matched ?? false);
                                                    }
                                                    $canShareLocation = $customerAttendanceEnabled && $providerLocationReady && !$isAttendanceMarked;
                                                @endphp
                                                <div class="custom-control custom-switch mb-2" style="padding-left: 2.25rem;">
                                                    <input
                                                        type="checkbox"
                                                        class="custom-control-input customer-attendance-enable-toggle"
                                                        id="attendance-enable-{{ $assignment['lead_id'] }}-{{ $assignment['assignment_type'] }}-{{ $assignment['assignment_id'] }}"
                                                        data-operation-lead-id="{{ $assignment['lead_id'] }}"
                                                        {{ $customerAttendanceEnabled ? 'checked' : '' }}
                                                        {{ $isAttendanceMarked ? 'disabled' : '' }}
                                                    >
                                                    <label class="custom-control-label text-white small" for="attendance-enable-{{ $assignment['lead_id'] }}-{{ $assignment['assignment_type'] }}-{{ $assignment['assignment_id'] }}" style="cursor: pointer;">
                                                        Attendance mark (location)
                                                    </label>
                                                </div>
                                                <button
                                                    type="button"
                                                    class="btn btn-light w-100 customer-location-btn"
                                                    data-operation-lead-id="{{ $assignment['lead_id'] }}"
                                                    data-assignment-type="{{ $assignment['assignment_type'] }}"
                                                    data-assignment-id="{{ $assignment['assignment_id'] }}"
                                                    data-customer-enabled="{{ $customerAttendanceEnabled ? '1' : '0' }}"
                                                    data-provider-ready="{{ $providerLocationReady ? '1' : '0' }}"
                                                    data-attendance-marked="{{ $isAttendanceMarked ? '1' : '0' }}"
                                                    @if(!$canShareLocation) disabled @endif
                                                    style="border-radius: 12px; font-weight: 600; color: #F7941D; padding: 10px 14px; box-shadow: 0 2px 8px rgba(0,0,0,0.15); border: none; display: flex; align-items: center; justify-content: center; gap: 8px; font-size: 13px; @if(!$canShareLocation) opacity:0.6; cursor:not-allowed; @endif"
                                                >
                                                    <i class="fas fa-map-marker-alt"></i>
                                                    <span class="customer-location-btn-label">
                                                        @if($isAttendanceMarked)
                                                            Attendance Marked
                                                        @elseif(!$customerAttendanceEnabled)
                                                            Enable attendance mark first
                                                        @elseif(!$providerLocationReady)
                                                            {{ $assignment['assignment_type'] === 'vendor' ? 'Waiting for vendor location' : 'Waiting for freelancer location + selfie' }}
                                                        @else
                                                            Share Location
                                                        @endif
                                                    </span>
                                                </button>
                                            </div>
                                            @else
                                            <div class="btn btn-light flex-fill" style="border-radius: 12px; font-weight: 600; color: #999; padding: 12px 16px; cursor: not-allowed; opacity: 0.6; border: none; display: flex; align-items: center; justify-content: center; gap: 8px; font-size: 14px;" disabled>
                                                <i class="fas fa-info-circle"></i> 
                                                <span>Not Assigned</span>
                                            </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            <!-- Active Queries Section -->
            @if($activeLeads->count() > 0 || $activeOperationLeads->count() > 0)
            <div class="row mt-3">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header bg-success">
                            <h3 class="card-title text-white"><i class="fas fa-check-circle"></i> Active Queries</h3>
                        </div>
                        <div class="card-body">
                            @if($activeLeads->count() > 0)
                            <h5 class="mb-3">Active Sales Leads</h5>
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>Date</th>
                                            <th>Query/Service</th>
                                            <th>Location</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($activeLeads as $lead)
                                        <tr>
                                            <td>{{ $lead->created_at->format('d M Y, h:i A') }}</td>
                                            <td>{{ $lead->query ?? 'N/A' }}</td>
                                            <td>{{ $lead->location ?? 'N/A' }}</td>
                                            <td>
                                                <span class="badge badge-success">{{ ucfirst($lead->status ?? 'Active') }}</span>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            @endif

                            @if($activeOperationLeads->count() > 0)
                            <h5 class="mt-4 mb-3">Active Operation Leads</h5>
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>Date</th>
                                            <th>Query/Service</th>
                                            <th>Location</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($activeOperationLeads as $opLead)
                                        <tr>
                                            <td>{{ $opLead->created_at->format('d M Y, h:i A') }}</td>
                                            <td>{{ $opLead->query ?? 'N/A' }}</td>
                                            <td>{{ $opLead->location ?? 'N/A' }}</td>
                                            <td>
                                                <span class="badge badge-success">{{ ucfirst($opLead->status ?? 'Active') }}</span>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            @endif

            @if($customerType === 'operation_lead' && $paymentInvoices->count() > 0)
            <div class="row mt-3">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Payment Invoices</h3>
                        </div>
                        <div class="card-body">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($paymentInvoices as $invoice)
                                    <tr>
                                        <td>{{ $invoice->created_at->format('d M Y') }}</td>
                                        <td>₹{{ number_format($invoice->payment_amount ?? 0, 2) }}</td>
                                        <td>
                                            @if(isset($invoice->status))
                                                <span class="badge status-{{ $invoice->status }}">{{ ucfirst($invoice->status) }}</span>
                                            @else
                                                N/A
                                            @endif
                                        </td>
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

@push('scripts')
<script>
(function() {
    // Wait for jQuery and DOM to be ready
    function initServiceRequest() {
        if (typeof jQuery === 'undefined') {
            console.error('jQuery is not loaded');
            setTimeout(initServiceRequest, 100);
            return;
        }
        
        var $ = jQuery;

        // Handle call button clicks from lead cards
        $(document).off('click', '.call-btn').on('click', '.call-btn', function(e) {
            e.preventDefault();
            var chatType = $(this).data('chat-type');
            var chatId = $(this).data('chat-id');
            
            if (chatType && chatId) {
                // Navigate to chat page and trigger call
                var chatUrl = '{{ route("customer.chats") }}?chatType=' + chatType + '&chatId=' + chatId + '&action=call';
                window.location.href = chatUrl;
            }
        });

        $(document).off('change', '.customer-attendance-enable-toggle').on('change', '.customer-attendance-enable-toggle', function() {
            var toggle = $(this);
            var leadId = toggle.data('operation-lead-id');
            var enabled = toggle.is(':checked');
            var card = toggle.closest('.lead-assignment-card');
            toggle.prop('disabled', true);
            $.ajax({
                url: '{{ route("customer.attendance.location-attendance-enabled") }}',
                method: 'POST',
                data: {
                    _token: $('meta[name="csrf-token"]').attr('content'),
                    operation_lead_id: leadId,
                    enabled: enabled ? 1 : 0
                },
                success: function(res) {
                    if (!res.success) {
                        alert(res.message || 'Update failed.');
                        toggle.prop('checked', !enabled);
                        return;
                    }
                    var shareBtn = card.find('.customer-location-btn');
                    var providerReady = String(shareBtn.data('provider-ready')) === '1';
                    var attendanceMarked = String(shareBtn.data('attendance-marked')) === '1';
                    var canShare = !!enabled && providerReady && !attendanceMarked;
                    shareBtn.data('customer-enabled', enabled ? '1' : '0');
                    shareBtn.attr('data-customer-enabled', enabled ? '1' : '0');
                    if (canShare) {
                        shareBtn.prop('disabled', false).css({ opacity: 1, cursor: 'pointer' });
                        shareBtn.find('.customer-location-btn-label').text('Share Location');
                    } else if (!enabled) {
                        shareBtn.prop('disabled', true).css({ opacity: 0.6, cursor: 'not-allowed' });
                        shareBtn.find('.customer-location-btn-label').text('Enable attendance mark first');
                    } else if (!providerReady) {
                        shareBtn.prop('disabled', true).css({ opacity: 0.6, cursor: 'not-allowed' });
                        var isVendor = String(shareBtn.data('assignment-type')) === 'vendor';
                        shareBtn.find('.customer-location-btn-label').text(isVendor ? 'Waiting for vendor location' : 'Waiting for freelancer location + selfie');
                    }
                },
                error: function(xhr) {
                    toggle.prop('checked', !enabled);
                    var message = xhr.responseJSON?.message || 'Update failed.';
                    alert(message);
                },
                complete: function() {
                    toggle.prop('disabled', false);
                }
            });
        });

        $(document).off('click', '.customer-location-btn').on('click', '.customer-location-btn', function(e) {
            e.preventDefault();
            var btn = $(this);
            var operationLeadId = btn.data('operation-lead-id');
            var assignmentType = btn.data('assignment-type');
            var assignmentId = btn.data('assignment-id');
            var customerEnabled = String(btn.data('customer-enabled')) === '1';
            var providerReady = String(btn.data('provider-ready')) === '1';
            var attendanceMarked = String(btn.data('attendance-marked')) === '1';

            if (attendanceMarked) {
                return;
            }

            if (!customerEnabled) {
                alert('Pehle upar "Attendance mark (location)" switch on karein.');
                return;
            }

            if (!providerReady) {
                alert(assignmentType === 'vendor'
                    ? 'Vendor ke assigned leads se location share hone tak wait karein.'
                    : 'Freelancer ke assigned leads se location + selfie complete hone tak wait karein.');
                return;
            }

            if (!navigator.geolocation) {
                alert('Geolocation browser me supported nahi hai.');
                return;
            }

            var originalHtml = btn.html();
            var keepDisabled = false;
            btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Sharing...');

            navigator.geolocation.getCurrentPosition(function(position) {
                $.ajax({
                    url: '{{ route("customer.attendance.location") }}',
                    method: 'POST',
                    data: {
                        _token: $('meta[name="csrf-token"]').attr('content'),
                        operation_lead_id: operationLeadId,
                        assignment_type: assignmentType,
                        assignment_id: assignmentId,
                        latitude: position.coords.latitude,
                        longitude: position.coords.longitude
                    },
                    success: function(response) {
                        alert(response.message || 'Location shared successfully.');
                        var statusHolder = btn.closest('.lead-assignment-card').find('[data-role="customer-attendance-status"]');
                        if (statusHolder.length) {
                            if (response.attendance_status === 'present' && response.is_location_matched) {
                                statusHolder.html('<span class="badge badge-success"><i class="fas fa-check-circle"></i> Attendance Marked</span>');
                                keepDisabled = true;
                                btn.data('attendance-marked', '1');
                                btn.attr('data-attendance-marked', '1');
                                btn.html('<i class="fas fa-check-circle"></i> <span>Attendance Marked</span>');
                            } else if (response.attendance_status === 'location_not_matched') {
                                statusHolder.html('<span class="badge badge-danger">Location Not Matched</span>');
                            } else {
                                statusHolder.html('<span class="badge badge-warning">Waiting for ' + (assignmentType === 'vendor' ? 'Vendor' : 'Freelancer') + '</span>');
                            }
                        }
                    },
                    error: function(xhr) {
                        var message = xhr.responseJSON?.message || 'Unable to share location.';
                        alert(message);
                    },
                    complete: function() {
                        if (keepDisabled) {
                            btn.prop('disabled', true);
                            return;
                        }
                        btn.prop('disabled', false).html(originalHtml);
                    }
                });
            }, function() {
                alert('Location permission allow kijiye.');
                btn.prop('disabled', false).html(originalHtml);
            }, {
                enableHighAccuracy: true,
                timeout: 10000,
                maximumAge: 0
            });
        });
    }
    
    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(initServiceRequest, 500);
        });
    } else {
        setTimeout(initServiceRequest, 500);
    }
})();
</script>
<style>
.cust-welcome-card {
    position: relative;
    overflow: hidden;
    border-radius: 18px;
    background: linear-gradient(135deg, #f7941d 0%, #ff8c2a 45%, #f28a1e 100%);
    box-shadow: 0 12px 28px rgba(242, 138, 30, 0.22);
    padding: 1.35rem 1.5rem;
}
.cust-welcome-bg-shape {
    position: absolute;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.12);
    pointer-events: none;
}
.cust-welcome-bg-shape--1 {
    width: 180px;
    height: 180px;
    top: -70px;
    right: -40px;
}
.cust-welcome-bg-shape--2 {
    width: 120px;
    height: 120px;
    bottom: -50px;
    left: 20%;
}
.cust-welcome-inner {
    position: relative;
    z-index: 1;
    display: flex;
    align-items: center;
    gap: 1rem;
    flex-wrap: wrap;
}
.cust-welcome-avatar {
    width: 64px;
    height: 64px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.95);
    color: #f28a1e;
    font-size: 1.6rem;
    font-weight: 700;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    box-shadow: 0 6px 16px rgba(0, 0, 0, 0.12);
    border: 3px solid rgba(255, 255, 255, 0.65);
}
.cust-welcome-text {
    flex: 1 1 220px;
    min-width: 0;
}
.cust-welcome-label {
    color: rgba(255, 255, 255, 0.88);
    font-size: 0.9rem;
    font-weight: 500;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    margin: 0;
}
.cust-welcome-name {
    color: #fff;
    font-size: clamp(1.35rem, 2.4vw, 1.85rem);
    font-weight: 700;
    line-height: 1.2;
    margin: 0;
    word-break: break-word;
}
.cust-welcome-sub {
    color: rgba(255, 255, 255, 0.92);
    font-size: 0.88rem;
    max-width: 520px;
}
.cust-welcome-badge {
    align-items: center;
    background: rgba(255, 255, 255, 0.18);
    border: 1px solid rgba(255, 255, 255, 0.35);
    border-radius: 999px;
    color: #fff;
    font-size: 0.82rem;
    font-weight: 600;
    padding: 0.55rem 0.95rem;
    white-space: nowrap;
}
@media (max-width: 767px) {
    .cust-welcome-card {
        padding: 1.1rem 1rem;
    }
    .cust-welcome-avatar {
        width: 54px;
        height: 54px;
        font-size: 1.35rem;
    }
    .cust-welcome-sub {
        font-size: 0.82rem;
    }
}
.consultation-card {
    border-radius: 16px;
    overflow: hidden;
    background: #fff;
}
.consultation-card-header {
    background: linear-gradient(135deg, #f4a43a 0%, #f28a1e 100%);
    padding: 14px 18px;
}
.consultation-title {
    color: #fff;
    font-weight: 700;
    font-size: 1.05rem;
    letter-spacing: 0.2px;
}
.consultation-count-pill {
    background: rgba(255, 255, 255, 0.22);
    color: #fff;
    border: 1px solid rgba(255, 255, 255, 0.35);
    border-radius: 999px;
    font-size: 0.78rem;
    font-weight: 600;
    padding: 6px 12px;
}
.consultation-table-wrap {
    padding: 12px;
}
.consultation-table {
    border-collapse: separate;
    border-spacing: 0;
}
.consultation-table thead th {
    border: 0 !important;
    background: #f7f9fc;
    color: #607080;
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    font-weight: 700;
    padding: 12px 14px;
}
.consultation-table thead th:first-child {
    border-top-left-radius: 12px;
}
.consultation-table thead th:last-child {
    border-top-right-radius: 12px;
}
.consultation-table tbody td {
    border-top: 1px solid #eef1f4 !important;
    padding: 14px;
    vertical-align: middle;
    color: #25313c;
}
.consultation-table tbody tr:first-child td {
    border-top: 0 !important;
}
.consultation-date-main {
    font-weight: 700;
    color: #1a2433;
}
.consultation-date-sub {
    margin-top: 2px;
    font-size: 0.82rem;
    color: #718096;
}
.consultation-doctor-name {
    font-weight: 600;
    color: #1f2937;
}
.consultation-mode-badge {
    font-size: 0.78rem;
    font-weight: 600;
    border-radius: 999px;
    padding: 7px 10px;
}
.consultation-mode-online {
    background: #fff3e6;
    color: #c86a00;
    border: 1px solid #ffd8ad;
}
.consultation-mode-other {
    background: #f1f5f9;
    color: #4a5568;
    border: 1px solid #dbe3ec;
}
.consultation-join-btn {
    background: linear-gradient(135deg, #f4a43a 0%, #f28a1e 100%);
    color: #fff !important;
    border: none;
    border-radius: 10px;
    font-weight: 600;
    font-size: 0.83rem;
    padding: 7px 12px;
    box-shadow: 0 4px 10px rgba(242, 138, 30, 0.28);
}
.consultation-join-btn:hover {
    color: #fff !important;
    transform: translateY(-1px);
}
.consultation-muted-note {
    color: #7a8794;
    font-size: 0.82rem;
    font-weight: 500;
}
.lead-assignment-card {
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}
.lead-assignment-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15) !important;
}
.lead-assignment-card .btn-light {
    background-color: #fff !important;
    color: #F7941D !important;
    border: none !important;
    font-weight: 600 !important;
    transition: all 0.3s ease !important;
    box-shadow: 0 2px 8px rgba(0,0,0,0.15) !important;
}
.lead-assignment-card .btn-light:hover {
    background-color: #fff !important;
    color: #F7941D !important;
    transform: translateY(-2px) !important;
    box-shadow: 0 4px 12px rgba(0,0,0,0.25) !important;
}
.lead-assignment-card .btn-light:active {
    transform: translateY(0) !important;
    box-shadow: 0 2px 6px rgba(0,0,0,0.15) !important;
}
.lead-assignment-card .btn-light i {
    transition: transform 0.3s ease;
}
.lead-assignment-card .btn-light:hover i {
    transform: scale(1.1);
}
.lead-assignment-card .call-btn {
    cursor: pointer;
}
.lead-assignment-card .call-btn:hover {
    background-color: #fff !important;
    color: #F7941D !important;
    transform: translateY(-2px) !important;
    box-shadow: 0 4px 12px rgba(0,0,0,0.25) !important;
}
.lead-assignment-card .call-btn:active {
    transform: translateY(0) !important;
    box-shadow: 0 2px 6px rgba(0,0,0,0.15) !important;
}
@media (max-width: 768px) {
    .consultation-card-header {
        padding: 12px 14px;
    }
    .consultation-title {
        font-size: 0.95rem;
    }
    .consultation-count-pill {
        font-size: 0.72rem;
        padding: 5px 10px;
    }
    .consultation-table-wrap {
        padding: 8px;
    }
    .consultation-table thead th,
    .consultation-table tbody td {
        padding: 10px;
    }
    .consultation-mode-badge {
        white-space: normal;
        line-height: 1.25;
    }
    .lead-assignment-card {
        min-height: 250px;
    }
    .lead-assignment-card .btn-light {
        padding: 10px 12px !important;
        font-size: 13px !important;
    }
    .lead-assignment-card .btn-light i {
        font-size: 14px !important;
    }
    .lead-assignment-card .btn-light span {
        font-size: 13px !important;
    }
}
</style>
@endpush
@endsection

