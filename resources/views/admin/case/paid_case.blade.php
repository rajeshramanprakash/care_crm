@extends('admin.layouts.app')

@section('title', 'Paid Cases')

@section('header-css')
<link rel="stylesheet" href="//cdn.datatables.net/1.13.1/css/jquery.dataTables.min.css">
@endsection

@section('main')
<div class="content-wrapper pb-5">
    <section class="content-header">
        <div class="container-fluid">
            <div class="d-flex justify-content-between mb-2">
                <h1 class="m-0">Paid Cases</h1>
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
                            <th>Vendor Name</th>
                            <th>Case Code</th>
                            <th>Claim No</th>
                            <th>Name</th>
                            <th>Hospital</th>
                            <th>Corp</th>
                            <th>Paid Amount</th>
                            <th>Commission</th>
                            <th>Paid Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
            </div>
        </div>
    </section>
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
            const table = $('#casesTable').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: `{{ route('admin.paid_cases_ajax') }}?{!! $filter !!}`,
                    dataSrc: 'data'
                },
                columns: [{
                        data: 'id',
                        render: function(data, type, row) {
        return `<input type="checkbox" class="is_marked" data-id="${row.id}" ${row.is_marked == 1 ? 'checked' : ''}> ${data}`;
    },
                        searchable: true,
                        sortable: true
                    },
                    {
                        data: 'user_name',
                        searchable: true,
                        sortable: true
                    },
                    {
                        data: 'case_code',
                        searchable: true,
                        sortable: true
                    },
                    {
                        data: 'claim_no',
                        searchable: true,
                        sortable: true
                    },
                    {
                        data: 'name',
                        searchable: true,
                        sortable: true
                    },
                    {
                        data: 'hospital',
                        searchable: true,
                        sortable: true
                    },
                    {
                        data: 'corp',
                        searchable: true,
                        sortable: true
                    },
                    {
                        data: 'paid_amt',
                        searchable: true,
                        sortable: true
                    },
                    {
                        data: 'commission',
                        searchable: true,
                        sortable: true
                    },
                    {
                        data: 'paid_date',
                        render: function(data, type, row) {
                            return data ?
                                `${moment(data).format("DD-MMM-YYYY")}`:
                                '-';
                        },
                        searchable: true,
                        sortable: true
                    },
                    {
                        data: 'id',
                        render: function(data, type, row) {
                            return `<button class="btn btn-danger btn-del-case" data-id="${data}" data-encrypted-id="${row.encrypted_id}">Delete</button>`;
                        },
                    },
                ],
                order: [
                    [9, 'desc']
                ],
                responsive: true,
                paging: true,
                searching: true,
                lengthChange: true,
                autoWidth: false,
                rowCallback: function(row, data, index) {
                    if(data.is_marked == 1){
                        row.style.backgroundColor = '#FFB6C1';
                    }
                }
            });

            $(document).on('change', '.is_marked', function() {
    var caseId = $(this).data('id');
    var is_marked = $(this).is(':checked') ? 1 : 0;

    $.ajax({
        url: `{{ route('admin.update_vendor_paid_case') }}`,
        method: 'POST',
        data: {
            _token: '{{ csrf_token() }}',
            case_id: caseId,
            is_marked: is_marked
        },
        success: function(response) {
            location.reload();
        },
        error: function(xhr, status, error) {
        }
    });
});

            $(document).on('click', '.btn-del-case', function() {
                var caseId = $(this).data('id');
                var encryptedId = $(this).data('encrypted-id');
                var confirmDelete = window.confirm(
                    'Are you sure you want to delete this Vendor Paid Case? This action cannot be undone.');
                if (confirmDelete) {
                    window.location.href = '/admin/vendor_paid/cases/delete/' + encryptedId;
                }
            });
        });
</script>
@endsection
