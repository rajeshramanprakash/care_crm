@extends('manager.layouts.app')

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
</style>
@endsection

@section('main')
<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center">
                <h1 class="m-0">Lead Details</h1>
                <a href="{{ route('manager.leads.index') }}" class="btn btn-primary">
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
                        <div class="col-md-6 ">
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
                                        @php
                                            $genderClass = match(strtolower($lead->patient_gender)) {
                                                'male' => 'bg-primary',
                                                'female' => 'bg-danger',
                                                'other' => 'bg-secondary',
                                                default => 'bg-secondary'
                                            };
                                            $genderText = match(strtolower($lead->patient_gender)) {
                                                'male' => 'Male',
                                                'female' => 'Female',
                                                'other' => 'Other',
                                                default => ucfirst($lead->patient_gender)
                                            };
                                        @endphp
                                        <span class="badge {{ $genderClass }}">{{ $genderText }}</span>
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
                                <div class="detail-value">{{ $lead->status_remarks ?? '-' }}</div>
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
                            @if($lead->status === 'future prospect' && $lead->future_prospect_date)
                            <div class="detail-group">
                                <div class="detail-label">Future Contact Date</div>
                                <div class="detail-value">{{ date('d-m-Y', strtotime($lead->future_prospect_date)) }}</div>
                            </div>
                            @endif
                            @if($lead->status === 'prospect' && $lead->prospect_close_rate !== null)
                            <div class="detail-group">
                                <div class="detail-label">Close Rate</div>
                                <div class="detail-value">{{ $lead->prospect_close_rate }}%</div>
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
            <div class="modal-header">
                <h5 class="modal-title" id="editLeadModalLabel">Edit Lead</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
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
                        <select class="form-control" id="edit_location" name="location" required>
                            <option value="">Select Location</option>
                            @foreach($locations as $location)
                                <option value="{{ $location->name }}">{{ $location->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group mb-3">
                        <label for="edit_query" class="form-label">Query</label>
                        <select class="form-control" id="edit_query" name="query" required>
                            @foreach ($services as $service)
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
                            <option value="price issue">Price Issue</option>
                            <option value="no response">No Response</option>
                            <option value="duplicate">Duplicate</option>
                            <option value="spam">Spam</option>
                        </select>
                    </div>
                    <!-- Status Remarks Field -->
                    <div class="form-group mb-3" id="edit_status_remarks_group" style="display: none;">
                        <label for="edit_status_remarks" class="form-label">Status Remarks</label>
                        <textarea class="form-control" id="edit_status_remarks" name="status_remarks" rows="3" placeholder="Enter remarks for the selected status"></textarea>
                    </div>
                    <div class="form-group mb-3">
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
                    <div class="form-group mb-3" id="edit_future_prospect_date_group" style="display: none;">
                        <label for="edit_future_prospect_date" class="form-label">Future Contact Date</label>
                        <input type="date" class="form-control" id="edit_future_prospect_date" name="future_prospect_date">
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
<!-- Load jQuery first -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
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
            url: '/manager/leads/' + leadId + '/edit',
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
                $('#edit_location').val(response.location || '');
                $('#edit_query').val(response.query || '');
                $('#edit_status').val(response.status || '');
                $('#edit_stage').val(response.stage || 'active');
                // Set remarks fields
                $('#edit_query_remarks').val(response.query_remarks || '');
                $('#edit_status_remarks').val(response.status_remarks || '');
                // Show/hide remarks fields based on selections
                if (response.query) {
                    $('#edit_query_remarks_group').show();
                }
                if (response.status) {
                    $('#edit_status_remarks_group').show();
                }
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
        var $btn = $(this);
        var formData = $('#editLeadForm').serializeArray();
        var data = {};
        // Convert form data to object and handle special fields
        $(formData).each(function(index, obj){
            data[obj.name] = obj.value;
        });
        // Only include future_prospect_date if status is future prospect
        if (data.status !== 'future prospect') {
            delete data.future_prospect_date;
        }
        // Only include prospect_rate if status is prospect
        if (data.status !== 'prospect') {
            delete data.prospect_rate;
        }
        var leadId = $('#edit_lead_id').val();
        // Disable button and show loading state
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Updating...');
        $.ajax({
            url: '/manager/leads/' + leadId,
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
        $('#edit_prospect_close_rate_group').hide();
        $('#edit_status_remarks_group').hide();
        // Show relevant field based on selection
        if (selectedStatus === 'future prospect') {
            $('#edit_future_prospect_date_group').show();
            $('#edit_future_prospect_date').prop('required', true);
        } else if (selectedStatus === 'prospect') {
            $('#edit_prospect_close_rate_group').show();
            $('#edit_prospect_rate').prop('required', true);
        }
        // Show status remarks for any status selection
        if (selectedStatus) {
            $('#edit_status_remarks_group').show();
            $('#edit_status_remarks').prop('required', true);
        } else {
            $('#edit_status_remarks_group').hide();
            $('#edit_status_remarks').prop('required', false);
        }
    });
    // Handle query change in edit form
    $('#edit_query').on('change', function() {
        const selectedQuery = $(this).val();
        if (selectedQuery) {
            $('#edit_query_remarks_group').show();
            $('#edit_query_remarks').prop('required', true);
        } else {
            $('#edit_query_remarks_group').hide();
            $('#edit_query_remarks').prop('required', false);
        }
    });
    // Handle modal close
    $('#editLeadModal').on('hidden.bs.modal', function() {
        $('#editLeadForm')[0].reset();
    });
});
</script>
@endsection
