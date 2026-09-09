@extends('admin.layouts.app')
@section('title', $page_heading)

@section('content')
<div class="content-wrapper">
    <div class="container-fluid py-3">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title mb-0">{{ $page_heading }}</h3>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ $item->exists ? route('admin.insurers.update', $item) : route('admin.insurers.store') }}" enctype="multipart/form-data">
                    @csrf
                    @if($item->exists)
                        @method('PUT')
                    @endif

                    @include('admin.partials.partner-user-form-fields', ['item' => $item, 'partner_type' => $partner_type ?? 'insurer'])

                    <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i> {{ $item->exists ? 'Update' : 'Create' }} Insurer</button>
                    <a href="{{ route('admin.insurers.index') }}" class="btn btn-secondary ml-2">Cancel</a>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
