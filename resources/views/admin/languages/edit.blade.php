@extends('admin.layouts.app')
@section('title', 'Edit Language')

@section('content')
<div class="content-wrapper">
    <div class="container-fluid py-3">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title mb-0">Edit Language — {{ $language->name }}</h3>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.languages.update', $language) }}">
                    @csrf
                    @method('PUT')
                    @include('admin.languages.partials.form', ['language' => $language])
                    <button type="submit" class="btn btn-primary">Update</button>
                    <a href="{{ route('admin.languages.translations', $language) }}" class="btn btn-warning ml-2">Form translations</a>
                    <a href="{{ route('admin.languages.index') }}" class="btn btn-secondary ml-2">Back</a>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
