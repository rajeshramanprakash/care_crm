@extends('admin.layouts.app')

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
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCaseModal">Add New Case</button>
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
                                <th>Tpa Member</th>
                                <th>Claim No</th>
                                <th>Name</th>
                                <th>Hospital</th>
                                <th>Corp</th>
                                <th>Paid Amount</th>
                                <th>Approved Type</th>
                                <th>Commission</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </section>

        <!-- Add Case Modal -->
        <div class="modal fade" id="addCaseModal" tabindex="-1" aria-labelledby="addCaseModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content  modal-lg">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addCaseModalLabel">Add Case</h5>
                        <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <form id="addCaseForm">
                            @csrf
                            <div class="row">
                                <div class="form-group col-6">
                                    <label for="claimNo">Claim No</label>
                                    <input type="text" class="form-control" id="claimNo" name="claim_no" required>
                                </div>
                                <div class="form-group col-6">
                                    <label for="name">Name</label>
                                    <input type="text" class="form-control" id="name" name="name" required>
                                </div>
                                <div class="form-group col-6">
                                    <label for="hospital">Hospital</label>
                                    <input type="text" class="form-control" id="hospital" name="hospital" required>
                                </div>
                                <div class="form-group col-6">
                                    <label for="corp">Corp</label>
                                    <input type="text" class="form-control" id="corp" name="corp" required>
                                </div>
                                <div class="form-group col-6">
                                    <label for="paidAmt">Paid Amount</label>
                                    <input type="number" class="form-control" id="paidAmt" name="paid_amt" required>
                                </div>
                                <div class="form-group col-6">
                                    <label for="approvedType">Approved Type</label>
                                    <select class="form-control" id="approvedType" name="approved_type" required>
                                        <option value="">Select Approved Type</option>
                                        <option value="Direct Commission">Direct Commission</option>
                                        <option value="First Commission">First Commission</option>
                                        <option value="Second Commission">Second Commission</option>
                                    </select>
                                </div>
                                <div class="form-group col-6">
                                    <label for="tpaMember">TPA Member</label>
                                    <select class="form-control" id="tpaMember" name="user_id" required>
                                        <option value="">Select TPA Member</option>
                                        @foreach ($tpaMembers as $member)
                                            <option value="{{ $member->id }}">{{ $member->f_name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary">Add Case</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('footer-script')
    <script src="//cdn.datatables.net/1.13.1/js/jquery.dataTables.min.js"></script>
    <script>
        $(document).ready(function() {
            const table = $('#casesTable').DataTable({
                processing: true,
                serverSide: true,
                ajax: '{{ route('admin.tpa_case.ajax_list') }}',
                columns: [{
                        data: 'id'
                    },
                    {
                        data: 'tpa_member'
                    },
                    {
                        data: 'claim_no'
                    },
                    {
                        data: 'name'
                    },
                    {
                        data: 'hospital'
                    },
                    {
                        data: 'corp'
                    },
                    {
                        data: 'paid_amt'
                    },
                    {
                        data: 'approved_type'
                    },
                    {
                        data: 'commission'
                    },
                    {
                        data: null,
                        render: function(data, type, row) {
                            return `
                                <button class="btn btn-danger btn-sm delete-case" data-id="${row.id}">Delete</button>
                            `;
                        },
                        orderable: false
                    }
                ]
            });

            $('#addCaseForm').on('submit', function(e) {
                e.preventDefault();
                const formData = $(this).serialize();
                $.post('{{ route('admin.tpa_case.store') }}', formData, function(response) {
                    $('#caseModal').modal('hide');
                    table.ajax.reload();
                }).fail(function(xhr) {
                    alert('Failed to add case.');
                });
            });

            $(document).on('click', '.delete-case', function() {
                const id = $(this).data('id');
                if (confirm('Are you sure you want to delete this case?')) {
                    $.ajax({
                        url: `/admin/tpa-case/${id}`,
                        method: 'DELETE',
                        data: {
                            _token: '{{ csrf_token() }}'
                        },
                        success: function() {
                            table.ajax.reload();
                            window.location.reload();
                        },
                        error: function() {
                            alert('Failed to delete the case.');
                        }
                    });
                }
            });
        });
    </script>
@endsection
