@extends('admin.layouts.app')
@section('title', 'Job-Req')
@section('header-css')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.1/css/jquery.dataTables.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.4.0/css/responsive.dataTables.min.css">
<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
<style>
    /* Main container and card styling */
    .content-wrapper { background: #f8f9fa; }
    .card {
        border: none;
        border-radius: 12px;
        box-shadow: 0 4px 24px rgba(0,0,0,0.05);
        overflow: hidden;
    }
    .card-header {
        background: #fff;
        padding: 1rem 1.5rem;
        border-bottom: 1px solid #f0f0f0;
    }
    .card-body {
        padding: 0;
    }

    /* Table styling to match the image */
    .table.dataTable {
        border-collapse: collapse !important;
        width: 100% !important;
        background: #fff;
    }
    .table thead th {
        color: #888;
        font-weight: 600;
        font-size: 0.9rem;
        background: #fff;
        border-bottom: 1px solid #f0f0f0;
        letter-spacing: 0.02em;
        padding: 1rem 1.25rem;
        text-transform: uppercase;
        white-space: nowrap;
    }
    .table tbody td {
        color: #444;
        font-size: 0.95rem;
        border-top: 1px solid #f0f0f0;
        padding: 1rem 1.25rem;
        vertical-align: middle;
    }
    .table tbody tr:first-child td {
        border-top: none;
    }
    .table tbody tr:hover {
        background: #f8f9fa;
    }
    .table-responsive {
        border-radius: 12px;
    }

    /* Action Button Styling */
    .action-btn {
        background: #f0f2f5;
        border: 1px solid #e0e2e5;
        border-radius: 8px;
        color: #606770;
        width: 38px;
        height: 38px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s;
        font-size: 1rem;
    }
    .action-btn:hover {
        background: #e4e6e9;
        color: #000;
    }

    /* Modal styling */
    .modal-header .close{
        margin: 0px !important;
    }
    .themed-close {
        background: #ea8a2b !important;
        color: #fff !important;
        border: none;
        border-radius: 50%;
        width: 36px;
        height: 36px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        opacity: 1 !important;
        transition: background 0.2s, color 0.2s, box-shadow 0.2s;
        box-shadow: 0 2px 8px #ea8a2b22;
        margin-left: 10px;
    }
    .themed-close:hover, .themed-close:focus {
        background: #d97a1a !important;
        color: #fff !important;
        outline: none;
        box-shadow: 0 4px 12px #ea8a2b44;
    }
    .themed-close span {
        line-height: 1;
        font-weight: bold;
    }
    /* Modal content rounded and shadow */
    .modal-content {
        border-radius: 18px;
        box-shadow: 0 6px 32px #ea8a2b33;
        border: none;
        padding-bottom: 0;
    }
    /* Modal header accent */
    .modal-header {
        border-bottom: 2px solid #ea8a2b;
        background: #fff7f0;
        border-top-left-radius: 18px;
        border-top-right-radius: 18px;
        padding-top: 18px;
        padding-bottom: 12px;
    }
    .modal-title {
        font-weight: 700;
        color: #ea8a2b;
        letter-spacing: 0.5px;
    }
    /* Form fields */
    .form-control {
        border-radius: 8px;
        border: 1.5px solid #e0e0e0;
        transition: border-color 0.2s, box-shadow 0.2s;
        font-size: 1rem;
    }
    .form-control:focus {
        border-color: #ea8a2b;
        box-shadow: 0 0 0 2px #ea8a2b22;
    }
    .form-group label {
        font-weight: 600;
        color: #ea8a2b;
        margin-bottom: 6px;
    }
    /* Modal footer buttons */
    .modal-footer {
        border-top: none;
        padding-bottom: 24px;
        padding-top: 16px;
    }
    .modal-footer .btn-primary {
        background: #ea8a2b;
        border: none;
        border-radius: 20px;
        font-weight: 600;
        padding: 8px 28px;
        transition: background 0.2s;
    }
    .modal-footer .btn-primary:hover {
        background: #d97a1a;
    }
    .modal-footer .btn-secondary {
        background: #f3f3f3;
        color: #222;
        border: none;
        border-radius: 20px;
        font-weight: 600;
        padding: 8px 22px;
        margin-right: 8px;
        transition: background 0.2s;
    }
    .modal-footer .btn-secondary:hover {
        background: #e0e0e0;
        color: #ea8a2b;
    }
    .btn-primary {
        background: #ea8a2b !important;
        border: none !important;
        border-radius: 20px !important;
        font-weight: 600;
        padding: 8px 22px;
        box-shadow: 0 2px 8px #ea8a2b22;
        transition: background 0.2s;
    }
    .btn-primary:hover {
        background: #d97a1a !important;
    }
    .btn-success {
        background: #28a745 !important;
        border: none !important;
        border-radius: 20px !important;
        font-weight: 600;
        padding: 8px 22px;
        box-shadow: 0 2px 8px #28a74522;
        transition: background 0.2s;
    }
    .btn-success:hover {
        background: #218838 !important;
    }
    .table-scroll-rows {
        width: 100%;
        background: #fff;
        border-radius: 12px;
    }
    .scrollable-table {
        width: 100%;
        margin-bottom: 0;
        border-collapse: separate;
    }
    .scrollable-table thead th {
        position: sticky;
        top: 0;
        background: #fff;
        z-index: 2;
    }
    .scrollable-table tbody {
        display: block;
        max-height: 400px;
        overflow-y: auto;
        width: 100%;
    }
    .scrollable-table thead,
    .scrollable-table tbody tr {
        display: table;
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

@section('content')
<div class="content-wrapper pb-5">

    <section class="content">
        <div class="container-fluid pt-3">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title mb-0" style="font-weight: 800; color: #ea8a2b;">Job Requests</h3>
                    <div>
                        <button type="button" class="btn btn-success mr-2" data-toggle="modal" data-target="#importJobRequestModal">
                            <i class="fas fa-file-excel"></i> Import Excel
                        </button>
                        <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#addJobRequestModal">
                            <i class="fas fa-plus"></i> Add New Job Request
                        </button>
                    </div>
                </div>
                <div class="table-scroll-rows">
                    <table id="job-request-table" class="table scrollable-table">
                        <thead class="thead-light">
                            <tr>
                                <th>Lead ID</th>
                                <th>Date/Time</th>
                                <th>Executive</th>
                                <th>Customer</th>
                                <th>Contact No</th>
                                <th>Age</th>
                                <th>12/24 hr</th>
                                <th>Job Title</th>
                                <th>City</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>
</div>

<!-- Add Job Request Modal -->
<div class="modal fade" id="addJobRequestModal" tabindex="-1" role="dialog" aria-labelledby="addJobRequestModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addJobRequestModalLabel">Add New Job Request</h5>
                <button type="button" class="close themed-close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="addJobRequestForm">
                    @csrf
                    <div class="form-group">
                        <label for="date_time">Date/Time</label>
                        <input type="datetime-local" class="form-control" id="date_time" name="date_time" required>
                    </div>
                    <div class="form-group">
                        <label for="executive_id">Executive</label>
                        <select class="form-control" id="executive_id" name="executive_id" required>
                            <option value="">Select Executive</option>
                            @foreach($executives as $executive)
                                <option value="{{ $executive->id }}">{{ $executive->f_name }} {{ $executive->l_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="customer_name">Customer Name</label>
                        <input type="text" class="form-control" id="customer_name" name="customer_name" required>
                    </div>
                    <div class="form-group">
                        <label for="contact_no">Contact No</label>
                        <input type="text" class="form-control" id="contact_no" name="contact_no" required>
                    </div>
                    <div class="form-group">
                        <label for="name">Name</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>
                    <div class="form-group">
                        <label for="age">Age</label>
                        <input type="text" class="form-control" id="age" name="age" required>
                    </div>
                    <div class="form-group">
                        <label for="gender">Gender</label>
                        <select class="form-control" id="gender" name="gender" required>
                            <option value="male">Male</option>
                            <option value="female">Female</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="expected_salary">Expected Salary</label>
                        <input type="number" class="form-control" id="expected_salary" name="expected_salary">
                    </div>
                    <div class="form-group">
                        <label for="shift">12/24 hr</label>
                        <select class="form-control" id="shift" name="shift" required>
                            <option value="12">12</option>
                            <option value="24">24</option>
                            <option value="both">Both</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="total_experience">Total Experience</label>
                        <input type="text" class="form-control" id="total_experience" name="total_experience">
                    </div>
                    <div class="form-group">
                        <label for="job_title">Job Title</label>
                        <select class="form-control" id="job_title" name="job_title" required>
                            <option value="">Select Job Title</option>
                            @foreach($services as $service)
                                <option value="{{ $service->name }}">{{ $service->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="other_remark">Other Remark</label>
                        <textarea class="form-control" id="other_remark" name="other_remark"></textarea>
                    </div>
                    <div class="form-group">
                        <label for="city">Location</label>
                        <select class="form-control" id="city" name="city" required>
                            <option value="">Select Location</option>
                            @foreach($locations as $location)
                                <option value="{{ $location->name }}">{{ $location->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="remark">Remark</label>
                        <textarea class="form-control" id="remark" name="remark"></textarea>
                    </div>
                    <div class="form-group">
                        <label for="status">Status</label>
                        <select class="form-control" id="status" name="status" required>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                            <option value="blacklist">Blacklist</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="saveJobRequest">Save</button>
            </div>
        </div>
    </div>
</div>

<!-- Show Job Request Modal -->
<div class="modal fade" id="showJobRequestModal" tabindex="-1" role="dialog" aria-labelledby="showJobRequestModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="showJobRequestModalLabel">Job Request Details</h5>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Date/Time:</strong> <span id="show_date_time"></span></p>
                        <p><strong>Executive:</strong> <span id="show_executive"></span></p>
                        <p><strong>Customer Name:</strong> <span id="show_customer_name"></span></p>
                        <p><strong>Contact No:</strong> <span id="show_contact_no"></span></p>
                        <p><strong>Name:</strong> <span id="show_name"></span></p>
                        <p><strong>Age:</strong> <span id="show_age"></span></p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Expected Salary:</strong> <span id="show_expected_salary"></span></p>
                        <p><strong>Shift:</strong> <span id="show_shift"></span></p>
                        <p><strong>Total Experience:</strong> <span id="show_total_experience"></span></p>
                        <p><strong>Job Title:</strong> <span id="show_job_title"></span></p>
                        <p><strong>City:</strong> <span id="show_city"></span></p>
                        <p><strong>Status:</strong> <span id="show_status"></span></p>
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-12">
                        <p><strong>Other Remark:</strong></p>
                        <p id="show_other_remark"></p>
                    </div>
                    <div class="col-12">
                        <p><strong>Remark:</strong></p>
                        <p id="show_remark"></p>
                    </div>
                </div>
                
                <!-- Call Recordings Section -->
                <div class="row mt-4" id="recordings_section" style="display: none;">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header text-light" style="background: linear-gradient(135deg, #ea8a2b 0%, #f4a261 100%);">
                                <h5 class="card-title text-light mb-0">
                                    <i class="fa fa-phone"></i> Call Recordings
                                    <span class="badge badge-light float-right" id="recording_count" style="font-size: 12px;">0 Recording(s)</span>
                                </h5>
                            </div>
                            <div class="card-body" id="recordings_content">
                                <!-- Recordings will be loaded here -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeShowModal()">Press Esc for close</button>
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
        </div>        <hr class="mb-2">
        <form id="filters-form" method="post">
            @csrf
            <div class="accordion text-sm" id="accordionExample">

                <!-- Gender Filter -->
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="btn btn-block btn-sm btn-secondary text-left text-bold text-light"
                            type="button" data-bs-toggle="collapse" data-bs-target="#collapseGender"
                            aria-expanded="false" aria-controls="collapseGender">Gender</button>
                    </h2>
                    <div id="collapseGender" class="accordion-collapse collapse" data-bs-parent="#accordionExample">
                        <div class="accordion-body pl-2 pb-4">
                            @foreach (["male", "female", "other"] as $gender)
                            <div class="custom-control custom-checkbox my-1">
                                <input class="custom-control-input" type="checkbox"
                                    id="filter_gender_{{ $gender }}" name="gender[]"
                                    value="{{ $gender }}" {{ isset($filter_params['gender']) && in_array($gender, $filter_params['gender']) ? 'checked' : '' }}>
                                <label for="filter_gender_{{ $gender }}" class="custom-control-label">{{ ucfirst($gender) }}</label>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>
                <!-- Shift Filter -->
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="btn btn-block btn-sm btn-secondary text-left text-bold text-light"
                            type="button" data-bs-toggle="collapse" data-bs-target="#collapseShift"
                            aria-expanded="false" aria-controls="collapseShift">12/24 hr</button>
                    </h2>
                    <div id="collapseShift" class="accordion-collapse collapse" data-bs-parent="#accordionExample">
                        <div class="accordion-body pl-2 pb-4">
                            @foreach (["12", "24", "both"] as $shift)
                            <div class="custom-control custom-checkbox my-1">
                                <input class="custom-control-input" type="checkbox"
                                    id="filter_shift_{{ $shift }}" name="shift[]"
                                    value="{{ $shift }}" {{ isset($filter_params['shift']) && in_array($shift, $filter_params['shift']) ? 'checked' : '' }}>
                                <label for="filter_shift_{{ $shift }}" class="custom-control-label">{{ $shift == 'both' ? 'Both' : $shift . ' hr' }}</label>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>
                <!-- Location Filter -->
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="btn btn-block btn-sm btn-secondary text-left text-bold text-light"
                            type="button" data-bs-toggle="collapse" data-bs-target="#collapseLocation"
                            aria-expanded="false" aria-controls="collapseLocation">Location</button>
                    </h2>
                    <div id="collapseLocation" class="accordion-collapse collapse" data-bs-parent="#accordionExample">
                        <div class="accordion-body pl-2 pb-4">
                            @foreach ($locations as $location)
                            <div class="custom-control custom-checkbox my-1">
                                <input class="custom-control-input" type="checkbox"
                                    id="filter_location_{{ $location->id }}" name="city[]"
                                    value="{{ $location->name }}" {{ isset($filter_params['city']) && in_array($location->name, $filter_params['city']) ? 'checked' : '' }}>
                                <label for="filter_location_{{ $location->id }}" class="custom-control-label">{{ $location->name }}</label>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>
                <!-- Status Filter -->
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="btn btn-block btn-sm btn-secondary text-left text-bold text-light"
                            type="button" data-bs-toggle="collapse" data-bs-target="#collapseStatus"
                            aria-expanded="false" aria-controls="collapseStatus">Status</button>
                    </h2>
                    <div id="collapseStatus" class="accordion-collapse collapse" data-bs-parent="#accordionExample">
                        <div class="accordion-body pl-2 pb-4">
                            @foreach (["active", "inactive", "blacklist"] as $status)
                            <div class="custom-control custom-checkbox my-1">
                                <input class="custom-control-input" type="checkbox"
                                    id="filter_status_{{ $status }}" name="status[]"
                                    value="{{ $status }}" {{ isset($filter_params['status']) && in_array($status, $filter_params['status']) ? 'checked' : '' }}>
                                <label for="filter_status_{{ $status }}" class="custom-control-label">{{ ucfirst($status) }}</label>
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

<!-- Edit Job Request Modal -->
<div class="modal fade" id="editJobRequestModal" tabindex="-1" role="dialog" aria-labelledby="editJobRequestModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                    <h5 class="modal-title" id="editJobRequestModalLabel">Edit Job Request</h5>
                </div>
            <div class="modal-body">
                <form id="editJobRequestForm">
                    @csrf
                    @method('PUT')
                    <input type="hidden" id="edit_id" name="id">
                    <div class="form-group">
                        <label for="edit_date_time">Date/Time</label>
                        <input type="datetime-local" class="form-control" id="edit_date_time" name="date_time" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_executive_id">Executive</label>
                        <select class="form-control" id="edit_executive_id" name="executive_id">
                            <option value="">Select Executive</option>
                            @foreach($executives as $executive)
                                <option value="{{ $executive->id }}">{{ $executive->f_name }} {{ $executive->l_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="edit_customer_name">Customer Name</label>
                        <input type="text" class="form-control" id="edit_customer_name" name="customer_name" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_contact_no">Contact No</label>
                        <input type="text" class="form-control" id="edit_contact_no" name="contact_no" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_name">Name</label>
                        <input type="text" class="form-control" id="edit_name" name="name" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_age">Age</label>
                        <input type="text" class="form-control" id="edit_age" name="age" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_gender">Gender</label>
                        <select class="form-control" id="edit_gender" name="gender">
                            <option value="male">Male</option>
                            <option value="female">Female</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="edit_expected_salary">Expected Salary</label>
                        <input type="number" class="form-control" id="edit_expected_salary" name="expected_salary">
                    </div>
                    <div class="form-group">
                        <label for="edit_shift">12/24 hr</label>
                        <select class="form-control" id="edit_shift" name="shift" required>
                            <option value="12">12</option>
                            <option value="24">24</option>
                            <option value="both">Both</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="edit_total_experience">Total Experience</label>
                        <input type="text" class="form-control" id="edit_total_experience" name="total_experience">
                    </div>
                    <div class="form-group">
                        <label for="edit_job_title">Job Title</label>
                        <select class="form-control" id="edit_job_title" name="job_title" required>
                            <option value="">Select Job Title</option>
                            @foreach($services as $service)
                                <option value="{{ $service->name }}">{{ $service->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="edit_other_remark">Other Remark</label>
                        <textarea class="form-control" id="edit_other_remark" name="other_remark"></textarea>
                    </div>
                    <div class="form-group">
                        <label for="edit_city">Location</label>
                        <select class="form-control" id="edit_city" name="city">
                            <option value="">Select Location</option>
                            @foreach($locations as $location)
                                <option value="{{ $location->name }}">{{ $location->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="edit_remark">Remark</label>
                        <textarea class="form-control" id="edit_remark" name="remark"></textarea>
                    </div>
                    <div class="form-group">
                        <label for="edit_status">Status</label>
                        <select class="form-control" id="edit_status" name="status">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                            <option value="blacklist">Blacklist</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeEditModal()">Press Esc for close</button>
                <button type="button" class="btn btn-info" onclick="debugFormData()">Debug Data</button>
                <button type="button" class="btn btn-primary" id="updateJobRequest">Update</button>
            </div>
        </div>
    </div>
</div>

<!-- Import Job Request Modal -->
<div class="modal fade" id="importJobRequestModal" tabindex="-1" role="dialog" aria-labelledby="importJobRequestModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="importJobRequestModalLabel">Import Job Requests from Excel</h5>
                <button type="button" class="close themed-close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info">
                    <h6><i class="fas fa-info-circle"></i> Excel File Format Requirements:</h6>
                    <p class="mb-2">Your Excel file should have the following columns in the first row (header):</p>
                    <ul class="mb-2">
                        <li><strong>date_time</strong> - Date and time (optional - will be set to current date automatically)</li>
                        <li><strong>executive_name</strong> - Executive name (optional)</li>
                        <li><strong>customer_name</strong> - Customer name (required)</li>
                        <li><strong>contact_no</strong> - Contact number (required)</li>
                        <li><strong>name</strong> - Person name (required)</li>
                        <li><strong>age</strong> - Age (required)</li>
                        <li><strong>gender</strong> - Gender: male, female, or other (required)</li>
                        <li><strong>expected_salary</strong> - Expected salary (optional)</li>
                        <li><strong>shift</strong> - Shift: 12, 24, or both (required)</li>
                        <li><strong>total_experience</strong> - Total experience (optional)</li>
                        <li><strong>job_title</strong> - Job title (required)</li>
                        <li><strong>other_remark</strong> - Other remarks (optional)</li>
                        <li><strong>city</strong> - City/Location (optional)</li>
                        <li><strong>remark</strong> - Remarks (optional)</li>
                        <li><strong>status</strong> - Status: active, inactive, or blacklist (optional, defaults to active)</li>
                    </ul>
                    <p class="mb-0"><strong>Note:</strong> The first row should contain headers, and data should start from the second row.</p>
                    <p class="mb-0 mt-2"><strong>Important:</strong> All uploaded leads will automatically have their date/time set to the current date and time when imported.</p>
                </div>
                
                <div class="text-center mb-3">
                    <button type="button" class="btn btn-outline-primary btn-sm" onclick="downloadTemplate()">
                        <i class="fas fa-download"></i> Download Sample Template
                    </button>
                </div>
                
                <form id="importJobRequestForm" enctype="multipart/form-data">
                    @csrf
                    <div class="form-group">
                        <label for="import_file">Select Excel File (.xlsx or .xls)</label>
                        <input type="file" class="form-control" id="import_file" name="file" accept=".xlsx,.xls" required>
                        <small class="form-text text-muted">Maximum file size: 10MB</small>
                    </div>
                </form>
                
                <div id="import-progress" style="display: none;">
                    <div class="progress">
                        <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 100%">
                            Importing...
                        </div>
                    </div>
                </div>
                
                <div id="import-results" style="display: none;">
                    <div class="alert alert-success">
                        <h6><i class="fas fa-check-circle"></i> Import Results:</h6>
                        <p id="import-success-message"></p>
                    </div>
                    <div id="import-errors" style="display: none;">
                        <div class="alert alert-warning">
                            <h6><i class="fas fa-exclamation-triangle"></i> Errors Found:</h6>
                            <ul id="import-error-list"></ul>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-success" id="importJobRequestBtn">
                    <i class="fas fa-upload"></i> Import File
                </button>
            </div>
        </div>
    </div>
</div>

@include('whatsapp.chat')
@endsection

@section('footer-script')
<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
<script src="https://cdn.datatables.net/1.13.1/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.4.0/js/dataTables.responsive.min.js"></script>
<script>
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

$(function() {
    var table = $('#job-request-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route('admin.jobproc.jobrequests') }}',
            data: function(d) {
                // Only add filter params, do not overwrite DataTables params
                var filterData = $('#filters-form').serializeArray();
                var filterFields = {};
                // Collect all filter fields as arrays (flat, never nested)
                filterData.forEach(function(item) {
                    var name = item.name.replace(/\[\]$/, '');
                    if (filterFields[name]) {
                        filterFields[name].push(item.value);
                    } else {
                        filterFields[name] = [item.value];
                    }
                });
                // Add to d only if at least one value is selected
                Object.keys(filterFields).forEach(function(key) {
                    var values = filterFields[key].filter(function(v) { return v !== ''; });
                    if (values.length > 0) {
                        d[key] = values;
                    } else {
                        delete d[key];
                    }
                });
            }
        },
        responsive: false,
        columns: [
            { data: 'lead_id', name: 'lead_id' },
            {
                data: 'date_time',
                name: 'date_time',
                render: function(data) {
                    return moment(data).format('DD-MMMM HH:mm');
                }
            },
            { data: 'executive', name: 'executive' },
            { data: 'customer_name', name: 'customer_name' },
            {
                data: 'contact_no',
                name: 'contact_no',
                render: function(data, type, row) {
                    return `
                        <div class=""> ${data}
                                <i class="contact-icons fas fa-phone" style="margin-left: 8px; vertical-align: middle;" onclick="makeCall('${data}')"></i>
                                <i class="contact-icons fab fa-whatsapp" style="margin-left: 4px; vertical-align: middle; color: green; cursor: pointer;" onclick="handle_whatsapp_msg('${data}')" id="what_id-${data}" title="WhatsApp"></i></div>
                    `;
                }
            },
            { data: 'age', name: 'age' },
            {
                data: 'shift',
                name: 'shift',
                render: function(data, type, row) {
                    if (!data) return '-';
                    if (data === 'both') return 'Both';
                    return data + ' hr';
                }
            },
            { data: 'job_title', name: 'job_title' },
            { data: 'city', name: 'city' },
            { data: 'status', name: 'status' },
            {
                data: 'id',
                name: 'actions',
                orderable: false,
                searchable: false,
                render: function(data, type, row) {
                    return `
                        <div class="btn-group" role="group">
                            <button class="btn action-btn show-job" data-id="${data}" title="View">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="btn action-btn edit-job" data-id="${data}" title="Edit">
                                <i class="fas fa-pen"></i>
                            </button>
                            <button class="btn action-btn delete-job" data-id="${data}" title="Delete">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    `;
                }
            }
        ],
        order: [[0, 'desc']],
        pageLength: 25
    });

    // On filter form submit, reload DataTable with new filters
    $('#filters-form').on('submit', function(e) {
        e.preventDefault();
        table.ajax.reload();
    });

    // Show Job Request
    $(document).on('click', '.show-job', function() {
        var id = $(this).data('id');
        $.get('{{ url('admin/jobproc') }}/' + id, function(data) {
            $('#show_date_time').text(data.date_time);
            $('#show_executive').text(data.executive_name);
            $('#show_customer_name').text(data.customer_name);
            $('#show_contact_no').text(data.contact_no);
            $('#show_name').text(data.name);
            $('#show_age').text(data.age);
            $('#show_expected_salary').text(data.expected_salary);
            // Display shift with proper formatting
            var shiftDisplay = data.shift;
            if (data.shift === 'both') {
                shiftDisplay = 'Both';
            } else if (data.shift === '12' || data.shift === '24') {
                shiftDisplay = data.shift + ' hr';
            }
            $('#show_shift').text(shiftDisplay);
            $('#show_total_experience').text(data.total_experience);
            $('#show_job_title').text(data.job_title);
            $('#show_other_remark').text(data.other_remark);
            $('#show_city').text(data.city);
            $('#show_remark').text(data.remark);
            $('#show_status').text(data.status);
            $('#show_lead_id').text(data.lead_id);
            
            // Load and display recordings
            loadJobRequestRecordings(data);
            
            $('#showJobRequestModal').modal('show');
        });
    });
    
    // Function to load and display recordings
    function loadJobRequestRecordings(data) {
        const recordingsSection = $('#recordings_section');
        const recordingsContent = $('#recordings_content');
        const recordingCount = $('#recording_count');
        
        if (data.recording_url) {
            try {
                let recordings = [];
                
                // Parse recordings from JobRequest
                if (typeof data.recording_url === 'string') {
                    recordings = JSON.parse(data.recording_url);
                } else if (Array.isArray(data.recording_url)) {
                    recordings = data.recording_url;
                }
                
                if (recordings && recordings.length > 0) {
                    let recordingsHtml = '';
                    
                    recordings.forEach(function(rec, index) {
                        const meta = rec.metadata ? JSON.parse(rec.metadata) : {};
                        const statusClass = meta.dialstatus === 'answered' ? 'badge-success' : 'badge-warning';
                        const statusText = meta.dialstatus ? meta.dialstatus.charAt(0).toUpperCase() + meta.dialstatus.slice(1) : 'N/A';
                        
                        recordingsHtml += `
                            <div class="mb-3 p-3" style="background-color: #fff7f0; border-left: 4px solid #ea8a2b; border-radius: 8px;">
                                <audio controls style="vertical-align:middle;max-width:520px;width:500px;">
                                    <source src="${rec.url}" type="audio/wav">
                                    Your browser does not support the audio element.
                                </audio>
                                <a href="${rec.url}" target="_blank" class="btn btn-sm btn-outline-primary ms-2" title="Download">
                                    <i class="fa fa-download"></i> Download
                                </a>
                                <div class="mt-2">
                                    <span class="badge badge-info">
                                        <i class="fa fa-calendar"></i> ${meta.datetime || 'N/A'}
                                    </span>
                                    <span class="badge badge-secondary">
                                        <i class="fa fa-user"></i> ${meta.caller_agent || 'N/A'}
                                    </span>
                                    <span class="badge ${statusClass}">
                                        <i class="fa fa-phone-alt"></i> ${statusText}
                                    </span>
                                    ${meta.duration ? `<span class="badge badge-primary"><i class="fa fa-clock"></i> ${meta.duration}s</span>` : ''}
                                </div>
                            </div>
                        `;
                    });
                    
                    recordingsContent.html(recordingsHtml);
                    recordingCount.text(`${recordings.length} Recording(s)`);
                    recordingsSection.show();
                } else {
                    recordingsContent.html(`
                        <div class="text-center text-muted py-4">
                            <i class="fa fa-phone-slash fa-3x mb-3" style="opacity: 0.3;"></i>
                            <p>No call recordings found.</p>
                        </div>
                    `);
                    recordingCount.text('0 Recording(s)');
                    recordingsSection.show();
                }
            } catch (error) {
                console.error('Error parsing recordings:', error);
                recordingsContent.html(`
                    <div class="text-center text-muted py-4">
                        <i class="fa fa-exclamation-triangle fa-3x mb-3" style="opacity: 0.3;"></i>
                        <p>Error loading recordings.</p>
                    </div>
                `);
                recordingCount.text('0 Recording(s)');
                recordingsSection.show();
            }
        } else {
            recordingsContent.html(`
                <div class="text-center text-muted py-4">
                    <i class="fa fa-phone-slash fa-3x mb-3" style="opacity: 0.3;"></i>
                    <p>No call recordings found.</p>
                </div>
            `);
            recordingCount.text('0 Recording(s)');
            recordingsSection.show();
        }
    }

    // Edit Job Request
    $(document).on('click', '.edit-job', function() {
        var id = $(this).data('id');
        $.get('{{ url('admin/jobproc') }}/' + id + '/edit', function(data) {
            $('#edit_id').val(data.id);
            $('#edit_date_time').val(data.date_time);
            $('#edit_executive_id').val(data.executive_id);
            $('#edit_customer_name').val(data.customer_name);
            $('#edit_contact_no').val(data.contact_no);
            $('#edit_name').val(data.name);
            $('#edit_age').val(data.age);
            $('#edit_gender').val(data.gender);
            $('#edit_expected_salary').val(data.expected_salary);
            $('#edit_shift').val(data.shift);
            $('#edit_total_experience').val(data.total_experience);
            $('#edit_job_title').val(data.job_title);
            $('#edit_other_remark').val(data.other_remark);
            $('#edit_city').val(data.city);
            $('#edit_remark').val(data.remark);
            $('#edit_status').val(data.status);
            $('#editJobRequestModal').modal('show');
        });
    });

    // Update Job Request
    $('#updateJobRequest').click(function() {
        var id = $('#edit_id').val();

        // Comprehensive form validation
        var requiredFields = {
            'date_time': 'Date/Time',
            'customer_name': 'Customer Name',
            'contact_no': 'Contact No',
            'name': 'Name',
            'age': 'Age',
            'gender': 'Gender',
            'shift': 'Shift',
            'job_title': 'Job Title'
        };

        var missingFields = [];
        for (var field in requiredFields) {
            var value = $('#edit_' + field).val();
            if (!value || value.trim() === '') {
                missingFields.push(requiredFields[field]);
            }
        }

        if (missingFields.length > 0) {
               toastr.error('Please fill in the following required fields:\n' + missingFields.join('\n'));
            return;
        }

        var formData = $('#editJobRequestForm').serialize();

        console.log('Updating job request with ID:', id);
        console.log('Form data:', formData);

        $.ajax({
            url: '{{ url('admin/jobproc') }}/' + id,
            type: 'POST',
            data: formData,
            success: function(response) {
                console.log('Success response:', response);
                $('#editJobRequestModal').modal('hide');
                $('#editJobRequestForm')[0].reset(); // Reset form
                toastr.success('Job Request updated successfully!');
                setTimeout(function() {
                    location.reload();
                }, 1000);
            },
            error: function(xhr, status, error) {
                console.log('Error details:', {
                    status: status,
                    error: error,
                    responseText: xhr.responseText,
                    statusCode: xhr.status
                });

                var errorMessage = 'Error updating Job Request!';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                } else if (xhr.responseJSON && xhr.responseJSON.errors) {
                    // Show validation errors
                    var errors = xhr.responseJSON.errors;
                    var errorList = [];
                    for (var field in errors) {
                        errorList.push(field + ': ' + errors[field][0]);
                    }
                    errorMessage = 'Validation errors:\n' + errorList.join('\n');
                } else if (xhr.responseText) {
                    try {
                        var errorData = JSON.parse(xhr.responseText);
                        if (errorData.message) {
                            errorMessage = errorData.message;
                        }
                    } catch (e) {
                        // If not JSON, use response text
                        errorMessage = xhr.responseText;
                    }
                }

                   toastr.error(errorMessage);
            }
        });
    });

    // Delete Job Request
    $(document).on('click', '.delete-job', function() {
        if(confirm('Are you sure you want to delete this job request?')) {
            var id = $(this).data('id');
            $.ajax({
                url: '{{ url('admin/jobproc') }}/' + id,
                type: 'DELETE',
                data: {
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    $('#job-request-table').DataTable().ajax.reload();
                    alert('Job Request deleted successfully!');
                },
                error: function(error) {
                    alert('Error deleting Job Request!');
                }
            });
        }
    });

    $('#saveJobRequest').on('click', function() {
        // Form validation
        var requiredFields = ['date_time', 'executive_id', 'customer_name', 'contact_no', 'name', 'age', 'gender', 'shift', 'job_title', 'city', 'status'];
        var missingFields = [];
        
        requiredFields.forEach(function(field) {
            var value = $('#' + field).val();
            if (!value || value.trim() === '') {
                missingFields.push(field.replace('_', ' '));
            }
        });
        
        if (missingFields.length > 0) {
               toastr.error('Please fill in the following required fields:\n' + missingFields.join('\n'));
            return;
        }
        
        var formData = $('#addJobRequestForm').serialize();
        console.log('Form data being sent:', formData);
        
        $.ajax({
            url: '{{ route('admin.jobproc.store') }}',
            type: 'POST',
            data: formData,
            success: function(response) {
                console.log('Success response:', response);
                $('#addJobRequestModal').modal('hide');
                $('#addJobRequestForm')[0].reset(); // Reset form
                toastr.success('Job Request added successfully!');
                setTimeout(function() {
                    location.reload();
                }, 1000);
            },
            error: function(xhr, status, error) {
                console.log('Error details:', {
                    status: status,
                    error: error,
                    responseText: xhr.responseText,
                    statusCode: xhr.status
                });
                
                var errorMessage = 'Error adding Job Request!';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                } else if (xhr.responseJSON && xhr.responseJSON.errors) {
                    // Show validation errors
                    var errors = xhr.responseJSON.errors;
                    var errorList = [];
                    for (var field in errors) {
                        errorList.push(field + ': ' + errors[field][0]);
                    }
                    errorMessage = 'Validation errors:\n' + errorList.join('\n');
                } else if (xhr.responseText) {
                    try {
                        var errorData = JSON.parse(xhr.responseText);
                        if (errorData.message) {
                            errorMessage = errorData.message;
                        }
                    } catch (e) {
                        // If not JSON, use response text
                        errorMessage = xhr.responseText;
                    }
                }
                
                   toastr.error(errorMessage);
            }
        });
    });

    // Debug function to show form data
    function debugFormData() {
        var formData = $('#editJobRequestForm').serialize();
        var formDataArray = $('#editJobRequestForm').serializeArray();

        console.log('Form Data (serialized):', formData);
        console.log('Form Data (array):', formDataArray);

        var dataObject = {};
        formDataArray.forEach(function(item) {
            dataObject[item.name] = item.value;
        });
        console.log('Form Data (object):', dataObject);

        alert('Check browser console for form data details');
    }

    // Close modal functions
    function closeEditModal() {
        $('#editJobRequestModal').modal('hide');
    }

    function closeShowModal() {
        $('#showJobRequestModal').modal('hide');
    }

    // Import Job Request functionality
    $('#importJobRequestBtn').on('click', function() {
        var fileInput = $('#import_file')[0];
        var file = fileInput.files[0];
        
        if (!file) {
            alert('Please select a file to import.');
            return;
        }
        
        // Validate file type
        var allowedTypes = ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/vnd.ms-excel'];
        if (!allowedTypes.includes(file.type)) {
            alert('Please select a valid Excel file (.xlsx or .xls).');
            return;
        }
        
        // Validate file size (10MB)
        if (file.size > 10 * 1024 * 1024) {
            alert('File size must be less than 10MB.');
            return;
        }
        
        var formData = new FormData();
        formData.append('file', file);
        formData.append('_token', '{{ csrf_token() }}');
        
        // Show progress
        $('#import-progress').show();
        $('#import-results').hide();
        $('#importJobRequestBtn').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Importing...');
        
        $.ajax({
            url: '{{ route("admin.jobproc.import") }}',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                $('#import-progress').hide();
                $('#import-results').show();
                
                if (response.success) {
                    $('#import-success-message').text(response.message);
                    
                    if (response.error_count > 0) {
                        $('#import-errors').show();
                        var errorList = $('#import-error-list');
                        errorList.empty();
                        response.errors.forEach(function(error) {
                            errorList.append('<li>' + error + '</li>');
                        });
                    } else {
                        $('#import-errors').hide();
                    }
                    
                    // Reload the table
                    $('#job-request-table').DataTable().ajax.reload();
                } else {
                    $('#import-success-message').text('Import failed: ' + response.message);
                }
            },
            error: function(xhr, status, error) {
                $('#import-progress').hide();
                $('#import-results').show();
                
                var errorMessage = 'Error importing file.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                } else if (xhr.responseJSON && xhr.responseJSON.errors) {
                    var errors = xhr.responseJSON.errors;
                    var errorList = [];
                    for (var field in errors) {
                        errorList.push(field + ': ' + errors[field][0]);
                    }
                    errorMessage = 'Validation errors:\n' + errorList.join('\n');
                }
                
                $('#import-success-message').text(errorMessage);
            },
            complete: function() {
                $('#importJobRequestBtn').prop('disabled', false).html('<i class="fas fa-upload"></i> Import File');
            }
        });
    });
    
    // Reset import modal when closed
    $('#importJobRequestModal').on('hidden.bs.modal', function() {
        $('#importJobRequestForm')[0].reset();
        $('#import-progress').hide();
        $('#import-results').hide();
        $('#import-errors').hide();
    });
    
    // Download template function
    window.downloadTemplate = function() {
        // Create a simple CSV template that can be opened in Excel
        var csvContent = "date_time,executive_name,customer_name,contact_no,name,age,gender,expected_salary,shift,total_experience,job_title,other_remark,city,remark,status\n";
        csvContent += ",John Doe,ABC Company,9876543210,Jane Smith,25,female,25000,12,2 years,Nurse,Good communication skills,Mumbai,Urgent requirement,active\n";
        csvContent += ",Jane Smith,XYZ Corp,9876543211,Mike Johnson,30,male,30000,24,5 years,Driver,Valid license,Delhi,Immediate joining,active\n";
        
        var blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
        var link = document.createElement("a");
        var url = URL.createObjectURL(blob);
        link.setAttribute("href", url);
        link.setAttribute("download", "job_request_template.csv");
        link.style.visibility = 'hidden';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    };
});
</script>
@endsection
