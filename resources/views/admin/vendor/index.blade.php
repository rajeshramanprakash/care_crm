@extends('admin.layouts.app')

@section('header-css')
@include('admin.doctor_requests.partials.registration_detail_styles')
<style>
    /* Action Buttons Styling */
    .action-buttons {
        display: flex;
        gap: 8px;
        align-items: center;
        justify-content: center;
    }

    .action-btn {
        width: 32px;
        height: 32px;
        border: none;
        border-radius: 6px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.2s ease;
        position: relative;
        overflow: hidden;
    }

    .action-btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
    }

    .action-btn:active {
        transform: translateY(0);
    }

    /* View Button - Grey Eye Icon */
    .view-btn {
        background-color: #f8f9fa;
        color: #6c757d;
        border: 1px solid #dee2e6;
    }

    .view-btn:hover {
        background-color: #e9ecef;
        color: #495057;
        border-color: #adb5bd;
    }

    .view-btn i {
        font-size: 14px;
    }

    /* Edit Button - Grey Pencil Icon */
    .edit-btn {
        background-color: #f8f9fa;
        color: #6c757d;
        border: 1px solid #dee2e6;
    }

    .edit-btn:hover {
        background-color: #e9ecef;
        color: #495057;
        border-color: #adb5bd;
    }

    .edit-btn i {
        font-size: 14px;
    }

    /* Delete Button - Red Trash Icon */
    .delete-btn {
        background-color: #dc3545;
        color: #ffffff;
        border: 1px solid #dc3545;
    }

    .delete-btn:hover {
        background-color: #c82333;
        border-color: #bd2130;
        color: #ffffff;
    }

    .delete-btn i {
        font-size: 14px;
    }

    .vd-price-req-btn {
        background: #fff7ed;
        color: #c2410c;
        border: 1px solid #fdba74;
    }
    .vd-price-req-btn:hover {
        background: #ffedd5;
        color: #9a3412;
    }

    /* Table styling improvements */
    .table td {
        vertical-align: middle;
    }

    /* Responsive adjustments */
    @media (max-width: 768px) {
        .action-buttons {
            flex-direction: column;
            gap: 4px;
        }

        .action-btn {
            width: 28px;
            height: 28px;
        }

        .action-btn i {
            font-size: 12px;
        }
    }
</style>
@endsection

@section('content')
<div class="content-wrapper pb-5 pt-2">

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
                    <h3 class="card-title mb-0">Vendors</h3>
                    <div class="d-flex align-items-center gap-2">
                        @if(($pendingPriceChangeCount ?? 0) > 0)
                            <span class="badge badge-warning px-3 py-2">
                                <i class="fas fa-rupee-sign mr-1"></i>{{ $pendingPriceChangeCount }} pending price request(s)
                            </span>
                        @endif
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createVendorModal">
                            Add New Vendor
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive" style="max-height: 450px; overflow-y: auto;">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th style="min-width: 50px;">ID</th>
                                    <th style="min-width: 140px;">Lead ID</th>
                                    <th style="min-width: 130px;">Name</th>
                                    <th style="min-width: 130px;">Contact No</th>
                                    <th style="min-width: 150px;">Email</th>
                                    <th style="min-width: 280px;">Location &amp; Services</th>
                                    <th style="min-width: 100px;">Status</th>
                                    <th style="min-width: 120px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($vendors as $vendor)
                                <tr>
                                    <td class="font-weight-bold text-muted">{{ $vendor->id }}</td>
                                    <td class="text-nowrap"><span class="badge bg-light text-dark border px-2 py-1" style="font-family: ui-monospace, monospace; font-size: 0.82rem;">{{ $vendor->lead_id ?: ('CLX-VEN-' . str_pad((string)$vendor->id, 6, '0', STR_PAD_LEFT)) }}</span></td>
                                    <td class="font-weight-bold">{{ $vendor->name }}</td>
                                    <td class="text-nowrap" style="white-space: nowrap;">{{ $vendor->contact_no }}</td>
                                    <td class="text-muted">{{ $vendor->email ?: '—' }}</td>
                                    <td>
                                        @php
                                            $vendorBlocks = \App\Services\VendorServiceSync::blocksForVendor($vendor);
                                        @endphp
                                        @if($vendor->location)
                                            <div class="mb-1"><span class="badge bg-secondary">{{ $vendor->location }}</span></div>
                                        @endif
                                        @if(count($vendorBlocks) > 0)
                                            @foreach($vendorBlocks as $block)
                                                @php $service = $services->find($block['service_id'] ?? 0); @endphp
                                                <div class="mb-1">
                                                    <span class="fw-bold text-primary">{{ $service->name ?? ($block['service_name'] ?? 'Service') }}</span>
                                                    @foreach($block['sub_services'] ?? [] as $sub)
                                                        <div class="small text-muted ms-2">
                                                            — {{ $sub['sub_service_name'] ?? 'Sub-service' }}
                                                            @if(!empty($sub['tags']))
                                                                <span class="badge badge-light border">{{ implode(', ', $sub['tags']) }}</span>
                                                            @endif
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @endforeach
                                        @else
                                            <span class="text-muted">No services assigned</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($vendor->status == 'active')
                                            <span class="badge bg-success">Active</span>
                                        @else
                                            <span class="badge bg-warning">Duty Off</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            @if(($pendingByVendor[$vendor->id] ?? 0) > 0)
                                                <button class="action-btn vd-price-req-btn" data-id="{{ $vendor->id }}" title="Price change requests ({{ $pendingByVendor[$vendor->id] }})">
                                                    <i class="fas fa-rupee-sign"></i>
                                                </button>
                                            @endif
                                            <button class="action-btn view-btn view-vendor" data-id="{{ $vendor->id }}" title="View Details">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="action-btn edit-btn edit-vendor" data-id="{{ $vendor->id }}" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="action-btn delete-btn delete-vendor" data-id="{{ $vendor->id }}" title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
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
</div>

<!-- Create Vendor Modal -->
<div class="modal fade" id="createVendorModal" tabindex="-1" aria-labelledby="createVendorModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createVendorModalLabel">Add New Vendor</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">&times;</button>
            </div>
            <form id="createVendorForm">
                @csrf
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="name">Name *</label>
                                <input type="text" class="form-control" id="name" name="name" required>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="contact_no">Contact No</label>
                                <input type="text" class="form-control" id="contact_no" name="contact_no">
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="email">Email</label>
                                <input type="email" class="form-control" id="email" name="email">
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="status">Status</label>
                                <select class="form-control" id="status" name="status">
                                    <option value="active">Active</option>
                                    <option value="dutyoff">Duty Off</option>
                                </select>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Account Details Section -->
                    <h6 class="mt-4 mb-3">Account Details</h6>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="account_name">Account Name</label>
                                <input type="text" class="form-control" id="account_name" name="account_name">
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="account_number">Account Number</label>
                                <input type="text" class="form-control" id="account_number" name="account_number">
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="ifsc_code">IFSC Code</label>
                                <input type="text" class="form-control" id="ifsc_code" name="ifsc_code">
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="upi_id">UPI ID (Optional)</label>
                                <input type="text" class="form-control" id="upi_id" name="upi_id">
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="create_location">Location <span class="text-danger">*</span></label>
                                <select class="form-control" id="create_location" name="location" required>
                                    <option value="">Select Location</option>
                                    @foreach($locations as $location)
                                        <option value="{{ $location->name }}" data-city-name="{{ $location->name }}">{{ $location->name }}</option>
                                    @endforeach
                                </select>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                    </div>

                    @include('admin.vendor.partials.vendor-services-panel', ['formPrefix' => 'create'])
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save Vendor</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Vendor Modal -->
<div class="modal fade" id="editVendorModal" tabindex="-1" aria-labelledby="editVendorModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editVendorModalLabel">Edit Vendor</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editVendorForm">
                @csrf
                @method('PUT')
                <input type="hidden" id="edit_vendor_id" name="id">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="edit_name">Name *</label>
                                <input type="text" class="form-control" id="edit_name" name="name" required>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="edit_contact_no">Contact No</label>
                                <input type="text" class="form-control" id="edit_contact_no" name="contact_no">
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="edit_email">Email</label>
                                <input type="email" class="form-control" id="edit_email" name="email">
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="edit_status">Status</label>
                                <select class="form-control" id="edit_status" name="status">
                                    <option value="active">Active</option>
                                    <option value="dutyoff">Duty Off</option>
                                </select>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Account Details Section -->
                    <h6 class="mt-4 mb-3">Account Details</h6>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="edit_account_name">Account Name</label>
                                <input type="text" class="form-control" id="edit_account_name" name="account_name">
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="edit_account_number">Account Number</label>
                                <input type="text" class="form-control" id="edit_account_number" name="account_number">
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="edit_ifsc_code">IFSC Code</label>
                                <input type="text" class="form-control" id="edit_ifsc_code" name="ifsc_code">
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="edit_upi_id">UPI ID (Optional)</label>
                                <input type="text" class="form-control" id="edit_upi_id" name="upi_id">
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="edit_location">Location <span class="text-danger">*</span></label>
                                <select class="form-control" id="edit_location" name="location" required>
                                    <option value="">Select Location</option>
                                    @foreach($locations as $location)
                                        <option value="{{ $location->name }}" data-city-name="{{ $location->name }}">{{ $location->name }}</option>
                                    @endforeach
                                </select>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                    </div>

                    @include('admin.vendor.partials.vendor-services-panel', ['formPrefix' => 'edit'])
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Update Vendor</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- View Vendor Modal -->
<div class="modal fade" id="viewVendorModal" tabindex="-1" aria-labelledby="viewVendorModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewVendorModalLabel">Vendor Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Basic Information -->
                <h6 class="mb-3">Basic Information</h6>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label class="fw-bold">Name:</label>
                            <p id="view_name" class="mb-0"></p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label class="fw-bold">Customer Name:</label>
                            <p id="view_customer_name" class="mb-0"></p>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label class="fw-bold">Contact No:</label>
                            <p id="view_contact_no" class="mb-0"></p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label class="fw-bold">Email:</label>
                            <p id="view_email" class="mb-0"></p>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label class="fw-bold">Age:</label>
                            <p id="view_age" class="mb-0"></p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label class="fw-bold">Gender:</label>
                            <p id="view_gender" class="mb-0"></p>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label class="fw-bold">Status:</label>
                            <p id="view_status" class="mb-0"></p>
                        </div>
                    </div>
                </div>

                <!-- Professional Details -->
                <h6 class="mt-4 mb-3">Professional Details</h6>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label class="fw-bold">Job Title:</label>
                            <p id="view_job_title" class="mb-0"></p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label class="fw-bold">Location:</label>
                            <p id="view_location" class="mb-0"></p>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label class="fw-bold">Shift (12/24 hr):</label>
                            <p id="view_shift" class="mb-0"></p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label class="fw-bold">Total Experience:</label>
                            <p id="view_total_experience" class="mb-0"></p>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label class="fw-bold">Expected Salary:</label>
                            <p id="view_expected_salary" class="mb-0"></p>
                        </div>
                    </div>
                </div>

                <!-- Account Details Section -->
                <h6 class="mt-4 mb-3">Account Details</h6>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label class="fw-bold">Account Name:</label>
                            <p id="view_account_name" class="mb-0"></p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label class="fw-bold">Account Number:</label>
                            <p id="view_account_number" class="mb-0"></p>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label class="fw-bold">IFSC Code:</label>
                            <p id="view_ifsc_code" class="mb-0"></p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label class="fw-bold">UPI ID:</label>
                            <p id="view_upi_id" class="mb-0"></p>
                        </div>
                    </div>
                </div>

                <!-- Services Section -->
                <h6 class="mt-4 mb-3">Location, Services, Tags &amp; Pricing</h6>
                <div class="form-group mb-3">
                    <div id="view_vendor_services"></div>
                </div>

                <!-- Documents & Vendor Agreement (Leegality) Section -->
                <div class="row mt-4 mb-3">
                    <div class="col-12" id="venLeegalityPanelWrap">
                    </div>
                </div>

                <!-- Documents Section -->
                <h6 class="mt-4 mb-3">Documents</h6>
                <div class="form-group mb-3">
                    <table class="table table-bordered">
                        <tr>
                            <th style="width: 200px;">Aadhar Card</th>
                            <td id="view_aadhar_card">
                                <span class="text-muted">Loading...</span>
                            </td>
                        </tr>
                        <tr>
                            <th>PAN Card</th>
                            <td id="view_pan_card">
                                <span class="text-muted">Loading...</span>
                            </td>
                        </tr>
                        <tr>
                            <th>Qualification Certificate</th>
                            <td id="view_qualification_certificate">
                                <span class="text-muted">Loading...</span>
                            </td>
                        </tr>
                        <tr>
                            <th>Bank Document / Cancelled Cheque</th>
                            <td id="view_bank_document">
                                <span class="text-muted">Loading...</span>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
</div>

</div>

@include('admin.vendor.partials.vendor-services-script')

{{-- Vendor price change requests modal --}}
<div class="modal fade" id="vdPriceChangeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="background:#fff7ed;">
                <h5 class="modal-title"><i class="fas fa-rupee-sign text-warning mr-1"></i> Vendor price change requests</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="vdPriceChangeVendorMeta" class="mb-3 small text-muted"></div>
                <div id="vdPriceChangeList"></div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="documentViewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="documentViewModalLabel">View Document</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center" id="documentViewContent"></div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const createModal = new bootstrap.Modal(document.getElementById('createVendorModal'));
        const editModal = new bootstrap.Modal(document.getElementById('editVendorModal'));
        const viewModal = new bootstrap.Modal(document.getElementById('viewVendorModal'));

        AdminVendorServices.initForm('create', 'create_location');
        AdminVendorServices.initForm('edit', 'edit_location');

        $.ajaxSetup({
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
        });

        function showValidationErrors(form, errors) {
            form.find('.is-invalid').removeClass('is-invalid');
            Object.keys(errors).forEach(field => {
                const input = form.find(`[name="${field}"]`);
                if (input.length) {
                    input.addClass('is-invalid');
                    input.siblings('.invalid-feedback').text(errors[field][0]);
                } else {
                    toastr.error(errors[field][0]);
                }
            });
        }

        function resetForm(form) {
            form[0].reset();
            form.find('.is-invalid').removeClass('is-invalid');
            form.find('.invalid-feedback').text('');
            AdminVendorServices.clearBlocks(form.attr('id') === 'createVendorForm' ? 'create' : 'edit');
        }

        function renderVendorServicesView(response) {
            let html = '';
            if (response.location) {
                html += `<div class="mb-2"><strong>Location:</strong> <span class="badge bg-secondary">${response.location}</span></div>`;
            }
            const blocks = response.vendor_services || [];
            if (!blocks.length) {
                html += '<div class="text-muted">No services assigned</div>';
                return html;
            }
            blocks.forEach(function (block) {
                html += `<div class="border rounded p-2 mb-2"><div class="fw-bold text-primary">${block.service_name || 'Service'}</div>`;
                (block.sub_services || []).forEach(function (sub) {
                    html += `<div class="small ms-2 mb-1">`;
                    html += sub.sub_service_id ? `Sub: ${sub.sub_service_name || sub.sub_service_id}` : 'Service-level';
                    if (sub.tags && sub.tags.length) {
                        html += ` <span class="badge badge-light border">${sub.tags.join(', ')}</span>`;
                    }
                    html += `</div>`;
                });
                if (block.price_overrides && block.price_overrides.length) {
                    html += '<table class="table table-sm table-bordered mt-2 mb-0"><thead><tr><th>Type</th><th>12hr</th><th>24hr</th><th>One-time</th></tr></thead><tbody>';
                    block.price_overrides.forEach(function (p) {
                        const label = parseInt(p.service_sub_service_id || 0, 10) === 0 ? 'Service' : 'Sub #' + p.service_sub_service_id;
                        html += `<tr><td>${label}</td><td>${p.price_12hr ?? '—'}</td><td>${p.price_24hr ?? '—'}</td><td>${p.price_onetime ?? '—'}</td></tr>`;
                    });
                    html += '</tbody></table>';
                }
                html += '</div>';
            });
            return html;
        }

        $('#createVendorForm').on('submit', function(e) {
            e.preventDefault();
            AdminVendorServices.serialize('create');
            const form = $(this);
            $.ajax({
                url: "{{ route('admin.vendors.store') }}",
                method: 'POST',
                data: form.serialize(),
                success: function(response) {
                    if (response.status === 'success') {
                        createModal.hide();
                        resetForm(form);
                        location.reload();
                    }
                },
                error: function(xhr) {
                    if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                        showValidationErrors(form, xhr.responseJSON.errors);
                    } else {
                        toastr.error((xhr.responseJSON && xhr.responseJSON.message) || 'Error occurred while creating vendor');
                    }
                }
            });
        });

        $('.edit-vendor').on('click', function() {
            const id = $(this).data('id');
            const form = $('#editVendorForm');
            $.ajax({
                url: "{{ route('admin.vendors.edit') }}/" + id,
                method: 'GET',
                success: function(response) {
                    $('#edit_vendor_id').val(response.id);
                    $('#edit_name').val(response.name);
                    $('#edit_contact_no').val(response.contact_no);
                    $('#edit_email').val(response.email);
                    $('#edit_account_name').val(response.account_name);
                    $('#edit_account_number').val(response.account_number);
                    $('#edit_ifsc_code').val(response.ifsc_code);
                    $('#edit_upi_id').val(response.upi_id);
                    $('#edit_status').val(response.status || 'active');
                    $('#edit_location').val(response.location || '');
                    AdminVendorServices.loadBlocks('edit', response.location || '', response.vendor_services || []);
                    editModal.show();
                },
                error: function() {
                    toastr.error('Error occurred while fetching vendor details');
                }
            });
        });

        $('#editVendorForm').on('submit', function(e) {
            e.preventDefault();
            AdminVendorServices.serialize('edit');
            const form = $(this);
            const id = $('#edit_vendor_id').val();
            $.ajax({
                url: "{{ route('admin.vendors.update') }}/" + id,
                method: 'PUT',
                data: form.serialize(),
                success: function(response) {
                    if (response.status === 'success') {
                        editModal.hide();
                        location.reload();
                    }
                },
                error: function(xhr) {
                    if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                        showValidationErrors(form, xhr.responseJSON.errors);
                    } else {
                        toastr.error((xhr.responseJSON && xhr.responseJSON.message) || 'Error occurred while updating vendor');
                    }
                }
            });
        });

        $('.view-vendor').on('click', function() {
            const id = $(this).data('id');
            $.ajax({
                url: "{{ route('admin.vendors.edit') }}/" + id,
                method: 'GET',
                success: function(response) {
                    $('#view_name').text(response.name || 'N/A');
                    $('#view_customer_name').text(response.customer_name || 'N/A');
                    $('#view_contact_no').text(response.contact_no || 'N/A');
                    $('#view_email').text(response.email || 'N/A');
                    $('#view_age').text(response.age || 'N/A');
                    if (response.gender) {
                        $('#view_gender').text(response.gender.charAt(0).toUpperCase() + response.gender.slice(1));
                    } else {
                        $('#view_gender').text('N/A');
                    }
                    if (response.status === 'active') {
                        $('#view_status').html('<span class="badge bg-success">Active</span>');
                    } else if (response.status === 'dutyoff') {
                        $('#view_status').html('<span class="badge bg-warning">Duty Off</span>');
                    } else {
                        $('#view_status').html('<span class="badge bg-secondary">' + (response.status || 'N/A') + '</span>');
                    }
                    $('#view_job_title').text(response.job_title || 'N/A');
                    $('#view_location').text(response.location || 'N/A');
                    $('#view_shift').text(response.shift || 'N/A');
                    $('#view_total_experience').text(response.total_experience || 'N/A');
                    $('#view_expected_salary').text(response.expected_salary ? '₹' + parseFloat(response.expected_salary).toLocaleString('en-IN') : 'N/A');
                    $('#view_account_name').text(response.account_name || 'N/A');
                    $('#view_account_number').text(response.account_number || 'N/A');
                    $('#view_ifsc_code').text(response.ifsc_code || 'N/A');
                    $('#view_upi_id').text(response.upi_id || 'N/A');
                    $('#view_vendor_services').html(renderVendorServicesView(response));

                    const baseUrl = '{{ asset("storage") }}';
                    function docCell(field, label) {
                        if (response[field]) {
                            const url = baseUrl + '/' + response[field];
                            return `<button type="button" class="btn btn-sm btn-primary" onclick="viewDocument('${url}', '${label}')"><i class="fas fa-eye"></i> View</button>
                                <a href="${url}" download class="btn btn-sm btn-success"><i class="fas fa-download"></i> Download</a>`;
                        }
                        return '<span class="text-muted">Not uploaded</span>';
                    }
                    $('#view_aadhar_card').html(docCell('aadhar_card', 'Aadhar Card'));
                    $('#view_pan_card').html(docCell('pan_card', 'PAN Card'));
                    $('#view_qualification_certificate').html(docCell('qualification_certificate', 'Qualification Certificate'));
                    $('#view_bank_document').html(docCell('bank_document', 'Bank Document'));
                    $('#venLeegalityPanelWrap').html(response.leegality_html || '');
                    viewModal.show();
                },
                error: function() {
                    toastr.error('Error occurred while fetching vendor details');
                }
            });
        });

        $('.delete-vendor').on('click', function() {
            if (!confirm('Are you sure you want to delete this vendor?')) return;
            const id = $(this).data('id');
            $.ajax({
                url: "{{ route('admin.vendors.destroy') }}/" + id,
                method: 'DELETE',
                success: function(response) {
                    if (response.status === 'success') location.reload();
                },
                error: function() {
                    toastr.error('Error occurred while deleting vendor');
                }
            });
        });

        $('#createVendorModal').on('hidden.bs.modal', function() {
            resetForm($('#createVendorForm'));
        });

        var vdPriceModal = new bootstrap.Modal(document.getElementById('vdPriceChangeModal'));
        var vdRejectReqId = null;

        $(document).on('click', '.vd-price-req-btn', function() {
            var vendorId = $(this).data('id');
            $('#vdPriceChangeList').html('<p class="text-muted">Loading…</p>');
            $.get('{{ url('admin/vendors') }}/' + vendorId + '/price-change-requests', function(res) {
                if (!res.success) return;
                $('#vdPriceChangeVendorMeta').html('<strong>' + (res.vendor.name || 'Vendor') + '</strong>' + (res.vendor.location ? ' · ' + res.vendor.location : ''));
                if (!res.requests || !res.requests.length) {
                    $('#vdPriceChangeList').html('<p class="text-muted mb-0">No pending requests.</p>');
                } else {
                    var html = '';
                    res.requests.forEach(function(req) {
                        html += '<div class="border rounded p-3 mb-2" data-req-id="' + req.id + '">';
                        html += '<div class="d-flex justify-content-between flex-wrap gap-2"><strong>' + (req.service_name || 'Service') + '</strong><span class="badge badge-warning">Pending</span></div>';
                        html += '<div class="small text-muted">' + (req.sub_service_name || 'Service-level') + ' · ' + req.price_type_label + '</div>';
                        html += '<div class="mt-1">' + (req.current_price != null ? '₹' + Number(req.current_price).toLocaleString('en-IN') : '—') + ' → <strong>₹' + Number(req.requested_price).toLocaleString('en-IN') + '</strong></div>';
                        html += '<div class="mt-2"><button type="button" class="btn btn-sm btn-success vd-approve-req" data-id="' + req.id + '">Approve</button> ';
                        html += '<button type="button" class="btn btn-sm btn-outline-danger vd-reject-req" data-id="' + req.id + '">Reject</button></div></div>';
                    });
                    $('#vdPriceChangeList').html(html);
                }
                vdPriceModal.show();
            });
        });

        $(document).on('click', '.vd-approve-req', function() {
            var id = $(this).data('id');
            var $btn = $(this);
            $btn.prop('disabled', true);
            $.post('{{ url('admin/vendors/price-change-requests') }}/' + id + '/approve', { _token: '{{ csrf_token() }}' })
                .done(function(res) {
                    toastr.success(res.message || 'Approved');
                    vdPriceModal.hide();
                    location.reload();
                })
                .fail(function(xhr) {
                    toastr.error((xhr.responseJSON && xhr.responseJSON.message) || 'Failed');
                    $btn.prop('disabled', false);
                });
        });

        $(document).on('click', '.vd-reject-req', function() {
            vdRejectReqId = $(this).data('id');
            var note = prompt('Rejection reason (required):');
            if (note === null) return;
            if (!String(note).trim()) { toastr.error('Rejection note required.'); return; }
            $.post('{{ url('admin/vendors/price-change-requests') }}/' + vdRejectReqId + '/reject', {
                _token: '{{ csrf_token() }}',
                admin_note: note
            }).done(function(res) {
                toastr.success(res.message || 'Rejected');
                vdPriceModal.hide();
                location.reload();
            }).fail(function(xhr) {
                toastr.error((xhr.responseJSON && xhr.responseJSON.message) || 'Failed');
            });
        });

        $(document).on('click', '.ven-leegality-send-btn', function () {
            var $btn = $(this);
            var url = $btn.data('url');
            if (!url) return;
            if (!window.confirm('Send agreement for e-signature to the vendor email via Leegality?')) return;

            $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Sending…');
            $.ajax({
                url: url,
                type: 'POST',
                data: { _token: $('meta[name="csrf-token"]').attr('content') },
                success: function (res) {
                    if (res.success) {
                        if (window.toastr) toastr.success(res.message || 'Sent');
                        if (res.html) $('#venLeegalityPanelWrap').html(res.html);
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

        $(document).on('click', '.ven-leegality-add-sig-btn', function () {
            var $btn = $(this);
            var url = $btn.data('url');
            if (!url) return;
            if (!window.confirm('Add your official signature to this document and email final signed PDF to the vendor?')) return;

            $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Signing & Emailing…');
            $.ajax({
                url: url,
                type: 'POST',
                data: { _token: $('meta[name="csrf-token"]').attr('content') },
                success: function (res) {
                    if (res.success) {
                        if (window.toastr) toastr.success(res.message || 'Signature added & emailed successfully!');
                        if (res.html) $('#venLeegalityPanelWrap').html(res.html);
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

        $(document).on('click', '.ven-leegality-refresh-btn', function () {
            var $btn = $(this);
            var url = $btn.data('url');
            if (!url) return;
            $btn.prop('disabled', true);
            $.ajax({
                url: url,
                type: 'POST',
                data: { _token: $('meta[name="csrf-token"]').attr('content') },
                success: function (res) {
                    if (res.success) {
                        if (window.toastr) toastr.success(res.message || 'Refreshed');
                        if (res.html) $('#venLeegalityPanelWrap').html(res.html);
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
    });

    function viewDocument(url, title) {
        const modal = new bootstrap.Modal(document.getElementById('documentViewModal'));
        const modalTitle = document.getElementById('documentViewModalLabel');
        const modalContent = document.getElementById('documentViewContent');
        modalTitle.textContent = title;
        const extension = url.split('.').pop().toLowerCase();
        if (['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(extension)) {
            modalContent.innerHTML = `<img src="${url}" class="img-fluid" alt="${title}" style="max-height: 70vh;">`;
        } else if (extension === 'pdf') {
            modalContent.innerHTML = `<iframe src="${url}" style="width: 100%; height: 70vh; border: none;"></iframe>`;
        } else {
            modalContent.innerHTML = `<div class="alert alert-info"><p>Preview not available.</p><a href="${url}" download class="btn btn-primary">Download</a></div>`;
        }
        modal.show();
    }
</script>
@endsection
