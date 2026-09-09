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
                    <div class="row">
                        <button class="btn btn-warning mx-2" data-bs-toggle="modal" data-bs-target="#TpaAmountModal">TPA Amount</button>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createWalletModal">Create
                            New</button>
                    </div>
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
                                <th>Action</th>
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
                                <label for="user_id">User</label>
                                <select name="user_id" class="form-control" required>
                                    @foreach ($users as $user)
                                        <option value="{{ $user->id }}"> {{ $user->f_name }} {{ $user->l_name }} ---
                                            {{ $user->role_id == 8 ? 'Tpa' : ($user->role_id == 10 ? 'Vendor' : $user->role_id) }}
                                            --- {{ $user->wallet ?? '0' }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
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
        <div class="modal fade" id="TpaAmountModal" tabindex="-1" aria-labelledby="TpaAmountModalLabel">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="TpaAmountModalLabel">TPA Users List</h5>
                        <a class="btn btn-close txt-black" data-bs-dismiss="modal" aria-label="Close">
                            <i class="fas fa-times"></i>
                        </a>
                    </div>
                    <div class="modal-body">
                        <ul class="list-group">
                            @foreach ($users as $user)
                                <li class="list-group-item">
                                    <div class="row">
                                        <div class="col-6">
                                            {{ $user->f_name }} {{ $user->l_name }}
                                        </div>
                                        <div class="col-6">
                                         Wallet Balance: ₹ {{ $user->wallet ?? '0' }}
                                        </div>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
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
            const table = $('#walletsTable').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: `{{ route('admin.wallets.ajax') }}`,
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
                        data: 'id',
                        name: 'action',
                        orderable: false,
                        searchable: false,
                        render: function(data, type, row) {
                            return `<a href="{{ route('admin.wallets.delete', '') }}/${data}" class="btn btn-danger btn-sm">Delete</a>`;
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
                    url: `{{ route('admin.wallets.store') }}`,
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
