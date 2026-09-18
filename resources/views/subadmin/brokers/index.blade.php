@extends('admin.layouts.app')
@section('title', 'Broker Accounts')

@section('content')
<div class="content-wrapper">
    <div class="container-fluid py-3">
        <div class="card">
            <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
                <div>
                    <h3 class="card-title mb-0">Broker Accounts</h3>
                    <p class="text-muted small mb-0 mt-1">Create login credentials for brokers. They sign in at <code>/broker/login</code>.</p>
                </div>
                @can('create_broker')
                <a href="{{ route('subadmin.brokers.create') }}" class="btn btn-primary btn-sm">
                    <i class="fas fa-plus mr-1"></i> Add New Broker
                </a>
                @endcan
            </div>
            <div class="card-body">
                @if(session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif

                @if($items->isEmpty())
                    <p class="text-muted mb-0">No broker accounts yet.</p>
                @else
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Company</th>
                                    <th>Username</th>
                                    <th>Email</th>
                                    <th>Mobile</th>
                                    <th>Documents</th>
                                    <th>Corporate IDs</th>
                                    <th>Total Employees</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($items as $item)
                                    <tr>
                                        <td><strong>{{ $item->name }}</strong></td>
                                        <td>{{ $item->company_name ?: '—' }}</td>
                                        <td><code>{{ $item->username }}</code></td>
                                        <td>{{ $item->email ?: '—' }}</td>
                                        <td>{{ $item->mobile ?: '—' }}</td>
                                        <td class="small">
                                            @if($item->mou_file)<span class="badge badge-info mr-1">MOU</span>@endif
                                            @if(is_array($item->company_documents) && count($item->company_documents))
                                                <span class="badge badge-secondary">{{ count($item->company_documents) }} doc(s)</span>
                                            @endif
                                            @if(!$item->mou_file && empty($item->company_documents))—@endif
                                        </td>
                                        <td class="text-center">
                                            @if($item->corporate_users_count > 0)
                                                <button type="button"
                                                    class="btn btn-sm btn-outline-primary py-0 px-2 js-partner-corporate-count"
                                                    data-partner-type="broker"
                                                    data-partner-id="{{ $item->id }}"
                                                    title="View corporate accounts created by this broker">
                                                    {{ $item->corporate_users_count }}
                                                </button>
                                            @else
                                                <span class="text-muted">0</span>
                                            @endif
                                        </td>
                                        @php $brokerEmployeeTotal = $item->corporateUsers->sum('employees_count'); @endphp
                                        <td class="text-center">
                                            @if($brokerEmployeeTotal > 0)
                                                <a href="{{ route('subadmin.corporate-accounts.index', ['broker_id' => $item->id]) }}" class="btn btn-sm btn-outline-success py-0 px-2" title="View corporates & employees for this broker">{{ $brokerEmployeeTotal }}</a>
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
                                        <td class="text-nowrap">
                                            @can('edit_broker')
                                            <a href="{{ route('subadmin.brokers.edit', $item) }}" class="btn btn-sm btn-info" title="Edit"><i class="fas fa-edit"></i></a>
                                            @endcan
                                            @can('delete_broker')
                                            <form action="{{ route('subadmin.brokers.destroy', $item) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this broker account?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-danger" title="Delete"><i class="fas fa-trash"></i></button>
                                            </form>
                                            @endcan
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

@include('admin.partials.partner-corporate-accounts-admin', [
    'items' => $items,
    'partner_type' => $partner_type ?? 'broker',
    'partner_label' => $partner_label ?? 'Broker',
])
@endsection
