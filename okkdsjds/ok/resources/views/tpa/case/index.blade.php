@extends('tpa.layouts.app')

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
                                <th>Claim No</th>
                                <th>Name</th>
                                <th>Hospital</th>
                                <th>Corp</th>
                                <th>Paid  Amount</th>
                                <th>Approved Type</th>
                                <th>Commission</th>
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
                    url: `{{ route('tpa_case_ajax') }}?{!! $filter !!}`,
                    dataSrc: 'data'
                },
                columns: [{
                        data: 'id',
                        searchable: true,
                        sortable: false
                    },
                    {
                        data: 'claim_no',
                        searchable: true,
                        sortable: false
                    },
                    {
                        data: 'name',
                        searchable: true,
                        sortable: false
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
                        data: 'paid_amt',
                        searchable: true,
                        sortable: false
                    },
                    {
                        data: 'approved_type',
                        searchable: true,
                        sortable: false
                    },
                    {
                        data: 'commission',
                        searchable: true,
                        sortable: false
                    },

                ],
                order: [
                    [0, 'desc']
                ],
                responsive: true,
                paging: true,
                searching: true,
                lengthChange: true,
                autoWidth: false
            });
            $(document).on('click', '.btn-view-case', function() {
                var caseId = $(this).data('id'); // Get case ID from data attribute
                var encryptedId = $(this).data('encrypted-id');
                window.location.href = '/tpa/cases/view/' + encryptedId; // Redirect to case show page
            });
        });
    </script>
@endsection
