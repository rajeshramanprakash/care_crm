@extends('admin.layouts.app')
@section('title', 'Doctor request #' . $doctor->id)

@section('content')
<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid d-flex justify-content-between align-items-center flex-wrap">
            <div>
                <h1>Doctor registration</h1>
                <p class="text-muted mb-0">Full details as submitted on registration.</p>
            </div>
            <div>
                <a href="{{ route('subadmin.doctor_requests.index') }}" class="btn btn-default"><i class="fas fa-arrow-left"></i> Back to list</a>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    @include('admin.doctor_requests.partials.registration_detail_card', ['doctor' => $doctor, 'doctorReferralUsers' => $doctorReferralUsers ?? collect()])
                </div>
            </div>
        </div>
    </section>
</div>
@include('admin.doctor_requests.partials.website_reviews_modal_markup')
@endsection

@section('footer-script')
@include('admin.doctor_requests.partials.doctor_consultation_pricing_editor_scripts')
@include('admin.doctor_requests.partials.registration_profile_editor_scripts')
@include('admin.doctor_requests.partials.registration_profile_image_admin_scripts')
@include('admin.doctor_requests.partials.website_reviews_modal_scripts_show')
@include('admin.doctor_requests.partials.leegality_agreement_scripts')
@endsection
