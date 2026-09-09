@extends('operation_manager.layouts.app')

@section('title', $page_heading . ' | Leads')

@section('header-css')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.1/css/jquery.dataTables.min.css">
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

    <section class="content">
        <div class="container-fluid user-table-container">
            <div class="table-responsive">
                <div class="scrollable-table">
                    <table id="serverTable" class="table text-sm align-middle">
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
            </div>
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
@endsection

@section('footer-script')
    <script src="https://cdn.datatables.net/1.13.1/js/jquery.dataTables.min.js"></script>
    <script src="{{ asset('plugins/moment/moment.min.js') }}"></script>
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
            var table = $('#serverTable').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: "{{ route('operation-manager.users.getUsers') }}",
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
                        name: 'location_name'
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
                                <span class="ms-2 ${isOn ? 'toggle-label-on' : 'toggle-label-off'}">${isOn ? 'On Duty' : 'Off Duty'}</span>
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
                                <span class="ms-2 ${isActive ? 'toggle-label-on' : 'toggle-label-off'}">${isActive ? 'Active' : 'Break'}</span>
                            `;
                        }
                    },
                    {
                        data: 'action',
                        name: 'action',
                        orderable: false,
                        searchable: false,
                        render: function(data, type, row) {
                            return `
                                <div class="action-btns">
                                    <a href="/operation-manager/break-logs/user/${row.id}" class="btn btn-info" title="Break Logs"><i class="fas fa-clock"></i></a>
                                    <a href="/operation-manager/duty-logs/user/${row.id}" class="btn btn-success" title="Duty Logs"><i class="fas fa-user-check"></i></a>
                                </div>
                            `;
                        }
                    }
                ]
            });

            const csrfToken = "{{ csrf_token() }}";

            $(document).on('click', '.delete-btn', function() {
                if (confirm('Are you sure you want to delete this user?')) {
                    var userId = $(this).data('id');
                    $.ajax({
                        url: "{{ route('operation-manager.users.destroy', '') }}/" + userId,
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
                }
            });

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
                }
            });

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
        });
    </script>
@endsection
