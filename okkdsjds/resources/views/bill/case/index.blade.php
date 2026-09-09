@extends('bill.layouts.app')
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
                            <th>Vendor</th>
                            <th>Name</th>
                            <th>Age</th>
                            <th>Hospital</th>
                            <th>Corp</th>
                            <th>Diagnosis</th>
                            <th>Bill Range</th>
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
                    url: `{{ route('bill_case_ajax') }}?{!! $filter !!}`,
                    dataSrc: 'data'
                },
                columns: [
                    {
                        data: 'id',
                        render: function(data, type, row) {
        return `<input type="checkbox" class="working-checkbox" data-id="${row.id}" ${row.is_working_row ? 'checked' : ''}> ${data}`;
    },
                        searchable: true,
                        sortable: false
                    },
                    {
                        data: 'is_priority',
                        visible: false
                    },
                    {
                        data: 'case_code',
                        searchable: true,
                        sortable: false
                    },
                    {
                        data: 'created_by_name',
                        searchable: true,
                        sortable: false
                    },
                    {
                        data: 'name',
                        searchable: true,
                        sortable: false
                    },
                    {
                        data: 'age',
                        render: function(data, type, row) {
                            return data ? `${data}--${row.gender}` : ``;
                            },
                        searchable: true,
                        sortable: false,

                    },
                    {
                        data: 'hospital',
                        searchable: true,
                        sortable: false
                    },
                    {
                        data: 'corp',
                        searchable: true,
                        sortable: false
                    },
                    {
                        data: 'diagnosis',
                        searchable: true,
                        sortable: false
                    },
                    {
                        data: 'bill_range',
                        searchable: true,
                        sortable: false
                    },
                    {
                        data: 'doa',
                        render: function(data, type, row) {
                            return data ?
                                `${moment(data).format("DD-MMM-YYYY")} at ${moment(row.doa_time, "HH:mm:ss").format("hh:mm A")}` :
                                '-';
                        },
                        searchable: true,
                        sortable: false
                    },
                    {
                        data: 'dod',
                        render: function(data, type, row) {
                            return data ?
                                `${moment(data).format("DD-MMM-YYYY")} at ${moment(row.dod_time, "HH:mm:ss").format("hh:mm A")}` :
                                '-';
                        },
                        searchable: true,
                        sortable: false
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
                }
            });
            $(document).on('click', '.btn-view-case', function() {
                var caseId = $(this).data('id');
                var encryptedId = $(this).data('encrypted-id');
                window.location.href = '/billing/cases/view/' + encryptedId;
            });
        });

        $(document).on('change', '.working-checkbox', function() {
    var caseId = $(this).data('id');
    var isWorking = $(this).is(':checked') ? 1 : 0;

    $.ajax({
        url: `{{ route('update_working_status') }}`,
        method: 'POST',
        data: {
            _token: '{{ csrf_token() }}',
            case_id: caseId,
            is_working: isWorking
        },
        success: function(response) {
            location.reload();
        },
        error: function(xhr, status, error) {
        }
    });
});

</script>
@endsection
