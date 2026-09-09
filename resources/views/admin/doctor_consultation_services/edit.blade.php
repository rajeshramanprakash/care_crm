@extends('admin.layouts.app')

@section('title', 'Edit consultation service')

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
                <span>Edit</span>
            </nav>
            <h1><i class="fas fa-pen mr-2"></i>Edit: {{ $item->name }}</h1>
            <p>Update how this service appears on the website and in doctor registration.</p>
        </div>

        @include('admin.doctor_consultation_services.partials.form', [
            'formAction' => route('admin.doctor_consultation_services.update', $item),
            'formMethod' => 'PUT',
            'submitLabel' => 'Save changes',
            'item' => $item,
        ])
    </div>
</div>
@endsection

@section('footer-script')
    @include('admin.doctor_consultation_services.partials.tags-script')
    @include('admin.doctor_consultation_services.partials.sub-services-script')
@endsection
