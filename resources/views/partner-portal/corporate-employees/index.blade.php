@php
    $layout = $routePrefix === 'broker' ? 'broker.layouts.app' : 'insurer.layouts.app';
    $modalPrefix = $routePrefix . 'EmployeeDetailModal';
@endphp
@extends($layout)
@section('title', 'Employees — '.$corporate->corporate_name)
@section('page_title', 'Employees: '.$corporate->corporate_name)

@section('content')
<p class="text-muted small mb-3">Employees added by this corporate. Click <strong>View</strong> for full details.</p>
<a href="{{ route($routePrefix.'.corporates.index') }}" class="btn btn-sm btn-secondary mb-3"><i class="fas fa-arrow-left mr-1"></i> Back to corporates</a>

@if($items->isEmpty())
    <p class="text-muted mb-0">No employees added yet by this corporate.</p>
@else
    <div class="table-responsive">
        <table class="table table-bordered table-hover table-sm mb-0">
            <thead>
                <tr>
                    <th>Employee ID</th>
                    <th>Name</th>
                    <th>Phone</th>
                    <th>Email</th>
                    <th>SI Limit</th>
                    <th>Active period</th>
                    <th>Policy</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($items as $item)
                    <tr>
                        <td><code>{{ $item->employee_id }}</code></td>
                        <td>{{ $item->employee_name }}</td>
                        <td>{{ $item->phone_number ?? '—' }}</td>
                        <td>{{ $item->email ?? '—' }}</td>
                        <td>{{ $item->si_limit ?? '—' }}</td>
                        <td class="small">
                            {{ $item->active_from?->format('d M Y') ?? '—' }}
                            –
                            {{ $item->active_to?->format('d M Y') ?? '—' }}
                        </td>
                        <td>
                            @if($item->policy_terms_file)
                                <a href="{{ $item->policyTermsUrl() }}" target="_blank" rel="noopener">File</a>
                            @else — @endif
                        </td>
                        <td>
                            @if($item->is_active)<span class="badge badge-success">Active</span>
                            @else<span class="badge badge-secondary">Inactive</span>@endif
                        </td>
                        <td class="text-nowrap">
                            <button type="button"
                                class="btn btn-sm btn-info"
                                data-toggle="modal"
                                data-target="#{{ $modalPrefix }}{{ $item->id }}">
                                View
                            </button>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif

@foreach($items as $item)
    @include('partials.corporate-employee-detail-modal', [
        'employee' => $item,
        'corporate' => $corporate,
        'modalIdPrefix' => $modalPrefix,
    ])
@endforeach
@endsection
