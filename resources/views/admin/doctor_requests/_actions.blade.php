@php
    /** @var \App\Models\DoctorRequest $row */
    $st = strtolower((string) ($row->approval_status ?? ''));
    if (! in_array($st, ['approved', 'pending', 'rejected'], true)) {
        $st = 'pending';
    }
    $pendingPriceChanges = (int) ($row->pending_price_change_count ?? 0);
@endphp
<div class="doctor-req-actions d-inline-flex flex-nowrap align-items-center justify-content-end" role="group" aria-label="Row actions">
    @if($pendingPriceChanges > 0)
        <button type="button" class="btn btn-sm doctor-req-icon-btn btn-warning doctor-req-price-alert-btn" title="Price change request — review" aria-label="Price change requests" onclick="openDoctorPriceChangeRequests({{ $row->id }})">
            <i class="fas fa-rupee-sign"></i>
            <span class="doctor-req-price-count">{{ $pendingPriceChanges }}</span>
        </button>
    @endif
    <button type="button" class="btn btn-sm doctor-req-icon-btn btn-outline-secondary" title="View registration" aria-label="View registration" onclick="openDoctorViewModal({{ $row->id }})"><i class="fas fa-eye"></i></button>
    <button type="button" class="btn btn-sm doctor-req-icon-btn btn-outline-primary" title="Consultation pricing" aria-label="Pricing" onclick="openDoctorPricing({{ $row->id }})"><i class="fas fa-tags"></i></button>
    <div class="dropdown doctor-req-decision-dd">
        <button class="btn btn-sm doctor-req-dd-btn btn-outline-secondary dropdown-toggle doctor-req-dd-toggle" type="button" id="doctorReqDd{{ $row->id }}" data-bs-toggle="dropdown" data-bs-auto-close="true" aria-expanded="false" title="Set status: approve, pending, or reject" aria-haspopup="true" aria-label="Open status menu">
            <span class="doctor-req-dd-inner" aria-hidden="true">
                <i class="fas fa-gavel"></i>
                <i class="fas fa-chevron-down doctor-req-dd-chevron"></i>
            </span>
        </button>
        <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0" aria-labelledby="doctorReqDd{{ $row->id }}">
            <li><h6 class="dropdown-header text-muted mb-0 py-2">Set status</h6></li>
            <li>
                <button type="button" class="dropdown-item text-success d-flex align-items-center justify-content-between" @if($st === 'approved') disabled @endif onclick="if(!this.disabled) setDoctorStatus({{ $row->id }}, 'approved')">
                    <span><i class="fas fa-check fa-fw mr-1"></i>Approve</span>
                    @if($st === 'approved')<span class="badge badge-light text-secondary">Current</span>@endif
                </button>
            </li>
            <li>
                <button type="button" class="dropdown-item text-warning d-flex align-items-center justify-content-between" @if($st === 'pending') disabled @endif onclick="if(!this.disabled) setDoctorStatus({{ $row->id }}, 'pending')">
                    <span><i class="fas fa-clock fa-fw mr-1"></i>Pending</span>
                    @if($st === 'pending')<span class="badge badge-light text-secondary">Current</span>@endif
                </button>
            </li>
            <li>
                <button type="button" class="dropdown-item text-danger d-flex align-items-center justify-content-between" @if($st === 'rejected') disabled @endif onclick="if(!this.disabled) setDoctorStatus({{ $row->id }}, 'rejected')">
                    <span><i class="fas fa-times fa-fw mr-1"></i>Reject</span>
                    @if($st === 'rejected')<span class="badge badge-light text-secondary">Current</span>@endif
                </button>
            </li>
        </ul>
    </div>
</div>
