@extends('admin.layouts.app')
@section('title', 'Corporate Employees')

@section('content')
<div class="content-wrapper">
    <div class="container-fluid py-3">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title mb-0">Corporate Employees</h3>
                <p class="text-muted small mb-0 mt-1">All employees added by corporates (via broker/insurer-created corporate accounts).</p>
            </div>
            <div class="card-body">
                <form method="GET" class="form-row mb-3">
                    <div class="col-md-4 mb-2">
                        <input type="text" name="q" class="form-control form-control-sm" placeholder="Search ID, name, email, phone" value="{{ request('q') }}">
                    </div>
                    <div class="col-md-3 mb-2">
                        <input type="number" name="corporate_id" class="form-control form-control-sm" placeholder="Corporate ID" value="{{ request('corporate_id') }}">
                    </div>
                    <div class="col-md-2 mb-2">
                        <button type="submit" class="btn btn-sm btn-primary btn-block">Filter</button>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table table-bordered table-hover table-sm mb-0">
                        <thead>
                            <tr>
                                <th>Employee ID</th>
                                <th>Name</th>
                                <th>Corporate</th>
                                <th>Created by</th>
                                <th>Phone</th>
                                <th>Status</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($items as $item)
                                <tr>
                                    <td><code>{{ $item->employee_id }}</code></td>
                                    <td>{{ $item->employee_name }}</td>
                                    <td>{{ $item->corporate->corporate_name ?? '—' }}</td>
                                    <td class="small">{{ $item->corporate?->createdByLabel() ?? '—' }}</td>
                                    <td>{{ $item->phone_number ?? '—' }}</td>
                                    <td>
                                        @if($item->is_active)<span class="badge badge-success">Active</span>
                                        @else<span class="badge badge-secondary">Inactive</span>@endif
                                    </td>
                                    <td>
                                        <button type="button"
                                            class="btn btn-sm btn-info"
                                            data-toggle="modal"
                                            data-target="#adminEmployeeModal{{ $item->id }}"
                                            data-bs-toggle="modal"
                                            data-bs-target="#adminEmployeeModal{{ $item->id }}">
                                            View
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="text-muted text-center">No employees found.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="mt-3">{{ $items->links() }}</div>
            </div>
        </div>
    </div>
</div>

@foreach($items as $item)
    @include('admin.partials.corporate-employee-detail-modal', ['employee' => $item])
@endforeach
@endsection
