@extends('admin.layouts.app')

@section('content')
<div class="content-wrapper">

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Services</h3>
                    <div class="card-tools">
                        <a href="{{ route('admin.services.create') }}" class="btn btn-primary btn-sm">
                            Add New Service
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success">
                            {{ session('success') }}
                        </div>
                    @endif

                    <div style="max-height: 450px; overflow-y: auto;">
                        <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th style="font-size: 14px; text-align:center;">ID</th>
                                <th style="font-size: 14px; text-align:center;">Name</th>
                                <th style="font-size: 14px; text-align:center;">Description</th>
                                <th style="font-size: 14px; text-align:center;">Created At</th>
                                <th style="font-size: 14px; text-align:center;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($services as $service)
                                <tr>
                                    <td style="font-size: 14px; text-align:center;">{{ $service->id }}</td>
                                    <td style="font-size: 14px; text-align:center;">{{ $service->name }}</td>
                                    <td style="font-size: 14px; text-align:center;">{{ $service->description ? Str::limit($service->description, 50) : '-' }}</td>
                                    <td style="font-size: 14px; text-align:center;">{{ $service->created_at->format('Y-m-d H:i:s') }}</td>
                                    <td style="font-size: 14px; text-align:center;">
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
                </div>
            </div>
        </div>
    </div>
</div>
</div>
@endsection
