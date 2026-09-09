@extends('vendor.layouts.app')

@section('title', 'My Tickets')

@section('header-css')
    <link rel="stylesheet" href="//cdn.datatables.net/1.13.1/css/jquery.dataTables.min.css">
@endsection


@section('main')
<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>My Tickets</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('vendor.dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item active">Tickets</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <!-- Statistics Cards -->
            <div class="row">
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-info">
                        <div class="inner">
                            <h3 id="total-tickets">0</h3>
                            <p>Total Tickets</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-ticket-alt"></i>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-success">
                        <div class="inner">
                            <h3 id="active-tickets">0</h3>
                            <p>Active Tickets</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-warning">
                        <div class="inner">
                            <h3 id="pending-tickets">0</h3>
                            <p>Pending Approval</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-clock"></i>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-danger">
                        <div class="inner">
                            <h3 id="unread-messages">0</h3>
                            <p>Unread Messages</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-envelope"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filter Tabs -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <ul class="nav nav-tabs card-header-tabs">
                                <li class="nav-item">
                                    <a class="nav-link {{ $status === 'all' ? 'active' : '' }}" href="{{ route('vendor.tickets.index', ['status' => 'all']) }}">
                                        All Tickets
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link {{ $status === 'active' ? 'active' : '' }}" href="{{ route('vendor.tickets.index', ['status' => 'active']) }}">
                                        Active
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link {{ $status === 'closed' ? 'active' : '' }}" href="{{ route('vendor.tickets.index', ['status' => 'closed']) }}">
                                        Closed
                                    </a>
                                </li>
                            </ul>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table id="tickets-table" class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th>Ticket #</th>
                                            <th>Case Code</th>
                                            <th>Claim No</th>
                                            <th>Patient Name</th>
                                            <th>Case Type</th>
                                            <th>Status</th>
                                            <th>Created</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <!-- Data will be loaded via AJAX -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>


<script src="//cdn.datatables.net/1.13.1/js/jquery.dataTables.min.js"></script>


<script>
$(document).ready(function() {
    // Initialize DataTable
    var table = $('#tickets-table').DataTable({
        processing: true,
        serverSide: false,
        ajax: {
            url: '{{ route("vendor.tickets.ajax") }}',
            data: function(d) {
                d.status = '{{ $status }}';
            }
        },
        columns: [
            {data: 'ticket_number', name: 'ticket_number'},
            {data: 'case_code', name: 'case_code'},
            {data: 'claim_no', name: 'claim_no'},
            {data: 'patient_name', name: 'patient_name'},
            {data: 'case_type', name: 'case_type'},
            {data: 'status', name: 'status'},
            {data: 'created_at', name: 'created_at'},
            {data: 'actions', name: 'actions', orderable: false, searchable: false}
        ],
        order: [[6, 'desc'], [7, 'desc']]
    });

    // Load statistics
    loadStatistics();

    function loadStatistics() {
        $.get('{{ route("vendor.tickets.unread-count") }}', function(data) {
            $('#unread-messages').text(data.unread_count || 0);
        });

        // Load ticket counts
        $.get('{{ route("vendor.tickets.ajax") }}', {status: '{{ $status }}'}, function(data) {
            $('#total-tickets').text(data.data.length);
            $('#active-tickets').text(data.data.filter(function(ticket) { return ticket.is_active === 'Active'; }).length);
            $('#pending-tickets').text(data.data.filter(function(ticket) { return ticket.status === 'pending'; }).length);
        });
    }

    // Refresh data every 30 seconds
    setInterval(function() {
        table.ajax.reload(null, false);
        loadStatistics();
    }, 30000);
});
</script>
@endsection
