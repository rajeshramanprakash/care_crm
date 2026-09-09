@extends('freelancer.layouts.app')
@section('title', 'Personal Details')

@section('main')
@php
    $displayProfilePath = $freelancer->getDisplayProfileImagePath();
    $uniformDressType = $freelancer->getUniformDressType();
    $requiresUniformApproval = $uniformDressType !== null;
    $profilePendingReview = $requiresUniformApproval && ($freelancer->profile_image_status ?? '') === 'pending_review';

    $ageValue = $freelancer->age ?? '';
    if (strpos($ageValue, '|') !== false) {
        $ageValue = explode('|', $ageValue)[0];
    }

    $genderValue = $freelancer->gender ?? '';
    if (!$genderValue && $freelancer->age && strpos($freelancer->age, '|') !== false) {
        $genderValue = explode('|', $freelancer->age)[1] ?? '';
    }

    $shiftLabel = match ($freelancer->shift) {
        '12' => '12 Hours',
        '24' => '24 Hours',
        'both' => 'Both (12 & 24 Hours)',
        'onetime' => 'One-time',
        default => $freelancer->shift ? ucfirst($freelancer->shift) : null,
    };

    $locationDisplay = $freelancer->location ?? $freelancer->city ?? null;
@endphp

<div class="content-wrapper fl-personal-wrapper">
    <section class="content">
        <div class="container-fluid pt-3">
            <div class="row">
                <div class="col-12">
                    <div class="card fl-personal-card">
                        <div class="card-header fl-personal-header">
                            <h3 class="card-title mb-0">Personal Details</h3>
                        </div>
                        <div class="card-body fl-personal-body">

                            {{-- Profile hero --}}
                            <div class="row mb-4 align-items-center">
                                <div class="col-lg-4 col-md-5 text-center text-md-left mb-3 mb-md-0">
                                    <div class="profile-image-container">
                                        <img id="profileImagePreview"
                                             src="{{ $displayProfilePath ? asset('storage/' . $displayProfilePath) : asset('images/default-user.png') }}"
                                             alt="Profile Image"
                                             class="fl-profile-image"
                                             onclick="document.getElementById('profileImageInput').click()">
                                        <div class="profile-image-overlay"
                                             onclick="document.getElementById('profileImageInput').click()">
                                            <i class="fas fa-camera"></i>
                                        </div>
                                    </div>
                                    <input type="file" id="profileImageInput" accept="image/*" class="d-none" onchange="uploadProfileImage(this)">
                                    <p class="profile-hint mb-1">Profile Image Update</p>
                                    @if($requiresUniformApproval)
                                        <p class="small text-info mb-0">{{ ucfirst($uniformDressType) }} photos admin review ke baad approve hote hain.</p>
                                        @if($profilePendingReview)
                                            <p class="small text-warning mb-0 mt-1"><i class="fas fa-clock"></i> Aapki latest photo admin approval ka wait kar rahi hai.</p>
                                        @endif
                                    @endif
                                </div>
                                <div class="col-lg-8 col-md-7">
                                    <div class="profile-meta-grid">
                                        <div class="profile-meta-card">
                                            <div class="meta-label">Lead ID</div>
                                            <div class="meta-value">{{ $freelancer->lead_id ?? '—' }}</div>
                                        </div>
                                        <div class="profile-meta-card">
                                            <div class="meta-label">Status</div>
                                            <div class="meta-value">
                                                @if(isset($freelancer->status))
                                                    <span class="badge status-badge status-{{ $freelancer->status }}">{{ ucfirst($freelancer->status) }}</span>
                                                @else
                                                    <span class="text-muted">—</span>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="profile-meta-card">
                                            <div class="meta-label">Job Title</div>
                                            <div class="meta-value">{{ $freelancer->job_title ?? '—' }}</div>
                                        </div>
                                        <div class="profile-meta-card">
                                            <div class="meta-label">Location</div>
                                            <div class="meta-value">{{ $locationDisplay ?? '—' }}</div>
                                        </div>
                                        <div class="profile-meta-card">
                                            <div class="meta-label">Shift</div>
                                            <div class="meta-value">{{ $shiftLabel ?? '—' }}</div>
                                        </div>
                                        <div class="profile-meta-card">
                                            <div class="meta-label">Registered</div>
                                            <div class="meta-value">{{ $freelancer->created_at?->format('d M Y') ?? '—' }}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Personal information --}}
                            <div class="section-shell mb-4">
                                <div class="section-shell-title">
                                    <i class="fas fa-user mr-2"></i>Personal Information
                                </div>
                                <div class="section-shell-body">
                                    <div class="table-responsive fl-two-col-layout">
                                        <table class="table fl-kv-table mb-0">
                                            @include('freelancer.partials.personal_details_field', [
                                                'label' => 'Name',
                                                'fieldName' => 'name',
                                                'inputId' => 'name_input',
                                                'filled' => (bool) $freelancer->name,
                                                'value' => $freelancer->name,
                                            ])
                                            @include('freelancer.partials.personal_details_field', [
                                                'label' => 'Customer Name',
                                                'fieldName' => 'customer_name',
                                                'inputId' => 'customer_name_input',
                                                'filled' => (bool) $freelancer->customer_name,
                                                'value' => $freelancer->customer_name,
                                            ])
                                            @include('freelancer.partials.personal_details_field', [
                                                'label' => 'Age',
                                                'fieldName' => 'age',
                                                'inputId' => 'age_input',
                                                'inputType' => 'number',
                                                'filled' => (bool) $ageValue,
                                                'value' => $ageValue,
                                                'inputAttrs' => 'min="0" max="150"',
                                            ])
                                            @include('freelancer.partials.personal_details_field', [
                                                'label' => 'Gender',
                                                'fieldName' => 'gender',
                                                'inputId' => 'gender_input',
                                                'inputType' => 'select',
                                                'filled' => (bool) $genderValue,
                                                'displayValue' => ucfirst($genderValue),
                                                'options' => ['' => 'Select Gender', 'male' => 'Male', 'female' => 'Female', 'other' => 'Other'],
                                            ])
                                            @include('freelancer.partials.personal_details_field', [
                                                'label' => 'Contact No',
                                                'fieldName' => 'contact_no',
                                                'inputId' => 'contact_no_input',
                                                'inputType' => 'tel',
                                                'filled' => (bool) $freelancer->contact_no,
                                                'value' => $freelancer->contact_no,
                                                'inputAttrs' => 'maxlength="10"',
                                            ])
                                            @include('freelancer.partials.personal_details_field', [
                                                'label' => 'Mobile',
                                                'fieldName' => 'mobile',
                                                'inputId' => 'mobile_input',
                                                'inputType' => 'tel',
                                                'filled' => (bool) $freelancer->mobile,
                                                'value' => $freelancer->mobile,
                                                'inputAttrs' => 'maxlength="10"',
                                            ])
                                        </table>
                                    </div>
                                </div>
                            </div>

                            {{-- Professional information --}}
                            <div class="section-shell mb-4">
                                <div class="section-shell-title">
                                    <i class="fas fa-briefcase mr-2"></i>Professional Information
                                </div>
                                <div class="section-shell-body">
                                    <div class="table-responsive fl-two-col-layout">
                                        <table class="table fl-kv-table mb-0">
                                            @include('freelancer.partials.personal_details_field', [
                                                'label' => 'Job Title',
                                                'fieldName' => 'job_title',
                                                'inputId' => 'job_title_input',
                                                'filled' => (bool) $freelancer->job_title,
                                                'value' => $freelancer->job_title,
                                            ])
                                            @include('freelancer.partials.personal_details_field', [
                                                'label' => 'Location',
                                                'fieldName' => 'location',
                                                'inputId' => 'location_input',
                                                'filled' => (bool) $locationDisplay,
                                                'value' => $locationDisplay,
                                            ])
                                            @include('freelancer.partials.personal_details_field', [
                                                'label' => 'Total Experience',
                                                'fieldName' => 'total_experience',
                                                'inputId' => 'total_experience_input',
                                                'filled' => (bool) $freelancer->total_experience,
                                                'value' => $freelancer->total_experience,
                                                'placeholder' => 'e.g. 2 years, 5 years',
                                            ])
                                            <tr>
                                                <th>Shift</th>
                                                <td>
                                                    @if($shiftLabel)
                                                        <span class="fl-field-value">{{ $shiftLabel }}</span>
                                                    @else
                                                        <div class="fl-field-edit">
                                                            <select class="form-control form-control-sm" id="shift_input">
                                                                <option value="">Select Shift</option>
                                                                <option value="12">12 Hours</option>
                                                                <option value="24">24 Hours</option>
                                                                <option value="both">Both (12 & 24 Hours)</option>
                                                                <option value="onetime">One-time</option>
                                                            </select>
                                                            <button type="button" class="btn btn-sm fl-save-btn" onclick="saveField('shift', 'shift_input')">
                                                                <i class="fas fa-save"></i> Save
                                                            </button>
                                                        </div>
                                                        <small class="fl-field-hint">This field needs to be filled</small>
                                                    @endif
                                                </td>
                                            </tr>
                                        </table>
                                    </div>
                                </div>
                            </div>

                            {{-- Services & pricing --}}
                            <div class="section-shell mb-4">
                                <div class="section-shell-title">
                                    <i class="fas fa-tags mr-2"></i>My Services &amp; Pricing
                                </div>
                                <div class="section-shell-body">
                                    @include('freelancer.partials.services_pricing_summary')
                                </div>
                            </div>

                            {{-- Documents --}}
                            <div class="section-shell mb-0">
                                <div class="section-shell-title">
                                    <i class="fas fa-folder-open mr-2"></i>Documents
                                </div>
                                <div class="table-responsive document-table-wrap">
                                    <table class="table document-table mb-0">
                                        <tr>
                                            <th>Aadhar Card</th>
                                            <td id="aadharCardCell">
                                                @if($freelancer->aadhar_card)
                                                    <button type="button" class="btn btn-sm doc-action-btn doc-view-btn" onclick="viewDocument('{{ asset('storage/' . $freelancer->aadhar_card) }}', 'Aadhar Card')">
                                                        <i class="fas fa-eye mr-1"></i>View
                                                    </button>
                                                    <a href="{{ asset('storage/' . $freelancer->aadhar_card) }}" download class="btn btn-sm doc-action-btn doc-download-btn">
                                                        <i class="fas fa-download mr-1"></i>Download
                                                    </a>
                                                @else
                                                    <input type="file" id="aadharCardInput" accept="image/*,.pdf" class="d-none" onchange="uploadDocument('aadhar_card', this)">
                                                    <button type="button" class="btn btn-sm doc-action-btn doc-upload-btn" onclick="document.getElementById('aadharCardInput').click()">
                                                        <i class="fas fa-upload mr-1"></i>Upload
                                                    </button>
                                                    <span class="text-muted ml-2 small">Not uploaded</span>
                                                @endif
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>PAN Card</th>
                                            <td id="panCardCell">
                                                @if($freelancer->pan_card)
                                                    <button type="button" class="btn btn-sm doc-action-btn doc-view-btn" onclick="viewDocument('{{ asset('storage/' . $freelancer->pan_card) }}', 'PAN Card')">
                                                        <i class="fas fa-eye mr-1"></i>View
                                                    </button>
                                                    <a href="{{ asset('storage/' . $freelancer->pan_card) }}" download class="btn btn-sm doc-action-btn doc-download-btn">
                                                        <i class="fas fa-download mr-1"></i>Download
                                                    </a>
                                                @else
                                                    <input type="file" id="panCardInput" accept="image/*,.pdf" class="d-none" onchange="uploadDocument('pan_card', this)">
                                                    <button type="button" class="btn btn-sm doc-action-btn doc-upload-btn" onclick="document.getElementById('panCardInput').click()">
                                                        <i class="fas fa-upload mr-1"></i>Upload
                                                    </button>
                                                    <span class="text-muted ml-2 small">Not uploaded</span>
                                                @endif
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>Qualification Certificate</th>
                                            <td id="qualificationCertificateCell">
                                                @if($freelancer->qualification_certificate)
                                                    <button type="button" class="btn btn-sm doc-action-btn doc-view-btn" onclick="viewDocument('{{ asset('storage/' . $freelancer->qualification_certificate) }}', 'Qualification Certificate')">
                                                        <i class="fas fa-eye mr-1"></i>View
                                                    </button>
                                                    <a href="{{ asset('storage/' . $freelancer->qualification_certificate) }}" download class="btn btn-sm doc-action-btn doc-download-btn">
                                                        <i class="fas fa-download mr-1"></i>Download
                                                    </a>
                                                @else
                                                    <input type="file" id="qualificationCertificateInput" accept="image/*,.pdf" class="d-none" onchange="uploadDocument('qualification_certificate', this)">
                                                    <button type="button" class="btn btn-sm doc-action-btn doc-upload-btn" onclick="document.getElementById('qualificationCertificateInput').click()">
                                                        <i class="fas fa-upload mr-1"></i>Upload
                                                    </button>
                                                    <span class="text-muted ml-2 small">Not uploaded</span>
                                                @endif
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

{{-- Document View Modal --}}
<div class="modal fade" id="documentViewModal" tabindex="-1" aria-labelledby="documentViewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="documentViewModalLabel">View Document</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center">
                <div id="documentViewContent"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .fl-personal-wrapper {
        overflow-y: auto;
        max-height: calc(100vh - 120px);
        background: #f5f7fb;
    }
    .fl-personal-card {
        border: 0;
        border-radius: 14px;
        overflow: hidden;
        box-shadow: 0 10px 28px rgba(15, 23, 42, 0.08);
    }
    .fl-personal-header {
        background: linear-gradient(90deg, #f7941d 0%, #ff7a18 100%);
        color: #fff;
        padding: 1rem 1.25rem;
    }
    .fl-personal-header p {
        opacity: 0.95;
        font-size: 0.88rem;
    }
    .fl-personal-body {
        max-height: calc(100vh - 250px);
        overflow-y: auto;
        padding: 1.25rem;
    }
    .profile-image-container {
        position: relative;
        display: inline-block;
    }
    .fl-profile-image {
        width: 150px;
        height: 150px;
        border-radius: 50%;
        object-fit: cover;
        border: 4px solid #f7941d;
        box-shadow: 0 8px 20px rgba(247, 148, 29, 0.26);
        cursor: pointer;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .fl-profile-image:hover {
        transform: scale(1.02);
        box-shadow: 0 12px 22px rgba(247, 148, 29, 0.33);
    }
    .profile-image-overlay {
        position: absolute;
        bottom: 8px;
        right: 5px;
        background: #f7941d;
        color: #fff;
        border-radius: 50%;
        width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        border: 3px solid #fff;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.15);
    }
    .profile-hint {
        margin-top: 0.7rem;
        color: #6b7280;
        font-size: 0.87rem;
    }
    .profile-meta-grid {
        display: grid;
        gap: 0.8rem;
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }
    .profile-meta-card {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 10px;
        padding: 0.75rem 0.85rem;
        min-height: 78px;
        display: flex;
        flex-direction: column;
        justify-content: center;
    }
    .meta-label {
        font-size: 0.72rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.35px;
        color: #64748b;
        margin-bottom: 0.25rem;
    }
    .meta-value {
        font-size: 0.92rem;
        font-weight: 600;
        color: #0f172a;
        word-break: break-word;
    }
    .section-shell {
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        overflow: hidden;
        background: #fff;
    }
    .section-shell-title {
        font-size: 0.96rem;
        font-weight: 700;
        color: #334155;
        padding: 0.85rem 1rem;
        border-bottom: 1px solid #e5e7eb;
        background: #f8fafc;
    }
    .section-shell-body {
        padding: 1rem;
    }
    .fl-kv-table {
        margin-bottom: 0;
    }
    .fl-field-value {
        color: #0f172a;
        font-weight: 500;
    }
    .fl-field-edit {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 0.5rem;
    }
    .fl-field-edit .form-control {
        flex: 1 1 180px;
        max-width: 100%;
    }
    .fl-save-btn {
        background: #f7941d;
        border-color: #f7941d;
        color: #fff;
        font-weight: 600;
        border-radius: 8px;
        white-space: nowrap;
    }
    .fl-save-btn:hover {
        background: #e8850f;
        border-color: #e8850f;
        color: #fff;
    }
    .fl-field-hint {
        display: block;
        margin-top: 0.35rem;
        color: #94a3b8;
        font-size: 0.8rem;
    }
    @media (min-width: 992px) {
        .fl-two-col-layout .fl-kv-table tbody {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 0.8rem 1rem;
        }
        .fl-two-col-layout .fl-kv-table tr {
            display: block;
            margin: 0;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            overflow: hidden;
            background: #fff;
        }
        .fl-two-col-layout .fl-kv-table th,
        .fl-two-col-layout .fl-kv-table td {
            display: block;
            width: 100% !important;
            border: 0;
            margin: 0;
        }
        .fl-two-col-layout .fl-kv-table th {
            padding: 0.62rem 0.8rem;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.25px;
            color: #475569;
            background: #f8fafc;
            border-bottom: 1px solid #e5e7eb;
        }
        .fl-two-col-layout .fl-kv-table td {
            padding: 0.7rem 0.8rem;
            color: #0f172a;
            background: #fff;
            word-break: break-word;
        }
    }
    @media (max-width: 991px) {
        .fl-kv-table th {
            width: 38%;
            background: #f8fafc;
            font-size: 0.82rem;
            color: #475569;
            vertical-align: top;
        }
        .fl-kv-table td {
            vertical-align: top;
        }
    }
    .document-table-wrap {
        background: #fff;
    }
    .document-table {
        margin-bottom: 0;
    }
    .document-table th,
    .document-table td {
        vertical-align: middle;
        border-color: #edf2f7;
        padding: 0.85rem 1rem;
    }
    .document-table th {
        width: 260px;
        color: #1f2937;
        font-weight: 600;
        background: #fcfcfd;
    }
    .doc-action-btn {
        border-radius: 8px;
        border: 0;
        font-weight: 600;
        font-size: 0.8rem;
        padding: 0.36rem 0.7rem;
        margin-right: 0.35rem;
        margin-bottom: 0.2rem;
    }
    .doc-view-btn { background: #0ea5e9; color: #fff; }
    .doc-view-btn:hover { background: #0284c7; color: #fff; }
    .doc-download-btn { background: #22c55e; color: #fff; }
    .doc-download-btn:hover { background: #16a34a; color: #fff; }
    .doc-upload-btn { background: #f59e0b; color: #fff; }
    .doc-upload-btn:hover { background: #d97706; color: #fff; }
    .fl-personal-wrapper::-webkit-scrollbar,
    .fl-personal-body::-webkit-scrollbar { width: 8px; }
    .fl-personal-wrapper::-webkit-scrollbar-track,
    .fl-personal-body::-webkit-scrollbar-track { background: #f1f1f1; border-radius: 10px; }
    .fl-personal-wrapper::-webkit-scrollbar-thumb,
    .fl-personal-body::-webkit-scrollbar-thumb { background: #F7941D; border-radius: 10px; }
    .fl-personal-wrapper,
    .fl-personal-body { scrollbar-width: thin; scrollbar-color: #F7941D #f1f1f1; }
    @media (max-width: 767px) {
        .fl-personal-body { padding: 0.95rem; }
        .fl-profile-image { width: 128px; height: 128px; }
        .document-table th { width: 140px; }
        .profile-meta-grid { grid-template-columns: 1fr 1fr; }
    }
    @media (max-width: 480px) {
        .profile-meta-grid { grid-template-columns: 1fr; }
    }
</style>
@endpush

@push('scripts')
<script>
function viewDocument(url, title) {
    const modal = new bootstrap.Modal(document.getElementById('documentViewModal'));
    const modalTitle = document.getElementById('documentViewModalLabel');
    const modalContent = document.getElementById('documentViewContent');

    modalTitle.textContent = title;
    const extension = url.split('.').pop().toLowerCase();

    if (['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(extension)) {
        modalContent.innerHTML = '<img src="' + url + '" class="img-fluid" alt="' + title + '" style="max-height: 70vh;">';
    } else if (extension === 'pdf') {
        modalContent.innerHTML = '<iframe src="' + url + '" style="width: 100%; height: 70vh; border: none;"></iframe>';
    } else {
        modalContent.innerHTML =
            '<div class="alert alert-info">' +
            '<p>This file type cannot be previewed. Please download to view.</p>' +
            '<a href="' + url + '" download class="btn btn-primary"><i class="fas fa-download"></i> Download ' + title + '</a>' +
            '</div>';
    }

    modal.show();
}

function uploadDocument(documentType, input) {
    if (!input.files || !input.files[0]) return;

    const file = input.files[0];
    const formData = new FormData();
    formData.append('document', file);
    formData.append('document_type', documentType);
    formData.append('_token', @json(csrf_token()));

    const $input = $(input);
    const cellIdMap = {
        aadhar_card: 'aadharCardCell',
        pan_card: 'panCardCell',
        qualification_certificate: 'qualificationCertificateCell'
    };
    const $cell = $('#' + cellIdMap[documentType]);
    const originalContent = $cell.html();
    $input.prop('disabled', true);
    $cell.html('<span class="text-info"><i class="fas fa-spinner fa-spin"></i> Uploading…</span>');

    $.ajax({
        url: @json(route('freelancer.personal-details.upload-document')),
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function (response) {
            if (response.success) {
                toastr.success(response.message || 'Document uploaded successfully');
                const documentUrl = response.document_url;
                const documentName = response.document_name || documentType.replace(/_/g, ' ').replace(/\b\w/g, function (l) { return l.toUpperCase(); });
                $cell.html(
                    '<button type="button" class="btn btn-sm doc-action-btn doc-view-btn" onclick="viewDocument(\'' + documentUrl + '\', \'' + documentName + '\')">' +
                    '<i class="fas fa-eye mr-1"></i>View</button>' +
                    '<a href="' + documentUrl + '" download class="btn btn-sm doc-action-btn doc-download-btn">' +
                    '<i class="fas fa-download mr-1"></i>Download</a>'
                );
            } else {
                toastr.error(response.message || 'Failed to upload document');
                $cell.html(originalContent);
                $input.prop('disabled', false);
            }
        },
        error: function (xhr) {
            toastr.error((xhr.responseJSON && xhr.responseJSON.message) || 'Failed to upload document');
            $cell.html(originalContent);
            $input.prop('disabled', false);
        }
    });
}

function uploadProfileImage(input) {
    if (!input.files || !input.files[0]) return;

    const file = input.files[0];
    const formData = new FormData();
    formData.append('profile_image', file);
    formData.append('_token', @json(csrf_token()));

    const reader = new FileReader();
    reader.onload = function (e) {
        document.getElementById('profileImagePreview').src = e.target.result;
    };
    reader.readAsDataURL(file);

    $.ajax({
        url: @json(route('freelancer.personal-details.update-profile-image')),
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function (response) {
            if (response.success) {
                toastr.success(response.message || 'Profile image updated successfully');
                if (response.profile_image_url) {
                    document.getElementById('profileImagePreview').src = response.profile_image_url;
                }
            } else {
                toastr.error(response.message || 'Failed to update profile image');
            }
        },
        error: function (xhr) {
            toastr.error((xhr.responseJSON && xhr.responseJSON.message) || 'Failed to update profile image');
        }
    });
}

function saveField(fieldName, inputId) {
    const input = document.getElementById(inputId);
    const value = input.value.trim();

    if (!value) {
        toastr.error('Please enter a value');
        return;
    }

    const editWrap = input.closest('.fl-field-edit');
    const button = editWrap ? editWrap.querySelector('.fl-save-btn') : input.nextElementSibling;
    const originalButtonHtml = button.innerHTML;

    input.disabled = true;
    button.disabled = true;
    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving…';

    $.ajax({
        url: @json(route('freelancer.personal-details.update-field')),
        type: 'POST',
        data: {
            field_name: fieldName,
            field_value: value,
            _token: @json(csrf_token())
        },
        success: function (response) {
            if (response.success) {
                toastr.success(response.message || 'Field updated successfully');
                setTimeout(function () { window.location.reload(); }, 800);
            } else {
                toastr.error(response.message || 'Failed to update field');
                input.disabled = false;
                button.disabled = false;
                button.innerHTML = originalButtonHtml;
            }
        },
        error: function (xhr) {
            toastr.error((xhr.responseJSON && xhr.responseJSON.message) || 'Failed to update field');
            input.disabled = false;
            button.disabled = false;
            button.innerHTML = originalButtonHtml;
        }
    });
}

$(function () {
    $('#contact_no_input, #mobile_input').on('input', function () {
        this.value = this.value.replace(/[^0-9]/g, '');
    });
});
</script>
@endpush
