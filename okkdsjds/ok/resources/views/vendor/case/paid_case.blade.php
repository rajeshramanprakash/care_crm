@extends('vendor.layouts.app')

@section('title', 'Paid Cases')


@section('navbar-right-links')
<li class="nav-item">
    <button type="button" class="btn btn-primary px-3 mx-2 py-1" style="padding: 3px 0; margin-top:2px; background-color: #ffffff50; border: 1px solid #ffffff80; border-radius: 30px; ">Total Commission <b>₹{{ $total_commission ?? '0' }}</b></button>
</li>
@endsection
@section('header-css')
    <link rel="stylesheet" href="//cdn.datatables.net/1.13.1/css/jquery.dataTables.min.css">
    <script src="//cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.4/moment.min.js"></script>
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
                                <th>Case Code</th>
                                <th>Claim No</th>
                                <th>Name</th>
                                <th>Corp</th>
                                <th>Paid  Amount</th>
                                <th>Commission</th>
                                <th>Paid Date</th>
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
                    url: `{{ route('vendor.paid_cases_ajax') }}?{!! $filter !!}`,
                    dataSrc: 'data'
                },
                columns: [{
                        data: 'id',
                        render: function(data, type, row) {
        return `<input type="checkbox" class="is_marked" data-id="${row.id}" ${row.is_marked_vendor == 1 ? 'checked' : ''}> ${data}`;
    },
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

                ],
                order: [
                    [7, 'desc']
                ],
                responsive: true,
                paging: true,
                searching: true,
                lengthChange: true,
                autoWidth: false,
                rowCallback: function(row, data, index) {
                    if(data.is_marked_vendor == 1){
                        row.style.backgroundColor = '#FFB6C1';
                    }
                }
            });
            $(document).on('click', '.btn-view-case', function() {
                var caseId = $(this).data('id');
                window.location.href = '/tpa/cases/view/' + caseId;
            });
        });

        $(document).on('change', '.is_marked', function() {
    var caseId = $(this).data('id');
    var is_marked = $(this).is(':checked') ? 1 : 0;

    $.ajax({
        url: `{{ route('vendor.update_vendor_paid_case') }}`,
        method: 'POST',
        data: {
            _token: '{{ csrf_token() }}',
            case_id: caseId,
            is_marked_vendor: is_marked
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
