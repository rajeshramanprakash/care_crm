@extends('admin.layouts.app')
@section('title', 'Corporate Accounts')

@section('content')
<div class="content-wrapper">
    <div class="container-fluid py-3">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title mb-0">Corporate Accounts</h3>
                <p class="text-muted small mb-0 mt-1">
                    All corporates created by brokers/insurers. <strong>Employees</strong> = count added by that corporate via their portal.
                    Total employees across listed accounts: <strong>{{ $totalEmployees }}</strong>
                </p>
            </div>
            <div class="card-body">
                @if(request('broker_id') || request('insurer_id'))
                    <div class="alert alert-info py-2 small">
                        Filtered by {{ request('broker_id') ? 'broker ID '.request('broker_id') : 'insurer ID '.request('insurer_id') }}.
                        <a href="{{ route('subadmin.corporate-accounts.index') }}">Show all</a>
                    </div>
                @endif
                <form method="GET" class="form-row mb-3">
                    @if(request('broker_id'))<input type="hidden" name="broker_id" value="{{ request('broker_id') }}">@endif
                    @if(request('insurer_id'))<input type="hidden" name="insurer_id" value="{{ request('insurer_id') }}">@endif
                    <div class="col-md-4 mb-2">
                        <input type="text" name="q" class="form-control form-control-sm" placeholder="Search corporate name or username" value="{{ request('q') }}">
                    </div>
                    <div class="col-md-3 mb-2">
                        <select name="owner_type" class="form-control form-control-sm">
                            <option value="">All (Broker + Insurer)</option>
                            <option value="broker" @selected(request('owner_type') === 'broker')>Created by Broker</option>
                            <option value="insurer" @selected(request('owner_type') === 'insurer')>Created by Insurer</option>
                        </select>
                    </div>
                    <div class="col-md-2 mb-2">
                        <button type="submit" class="btn btn-sm btn-primary btn-block">Filter</button>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table table-bordered table-hover table-sm mb-0">
                        <thead>
                            <tr>
                                <th>Corporate name</th>
                                <th>Username</th>
                                <th>Created by</th>
                                <th class="text-center">Employees added</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($items as $item)
                                <tr>
                                    <td><strong>{{ $item->corporate_name }}</strong></td>
                                    <td><code>{{ $item->username }}</code></td>
                                    <td class="small">{{ $item->createdByLabel() }}</td>
                                    <td class="text-center">
                                        @if($item->employees_count > 0)
                                            <a href="{{ route('subadmin.corporate-employees.index', ['corporate_id' => $item->id]) }}" class="btn btn-sm btn-outline-primary py-0 px-2" title="View employees">
                                                {{ $item->employees_count }}
                                            </a>
                                        @else
                                            <span class="text-muted">0</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($item->is_active)
                                            <span class="badge badge-success">Active</span>
                                        @else
                                            <span class="badge badge-secondary">Inactive</span>
                                        @endif
                                    </td>
                                    <td class="small">{{ $item->created_at?->format('d M Y') ?? '—' }}</td>
                                    <td>
                                        @if($item->employees_count > 0)
                                            <a href="{{ route('subadmin.corporate-employees.index', ['corporate_id' => $item->id]) }}" class="btn btn-sm btn-info">View employees</a>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="text-muted text-center">No corporate accounts yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="mt-3">{{ $items->links() }}</div>
            </div>
        </div>
    </div>
</div>
@endsection
