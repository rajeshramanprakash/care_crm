@extends('corporate.layouts.app')
@section('title', 'Employees')
@section('page_title', 'Employees')

@section('content')
<div class="mb-3 p-3 rounded border" style="background:#fff5eb;border-color:#fed7aa!important;">
    <strong><i class="fas fa-link mr-1"></i> Employee login URL</strong>
    <div class="input-group mt-2">
        <input type="text" class="form-control form-control-sm" readonly value="{{ $employee_login_url }}">
    </div>
    <small class="text-muted d-block mt-1">Employees sign in with <strong>corporate username</strong> ({{ $corporate->username }}), <strong>employee ID</strong>, and <strong>password</strong> you set.</small>
</div>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
    <h5 class="mb-0">Employee list</h5>
    <div>
        <a href="{{ route('corporate.employees.template') }}" class="btn btn-outline-secondary btn-sm"><i class="fas fa-download mr-1"></i> CSV template</a>
        <a href="{{ route('corporate.employees.create') }}" class="btn btn-primary btn-sm"><i class="fas fa-plus mr-1"></i> Add employee</a>
    </div>
</div>

<div class="card mb-4">
    <div class="card-body py-3">
        <h6 class="mb-2">Bulk upload (CSV)</h6>
        <p class="small text-muted mb-2">Upload employee data in bulk. <strong>Policy Terms &amp; Conditions</strong> must be added manually per employee after import (PDF, JPG, PNG).</p>
        <form method="POST" action="{{ route('corporate.employees.import') }}" enctype="multipart/form-data" class="form-inline flex-wrap gap-2">
            @csrf
            <input type="file" name="bulk_file" class="form-control form-control-sm" accept=".csv,.txt" required>
            <button type="submit" class="btn btn-sm btn-success"><i class="fas fa-upload mr-1"></i> Import CSV</button>
        </form>
    </div>
</div>

@if($items->isEmpty())
    <p class="text-muted mb-0">No employees yet.</p>
@else
    <div class="table-responsive">
        <table class="table table-bordered table-hover table-sm mb-0">
            <thead>
                <tr>
                    <th>Employee ID</th>
                    <th>Name</th>
                    <th>Phone</th>
                    <th>Policy</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($items as $item)
                    <tr>
                        <td><code>{{ $item->employee_id }}</code></td>
                        <td>{{ $item->employee_name }}</td>
                        <td>{{ $item->phone_number ?: '—' }}</td>
                        <td>
                            @if($item->policy_terms_file)
                                <a href="{{ $item->policyTermsUrl() }}" target="_blank" rel="noopener" class="badge badge-info">View</a>
                            @else
                                <span class="text-muted small">Not uploaded</span>
                            @endif
                        </td>
                        <td>
                            @if($item->is_active)<span class="badge badge-success">Active</span>
                            @else<span class="badge badge-secondary">Inactive</span>@endif
                        </td>
                        <td class="text-nowrap">
                            <a href="{{ route('corporate.employees.edit', $item) }}" class="btn btn-sm btn-info"><i class="fas fa-edit"></i></a>
                            <form action="{{ route('corporate.employees.destroy', $item) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this employee?');">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
@endsection
