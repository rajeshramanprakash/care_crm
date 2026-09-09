@extends('operation_manager.layouts.app')
@section('title', 'Job-Req')
@section('header-css')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.1/css/jquery.dataTables.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.4.0/css/responsive.dataTables.min.css">
<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
<style>
    .table-responsive { max-height: 600px; overflow-y: auto; }
    .table thead th { background-color: #f8f9fa; border-bottom: 2px solid #dee2e6; color: #495057; font-weight: 600; text-transform: uppercase; font-size: 0.85rem; padding: 12px 8px; white-space: nowrap; }
    .table tbody td { padding: 12px 8px; vertical-align: middle; font-size: 0.9rem; border-bottom: 1px solid #dee2e6; }
    .table tbody tr:hover { background-color: #f8f9fa; }
    /* Custom Table Scroll CSS */
    .custom-table-scroll-x {
        width: 100%;
        overflow-x: auto;
        border-radius: 12px;
        box-shadow: none;
        background: #fff;
        margin-bottom: 0;
    }
    .custom-table-scroll-x table {
        min-width: 1200px;
        width: 100%;
        table-layout: fixed;
    }
    table thead, table tfoot {
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
    /* Contact No column styling */
    .contact-cell {
        min-width: 200px;
        white-space: nowrap !important;
        word-break: normal !important;
        overflow-wrap: normal !important;
    }
    /* Allow other cells to break lines if content is long */
    .table td, .table th {
        word-break: break-word !important;
        white-space: normal !important;
        max-width: 180px;
        overflow-wrap: break-word !important;
    }
    .contact-icons {
        color: #27ae60;
        font-size: 1.3rem;
        margin-right: 6px;
        cursor: pointer;
        vertical-align: middle;
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
        <div class="container-fluid">
            <div class="card mb-4">
                <div class="card-header">
                    <h3 class="card-title" style="font-weight: 800; color: #ea8a2b;">All Job Requests</h3>
                </div>
                <div class="card-body">
                    <div class="custom-table-scroll-x">
                        <table id="job-request-table" class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Lead ID</th>
                                    <th>Date/Time</th>
                                    <th>Executive</th>
                                    <th>Customer</th>
                                    <th class="contact-cell">Contact</th>
                                    <th>Age</th>
                                    <th>12/24 hr</th>
                                    <th>Job Title</th>
                                    <th>City</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>


<!-- Show Job Request Modal -->
<div class="modal fade" id="showJobRequestModal" tabindex="-1" role="dialog" aria-labelledby="showJobRequestModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="showJobRequestModalLabel" style="color: #000;">Job Request Details</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <p style="padding: 10px; background-color:rgb(219, 217, 217); border-radius:7px;"><strong>Date/Time:</strong> <span id="show_date_time"></span></p>
                        <p style="padding: 10px; background-color:rgb(219, 217, 217); border-radius:7px;"><strong>Executive:</strong> <span id="show_executive"></span></p>
                        <p style="padding: 10px; background-color:rgb(219, 217, 217); border-radius:7px;"><strong>Customer Name:</strong> <span id="show_customer_name"></span></p>
                        <p style="padding: 10px; background-color:rgb(219, 217, 217); border-radius:7px;"><strong>Contact No:</strong> <span id="show_contact_no"></span></p>
                        <p style="padding: 10px; background-color:rgb(219, 217, 217); border-radius:7px;"><strong>Name:</strong> <span id="show_name"></span></p>
                        <p style="padding: 10px; background-color:rgb(219, 217, 217); border-radius:7px;"><strong>Age:</strong> <span id="show_age"></span></p>
                        <p style="padding: 10px; background-color:rgb(255, 243, 205); border-radius:7px;"><strong>Lead ID:</strong> <span id="show_lead_id" class="badge badge-warning" style="font-size:1.1em;"></span></p>
                    </div>
                    <div class="col-md-6">
                        <p style="padding: 10px; background-color:rgb(219, 217, 217); border-radius:7px;"><strong>Expected Salary:</strong> <span id="show_expected_salary"></span></p>
                        <p style="padding: 10px; background-color:rgb(219, 217, 217); border-radius:7px;"><strong>Shift:</strong> <span id="show_shift"></span></p>
                        <p style="padding: 10px; background-color:rgb(219, 217, 217); border-radius:7px;"><strong>Total Experience:</strong> <span id="show_total_experience"></span></p>
                        <p style="padding: 10px; background-color:rgb(219, 217, 217); border-radius:7px;"><strong>Job Title:</strong> <span id="show_job_title"></span></p>
                        <p style="padding: 10px; background-color:rgb(219, 217, 217); border-radius:7px;"><strong>City:</strong> <span id="show_city"></span></p>
                        <p style="padding: 10px; background-color:rgb(219, 217, 217); border-radius:7px;"><strong>Status:</strong> <span id="show_status"></span></p>
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-12">
                        <p style="padding: 10px; background-color:rgb(219, 217, 217); border-radius:7px;"><strong>Other Remark:</strong></p>
                        <p id="show_other_remark"></p>
                    </div>
                    <div class="col-12">
                        <p style="padding: 10px; background-color:rgb(219, 217, 217); border-radius:7px;"><strong>Remark:</strong></p>
                        <p id="show_remark"></p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
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
                            @foreach (["12", "24"] as $shift)
                            <div class="custom-control custom-checkbox my-1">
                                <input class="custom-control-input" type="checkbox"
                                    id="filter_shift_{{ $shift }}" name="shift[]"
                                    value="{{ $shift }}" {{ isset($filter_params['shift']) && in_array($shift, $filter_params['shift']) ? 'checked' : '' }}>
                                <label for="filter_shift_{{ $shift }}" class="custom-control-label">{{ $shift }} hr</label>
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
                            @foreach (["active", "inactive"] as $status)
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
@include('whatsapp.chat')

@endsection
@section('footer-script')
<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.4/moment.min.js"></script>
<script src="https://cdn.datatables.net/1.13.1/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.4.0/js/dataTables.responsive.min.js"></script>
<script>
function makeCall(customerNumber) {
    if (!confirm('Are you sure you want to make this call?')) return;
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
            url: '{{ route("operation-manager.jobproc.all_job_req.jobrequests") }}',
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
            },
            error: function (xhr, error, thrown) {
                toastr.error('Error loading job requests. Please try again.');
                console.error('DataTables error:', error);
            }
        },
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
            { data: 'executive', name: 'executive', orderable: false, searchable: false },
            { data: 'customer_name', name: 'customer_name' },
            { data: 'contact_no', name: 'contact_no', className: 'contact-cell',
                render: function(data, type, row) {
                    return `
                        <div class="">
                            <span>${data}</span>
                            <button onclick="makeCall('${data}')" class="btn btn-sm call-btn" title="Call">
                                <i class="fas fa-phone contact-icons"></i>
                            </button>
                            <i class="fab fa-whatsapp contact-icons" onclick="handle_whatsapp_msg('${data}')" id="what_id-${data}" title="WhatsApp"></i>
                        </div>
                    `;
                }
            },
            { data: 'age', name: 'age' },
            { data: 'shift', name: 'shift' },
            { data: 'job_title', name: 'job_title' },
            { data: 'city', name: 'city' },
            { data: 'status', name: 'status' },
            { data: 'action', name: 'action', orderable: false, searchable: false }
        ],
        order: [[0, 'desc']]
    });

    $('#filters-form').on('submit', function(e) {
        e.preventDefault();
        table.ajax.reload();
    });

    // Show Job Request
    $(document).on('click', '.show-job', function() {
        var id = $(this).data('id');
        $.ajax({
            url: '{{ url("operation-manager/jobproc") }}/' + id,
            type: 'GET',
            success: function(data) {
                $('#show_date_time').text(data.date_time);
                $('#show_executive').text(data.executive_name);
                $('#show_customer_name').text(data.customer_name);
                $('#show_contact_no').text(data.contact_no);
                $('#show_name').text(data.name);
                $('#show_age').text(data.age);
                $('#show_expected_salary').text(data.expected_salary);
                $('#show_shift').text(data.shift);
                $('#show_total_experience').text(data.total_experience);
                $('#show_job_title').text(data.job_title);
                $('#show_other_remark').text(data.other_remark);
                $('#show_city').text(data.city);
                $('#show_remark').text(data.remark);
                $('#show_status').text(data.status);
                $('#show_lead_id').text(data.lead_id);
                $('#showJobRequestModal').modal('show');
            },
            error: function(xhr) {
                toastr.error('Error loading job request details');
            }
        });
    });
});
</script>
@endsection
