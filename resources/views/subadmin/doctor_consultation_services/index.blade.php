@extends('admin.layouts.app')

@section('title', 'Doctor consultation services')

@section('header-css')
    @include('admin.doctor_consultation_services.partials.styles')
@endsection

@section('content')
<div class="content-wrapper dcs-page">
    <div class="container-fluid py-3">
        <div class="card dcs-table-card">
            <div class="card-header d-flex flex-wrap justify-content-between align-items-center">
                <div>
                    <h3 class="card-title mb-0">Doctor consultation services</h3>
                    <p class="mb-0 mt-1 small" style="opacity: 0.9;">Website consultation page &amp; doctor registration</p>
                </div>
                <a href="{{ route('subadmin.doctor_consultation_services.create') }}" class="btn btn-light btn-sm font-weight-bold">
                    <i class="fas fa-plus mr-1"></i> Add service
                </a>
            </div>
            <div class="card-body">
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm" role="alert">
                        <i class="fas fa-check-circle mr-1"></i> {{ session('success') }}
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                @endif

                @if($items->isEmpty())
                    <div class="dcs-empty">
                        <div><i class="fas fa-stethoscope d-block"></i></div>
                        <p class="mb-2 font-weight-bold text-dark">No consultation services yet</p>
                        <p class="small mb-3">Add a service to enable doctor registration and the public booking page.</p>
                        <a href="{{ route('subadmin.doctor_consultation_services.create') }}" class="btn btn-dcs-primary">
                            <i class="fas fa-plus mr-1"></i> Create first service
                        </a>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th class="text-center" style="width: 56px;">#</th>
                                    <th>Service name</th>
                                    <th class="text-center">Sub-services</th>
                                    <th>Tags</th>
                                    <th class="text-center">Duration</th>
                                    <th class="text-center">Order</th>
                                    <th class="text-center">Status</th>
                                    <th class="text-center" style="width: 100px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($items as $row)
                                    <tr>
                                        <td class="text-center text-muted">{{ $row->id }}</td>
                                        <td>
                                            <span class="font-weight-bold" style="color: var(--dcs-heading);">{{ $row->name }}</span>
                                        </td>
                                        <td class="text-center">
                                            @if(($row->sub_services_count ?? 0) > 0)
                                                <span class="badge badge-light border">{{ $row->sub_services_count }}</span>
                                            @else
                                                <span class="text-muted small">—</span>
                                            @endif
                                        </td>
                                        <td>
                                            @php $tagList = is_array($row->specialization_options) ? $row->specialization_options : []; @endphp
                                            @if(count($tagList))
                                                <div class="dcs-tags-preview">
                                                    @foreach(array_slice($tagList, 0, 4) as $tag)
                                                        <span class="dcs-tag-preview">{{ $tag }}</span>
                                                    @endforeach
                                                    @if(count($tagList) > 4)
                                                        <span class="dcs-tag-preview">+{{ count($tagList) - 4 }} more</span>
                                                    @endif
                                                </div>
                                            @else
                                                <span class="text-muted small">—</span>
                                            @endif
                                        </td>
                                        <td class="text-center">{{ $row->consultation_duration_minutes ?? 30 }} min</td>
                                        <td class="text-center text-muted">{{ $row->sort_order }}</td>
                                        <td class="text-center">
                                            @if($row->is_active)
                                                <span class="badge-dcs-active">Active</span>
                                            @else
                                                <span class="badge-dcs-inactive">Inactive</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <a href="{{ route('subadmin.doctor_consultation_services.edit', $row) }}" class="btn btn-sm btn-outline-primary rounded-circle mr-1" title="Edit">
                                                <i class="fas fa-pen"></i>
                                            </a>
                                            <form action="{{ route('subadmin.doctor_consultation_services.destroy', $row) }}" method="POST" class="d-inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger rounded-circle" title="Delete" onclick="return confirm('Delete “{{ addslashes($row->name) }}”? This cannot be undone.');">
                                                    <i class="fas fa-trash-alt"></i>
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
