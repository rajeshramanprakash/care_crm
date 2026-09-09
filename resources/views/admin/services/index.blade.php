@extends('admin.layouts.app')

@section('content')
<div class="content-wrapper dcs-page">

<div class="container-fluid py-3">
    <div class="card dcs-table-card">
        <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
            <h3 class="card-title mb-0">Services</h3>
            <a href="{{ route('admin.services.create') }}" class="btn btn-light btn-sm font-weight-bold">
                <i class="fas fa-plus mr-1"></i> Add New Service
            </a>
        </div>
        <div class="card-body">
            @if(session('success'))
                <div class="alert alert-success">
                    {{ session('success') }}
                </div>
            @endif

            @if($services->isEmpty())
                <div class="dcs-empty">
                    <div><i class="fas fa-concierge-bell d-block"></i></div>
                    <p class="mb-0">No services yet. Use <strong>Add New Service</strong> to create one.</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-bordered table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Order</th>
                                <th>Icon</th>
                                <th>Name</th>
                                <th>Duration</th>
                                <th>Tags</th>
                                <th>Sub-services</th>
                                <th>Description</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($services as $service)
                                @php
                                    $tags = is_array($service->specialization_options) ? $service->specialization_options : [];
                                @endphp
                                <tr>
                                    <td>{{ $service->sort_order ?? 0 }}</td>
                                    <td>
                                        @if($service->icon_path)
                                            <img src="{{ asset('storage/' . ltrim(str_replace('\\', '/', $service->icon_path), '/')) }}" alt="" width="40" height="40" style="object-fit:contain;border-radius:8px;border:1px solid #e9ecef;padding:4px;background:#fff;">
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td><strong>{{ $service->name }}</strong></td>
                                    <td>{{ (int) ($service->consultation_duration_minutes ?? 30) }} min</td>
                                    <td>
                                        @if(count($tags) > 0)
                                            @foreach(array_slice($tags, 0, 3) as $tag)
                                                <span class="badge badge-light border mr-1">{{ $tag }}</span>
                                            @endforeach
                                            @if(count($tags) > 3)
                                                <span class="small text-muted">+{{ count($tags) - 3 }}</span>
                                            @endif
                                        @else
                                            <span class="text-muted small">On sub-services</span>
                                        @endif
                                    </td>
                                    <td>{{ (int) ($service->sub_services_count ?? 0) }}</td>
                                    <td>{{ $service->description ? Str::limit($service->description, 40) : '—' }}</td>
                                    <td class="text-nowrap">
                                        <a href="{{ route('admin.services.edit', $service) }}" class="btn btn-link p-0 m-0" title="Edit">
                                            <i class="fas fa-edit text-primary" style="font-size: 18px;"></i>
                                        </a>
                                        <form action="{{ route('admin.services.destroy', $service) }}" method="POST" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-link p-0 m-0" onclick="return confirm('Are you sure you want to delete this service?')" title="Delete">
                                                <i class="fas fa-trash-alt text-danger" style="font-size: 18px;"></i>
                                            </button>
                                        </form>
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
@endsection

@section('header-css')
    @include('admin.doctor_consultation_services.partials.styles')
@endsection
