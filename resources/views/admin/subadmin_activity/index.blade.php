@extends('admin.layouts.app')

@section('header-css')
    <style>
        .activity-badge { border-radius: 8px; font-size: 0.75rem; font-weight: 600; padding: 3px 8px; border: 1.5px solid; display: inline-block; }
        .activity-create { color: #198754; border-color: #198754; }
        .activity-update { color: #0d6efd; border-color: #0d6efd; }
        .activity-delete { color: #dc3545; border-color: #dc3545; }
        .activity-other { color: #6c757d; border-color: #6c757d; }
        .activity-url { font-size: 0.75rem; color: #888; word-break: break-all; }
    </style>
@endsection

@section('content')
<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Sub Admin Activity</h1>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <div class="card">
                <div class="card-body">
                    <form method="GET" action="{{ route('admin.subadmin_activity.index') }}" class="row g-2 mb-3">
                        <div class="col-md-2">
                            <select name="user_id" class="form-control">
                                <option value="">All Sub Admins</option>
                                @foreach($subAdmins as $subAdmin)
                                    <option value="{{ $subAdmin->user_id }}" @selected(request('user_id') == $subAdmin->user_id)>{{ $subAdmin->user_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select name="module" class="form-control">
                                <option value="">All Modules</option>
                                @foreach($modules as $module)
                                    <option value="{{ $module }}" @selected(request('module') === $module)>{{ $module }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select name="action" class="form-control">
                                <option value="">All Actions</option>
                                @foreach($actions as $value => $label)
                                    <option value="{{ $value }}" @selected(request('action') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <input type="date" name="from" value="{{ request('from') }}" class="form-control" title="From">
                        </div>
                        <div class="col-md-2">
                            <input type="date" name="to" value="{{ request('to') }}" class="form-control" title="To">
                        </div>
                        <div class="col-md-2 d-flex gap-2">
                            <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Search">
                        </div>
                        <div class="col-12 d-flex gap-2">
                            <button type="submit" class="btn btn-primary btn-sm">Filter</button>
                            <a href="{{ route('admin.subadmin_activity.index') }}" class="btn btn-secondary btn-sm">Reset</a>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-bordered table-hover align-middle">
                            <thead>
                                <tr>
                                    <th style="width: 150px;">Date / Time</th>
                                    <th>Sub Admin</th>
                                    <th>Action</th>
                                    <th>Module</th>
                                    <th>Description</th>
                                    <th>Status</th>
                                    <th>IP</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($logs as $log)
                                    <tr>
                                        <td>{{ $log->created_at?->format('d M Y, h:i A') }}</td>
                                        <td>{{ $log->user_name }} <small class="text-muted">#{{ $log->user_id }}</small></td>
                                        <td><span class="activity-badge activity-{{ $log->action }}">{{ ucfirst($log->action) }}</span></td>
                                        <td>{{ $log->module }}</td>
                                        <td>
                                            {{ $log->description }}
                                            <div class="activity-url">{{ $log->method }} {{ $log->url }}</div>
                                        </td>
                                        <td>{{ $log->status_code }}</td>
                                        <td>{{ $log->ip_address }}</td>
                                        <td><a href="{{ route('admin.subadmin_activity.show', $log) }}" class="btn btn-sm btn-outline-primary">Details</a></td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center text-muted">Abhi koi activity nahi mili.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{ $logs->links('pagination::bootstrap-4') }}
                </div>
            </div>
        </div>
    </section>
</div>
@endsection
