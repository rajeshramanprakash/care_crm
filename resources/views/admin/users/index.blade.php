@extends('admin.layouts.app')

@section('title', $page_heading . ' | Leads')

@section('header-css')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.1/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.css" />
@endsection

@section('main')
<style>
    .user-table-container {
        background: #fff;
        border-radius: 12px;
        box-shadow: 0 2px 8px #0001;
        padding: 24px 18px;
        margin-top: 18px;
    }
    .role-filter {
        border-radius: 20px !important;
        margin-right: 8px;
        font-weight: 500;
        border: none !important;
        background: #f5f5f5 !important;
        color: #333 !important;
        transition: background 0.2s, color 0.2s;
    }
    .role-filter.active, .role-filter:hover {
        background: #ea8a2b !important;
        color: #fff !important;
    }
    .add-user-btn {
        background: #ea8a2b !important;
        border: none;
        border-radius: 20px;
        font-weight: 600;
        padding: 8px 22px;
        color: #fff !important;
        box-shadow: 0 2px 8px #ea8a2b22;
        transition: background 0.2s;
    }
    .add-user-btn:hover {
        background: #d97a1a !important;
    }
    .table thead th {
        background: #f7f7f7;
        font-weight: 700;
        border-bottom: 2px solid #ea8a2b;
        position: sticky;
        top: 0;
        z-index: 2;
    }
    .table tbody tr {
        transition: background 0.2s;
    }
    .table tbody tr:hover {
        background: #fff3e6;
    }
    .table td, .table th {
        vertical-align: middle !important;
    }
    .action-btns .btn {
        border-radius: 50%;
        width: 34px;
        height: 34px;
        padding: 0;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        margin-right: 4px;
    }
    .img-thumbnail {
        border-radius: 50% !important;
        border: 2px solid #ea8a2b22;
        box-shadow: 0 1px 4px #0001;
    }
    /* Scrollable table body */
    .scrollable-table {
        max-height: 480px;
        overflow-y: auto;
        width: 100%;
    }
    .scrollable-table table {
        margin-bottom: 0;
    }
    @media (max-width: 768px) {
        .user-table-container { padding: 8px 2px; }
        .table-responsive { font-size: 0.95rem; }
        .scrollable-table { max-height: 320px; }
    }
    /* Custom toggle switch styling for better visibility */
    .form-switch .form-check-input {
        width: 3em;
        height: 1.5em;
        background-color: #dee2e6;
        border-radius: 2em;
        transition: background-color 0.2s;
        cursor: pointer;
    }
    .form-switch .form-check-input:checked {
        background-color: #0d6efd;
    }
    .form-switch .form-check-input:focus {
        box-shadow: 0 0 0 0.2rem rgba(13,110,253,.25);
    }
    .form-switch .form-check-label {
        margin-left: 0.75em;
        font-weight: 500;
    }
    .toggle-label-on {
        color: #198754;
        font-weight: 600;
    }
    .toggle-label-off {
        color: #dc3545;
        font-weight: 600;
    }
</style>
<div class="content-wrapper pb-5">
    <section class="content-header">
        <div class="container-fluid">
            <div class="d-flex justify-content-between mb-2 align-items-center flex-wrap">
                <div class="d-flex flex-wrap mb-3">
                    <button class="btn role-filter active" data-role="">All</button>
                    @foreach ($roles as $role)
                        <button class="btn role-filter" data-role="{{ $role->name }}">{{ $role->name }}</button>
                    @endforeach
                    @can('create_user')
                    <a href="{{ route('admin.users.manage') }}" class="btn add-user-btn">
                        <i class="fas fa-user-plus"></i> Add New User
                    </a>
                    @endcan
                </div>                <div class="d-flex gap-2">
                    <a href="{{ route('admin.break_logs.index') }}" class="btn btn-info">
                        <i class="fas fa-clock"></i> Break Logs
                    </a>
                </div>
            </div>

        </div>
    </section>
    <section class="content">
        <div class="container-fluid user-table-container">
            <table id="serverTable" class="table text-sm align-middle" style="width:100%">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Image</th>
                        <th>First Name</th>
                        <th>Last Name</th>
                        <th>Email</th>
                        <th>Mobile</th>
                        <th>Location</th>
                        <th>Parent Member</th>
                        <th>Role</th>
                        <th>Lead Type</th>
                        <th>Created At</th>
                        <th>Duty Status</th>
                        <th>Break Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                </tbody>
            </table>
        </div>
    </section>
</div>
@include('whatsapp.chat')
<!-- Break Reason Modal -->
<div class="modal fade" id="breakReasonModal" tabindex="-1" aria-labelledby="breakReasonModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="breakReasonModalLabel">Enter Break Reason</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="breakReasonForm">
          <input type="hidden" id="break-user-id" name="user_id">
          <div class="mb-3">
            <label for="break-reason" class="form-label">Reason</label>
            <input type="text" class="form-control" id="break-reason" name="break_reason" required>
          </div>
          <button type="submit" class="btn btn-primary">Submit</button>
        </form>
      </div>
    </div>
  </div>
</div>

<!-- User Locations Modal -->
<div class="modal fade" id="userLocationsModal" tabindex="-1" aria-labelledby="userLocationsModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered" style="max-width: 90vw;">
    <div class="modal-content">
      <div class="modal-header" style="background: linear-gradient(135deg, #ea8a2b 0%, #d97a1a 100%); color: white; border: none;">
        <h5 class="modal-title" id="userLocationsModalLabel">
            <i class="fas fa-map-marked-alt"></i> User Locations
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-0">
        <div class="row g-0">
          <!-- Locations List Section -->
          <div class="col-md-4 p-4" style="background-color: #f8f9fa; border-right: 2px solid #ea8a2b;">
            <h6 class="mb-3" id="locationUserName" style="color: #ea8a2b; font-weight: 700;">
              <i class="fas fa-user-circle"></i> <span id="userNameText"></span>
            </h6>
            <p class="text-muted small mb-3">
              <i class="fas fa-info-circle"></i> Click any location to view on map
            </p>
            <div id="locationsListContainer" style="max-height: 650px; overflow-y: auto;">
                <!-- Locations will be dynamically inserted here -->
            </div>
          </div>
          
          <!-- Map Section -->
          <div class="col-md-8 p-0" style="position: relative;">
            <div id="mapPlaceholder" style="height: 700px; display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);">
              <div class="text-center">
                <i class="fas fa-map-marked-alt fa-5x mb-3" style="color: #ea8a2b; opacity: 0.3;"></i>
                <p style="color: #666; font-size: 16px; font-weight: 500;">Loading locations on map...</p>
              </div>
            </div>
            <div id="locationMap" style="height: 700px; display: none;"></div>
            <div id="mapLoader" style="display: none; position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); z-index: 1000; background: white; padding: 20px; border-radius: 10px; box-shadow: 0 4px 20px rgba(0,0,0,0.2);">
              <i class="fas fa-spinner fa-spin fa-3x" style="color: #ea8a2b;"></i>
              <p class="mt-3 mb-0" style="color: #666;">Loading map...</p>
            </div>
          </div>
        </div>
      </div>
      <div class="modal-footer" style="border-top: 2px solid #ea8a2b;">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
          <i class="fas fa-times"></i> Close
        </button>
      </div>
    </div>
  </div>
</div>

<style>
    .location-badge {
        display: block;
        background: linear-gradient(135deg, #ea8a2b 0%, #d97a1a 100%);
        color: white;
        padding: 8px 14px;
        margin: 6px 0;
        border-radius: 10px;
        font-weight: 600;
        font-size: 13px;
        box-shadow: 0 2px 8px rgba(234, 138, 43, 0.3);
        transition: all 0.3s ease;
        cursor: pointer;
        border: 2px solid transparent;
        text-align: left;
    }
    .location-badge:hover {
        transform: translateX(4px) scale(1.01);
        box-shadow: 0 4px 15px rgba(234, 138, 43, 0.5);
        border-color: white;
    }
    .location-badge.active {
        background: linear-gradient(135deg, #198754 0%, #157347 100%);
        border-color: white;
        box-shadow: 0 4px 15px rgba(25, 135, 84, 0.5);
    }
    .location-badge i {
        margin-right: 8px;
        font-size: 13px;
    }
    .location-badge .badge-arrow {
        float: right;
        font-size: 11px;
        transition: transform 0.3s;
    }
    .location-badge:hover .badge-arrow {
        transform: translateX(4px);
    }
    .no-locations {
        text-align: center;
        padding: 40px 20px;
        color: #999;
        font-style: italic;
    }
    .no-locations i {
        display: block;
        margin-bottom: 15px;
        color: #ea8a2b;
        opacity: 0.3;
    }
    
    /* Custom scrollbar for locations list */
    #locationsListContainer::-webkit-scrollbar {
        width: 8px;
    }
    #locationsListContainer::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 10px;
    }
    #locationsListContainer::-webkit-scrollbar-thumb {
        background: #ea8a2b;
        border-radius: 10px;
    }
    #locationsListContainer::-webkit-scrollbar-thumb:hover {
        background: #d97a1a;
    }
    
    /* Leaflet map custom marker */
    .custom-marker {
        background-color: #ea8a2b;
        border: 3px solid white;
        border-radius: 50%;
        width: 30px;
        height: 30px;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 3px 10px rgba(0,0,0,0.3);
    }
</style>
@endsection

@section('footer-script')
    <script src="https://cdn.datatables.net/1.13.1/js/jquery.dataTables.min.js"></script>
    <script src="{{ asset('plugins/moment/moment.min.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        const canEditUser = @json(auth()->user()->can('edit_user'));
        const canDeleteUser = @json(auth()->user()->can('delete_user'));
        const canViewUser = @json(auth()->user()->can('view_user'));
        const hasAnyActionPerm = canEditUser || canDeleteUser || canViewUser;
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
            var table = $('#serverTable').DataTable({
                processing: true,
                serverSide: true,
                scrollX: true,
                scrollY: "480px",
                scrollCollapse: true,
                autoWidth: false,
                ajax: {
                    url: "{{ route('admin.users.getUsers') }}",
                    data: function(d) {
                        d.role = $('.role-filter.active').data('role') || '';
                    }
                },
                columns: [{
                        data: 'id',
                        name: 'id'
                    },
                    {
                        data: 'profile_image',
                        name: 'profile_image',
                        render: function(data, type, row) {
                            var imageUrl = data ? `/storage/${data}` :
                                '{{ asset('images/default-user.png') }}';
                            return `<a onclick="handle_view_image('${imageUrl}', '{{ route('updateProfileImage') }}/${row.id}')" href="javascript:void(0);">
                    <img class="img-thumbnail" src="${imageUrl}" style="width: 50px;" onerror="this.onerror=null; this.src='{{ asset('images/default-user.png') }}'">
                </a>`;
                        }
                    },
                    {
                        data: 'f_name',
                        name: 'f_name'
                    },
                    {
                        data: 'l_name',
                        name: 'l_name'
                    },
                    {
                        data: 'email',
                        name: 'email',

                    },
                    {
                        data: 'mobile',
                        name: 'mobile',
                        render: function(data, type, row) {
                            return `
                                <div class="d-flex align-items-center">
                                    <span class="me-2">${data}</span>
                                    &nbsp;&nbsp;&nbsp;&nbsp;
                                    <button onclick="makeCall('${data}')" class="btn btn-sm call-btn me-2" title="Call" style="padding:0px;">
                                        <i class="fas fa-phone"></i>
                                    </button>
                                    &nbsp;&nbsp;&nbsp;&nbsp;
                                    <i class="fab fa-whatsapp" onclick="handle_whatsapp_msg('${data}')" id="what_id-${data}" style="font-size: 20px; color: green; cursor: pointer;" title="WhatsApp"></i>
                                </div>
                            `;
                        }
                    },
                    {
                        data: 'location_name',
                        name: 'location_name',
                        orderable: false,
                        searchable: false,
                        render: function(data, type, row) {
                            // Parse locations - it could be a comma-separated string or array
                            var locations = [];
                            if (data && data !== 'N/A') {
                                locations = typeof data === 'string' ? data.split(',').map(s => s.trim()) : data;
                            }
                            var locationCount = locations.length;
                            
                            return `
                                <button class="btn btn-xs btn-info view-locations-btn" 
                                    data-user-id="${row.id}" 
                                    data-locations='${JSON.stringify(locations)}'
                                    data-user-name="${row.f_name} ${row.l_name}"
                                    title="View Locations"
                                    style="font-size: 11px; padding: 4px 10px; border-radius: 12px;">
                                    <i class="fas fa-map-marker-alt"></i> ${locationCount}
                                </button>
                            `;
                        }
                    },
                    {
                        data: 'parent_name',
                        name: 'parent_name'
                    },
                    {
                        data: 'roles',
                        name: 'roles'
                    },
                    {
                        data: 'lead_type',
                        name: 'lead_type'
                    },
                    {
                        data: 'created_at',
                        name: 'created_at',
                        render: function(data) {
                            return moment(data).format('DD MMMM YYYY');
                        }
                    },
                    {
                        data: 'is_active',
                        name: 'is_active',
                        orderable: false,
                        searchable: false,
                        render: function(data, type, row) {
                            var isOn = row.is_active == 1;
                            return `
                                <span class="duty-toggle-icon" data-user-id="${row.id}" data-status="${isOn ? 1 : 0}" style="cursor:pointer; font-size:2rem; color:${isOn ? '#198754' : '#adb5bd'};">
                                    <i class="fas fa-toggle-${isOn ? 'on' : 'off'}"></i>
                                </span>
                            `;
                        }
                    },
                    {
                        data: 'is_break',
                        name: 'is_break',
                        orderable: false,
                        searchable: false,
                        render: function(data, type, row) {
                            var isActive = row.is_break == 0;
                            return `
                                <span class="break-toggle-icon" data-user-id="${row.id}" data-status="${isActive ? 0 : 1}" style="cursor:pointer; font-size:2rem; color:${isActive ? '#198754' : '#dc3545'};">
                                    <i class="fas fa-toggle-${isActive ? 'on' : 'off'}"></i>
                                </span>
                            `;
                        }
                    },
                    {
                        data: 'action',
                        name: 'action',
                        orderable: false,
                        searchable: false,
                        visible: hasAnyActionPerm,
                        render: function(data, type, row) {
                            // Conditionally render create or delete agent button
                            const agentButton = row.tata_agent_id 
                                ? `<button class="btn btn-secondary delete-agent-btn" data-id="${row.id}" data-agent-id="${row.tata_agent_id}" title="Delete Tata Agent"><i class="fas fa-times"></i></button>`
                                : `<button class="btn btn-warning create-agent-btn" data-id="${row.id}" title="Create Tata Agent"><i class="fas fa-phone"></i></button>`;
                            
                            let html = '<div class="action-btns">';
                            if (canEditUser) html += `<a href="/admin/users/manage/${row.id}" class="btn btn-primary" title="Edit"><i class="fas fa-edit"></i></a>`;
                            if (canViewUser) html += `<a href="/admin/break-logs/user/${row.id}" class="btn btn-info" title="Break Logs"><i class="fas fa-clock"></i></a>`;
                            if (canViewUser) html += `<a href="/admin/duty-logs/user/${row.id}" class="btn btn-success" title="Duty Logs"><i class="fas fa-user-check"></i></a>`;
                            if (canEditUser) html += agentButton;
                            if (canDeleteUser) html += `<button class="btn btn-danger delete-btn" data-id="${row.id}" title="Delete"><i class="fas fa-trash"></i></button>`;
                            html += '</div>';
                            return html;
                        }
                    }
                ]
            });

            const csrfToken = "{{ csrf_token() }}";

            $(document).on('click', '.delete-btn', function() {
                if (confirm('Are you sure you want to delete this user?')) {
                    var userId = $(this).data('id');
                    $.ajax({
                        url: "{{ route('admin.users.destroy', '') }}/" + userId,
                        type: 'get',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken
                        },
                        success: function(response) {
                            alert(response.message);
                            table.ajax.reload();
                        }
                    });
                }
            });

            // Create Agent functionality
            $(document).on('click', '.create-agent-btn', function() {
                var $btn = $(this);
                var userId = $btn.data('id');
                
                if (!confirm('Are you sure you want to create a Tata agent for this user?')) {
                    return;
                }

                // Disable button and show loading
                $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');

                $.ajax({
                    url: "/admin/users/" + userId + "/create-agent",
                    type: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken
                    },
                    success: function(response) {
                        if (response.success) {
                            toastr.success(response.message);
                            // Reload the table to show delete button
                            table.ajax.reload();
                        } else {
                            toastr.error(response.message);
                            $btn.prop('disabled', false).html('<i class="fas fa-phone"></i>');
                        }
                    },
                    error: function(xhr) {
                        var errorMessage = 'Failed to create agent';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        }
                        toastr.error(errorMessage);
                        $btn.prop('disabled', false).html('<i class="fas fa-phone"></i>');
                    }
                });
            });

            // Delete Agent functionality
            $(document).on('click', '.delete-agent-btn', function() {
                var $btn = $(this);
                var userId = $btn.data('id');
                var agentId = $btn.data('agent-id');
                
                if (!confirm('Are you sure you want to delete the Tata agent for this user? This action cannot be undone.')) {
                    return;
                }

                // Disable button and show loading
                $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');

                $.ajax({
                    url: "/admin/users/" + userId + "/delete-agent",
                    type: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken
                    },
                    success: function(response) {
                        if (response.success) {
                            toastr.success(response.message);
                            // Reload the table to show create button
                            table.ajax.reload();
                        } else {
                            toastr.error(response.message);
                            $btn.prop('disabled', false).html('<i class="fas fa-times"></i>');
                        }
                    },
                    error: function(xhr) {
                        var errorMessage = 'Failed to delete agent';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        }
                        toastr.error(errorMessage);
                        $btn.prop('disabled', false).html('<i class="fas fa-times"></i>');
                    }
                });
            });

            // Duty icon logic
            $(document).off('click', '.duty-toggle-icon');
            $(document).on('click', '.duty-toggle-icon', function() {
                var $icon = $(this);
                var userId = $icon.data('user-id');
                var isActive = $icon.data('status') == 1 ? 0 : 1; // toggle
                if (isActive) {
                    // Set to On Duty
                    $.ajax({
                        url: `/duty_active/${userId}/1`,
                        type: 'GET',
                        success: function(response) {
                            toastr.success('User set to On Duty');
                            // Update icon and label instantly
                            $icon.data('status', 1);
                            $icon.css('color', '#198754');
                            $icon.find('i').removeClass('fa-toggle-off').addClass('fa-toggle-on');
                            $icon.next('span').removeClass('toggle-label-off').addClass('toggle-label-on').text('On Duty');
                        },
                        error: function(xhr) {
                            toastr.error('Failed to update duty status');
                        }
                    });
                } else {
                    // Set to Off Duty (show modal for reason)
                    $('#break-user-id').val(userId);
                    $('#break-reason').val('');
                    $('#breakReasonModalLabel').text('Enter Duty Off Reason');
                    $('#breakReasonForm').data('toggle-type', 'duty');
                    var modal = new bootstrap.Modal(document.getElementById('breakReasonModal'));
                    modal.show();
                    // On modal submit, the table will reload or update
                }
            });
            // Break icon logic
            $(document).off('click', '.break-toggle-icon');
            $(document).on('click', '.break-toggle-icon', function() {
                var $icon = $(this);
                var userId = $icon.data('user-id');
                var isBreak = $icon.data('status') == 0 ? 1 : 0; // toggle
                if (isBreak === 0) {
                    // Set to Active
                    $.ajax({
                        url: `/break_active/${userId}/0`,
                        type: 'GET',
                        success: function(response) {
                            toastr.success('User set to Active');
                            // Update icon and label instantly
                            $icon.data('status', 0);
                            $icon.css('color', '#198754');
                            $icon.find('i').removeClass('fa-toggle-off').addClass('fa-toggle-on');
                            $icon.next('span').removeClass('toggle-label-off').addClass('toggle-label-on').text('Active');
                        },
                        error: function(xhr) {
                            toastr.error('Failed to update break status');
                        }
                    });
                } else {
                    // Set to Break (show modal for reason)
                    $('#break-user-id').val(userId);
                    $('#break-reason').val('');
                    $('#breakReasonModalLabel').text('Enter Break Reason');
                    $('#breakReasonForm').data('toggle-type', 'break');
                    var modal = new bootstrap.Modal(document.getElementById('breakReasonModal'));
                    modal.show();
                    // On modal submit, the table will reload or update
                }
            });
            // Update breakReasonForm submit to handle both toggles
            $('#breakReasonForm').on('submit', function(e) {
                e.preventDefault();
                var userId = $('#break-user-id').val();
                var reason = $('#break-reason').val();
                var toggleType = $(this).data('toggle-type');
                var url = '';
                if (toggleType === 'duty') {
                    url = `/duty_active/${userId}/0/${encodeURIComponent(reason)}`;
                } else {
                    url = `/break_active/${userId}/1/${encodeURIComponent(reason)}`;
                }
                $.ajax({
                    url: url,
                    type: 'GET',
                    success: function(response) {
                        toastr.success('User status updated');
                        var modal = bootstrap.Modal.getInstance(document.getElementById('breakReasonModal'));
                        modal.hide();
                        // Update the correct icon and label instantly
                        if (toggleType === 'duty') {
                            var $icon = $(`.duty-toggle-icon[data-user-id='${userId}']`);
                            $icon.data('status', 0);
                            $icon.css('color', '#adb5bd');
                            $icon.find('i').removeClass('fa-toggle-on').addClass('fa-toggle-off');
                            $icon.next('span').removeClass('toggle-label-on').addClass('toggle-label-off').text('Off Duty');
                        } else {
                            var $icon = $(`.break-toggle-icon[data-user-id='${userId}']`);
                            $icon.data('status', 1);
                            $icon.css('color', '#dc3545');
                            $icon.find('i').removeClass('fa-toggle-on').addClass('fa-toggle-off');
                            $icon.next('span').removeClass('toggle-label-on').addClass('toggle-label-off').text('Break');
                        }
                    },
                    error: function(xhr) {
                        toastr.error('Failed to update user status');
                    }
                });
            });

            $('.role-filter').on('click', function() {
                $('.role-filter').removeClass('active');
                $(this).addClass('active');
                table.ajax.reload();
            });

            // Map related variables
            let locationMap = null;
            let allMarkers = [];
            let locationData = [];
            let highlightedLocation = null;

            // Handle View Locations button click
            $(document).on('click', '.view-locations-btn', function() {
                var locations = JSON.parse($(this).attr('data-locations'));
                var userName = $(this).attr('data-user-name');
                
                // Set user name in modal
                $('#userNameText').text(userName);
                
                // Clear previous content
                var $container = $('#locationsListContainer');
                $container.empty();
                
                // Reset highlighted location
                highlightedLocation = null;
                
                if (locations && locations.length > 0) {
                    // Create location badges
                    var html = '';
                    locations.forEach(function(location, index) {
                        if (location && location.trim() !== '') {
                            html += `
                                <div class="location-badge" data-location="${location.trim()}" data-index="${index}">
                                    <i class="fas fa-map-marker-alt"></i> ${location.trim()}
                                    <i class="fas fa-chevron-right badge-arrow"></i>
                                </div>
                            `;
                        }
                    });
                    $container.html(html);
                    
                    // Load all locations on map
                    loadAllLocationsOnMap(locations);
                } else {
                    $container.html('<div class="no-locations"><i class="fas fa-map-marker-alt fa-3x"></i><p>No locations assigned</p></div>');
                    $('#mapPlaceholder').show();
                    $('#locationMap').hide();
                }
                
                // Show modal
                var modal = new bootstrap.Modal(document.getElementById('userLocationsModal'));
                modal.show();
            });

            // Function to load all locations on map
            async function loadAllLocationsOnMap(locations) {
                $('#mapLoader').show();
                $('#mapPlaceholder').hide();
                
                locationData = [];
                
                // Geocode all locations
                for (let location of locations) {
                    if (location && location.trim() !== '') {
                        try {
                            const response = await fetch(`https://nominatim.openstreetmap.org/search?city=${encodeURIComponent(location.trim())}&country=India&format=json&limit=1`);
                            const data = await response.json();
                            
                            if (data && data.length > 0) {
                                locationData.push({
                                    name: location.trim(),
                                    lat: parseFloat(data[0].lat),
                                    lon: parseFloat(data[0].lon),
                                    displayName: data[0].display_name
                                });
                            }
                        } catch (error) {
                            console.error(`Error geocoding ${location}:`, error);
                        }
                    }
                }
                
                if (locationData.length > 0) {
                    // Hide loader and show map
                    $('#mapLoader').hide();
                    $('#locationMap').show();
                    
                    // Initialize map centered on India
                    if (locationMap) {
                        locationMap.remove();
                    }
                    
                    locationMap = L.map('locationMap').setView([20.5937, 78.9629], 5); // India center
                    
                    // Add OpenStreetMap tiles
                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        attribution: '© OpenStreetMap contributors',
                        maxZoom: 19
                    }).addTo(locationMap);
                    
                    // Add markers for all locations
                    allMarkers = [];
                    
                    locationData.forEach(function(loc) {
                        // Create custom icon - orange by default
                        var markerIcon = L.divIcon({
                            className: 'custom-div-icon',
                            html: '<div class="custom-marker"><i class="fas fa-map-pin" style="color: white; font-size: 16px;"></i></div>',
                            iconSize: [30, 30],
                            iconAnchor: [15, 30]
                        });
                        
                        // Add marker
                        var marker = L.marker([loc.lat, loc.lon], { icon: markerIcon })
                            .addTo(locationMap)
                            .bindPopup(`
                                <div style="text-align: center;">
                                    <h6 style="color: #ea8a2b; margin-bottom: 8px;">
                                        <i class="fas fa-map-marker-alt"></i> ${loc.name}
                                    </h6>
                                    <p style="margin: 0; font-size: 12px; color: #666;">${loc.displayName}</p>
                                </div>
                            `);
                        
                        marker.locationName = loc.name;
                        allMarkers.push(marker);
                    });
                    
                    // Fit map to show all markers
                    if (allMarkers.length > 0) {
                        var group = L.featureGroup(allMarkers);
                        locationMap.fitBounds(group.getBounds().pad(0.1));
                    }
                    
                    toastr.success(`Loaded ${locationData.length} location(s) on map`);
                } else {
                    $('#mapLoader').hide();
                    toastr.error('Could not load locations on map');
                }
            }

            // Handle location badge click to highlight
            $(document).on('click', '.location-badge', function() {
                var locationName = $(this).data('location');
                
                // Check if clicking on already highlighted location
                if (highlightedLocation === locationName) {
                    // Un-highlight
                    highlightedLocation = null;
                    $(this).removeClass('active');
                    
                    // Zoom back to show all locations with smooth animation
                    if (allMarkers.length > 0) {
                        var group = L.featureGroup(allMarkers);
                        locationMap.flyToBounds(group.getBounds().pad(0.1), {
                            animate: true,
                            duration: 1
                        });
                    }
                } else {
                    // Highlight this location
                    $('.location-badge').removeClass('active');
                    $(this).addClass('active');
                    highlightedLocation = locationName;
                    
                    // Find the location data
                    var loc = locationData.find(l => l.name === locationName);
                    if (loc) {
                        // Zoom to location and center it with smooth animation
                        locationMap.flyTo([loc.lat, loc.lon], 11, {
                            animate: true,
                            duration: 1
                        });
                        
                        // Open popup for this marker
                        allMarkers.forEach(function(marker) {
                            if (marker.locationName === locationName) {
                                marker.openPopup();
                            }
                        });
                    }
                }
            });

            // Clean up map when modal is closed
            $('#userLocationsModal').on('hidden.bs.modal', function () {
                if (locationMap) {
                    locationMap.remove();
                    locationMap = null;
                }
                allMarkers = [];
                locationData = [];
                highlightedLocation = null;
                $('.location-badge').removeClass('active');
                $('#mapPlaceholder').show();
                $('#locationMap').hide();
            });
        });
    </script>
@endsection
