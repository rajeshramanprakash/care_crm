@extends('admin.layouts.app')

@section('title', 'Add New Service')

@section('header-css')
    @include('admin.doctor_consultation_services.partials.styles')
@endsection

@section('content')
<div class="content-wrapper dcs-page">
    <div class="container-fluid py-3">
        <div class="dcs-hero">
            <nav class="dcs-breadcrumb" aria-label="breadcrumb">
                <a href="{{ route('admin.services.index') }}">Services</a>
                <span class="mx-1">/</span>
                <span>Add new</span>
            </nav>
            <h1><i class="fas fa-plus-circle mr-2"></i>Add New Service</h1>
            <p>Service name, icon, session duration, display order, tags aur optional sub-services set karein.</p>
        </div>

        @include('admin.services.partials.form', [
            'formAction' => route('admin.services.store'),
            'submitLabel' => 'Create Service',
        ])
    </div>
</div>
@endsection

@section('footer-script')
    @include('admin.doctor_consultation_services.partials.tags-script')
    @include('admin.doctor_consultation_services.partials.sub-services-script')
@endsection
