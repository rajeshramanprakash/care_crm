@extends('doctor_carelix.layouts.app')
@section('title', 'Personal Details | Doctor')

@section('main')
@php
    $doctorPublicFileUrl = function (?string $path): ?string {
        if (!$path) {
            return null;
        }
        $path = str_replace('\\', '/', trim($path));
        if (preg_match('#^https?://#i', $path)) {
            return $path;
        }
        $path = ltrim($path, '/');
        if (str_starts_with($path, 'storage/')) {
            return '/'.$path;
        }

        return '/storage/'.$path;
    };
@endphp
<div class="content-wrapper doctor-personal-wrapper">
    <section class="content">
        <div class="container-fluid pt-3">
            <div class="row">
                <div class="col-12">
                    <div class="card doctor-personal-card">
                        <div class="card-header doctor-personal-header">
                            <h3 class="card-title mb-1">Personal Details</h3>
                        </div>
                        <div class="card-body doctor-personal-body">
                            <!-- Profile Image + Meta Section -->
                            <div class="row mb-4 align-items-center">
                                <div class="col-lg-4 col-md-5 text-center text-md-left mb-3 mb-md-0">
                                    <div class="profile-image-container">
                                        @php
                                            $portalProfilePath = ($doctor->profile_image_status ?? '') === 'approved' && $doctor->profile_image
                                                ? $doctor->profile_image
                                                : null;
                                        @endphp
                                        <img id="profileImagePreview" 
                                             src="{{ $portalProfilePath ? $doctorPublicFileUrl($portalProfilePath) : asset('images/default-user.png') }}" 
                                             alt="Profile Image" 
                                             class="doctor-profile-image"
                                             onclick="document.getElementById('profileImageInput').click()">
                                        <div class="profile-image-overlay"
                                             onclick="document.getElementById('profileImageInput').click()">
                                            <i class="fas fa-camera"></i>
                                        </div>
                                    </div>
                                    <input type="file" id="profileImageInput" accept="image/*" style="display: none;" onchange="uploadProfileImage(this)">
                                    <p class="profile-hint mb-1">Click on image to update your profile photo</p>
                                    @if(($doctor->profile_image_status ?? 'pending_review') !== 'approved')
                                        <p class="small text-warning mb-0"><i class="fas fa-clock"></i> Profile photo pending admin approval.</p>
                                    @endif
                                    <p id="profileImagePendingMsg" class="small text-info mb-0 mt-1" style="display:none;"><i class="fas fa-info-circle"></i> Photo uploaded. <strong>Please wait for approval by admin.</strong></p>
                                </div>
                                <div class="col-lg-8 col-md-7">
                                    <div class="profile-meta-grid">
                                        <div class="profile-meta-card">
                                            <div class="meta-label">Lead</div>
                                            <div class="meta-value">{{ $doctor->lead_id ?? '—' }}</div>
                                        </div>
                                        <div class="profile-meta-card">
                                            <div class="meta-label">Registered</div>
                                            <div class="meta-value">{{ $doctor->created_at?->format('d M Y, h:i A') ?? '—' }}</div>
                                        </div>
                                        <div class="profile-meta-card">
                                            <div class="meta-label">Status</div>
                                            <div class="meta-value">
                                                @if($doctor->approval_status === 'approved')
                                                    <span class="badge badge-success">Approved</span>
                                                @elseif($doctor->approval_status === 'rejected')
                                                    <span class="badge badge-danger">Rejected</span>
                                                @else
                                                    <span class="badge badge-warning">Pending</span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="section-shell mb-4">
                                <div class="section-shell-title">
                                    <i class="fas fa-user-check mr-2"></i>Registration Details
                                </div>
                                <div class="section-shell-body">
                                    @include('admin.doctor_requests.partials.registration_detail_card', [
                                        'doctor' => $doctor,
                                        'hideProfilePhotoRow' => true,
                                        'hideDocumentsSection' => true,
                                        'hideWebsiteConsultationBookingsSection' => true,
                                        'hideCalendarAvailabilitySection' => true,
                                        'hideSetConsultationChargesSection' => true,
                                        'doctorPortalReadOnly' => true,
                                        'deferPortalPricingSection' => true,
                                        'useTwoColumnDetailsLayout' => true,
                                        'hideLeadRegisteredStatusHeader' => true,
                                        'allowPortalEmailEdit' => true,
                                    ])
                                </div>
                            </div>

                            <div class="section-shell mb-4">
                                <div class="section-shell-title">
                                    <i class="fas fa-folder-open mr-2"></i>Documents
                                </div>
                                <div class="table-responsive document-table-wrap">
                                <table class="table document-table mb-0">
                                    <tr>
                                        <th>Aadhar Card</th>
                                        <td id="aadharCardCell">
                                            @if($doctor->aadhar_card)
                                                <button type="button" class="btn btn-sm doc-action-btn doc-view-btn" onclick="viewDocument('{{ asset('storage/' . $doctor->aadhar_card) }}', 'Aadhar Card')">
                                                    <i class="fas fa-eye mr-1"></i>View
                                                </button>
                                                <a href="{{ asset('storage/' . $doctor->aadhar_card) }}" download class="btn btn-sm doc-action-btn doc-download-btn">
                                                    <i class="fas fa-download mr-1"></i>Download
                                                </a>
                                            @else
                                                <input type="file" id="aadharCardInput" accept="image/*,.pdf" style="display: none;" onchange="uploadDocument('aadhar_card', this)">
                                                <button type="button" class="btn btn-sm doc-action-btn doc-upload-btn" onclick="document.getElementById('aadharCardInput').click()">
                                                    <i class="fas fa-upload mr-1"></i>Upload Aadhar Card
                                                </button>
                                                <span class="text-muted ml-2 small">Not uploaded</span>
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>PAN Card</th>
                                        <td id="panCardCell">
                                            @if($doctor->pan_card)
                                                <button type="button" class="btn btn-sm doc-action-btn doc-view-btn" onclick="viewDocument('{{ asset('storage/' . $doctor->pan_card) }}', 'PAN Card')">
                                                    <i class="fas fa-eye mr-1"></i>View
                                                </button>
                                                <a href="{{ asset('storage/' . $doctor->pan_card) }}" download class="btn btn-sm doc-action-btn doc-download-btn">
                                                    <i class="fas fa-download mr-1"></i>Download
                                                </a>
                                            @else
                                                <input type="file" id="panCardInput" accept="image/*,.pdf" style="display: none;" onchange="uploadDocument('pan_card', this)">
                                                <button type="button" class="btn btn-sm doc-action-btn doc-upload-btn" onclick="document.getElementById('panCardInput').click()">
                                                    <i class="fas fa-upload mr-1"></i>Upload PAN Card
                                                </button>
                                                <span class="text-muted ml-2 small">Not uploaded</span>
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Qualification Certificate</th>
                                        <td id="qualificationCertificateCell">
                                            @if($doctor->qualification_certificate)
                                                <button type="button" class="btn btn-sm doc-action-btn doc-view-btn" onclick="viewDocument('{{ asset('storage/' . $doctor->qualification_certificate) }}', 'Qualification Certificate')">
                                                    <i class="fas fa-eye mr-1"></i>View
                                                </button>
                                                <a href="{{ asset('storage/' . $doctor->qualification_certificate) }}" download class="btn btn-sm doc-action-btn doc-download-btn">
                                                    <i class="fas fa-download mr-1"></i>Download
                                                </a>
                                            @else
                                                <input type="file" id="qualificationCertificateInput" accept="image/*,.pdf" style="display: none;" onchange="uploadDocument('qualification_certificate', this)">
                                                <button type="button" class="btn btn-sm doc-action-btn doc-upload-btn" onclick="document.getElementById('qualificationCertificateInput').click()">
                                                    <i class="fas fa-upload mr-1"></i>Upload Qualification Certificate
                                                </button>
                                                <span class="text-muted ml-2 small">Not uploaded</span>
                                            @endif
                                        </td>
                                    </tr>
                                </table>
                                </div>
                            </div>

                            <div class="section-shell mb-4">
                                <div class="section-shell-title">
                                    <i class="fas fa-stethoscope mr-2"></i>My consultation services &amp; pricing
                                </div>
                                <div class="section-shell-body">
                                    @include('doctor_carelix.partials.services_pricing_summary', ['doctor' => $doctor])
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<!-- Document View Modal -->
<div class="modal fade" id="documentViewModal" tabindex="-1" aria-labelledby="documentViewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="documentViewModalLabel">View Document</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center">
                <div id="documentViewContent">
                    <!-- Document will be loaded here -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
function viewDocument(url, title) {
    const modal = new bootstrap.Modal(document.getElementById('documentViewModal'));
    const modalTitle = document.getElementById('documentViewModalLabel');
    const modalContent = document.getElementById('documentViewContent');
    
    modalTitle.textContent = title;
    
    // Check file extension
    const extension = url.split('.').pop().toLowerCase();
    
    if (['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(extension)) {
        // Image file
        modalContent.innerHTML = `<img src="${url}" class="img-fluid" alt="${title}" style="max-height: 70vh;">`;
    } else if (extension === 'pdf') {
        // PDF file
        modalContent.innerHTML = `<iframe src="${url}" style="width: 100%; height: 70vh; border: none;"></iframe>`;
    } else {
        // Other file types - show download link
        modalContent.innerHTML = `
            <div class="alert alert-info">
                <p>This file type cannot be previewed. Please download to view.</p>
                <a href="${url}" download class="btn btn-primary">
                    <i class="fas fa-download"></i> Download ${title}
                </a>
            </div>
        `;
    }
    
    modal.show();
}

function uploadDocument(documentType, input) {
    if (input.files && input.files[0]) {
        const file = input.files[0];
        const formData = new FormData();
        formData.append('document', file);
        formData.append('document_type', documentType);
        formData.append('_token', '{{ csrf_token() }}');

        // Disable input and show loading
        const $input = $(input);
        // Map document types to cell IDs
        const cellIdMap = {
            'aadhar_card': 'aadharCardCell',
            'pan_card': 'panCardCell',
            'qualification_certificate': 'qualificationCertificateCell'
        };
        const $cell = $('#' + cellIdMap[documentType]);
        const originalContent = $cell.html();
        $input.prop('disabled', true);
        $cell.html('<span class="text-info"><i class="fas fa-spinner fa-spin"></i> Uploading...</span>');

        // Upload to server
        $.ajax({
            url: '{{ route("doctor_portal.personal-details.upload-document") }}',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    if (typeof toastr !== 'undefined') {
                        toastr.success(response.message || 'Document uploaded successfully');
                    } else {
                        alert(response.message || 'Document uploaded successfully');
                    }
                    
                    // Update the cell with view/download buttons
                    const documentUrl = response.document_url;
                    const documentName = response.document_name || documentType.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase());
                    $cell.html(`
                        <button type="button" class="btn btn-sm doc-action-btn doc-view-btn" onclick="viewDocument('${documentUrl}', '${documentName}')">
                            <i class="fas fa-eye mr-1"></i>View
                        </button>
                        <a href="${documentUrl}" download class="btn btn-sm doc-action-btn doc-download-btn">
                            <i class="fas fa-download mr-1"></i>Download
                        </a>
                    `);
                } else {
                    if (typeof toastr !== 'undefined') {
                        toastr.error(response.message || 'Failed to upload document');
                    } else {
                        alert(response.message || 'Failed to upload document');
                    }
                    $cell.html(originalContent);
                    $input.prop('disabled', false);
                }
            },
            error: function(xhr) {
                const errorMsg = xhr.responseJSON?.message || 'Failed to upload document';
                if (typeof toastr !== 'undefined') {
                    toastr.error(errorMsg);
                } else {
                    alert(errorMsg);
                }
                $cell.html(originalContent);
                $input.prop('disabled', false);
            }
        });
    }
}

function uploadProfileImage(input) {
    if (input.files && input.files[0]) {
        const file = input.files[0];
        const formData = new FormData();
        formData.append('profile_image', file);
        formData.append('_token', '{{ csrf_token() }}');

        // Show preview
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('profileImagePreview').src = e.target.result;
        };
        reader.readAsDataURL(file);

        // Upload to server
        $.ajax({
            url: '{{ route("doctor_portal.personal-details.update-profile-image") }}',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    if (typeof toastr !== 'undefined') {
                        toastr.success(response.message || 'Photo uploaded');
                    } else {
                        alert(response.message || 'Photo uploaded');
                    }
                    if (response.pending_approval) {
                        $('#profileImagePendingMsg').show();
                    }
                    if (response.profile_image_url && !response.pending_approval) {
                        document.getElementById('profileImagePreview').src = response.profile_image_url;
                    }
                } else {
                    if (typeof toastr !== 'undefined') {
                        toastr.error(response.message || 'Failed to update profile image');
                    } else {
                        alert(response.message || 'Failed to update profile image');
                    }
                }
            },
            error: function(xhr) {
                const errorMsg = xhr.responseJSON?.message || 'Failed to update profile image';
                if (typeof toastr !== 'undefined') {
                    toastr.error(errorMsg);
                } else {
                    alert(errorMsg);
                }
            }
        });
    }
}

function saveDoctorPortalField(fieldName, inputId) {
    const input = document.getElementById(inputId);
    if (!input) {
        return;
    }
    const value = input.value.trim();

    if (!value) {
        if (typeof toastr !== 'undefined') {
            toastr.error('Please enter a value');
        } else {
            alert('Please enter a value');
        }
        return;
    }

    if (fieldName === 'email') {
        const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailPattern.test(value)) {
            if (typeof toastr !== 'undefined') {
                toastr.error('Please enter a valid email address');
            } else {
                alert('Please enter a valid email address');
            }
            return;
        }
    }

    input.disabled = true;
    const button = input.parentElement ? input.parentElement.querySelector('button') : null;
    const originalButtonHtml = button ? button.innerHTML : '';
    if (button) {
        button.disabled = true;
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
    }

    $.ajax({
        url: '{{ route("doctor_portal.personal-details.update-field") }}',
        type: 'POST',
        data: {
            field_name: fieldName,
            field_value: value,
            _token: '{{ csrf_token() }}'
        },
        success: function(response) {
            if (response.success) {
                if (typeof toastr !== 'undefined') {
                    toastr.success(response.message || 'Updated successfully');
                } else {
                    alert(response.message || 'Updated successfully');
                }
                if (fieldName === 'email' && response.field_value) {
                    input.value = response.field_value;
                }
            } else {
                if (typeof toastr !== 'undefined') {
                    toastr.error(response.message || 'Failed to update');
                } else {
                    alert(response.message || 'Failed to update');
                }
            }
            input.disabled = false;
            if (button) {
                button.disabled = false;
                button.innerHTML = originalButtonHtml;
            }
        },
        error: function(xhr) {
            const errorMsg = xhr.responseJSON?.message || (xhr.responseJSON?.errors?.field_value?.[0]) || 'Failed to update';
            if (typeof toastr !== 'undefined') {
                toastr.error(errorMsg);
            } else {
                alert(errorMsg);
            }
            input.disabled = false;
            if (button) {
                button.disabled = false;
                button.innerHTML = originalButtonHtml;
            }
        }
    });
}

</script>
<style>
    .doctor-personal-wrapper {
        overflow-y: auto;
        max-height: calc(100vh - 120px);
        background: #f5f7fb;
    }
    .doctor-personal-card {
        border: 0;
        border-radius: 14px;
        overflow: hidden;
        box-shadow: 0 10px 28px rgba(15, 23, 42, 0.08);
    }
    .doctor-personal-header {
        background: linear-gradient(90deg, #f7941d 0%, #ff7a18 100%);
        color: #fff;
        padding: 1rem 1.25rem;
    }
    .doctor-personal-header p {
        opacity: 0.95;
        font-size: 0.88rem;
    }
    .doctor-personal-body {
        max-height: calc(100vh - 250px);
        overflow-y: auto;
        padding: 1.25rem;
    }
    .profile-image-container {
        position: relative;
        display: inline-block;
    }
    .doctor-profile-image {
        width: 150px;
        height: 150px;
        border-radius: 50%;
        object-fit: cover;
        border: 4px solid #f7941d;
        box-shadow: 0 8px 20px rgba(247, 148, 29, 0.26);
        cursor: pointer;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .doctor-profile-image:hover {
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
        background: #ffffff;
        border: 1px solid #e5e7eb;
        border-radius: 10px;
        padding: 0.75rem 0.85rem;
        min-height: 82px;
        display: flex;
        flex-direction: column;
        justify-content: center;
    }
    .meta-label {
        font-size: 0.76rem;
        text-transform: uppercase;
        letter-spacing: 0.25px;
        color: #64748b;
        font-weight: 700;
        margin-bottom: 0.2rem;
    }
    .meta-value {
        color: #0f172a;
        font-weight: 600;
        line-height: 1.35;
    }
    .section-shell {
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        background: #fff;
        overflow: hidden;
        box-shadow: 0 4px 12px rgba(15, 23, 42, 0.04);
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
    @media (min-width: 992px) {
        .section-shell-body .dr-two-col-layout .dr-kv-table tbody {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 0.8rem 1rem;
        }
        .section-shell-body .dr-two-col-layout .dr-kv-table tr {
            display: block;
            margin: 0;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            overflow: hidden;
            background: #fff;
        }
        .section-shell-body .dr-two-col-layout .dr-kv-table th,
        .section-shell-body .dr-two-col-layout .dr-kv-table td {
            display: block;
            width: 100% !important;
            border: 0;
            margin: 0;
        }
        .section-shell-body .dr-two-col-layout .dr-kv-table th {
            padding: 0.62rem 0.8rem;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.25px;
            color: #475569;
            background: #f8fafc;
            border-bottom: 1px solid #e5e7eb;
        }
        .section-shell-body .dr-two-col-layout .dr-kv-table td {
            padding: 0.7rem 0.8rem;
            color: #0f172a;
            background: #fff;
            word-break: break-word;
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
    .doc-view-btn {
        background: #0ea5e9;
        color: #fff;
    }
    .doc-view-btn:hover {
        background: #0284c7;
        color: #fff;
    }
    .doc-download-btn {
        background: #22c55e;
        color: #fff;
    }
    .doc-download-btn:hover {
        background: #16a34a;
        color: #fff;
    }
    .doc-upload-btn {
        background: #f59e0b;
        color: #fff;
    }
    .doc-upload-btn:hover {
        background: #d97706;
        color: #fff;
    }
    /* Custom scrollbar styling */
    .content-wrapper::-webkit-scrollbar,
    .card-body::-webkit-scrollbar {
        width: 8px;
    }
    
    .content-wrapper::-webkit-scrollbar-track,
    .card-body::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 10px;
    }
    
    .content-wrapper::-webkit-scrollbar-thumb,
    .card-body::-webkit-scrollbar-thumb {
        background: #F7941D;
        border-radius: 10px;
    }
    
    .content-wrapper::-webkit-scrollbar-thumb:hover,
    .card-body::-webkit-scrollbar-thumb:hover {
        background: #d97706;
    }
    
    /* Firefox scrollbar */
    .content-wrapper,
    .card-body {
        scrollbar-width: thin;
        scrollbar-color: #F7941D #f1f1f1;
    }
    @media (max-width: 767px) {
        .doctor-personal-body {
            padding: 0.95rem;
        }
        .doctor-profile-image {
            width: 128px;
            height: 128px;
        }
        .document-table th {
            width: 180px;
        }
        .profile-meta-grid {
            grid-template-columns: 1fr;
        }
    }
</style>
@endsection
