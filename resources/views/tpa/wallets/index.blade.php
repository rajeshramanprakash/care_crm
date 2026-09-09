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
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createWalletModal">Create New</button>
                </div>
            </div>
        </section>

        <section class="content">
            <div class="container-fluid">
                <div class="table-responsive">
                    <table id="walletsTable" class="table text-sm">
                        <thead class="sticky_head bg-light">
                            <tr>
                                <th>ID</th>
                                <th>Amount</th>
                                <th>Payment Type</th>
                                <th>Payment Proof</th>
                                <th>Message</th>
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
    <script>
        $(document).ready(function() {
            const table = $('#walletsTable').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: `{{ route('tpa.wallets.ajax') }}`,
                    dataSrc: 'data'
                },
                columns: [
                    { data: 'id', name: 'id' },
                    { data: 'ammount', name: 'amount' },
                    { data: 'payment_type', name: 'payment_type' },
                    { data: 'payment_proof', name: 'payment_proof', render: function(data) {
                        return `<a href="/storage/${data}" target="_blank">View Proof</a>`;
                    }},
                    { data: 'msg', name: 'msg' },
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
        });
    </script>
@endsection
