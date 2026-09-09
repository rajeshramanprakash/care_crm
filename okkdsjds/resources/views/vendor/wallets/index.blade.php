@extends('vendor.layouts.app')
@php
        $auth_user = Auth::guard('Vendor')->user();
        $wallet_balance = $total_paid-$total_commission;
@endphp
@section('title', $page_heading)
@section('navbar-right-links')
<li class="nav-item">
    <button type="button" class="btn btn-primary"
        style="padding: 3px 0; margin-top:2px; background-color: #ffffff50; border: 1px solid #ffffff80; border-radius: 30px; ">&nbsp;&nbsp;&nbsp;&nbsp;
        <svg fill="#fff" height="25px" width="25px" version="1.1" id="Layer_1"
            xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"
            viewBox="0 0 458.531 458.531" xml:space="preserve">
            <g id="XMLID_830_">
                <g>
                    <g>
                        <path d="M336.688,343.962L336.688,343.962c-21.972-0.001-39.848-17.876-39.848-39.848v-66.176
c0-21.972,17.876-39.847,39.848-39.847h103.83c0.629,0,1.254,0.019,1.876,0.047v-65.922c0-16.969-13.756-30.725-30.725-30.725
H30.726C13.756,101.49,0,115.246,0,132.215v277.621c0,16.969,13.756,30.726,30.726,30.726h380.943
c16.969,0,30.725-13.756,30.725-30.726v-65.922c-0.622,0.029-1.247,0.048-1.876,0.048H336.688z" />
                        <path d="M440.518,219.925h-103.83c-9.948,0-18.013,8.065-18.013,18.013v66.176c0,9.948,8.065,18.013,18.013,18.013h103.83
c9.948,0,18.013-8.064,18.013-18.013v-66.176C458.531,227.989,450.466,219.925,440.518,219.925z M372.466,297.024
c-14.359,0-25.999-11.64-25.999-25.999s11.64-25.999,25.999-25.999c14.359,0,25.999,11.64,25.999,25.999
C398.465,285.384,386.825,297.024,372.466,297.024z" />
                        <path
                            d="M358.169,45.209c-6.874-20.806-29.313-32.1-50.118-25.226L151.958,71.552h214.914L358.169,45.209z" />
                    </g>
                </g>
            </g>
        </svg>
        &nbsp;&nbsp;<b style="color: {{ ($wallet_balance ?? 0) < 0 ? 'red' : 'green' }};">
            ₹{{ $wallet_balance ?? '0' }}
        </b>
        &nbsp;&nbsp;&nbsp;&nbsp;
    </button>
</li>
<li class="nav-item">
    <button type="button" class="btn btn-primary px-3 mx-2 py-1" style="padding: 3px 0; margin-top:2px; background-color: #ffffff50; border: 1px solid #ffffff80; border-radius: 30px; ">Total Transfer <b>₹{{ $total_paid ?? '0' }}</b></button>
</li>
@endsection
@section('header-css')
    <link rel="stylesheet" href="//cdn.datatables.net/1.13.1/css/jquery.dataTables.min.css">
@endsection

@section('main')
    <div class="content-wrapper pb-5">
        <section class="content-header">
            <div class="container-fluid">
                <div class="d-flex justify-content-between mb-2">
                    <h1 class="m-0">{{ $page_heading }}</h1>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createWalletModal">Create
                        New</button>
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
                                <th>User</th>
                                <th>Paid By</th>
                                <th>Amount</th>
                                <th>Payment Type</th>
                                <th>Payment Proof</th>
                                <th>Message</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <div class="modal fade" id="createWalletModal" tabindex="-1" aria-labelledby="createWalletModalLabel">
            <div class="modal-dialog modal-lg">
                <form id="walletForm">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="createWalletModalLabel">Create Wallet Entry</h5>
                            <a class="btn btn-close txt-black" data-bs-dismiss="modal" aria-label="Close">
                                <i class="fas fa-times"></i>
                            </a>
                        </div>
                        <div class="modal-body row">
                            <div class="form-group col-md-6">
                                <label for="amount">Amount</label>
                                <input type="number" name="amount" class="form-control" required>
                            </div>
                            <div class="form-group col-md-6">
                                <label for="payment_type">Payment Type</label>
                                <input type="text" name="payment_type" class="form-control" required>
                            </div>
                            <div class="form-group col-md-6">
                                <label for="payment_proof">Payment Proof</label>
                                <input type="file" name="payment_proof" class="form-control" required>
                            </div>
                            <div class="form-group col-md-6">
                                <label for="msg">Message</label>
                                <textarea name="msg" class="form-control"></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="submit" class="btn btn-primary">Create</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
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
                    url: `{{ route('vendor.wallets.ajax') }}`,
                    dataSrc: 'data'
                },
                columns: [{
                        data: 'id',
                        name: 'id'
                    },
                    {
                        data: 'user.f_name',
                        name: 'user.f_name'
                    },
                    {
                        data: 'paid_by_name',
                        name: 'paid_by_name'
                    },
                    {
                        data: 'ammount',
                        name: 'amount'
                    },
                    {
                        data: 'payment_type',
                        name: 'payment_type'
                    },
                    {
                        data: 'payment_proof',
                        name: 'payment_proof',
                        render: function(data) {
                            return `<a href="/storage/${data}" target="_blank">View Proof</a>`;
                        }
                    },
                    {
                        data: 'msg',
                        name: 'msg'
                    },
                    {
                        data: 'is_approved',
                        name: 'is_approved',
                        render: function(data, type, row) {
                            if (data === 0) {
                                return '<button class="btn btn-warning btn-sm">Pending</button>';
                            } else if (data === 1) {
                                return '<button class="btn btn-success btn-sm">Approved</button>';
                            } else if (data === 2) {
                                return '<button class="btn btn-danger btn-sm">Rejected</button>';
                            } else {
                                return '<button class="btn btn-secondary btn-sm">Unknown</button>';
                            }
                        }
                    }
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

            $('#walletForm').submit(function(e) {
                e.preventDefault();
                const formData = new FormData(this);

                $.ajax({
                    url: `{{ route('vendor.wallets.store') }}`,
                    method: 'POST',
                    data: formData,
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        if (response.success) {
                            $('#createWalletModal').modal('hide');
                            table.ajax.reload();
                            alert(response.message);
                        }
                    }
                });
            });
        });
    </script>
@endsection
