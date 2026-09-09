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
                                <th>Case Code</th>
                                <th>Case Code</th>
                                <th>Created By</th>
                                <th>Name</th>
                                <th>Age</th>
                                <th>Corp</th>
                                <th>Relation</th>
                                <th>Date of Admission</th>
                                <th>Date of Discharge</th>
                                <th>Claim no</th>
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
                            <label for="claim_no">Main Claim link</label>
                            <input class="form-control" name="claim_no_link" id="claim_no_link_form_id">
                        </div>

                        <div class="form-group">
                            <label for="post_claim_no">Post 1 claim no</label>
                            <input class="form-control" name="post_claim_no" id="post_claim_no_form_id">
                        </div>
                        <div class="form-group">
                            <label for="post_claim_no">Post 1 claim link</label>
                            <input class="form-control" name="post_claim_no_link" id="post_claim_no_link_form_id">
                        </div>

                        <div class="form-group">
                            <label for="post_two_claim_no">Post 2 claim no</label>
                            <input class="form-control" name="post_two_claim_no" id="post_two_claim_no_form_id" />
                        </div>
                        <div class="form-group">
                            <label for="post_two_claim_no">Post 2 claim link</label>
                            <input class="form-control" name="post_two_claim_no_link" id="post_two_claim_no_link_form_id" />
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
    <script src="//cdn.datatables.net/1.13.1/js/jquery.dataTables.min.js"></script>
    @php
        $filter = '';
        if (isset($filter_params['dashboard_filters'])) {
            $filter = 'dashboard_filters=' . $filter_params['dashboard_filters'];
        }
    @endphp
    <script>

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
                url: `{{ route('postsales.cases.save-claimno') }}`,
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

        $(document).ready(function() {
            const table = $('#casesTable').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: `{{ route('postsales_case_ajax') }}?{!! $filter !!}`,
                    dataSrc: 'data'
                },
                columns: [{
                        data: 'id',
                        searchable: true,
                        sortable: true
                    },
                    {
                        data: 'is_priority',
                        visible: false // Hide this column from display
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
return `<button class="btn btn-primary btn-sm" onclick="openClaimForm('${row.id}', '${row.claim_no || ''}', '${row.post_claim_no || ''}', '${row.post_two_claim_no || ''}', '${row.claim_no_link || ''}', '${row.post_claim_no_link || ''}', '${row.post_two_claim_no_link || ''}')">Claim no.</button>`;
                        },
                        orderable: false
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
                var caseId = $(this).data('id');
                var encryptedId = $(this).data('encrypted-id');
                window.location.href = '/postsales/cases/view/' + encryptedId;
            });
        });
    </script>
@endsection
