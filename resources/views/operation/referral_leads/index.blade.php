@extends('operation.layouts.app')
@section('title', 'Referral Leads')

@section('header-css')
<style>
    .srl-wrapper { background: #f5f7fb; min-height: calc(100vh - 120px); }
    .srl-card {
        border: 0;
        border-radius: 14px;
        box-shadow: 0 8px 24px rgba(15, 23, 42, 0.06);
        overflow: hidden;
    }
    .srl-header {
        background: linear-gradient(180deg, #fff 0%, #fff8f1 100%);
        border-bottom: 1px solid #fee2c8;
        padding: 1rem 1.15rem;
    }
    .srl-table thead th {
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        color: #9a3412;
        background: #fff7ed;
        border-bottom: 2px solid #fed7aa !important;
        white-space: nowrap;
    }
    .srl-table tbody td {
        vertical-align: middle;
        color: #334155;
        font-size: 0.9rem;
    }
    .srl-detail {
        max-width: 260px;
        white-space: pre-wrap;
        word-break: break-word;
    }
    .srl-modal-content {
        border: 0;
        border-radius: 14px;
        overflow: hidden;
        box-shadow: 0 20px 50px rgba(15, 23, 42, 0.18);
    }
    .srl-modal-header {
        background: linear-gradient(180deg, #fff 0%, #fff8f1 100%);
        border-bottom: 1px solid #fee2c8;
    }
    .srl-label {
        display: block;
        font-size: 0.82rem;
        font-weight: 700;
        color: #475569;
        margin-bottom: 0.35rem;
    }
    .srl-input {
        height: 42px;
        border-radius: 10px;
        border: 1px solid #dbe2ea;
        font-size: 0.92rem;
    }
    .srl-textarea { height: auto; min-height: 110px; }
    .srl-input:focus {
        border-color: #F07F28;
        box-shadow: 0 0 0 0.2rem rgba(240, 127, 40, 0.15);
    }
    .srl-submit {
        background: #F07F28;
        border-color: #F07F28;
        border-radius: 10px;
        padding: 0.65rem 1rem;
        font-size: 0.95rem;
    }
    .srl-submit:hover { background: #d96d1a; border-color: #d96d1a; }
    .srl-row-rejected { background: #fff1f2 !important; }
    .srl-badge {
        display: inline-block;
        font-size: 0.72rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.03em;
        padding: 0.22rem 0.5rem;
        border-radius: 999px;
    }
    .srl-badge-pending { background: #fffbeb; color: #b45309; border: 1px solid #fde68a; }
    .srl-badge-approved { background: #ecfdf5; color: #047857; border: 1px solid #a7f3d0; }
    .srl-badge-rejected { background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca; }
</style>
@endsection

@section('main')
<div class="content-wrapper srl-wrapper">
    <section class="content">
        <div class="container-fluid pt-3">
            <div class="card srl-card">
                <div class="card-header srl-header d-flex align-items-center justify-content-between flex-wrap">
                    <div>
                        <h3 class="card-title mb-0"><i class="fas fa-share-square mr-2" style="color:#F07F28"></i>Referral Leads</h3>
                        <p class="text-muted small mb-0 mt-1">Generated leads are auto-approved and assigned to a sales executive.</p>
                        <p class="mb-0 mt-1 small font-weight-bold" style="color:#9a3412;">
                            <i class="fas fa-percent mr-1"></i>Your commission: {{ number_format($referralLeadCommissionPercent ?? 10, 2) }}% per lead
                        </p>
                    </div>
                    <button type="button" class="btn btn-warning text-dark font-weight-bold mt-2 mt-md-0" id="openOperationGenerateLeadModalBtn" data-bs-toggle="modal" data-bs-target="#operationGenerateLeadModal">
                        <i class="fas fa-plus mr-1"></i>Generate Lead
                    </button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table srl-table mb-0">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Number</th>
                                    <th>Service</th>
                                    <th>Detail (Query Remark)</th>
                                    <th>Bulk</th>
                                    <th>Commission %</th>
                                    <th>Manager status</th>
                                    <th>Assigned to</th>
                                    <th>Generated</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($items as $item)
                                    <tr class="{{ ($item->status ?? '') === 'rejected' ? 'srl-row-rejected' : '' }}">
                                        <td>{{ $item->id }}</td>
                                        <td class="font-weight-bold">{{ $item->mobile }}</td>
                                        <td>{{ $item->service ?: '—' }}</td>
                                        <td class="srl-detail">{{ $item->detail ?: '—' }}</td>
                                        <td>{{ $item->bulk_qty }}</td>
                                        <td class="text-nowrap">{{ $item->commission_percent !== null ? number_format((float) $item->commission_percent, 2).'%' : '—' }}</td>
                                        <td>
                                            @php $st = $item->status ?? 'pending'; @endphp
                                            @if($st === 'approved')
                                                <span class="srl-badge srl-badge-approved">Approved</span>
                                            @elseif($st === 'rejected')
                                                <span class="srl-badge srl-badge-rejected">Rejected</span>
                                            @else
                                                <span class="srl-badge srl-badge-pending">Pending</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($item->assignedExecutive)
                                                {{ trim(($item->assignedExecutive->f_name ?? '').' '.($item->assignedExecutive->l_name ?? '')) ?: '—' }}
                                            @else
                                                —
                                            @endif
                                        </td>
                                        <td class="text-nowrap small">{{ $item->created_at?->format('d M Y, H:i') ?? '—' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="text-center text-muted py-4">
                                            <i class="fas fa-inbox mr-1"></i>No referral leads yet. Click <strong>Generate Lead</strong> to create one.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<div class="modal fade" id="operationGenerateLeadModal" tabindex="-1" aria-labelledby="operationGenerateLeadModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content srl-modal-content">
            <div class="modal-header srl-modal-header">
                <h5 class="modal-title font-weight-bold" id="operationGenerateLeadModalLabel">
                    <i class="fas fa-plus mr-2" style="color:#F07F28"></i>Generate Lead
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="POST" action="{{ route('operation.referral_leads.store') }}" id="operationGenerateLeadForm">
                    @csrf
                    <div class="form-group mb-3">
                        <label class="srl-label">Number <span class="text-danger">*</span></label>
                        <input type="text" name="number" class="form-control srl-input @error('number') is-invalid @enderror" value="{{ old('number') }}" maxlength="10" placeholder="10-digit mobile" required pattern="[0-9]{10}" inputmode="numeric">
                        @error('number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group mb-3">
                        <label class="srl-label">Service <span class="text-muted font-weight-normal">(optional)</span></label>
                        <select name="service" class="form-control srl-input @error('service') is-invalid @enderror">
                            <option value="">Select service (optional)</option>
                            @foreach($serviceOptions as $opt)
                                <option value="{{ $opt }}" {{ old('service') === $opt ? 'selected' : '' }}>{{ $opt }}</option>
                            @endforeach
                        </select>
                        @error('service')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group mb-3">
                        <label class="srl-label">Detail <span class="text-muted font-weight-normal">(optional)</span></label>
                        <textarea name="detail" class="form-control srl-input srl-textarea @error('detail') is-invalid @enderror" rows="4" placeholder="Additional notes — shown to sales as Query Remark">{{ old('detail') }}</textarea>
                        @error('detail')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <small class="text-muted">If filled, this appears as <strong>Query Remark</strong> for the sales team.</small>
                    </div>
                    <div class="form-group mb-2">
                        <label class="srl-label">Bulk <span class="text-danger">*</span></label>
                        <input type="number" name="bulk" min="1" step="1" class="form-control srl-input @error('bulk') is-invalid @enderror" value="{{ old('bulk') }}" placeholder="Numeric quantity" required>
                        @error('bulk')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <p class="text-muted small mb-3">Lead will be auto-approved and assigned to a sales executive.</p>
                    <button type="submit" class="btn btn-block font-weight-bold text-white srl-submit w-100">Submit to sales</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@section('footer-script')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var modalEl = document.getElementById('operationGenerateLeadModal');
        if (!modalEl || typeof bootstrap === 'undefined' || !bootstrap.Modal) return;

        var modal = bootstrap.Modal.getOrCreateInstance(modalEl);
        var openBtn = document.getElementById('openOperationGenerateLeadModalBtn');
        if (openBtn) {
            openBtn.addEventListener('click', function (e) {
                e.preventDefault();
                modal.show();
            });
        }
        @if($errors->any())
            modal.show();
        @endif
    });
</script>
@endsection
