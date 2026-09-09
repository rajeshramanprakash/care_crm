@extends('admin.layouts.app')
@section('title', 'Registration Languages')

@section('content')
<div class="content-wrapper">
    <div class="container-fluid py-3">
        <div class="card">
            <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
                <div>
                    <h3 class="card-title mb-0">Registration Languages</h3>
                    <p class="text-muted small mb-0 mt-1">Add language by name only — registration form labels auto-translate (Gemini AI).</p>
                </div>
                <a href="{{ route('admin.languages.create') }}" class="btn btn-primary btn-sm">
                    <i class="fas fa-plus mr-1"></i> Add Language
                </a>
            </div>
            <div class="card-body">
                @if(session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif
                @if(session('warning'))
                    <div class="alert alert-warning">{{ session('warning') }}</div>
                @endif

                @if($languages->isEmpty())
                    <div class="text-center text-muted py-4">
                        <i class="fas fa-language fa-2x mb-2"></i>
                        <p class="mb-0">No languages yet. Add English, Hindi, or other languages for the registration form dropdown.</p>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Order</th>
                                    <th>Name</th>
                                    <th>Code</th>
                                    <th>Native name</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($languages as $language)
                                    <tr>
                                        <td>{{ $language->sort_order }}</td>
                                        <td><strong>{{ $language->name }}</strong></td>
                                        <td><code>{{ $language->code }}</code></td>
                                        <td>{{ $language->native_name ?: '—' }}</td>
                                        <td>
                                            @if($language->is_active)
                                                <span class="badge badge-success">Active</span>
                                            @else
                                                <span class="badge badge-secondary">Inactive</span>
                                            @endif
                                        </td>
                                        <td class="text-nowrap">
                                            <form action="{{ route('admin.languages.auto_translate', $language) }}" method="POST" class="d-inline"
                                                  onsubmit="return confirm('Re-translate all registration labels to {{ $language->name }}?');">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-success" title="Auto-translate again">
                                                    <i class="fas fa-magic"></i>
                                                </button>
                                            </form>
                                            <a href="{{ route('admin.languages.translations', $language) }}" class="btn btn-sm btn-warning" title="Edit translations (optional)">
                                                <i class="fas fa-file-alt"></i>
                                            </a>
                                            <a href="{{ route('admin.languages.edit', $language) }}" class="btn btn-sm btn-info" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <form action="{{ route('admin.languages.destroy', $language) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this language and all its translations?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-danger" title="Delete">
                                                    <i class="fas fa-trash"></i>
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
