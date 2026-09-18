@extends('admin.layouts.app')

@section('title', 'Edit location')

@section('header-css')
    @include('admin.locations.partials.form-styles')
@endsection

@section('content')
<div class="content-wrapper loc-page">
    <div class="container-fluid py-3">
        <div class="loc-hero">
            <nav class="loc-breadcrumb" aria-label="breadcrumb">
                <a href="{{ route('subadmin.locations.index') }}">Locations</a>
                <span class="mx-1">/</span>
                <span>Edit</span>
            </nav>
            <h1><i class="fas fa-pen mr-2"></i>Edit: {{ $location->name }}</h1>
            <p>State, tier, city aur is location ki doctor / vendor / freelancer pricing update karein.</p>
        </div>

        @include('admin.locations.partials.location-form', [
            'formAction' => route('subadmin.locations.update', $location),
            'formMethod' => 'PUT',
            'submitLabel' => 'Save changes',
            'location' => $location,
        ])
    </div>
</div>
@endsection

@section('footer-script')
    @include('admin.locations.partials.services-pricing-script')
@endsection
