@extends('vendor.layouts.app')
@section('title', 'Personal Details')

@section('main')
@php
    $shiftLabel = match ($vendor->shift) {
        '12' => '12 Hours',
        '24' => '24 Hours',
        'both' => 'Both (12 & 24 Hours)',
        'onetime' => 'One-time',
        default => $vendor->shift ? ucfirst($vendor->shift) : null,
    };
@endphp

<div class="content-wrapper vd-personal-wrapper">
    <section class="content">
        <div class="container-fluid pt-3">
            <div class="row">
                <div class="col-12">
                    <div class="card vd-personal-card">
                        <div class="card-header vd-personal-header">
                            <h3 class="card-title mb-0">Personal Details</h3>
                            
                        </div>
                        <div class="card-body vd-personal-body">

                            <div class="row mb-4 align-items-center">
                                <div class="col-lg-4 col-md-5 mb-3 mb-md-0">
                                    <div class="vd-profile-block">
                                        <div class="profile-image-container">
                                            <img id="profileImagePreview"
                                                 src="{{ $vendor->profile_image ? asset('storage/' . $vendor->profile_image) : asset('images/default-user.png') }}"
                                                 alt="Profile"
                                                 class="vd-profile-image"
                                                 onclick="document.getElementById('profileImageInput').click()">
                                            <div class="profile-image-overlay"
                                                 onclick="document.getElementById('profileImageInput').click()">
                                                <i class="fas fa-camera"></i>
                                            </div>
                                        </div>
                                        <input type="file" id="profileImageInput" accept="image/*" class="d-none" onchange="uploadProfileImage(this)">
                                        <p class="profile-hint mb-0">Profile Update</p>
                                    </div>
                                </div>
                                <div class="col-lg-8 col-md-7">
                                    <div class="profile-meta-grid">
                                        <div class="profile-meta-card">
                                            <div class="meta-label">Vendor Name</div>
                                            <div class="meta-value">{{ $vendor->name ?? '—' }}</div>
                                        </div>
                                        <div class="profile-meta-card">
                                            <div class="meta-label">Status</div>
                                            <div class="meta-value">
                                                @if(($vendor->status ?? '') === 'active')
                                                    <span class="badge badge-success">Active</span>
                                                @elseif(($vendor->status ?? '') === 'dutyoff')
                                                    <span class="badge badge-warning">Duty Off</span>
                                                @else
                                                    <span class="badge badge-secondary">{{ ucfirst($vendor->status ?? 'N/A') }}</span>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="profile-meta-card">
                                            <div class="meta-label">Location</div>
                                            <div class="meta-value">{{ $vendor->location ?? '—' }}</div>
                                        </div>
                                        <div class="profile-meta-card">
                                            <div class="meta-label">Contact</div>
                                            <div class="meta-value">{{ $vendor->contact_no ?? '—' }}</div>
                                        </div>
                                        <div class="profile-meta-card">
                                            <div class="meta-label">Email</div>
                                            <div class="meta-value">{{ $vendor->email ?? '—' }}</div>
                                        </div>
                                        <div class="profile-meta-card">
                                            <div class="meta-label">Registered</div>
                                            <div class="meta-value">{{ $vendor->created_at?->format('d M Y') ?? '—' }}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="section-shell mb-4">
                                <div class="section-shell-title"><i class="fas fa-user mr-2"></i>Basic Information</div>
                                <div class="section-shell-body">
                                    <div class="table-responsive vd-two-col-layout">
                                        <table class="table vd-kv-table mb-0">
                                            <tr><th>Customer / Entity Name</th><td>{{ $vendor->customer_name ?? '—' }}</td></tr>
                                            <tr><th>Age</th><td>{{ $vendor->age ?? '—' }}</td></tr>
                                            <tr><th>Gender</th><td>{{ $vendor->gender ? ucfirst($vendor->gender) : '—' }}</td></tr>
                                            <tr><th>Total Experience</th><td>{{ $vendor->total_experience ?? '—' }}</td></tr>
                                            @if($shiftLabel)
                                            <tr><th>Shift</th><td>{{ $shiftLabel }}</td></tr>
                                            @endif
                                        </table>
                                    </div>
                                </div>
                            </div>

                            <div class="section-shell mb-4">
                                <div class="section-shell-title"><i class="fas fa-tags mr-2"></i>My Services &amp; Pricing</div>
                                <div class="section-shell-body">
                                    @include('vendor.partials.services_pricing_summary')
                                </div>
                            </div>

                            <div class="section-shell mb-0">
                                <div class="section-shell-title"><i class="fas fa-folder-open mr-2"></i>Documents</div>
                                <div class="table-responsive document-table-wrap">
                                    <table class="table document-table mb-0">
                                        <tr>
                                            <th>Aadhar Card</th>
                                            <td id="aadharCardCell">
                                                @if($vendor->aadhar_card)
                                                    <a href="{{ asset('storage/' . $vendor->aadhar_card) }}" target="_blank" class="btn btn-sm doc-action-btn doc-view-btn"><i class="fas fa-eye mr-1"></i>View</a>
                                                    <a href="{{ asset('storage/' . $vendor->aadhar_card) }}" download class="btn btn-sm doc-action-btn doc-download-btn"><i class="fas fa-download mr-1"></i>Download</a>
                                                @else
                                                    <input type="file" id="aadharCardInput" accept="image/*,.pdf" class="d-none" onchange="uploadDocument('aadhar_card', this)">
                                                    <button type="button" class="btn btn-sm doc-action-btn doc-upload-btn" onclick="document.getElementById('aadharCardInput').click()"><i class="fas fa-upload mr-1"></i>Upload</button>
                                                    <span class="text-muted ml-2 small">Not uploaded</span>
                                                @endif
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>PAN Card</th>
                                            <td id="panCardCell">
                                                @if($vendor->pan_card)
                                                    <a href="{{ asset('storage/' . $vendor->pan_card) }}" target="_blank" class="btn btn-sm doc-action-btn doc-view-btn"><i class="fas fa-eye mr-1"></i>View</a>
                                                    <a href="{{ asset('storage/' . $vendor->pan_card) }}" download class="btn btn-sm doc-action-btn doc-download-btn"><i class="fas fa-download mr-1"></i>Download</a>
                                                @else
                                                    <input type="file" id="panCardInput" accept="image/*,.pdf" class="d-none" onchange="uploadDocument('pan_card', this)">
                                                    <button type="button" class="btn btn-sm doc-action-btn doc-upload-btn" onclick="document.getElementById('panCardInput').click()"><i class="fas fa-upload mr-1"></i>Upload</button>
                                                    <span class="text-muted ml-2 small">Not uploaded</span>
                                                @endif
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>Qualification Certificate</th>
                                            <td id="qualificationCertificateCell">
                                                @if($vendor->qualification_certificate)
                                                    <a href="{{ asset('storage/' . $vendor->qualification_certificate) }}" target="_blank" class="btn btn-sm doc-action-btn doc-view-btn"><i class="fas fa-eye mr-1"></i>View</a>
                                                    <a href="{{ asset('storage/' . $vendor->qualification_certificate) }}" download class="btn btn-sm doc-action-btn doc-download-btn"><i class="fas fa-download mr-1"></i>Download</a>
                                                @else
                                                    <input type="file" id="qualificationCertificateInput" accept="image/*,.pdf" class="d-none" onchange="uploadDocument('qualification_certificate', this)">
                                                    <button type="button" class="btn btn-sm doc-action-btn doc-upload-btn" onclick="document.getElementById('qualificationCertificateInput').click()"><i class="fas fa-upload mr-1"></i>Upload</button>
                                                    <span class="text-muted ml-2 small">Not uploaded</span>
                                                @endif
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>Bank Document</th>
                                            <td id="bankDocumentCell">
                                                @if($vendor->bank_document)
                                                    <a href="{{ asset('storage/' . $vendor->bank_document) }}" target="_blank" class="btn btn-sm doc-action-btn doc-view-btn"><i class="fas fa-eye mr-1"></i>View</a>
                                                    <a href="{{ asset('storage/' . $vendor->bank_document) }}" download class="btn btn-sm doc-action-btn doc-download-btn"><i class="fas fa-download mr-1"></i>Download</a>
                                                @else
                                                    <span class="text-muted">Not uploaded</span>
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
@endsection

@push('styles')
<style>
    .vd-personal-wrapper { overflow-y: auto; max-height: calc(100vh - 120px); background: #f5f7fb; }
    .vd-personal-card { border: 0; border-radius: 14px; overflow: hidden; box-shadow: 0 10px 28px rgba(15, 23, 42, 0.08); }
    .vd-personal-header { background: linear-gradient(90deg, #f7941d 0%, #ff7a18 100%); color: #fff; padding: 1rem 1.25rem; }
    .vd-personal-header p { opacity: 0.95; font-size: 0.88rem; }
    .vd-personal-body { max-height: calc(100vh - 250px); overflow-y: auto; padding: 1.25rem; }
    .vd-profile-block {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        text-align: center;
        width: 100%;
    }
    .profile-image-container { position: relative; display: inline-block; margin: 0 auto; }
    .vd-profile-image {
        width: 150px; height: 150px; border-radius: 50%; object-fit: cover;
        border: 4px solid #f7941d; box-shadow: 0 8px 20px rgba(247, 148, 29, 0.26); cursor: pointer;
        display: block;
    }
    .profile-image-overlay {
        position: absolute; bottom: 8px; right: 5px; background: #f7941d; color: #fff;
        border-radius: 50%; width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;
        cursor: pointer; border: 3px solid #fff;
    }
    .profile-hint {
        margin-top: 0.75rem;
        color: #64748b;
        font-size: 0.84rem;
        font-weight: 500;
        letter-spacing: 0.02em;
        text-align: center;
        width: 100%;
        max-width: 150px;
    }
    .profile-meta-grid { display: grid; gap: 0.8rem; grid-template-columns: repeat(3, minmax(0, 1fr)); }
    .profile-meta-card { background: #fff; border: 1px solid #e5e7eb; border-radius: 10px; padding: 0.75rem 0.85rem; min-height: 78px; }
    .meta-label { font-size: 0.72rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.35px; color: #64748b; margin-bottom: 0.25rem; }
    .meta-value { font-size: 0.92rem; font-weight: 600; color: #0f172a; word-break: break-word; }
    .section-shell { border: 1px solid #e5e7eb; border-radius: 12px; overflow: hidden; background: #fff; }
    .section-shell-title { font-size: 0.96rem; font-weight: 700; color: #334155; padding: 0.85rem 1rem; border-bottom: 1px solid #e5e7eb; background: #f8fafc; }
    .section-shell-body { padding: 1rem; }
    @media (min-width: 992px) {
        .vd-two-col-layout .vd-kv-table tbody { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 0.8rem 1rem; }
        .vd-two-col-layout .vd-kv-table tr { display: block; border: 1px solid #e5e7eb; border-radius: 10px; overflow: hidden; }
        .vd-two-col-layout .vd-kv-table th, .vd-two-col-layout .vd-kv-table td { display: block; width: 100% !important; border: 0; }
        .vd-two-col-layout .vd-kv-table th { padding: 0.62rem 0.8rem; font-size: 0.8rem; text-transform: uppercase; color: #475569; background: #f8fafc; border-bottom: 1px solid #e5e7eb; }
        .vd-two-col-layout .vd-kv-table td { padding: 0.7rem 0.8rem; }
    }
    .document-table th, .document-table td { vertical-align: middle; border-color: #edf2f7; padding: 0.85rem 1rem; }
    .document-table th { width: 260px; font-weight: 600; background: #fcfcfd; }
    .doc-action-btn { border-radius: 8px; border: 0; font-weight: 600; font-size: 0.8rem; padding: 0.36rem 0.7rem; margin-right: 0.35rem; }
    .doc-view-btn { background: #0ea5e9; color: #fff; }
    .doc-download-btn { background: #22c55e; color: #fff; }
    .doc-upload-btn { background: #f59e0b; color: #fff; }
    @media (max-width: 767px) { .profile-meta-grid { grid-template-columns: 1fr 1fr; } .vd-profile-image { width: 128px; height: 128px; } }
    @media (max-width: 480px) { .profile-meta-grid { grid-template-columns: 1fr; } }
</style>
@endpush

@push('scripts')
<script>
function uploadProfileImage(input) {
    if (!input.files || !input.files[0]) return;
    var formData = new FormData();
    formData.append('profile_image', input.files[0]);
    formData.append('_token', @json(csrf_token()));
    $.ajax({
        url: @json(route('vendor.personal-details.update-profile-image')),
        type: 'POST', data: formData, processData: false, contentType: false,
        success: function(r) {
            if (r.success) {
                if (r.profile_image_url) $('#profileImagePreview').attr('src', r.profile_image_url);
                toastr.success(r.message || 'Profile updated');
            } else toastr.error(r.message || 'Failed');
        },
        error: function(xhr) { toastr.error((xhr.responseJSON && xhr.responseJSON.message) || 'Upload failed'); }
    });
}

function uploadDocument(documentType, input) {
    if (!input.files || !input.files[0]) return;
    var formData = new FormData();
    formData.append('document', input.files[0]);
    formData.append('document_type', documentType);
    formData.append('_token', @json(csrf_token()));
    var cellIdMap = { aadhar_card: 'aadharCardCell', pan_card: 'panCardCell', qualification_certificate: 'qualificationCertificateCell' };
    var $cell = $('#' + cellIdMap[documentType]);
    var orig = $cell.html();
    $cell.html('<span class="text-info"><i class="fas fa-spinner fa-spin"></i> Uploading…</span>');
    $.ajax({
        url: @json(route('vendor.personal-details.upload-document')),
        type: 'POST', data: formData, processData: false, contentType: false,
        success: function(r) {
            if (r.success) {
                var url = r.document_url || r.document_path;
                $cell.html('<a href="' + url + '" target="_blank" class="btn btn-sm doc-action-btn doc-view-btn"><i class="fas fa-eye mr-1"></i>View</a> <a href="' + url + '" download class="btn btn-sm doc-action-btn doc-download-btn"><i class="fas fa-download mr-1"></i>Download</a>');
                toastr.success(r.message);
            } else { $cell.html(orig); toastr.error(r.message); }
        },
        error: function(xhr) { $cell.html(orig); toastr.error((xhr.responseJSON && xhr.responseJSON.message) || 'Upload failed'); }
    });
}
</script>
@endpush
