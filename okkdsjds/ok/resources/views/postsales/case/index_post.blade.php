@extends('postsales.layouts.app')

@section('title', $page_heading)

@section('header-css')
    <link rel="stylesheet" href="//cdn.datatables.net/1.13.1/css/jquery.dataTables.min.css">
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
        <aside class="control-sidebar control-sidebar-dark" style="display: none;">
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
                                <a href="{{ route('postsales.case.index') }}" type="submit"
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


        $(document).ready(function() {
            const table = $('#casesTable').DataTable({
                processing: true,
                serverSide: false,
                ajax: {
                    url: `{{ route('postsales_case_ajax') }}?{!! $filter !!}`,
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
                        data: 'post_approved_amt',
                        searchable: true,
                        sortable: true
                    },
                    {
                        data: 'post_approved_date',
                        render: function(data, type, row) {
                            return data ?
                                `${moment(data).format("DD-MMM-YYYY")}` :
                                'N/A';
                        },
                        searchable: true,
                        sortable: true
                    },

                    {
                        data: 'post_paid_date',
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
                                '">View</button>';
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
                var encryptedId = $(this).data('encrypted-id');
                window.location.href = '/postsales/cases/view/' + encryptedId;
            });


        });
    </script>
@endsection
