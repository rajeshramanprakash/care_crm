@extends('admin.layouts.app')

@section('title', $page_heading)

@section('header-css')
    <link rel="stylesheet" href="//cdn.datatables.net/1.13.1/css/jquery.dataTables.min.css">
@endsection
@section('navbar-right-links')
    <li class="nav-item">
        <a class="nav-link" title="Filters" data-widget="control-sidebar" data-controlsidebar-slide="true"
            href="javascript:void(0);" role="button">
            <i class="fas fa-filter"></i>
        </a>
    </li>
@endsection
@section('main')
    <div class="content-wrapper pb-5">
        <section class="content-header">
            <div class="container-fluid">
                <div class="d-flex justify-content-between mb-2">
                    <h1 class="m-0">{{ $page_heading }}</h1>
                </div>
            </div>
        </section>

        <section class="content">
            <div class="container-fluid">
                <div class="table-responsive">
                    <table id="casesTable" class="table text-sm">
                        <thead class="sticky_head bg-light">
                            <tr>
                                <th>ID</th>
                                <th>Is Priority</th>
                                <th>Case Code</th>
                                <th>Created By</th>
                                <th>Name</th>
                                <th>Claim No</th>
                                <th>Corp</th>
                                <th>Hospital</th>
                                <th>Approved Amt</th>
                                <th>Approved Date</th>
                                <th>Paid Date</th>
                                <th>Actions</th>
                                <th>Actions</th>
                                <th>Actions</th>
                                <th>Actions</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
        <aside class="control-sidebar control-sidebar-dark">
            <div class="p-3 control-sidebar-content">
                <h5>Lead Filters</h5>
                <hr class="mb-2">
                <form action="" id="filters-form" method="post">
                    @csrf
                    <div class="accordion text-sm" id="accordionExample">
                        <div class="accordion-item">
                            <div class="accordion text-sm" id="accordionExample">
                                <div class="accordion-item">
                                    <h2 class="accordion-header">
                                        <button class="btn btn-block btn-sm btn-secondary text-left text-bold text-light"
                                            type="button" data-bs-toggle="collapse" data-bs-target="#collapse41"
                                            aria-expanded="true" aria-controls="collapse41">Vendor</button>
                                    </h2>
                                    <div id="collapse41"
                                        class="accordion-collapse collapse {{ isset($filter_params['vendor_member']) ? 'show' : '' }}"
                                        data-bs-parent="#accordionExample">
                                        <div class="accordion-body pl-2 pb-4">
                                            @foreach ($vendors as $vendor)
                                                <div class="custom-control custom-checkbox my-1">
                                                    <input class="custom-control-input" type="checkbox"
                                                        id="vendor_member_{{ $vendor->id }}" name="vendor_member[]"
                                                        value="{{ $vendor->id }}"
                                                        {{ isset($filter_params['vendor_member']) && in_array($vendor->id, $filter_params['vendor_member']) ? 'checked' : '' }}>
                                                    <label for="vendor_member_{{ $vendor->id }}"
                                                        class="custom-control-label">{{ $vendor->f_name }}</label>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="my-5">
                                <button type="submit" class="btn btn-sm text-light btn-block"
                                    style="background-color: var(--wb-renosand);">Apply</button>
                                <a href="{{ route('admin.case.index') }}" type="submit"
                                    class="btn btn-sm btn-secondary btn-block">Reset</a>
                            </div>
                </form>
            </div>
        </aside>

        <div class="modal fade" id="tpaAllotmentModal" tabindex="-1" aria-labelledby="tpaAllotmentModalLabel"
            aria-hidden="true">
            <div class="modal-dialog">
                <form id="tpaAllotmentForm">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="tpaAllotmentModalLabel">TPA Allotment</h5>
                            <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">&times;</button>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" name="case_id" id="caseId">
                            <div class="text-center">Main Claim Tpa</div>
                            <hr />
                            <!-- TPA Type Selection -->
                            <div class="form-group">
                                <label for="tpa_type">TPA Type</label>
                                <select class="form-control" name="tpa_type" id="tpa_type" required>
                                    <option value="direct">Direct</option>
                                    <option value="first">First</option>
                                </select>
                            </div>

                            <!-- TPA Allotment 1 -->
                            <div class="form-group">
                                <label for="tpa_allot_after_claim_no_received">TPA 1</label>
                                <select class="form-control" name="tpa_allot_after_claim_no_received" id="tpa_allotment"
                                    >
                                    <option value="" disabled>Select TPA</option>
                                    @foreach ($tpa_roles as $tpa)
                                        <option value="{{ $tpa->id }}">
                                            {{ $tpa->f_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- TPA Allotment 2 -->
                            <div class="form-group">
                                <label for="tpa_allot_after_claim_no_received_two">TPA 2</label>
                                <select class="form-control" name="tpa_allot_after_claim_no_received_two"
                                    id="tpa_allotment_two">
                                    <option value="" disabled>Select TPA</option>
                                    @foreach ($tpa_roles as $tpa)
                                        <option value="{{ $tpa->id }}">
                                            {{ $tpa->f_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <hr />
                            <div class="text-center">Post One Tpa</div>
                            <hr />

                            {{-- post tpa  --}}
                            <!--Post TPA Type Selection -->
                            <div class="form-group">
                                <label for="post_tpa_type">Post One TPA Type</label>
                                <select class="form-control" name="post_tpa_type" id="post_tpa_type" required>
                                    <option value="direct">Direct</option>
                                    <option value="first">First</option>
                                </select>
                            </div>

                            <!--Post TPA Allotment 1 -->
                            <div class="form-group">
                                <label for="post_tpa_allot_after_claim_no_received">Post One TPA 1</label>
                                <select class="form-control" name="post_tpa_allot_after_claim_no_received" id="post_tpa_allot_after_claim_no_received"
                                    >
                                    <option value="" disabled>Select TPA</option>
                                    @foreach ($tpa_roles as $tpa)
                                        <option value="{{ $tpa->id }}">
                                            {{ $tpa->f_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <!--Post TPA Allotment 2 -->
                            <div class="form-group">
                                <label for="post_tpa_allot_after_claim_no_received_two">Post One TPA 2</label>
                                <select class="form-control" name="post_tpa_allot_after_claim_no_received_two"
                                    id="post_tpa_allot_after_claim_no_received_two">
                                    <option value="" disabled>Select TPA</option>
                                    @foreach ($tpa_roles as $tpa)
                                        <option value="{{ $tpa->id }}">
                                            {{ $tpa->f_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            {{-- post Two tpa  --}}
                            <hr />
                            <div class="text-center">Post Two Tpa</div>
                            <hr />
                            <!--Post TPA Type Selection -->
                            <div class="form-group">
                                <label for="post_two_tpa_type">Post Two TPA Type</label>
                                <select class="form-control" name="post_two_tpa_type" id="post_two_tpa_type" required>
                                    <option value="direct">Direct</option>
                                    <option value="first">First</option>
                                </select>
                            </div>

                            <!--Post TPA Allotment 1 -->
                            <div class="form-group">
                                <label for="post_two_tpa_allot_after_claim_no_received">Post Two TPA 1</label>
                                <select class="form-control" name="post_two_tpa_allot_after_claim_no_received" id="post_two_tpa_allot_after_claim_no_received"
                                    >
                                    <option value="" disabled>Select TPA</option>
                                    @foreach ($tpa_roles as $tpa)
                                        <option value="{{ $tpa->id }}">
                                            {{ $tpa->f_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <!--Post TPA Allotment 2 -->
                            <div class="form-group">
                                <label for="post_two_tpa_allot_after_claim_no_received_two">Post Two TPA 2</label>
                                <select class="form-control" name="post_two_tpa_allot_after_claim_no_received_two"
                                    id="post_two_tpa_allot_after_claim_no_received_two">
                                    <option value="" disabled>Select TPA</option>
                                    @foreach ($tpa_roles as $tpa)
                                        <option value="{{ $tpa->id }}">
                                            {{ $tpa->f_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-primary">Save changes</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        <div class="modal fade" id="clainNoModal" tabindex="-1" aria-labelledby="clainNoModalLabel"
            aria-hidden="true">
            <div class="modal-dialog">
                <form id="clainNoForm">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="clainNoModalLabel">Claim No</h5>
                            <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">&times;</button>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" name="case_id" id="caseIdclaim">

                            <div class="form-group">
                                <label for="claim_no">Main Claim no</label>
                                <input class="form-control" name="claim_no" id="claim_no_form_id" required>
                            </div>

                            <div class="form-group">
                                <label for="claim_no">Main Claim Link</label>
                                <input class="form-control" name="claim_no_link" id="claim_no_link_form_id" required>
                            </div>

                            <div class="form-group">
                                <label for="post_claim_no">Post 1 claim no</label>
                                <input class="form-control" name="post_claim_no" id="post_claim_no_form_id">
                            </div>

                            <div class="form-group">
                                <label for="post_claim_no">Post 1 claim Link</label>
                                <input class="form-control" name="post_claim_no_link" id="post_claim_no_link_form_id">
                            </div>

                            <div class="form-group">
                                <label for="post_two_claim_no">Post 2 claim no</label>
                                <input class="form-control" name="post_two_claim_no"
                                    id="post_two_claim_no_form_id" />
                            </div>

                            <div class="form-group">
                                <label for="post_two_claim_no">Post 2 claim link</label>
                                <input class="form-control" name="post_two_claim_no_link"
                                    id="post_two_claim_no_link_form_id" />
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-primary">Save changes</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

    </div>

@endsection

@section('footer-script')
    @php
        $filter = '';
        if (isset($filter_params['dashboard_filters'])) {
            $filter = 'dashboard_filters=' . $filter_params['dashboard_filters'];
        }
    @endphp
    <script src="//cdn.datatables.net/1.13.1/js/jquery.dataTables.min.js"></script>
    <script>
        function openTpaForm(caseId, tpaType, tpaAllotment, tpaAllotmentTwo) {
            $('#caseId').val(caseId);
            $('#tpa_type').val('');
            if (tpaType) {
                $('#tpa_type').find('option').each(function() {
                    if ($(this).val() == tpaType) {
                        $(this).prop('selected', true);
                    }
                });
            }
            $('#tpa_allotment').val('');
            if (tpaAllotment) {
                $('#tpa_allotment').find('option').each(function() {
                    if ($(this).val() == tpaAllotment) {
                        $(this).prop('selected', true);
                    }
                });
            }
            $('#tpa_allotment_two').val('');
            if (tpaAllotmentTwo) {
                $('#tpa_allotment_two').find('option').each(function() {
                    if ($(this).val() == tpaAllotmentTwo) {
                        $(this).prop('selected', true);
                    }
                });
            }

            // post 1 tpa

            $('#post_tpa_type').val('');
            if (tpaType) {
                $('#post_tpa_type').find('option').each(function() {
                    if ($(this).val() == tpaType) {
                        $(this).prop('selected', true);
                    }
                });
            }
            $('#post_tpa_allot_after_claim_no_received').val('');
            if (tpaAllotment) {
                $('#post_tpa_allot_after_claim_no_received').find('option').each(function() {
                    if ($(this).val() == tpaAllotment) {
                        $(this).prop('selected', true);
                    }
                });
            }
            $('#post_tpa_allot_after_claim_no_received_two').val('');
            if (tpaAllotmentTwo) {
                $('#post_tpa_allot_after_claim_no_received_two').find('option').each(function() {
                    if ($(this).val() == tpaAllotmentTwo) {
                        $(this).prop('selected', true);
                    }
                });
            }

            // post 2 tpa
            $('#post_two_tpa_type').val('');
            if (tpaType) {
                $('#post_two_tpa_type').find('option').each(function() {
                    if ($(this).val() == tpaType) {
                        $(this).prop('selected', true);
                    }
                });
            }
            $('#post_two_tpa_allot_after_claim_no_received').val('');
            if (tpaAllotment) {
                $('#post_two_tpa_allot_after_claim_no_received').find('option').each(function() {
                    if ($(this).val() == tpaAllotment) {
                        $(this).prop('selected', true);
                    }
                });
            }
            $('#post_two_tpa_allot_after_claim_no_received_two').val('');
            if (tpaAllotmentTwo) {
                $('#post_two_tpa_allot_after_claim_no_received_two').find('option').each(function() {
                    if ($(this).val() == tpaAllotmentTwo) {
                        $(this).prop('selected', true);
                    }
                });
            }
            $('#tpaAllotmentModal').modal('show');
        }
        function openClaimForm(caseId, claim_no, post_claim_no, post_two_claim_no, claim_no_link, post_claim_no_link, post_two_claim_no_link) {
            $('#caseIdclaim').val(caseId);
            $('#claim_no_form_id').val(claim_no);
            $('#claim_no_link_form_id').val(claim_no_link);
            $('#post_claim_no_form_id').val(post_claim_no);
            $('#post_claim_no_link_form_id').val(post_claim_no_link);
            $('#post_two_claim_no_form_id').val(post_two_claim_no);
            $('#post_two_claim_no_link_form_id').val(post_two_claim_no_link);
            $('#clainNoModal').modal('show');
        }


        $('#clainNoForm').on('submit', function(e) {
            e.preventDefault();

            let formDataArray = $(this).serializeArray();
            let formData = {};
            formDataArray.forEach(item => {
                formData[item.name] = item.value;
            });
            formData._token = '{{ csrf_token() }}';
            $.ajax({
                url: `{{ route('admin.cases.save-claimno') }}`,
                method: 'POST',
                data: formData,
                success: function(response) {
                    alert(response.message);
                    $('#clainNoModal').modal('hide');
                    location.reload();
                },
                error: function(xhr) {
                    alert('An error occurred. Please try again.');
                    console.error(xhr.responseText);
                }
            });
        });
        $('#tpaAllotmentForm').on('submit', function(e) {
            e.preventDefault();

            let formDataArray = $(this).serializeArray();
            let formData = {};
            formDataArray.forEach(item => {
                formData[item.name] = item.value;
            });
            formData._token = '{{ csrf_token() }}';
            $.ajax({
                url: `{{ route('admin.cases.save-tpa') }}`,
                method: 'POST',
                data: formData,
                success: function(response) {
                    alert(response.message);
                    $('#tpaAllotmentModal').modal('hide');
                    location.reload();
                },
                error: function(xhr) {
                    alert('An error occurred. Please try again.');
                    console.error(xhr.responseText);
                }
            });
        });


        $(document).ready(function() {
            const table = $('#casesTable').DataTable({
                processing: true,
                serverSide: false,
                ajax: {
                    url: `{{ route('admin_case_ajax') }}?{!! $filter !!}`,
                    data: function(d) {
                        let formData = $('#filters-form').serializeArray();
                        formData.forEach(function(item) {
                            if (item.name.endsWith('[]')) {
                                if (!d[item.name]) {
                                    d[item.name] = [];
                                }
                                d[item.name].push(item.value);
                            } else {
                                d[item.name] = item.value;
                            }
                        });
                    },
                    dataSrc: 'data'
                },
                columns: [{
                        data: 'id',
                        searchable: true,
                        sortable: true
                    },
                    {
                        data: 'is_priority',
                        visible: false,
                        searchable: true
                    },
                    {
                        data: 'case_code',
                        searchable: true,
                        sortable: true
                    },
                    {
                        data: 'created_by',
                        searchable: true,
                        sortable: true
                    },
                    {
                        data: 'name',
                        searchable: true,
                        sortable: true
                    },
                    {
                        data: 'claim_no',
                        searchable: true,
                        sortable: true
                    },
                    {
                        data: 'corp',
                        searchable: true,
                        sortable: true
                    },
                    {
                        data: 'hospital',
                        searchable: true,
                        sortable: true
                    },
                    {
                        data: 'post_two_approved_amt',
                        searchable: true,
                        sortable: true
                    },
                    {
                        data: 'post_two_approved_date',
                        render: function(data, type, row) {
                            return data ?
                                `${moment(data).format("DD-MMM-YYYY")}` :
                                'N/A';
                        },
                        searchable: true,
                        sortable: true
                    },

                    {
                        data: 'post_two_paid_date',
                        render: function(data, type, row) {
                            return data ?
                                `${moment(data).format("DD-MMM-YYYY")}` :
                                'N/A';
                        },
                        searchable: true,
                        sortable: true
                    },
                    {
                        data: 'claim_no',
                        visible: false,
                        searchable: true
                    },
                    {
                        data: 'post_claim_no',
                        visible: false,
                        searchable: true
                    },
                    {
                        data: 'post_two_claim_no',
                        visible: false,
                        searchable: true
                    },
                    {
                        data: 'member_id',
                        visible: false,
                        searchable: true
                    },
                    {
                        data: null,
                        render: function(data, type, row) {
                            return '<div class="display-flex"><button class="btn btn-info btn-view-case" data-id="' +
                                row.id + '" data-encrypted-id="' + row.encrypted_id +
                                '">View</button> <button class="btn btn-danger btn-del-case" data-id="' +
                                row.id + '">Delete</button></div>';
                        },
                        orderable: false
                    },
                ],
                order: [
                    [1, 'desc'],
                    [0, 'desc']
                ],
                responsive: true,
                paging: true,
                searching: true,
                lengthChange: true,
                autoWidth: false,
                rowCallback: function(row, data, index) {
                    row.style.backgroundColor = data.case_color;
                    row.style.color = data.text_color;
                }
            });

            $('#filters-form').on('submit', function(e) {
                e.preventDefault();
                table.ajax.reload(null, false);
                document.querySelector('[data-widget="control-sidebar"]').click();
            });
            $(document).on('click', '.btn-view-case', function() {
                var caseId = $(this).data('id');
                // Get encrypted case ID from the actions column
                var encryptedId = $(this).closest('tr').find('.btn-view-case').attr('data-encrypted-id');
                window.location.href = '/admin/cases/view/' + encryptedId;
            });
            $(document).on('click', '.btn-del-case', function() {
                var caseId = $(this).data('id');
                var confirmDelete = window.confirm(
                    'Are you sure you want to delete this case? This action cannot be undone.');
                if (confirmDelete) {
                    window.location.href = '/admin/cases/delete/' + caseId;
                }
            });

            $(document).on('click', '.priority-star', function() {
                var caseId = $(this).data('id');
                var starIcon = $(this).find('i');
                var isPriority = starIcon.hasClass('fas');

                // Toggle classes to visually reflect the change immediately
                starIcon.toggleClass('fas far');
                starIcon.toggleClass('text-warning');

                // Use the opposite value of isPriority to toggle
                $.ajax({
                    url: `/admin/cases/set-priority/${caseId}`,
                    method: 'POST',
                    data: {
                        is_priority: isPriority ? 0 : 1, // Toggle the value
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        console.log(response.message);
                    },
                    error: function(xhr) {
                        console.error(xhr.responseText);
                        alert('An error occurred while updating priority.');
                    }
                });
            });


        });
    </script>
@endsection
