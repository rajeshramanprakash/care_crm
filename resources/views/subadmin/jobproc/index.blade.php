@extends('admin.layouts.app')
@section('title', 'Job-Req')
@section('header-css')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.1/css/jquery.dataTables.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.4.0/css/responsive.dataTables.min.css">
<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
@include('admin.doctor_requests.partials.registration_detail_styles')
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
    .jp-price-req-highlight {
        background: #fff3cd;
        border-radius: 4px;
        padding: 2px 6px;
        font-weight: 700;
        color: #856404;
    }
    .action-btn.jp-price-req-btn {
        background: #fff3cd;
        border-color: #ffc107;
        color: #856404;
        position: relative;
    }
    .action-btn.jp-price-req-btn .jp-price-count {
        position: absolute;
        top: -6px;
        right: -6px;
        background: #dc3545;
        color: #fff;
        font-size: 0.65rem;
        font-weight: 700;
        border-radius: 999px;
        min-width: 16px;
        height: 16px;
        line-height: 16px;
        text-align: center;
        padding: 0 4px;
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
            @php $pendingPriceChangeCount = (int) ($pendingPriceChangeCount ?? 0); @endphp
            @if($pendingPriceChangeCount > 0)
                <div class="alert alert-warning d-flex align-items-center mb-3" role="alert">
                    <i class="fas fa-rupee-sign fa-lg mr-2"></i>
                    <div>
                        <strong>{{ $pendingPriceChangeCount }} freelancer price change request{{ $pendingPriceChangeCount === 1 ? '' : 's' }} pending</strong>
                        — Job Title column mein highlighted rows check karein aur <i class="fas fa-rupee-sign"></i> button se review karein.
                    </div>
                </div>
            @endif
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
                        <label for="shift">12/24 hr / One-time</label>
                        <select class="form-control" id="shift" name="shift" required>
                            <option value="12">12 Hours</option>
                            <option value="24">24 Hours</option>
                            <option value="both">Both</option>
                            <option value="onetime">One-time</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="total_experience">Total Experience</label>
                        <input type="text" class="form-control" id="total_experience" name="total_experience">
                    </div>
                    <div class="form-group">
                        <label for="city">Location</label>
                        <select class="form-control location-select" id="city" name="city" required>
                            <option value="">Select Location</option>
                            @foreach($locations as $location)
                                <option value="{{ $location->name }}" data-state="{{ $location->state ?? '' }}" data-city-name="{{ $location->name }}">{{ $location->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="job_title">Job Title (Service)</label>
                        <select class="form-control" id="job_title" name="job_title" required disabled>
                            <option value="">Pehle location select karein</option>
                        </select>
                    </div>

                    @include('admin.jobproc.partials.freelancer-service-panel', ['fieldPrefix' => ''])

                    <div class="form-group">
                        <label for="other_remark">Other Remark</label>
                        <textarea class="form-control" id="other_remark" name="other_remark"></textarea>
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
                        <div id="show_service_pricing_section" style="display:none;" class="mt-2 mb-2">
                            <p class="mb-1"><strong>Services &amp; pricing:</strong></p>
                            <div id="show_service_pricing_list" class="small"></div>
                        </div>
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

                <!-- Attendant profile photo (Gemini uniform) -->
                <div class="row mt-4" id="jp_profile_image_section" style="display: none;">
                    <div class="col-12">
                        <div class="fl-profile-image-admin border rounded p-3 bg-white"
                             id="jpProfileImageAdminWrap"
                             data-generate-url=""
                             data-approve-url=""
                             data-jp-id="">
                            <h6 class="font-weight-bold mb-1" id="jpProfileImageTitle">Profile photo — Carelix uniform (Gemini)</h6>
                            <p class="small text-muted mb-3" id="jpProfileImageDesc">Generate a branded uniform portrait from the freelancer&apos;s upload, preview, then approve to update their profile.</p>
                            <div class="row">
                                <div class="col-md-4 mb-3 mb-md-0">
                                    <p class="small font-weight-bold text-secondary mb-1">Freelancer upload</p>
                                    <div id="jpProfileUploadWrap">
                                        <p class="text-muted small mb-0" id="jpProfileUploadEmpty">No upload yet.</p>
                                        <a href="#" target="_blank" rel="noopener" id="jpProfileUploadLink" class="d-none">
                                            <img src="" alt="Original upload" class="img-fluid rounded border" id="jpProfileUploadImg" style="max-height:200px;object-fit:cover;width:100%;">
                                        </a>
                                    </div>
                                </div>
                                <div class="col-md-8">
                                    <p class="small mb-2">
                                        Status: <span class="badge badge-warning" id="jpProfileImageStatusBadge">Pending admin review</span>
                                    </p>
                                    <div id="jpProfileApprovedWrap" class="d-none mb-2">
                                        <p class="small font-weight-bold text-secondary mb-1">Live profile</p>
                                        <img src="" alt="Approved profile" class="img-fluid rounded border" style="max-height:160px;" id="jpProfileApprovedImg">
                                    </div>
                                    <p class="small font-weight-bold text-secondary mb-1">Generated preview</p>
                                    <div id="jpProfilePendingWrap" class="d-none">
                                        <img src="" alt="Generated preview" class="img-fluid rounded border mb-2" style="max-height:220px;" id="jpProfilePendingImg">
                                    </div>
                                    <p class="small text-muted mb-2" id="jpProfilePendingEmpty">No preview yet. Click generate after the freelancer uploads a photo.</p>
                                    <div class="d-flex flex-wrap align-items-center" style="gap: 8px;">
                                        <button type="button" class="btn btn-info btn-sm" id="jpProfileGenerateBtn" disabled>
                                            <i class="fas fa-magic"></i> Generate uniform preview
                                        </button>
                                        <button type="button" class="btn btn-success btn-sm d-none" id="jpProfileApproveBtn">
                                            <i class="fas fa-check"></i> Approve profile photo
                                        </button>
                                    </div>
                                    <p class="small fl-profile-image-admin-msg mt-2 mb-0" id="jpProfileImageAdminMsg"></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Documents Section -->
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header text-light" style="background: linear-gradient(135deg, #ea8a2b 0%, #f4a261 100%);">
                                <h5 class="card-title text-light mb-0">
                                    <i class="fa fa-file-alt"></i> Documents
                                </h5>
                            </div>
                            <div class="card-body">
                                <!-- Leegality Agreement Section -->
                                <div class="mb-3" id="frlLeegalityPanelWrap">
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <div id="aadhar_card_section" style="display: none;">
                                            <button type="button" class="btn btn-info btn-sm" onclick="viewDocument('aadhar_card', 'Aadhar Card')">
                                                <i class="fa fa-eye"></i> View Aadhar Card
                                            </button>
                                        </div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <div id="pan_card_section" style="display: none;">
                                            <button type="button" class="btn btn-info btn-sm" onclick="viewDocument('pan_card', 'PAN Card')">
                                                <i class="fa fa-eye"></i> View PAN Card
                                            </button>
                                        </div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <div id="qualification_certificate_section" style="display: none;">
                                            <button type="button" class="btn btn-info btn-sm" onclick="viewDocument('qualification_certificate', 'Qualification Certificate')">
                                                <i class="fa fa-eye"></i> View Qualification Certificate
                                            </button>
                                        </div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <div id="bank_document_section" style="display: none;">
                                            <button type="button" class="btn btn-info btn-sm" onclick="viewDocument('bank_document', 'Bank Document')">
                                                <i class="fa fa-eye"></i> View Bank Document
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <!-- Document Viewer -->
                                <div id="document_viewer_section" style="display: none; margin-top: 20px;">
                                    <div class="card">
                                        <div class="card-header bg-primary text-white">
                                            <h6 class="mb-0" id="document_viewer_title"></h6>
                                            <button type="button" class="close text-white" onclick="closeDocumentViewer()" style="opacity: 1; margin-top: -20px;">
                                                <span>&times;</span>
                                            </button>
                                        </div>
                                        <div class="card-body text-center" style="max-height: 600px; overflow-y: auto;">
                                            <div id="document_viewer_content"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
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

<aside class="control-sidebar control-sidebar-dark">
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
                                <label for="filter_location_{{ $location->id }}" class="custom-control-label">{{ $location->display_label }}</label>
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
                        <label for="edit_shift">12/24 hr / One-time</label>
                        <select class="form-control" id="edit_shift" name="shift" required>
                            <option value="12">12 Hours</option>
                            <option value="24">24 Hours</option>
                            <option value="both">Both</option>
                            <option value="onetime">One-time</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="edit_total_experience">Total Experience</label>
                        <input type="text" class="form-control" id="edit_total_experience" name="total_experience">
                    </div>
                    <div class="form-group">
                        <label for="edit_city">Location</label>
                        <select class="form-control location-select" id="edit_city" name="city">
                            <option value="">Select Location</option>
                            @foreach($locations as $location)
                                <option value="{{ $location->name }}" data-state="{{ $location->state ?? '' }}" data-city-name="{{ $location->name }}">{{ $location->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="edit_job_title">Job Title (Service)</label>
                        <select class="form-control" id="edit_job_title" name="job_title" required disabled>
                            <option value="">Pehle location select karein</option>
                        </select>
                    </div>

                    @include('admin.jobproc.partials.freelancer-service-panel', ['fieldPrefix' => 'edit_'])

                    <div class="form-group">
                        <label for="edit_other_remark">Other Remark</label>
                        <textarea class="form-control" id="edit_other_remark" name="other_remark"></textarea>
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

<!-- Freelancer Price Change Requests Modal -->
<div class="modal fade" id="freelancerPriceChangeModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Freelancer price change requests</h5>
                <button type="button" class="close themed-close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" id="freelancerPriceChangeModalBody">
                <div class="text-center p-4 text-muted"><i class="fas fa-spinner fa-spin fa-2x"></i></div>
            </div>
        </div>
    </div>
</div>

@include('whatsapp.chat')
@endsection

@section('footer-script')
<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.4/moment.min.js"></script>
@include('admin.jobproc.partials.freelancer-service-script')
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
            url: '{{ route('subadmin.jobproc.jobrequests') }}',
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
                    if (!data) return '-';
                    if (typeof moment !== 'undefined') {
                        return moment(data).format('DD-MMMM HH:mm');
                    }
                    return data;
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
                    if (data === 'onetime') return 'One-time';
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
                    var priceBtn = '';
                    if ((row.pending_price_change_count || 0) > 0) {
                        priceBtn = `<button class="btn action-btn jp-price-req-btn" data-id="${data}" title="Price change request — review" onclick="openFreelancerPriceChangeRequests(${data})">
                            <i class="fas fa-rupee-sign"></i>
                            <span class="jp-price-count">${row.pending_price_change_count}</span>
                        </button>`;
                    }
                    return `
                        <div class="btn-group" role="group">
                            ${priceBtn}
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

    // Freelancer service pickers (add + edit)
    var jpAddServices = JobProcFreelancerServices.init('');
    var jpEditServices = JobProcFreelancerServices.init('edit_');

    function renderShowServicePricing(data) {
        var $sec = $('#show_service_pricing_section');
        var $list = $('#show_service_pricing_list').empty();
        var items = data.service_pricing || [];
        if (!items.length) {
            $sec.hide();
            return;
        }
        $sec.show();
        var shift = data.shift || '';
        items.forEach(function (it) {
            var tags = (it.tags || []).map(function (t) { return '<span class="badge badge-light border mr-1">' + t + '</span>'; }).join('');
            var prices = [];
            if (shift === '12' || shift === 'both') {
                prices.push('12hr: ₹' + (it.price_12hr != null && it.price_12hr !== '' ? it.price_12hr : '—'));
            }
            if (shift === '24' || shift === 'both') {
                prices.push('24hr: ₹' + (it.price_24hr != null && it.price_24hr !== '' ? it.price_24hr : '—'));
            }
            if (shift === 'onetime') {
                prices.push('One-time: ₹' + (it.price_onetime != null && it.price_onetime !== '' ? it.price_onetime : '—'));
            }
            var override = it.is_override ? ' <span class="badge badge-warning">Custom price</span>' : '';
            $list.append(
                '<div class="border rounded p-2 mb-2" style="background:#fafafa;">' +
                '<strong>' + it.label + '</strong>' + override +
                (tags ? '<div class="mt-1">' + tags + '</div>' : '') +
                '<div class="text-muted mt-1">' + prices.join(' · ') + '</div></div>'
            );
        });
    }

    // Freelancer price change requests (admin review)
    window.openFreelancerPriceChangeRequests = function (jobRequestId) {
        $('#freelancerPriceChangeModal').data('job-request-id', jobRequestId);
        $('#freelancerPriceChangeModalBody').html('<div class="text-center p-4 text-muted"><i class="fas fa-spinner fa-spin fa-2x"></i><p class="mt-2 mb-0">Loading…</p></div>');
        $('#freelancerPriceChangeModal').modal('show');
        $.get('{{ url('subadmin/jobproc') }}/' + jobRequestId + '/price-change-requests')
            .done(function (res) {
                renderFreelancerPriceChangeModal(res);
            })
            .fail(function () {
                $('#freelancerPriceChangeModalBody').html('<p class="text-danger mb-0">Could not load requests.</p>');
            });
    };

    function renderFreelancerPriceChangeModal(res) {
        var f = res.freelancer || {};
        var reqs = res.requests || [];
        var html = '<p class="mb-2"><strong>' + (f.name || 'Freelancer') + '</strong>';
        if (f.job_title) html += ' · ' + f.job_title;
        if (f.city) html += ' · ' + f.city;
        html += '</p>';
        if (!reqs.length) {
            html += '<p class="text-muted mb-0">No pending price change requests.</p>';
            $('#freelancerPriceChangeModalBody').html(html);
            return;
        }
        html += '<div class="table-responsive"><table class="table table-sm table-bordered mb-0"><thead><tr>' +
            '<th>Sub-service</th><th>Type</th><th>Current</th><th>Requested</th><th>When</th><th>Action</th></tr></thead><tbody>';
        reqs.forEach(function (r) {
            var cur = r.current_price != null ? '₹' + r.current_price : '—';
            var req = r.requested_price != null ? '₹' + r.requested_price : '—';
            html += '<tr data-req-id="' + r.id + '">' +
                '<td>' + (r.sub_service_name || r.service_name || 'Service') + '</td>' +
                '<td>' + (r.price_type_label || r.price_type) + '</td>' +
                '<td>' + cur + '</td>' +
                '<td><strong>' + req + '</strong></td>' +
                '<td class="text-nowrap small">' + (r.created_at || '') + '</td>' +
                '<td class="text-nowrap">' +
                '<button type="button" class="btn btn-success btn-sm jp-fl-price-approve" data-id="' + r.id + '">Approve</button> ' +
                '<button type="button" class="btn btn-outline-danger btn-sm jp-fl-price-reject" data-id="' + r.id + '">Reject</button>' +
                '</td></tr>';
        });
        html += '</tbody></table></div>';
        html += '<div id="jp_fl_reject_note_wrap" class="mt-3" style="display:none;">' +
            '<label class="font-weight-bold">Rejection note (required)</label>' +
            '<textarea class="form-control" id="jp_fl_reject_note" rows="2" placeholder="Freelancer ko batayein kyun reject kiya"></textarea>' +
            '<button type="button" class="btn btn-danger btn-sm mt-2" id="jp_fl_reject_confirm">Confirm reject</button>' +
            '<button type="button" class="btn btn-secondary btn-sm mt-2 ml-1" id="jp_fl_reject_cancel">Cancel</button>' +
            '</div>';
        $('#freelancerPriceChangeModalBody').html(html);
    }

    var jpFlRejectReqId = null;
    $(document).on('click', '.jp-fl-price-approve', function () {
        var id = $(this).data('id');
        var $btn = $(this);
        $btn.prop('disabled', true);
        $.post('{{ url('subadmin/jobproc/price-change-requests') }}/' + id + '/approve', {
            _token: '{{ csrf_token() }}'
        }).done(function (res) {
            toastr.success(res.message || 'Approved');
            var jobId = $('#freelancerPriceChangeModal').data('job-request-id');
            if (jobId) openFreelancerPriceChangeRequests(jobId);
            $('#job-request-table').DataTable().ajax.reload(null, false);
        }).fail(function (xhr) {
            toastr.error((xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Failed');
            $btn.prop('disabled', false);
        });
    });
    $(document).on('click', '.jp-fl-price-reject', function () {
        jpFlRejectReqId = $(this).data('id');
        $('#jp_fl_reject_note_wrap').show();
        $('#jp_fl_reject_note').focus();
    });
    $(document).on('click', '#jp_fl_reject_cancel', function () {
        jpFlRejectReqId = null;
        $('#jp_fl_reject_note_wrap').hide();
        $('#jp_fl_reject_note').val('');
    });
    $(document).on('click', '#jp_fl_reject_confirm', function () {
        if (!jpFlRejectReqId) return;
        var note = String($('#jp_fl_reject_note').val() || '').trim();
        if (!note) {
            toastr.error('Rejection note likhna zaroori hai.');
            return;
        }
        var $btn = $(this);
        $btn.prop('disabled', true);
        $.post('{{ url('subadmin/jobproc/price-change-requests') }}/' + jpFlRejectReqId + '/reject', {
            _token: '{{ csrf_token() }}',
            admin_note: note
        }).done(function (res) {
            toastr.success(res.message || 'Rejected');
            jpFlRejectReqId = null;
            $('#jp_fl_reject_note').val('');
            $('#jp_fl_reject_note_wrap').hide();
            var jobId = $('#freelancerPriceChangeModal').data('job-request-id');
            if (jobId) openFreelancerPriceChangeRequests(jobId);
            $('#job-request-table').DataTable().ajax.reload(null, false);
        }).fail(function (xhr) {
            toastr.error((xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Failed');
            $btn.prop('disabled', false);
        });
    });

    // Show Job Request
    $(document).on('click', '.show-job', function() {
        var id = $(this).data('id');
        $.get('{{ url('subadmin/jobproc') }}/' + id)
            .done(function(data) {
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
            } else if (data.shift === 'onetime') {
                shiftDisplay = 'One-time';
            } else if (data.shift === '12' || data.shift === '24') {
                shiftDisplay = data.shift + ' hr';
            }
            $('#show_shift').text(shiftDisplay);
            $('#show_total_experience').text(data.total_experience);
            $('#show_job_title').text(data.job_title);
            renderShowServicePricing(data);
            $('#show_other_remark').text(data.other_remark);
            $('#show_city').text(data.city);
            $('#show_remark').text(data.remark);
            $('#show_status').text(data.status);
            $('#show_lead_id').text(data.lead_id);
            
            // Close document viewer if open
            closeDocumentViewer();
            
            // Show/hide document buttons based on availability
            console.log('Document data received:', {
                aadhar_card: data.aadhar_card,
                pan_card: data.pan_card,
                qualification_certificate: data.qualification_certificate,
                bank_document: data.bank_document
            });
            
            if (data.aadhar_card) {
                $('#aadhar_card_section').show();
                $('#aadhar_card_section').data('url', data.aadhar_card);
                console.log('Aadhar card URL set:', data.aadhar_card);
            } else {
                $('#aadhar_card_section').hide();
            }
            
            if (data.pan_card) {
                $('#pan_card_section').show();
                $('#pan_card_section').data('url', data.pan_card);
                console.log('PAN card URL set:', data.pan_card);
            } else {
                $('#pan_card_section').hide();
            }
            
            if (data.qualification_certificate) {
                $('#qualification_certificate_section').show();
                $('#qualification_certificate_section').data('url', data.qualification_certificate);
                console.log('Qualification certificate URL set:', data.qualification_certificate);
            } else {
                $('#qualification_certificate_section').hide();
            }
            
            if (data.bank_document) {
                $('#bank_document_section').show();
                $('#bank_document_section').data('url', data.bank_document);
                console.log('Bank document URL set:', data.bank_document);
            } else {
                $('#bank_document_section').hide();
            }
            
            // Load and display recordings
            loadJobRequestRecordings(data);

            populateJobRequestProfileImageAdmin(data);
            
            $('#frlLeegalityPanelWrap').html(data.leegality_html || '');

            $('#showJobRequestModal').modal('show');
        })
            .fail(function(xhr) {
                console.error('Show job request failed', xhr);
                toastr.error('Job request details load nahi ho payi.');
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
                                    ${meta.call_direction ? `<span class="badge badge-info"><i class="fa fa-route"></i> ${meta.call_direction.charAt(0).toUpperCase() + meta.call_direction.slice(1)}</span>` : ''}
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
        $.get('{{ url('subadmin/jobproc') }}/' + id + '/edit')
            .done(function(data) {
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
            $('#edit_other_remark').val(data.other_remark);
            $('#edit_city').val(data.city || data.location);
            $('#edit_remark').val(data.remark);
            $('#edit_status').val(data.status);
            try {
                jpEditServices.restoreFromData({
                    job_title: data.job_title,
                    service_sub_services: data.service_sub_services || [],
                    service_price_overrides: data.service_price_overrides || []
                });
            } catch (e) {
                console.error('Service restore failed', e);
            }
            $('#editJobRequestModal').modal('show');
        })
            .fail(function(xhr) {
                console.error('Edit job request load failed', xhr);
                toastr.error('Job request edit load nahi ho paya.');
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

        var svcErr = jpEditServices.validateSelection();
        if (svcErr) {
            toastr.error(svcErr);
            return;
        }

        $('#edit_job_title').prop('disabled', false);
        var formData = $('#editJobRequestForm').serialize();

        console.log('Updating job request with ID:', id);
        console.log('Form data:', formData);

        $.ajax({
            url: '{{ url('subadmin/jobproc') }}/' + id,
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
                url: '{{ url('subadmin/jobproc') }}/' + id,
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

        var svcErr = jpAddServices.validateSelection();
        if (svcErr) {
            toastr.error(svcErr);
            return;
        }

        $('#job_title').prop('disabled', false);
        var formData = $('#addJobRequestForm').serialize();
        console.log('Form data being sent:', formData);
        
        $.ajax({
            url: '{{ route('subadmin.jobproc.store') }}',
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
            url: '{{ route('subadmin.jobproc.import") }}',
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

// Global functions for document viewer (must be outside $(document).ready() for onclick to work)
function viewDocument(documentType, documentName) {
    var url = $('#' + documentType + '_section').data('url');
    console.log('View Document:', documentType, 'URL:', url);
    
    if (!url) {
        alert('Document not found. Please check if the document was uploaded.');
        return;
    }
    
    // Construct full URL - handle both relative and absolute paths
    var fullUrl;
    if (url.startsWith('http://') || url.startsWith('https://')) {
        fullUrl = url;
    } else if (url.startsWith('/')) {
        fullUrl = url;
    } else {
        // Use /storage/ path format (same as other parts of the application)
        fullUrl = '/storage/' + url;
    }
    
    console.log('Document URL:', url);
    console.log('Full URL:', fullUrl);
    
    var fileExtension = url.split('.').pop().toLowerCase();
    var viewerContent = $('#document_viewer_content');
    var viewerSection = $('#document_viewer_section');
    var viewerTitle = $('#document_viewer_title');
    
    viewerTitle.text(documentName);
    viewerContent.html('<div class="text-center"><i class="fa fa-spinner fa-spin fa-3x"></i><p>Loading document...</p></div>');
    viewerSection.slideDown();
    
    // Scroll to document viewer
    setTimeout(function() {
        $('html, body').animate({
            scrollTop: viewerSection.offset().top - 100
        }, 500);
    }, 300);
    
    if (['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(fileExtension)) {
        // Display image
        var img = new Image();
        img.onload = function() {
            viewerContent.html('<img src="' + fullUrl + '" class="img-fluid" style="max-width: 100%; height: auto; border: 1px solid #ddd; border-radius: 4px;" alt="' + documentName + '">');
        };
        img.onerror = function() {
            viewerContent.html('<div class="alert alert-danger"><p>Error loading image. <a href="' + fullUrl + '" target="_blank" class="btn btn-primary btn-sm">Open in New Tab</a></p></div>');
        };
        img.src = fullUrl;
    } else if (fileExtension === 'pdf') {
        // Display PDF
        viewerContent.html('<iframe src="' + fullUrl + '" style="width: 100%; height: 600px; border: 1px solid #ddd; border-radius: 4px;" onerror="this.parentElement.innerHTML=\'<div class=\\\'alert alert-danger\\\'><p>Error loading PDF. <a href=\\\'' + fullUrl + '\\\' target=\\\'_blank\\\' class=\\\'btn btn-primary btn-sm\\\'>Open in New Tab</a></p></div>\'"></iframe>');
    } else {
        // For other file types, show download link
        viewerContent.html('<div class="alert alert-info"><p>This file type cannot be previewed. <a href="' + fullUrl + '" target="_blank" class="btn btn-primary">Download File</a></p></div>');
    }
}

// Function to close document viewer
function closeDocumentViewer() {
    $('#document_viewer_section').slideUp();
    $('#document_viewer_content').html('');
}

function populateJobRequestProfileImageAdmin(data) {
    var $section = $('#jp_profile_image_section');
    var $wrap = $('#jpProfileImageAdminWrap');
    var dressType = data && (data.uniform_dress_type || (data.is_attendant ? 'attendant' : null));
    if (!data || !dressType) {
        $section.hide();
        return;
    }
    var dressLabels = {
        attendant: { title: 'attendant uniform', service: 'Attendant' },
        nurse: { title: 'nurse uniform', service: 'Nurse' }
    };
    var label = dressLabels[dressType] || dressLabels.attendant;
    $('#jpProfileImageTitle').text('Profile photo — Carelix ' + label.title + ' (Gemini)');
    $('#jpProfileImageDesc').text('Only for ' + label.service + ' service. Generate a branded uniform portrait from the freelancer\'s upload, preview, then approve to update their profile.');
    $section.show();
    $wrap.attr('data-jp-id', data.id || '');
    $wrap.attr('data-generate-url', data.profile_image_generate_url || '');
    $wrap.attr('data-approve-url', data.profile_image_approve_url || '');

    var status = data.profile_image_status || 'pending_review';
    var badgeClass = status === 'approved' ? 'badge-success' : (status === 'rejected' ? 'badge-danger' : 'badge-warning');
    var badgeLabel = status === 'approved' ? 'Approved' : (status === 'rejected' ? 'Rejected' : 'Pending admin review');
    $('#jpProfileImageStatusBadge').removeClass('badge-success badge-danger badge-warning').addClass(badgeClass).text(badgeLabel);

    if (data.profile_image_upload_url) {
        $('#jpProfileUploadEmpty').addClass('d-none');
        $('#jpProfileUploadLink').removeClass('d-none').attr('href', data.profile_image_upload_url);
        $('#jpProfileUploadImg').attr('src', data.profile_image_upload_url);
        $('#jpProfileGenerateBtn').prop('disabled', false);
    } else {
        $('#jpProfileUploadEmpty').removeClass('d-none');
        $('#jpProfileUploadLink').addClass('d-none');
        $('#jpProfileGenerateBtn').prop('disabled', true);
    }

    if (data.profile_image_approved_url) {
        $('#jpProfileApprovedWrap').removeClass('d-none');
        $('#jpProfileApprovedImg').attr('src', data.profile_image_approved_url);
    } else {
        $('#jpProfileApprovedWrap').addClass('d-none');
    }

    if (data.profile_image_pending_url) {
        $('#jpProfilePendingWrap').removeClass('d-none');
        $('#jpProfilePendingImg').attr('src', data.profile_image_pending_url);
        $('#jpProfilePendingEmpty').addClass('d-none');
        $('#jpProfileApproveBtn').removeClass('d-none');
    } else {
        $('#jpProfilePendingWrap').addClass('d-none');
        $('#jpProfilePendingImg').attr('src', '');
        $('#jpProfilePendingEmpty').removeClass('d-none');
        $('#jpProfileApproveBtn').addClass('d-none');
    }

    $('#jpProfileImageAdminMsg').text('').removeClass('text-danger text-success');
}

(function () {
    if (window._jpProfileImageAdminBound) {
        return;
    }
    window._jpProfileImageAdminBound = true;

    function jpProfileCsrf() {
        return $('meta[name="csrf-token"]').attr('content') || '';
    }

    function jpProfileSetMsg($el, text, isError) {
        if (!$el.length) {
            return;
        }
        $el.text(text || '').toggleClass('text-danger', !!isError).toggleClass('text-success', !!text && !isError);
    }

    $(document).on('click', '#jpProfileGenerateBtn', function () {
        var $wrap = $(this).closest('.fl-profile-image-admin');
        var url = $wrap.data('generate-url');
        var $btn = $(this);
        var $msg = $('#jpProfileImageAdminMsg');
        if (!url) {
            return;
        }
        $btn.prop('disabled', true);
        jpProfileSetMsg($msg, 'Generating preview with Gemini… this may take up to a minute.', false);
        $.ajax({
            url: url,
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': jpProfileCsrf(),
                'X-Requested-With': 'XMLHttpRequest',
                Accept: 'application/json'
            },
            success: function (res) {
                if (res && res.success && res.preview_url) {
                    var bust = res.preview_url + (res.preview_url.indexOf('?') >= 0 ? '&' : '?') + 't=' + Date.now();
                    $('#jpProfilePendingImg').attr('src', bust);
                    $('#jpProfilePendingWrap').removeClass('d-none');
                    $('#jpProfilePendingEmpty').addClass('d-none');
                    $('#jpProfileApproveBtn').removeClass('d-none');
                    $('#jpProfileImageStatusBadge').removeClass('badge-success badge-danger').addClass('badge-warning').text('Pending admin review');
                    toastr.success(res.message || 'Preview ready');
                    jpProfileSetMsg($msg, 'Preview generated. Review the image, then click Approve.', false);
                } else {
                    toastr.error((res && res.message) ? res.message : 'Generation failed');
                    jpProfileSetMsg($msg, (res && res.message) ? res.message : 'Generation failed', true);
                }
            },
            error: function (xhr) {
                var m = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Request failed';
                toastr.error(m);
                jpProfileSetMsg($msg, m, true);
            },
            complete: function () {
                $btn.prop('disabled', false);
            }
        });
    });

    $(document).on('click', '#jpProfileApproveBtn', function () {
        var $wrap = $(this).closest('.fl-profile-image-admin');
        var url = $wrap.data('approve-url');
        var id = parseInt($wrap.data('jp-id'), 10) || 0;
        var $btn = $(this);
        var $msg = $('#jpProfileImageAdminMsg');
        if (!url || !confirm('Approve this profile photo for the freelancer?')) {
            return;
        }
        $btn.prop('disabled', true);
        jpProfileSetMsg($msg, 'Approving…', false);
        $.ajax({
            url: url,
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': jpProfileCsrf(),
                'X-Requested-With': 'XMLHttpRequest',
                Accept: 'application/json'
            },
            success: function (res) {
                if (res && res.success) {
                    toastr.success(res.message || 'Approved');
                    if (id) {
                        $.get('{{ url('subadmin/jobproc') }}/' + id, populateJobRequestProfileImageAdmin);
                    }
                } else {
                    toastr.error((res && res.message) ? res.message : 'Failed');
                    jpProfileSetMsg($msg, (res && res.message) ? res.message : 'Failed', true);
                    $btn.prop('disabled', false);
                }
            },
            error: function (xhr) {
                var m = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Request failed';
                toastr.error(m);
                jpProfileSetMsg($msg, m, true);
                $btn.prop('disabled', false);
            }
        });
    });

    $(document).on('click', '.frl-leegality-send-btn', function () {
        var $btn = $(this);
        var url = $btn.data('url');
        if (!url) return;
        if (!window.confirm('Send agreement for e-signature to the freelancer email via Leegality?')) return;

        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Sending…');
        $.ajax({
            url: url,
            type: 'POST',
            data: { _token: jpProfileCsrf() },
            success: function (res) {
                if (res.success) {
                    if (window.toastr) toastr.success(res.message || 'Sent');
                    if (res.html) $('#frlLeegalityPanelWrap').html(res.html);
                    if ($.fn.dataTable && $.fn.dataTable.isDataTable('#job-request-table')) {
                        $('#job-request-table').DataTable().ajax.reload(null, false);
                    }
                } else {
                    if (window.toastr) toastr.error(res.message || 'Failed');
                    $btn.prop('disabled', false).html('<i class="fas fa-paper-plane mr-1"></i> Send for Signature');
                }
            },
            error: function (xhr) {
                var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Failed to send for signature.';
                if (window.toastr) toastr.error(msg); else alert(msg);
                $btn.prop('disabled', false).html('<i class="fas fa-paper-plane mr-1"></i> Send for Signature');
            }
        });
    });

    $(document).on('click', '.frl-leegality-add-sig-btn', function () {
        var $btn = $(this);
        var url = $btn.data('url');
        if (!url) return;
        if (!window.confirm('Add your official signature to this document and email final signed PDF to the freelancer?')) return;

        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Signing & Emailing…');
        $.ajax({
            url: url,
            type: 'POST',
            data: { _token: jpProfileCsrf() },
            success: function (res) {
                if (res.success) {
                    if (window.toastr) toastr.success(res.message || 'Signature added & emailed successfully!');
                    if (res.html) $('#frlLeegalityPanelWrap').html(res.html);
                    if ($.fn.dataTable && $.fn.dataTable.isDataTable('#job-request-table')) {
                        $('#job-request-table').DataTable().ajax.reload(null, false);
                    }
                } else {
                    if (window.toastr) toastr.error(res.message || 'Failed to add signature');
                    $btn.prop('disabled', false).html('<i class="fas fa-file-signature mr-1"></i> Add My Signature');
                }
            },
            error: function (xhr) {
                var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Failed to add signature.';
                if (window.toastr) toastr.error(msg); else alert(msg);
                $btn.prop('disabled', false).html('<i class="fas fa-file-signature mr-1"></i> Add My Signature');
            }
        });
    });

    $(document).on('click', '.frl-leegality-refresh-btn', function () {
        var $btn = $(this);
        var url = $btn.data('url');
        if (!url) return;
        $btn.prop('disabled', true);
        $.ajax({
            url: url,
            type: 'POST',
            data: { _token: jpProfileCsrf() },
            success: function (res) {
                if (res.success) {
                    if (window.toastr) toastr.success(res.message || 'Refreshed');
                    if (res.html) $('#frlLeegalityPanelWrap').html(res.html);
                } else {
                    if (window.toastr) toastr.error(res.message || 'Failed');
                    $btn.prop('disabled', false);
                }
            },
            error: function (xhr) {
                var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Refresh failed.';
                if (window.toastr) toastr.error(msg); else alert(msg);
                $btn.prop('disabled', false);
            }
        });
    });
})();
</script>
@endsection
