@extends('manager.layouts.app')

@section('header-css')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.1/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.4.0/css/responsive.dataTables.min.css">
    <style>
        .badge, .status-badge {
            border-radius: 8px !important;
            font-size: 0.7rem !important;
            font-weight: 500;
            padding: 4px 5px !important;
            border: 1.5px solid transparent;
            background: none !important;
            display: inline-block;
            text-align: center;
            vertical-align: middle;
        }
        .custom-table-scroll-x {
            width: 100%;
            overflow-x: auto;
        }
        .custom-table-scroll-x table {
            min-width: 1100px;
            width: 100%;
            table-layout: fixed;
        }
    </style>
@endsection

@section('content')
<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Duty Logs</h1>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">All Duty Activities</h3>
                        </div>
                        <div class="card-body">
                            <div class="custom-table-scroll-x">
                                <table class="table table-bordered table-striped" id="duty-logs-table">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>User</th>
                                            <th>Status</th>
                                            <th>Reason</th>
                                            <th>Start Time</th>
                                            <th>End Time</th>
                                            <th>Duration</th>
                                            <th>Created At</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($dutyLogs as $log)
                                        <tr>
                                            <td>{{ $log->id }}</td>
                                            <td>
                                                <a href="{{ route('manager.duty_logs.user', $log->user_id) }}">
                                                    {{ $log->user->f_name }} {{ $log->user->l_name }}
                                                </a>
                                            </td>
                                            <td>
                                                <span class="badge {{ $log->break_status == 'active' ? 'bg-success' : 'bg-warning' }}">
                                                    {{ ucfirst($log->break_status) }}
                                                </span>
                                            </td>
                                            <td>{{ $log->break_reason ?? 'N/A' }}</td>
                                            <td>{{ $log->break_start_time ? $log->break_start_time->format('Y-m-d H:i:s') : 'N/A' }}</td>
                                            <td>{{ $log->break_end_time ? $log->break_end_time->format('Y-m-d H:i:s') : 'N/A' }}</td>
                                            <td>
                                                @if($log->break_start_time && $log->break_end_time)
                                                    {{ $log->break_start_time->diffForHumans($log->break_end_time, true) }}
                                                @elseif($log->break_start_time && $log->break_status == 'active')
                                                    <span class="text-success">Active</span>
                                                @else
                                                    N/A
                                                @endif
                                            </td>
                                            <td>{{ $log->created_at->format('Y-m-d H:i:s') }}</td>
                                        </tr>
                                        @endforeach
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
@endsection

@section('footer-script')
    <script src="https://cdn.datatables.net/1.13.1/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.4.0/js/dataTables.responsive.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#duty-logs-table').DataTable({
                responsive: true,
                order: [[0, 'desc']],
                pageLength: 25,
                scrollY: '50vh',
                paging: true
            });
        });
    </script>
@endsection
