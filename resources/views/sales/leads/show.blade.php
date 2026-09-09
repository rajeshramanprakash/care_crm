@extends('sales.layouts.app')

@section('title', 'Lead Details')

@section('header-css')
<style>
    .lead-details-card {
        border: none;
        border-radius: 10px;
        box-shadow: 0 0 20px rgba(0,0,0,0.08);
    }

    .lead-details-card .card-header {
        background-color: #f8f9fa;
        border-bottom: 1px solid #eee;
        padding: 20px;
    }

    .lead-details-card .card-body {
        padding: 30px;
    }

    .detail-group {
        margin-bottom: 1.5rem;
    }

    .detail-label {
        font-weight: 600;
        color: #495057;
        margin-bottom: 0.5rem;
    }

    .detail-value {
        color: #212529;
        font-size: 1rem;
    }

    .badge {
        padding: 8px 15px;
        font-size: 0.9rem;
        font-weight: 500;
    }

    .back-btn {
        margin-top: 20px;
    }

    .modal-header {
        background: #FD7E14 !important;
    }
    .modal-header .btn-close {
        color: #fff !important;
        font-size: 1.5rem;
        opacity: 1 !important;
        background: none !important;
        border: none !important;
        box-shadow: none !important;
    }
    .modal-header .btn-close:hover, .modal-header .btn-close:focus {
        color: #222 !important;
        background: #fff2e0 !important;
        border-radius: 50%;
    }
    .modal-header .btn-close span, .modal-header .btn-close i {
        font-weight: bold;
        font-size: 1.5rem;
        line-height: 1;
    }
</style>
@endsection

@section('main')
<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center">
                <h1 class="m-0">Lead Details</h1>
                <a href="{{ route('sales.leads.index') }}" class="btn btn-primary">
                    <i class="fas fa-arrow-left"></i> Back to Leads
                </a>
                <button type="button" class="btn btn-primary ms-2" id="showEditBtn">
                    <i class="fas fa-edit"></i> Edit
                </button>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <div class="card lead-details-card">
        <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="detail-group">
                                <div class="detail-label">Lead No</div>
                                <div class="detail-value">{{ $lead->id }}</div>
                            </div>
                            <div class="detail-group">
                                <div class="detail-label">Date</div>
                                <div class="detail-value">{{ date('d-m-Y', strtotime($lead->date)) }}</div>
                            </div>
                            <div class="detail-group">
                                <div class="detail-label">Executive</div>
                                <div class="detail-value">{{ $lead->executive ?? '-' }}</div>
                            </div>
                            <div class="detail-group">
                                <div class="detail-label">Customer Name</div>
                                <div class="detail-value">{{ $lead->customer_name ?? '-' }}</div>
                            </div>
                            <div class="detail-group">
                                <div class="detail-label">Patient Name</div>
                                <div class="detail-value">{{ $lead->patient_name ?? '-' }}</div>
                            </div>
                            <div class="detail-group">
                                <div class="detail-label">Patient Gender</div>
                                <div class="detail-value">
                                    @if($lead->patient_gender)
                                        <span class="badge bg-info">{{ ucfirst($lead->patient_gender) }}</span>
                                    @else
                                        -
                                    @endif
                                </div>
                            </div>
                            <div class="detail-group">
                                <div class="detail-label">Age</div>
                                <div class="detail-value">{{ $lead->age ?? '-' }}</div>
                            </div>
                            <div class="detail-group">
                                <div class="detail-label">Contact Type</div>
                                <div class="detail-value">{{ ucfirst($lead->contact_type) ?? '-' }}</div>
                            </div>
                            <div class="detail-group">
                                <div class="detail-label">Contact No</div>
                                <div class="detail-value">{{ $lead->contact_no ?? '-' }}</div>
                            </div>
                            <div class="detail-group">
                                <div class="detail-label">Last Call Status</div>
                                <div class="detail-value">
                                    @if($lead->last_call_status)
                                        @php
                                            $callStatusClass = match(strtolower($lead->last_call_status)) {
                                                'answered' => 'bg-success',
                                                'busy' => 'bg-warning',
                                                'no answer', 'noanswer' => 'bg-danger',
                                                'failed' => 'bg-secondary',
                                                default => 'bg-info'
                                            };
                                            $callStatusText = match(strtolower($lead->last_call_status)) {
                                                'answered' => 'Answered',
                                                'busy' => 'Busy',
                                                'no answer', 'noanswer' => 'Missed',
                                                'failed' => 'Failed',
                                                default => ucfirst($lead->last_call_status)
                                            };
                                        @endphp
                                        <span class="badge {{ $callStatusClass }}">
                                            {{ $callStatusText }}
                                        </span>
                                    @else
                                        -
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="detail-group">
                                <div class="detail-label">Location</div>
                                <div class="detail-value">{{ $lead->location ?? '-' }}</div>
                            </div>
                            <div class="detail-group">
                                <div class="detail-label">Lead Source</div>
                                <div class="detail-value">{{ strtoupper($lead->lead_source) ?? '-' }}</div>
                            </div>
                            <div class="detail-group">
                                <div class="detail-label">Query</div>
                                <div class="detail-value">{{ ucfirst($lead->query) ?? '-' }}</div>
                            </div>
                            <div class="detail-group">
                                <div class="detail-label">Query Remarks</div>
                                <div class="detail-value">{{ $lead->query_remarks ?? '-' }}</div>
                            </div>
                            <div class="detail-group">
                                <div class="detail-label">Status</div>
                                <div class="detail-value">
                                    @php
                                        $statusClass = match($lead->status) {
                                            'follow-up' => 'bg-info',
                                            'future prospect' => 'bg-warning',
                                            'prospect' => 'bg-success',
                                            'price issue' => 'bg-secondary',
                                            'no response' => 'bg-secondary',
                                            'duplicate' => 'bg-danger',
                                            'spam' => 'bg-dark',
                                            default => 'bg-secondary'
                                        };
                                    @endphp
                                    <span class="badge {{ $statusClass }}">
                                        {{ ucfirst($lead->status) ?? '-' }}
                                    </span>
                                </div>
                            </div>
                            <div class="detail-group">
                                <div class="detail-label">Status Remarks</div>
                                <div class="detail-value">
                                    @forelse($lead->statusRemarks as $remark)
                                        <div class="border rounded p-2 mb-2 bg-light">
                                            <div style="white-space:pre-wrap;">{{ $remark->remark }}</div>
                                            <small class="text-muted">{{ $remark->created_by_name }} — {{ $remark->created_at->format('d M Y, h:i A') }}</small>
                                        </div>
                                    @empty
                                        {{ $lead->status_remarks ?? '-' }}
                                    @endforelse
                                </div>
                            </div>
                            <div class="detail-group">
                                <div class="detail-label">Stage</div>
                                <div class="detail-value">
                                    @php
                                        $stageClass = match($lead->stage) {
                                            'active' => 'bg-success',
                                            'inactive' => 'bg-warning',
                                            'closed' => 'bg-danger',
                                            'profile required' => 'bg-info',
                                            default => 'bg-secondary'
                                        };
                                    @endphp
                                    <span class="badge {{ $stageClass }}">
                                        {{ ucfirst($lead->stage) ?? '-' }}
                                    </span>
                                </div>
                            </div>
                            @if($lead->stage === 'inactive')
                            <div class="detail-group">
                                <div class="detail-label">Inactive Stage Remark</div>
                                <div class="detail-value">{{ $lead->inactive_stage_remark ?? '-' }}</div>
                            </div>
                            @endif
                            @if($lead->status === 'future prospect' && $lead->future_prospect_date)
                            <div class="detail-group">
                                <div class="detail-label">Future contact date &amp; time</div>
                                <div class="detail-value">{{ \Carbon\Carbon::parse($lead->future_prospect_date)->format('d-m-Y H:i') }}</div>
                            </div>
                            @endif
                            @if($lead->status === 'follow-up' && $lead->follow_up_date)
                            <div class="detail-group">
                                <div class="detail-label">Follow-up date &amp; time</div>
                                <div class="detail-value">{{ \Carbon\Carbon::parse($lead->follow_up_date)->format('d-m-Y H:i') }}</div>
                            </div>
                            @endif
                            @if($lead->status === 'prospect' && $lead->prospect_close_rate !== null)
                            <div class="detail-group">
                                <div class="detail-label">Close Rate</div>
                                <div class="detail-value">{{ $lead->prospect_close_rate }}%</div>
                            </div>
                            @endif
                        </div>
                        {{-- Call Recordings --}}
                        <div class="col-12">
                            @php
                                $recordingsArr = [];
                                if (!empty($lead->recordings)) {
                                    if (is_string($lead->recordings)) {
                                        $recordingsArr = json_decode($lead->recordings, true) ?? [];
                                    } elseif (is_array($lead->recordings)) {
                                        $recordingsArr = $lead->recordings;
                                    } elseif ($lead->recordings instanceof \Illuminate\Support\Collection) {
                                        $recordingsArr = $lead->recordings->toArray();
                                    }
                                } elseif (!empty($lead->recording_url)) {
                                    try {
                                        $recordingsArr = is_string($lead->recording_url)
                                            ? json_decode($lead->recording_url, true)
                                            : $lead->recording_url;
                                    } catch (Throwable $e) {
                                        $recordingsArr = [];
                                    }
                                }
                            @endphp

                            @if(is_array($recordingsArr) && count($recordingsArr) > 0)
                                <div class="card mb-5">
                                    <div class="card-header text-light" style="background: linear-gradient(135deg, #fd7e14 0%, #f7941d 100%);">
                                        <h3 class="card-title text-light">
                                            <i class="fa fa-microphone"></i> Call Recordings
                                        </h3>
                                        <span class="badge badge-light float-right" style="font-size: 14px;">
                                            {{ count($recordingsArr) }} Recording(s)
                                        </span>
                                    </div>
                                    <div class="card-body">
                                        @foreach($recordingsArr as $rec)
                                            @php
                                                $meta = isset($rec['metadata']) ? json_decode($rec['metadata'], true) : [];
                                                $callDirection = $meta['call_direction'] ?? null;
                                            @endphp
                                            <div class="mb-3 p-3" style="background-color: #f8f9fa; border-left: 4px solid #FD7E14; border-radius: 8px;">
                                                <audio controls preload="metadata" style="vertical-align:middle;max-width:520px;width:500px;">
                                                    <source src="{{ $rec['url'] }}" type="audio/mpeg">
                                                    <source src="{{ $rec['url'] }}" type="audio/mp3">
                                                    <source src="{{ $rec['url'] }}" type="audio/wav">
                                                    Your browser does not support the audio element.
                                                </audio>
                                                <a href="{{ $rec['url'] }}" target="_blank" class="btn btn-sm btn-outline-primary ms-2" title="Download">
                                                    <i class="fa fa-download"></i> Download
                                                </a>
                                                <div class="mt-2">
                                                    <span class="badge badge-info" style="color: #000;">
                                                        <i class="fa fa-calendar"></i> {{ $meta['datetime'] ?? 'N/A' }}
                                                    </span>
                                                    <span class="badge badge-secondary" style="color: #000;">
                                                        <i class="fa fa-user"></i> {{ $meta['caller_agent'] ?? 'N/A' }}
                                                    </span>
                                                    <span class="badge badge-{{ isset($meta['dialstatus']) && $meta['dialstatus'] === 'answered' ? 'success' : 'warning' }}" style="color: #000;">
                                                        <i class="fa fa-phone-alt"></i> {{ isset($meta['dialstatus']) ? ucfirst((string)$meta['dialstatus']) : 'N/A' }}
                                                    </span>
                                                    @if(!empty($callDirection))
                                                        <span class="badge badge-primary" style="color:#fff;">
                                                            <i class="fa fa-route"></i> {{ ucfirst((string)$callDirection) }}
                                                        </span>
                                                    @endif
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<!-- Edit Lead Modal (copied from index.blade.php) -->
<div class="modal fade" id="editLeadModal" tabindex="-1" role="dialog" aria-labelledby="editLeadModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header" style="background: #FD7E14;">
                <h5 class="modal-title text-white" id="editLeadModalLabel">Edit Lead</h5>
                <button type="button" class="btn-close text-white" data-bs-dismiss="modal" aria-label="Close" style="font-size: 1.5rem; opacity: 1; background: none; border: none;">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="editLeadForm">
                    <input type="hidden" id="edit_lead_id" name="id">
                    <!-- Hidden fields for required data -->
                    <input type="hidden" id="edit_date" name="date">
                    <input type="hidden" id="edit_executive" name="executive">
                    <input type="hidden" id="edit_contact_type" name="contact_type">
                    <input type="hidden" id="edit_contact_no" name="contact_no">
                    <input type="hidden" id="edit_lead_source" name="lead_source">

                    <!-- Visible editable fields -->
                    <div class="form-group mb-3">
                        <label for="edit_customer_name" class="form-label">Customer Name</label>
                        <input type="text" class="form-control" id="edit_customer_name" name="customer_name" required>
                    </div>
                    <div class="form-group mb-3">
                        <label for="edit_patient_name" class="form-label">Patient Name</label>
                        <input type="text" class="form-control" id="edit_patient_name" name="patient_name" placeholder="Enter patient name if different from customer">
                    </div>
                    <div class="form-group mb-3">
                        <label for="edit_patient_gender" class="form-label">Patient Gender</label>
                        <select class="form-control" id="edit_patient_gender" name="patient_gender">
                            <option value="">Select Gender</option>
                            <option value="male">Male</option>
                            <option value="female">Female</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="form-group mb-3">
                        <label for="edit_age" class="form-label">Age</label>
                        <input type="number" class="form-control" id="edit_age" name="age" min="0" max="150" placeholder="Enter age">
                    </div>
                    <div class="form-group mb-3">
                        <label for="edit_location" class="form-label">Location</label>
                        <select class="form-control location-select" id="edit_location" name="location" required>
                            <option value="">Select Location</option>
                            @foreach(($locations ?? collect()) as $location)
                                <option value="{{ $location->name }}" data-state="{{ $location->state ?? '' }}" data-city-name="{{ $location->name }}">{{ $location->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group mb-3">
                        <label for="edit_query" class="form-label">Query</label>
                        <select class="form-control" id="edit_query" name="query" required>
                            <option value="">Select Query</option>
                            @foreach (($services ?? collect()) as $service)
                                <option value="{{ $service->name }}">{{ $service->name }}</option>
                            @endforeach
                            <option value="job request">Job Request</option>
                        </select>
                    </div>
                    <!-- Query Remarks Field -->
                    <div class="form-group mb-3" id="edit_query_remarks_group" style="display: none;">
                        <label for="edit_query_remarks" class="form-label">Query Remarks</label>
                        <textarea class="form-control" id="edit_query_remarks" name="query_remarks" rows="3" placeholder="Enter remarks for the selected query"></textarea>
                    </div>
                    <div class="form-group mb-3">
                        <label for="edit_status" class="form-label">Status</label>
                        <select class="form-control" id="edit_status" name="status" required>
                            <option value="">Select Status</option>
                            <option value="follow-up">Follow-up</option>
                            <option value="future prospect">Future Prospect</option>
                            <option value="prospect">Prospect</option>
                            <option value="no response">No Response</option>
                            <option value="price issue">Price Issue</option>
                            <option value="duplicate">Duplicate</option>
                            <option value="spam">Spam</option>
                        </select>
                    </div>
                    <!-- Status Remarks Field -->
                    @include('partials.lead-status-remarks-fields', [
                        'fieldPrefix' => 'edit',
                        'aiGenerateUrlTemplate' => url('/sales/leads/__ID__/status-remark/generate-ai'),
                    ])
                    <div class="form-group mb-3 bg-light rounded">
                        <label for="edit_stage" class="form-label">Stage</label>
                        <select class="form-control" id="edit_stage" name="stage" required>
                            <option value="">Select Stage</option>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                            <option value="closed">Closed</option>
                            <option value="profile required">Profile Required</option>
                        </select>
                    </div>
                    <!-- Future Prospect Date Field (Hidden by default) -->
                    <div class="form-group mb-3 bg-light rounded" id="edit_future_prospect_date_group" style="display: none;">
                        <label for="edit_future_prospect_date" class="form-label">Future contact date &amp; time</label>
                        <input type="datetime-local" class="form-control" id="edit_future_prospect_date" name="future_prospect_date" step="60">
                    </div>
                    <div class="form-group mb-3 bg-light rounded" id="edit_follow_up_date_group" style="display: none;">
                        <label for="edit_follow_up_date" class="form-label">Follow-up date &amp; time</label>
                        <input type="datetime-local" class="form-control" id="edit_follow_up_date" name="follow_up_date" step="60">
                    </div>
                    <!-- Prospect Close Rate Field (Hidden by default) -->
                    <div class="form-group mb-3" id="edit_prospect_close_rate_group" style="display: none;">
                        <label for="edit_prospect_rate" class="form-label">Rate (₹)</label>
                        <input type="number" class="form-control" id="edit_prospect_rate" name="prospect_rate" min="0" step="0.01">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="updateLead">Update Lead</button>
            </div>
        </div>
    </div>
</div>

@section('footer-script')
<script src="{{ asset('plugins/moment/moment.min.js') }}"></script>
<script>
function formatFutureProspectForDatetimeLocal(val) {
    if (!val) return '';
    var m = moment(val);
    return m.isValid() ? m.format('YYYY-MM-DDTHH:mm') : '';
}
function normalizeDateTimeLocalForApi(value) {
    if (!value) return '';
    var m = moment(value, ['YYYY-MM-DDTHH:mm', 'YYYY-MM-DD HH:mm:ss', moment.ISO_8601], true);
    if (!m.isValid()) return '';
    return m.format('YYYY-MM-DD HH:mm:ss');
}
$(function() {
    // Set up CSRF token for all AJAX requests
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    // Show edit modal and populate fields
    $('#showEditBtn').on('click', function() {
        var leadId = {{ $lead->id }};
        var $btn = $(this);
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');
        $.ajax({
            url: '/sales/leads/' + leadId + '/edit',
            type: 'GET',
            success: function(response) {
                // Set all fields (including hidden ones)
                $('#edit_lead_id').val(response.id);
                $('#edit_date').val(response.date);
                $('#edit_executive').val(response.executive || '');
                $('#edit_contact_type').val(response.contact_type || '');
                $('#edit_contact_no').val(response.contact_no || '');
                $('#edit_lead_source').val(response.lead_source || '');
                // Set visible editable fields
                $('#edit_customer_name').val(response.customer_name || '');
                $('#edit_patient_name').val(response.patient_name || '');
                $('#edit_patient_gender').val(response.patient_gender || '');
                $('#edit_age').val(response.age || '');
                $('#edit_location').val(response.location || '').trigger('change');
                $('#edit_query').val(response.query || '');
                $('#edit_status').val(response.status || '');
                $('#edit_stage').val(response.stage || 'active');
                // Set remarks fields
                $('#edit_query_remarks').val(response.query_remarks || '');
                if (window.LeadStatusRemarks) {
                    LeadStatusRemarks.onEditLeadLoaded('edit', response, { leadId: response.id });
                }
                // Show/hide remarks fields based on selections
                if (response.query && response.query !== 'job request') {
                    $('#edit_query_remarks_group').show();
                }
                if (response.status) {
                    $('#edit_status_remarks_group').show();
                }
                $('#edit_status').trigger('change');
                $('#edit_follow_up_date').val(formatFutureProspectForDatetimeLocal(response.follow_up_date));
                $('#edit_future_prospect_date').val(formatFutureProspectForDatetimeLocal(response.future_prospect_date));
                $('#edit_prospect_rate').val(response.prospect_rate != null && response.prospect_rate !== '' ? response.prospect_rate : '');
                // Show modal
                var editLeadModal = new bootstrap.Modal(document.getElementById('editLeadModal'));
                editLeadModal.show();
            },
            error: function(xhr) {
                let errorMessage = 'Error loading lead data';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }
                toastr.error(errorMessage);
            },
            complete: function() {
                $btn.prop('disabled', false).html('<i class="fas fa-edit"></i> Edit');
            }
        });
    });

    // Handle update button click
    $('#updateLead').on('click', function() {
        if (window.LeadStatusRemarks && !window.LeadStatusRemarks.validateBeforeSave('edit')) {
            return;
        }
        var $btn = $(this);
        var formData = $('#editLeadForm').serializeArray();
        var data = {};
        // Convert form data to object and handle special fields
        $(formData).each(function(index, obj){
            data[obj.name] = obj.value;
        });
        // Only include future_prospect_date if status is future prospect
        if (data.status === 'future prospect') {
            if (!data.future_prospect_date) {
                toastr.error('Please select future contact date and time');
                return;
            }
            var normalizedFuture = normalizeDateTimeLocalForApi(data.future_prospect_date);
            if (!normalizedFuture) {
                toastr.error('Invalid future contact date and time');
                return;
            }
            data.future_prospect_date = normalizedFuture;
        } else {
            delete data.future_prospect_date;
        }
        if (data.status === 'follow-up') {
            if (!data.follow_up_date) {
                toastr.error('Please select follow-up date and time');
                return;
            }
            var normalizedFollowUp = normalizeDateTimeLocalForApi(data.follow_up_date);
            if (!normalizedFollowUp) {
                toastr.error('Invalid follow-up date and time');
                return;
            }
            data.follow_up_date = normalizedFollowUp;
        } else {
            delete data.follow_up_date;
        }
        // Only include prospect_rate if status is prospect
        if (data.status !== 'prospect') {
            delete data.prospect_rate;
        }
        var leadId = $('#edit_lead_id').val();
        // Disable button and show loading state
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Updating...');
        $.ajax({
            url: '/sales/leads/' + leadId,
            type: 'PUT',
            data: data,
            success: function(response) {
                toastr.success(response.message || 'Lead updated successfully');
                var editLeadModal = bootstrap.Modal.getInstance(document.getElementById('editLeadModal'));
                editLeadModal.hide();
                location.reload();
            },
            error: function(xhr) {
                let errorMessage = 'Error updating lead';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                } else if (xhr.responseJSON && xhr.responseJSON.errors) {
                    errorMessage = Object.values(xhr.responseJSON.errors).flat().join('<br>');
                }
                toastr.error(errorMessage);
            },
            complete: function() {
                $btn.prop('disabled', false).html('Update Lead');
            }
        });
    });

    // Handle status change in edit form
    $('#edit_status').on('change', function() {
        const selectedStatus = $(this).val();
        // Hide both conditional fields first
        $('#edit_future_prospect_date_group').hide();
        $('#edit_follow_up_date_group').hide();
        $('#edit_prospect_close_rate_group').hide();
        $('#edit_status_remarks_group').hide();
        $('#edit_future_prospect_date').prop('required', false);
        $('#edit_follow_up_date').prop('required', false);
        $('#edit_prospect_rate').prop('required', false);
        // Show relevant field based on selection
        if (selectedStatus === 'follow-up') {
            $('#edit_follow_up_date_group').show();
            $('#edit_follow_up_date').prop('required', true);
        } else if (selectedStatus === 'future prospect') {
            $('#edit_future_prospect_date_group').show();
            $('#edit_future_prospect_date').prop('required', true);
        } else if (selectedStatus === 'prospect') {
            $('#edit_prospect_close_rate_group').show();
            $('#edit_prospect_rate').prop('required', true);
        }
        // Show status remarks for any status selection
        if (selectedStatus) {
            $('#edit_status_remarks_group').show();
            $('#edit_new_status_remark').prop('required', false);
        } else {
            $('#edit_status_remarks_group').hide();
            $('#edit_new_status_remark').prop('required', false);
        }
    });
    // Handle query change in edit form
    $('#edit_query').on('change', function() {
        const selectedQuery = $(this).val();
        if (selectedQuery && selectedQuery !== 'job request') {
            $('#edit_query_remarks_group').show();
            $('#edit_query_remarks').prop('required', true);
        } else {
            $('#edit_query_remarks_group').hide();
            $('#edit_query_remarks').prop('required', false);
        }

        // Always show Status, Status Remarks, and Stage fields
        $('#edit_status').closest('.form-group').show();
        $('#edit_stage').closest('.form-group').show();
        // Only show status remarks if status is selected
        if ($('#edit_status').val()) {
            $('#edit_status_remarks_group').show();
        }
    });
    // Handle modal close
    $('#editLeadModal').on('hidden.bs.modal', function() {
        $('#editLeadForm')[0].reset();
    });
});
</script>
@endsection
