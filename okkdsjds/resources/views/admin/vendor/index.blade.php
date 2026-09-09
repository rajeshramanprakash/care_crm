@extends('admin.layouts.app')

@section('header-css')
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
                <div class="card-header">
                    <h3 class="card-title">Vendors</h3>
                    <div class="card-tools">
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
                                    <th style="min-width: 120px;">Name</th>
                                    <th style="min-width: 120px;">Contact No</th>
                                    <th style="min-width: 150px;">Email</th>
                                    <th style="min-width: 300px;">Services, Cities & Working Hours</th>
                                    <th style="min-width: 100px;">Status</th>
                                    <th style="min-width: 120px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($vendors as $vendor)
                                <tr>
                                    <td>{{ $vendor->id }}</td>
                                    <td>{{ $vendor->name }}</td>
                                    <td>{{ $vendor->contact_no }}</td>
                                    <td>{{ $vendor->email }}</td>
                                    <td>
                                        @if($vendor->service_city_shifts)
                                            @foreach($vendor->service_city_shifts as $serviceData)
                                                @php $service = $services->find($serviceData['service_id']); @endphp
                                                @if($service)
                                                    <div class="mb-2">
                                                        <div class="fw-bold text-primary">{{ $service->name }}</div>
                                                        @if(isset($serviceData['cities']))
                                                            @foreach($serviceData['cities'] as $cityShift)
                                                                @php $location = $locations->find($cityShift['city_id']); @endphp
                                                                @if($location)
                                                                    <div class="ms-3 mb-1">
                                                                        <span class="badge bg-success">{{ $location->name }}</span>
                                                                        <span class="badge bg-info">{{ $cityShift['shift'] == 'both' ? 'Both' : $cityShift['shift'] . ' Hours' }}</span>
                                                                    </div>
                                                                @endif
                                                            @endforeach
                                                        @endif
                                                    </div>
                                                @endif
                                            @endforeach
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
    <div class="modal-dialog modal-lg">
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

                    <!-- Services, Cities & Working Hours Section -->
                    <h6 class="mt-4 mb-3">Services, Cities & Working Hours</h6>
                    <div id="serviceCityShiftsContainer">
                        <!-- Dynamic service-city-shifts will be added here -->
                    </div>
                    <div class="form-group mb-3">
                        <button type="button" class="btn btn-sm btn-outline-primary" id="addServiceSection">
                            <i class="fas fa-plus"></i> Add New Service
                        </button>
                    </div>
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
    <div class="modal-dialog modal-lg">
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

                    <!-- Services, Cities & Working Hours Section -->
                    <h6 class="mt-4 mb-3">Services, Cities & Working Hours</h6>
                    <div id="editServiceCityShiftsContainer">
                        <!-- Dynamic service-city-shifts will be added here -->
                    </div>
                    <div class="form-group mb-3">
                        <button type="button" class="btn btn-sm btn-outline-primary" id="editAddServiceSection">
                            <i class="fas fa-plus"></i> Add New Service
                        </button>
                    </div>
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
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label class="fw-bold">Name:</label>
                            <p id="view_name" class="mb-0"></p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label class="fw-bold">Contact No:</label>
                            <p id="view_contact_no" class="mb-0"></p>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label class="fw-bold">Email:</label>
                            <p id="view_email" class="mb-0"></p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label class="fw-bold">Status:</label>
                            <p id="view_status" class="mb-0"></p>
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

                <!-- Services, Cities & Working Hours Section -->
                <h6 class="mt-4 mb-3">Services, Cities & Working Hours</h6>
                <div class="form-group mb-3">
                    <div id="view_service_city_shifts"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize Bootstrap modals
        const createModal = new bootstrap.Modal(document.getElementById('createVendorModal'));
        const editModal = new bootstrap.Modal(document.getElementById('editVendorModal'));
        const viewModal = new bootstrap.Modal(document.getElementById('viewVendorModal'));

        // Set up CSRF token for all AJAX requests
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        // Function to show validation errors
        function showValidationErrors(form, errors) {
            form.find('.is-invalid').removeClass('is-invalid');
            Object.keys(errors).forEach(field => {
                const input = form.find(`[name="${field}"]`);
                input.addClass('is-invalid');
                input.siblings('.invalid-feedback').text(errors[field][0]);
            });
        }

        // Function to reset form
        function resetForm(form) {
            form[0].reset();
            form.find('.is-invalid').removeClass('is-invalid');
            form.find('.invalid-feedback').text('');
            $('#serviceCityShiftsContainer, #editServiceCityShiftsContainer').empty();
            serviceCounter = 0;
            editServiceCounter = 0;
        }

                // Store data for JavaScript use
        const locations = @json($locations);
        const services = @json($services);
        let serviceCounter = 0;
        let editServiceCounter = 0;

        // Function to create service section
        function createServiceSection(containerId, prefix = '') {
            const counter = prefix === 'edit' ? ++editServiceCounter : ++serviceCounter;
            const sectionId = prefix + 'service_section_' + counter;

            const section = `
                <div class="card mb-3 service-section" id="${sectionId}">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="mb-0">Service ${counter}</h6>
                        <button type="button" class="btn btn-sm btn-danger remove-service-section" data-section="${sectionId}">
                            <i class="fas fa-trash"></i> Remove Service
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label>Select Service</label>
                                    <select class="form-control service-select" name="${prefix}service_city_shifts[${counter}][service_id]" required>
                                        <option value="">Select Service</option>
                                        ${services.map(service => `<option value="${service.id}">${service.name}</option>`).join('')}
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="cities-container">
                            <h6 class="mt-3 mb-2">Cities & Working Hours</h6>
                            <div class="cities-list">
                                <!-- Cities will be added here -->
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-secondary add-city-btn" data-section="${sectionId}">
                                <i class="fas fa-plus"></i> Add City
                            </button>
                        </div>
                    </div>
                </div>
            `;

            $(containerId).append(section);
        }

        // Function to create city row within a service
        function createCityRow(serviceSectionId, prefix = '') {
            const serviceSection = $('#' + serviceSectionId);
            const serviceCounter = serviceSection.find('.service-select').attr('name').match(/\[(\d+)\]/)[1];
            const cityCounter = serviceSection.find('.city-row').length;
            const cityId = prefix + 'city_' + serviceCounter + '_' + cityCounter;

            const cityRow = `
                <div class="row mb-2 city-row" id="${cityId}">
                    <div class="col-md-5">
                        <div class="form-group">
                            <label>City</label>
                            <select class="form-control city-select" name="${prefix}service_city_shifts[${serviceCounter}][cities][${cityCounter}][city_id]" required>
                                <option value="">Select City</option>
                                ${locations.map(location => `<option value="${location.id}">${location.name}</option>`).join('')}
                            </select>
                        </div>
                    </div>
                    <div class="col-md-5">
                        <div class="form-group">
                            <label>Working Hours</label>
                            <select class="form-control shift-select" name="${prefix}service_city_shifts[${serviceCounter}][cities][${cityCounter}][shift]" required>
                                <option value="">Select Hours</option>
                                <option value="12">12 Hours</option>
                                <option value="24">24 Hours</option>
                                <option value="both">Both</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>&nbsp;</label>
                            <button type="button" class="btn btn-sm btn-danger remove-city" data-city="${cityId}">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            `;

            serviceSection.find('.cities-list').append(cityRow);
        }

        // Add service section for create form
        $('#addServiceSection').on('click', function() {
            createServiceSection('#serviceCityShiftsContainer');
        });

        // Add service section for edit form
        $('#editAddServiceSection').on('click', function() {
            createServiceSection('#editServiceCityShiftsContainer', 'edit');
        });

        // Remove service section
        $(document).on('click', '.remove-service-section', function() {
            $(this).closest('.service-section').remove();
        });

        // Add city to service
        $(document).on('click', '.add-city-btn', function() {
            const sectionId = $(this).data('section');
            const prefix = sectionId.startsWith('edit') ? 'edit' : '';
            createCityRow(sectionId, prefix);
        });

        // Remove city
        $(document).on('click', '.remove-city', function() {
            $(this).closest('.city-row').remove();
        });

        // Create Vendor
        $('#createVendorForm').on('submit', function(e) {
            e.preventDefault();
            const form = $(this);

            $.ajax({
                url: "{{ route('admin.vendors.store') }}",
                method: 'POST',
                data: form.serialize(),
                success: function(response) {
                    if(response.status === 'success') {
                        createModal.hide();
                        resetForm(form);
                        location.reload();
                    }
                },
                error: function(xhr) {
                    if(xhr.status === 422) {
                        showValidationErrors(form, xhr.responseJSON.errors);
                    } else {
                        alert('Error occurred while creating vendor');
                    }
                }
            });
        });

        // Edit Vendor
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
                    $('#edit_status').val(response.status);

                    // Clear and populate service-city-shifts
                    $('#editServiceCityShiftsContainer').empty();
                    if (response.service_city_shifts && response.service_city_shifts.length > 0) {
                        response.service_city_shifts.forEach(function(serviceData, serviceIndex) {
                            createServiceSection('#editServiceCityShiftsContainer', 'edit');
                            const serviceSection = $('#editServiceCityShiftsContainer .service-section').last();
                            serviceSection.find('.service-select').val(serviceData.service_id);

                            // Add cities for this service
                            if (serviceData.cities && serviceData.cities.length > 0) {
                                serviceData.cities.forEach(function(cityShift, cityIndex) {
                                    const sectionId = serviceSection.attr('id');
                                    createCityRow(sectionId, 'edit');
                                    const cityRow = serviceSection.find('.city-row').last();
                                    cityRow.find('.city-select').val(cityShift.city_id);
                                    cityRow.find('.shift-select').val(cityShift.shift);
                                });
                            }
                        });
                    }

                    editModal.show();
                },
                error: function() {
                    alert('Error occurred while fetching vendor details');
                }
            });
        });

        // View Vendor
        $('.view-vendor').on('click', function() {
            const id = $(this).data('id');

            $.ajax({
                url: "{{ route('admin.vendors.edit') }}/" + id,
                method: 'GET',
                success: function(response) {
                    // Set basic information
                    $('#view_name').text(response.name || 'N/A');
                    $('#view_contact_no').text(response.contact_no || 'N/A');
                    $('#view_email').text(response.email || 'N/A');
                    $('#view_account_name').text(response.account_name || 'N/A');
                    $('#view_account_number').text(response.account_number || 'N/A');
                    $('#view_ifsc_code').text(response.ifsc_code || 'N/A');
                    $('#view_upi_id').text(response.upi_id || 'N/A');

                    // Set status with badge
                    if (response.status == 'active') {
                        $('#view_status').html('<span class="badge bg-success">Active</span>');
                    } else if (response.status == 'dutyoff') {
                        $('#view_status').html('<span class="badge bg-warning">Duty Off</span>');
                    } else {
                        $('#view_status').text('N/A');
                    }

                    // Set services, cities and shifts
                    let serviceCityShiftsHtml = '';
                    if (response.service_city_shifts && response.service_city_shifts.length > 0) {
                        response.service_city_shifts.forEach(function(serviceData) {
                            const service = @json($services).find(s => s.id == serviceData.service_id);
                            if (service) {
                                serviceCityShiftsHtml += `
                                    <div class="mb-3">
                                        <div class="fw-bold text-primary mb-2">${service.name}</div>
                                `;

                                if (serviceData.cities && serviceData.cities.length > 0) {
                                    serviceData.cities.forEach(function(cityShift) {
                                        const location = @json($locations).find(l => l.id == cityShift.city_id);
                                        if (location) {
                                            const shiftText = cityShift.shift === 'both' ? 'Both' : cityShift.shift + ' Hours';
                                            serviceCityShiftsHtml += `
                                                <div class="ms-3 mb-1">
                                                    <span class="badge bg-success me-1">${location.name}</span>
                                                    <span class="badge bg-info">${shiftText}</span>
                                                </div>
                                            `;
                                        }
                                    });
                                }

                                serviceCityShiftsHtml += '</div>';
                            }
                        });
                    }
                    $('#view_service_city_shifts').html(serviceCityShiftsHtml || 'N/A');

                    viewModal.show();
                },
                error: function() {
                    alert('Error occurred while fetching vendor details');
                }
            });
        });

        // Update Vendor
        $('#editVendorForm').on('submit', function(e) {
            e.preventDefault();
            const form = $(this);
            const id = $('#edit_vendor_id').val();

            $.ajax({
                url: "{{ route('admin.vendors.update') }}/" + id,
                method: 'PUT',
                data: form.serialize(),
                success: function(response) {
                    if(response.status === 'success') {
                        editModal.hide();
                        resetForm(form);
                        location.reload();
                    }
                },
                error: function(xhr) {
                    if(xhr.status === 422) {
                        showValidationErrors(form, xhr.responseJSON.errors);
                    } else {
                        alert('Error occurred while updating vendor');
                    }
                }
            });
        });

        // Delete Vendor
        $('.delete-vendor').on('click', function() {
            if(confirm('Are you sure you want to delete this vendor?')) {
                const id = $(this).data('id');

                $.ajax({
                    url: "{{ route('admin.vendors.destroy') }}/" + id,
                    method: 'DELETE',
                    success: function(response) {
                        if(response.status === 'success') {
                            location.reload();
                        }
                    },
                    error: function() {
                        alert('Error occurred while deleting vendor');
                    }
                });
            }
        });

        // Reset forms when modals are hidden
        $('#createVendorModal').on('hidden.bs.modal', function() {
            resetForm($('#createVendorForm'));
        });

        $('#editVendorModal').on('hidden.bs.modal', function() {
            resetForm($('#editVendorForm'));
        });
    });
</script>

@endsection
