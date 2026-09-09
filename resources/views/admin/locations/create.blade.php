@extends('admin.layouts.app')

@section('title', 'Add location')

@section('header-css')
    @include('admin.locations.partials.form-styles')
@endsection

@section('content')
<div class="content-wrapper loc-page">
    <div class="container-fluid py-3">
        <div class="loc-hero">
            <nav class="loc-breadcrumb" aria-label="breadcrumb">
                <a href="{{ route('admin.locations.index') }}">Locations</a>
                <span class="mx-1">/</span>
                <span>Add new</span>
            </nav>
            <h1><i class="fas fa-plus-circle mr-2"></i>Add new location</h1>
            <p>State → Tier → City select karein. Doctor, vendor aur freelancer ki pricing is location ke liye alag save hogi.</p>
        </div>

        @include('admin.locations.partials.location-form', [
            'formAction' => route('admin.locations.store'),
            'submitLabel' => 'Create location',
        ])
    </div>
</div>
@endsection

@section('footer-script')
    @include('admin.locations.partials.services-pricing-script')
@endsection
