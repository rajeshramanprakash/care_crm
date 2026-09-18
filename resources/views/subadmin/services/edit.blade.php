@extends('admin.layouts.app')

@section('title', 'Edit Service')

@section('header-css')
    @include('admin.doctor_consultation_services.partials.styles')
@endsection

@section('content')
<div class="content-wrapper dcs-page">
    <div class="container-fluid py-3">
        <div class="dcs-hero">
            <nav class="dcs-breadcrumb" aria-label="breadcrumb">
                <a href="{{ route('subadmin.services.index') }}">Services</a>
                <span class="mx-1">/</span>
                <span>Edit</span>
            </nav>
            <h1><i class="fas fa-edit mr-2"></i>Edit Service</h1>
            <p>{{ $item->name }}</p>
        </div>

        @include('admin.services.partials.form', [
            'formAction' => route('subadmin.services.update', $item),
            'formMethod' => 'PUT',
            'submitLabel' => 'Update Service',
            'item' => $item,
        ])
    </div>
</div>
@endsection

@section('footer-script')
    @include('admin.doctor_consultation_services.partials.tags-script')
    @include('admin.doctor_consultation_services.partials.sub-services-script')
@endsection
