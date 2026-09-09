@extends('admin.layouts.app')

@section('title', 'Add consultation service')

@section('header-css')
    @include('admin.doctor_consultation_services.partials.styles')
@endsection

@section('content')
<div class="content-wrapper dcs-page">
    <div class="container-fluid py-3">
        <div class="dcs-hero">
            <nav class="dcs-breadcrumb" aria-label="breadcrumb">
                <a href="{{ route('admin.doctor_consultation_services.index') }}">Consultation services</a>
                <span class="mx-1">/</span>
                <span>Add new</span>
            </nav>
            <h1><i class="fas fa-plus-circle mr-2"></i>Add consultation service</h1>
            <p>Service name, duration aur display order set karein — yeh website booking aur doctor registration me use hoga.</p>
        </div>

        @include('admin.doctor_consultation_services.partials.form', [
            'formAction' => route('admin.doctor_consultation_services.store'),
            'submitLabel' => 'Create service',
        ])
    </div>
</div>
@endsection

@section('footer-script')
    @include('admin.doctor_consultation_services.partials.tags-script')
    @include('admin.doctor_consultation_services.partials.sub-services-script')
@endsection
