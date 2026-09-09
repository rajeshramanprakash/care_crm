@extends('admin.layouts.app')

@section('title', 'Admin Leads')

@section('header-css')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.1/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.4.0/css/responsive.dataTables.min.css">
    <style>
        .table-responsive {
    overflow-x: auto;
    position: relative;
}

table {
    width: 100%;
    min-width: 1400px;
    table-layout: fixed;
}
table thead, #table tfoot {
    position: sticky;
    top: 0;
    background: #fff;
    z-index: 2;
}
table tbody {
    display: block;
    max-height: 50vh;
    overflow-x: auto;
    overflow-y: auto;
    width: 100%;
}
table thead, table tfoot, table tbody tr {
    display: table;
    width: 100%;
    table-layout: fixed;
}

.custom-table-scroll-x {
    width: 100%;
    overflow-x: auto;
}
.custom-table-scroll-x table {
    min-width: 1400px;
    width: 100%;
    table-layout: fixed;
}

        </style>
@endsection
@section('navbar-right-links')
<li class="nav-item">
    <a class="nav-link filter-toggle" title="Filters" data-widget="control-sidebar"
        href="javascript:void(0);" role="button">
        <i class="fas fa-filter"></i>
    </a>
</li>


@endsection


@section('main')
    <div class="content-wrapper">
        <section class="content-header">
            <div class="container-fluid">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h1 class="m-0" style="font-weight: 800">Sales Leads</h1>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addLeadModal">
                        <i class="fas fa-plus"></i> Add New Lead
                    </button>
                </div>
            </div>
        </section>

        <section class="content">
            <div class="container-fluid">
                <div class="card">
                    <div class="card-body">
                        <div class="custom-table-scroll-x">
                            <table id="leads-table" class="table">
                                <thead>
                                    <tr>
                                        <th>Lead No</th>
                                        <th>Date</th>
                                        <th>Executive</th>
                                        <th>Customer</th>
                                        <th>Patient Name</th>
                                        <th>Patient Gender</th>
                                        <th>Mobile</th>
                                        <th>Last Call</th>
                                        <th>Location</th>
                                        <th>Source</th>
                                        <th>Query</th>
                                        <th>Status</th>
                                        <th>Stage</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <!-- Edit Lead Modal -->
    <div class="modal fade" id="editLeadModal" tabindex="-1" role="dialog" aria-labelledby="editLeadModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editLeadModalLabel"><i class="fa fa-pen-to-square me-2"></i>Edit Lead</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"
                        style="background-color: #FD7E14; color:white; border:none;">&times;</button>
                </div>
                <div class="modal-body">
                    <form id="editLeadForm">
                        <input type="hidden" id="edit_lead_id" name="id">
                        <div class="row g-3">
                            <div class="col-md-6 mb-3 p-3 bg-light rounded">
                                <label for="edit_executive" class="form-label"><i class="fa fa-user-tie me-1"></i>
                                    Executive</label>
                                <select class="form-control" id="edit_executive" name="executive" required>
                                    <option value="">Select Executive</option>
                                    @foreach ($executives as $executive)
                                        <option value="{{ $executive->id }}">{{ $executive->f_name }}
                                            {{ $executive->l_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 mb-3 p-3 bg-light rounded">
                                <label for="edit_customer_name" class="form-label"><i class="fa fa-user me-1"></i> Customer
                                    Name</label>
                                <input type="text" class="form-control" id="edit_customer_name" name="customer_name"
                                    required>
                            </div>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6 mb-3 p-3 bg-light rounded">
                                <label for="edit_patient_name" class="form-label"><i class="fa fa-user-injured me-1"></i> Patient
                                    Name</label>
                                <input type="text" class="form-control" id="edit_patient_name" name="patient_name"
                                    placeholder="Enter patient name if different from customer">
                            </div>
                            <div class="col-md-6 mb-3 p-3 bg-light rounded">
                                <label for="edit_patient_gender" class="form-label"><i class="fa fa-venus-mars me-1"></i> Patient
                                    Gender</label>
                                <select class="form-control" id="edit_patient_gender" name="patient_gender">
                                    <option value="">Select Gender</option>
                                    <option value="male">Male</option>
                                    <option value="female">Female</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6 mb-3 p-3 bg-light rounded">
                                <label for="edit_age" class="form-label"><i class="fa fa-calendar me-1"></i> Age</label>
                                <input type="number" class="form-control" id="edit_age" name="age" min="0" max="150" placeholder="Enter age">
                            </div>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6 mb-3 p-3 bg-light rounded">
                                <label for="edit_contact_type" class="form-label"><i class="fa fa-address-book me-1"></i>
                                    Contact Type</label>
                                <select class="form-control" id="edit_contact_type" name="contact_type" required>
                                    <option value="">Select Contact Type</option>
                                    <option value="call">Call</option>
                                    <option value="whatsapp">WhatsApp</option>
                                    <option value="email">Email</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3 p-3 bg-light rounded">
                                <label for="edit_contact_no" class="form-label"><i class="fa fa-phone me-1"></i> Contact
                                    No</label>
                                <input type="text" class="form-control" id="edit_contact_no" name="contact_no" required>
                            </div>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6 mb-3 p-3 bg-light rounded">
                                <label for="edit_lead_source" class="form-label"><i class="fa fa-globe me-1"></i> Lead
                                    Source</label>
                                <select class="form-control" id="edit_lead_source" name="lead_source" required>
                                    <option value="">Select Lead Source</option>
                                    <option value="web">Web</option>
                                    <option value="ivrs">IVRS</option>
                                    <option value="whatsapp">WhatsApp</option>
                                    <option value="manual">Manual</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3 p-3 bg-light rounded">
                                <label for="edit_location" class="form-label"><i class="fa fa-map-marker-alt me-1"></i>
                                    Location</label>
                                <select class="form-control" id="edit_location" name="location" required>
                                    <option value="">Select Location</option>
                                    @foreach($locations as $location)
                                        <option value="{{ $location->name }}">{{ $location->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6 mb-3 p-3 bg-light rounded">
                                <label for="edit_query" class="form-label"><i class="fa fa-question-circle me-1"></i>
                                    Query</label>
                                <select class="form-control" id="edit_query" name="query" required>
                                    <option value="">Select Query</option>
                                    @foreach ($services as $service)
                                        <option value="{{ $service->name }}">{{ $service->name }}</option>
                                    @endforeach
                                    <option value="job request">Job Request</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3 p-3 bg-light rounded" id="edit_query_remarks_group" style="display: none;">
                                <label for="edit_query_remarks" class="form-label"><i class="fa fa-comment me-1"></i> Query Remarks</label>
                                <textarea class="form-control" id="edit_query_remarks" name="query_remarks" rows="3" placeholder="Enter remarks for the selected query"></textarea>
                            </div>
                            <div class="col-md-6 mb-3 p-3 bg-light rounded">
                                <label for="edit_status" class="form-label"><i class="fa fa-info-circle me-1"></i>
                                    Status</label>
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
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6 mb-3 p-3 bg-light rounded">
                                <label for="edit_stage" class="form-label"><i class="fa fa-layer-group me-1"></i>
                                    Stage</label>
                                <select class="form-control" id="edit_stage" name="stage" required>
                                    <option value="">Select Stage</option>
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                    <option value="closed">Closed</option>
                                    <option value="profile required">Profile Required</option>
                                </select>
                            </div>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6 mb-3 p-3 bg-light rounded">
                                <label for="edit_shift_type" class="form-label"><i class="fa fa-clock me-1"></i>
                                    Shift Type</label>
                                <select class="form-control" id="edit_shift_type" name="shift_type">
                                    <option value="">Select Shift Type</option>
                                    <option value="12hr">12hr</option>
                                    <option value="24hr">24hr</option>
                                    <option value="both">Both</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div id="edit_future_prospect_date_group" class="p-3 bg-light rounded mb-2">
                                    <label for="edit_future_prospect_date" class="form-label"><i
                                            class="fa fa-calendar-alt me-1"></i> Future Contact Date</label>
                                    <input type="date" class="form-control" id="edit_future_prospect_date"
                                        name="future_prospect_date">
                                </div>
                                <div id="edit_prospect_close_rate_group" class="p-3 bg-light rounded">
                                    <label for="edit_prospect_rate" class="form-label"><i
                                            class="fa fa-rupee-sign me-1"></i> Rate (₹)</label>
                                    <input type="number" class="form-control" id="edit_prospect_rate"
                                        name="prospect_rate" min="0" step="0.01">
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i
                            class="fa fa-times me-1"></i> Close</button>
                    <button type="button" class="btn btn-primary" id="updateLead"><i class="fas fa-save me-1"></i>
                        Update Lead</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Lead Modal -->
    <div class="modal fade" id="addLeadModal" tabindex="-1" role="dialog" aria-labelledby="addLeadModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addLeadModalLabel"><i class="fa fa-plus me-2"></i>Add New Lead</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"
                        style="background-color: #FD7E14; color:white; border:none;">&times;</button>
                </div>
                <div class="modal-body">
                    <form id="addLeadForm">
                        <div class="row g-3">
                            <div class="col-md-6 mb-3 p-3 bg-light rounded">
                                <label for="executive" class="form-label"><i class="fa fa-user-tie me-1"></i>
                                    Executive</label>
                                <select class="form-control" id="executive" name="executive" required>
                                    <option value="">Select Executive</option>
                                    @foreach ($executives as $executive)
                                        <option value="{{ $executive->id }}"
                                            {{ old('executive') == $executive->id ? 'selected' : '' }}>
                                            {{ $executive->f_name }} {{ $executive->l_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 mb-3 p-3 bg-light rounded">
                                <label for="customer_name" class="form-label"><i class="fa fa-user me-1"></i> Customer
                                    Name</label>
                                <input type="text" class="form-control" id="customer_name" name="customer_name">
                            </div>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6 mb-3 p-3 bg-light rounded">
                                <label for="patient_name" class="form-label"><i class="fa fa-user-injured me-1"></i> Patient
                                    Name</label>
                                <input type="text" class="form-control" id="patient_name" name="patient_name"
                                    placeholder="Enter patient name if different from customer">
                            </div>
                            <div class="col-md-6 mb-3 p-3 bg-light rounded">
                                <label for="patient_gender" class="form-label"><i class="fa fa-venus-mars me-1"></i> Patient
                                    Gender</label>
                                <select class="form-control" id="patient_gender" name="patient_gender">
                                    <option value="">Select Gender</option>
                                    <option value="male">Male</option>
                                    <option value="female">Female</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6 mb-3 p-3 bg-light rounded">
                                <label for="age" class="form-label"><i class="fa fa-calendar me-1"></i> Age</label>
                                <input type="number" class="form-control" id="age" name="age" min="0" max="150" placeholder="Enter age">
                            </div>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6 mb-3 p-3 bg-light rounded">
                                <label for="contact_type" class="form-label"><i class="fa fa-address-book me-1"></i>
                                    Contact Type</label>
                                <select class="form-control" id="contact_type" name="contact_type" required>
                                    <option value="">Select Contact Type</option>
                                    <option value="call">Call</option>
                                    <option value="whatsapp">WhatsApp</option>
                                    <option value="email">Email</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3 p-3 bg-light rounded">
                                <label for="contact_no" class="form-label"><i class="fa fa-phone me-1"></i> Contact
                                    No</label>
                                <input type="text" class="form-control" id="contact_no" name="contact_no" required>
                            </div>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6 mb-3 p-3 bg-light rounded">
                                <label for="location" class="form-label"><i class="fa fa-map-marker-alt me-1"></i>
                                    Location</label>
                                <select class="form-control" id="location" name="location">
                                    <option value="">Select Location</option>
                                    @foreach($locations as $location)
                                        <option value="{{ $location->name }}">{{ $location->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 mb-3 p-3 bg-light rounded">
                                <label for="lead_source" class="form-label"><i class="fa fa-globe me-1"></i> Lead
                                    Source</label>
                                <select class="form-control" id="lead_source" name="lead_source">
                                    <option value="">Select Lead Source</option>
                                    <option value="web">Web</option>
                                    <option value="ivrs">IVRS</option>
                                    <option value="whatsapp">WhatsApp</option>
                                    <option value="manual">Manual</option>
                                </select>
                            </div>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6 mb-3 p-3 bg-light rounded">
                                <label for="query" class="form-label"><i class="fa fa-question-circle me-1"></i>
                                    Query</label>
                                <select class="form-control" id="query" name="query">
                                    <option value="">Select Query</option>
                                    @foreach ($services as $service)
                                        <option value="{{ $service->name }}">{{ $service->name }}</option>
                                    @endforeach
                                    <option value="job request">Job Request</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3 p-3 bg-light rounded" id="query_remarks_group" style="display: none;">
                                <label for="query_remarks" class="form-label"><i class="fa fa-comment me-1"></i> Query Remarks</label>
                                <textarea class="form-control" id="query_remarks" name="query_remarks" rows="3" placeholder="Enter remarks for the selected query"></textarea>
                            </div>
                            <div class="col-md-6 mb-3 p-3 bg-light rounded">
                                <label for="status" class="form-label"><i class="fa fa-info-circle me-1"></i>
                                    Status</label>
                                <select class="form-control" id="status" name="status">
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
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6 mb-3 p-3 bg-light rounded">
                                <label for="stage" class="form-label"><i class="fa fa-layer-group me-1"></i>
                                    Stage</label>
                                <select class="form-control" id="stage" name="stage">
                                    <option value="">Select Stage</option>
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                    <option value="closed">Closed</option>
                                    <option value="profile required">Profile Required</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="form-group mb-3" id="future_prospect_date_group" style="display: none;">
                                    <label for="future_prospect_date" class="form-label"><i
                                            class="fa fa-calendar-alt me-1"></i> Future Contact Date</label>
                                    <input type="date" class="form-control" id="future_prospect_date"
                                        name="future_prospect_date">
                                </div>
                                <div class="form-group mb-3" id="prospect_close_rate_group" style="display: none;">
                                    <label for="prospect_rate" class="form-label"><i class="fa fa-rupee-sign me-1"></i>
                                        Rate (₹)</label>
                                    <input type="number" class="form-control" id="prospect_rate" name="prospect_rate"
                                        min="0" step="0.01">
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i
                            class="fa fa-times me-1"></i> Close</button>
                    <button type="button" class="btn btn-primary" id="saveLead"><i class="fas fa-save me-1"></i> Save
                        Lead</button>
                </div>
            </div>
        </div>
    </div>

    <!-- View Lead Modal -->
    <div class="modal fade" id="viewLeadModal" tabindex="-1" role="dialog" aria-labelledby="viewLeadModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="viewLeadModalLabel"><i class="fa fa-eye me-2"></i>Lead Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"
                        style="background-color: #FD7E14; color:white; border:none;">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="mb-3 p-3 bg-light rounded">
                                <label class="fw-bold"><i class="fa fa-hashtag me-1"></i> Lead No:</label>
                                <p id="view_id"></p>
                            </div>
                            <div class="mb-3 p-3 bg-light rounded">
                                <label class="fw-bold"><i class="fa fa-calendar-alt me-1"></i> Date:</label>
                                <p id="view_date"></p>
                            </div>
                            <div class="mb-3 p-3 bg-light rounded">
                                <label class="fw-bold"><i class="fa fa-user-tie me-1"></i> Executive:</label>
                                <p id="view_executive"></p>
                            </div>
                            <div class="mb-3 p-3 bg-light rounded">
                                <label class="fw-bold"><i class="fa fa-user me-1"></i> Customer Name:</label>
                                <p id="view_customer_name"></p>
                            </div>
                            <div class="mb-3 p-3 bg-light rounded">
                                <label class="fw-bold"><i class="fa fa-user-injured me-1"></i> Patient Name:</label>
                                <p id="view_patient_name"></p>
                            </div>
                            <div class="mb-3 p-3 bg-light rounded">
                                <label class="fw-bold"><i class="fa fa-venus-mars me-1"></i> Patient Gender:</label>
                                <p id="view_patient_gender"></p>
                            </div>
                            <div class="mb-3 p-3 bg-light rounded">
                                <label class="fw-bold"><i class="fa fa-calendar me-1"></i> Age:</label>
                                <p id="view_age"></p>
                            </div>
                            <div class="mb-3 p-3 bg-light rounded">
                                <label class="fw-bold"><i class="fa fa-address-book me-1"></i> Contact Type:</label>
                                <p id="view_contact_type"></p>
                            </div>
                            <div class="mb-3 p-3 bg-light rounded">
                                <label class="fw-bold"><i class="fa fa-phone me-1"></i> Contact No:</label>
                                <p id="view_contact_no"></p>
                            </div>
                            <div class="mb-3 p-3 bg-light rounded">
                                <label class="fw-bold"><i class="fa fa-phone-alt me-1"></i> Last Call Status:</label>
                                <p id="view_last_call_status"></p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3 p-3 bg-light rounded">
                                <label class="fw-bold"><i class="fa fa-map-marker-alt me-1"></i> Location:</label>
                                <p id="view_location"></p>
                            </div>
                            <div class="mb-3 p-3 bg-light rounded">
                                <label class="fw-bold"><i class="fa fa-globe me-1"></i> Lead Source:</label>
                                <p id="view_lead_source"></p>
                            </div>
                            <div class="mb-3 p-3 bg-light rounded">
                                <label class="fw-bold"><i class="fa fa-question-circle me-1"></i> Query:</label>
                                <p id="view_query"></p>
                            </div>
                            <div class="mb-3 p-2 bg-light rounded" id="view_query_remarks_group" style="display: none;">
                                <label class="fw-bold" style="color:#FD7E14;"><i class="fa fa-comment me-1"></i> Query Remarks:</label>
                                <p id="view_query_remarks"></p>
                            </div>
                            <div class="mb-3 p-3 bg-light rounded">
                                <label class="fw-bold"><i class="fa fa-info-circle me-1"></i> Status:</label>
                                <p id="view_status"></p>
                            </div>
                            <div class="mb-3 p-2 bg-light rounded" id="view_status_remarks_group" style="display: none;">
                                <label class="fw-bold" style="color:#FD7E14;"><i class="fa fa-comment me-1"></i> Status Remarks:</label>
                                <p id="view_status_remarks"></p>
                            </div>
                            <div class="mb-3 p-3 bg-light rounded">
                                <label class="fw-bold"><i class="fa fa-layer-group me-1"></i> Stage:</label>
                                <p id="view_stage"></p>
                            </div>
                            <div class="mb-3 p-3 bg-light rounded">
                                <label class="fw-bold"><i class="fa fa-clock me-1"></i> Shift Type:</label>
                                <p id="view_shift_type"></p>
                            </div>
                            <div class="mb-3 p-3 bg-light rounded" id="view_future_date_group">
                                <label class="fw-bold"><i class="fa fa-calendar-alt me-1"></i> Future Contact
                                    Date:</label>
                                <p id="view_future_prospect_date"></p>
                            </div>
                            <div class="mb-3 p-3 bg-light rounded" id="view_close_rate_group">
                                <label class="fw-bold"><i class="fa fa-rupee-sign me-1"></i> Rate:</label>
                                <p id="view_prospect_rate"></p>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="mb-3 p-3 bg-light rounded" id="view_recordings_group" style="display:none;">
                                <label class="fw-bold"><i class="fa fa-microphone me-1"></i> Call Recordings:</label>
                                <div id="view_recordings"></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i
                            class="fa fa-times me-1"></i> Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Delete Confirmation Modal -->
    <div class="modal fade" id="deleteConfirmModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Delete Lead</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete this lead? This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmDelete">Delete</button>
                </div>
            </div>
        </div>
    </div>

    <aside class="control-sidebar control-sidebar-dark" style="display: none;">
        <div class="p-3 control-sidebar-content">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0">Filters</h5>
                <button type="button" class="btn btn-sm btn-outline-light control-sidebar-close">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <hr class="mb-2">
            <form id="filters-form" method="post">
                @csrf
                <div class="accordion text-sm" id="accordionExample">
                    <div class="accordion-item">
                        <div class="accordion text-sm" id="accordionExample">
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="btn btn-block btn-sm btn-secondary text-left text-bold text-light"
                                        type="button" data-bs-toggle="collapse" data-bs-target="#collapse1"
                                        aria-expanded="true" aria-controls="collapse1">Lead assigned to Executive</button>
                                </h2>
                                <div id="collapse1"
                                    class="accordion-collapse collapse {{ isset($filter_params['executives']) ? 'show' : '' }}"
                                    data-bs-parent="#accordionExample">
                                    <div class="accordion-body pl-2 pb-4">
                                        @foreach ($executives as $executive)
                                        <div class="custom-control custom-checkbox my-1">
                                            <input class="custom-control-input" type="checkbox"
                                                id="team_member_{{ $executive->id }}" name="executives[]"
                                                value="{{ $executive->id }}" {{ isset($filter_params['executives']) &&
                                                in_array($executive->id, $filter_params['executives']) ? 'checked' : '' }}>
                                            <label for="team_member_{{ $executive->id }}" class="custom-control-label">{{
                                                $executive->f_name }}</label>
                                        </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="btn btn-block btn-sm btn-secondary text-left text-bold text-light"
                                        type="button" data-bs-toggle="collapse" data-bs-target="#collapse2"
                                        aria-expanded="true" aria-controls="collapse2">Location</button>
                                </h2>
                                <div id="collapse2"
                                    class="accordion-collapse collapse {{ isset($filter_params['location']) ? 'show' : '' }}"
                                    data-bs-parent="#accordionExample">
                                    <div class="accordion-body pl-2 pb-4">
                                        @foreach ($locations as $location)
                                        <div class="custom-control custom-checkbox my-1">
                                            <input class="custom-control-input" type="checkbox"
                                                id="team_member_{{ $location->name }}" name="location[]"
                                                value="{{ $location->name }}" {{ isset($filter_params['location']) &&
                                                in_array($location->name, $filter_params['location']) ? 'checked' : '' }}>
                                            <label for="team_member_{{ $location->name }}" class="custom-control-label">{{
                                                $location->name }}</label>
                                        </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="btn btn-block btn-sm btn-secondary text-left text-bold text-light"
                                type="button" data-bs-toggle="collapse" data-bs-target="#collapseLeadSource"
                                aria-expanded="true" aria-controls="collapseLeadSource">Lead Source</button>
                        </h2>
                        <div id="collapseLeadSource" class="accordion-collapse collapse" data-bs-parent="#accordionExample">
                            <div class="accordion-body pl-2 pb-4">
                                @foreach (["web", "ivrs", "whatsapp", "manual"] as $source)
                                <div class="custom-control custom-checkbox my-1">
                                    <input class="custom-control-input" type="checkbox"
                                        id="filter_lead_source_{{ $source }}" name="lead_source[]"
                                        value="{{ $source }}" {{ isset($filter_params['lead_source']) && in_array($source, $filter_params['lead_source']) ? 'checked' : '' }}>
                                    <label for="filter_lead_source_{{ $source }}" class="custom-control-label">{{ ucfirst($source) }}</label>
                                </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="btn btn-block btn-sm btn-secondary text-left text-bold text-light"
                                type="button" data-bs-toggle="collapse" data-bs-target="#collapseQuery"
                                aria-expanded="true" aria-controls="collapseQuery">Query</button>
                        </h2>
                        <div id="collapseQuery" class="accordion-collapse collapse" data-bs-parent="#accordionExample">
                            <div class="accordion-body pl-2 pb-4">
                                @foreach ($services as $service)
                                <div class="custom-control custom-checkbox my-1">
                                    <input class="custom-control-input" type="checkbox"
                                        id="filter_query_{{ str_replace(' ', '_', $service->name) }}" name="query_filter[]"
                                        value="{{ $service->name }}" {{ isset($filter_params['query_filter']) && in_array($service->name, $filter_params['query_filter']) ? 'checked' : '' }}>
                                    <label for="filter_query_{{ str_replace(' ', '_', $service->name) }}" class="custom-control-label">{{ $service->name }}</label>
                                </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="btn btn-block btn-sm btn-secondary text-left text-bold text-light"
                                type="button" data-bs-toggle="collapse" data-bs-target="#collapseStatus"
                                aria-expanded="true" aria-controls="collapseStatus">Status</button>
                        </h2>
                        <div id="collapseStatus" class="accordion-collapse collapse" data-bs-parent="#accordionExample">
                            <div class="accordion-body pl-2 pb-4">
                                @foreach (["follow-up", "future prospect", "prospect", "no response", "price issue", "duplicate", "spam"] as $status)
                                <div class="custom-control custom-checkbox my-1">
                                    <input class="custom-control-input" type="checkbox"
                                        id="filter_status_{{ str_replace(' ', '_', $status) }}" name="status[]"
                                        value="{{ $status }}" {{ isset($filter_params['status']) && in_array($status, $filter_params['status']) ? 'checked' : '' }}>
                                    <label for="filter_status_{{ str_replace(' ', '_', $status) }}" class="custom-control-label">{{ ucfirst($status) }}</label>
                                </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="btn btn-block btn-sm btn-secondary text-left text-bold text-light"
                                type="button" data-bs-toggle="collapse" data-bs-target="#collapseStage"
                                aria-expanded="true" aria-controls="collapseStage">Stage</button>
                        </h2>
                        <div id="collapseStage" class="accordion-collapse collapse" data-bs-parent="#accordionExample">
                            <div class="accordion-body pl-2 pb-4">
                                @foreach (["active", "inactive", "closed", "profile required"] as $stage)
                                <div class="custom-control custom-checkbox my-1">
                                    <input class="custom-control-input" type="checkbox"
                                        id="filter_stage_{{ str_replace(' ', '_', $stage) }}" name="stage[]"
                                        value="{{ $stage }}" {{ isset($filter_params['stage']) && in_array($stage, $filter_params['stage']) ? 'checked' : '' }}>
                                    <label for="filter_stage_{{ str_replace(' ', '_', $stage) }}" class="custom-control-label">{{ ucfirst($stage) }}</label>
                                </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
                <div class="my-5">
                    <button type="submit" class="btn btn-sm btn-block"
                        style="background-color: var(--wb-renosand);">Apply</button>
                        <button type="button" class="btn btn-sm btn-block btn-secondary mt-2" onclick="window.location.reload();">Reset</button>
                </div>
            </form>
        </div>
    </aside>

    <!-- Service Rates Modal -->
    <div class="modal fade" id="serviceRatesModal" tabindex="-1" role="dialog" aria-labelledby="serviceRatesModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header" style="background-color:#FD7E14;">
                    <h5 class="modal-title" id="serviceRatesModalLabel" style="color:#fff;">Service Rates & Description</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" style="background-color:#FD7E14; color:#fff;">&times;</button>
                </div>
                <div class="modal-body">
                    <div id="serviceRatesContent">
                        <div class="text-center">
                            <div class="spinner-border text-primary" role="status">
                                <span class="sr-only">Loading...</span>
                            </div>
                            <p class="mt-2">Loading service rates...</p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    @include('whatsapp.chat');
@endsection

@section('footer-script')
    <script src="https://cdn.datatables.net/1.13.1/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.4.0/js/dataTables.responsive.min.js"></script>
    <script src="{{ asset('plugins/moment/moment.min.js') }}"></script>
    <script>
        function handle_whatsapp_msg(id) {
            const elementToUpdate = document.querySelector(`#what_id-${id}`);
            if (elementToUpdate) {
                elementToUpdate.outerHTML =
                    `<i class="fab fa-whatsapp" onclick="handle_whatsapp_msg(${id})" style="font-size: 25px; color: green;"></i>`;
            }
            const form_title = document.querySelector(`#form_title_modal`);
            form_title.innerHTML = `Whatsapp Messages of ${id}`;
            const manageWhatsappChatModal = new bootstrap.Modal(document.getElementById('wa_msg'));
            wamsg(id);
            manageWhatsappChatModal.show();
            const wa_status_url = `{{ route('whatsapp_chat.status') }}`;
            const wa_status_data = {
                mobile: id
            };
            fetch(wa_status_url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(wa_status_data),
                })
                .then(response => response.json())
                .then(data => {})
                .catch((error) => {});
        }


        // Add this function at the beginning of your script
        function makeCall(customerNumber) {
            if (!confirm('Are you sure you want to call this number?')) return;
            const $btn = $(event.target).closest('.call-btn');
            $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');
            $.ajax({
                url: `/call-outbound/${customerNumber}`,
                type: 'GET',
                success: function(response) {
                    if (response.success) {
                        toastr.success('Call initiated successfully');
                    } else {
                        toastr.error(response.message || 'Failed to initiate call');
                    }
                },
                error: function(xhr) {
                    let errorMessage = 'Error initiating call';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    }
                    toastr.error(errorMessage);
                },
                complete: function() {
                    $btn.prop('disabled', false).html('<i class="fas fa-phone"></i>');
                }
            });
        }

        // Function to handle audio loading issues
        function handleAudioLoading() {
            $('audio').each(function() {
                const audio = this;
                const url = $(audio).find('source').first().attr('src');
                
                // Handle audio events
                audio.addEventListener('loadedmetadata', function() {
                    console.log('Audio metadata loaded for:', url, 'Duration:', this.duration);
                });
                
                audio.addEventListener('canplay', function() {
                    console.log('Audio can play:', url);
                });
                
                audio.addEventListener('error', function(e) {
                    console.error('Audio loading error for:', url, e);
                    
                    // Show fallback message
                    if (!$(audio).next('.audio-fallback').length) {
                        $(audio).after(`
                            <div class="audio-fallback" style="color: #dc3545; font-size: 12px; margin-top: 5px;">
                                <i class="fa fa-exclamation-triangle"></i> Audio playback not supported. 
                                <a href="${url}" target="_blank" class="text-primary">Click to download</a>
                            </div>
                        `);
                    }
                });
                
                // Try to load the audio
                audio.load();
            });
        }

        // Global function to stop all audio
        function stopAllAudio() {
            $('audio').each(function() {
                if (!this.paused) {
                    this.pause();
                    this.currentTime = 0; // Reset to beginning
                    console.log('Audio stopped and reset');
                }
            });
        }

        // Function to enable audio playback in modal
        function enableModalAudioPlayback() {
            // Add click handler to modal to enable audio
            $('#viewLeadModal').on('click', function() {
                $('audio').each(function() {
                    this.play().catch(function(e) {
                        console.log('Autoplay prevented, user interaction required:', e);
                    });
                });
            });
            
            // Add click handler to audio elements
            $(document).on('click', 'audio', function() {
                // Stop all other audio before playing this one
                $('audio').not(this).each(function() {
                    if (!this.paused) {
                        this.pause();
                        this.currentTime = 0;
                    }
                });
                
                this.play().catch(function(e) {
                    console.log('Audio play failed:', e);
                });
            });
            
            // Add play event handler to stop other audio when one starts playing
            $(document).on('play', 'audio', function() {
                $('audio').not(this).each(function() {
                    if (!this.paused) {
                        this.pause();
                        this.currentTime = 0;
                    }
                });
            });
            
            // Stop all audio when modal closes
            $('#viewLeadModal').on('hidden.bs.modal', function() {
                console.log('Modal closed - stopping all audio');
                stopAllAudio();
            });
            
            // Stop all audio when modal is hidden (alternative event)
            $('#viewLeadModal').on('hide.bs.modal', function() {
                console.log('Modal hiding - stopping all audio');
                stopAllAudio();
            });
        }

        $(function() {
            // Set up CSRF token for all AJAX requests
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            // Initialize modal audio playback
            enableModalAudioPlayback();

            // Initialize DataTable
            var table = $('#leads-table').DataTable({
                processing: true,
                serverSide: false,
                responsive: false,
                ajax: {
                    url: '{{ route('admin.leads.getLeads') }}',
                    data: function(d) {
                        // Only add filter params, do not overwrite DataTables params
                        var filterData = $('#filters-form').serializeArray();
                        var filterFields = {};
                        // Collect all filter fields as arrays (flat, never nested)
                        filterData.forEach(function(item) {
                            // Remove [] from name if present
                            var name = item.name.replace(/\[\]$/, '');
                            if (filterFields[name]) {
                                filterFields[name].push(item.value);
                            } else {
                                filterFields[name] = [item.value];
                            }
                        });
                        // Add to d only if at least one value is selected
                        Object.keys(filterFields).forEach(function(key) {
                            // Only add if at least one non-empty value
                            var values = filterFields[key].filter(function(v) { return v !== ''; });
                            if (values.length > 0) {
                                d[key] = values;
                            } else {
                                delete d[key];
                            }
                        });
                    },
                    error: function(xhr, error, thrown) {
                        toastr.error('Error loading leads data. Please try again.');
                    }
                },
                columns: [{
                        data: 'lead_code',
                        name: 'lead_code'
                    },
                    {
                        data: 'date',
                        name: 'date',
                        className: 'no-wrap',

                        render: function(data) {
                            return moment(data).format('DD-MMM HH:mm');
                        }
                    },
                    {
                        data: 'executive',
                        name: 'executive'
                    },
                    {
                        data: 'customer_name',
                        name: 'customer_name'
                    },
                    {
                        data: 'patient_name',
                        name: 'patient_name'
                    },
                    {
                        data: 'patient_gender',
                        name: 'patient_gender',
                        render: function(data, type, row) {
                            if (!data) return '-';
                            let genderClass = '';
                            let genderText = '';
                            switch(data.toLowerCase()) {
                                case 'male': genderClass = 'badge bg-primary'; genderText = 'Male'; break;
                                case 'female': genderClass = 'badge bg-danger'; genderText = 'Female'; break;
                                case 'other': genderClass = 'badge bg-secondary'; genderText = 'Other'; break;
                                default: return data;
                            }
                            return `<span class="${genderClass}">${genderText}</span>`;
                        }
                    },
                    {
                        data: 'contact_no',
                        name: 'contact_no',
                        render: function(data, type, row) {
                            return `
                               <div class=""> ${data}
                                <i class="contact-icons fas fa-phone" style="margin-left: 8px; vertical-align: middle;" onclick="makeCall('${data}')"></i>
                                <i class="contact-icons fab fa-whatsapp" style="margin-left: 4px; vertical-align: middle; color: green; cursor: pointer;" onclick="handle_whatsapp_msg('${data}')" id="what_id-${data}" title="WhatsApp"></i>
                                </div>
                            `;
                        }
                    },
                                        {
                        data: null,
                        name: 'last_call_status',
                        render: function(data, type, row) {
                            // Get last call status from database or recordings
                            let lastCallStatus = row.last_call_status;

                            // If no last_call_status in database, try to get from recordings
                            if (!lastCallStatus && row.recording_url) {
                                try {
                                    let recordings = JSON.parse(row.recording_url);
                                    if (Array.isArray(recordings) && recordings.length > 0) {
                                        let lastRecording = recordings[recordings.length - 1];
                                        if (lastRecording.metadata) {
                                            let metadata = JSON.parse(lastRecording.metadata);
                                            lastCallStatus = metadata.dialstatus;
                                        }
                                    }
                                } catch (e) {
                                    // Ignore parsing errors
                                }
                            }

                            if (!lastCallStatus) return '-';

                            let statusClass = '';
                            let displayText = '';

                            switch (lastCallStatus.toLowerCase()) {
                                case 'answered':
                                    statusClass = 'badge bg-success';
                                    displayText = 'Answered';
                                    break;
                                case 'busy':
                                    statusClass = 'badge bg-warning';
                                    displayText = 'Busy';
                                    break;
                                case 'no answer':
                                case 'noanswer':
                                    statusClass = 'badge bg-danger';
                                    displayText = 'Missed';
                                    break;
                                case 'failed':
                                    statusClass = 'badge bg-secondary';
                                    displayText = 'Failed';
                                    break;
                                default:
                                    statusClass = 'badge bg-info';
                                    displayText = lastCallStatus.charAt(0).toUpperCase() + lastCallStatus.slice(1);
                            }
                            return `<span class="${statusClass}">${displayText}</span>`;
                        }
                    },
                    {
                        data: 'location',
                        name: 'location'
                    },
                    {
                        data: 'lead_source',
                        name: 'lead_source'
                    },
                    {
                        data: 'query',
                        name: 'query'
                    },
                    {
                        data: 'status',
                        name: 'status',
                        render: function(data, type, row) {
                            let statusClass = '';
                            switch (data) {
                                case 'follow-up':
                                    statusClass = 'badge bg-info';
                                    break;
                                case 'future prospect':
                                    statusClass = 'badge bg-warning';
                                    break;
                                case 'prospect':
                                    statusClass = 'badge bg-success';
                                    break;
                                case 'price issue':
                                    statusClass = 'badge bg-secondary';
                                    break;
                                case 'no response':
                                    statusClass = 'badge bg-secondary';
                                    break;
                                case 'duplicate':
                                    statusClass = 'badge bg-danger';
                                    break;
                                case 'spam':
                                    statusClass = 'badge bg-dark';
                                    break;
                                default:
                                    statusClass = 'badge bg-secondary';
                            }
                            return `<span class="${statusClass}">${data ? data.charAt(0).toUpperCase() + data.slice(1) : '-'}</span>`;
                        }
                    },
                    {
                        data: 'stage',
                        name: 'stage',
                        render: function(data, type, row) {
                            if (!data) return '-';
                            let stageClass = 'status-default';
                            let displayText = data.charAt(0).toUpperCase() + data.slice(1);

                            switch (data) {
                                case 'active':
                                    stageClass = 'status-active';
                                    break;
                                case 'inactive':
                                    stageClass = 'status-inactive';
                                    break;
                                case 'closed':
                                    stageClass = 'status-closed';
                                    break;
                                case 'profile required':
                                    stageClass = 'status-profile-required';
                                    break;
                            }
                            return `<span class="status-badge ${stageClass}">${displayText}</span>`;
                        }
                    },
                    {
                        data: null,
                        orderable: false,
                        searchable: false,
                        render: function(data, type, row) {
                            return `
                                <div class="btn-group" role="group">
                                    <button class="action-btn view-btn" data-id="${row.id}" title="View">
                                        <i class="fa fa-eye"></i>
                                    </button>
                                    <button class="action-btn edit-btn" data-id="${row.id}" title="Edit">
                                        <i class="fa fa-pen-to-square"></i>
                                    </button>
                                    <button class="action-btn delete-btn" data-id="${row.id}" title="Delete">
                                        <i class="fa fa-trash-can"></i>
                                    </button>
                                </div>
                            `;
                        }
                    }
                ],
                order: [
                    [0, 'desc']
                ]
            });

            // Initialize Bootstrap 5 Modal
            var editLeadModal = new bootstrap.Modal(document.getElementById('editLeadModal'));

            // Handle edit button click
            $(document).on('click', '.edit-btn', function() {
                var leadId = $(this).data('id');
                var editLeadModal = new bootstrap.Modal(document.getElementById('editLeadModal'));

                $.ajax({
                    url: '/admin/leads/' + leadId + '/edit',
                    type: 'GET',
                    success: function(response) {
                        // Set all fields
                        $('#edit_lead_id').val(response.id);
                        $('#edit_executive').val(response.executive);
                        $('#edit_customer_name').val(response.customer_name);
                        $('#edit_patient_name').val(response.patient_name);
                        $('#edit_patient_gender').val(response.patient_gender);
                        $('#edit_age').val(response.age);
                        $('#edit_contact_type').val(response.contact_type);
                        $('#edit_contact_no').val(response.contact_no);
                        $('#edit_location').val(response.location);
                        $('#edit_lead_source').val(response.lead_source);
                        $('#edit_query').val(response.query);
                        $('#edit_status').val(response.status);
                        $('#edit_stage').val(response.stage);
                        $('#edit_shift_type').val(response.shift_type);
                        $('#edit_future_prospect_date').val(response.future_prospect_date);
                        $('#edit_prospect_rate').val(response.prospect_rate);
                        $('#edit_query_remarks').val(response.query_remarks || '');

                        // Show/hide query remarks based on query
                        if (response.query && response.query !== 'job request') {
                            $('#edit_query_remarks_group').show();
                        }

                        // Show modal
                        editLeadModal.show();
                    },
                    error: function(xhr) {
                        let errorMessage = 'Error loading lead data';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        }
                        toastr.error(errorMessage);
                    }
                });
            });

            // Handle update button click
            $('#updateLead').on('click', function() {
                var $btn = $(this);
                var formData = $('#editLeadForm').serializeArray();
                var data = {};

                // Convert form data to object
                $(formData).each(function(index, obj) {
                    data[obj.name] = obj.value;
                });

                var leadId = $('#edit_lead_id').val();

                // Disable button and show loading state
                $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Updating...');

                $.ajax({
                    url: '/admin/leads/' + leadId,
                    type: 'PUT',
                    data: data,
                    success: function(response) {
                        toastr.success(response.message || 'Lead updated successfully');
                        var editLeadModal = bootstrap.Modal.getInstance(document.getElementById(
                            'editLeadModal'));
                        editLeadModal.hide();
                        table.ajax.reload(null, false);
                    },
                    error: function(xhr) {
                        let errorMessage = 'Error updating lead';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        } else if (xhr.responseJSON && xhr.responseJSON.errors) {
                            errorMessage = Object.values(xhr.responseJSON.errors).flat().join(
                                '<br>');
                        }
                        toastr.error(errorMessage);
                    },
                    complete: function() {
                        // Reset button state
                        $btn.prop('disabled', false).html('Update Lead');
                    }
                });
            });

            // Handle editable field click
            $(document).on('click', '.editable', function() {
                var leadId = $(this).data('id');
                $('.edit-btn[data-id="' + leadId + '"]').click();
            });

            // Handle modal close
            $('#editLeadModal').on('hidden.bs.modal', function() {
                $('#editLeadForm')[0].reset();
            });

            // Handle save button click for new lead
            $('#saveLead').on('click', function() {
                var $btn = $(this);
                var formData = $('#addLeadForm').serializeArray();
                var data = {};

                // Convert form data to object and handle special fields
                $(formData).each(function(index, obj) {
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

                // Disable button and show loading state
                $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Saving...');

                $.ajax({
                    url: '{{ route('admin.leads.store') }}',
                    type: 'POST',
                    data: data,
                    success: function(response) {
                        toastr.success(response.message || 'Lead created successfully');
                        var addLeadModal = bootstrap.Modal.getInstance(document.getElementById(
                            'addLeadModal'));
                        addLeadModal.hide();
                        table.ajax.reload(null, false);
                        $('#addLeadForm')[0].reset();
                    },
                    error: function(xhr) {
                        let errorMessage = 'Error creating lead';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        } else if (xhr.responseJSON && xhr.responseJSON.errors) {
                            errorMessage = Object.values(xhr.responseJSON.errors).flat().join(
                                '<br>');
                        }
                        toastr.error(errorMessage);
                    },
                    complete: function() {
                        // Reset button state
                        $btn.prop('disabled', false).html('Save Lead');
                    }
                });
            });

            // Handle add modal close
            $('#addLeadModal').on('hidden.bs.modal', function() {
                $('#addLeadForm')[0].reset();
            });

            // Handle status change in add form
            $('#status').on('change', function() {
                const selectedStatus = $(this).val();

                // Hide both conditional fields first
                $('#future_prospect_date_group').hide();
                $('#prospect_close_rate_group').hide();

                // Show relevant field based on selection
                if (selectedStatus === 'future prospect') {
                    $('#future_prospect_date_group').show();
                    $('#future_prospect_date').prop('required', true);
                } else if (selectedStatus === 'prospect') {
                    $('#prospect_close_rate_group').show();
                    $('#prospect_rate').prop('required', true);
                }
            });

            // Handle status change in edit form
            $('#edit_status').on('change', function() {
                const selectedStatus = $(this).val();

                // Hide both conditional fields first
                $('#edit_future_prospect_date_group').hide();
                $('#edit_prospect_close_rate_group').hide();

                // Show relevant field based on selection
                if (selectedStatus === 'future prospect') {
                    $('#edit_future_prospect_date_group').show();
                    $('#edit_future_prospect_date').prop('required', true);
                } else if (selectedStatus === 'prospect') {
                    $('#edit_prospect_close_rate_group').show();
                    $('#edit_prospect_rate').prop('required', true);
                }
            });

            // Handle location change in add form
            $('#location').on('change', function() {
                showServiceRatesAdd();
            });

            // Handle query change in add form
            $('#query').on('change', function() {
                const selectedQuery = $(this).val();
                if (selectedQuery && selectedQuery !== 'job request') {
                    $('#query_remarks_group').show();
                    $('#query_remarks').prop('required', true);
                } else {
                    $('#query_remarks_group').hide();
                    $('#query_remarks').prop('required', false);
                }
                showServiceRatesAdd();
            });

            // Handle location change in edit form
            $('#edit_location').on('change', function() {
                showServiceRates();
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
                showServiceRates();
            });

            // Handle view button click
            $(document).on('click', '.view-btn', function() {
                var leadId = $(this).data('id');
                var $btn = $(this);
                $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');

                $.ajax({
                    url: '/admin/leads/' + leadId + '/edit',
                    type: 'GET',
                    success: function(response) {
                        // Set all fields
                        $('#view_id').text(response.lead_code);
                        $('#view_date').text(moment(response.date).format('DD-MM-YYYY'));
                        $('#view_executive').text(response.executive || '-');
                        $('#view_customer_name').text(response.customer_name || '-');
                        $('#view_patient_name').text(response.patient_name || '-');
                        $('#view_patient_gender').text(response.patient_gender ? response.patient_gender.charAt(0).toUpperCase() + response.patient_gender.slice(1) : '-');
                        $('#view_age').text(response.age || '-');
                        $('#view_contact_type').text(response.contact_type ? response
                            .contact_type.charAt(0).toUpperCase() + response.contact_type
                            .slice(1) : '-');
                        $('#view_contact_no').text(response.contact_no || '-');

                                                // Set last call status with badge
                        let lastCallStatus = response.last_call_status;

                        // If no last_call_status in database, try to get from recordings
                        if (!lastCallStatus && response.recording_url) {
                            try {
                                let recordings = JSON.parse(response.recording_url);
                                if (Array.isArray(recordings) && recordings.length > 0) {
                                    let lastRecording = recordings[recordings.length - 1];
                                    if (lastRecording.metadata) {
                                        let metadata = JSON.parse(lastRecording.metadata);
                                        lastCallStatus = metadata.dialstatus;
                                    }
                                }
                            } catch (e) {
                                // Ignore parsing errors
                            }
                        }

                        if (lastCallStatus) {
                            let callStatusClass = '';
                            let callStatusText = '';

                            switch (lastCallStatus.toLowerCase()) {
                                case 'answered':
                                    callStatusClass = 'bg-success';
                                    callStatusText = 'Answered';
                                    break;
                                case 'busy':
                                    callStatusClass = 'bg-warning';
                                    callStatusText = 'Busy';
                                    break;
                                case 'no answer':
                                case 'noanswer':
                                    callStatusClass = 'bg-danger';
                                    callStatusText = 'Missed';
                                    break;
                                case 'failed':
                                    callStatusClass = 'bg-secondary';
                                    callStatusText = 'Failed';
                                    break;
                                default:
                                    callStatusClass = 'bg-info';
                                    callStatusText = lastCallStatus.charAt(0).toUpperCase() + lastCallStatus.slice(1);
                            }
                            $('#view_last_call_status').html(`<span class="badge ${callStatusClass}">${callStatusText}</span>`);
                        } else {
                            $('#view_last_call_status').text('-');
                        }

                        $('#view_location').text(response.location || '-');
                        $('#view_lead_source').text(response.lead_source ? response.lead_source
                            .toUpperCase() : '-');
                        $('#view_query').text(response.query ? response.query.charAt(0)
                            .toUpperCase() + response.query.slice(1) : '-');

                        // Set query remarks
                        if (response.query_remarks && response.query_remarks.trim() !== '') {
                            $('#view_query_remarks').text(response.query_remarks);
                            $('#view_query_remarks_group').show();
                        } else {
                            $('#view_query_remarks_group').hide();
                        }

                        // Set status with badge
                        let statusClass = '';
                        switch (response.status) {
                            case 'follow-up':
                                statusClass = 'bg-info';
                                break;
                            case 'future prospect':
                                statusClass = 'bg-warning';
                                break;
                            case 'prospect':
                                statusClass = 'bg-success';
                                break;
                            case 'price issue':
                                statusClass = 'bg-secondary';
                                break;
                            case 'no response':
                                statusClass = 'bg-secondary';
                                break;
                            case 'duplicate':
                                statusClass = 'bg-danger';
                                break;
                            case 'spam':
                                statusClass = 'bg-dark';
                                break;
                            default:
                                statusClass = 'bg-secondary';
                        }
                        $('#view_status').html(
                            `<span class="badge ${statusClass}">${response.status ? response.status.charAt(0).toUpperCase() + response.status.slice(1) : '-'}</span>`
                        );

                        // Set status remarks
                        if (response.status_remarks && response.status_remarks.trim() !== '') {
                            $('#view_status_remarks').text(response.status_remarks);
                            $('#view_status_remarks_group').show();
                        } else {
                            $('#view_status_remarks_group').hide();
                        }

                        // Set stage with badge
                        let stageClass = '';
                        switch (response.stage) {
                            case 'active':
                                stageClass = 'status-active';
                                break;
                            case 'inactive':
                                stageClass = 'status-inactive';
                                break;
                            case 'closed':
                                stageClass = 'status-closed';
                                break;
                            case 'profile required':
                                stageClass = 'status-profile-required';
                                break;
                            default:
                                stageClass = 'status-default';
                        }
                        $('#view_stage').html(
                            `<span class="status-badge ${stageClass}">${response.stage ? response.stage.charAt(0).toUpperCase() + response.stage.slice(1) : '-'}</span>`
                        );

                        // Set shift type
                        $('#view_shift_type').text(response.shift_type || '-');

                        // Handle conditional fields
                        if (response.status === 'future prospect' && response
                            .future_prospect_date) {
                            $('#view_future_date_group').show();
                            $('#view_future_prospect_date').text(moment(response
                                .future_prospect_date).format('DD-MM-YYYY'));
                        } else {
                            $('#view_future_date_group').hide();
                        }

                        if (response.status === 'prospect' && response.prospect_rate !== null) {
                            $('#view_close_rate_group').show();
                            $('#view_prospect_rate').text('₹' + parseFloat(response
                                .prospect_rate).toLocaleString('en-IN', {
                                minimumFractionDigits: 2,
                                maximumFractionDigits: 2
                            }));
                        } else {
                            $('#view_close_rate_group').hide();
                        }

                        // Handle call recordings
                        let recordingsArr = [];
                        if (response.recording_url) {
                            try {
                                recordingsArr = typeof response.recording_url === 'string'
                                    ? JSON.parse(response.recording_url)
                                    : response.recording_url;
                            } catch (e) {
                                recordingsArr = [];
                            }
                        }
                        if (Array.isArray(recordingsArr) && recordingsArr.length > 0) {
                            $('#view_recordings_group').show();
                            let html = '';
                            recordingsArr.forEach(function(rec, idx) {
                                let meta = {};
                                try { meta = rec.metadata ? JSON.parse(rec.metadata) : {}; } catch(e) {}
                                html += `<div class="mb-2">
                                    <div class="d-flex align-items-center">
                                        <audio controls preload="metadata" style="vertical-align:middle;max-width:520px; width:500px;" 
                                               onloadedmetadata="console.log('Audio loaded:', this.duration)" 
                                               oncanplay="console.log('Audio can play')"
                                               onerror="console.error('Audio error:', this.error)">
                                            <source src="${rec.url}" type="audio/mpeg">
                                            <source src="${rec.url}" type="audio/wav">
                                            <source src="${rec.url}" type="audio/mp3">
                                            Your browser does not support the audio element.
                                        </audio>
                                        <div style="display:none; color: #dc3545; font-size: 12px; margin-top: 5px;">
                                            <i class="fa fa-exclamation-triangle"></i> Audio playback not supported. 
                                            <a href="${rec.url}" target="_blank" class="text-primary">Click to download</a>
                                        </div>
                                    </div>
                                    <div class="d-flex align-items-center mt-1">
                                        <a href="${rec.url}" target="_blank" class="btn btn-sm btn-outline-primary ms-2" title="Download">
                                            <i class="fa fa-download"></i>
                                        </a>
                                        <span class="text-muted ms-2">
                                            ${(meta.datetime ? 'Date: ' + meta.datetime : '')}
                                            ${(meta.caller_agent ? ' | Agent: ' + meta.caller_agent : '')}
                                            ${(meta.dialstatus ? ' | Dial Status: ' + meta.dialstatus : '')}
                                        </span>
                                    </div>
                                </div>`;
                            });
                            $('#view_recordings').html(html);
                        } else {
                            $('#view_recordings_group').hide();
                        }

                        // Show modal
                        $('#viewLeadModal').modal('show');
                        
                        // Handle audio loading after modal is shown
                        setTimeout(function() {
                            handleAudioLoading();
                            
                            // Enable audio playback in modal
                            $('audio').each(function() {
                                this.load();
                                // Try to play if user has interacted
                                if (this.readyState >= 2) {
                                    this.play().catch(function(e) {
                                        console.log('Autoplay prevented:', e);
                                    });
                                }
                            });
                        }, 500);
                    },
                    error: function(xhr) {
                        let errorMessage = 'Error loading lead data';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        }
                        toastr.error(errorMessage);
                    },
                    complete: function() {
                        $btn.prop('disabled', false).html('<i class="fas fa-eye"></i>');
                    }
                });
            });

            // Add Delete Confirmation Modal
            $('body').append(`
                <div class="modal fade" id="deleteConfirmModal" tabindex="-1" role="dialog">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Delete Lead</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <p>Are you sure you want to delete this lead? This action cannot be undone.</p>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="button" class="btn btn-danger" id="confirmDelete">Delete</button>
                            </div>
                        </div>
                    </div>
                </div>
            `);

            // Handle delete button click
            $(document).on('click', '.delete-btn', function() {
                var leadId = $(this).data('id');
                var deleteModal = new bootstrap.Modal(document.getElementById('deleteConfirmModal'));

                // Store the lead ID in the confirm button
                $('#confirmDelete').data('id', leadId);

                // Show the confirmation modal
                deleteModal.show();
            });

            // Handle confirm delete
            $('#confirmDelete').on('click', function() {
                var leadId = $(this).data('id');
                var $btn = $(this);

                // Disable button and show loading state
                $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Deleting...');

                $.ajax({
                    url: '/admin/leads/' + leadId,
                    type: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        toastr.success(response.message || 'Lead deleted successfully');
                        var deleteModal = bootstrap.Modal.getInstance(document.getElementById(
                            'deleteConfirmModal'));
                        deleteModal.hide();
                        table.ajax.reload(null, false);
                    },
                    error: function(xhr) {
                        let errorMessage = 'Error deleting lead';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        }
                        toastr.error(errorMessage);
                    },
                    complete: function() {
                        // Reset button state
                        $btn.prop('disabled', false).html('Delete');
                    }
                });
            });

            // Reset delete modal when hidden
            $('#deleteConfirmModal').on('hidden.bs.modal', function() {
                $('#confirmDelete').prop('disabled', false).html('Delete');
            });

            // On filter form submit, reload DataTable with new filters
            $('#filters-form').on('submit', function(e) {
                e.preventDefault();
                table.ajax.reload();
            });

            // Function to show service rates modal for add form
            function showServiceRatesAdd() {
                const location = $('#location').val();
                const query = $('#query').val();

                if (location && query && query !== 'job request') {
                    // Show loading toast
                    toastr.info('Loading service rates...', 'Please wait');

                    // Fetch service rates
                    $.ajax({
                        url: '/api/locations/service-rates',
                        type: 'GET',
                        data: {
                            location: location,
                            service: query
                        },
                        success: function(response) {
                            if (response.success) {
                                const data = response.data;
                                let ratesHtml = `
                                    <div class="row">
                                        <div class="col-md-6">
                                            <h6 class="text-primary"><strong>Service:</strong> ${data.service_name}</h6>
                                            <h6 class="text-primary"><strong>Location:</strong> ${data.location_name}</h6>
                                        </div>
                                        <div class="col-md-6">
                                            <h6 class="text-success"><strong>Pricing:</strong></h6>
                                            <ul class="list-unstyled">
                                                <li><strong>12hr:</strong> ₹${data.prices.price_12hr || 'N/A'}</li>
                                                <li><strong>24hr:</strong> ₹${data.prices.price_24hr || 'N/A'}</li>
                                                <li><strong>One-time:</strong> ₹${data.prices.price_onetime || 'N/A'}</li>
                                            </ul>
                                        </div>
                                    </div>
                                `;

                                if (data.service_description) {
                                    ratesHtml += `
                                        <div class="row mt-3">
                                            <div class="col-12">
                                                <h6 class="text-info"><strong>Description:</strong></h6>
                                                <p class="text-muted">${data.service_description}</p>
                                            </div>
                                        </div>
                                    `;
                                }

                                $('#serviceRatesContent').html(ratesHtml);
                                // Show modal only when data is available
                                $('#serviceRatesModal').modal('show');
                                toastr.success('Service rates loaded successfully!');
                            } else {
                                // Show toast message instead of modal when no data
                                toastr.warning(response.message || 'Service rates not available for this location and service combination.', 'No Data Found');
                            }
                        },
                        error: function(xhr) {
                            // Show toast message instead of modal on error
                            toastr.error('Error loading service rates. Please try again.', 'Error');
                        }
                    });
                }
            }

            // Function to show service rates modal for edit form
            function showServiceRates() {
                const location = $('#edit_location').val();
                const query = $('#edit_query').val();

                if (location && query && query !== 'job request') {
                    // Show loading toast
                    toastr.info('Loading service rates...', 'Please wait');

                    // Fetch service rates
                    $.ajax({
                        url: '/api/locations/service-rates',
                        type: 'GET',
                        data: {
                            location: location,
                            service: query
                        },
                        success: function(response) {
                            if (response.success) {
                                const data = response.data;
                                let ratesHtml = `
                                    <div class="row">
                                        <div class="col-md-6">
                                            <h6 class="text-primary"><strong>Service:</strong> ${data.service_name}</h6>
                                            <h6 class="text-primary"><strong>Location:</strong> ${data.location_name}</h6>
                                        </div>
                                        <div class="col-md-6">
                                            <h6 class="text-success"><strong>Pricing:</strong></h6>
                                            <ul class="list-unstyled">
                                                <li><strong>12hr:</strong> ₹${data.prices.price_12hr || 'N/A'}</li>
                                                <li><strong>24hr:</strong> ₹${data.prices.price_24hr || 'N/A'}</li>
                                                <li><strong>One-time:</strong> ₹${data.prices.price_onetime || 'N/A'}</li>
                                            </ul>
                                        </div>
                                    </div>
                                `;

                                if (data.service_description) {
                                    ratesHtml += `
                                        <div class="row mt-3">
                                            <div class="col-12">
                                                <h6 class="text-info"><strong>Description:</strong></h6>
                                                <p class="text-muted">${data.service_description}</p>
                                            </div>
                                        </div>
                                    `;
                                }

                                $('#serviceRatesContent').html(ratesHtml);
                                // Show modal only when data is available
                                $('#serviceRatesModal').modal('show');
                                toastr.success('Service rates loaded successfully!');
                            } else {
                                // Show toast message instead of modal when no data
                                toastr.warning(response.message || 'Service rates not available for this location and service combination.', 'No Data Found');
                            }
                        },
                        error: function(xhr) {
                            // Show toast message instead of modal on error
                            toastr.error('Error loading service rates. Please try again.', 'Error');
                        }
                    });
                }
            }
        });
    </script>
@endsection
