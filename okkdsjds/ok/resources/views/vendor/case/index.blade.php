@extends('vendor.layouts.app')

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
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createCaseModal">Create New
                        Case</button>
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
                                <th>Case Code</th>
                                <th>Case Code</th>
                                <th>Name</th>
                                <th>Age</th>
                                <th>Corp</th>
                                <th>Relation</th>
                                <th class="no-wrap">Case Satus / Department</th>
                                <th>Date of Admission</th>
                                <th>Date of Discharge</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
        <!-- Modal for creating a new case -->
        <div class="modal fade" id="createCaseModal" tabindex="-1" aria-labelledby="createCaseModalLabel">
            <div class="modal-dialog modal-lg">
                <form id="createCaseForm">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="createCaseModalLabel">Create Case</h5>
                            <a class="btn btn-close txt-black" data-bs-dismiss="modal" aria-label="Close">
                                <i class="fas fa-times"></i>
                            </a>
                        </div>
                        <div class="modal-body row">
                            <div class="form-group col-lg-6 col-sm-12">
                                <label>Name</label>
                                <input type="text" class="form-control" name="name" required>
                            </div>
                            <div class="col-6 row">
                                <div class="form-group col-lg-6 col-sm-12">
                                    <label>Age</label>
                                    <input type="number" class="form-control" name="age" required>
                                </div>
                                <div class="form-group col-lg-6 col-sm-12">
                                    <label>Gender</label>
                                    <select class="form-control" name="gender" required>
                                        <option value="Male">Male</option>
                                        <option value="Female">Female</option>
                                        <option value="Other">Other</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group col-lg-6 col-sm-12">
                                <label>Corp</label>
                                <input type="text" class="form-control" name="corp" required>
                            </div>
                            <div class="form-group col-lg-6 col-sm-12">
                                <label for="member_id">Member Id</label>
                                <input type="text" class="form-control" name="member_id" id="member_id" required>
                            </div>
                            <div class="form-group col-lg-6 col-sm-12">
                                <label for="relation">Relation</label>
                                <select class="form-control" name="relation" id="relation" required>
                                    <option value="self">Self</option>
                                    <option value="mother">Mother</option>
                                    <option value="father">Father</option>
                                    <option value="daughter">Daughter</option>
                                    <option value="son">Son</option>
                                    <option value="husband">Husband</option>
                                    <option value="wife">Wife</option>
                                    <option value="brother">Brother</option>
                                    <option value="sister">Sister</option>
                                </select>
                            </div>
                            <div class="form-group col-lg-6 col-sm-12">
                                <label for="tpa">Tpa</label>
                                <select class="form-control" name="tpa" id="tpa" required>
                                    <option selected disabled>Choose</option>
                                    <option value="Mediassist">Mediassist</option>
                                    <option value="Paramount">Paramount</option>
                                    <option value="Raksha">Raksha</option>
                                    <option value="Hdfc">Hdfc</option>
                                    <option value="Digit">Digit</option>
                                    <option value="Good health">Good health</option>
                                    <option value="Icici">Icici</option>
                                    <option value="Bajaj">Bajaj</option>
                                    <option value="Vidal">Vidal</option>
                                    <option value="Hitpa">Hitpa</option>
                                    <option value="Health india">Health india</option>
                                    <option value="Ericson">Ericson</option>
                                    <option value="Universal sampoo">Universal sampoo</option>
                                    <option value="Nivs bupa">Nivs bupa</option>
                                    <option value="Navi">Navi</option>
                                    <option value="Chola ms">Chola ms</option>
                                    <option value="Care">Care</option>
                                    <option value="Aditya birla">Aditya birla</option>
                                    <option value="East west">East west</option>
                                    <option value="Md india">Md india</option>
                                </select>
                            </div>
                            <div class="form-group col-lg-6 col-sm-12">
                                <label>Date of Admission</label>
                                <input type="date" class="form-control" name="doa">
                            </div>
                            <div class="form-group col-lg-6 col-sm-12">
                                <label>Time of Admission</label>
                                <input type="time" class="form-control" name="doa_time">
                            </div>
                            <div class="form-group col-lg-6 col-sm-12">
                                <label>Date of Discharge</label>
                                <input type="date" class="form-control" name="dod">
                            </div>
                            <div class="form-group col-lg-6 col-sm-12">
                                <label>Time of Discharge</label>
                                <input type="time" class="form-control" name="dod_time">
                            </div>
                            <div class="form-group col-lg-6 col-sm-12">
                                <label for="aadhar_attachment">Aadhar Attachment</label>
                                <input type="file" class="form-control" name="aadhar_attachment"
                                    id="aadhar_attachment">
                            </div>
                            <div class="form-group col-lg-6 col-sm-12">
                                <label for="aadhar_attachment_2">Aadhar Attachment 2</label>
                                <input type="file" class="form-control" name="aadhar_attachment_2"
                                    id="aadhar_attachment_2">
                            </div>
                            <div class="form-group col-lg-6 col-sm-12">
                                <label for="pan_card">PAN Card</label>
                                <input type="file" class="form-control" name="pan_card" id="pan_card">
                            </div>
                            <div class="form-group col-lg-6 col-sm-12">
                                <label for="cancelled_cheque">Cancelled Cheque</label>
                                <input type="file" class="form-control" name="cancelled_cheque"
                                    id="cancelled_cheque">
                            </div>
                            <div class="form-group col-lg-6 col-sm-12">
                                <label for="policy">Policy</label>
                                <input type="file" class="form-control" name="policy" id="policy">
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="submit" class="btn btn-primary">Create Case</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('footer-script')
    <script src="//cdn.datatables.net/1.13.1/js/jquery.dataTables.min.js"></script>
    @php
        $filter = '';
        if (isset($filter_params['dashboard_filters'])) {
            $filter = 'dashboard_filters=' . $filter_params['dashboard_filters'];
        }
    @endphp
    <script>
        $(document).ready(function() {
            function toggleDateRequirements() {
                const relation = $('#relation').val();
                const isSelf = relation === 'self';
                $('input[name="doa"], input[name="dod"]').prop('required', isSelf);
            }

            toggleDateRequirements();

            $('#relation').on('change', function() {
                toggleDateRequirements();
            });

            const table = $('#casesTable').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: `{{ route('vendor_case_ajax') }}?{!! $filter !!}`,
                    dataSrc: 'data'
                },
                columns: [{
                        data: 'id',
                        searchable: true,
                        sortable: true
                    },
                    {
                        data: 'is_priority',
                        visible: false
                    },
                    {
                        data: 'case_code',
                        searchable: true,
                        sortable: true
                    },
                    {
                        data: 'name',
                        searchable: true,
                        sortable: true
                    },
                    {
                        data: 'age',
                        render: function(data, type, row) {
                            return data ? `${data}--${row.gender}` : ``;
                            },
                        searchable: true,
                        sortable: true,

                    },
                    {
                        data: 'corp',
                        searchable: true,
                        sortable: true
                    },
                    {
                        data: 'relation',
                        searchable: true,
                        sortable: true
                    },
                    {
                        data: 'case_department',
                        searchable: true,
                        sortable: true
                    },
                    {
                        data: 'doa',
                        render: function(data, type, row) {
                            return data ?
                                `${moment(data).format("DD-MMM-YYYY")} at ${moment(row.doa_time, "HH:mm:ss").format("hh:mm A")}` :
                                '-';
                        },
                        searchable: true,
                        sortable: true
                    },
                    {
                        data: 'dod',
                        render: function(data, type, row) {
                            return data ?
                                `${moment(data).format("DD-MMM-YYYY")} at ${moment(row.dod_time, "HH:mm:ss").format("hh:mm A")}` :
                                '-';
                        },
                        searchable: true,
                        sortable: true
                    },
                    {
                        data: null,
                        render: function(data, type, row) {
                            return '<button class="btn btn-info btn-view-case" data-id="' + row.id +
                                '" data-encrypted-id="' + row.encrypted_id + '">View</button>';
                        },
                        orderable: false
                    }
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
            $(document).on('click', '.btn-view-case', function() {
                var caseId = $(this).data('id'); // Get case ID from data attribute
                var encryptedId = $(this).data('encrypted-id');
                window.location.href = '/vendor/cases/view/' + encryptedId; // Redirect to case show page
            });


            $('#createCaseForm').submit(function(e) {
                e.preventDefault();
                const formData = new FormData(this);

                $.ajax({
                    url: `{{ route('vendor.case.store') }}`,
                    method: 'POST',
                    data: formData,
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        if (response.success) {
                            $('#createCaseModal').modal('hide');
                            table.ajax.reload();
                            alert(response.message);
                        }
                    }
                });
            });
        });
    </script>
@endsection
