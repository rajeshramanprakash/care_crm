@php
    /** @var \App\Models\DoctorRequest $doctor */
    $uploadUrl = $uploadUrl ?? null;
    $pendingUrl = $pendingUrl ?? null;
    $approvedUrl = $approvedUrl ?? null;
    $status = (string) ($doctor->profile_image_status ?? 'pending_review');
    $statusLabel = match ($status) {
        'approved' => 'Approved',
        'rejected' => 'Rejected',
        default => 'Pending admin review',
    };
@endphp
<div class="dr-profile-image-admin border rounded p-3 mt-3 bg-white"
     data-dr-id="{{ (int) $doctor->id }}"
     data-generate-url="{{ route('admin.doctor_requests.profile_image_generate', $doctor) }}"
     data-approve-url="{{ route('admin.doctor_requests.profile_image_approve', $doctor) }}">
    <h6 class="font-weight-bold mb-1">Profile photo — Carelix coat (Gemini)</h6>
    <p class="small text-muted mb-3">Generate a branded lab-coat portrait from the doctor&apos;s uploaded photo, preview it, then approve to publish on CareWeb.</p>

    <div class="row">
        <div class="col-md-4 mb-3 mb-md-0">
            <p class="small font-weight-bold text-secondary mb-1">Doctor upload</p>
            @if($uploadUrl)
                <a href="{{ $uploadUrl }}" target="_blank" rel="noopener">
                    <img src="{{ $uploadUrl }}" alt="Original upload" class="img-fluid rounded border" id="drProfileUploadImg" style="max-height:200px;object-fit:cover;width:100%;">
                </a>
            @else
                <p class="text-muted small mb-0">No upload yet.</p>
            @endif
        </div>
        <div class="col-md-8">
            <p class="small mb-2">
                Status: <span class="badge badge-{{ $status === 'approved' ? 'success' : ($status === 'rejected' ? 'danger' : 'warning') }}" id="drProfileImageStatusBadge">{{ $statusLabel }}</span>
            </p>
            @if($approvedUrl)
                <p class="small font-weight-bold text-secondary mb-1">Live on website</p>
                <img src="{{ $approvedUrl }}" alt="Approved profile" class="img-fluid rounded border mb-2" style="max-height:160px;" id="drProfileApprovedImg">
            @endif
            <p class="small font-weight-bold text-secondary mb-1">Generated preview</p>
            <div id="drProfilePendingWrap" class="{{ $pendingUrl ? '' : 'd-none' }}">
                <img src="{{ $pendingUrl ?? '' }}" alt="Generated preview" class="img-fluid rounded border mb-2" style="max-height:220px;" id="drProfilePendingImg">
            </div>
            <p class="small text-muted mb-2 {{ $pendingUrl ? 'd-none' : '' }}" id="drProfilePendingEmpty">No preview yet. Click generate after the doctor uploads a photo.</p>

            <div class="d-flex flex-wrap gap-2 align-items-center">
                <button type="button" class="btn btn-info btn-sm" id="drProfileGenerateBtn" @disabled(! $uploadUrl)>
                    <i class="fas fa-magic"></i> Generate Carelix coat preview
                </button>
                <button type="button" class="btn btn-success btn-sm {{ $pendingUrl ? '' : 'd-none' }}" id="drProfileApproveBtn">
                    <i class="fas fa-check"></i> Approve profile photo
                </button>
            </div>
            <p class="small dr-profile-image-admin-msg mt-2 mb-0" id="drProfileImageAdminMsg"></p>
        </div>
    </div>
</div>
