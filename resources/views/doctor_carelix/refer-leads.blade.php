@extends('doctor_carelix.layouts.app')
@section('title', 'Refer Lead')

@section('main')
@php
    $openReferModalOnError = $errors->any();
@endphp
<div class="content-wrapper dr-refer-wrapper">
    <section class="content">
        <div class="container-fluid pt-3">
            @if(session('status'))
                @php
                    $flashStatus = session('status');
                    $flashMessage = is_array($flashStatus) ? ($flashStatus['message'] ?? '') : (string) $flashStatus;
                @endphp
                @if($flashMessage !== '')
                <div class="alert alert-success alert-dismissible fade show">
                    {{ $flashMessage }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                @endif
            @endif

            <div class="card dr-refer-card">
                <div class="card-header dr-refer-header d-flex align-items-center justify-content-between flex-wrap">
                    <div>
                        <h3 class="card-title mb-0"><i class="fas fa-user-plus mr-2" style="color:#F07F28"></i>Refer Lead</h3>
                        <p class="text-muted small mb-0 mt-1">Submit leads to sales. They are assigned to an executive automatically.</p>
                    </div>
                    <button type="button" class="btn btn-warning text-dark font-weight-bold mt-2 mt-md-0" id="openDoctorReferLeadModalBtn" data-bs-toggle="modal" data-bs-target="#doctorReferLeadModal">
                        <i class="fas fa-plus mr-1"></i>Refer Lead
                    </button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table dr-refer-table mb-0">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Number</th>
                                    <th>Service</th>
                                    <th>Detail (Query Remark)</th>
                                    <th>Bulk</th>
                                    <th>Commission %</th>
                                    <th>Submitted</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($items as $item)
                                    <tr>
                                        <td>{{ $item->id }}</td>
                                        <td class="font-weight-bold">{{ $item->mobile }}</td>
                                        <td>{{ $item->service ?: '—' }}</td>
                                        <td class="dr-refer-detail">{{ $item->detail ?: '—' }}</td>
                                        <td>{{ $item->bulk_qty }}</td>
                                        <td>{{ $item->commission_percent !== null ? number_format((float) $item->commission_percent, 2).'%' : '—' }}</td>
                                        <td class="text-nowrap small">{{ $item->created_at?->format('d M Y, H:i') ?? '—' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center text-muted py-4">
                                            <i class="fas fa-inbox mr-1"></i>No referred leads yet. Click <strong>Refer Lead</strong> to submit one.
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

{{-- Modal outside content-wrapper so Bootstrap backdrop is not clipped --}}
<div class="modal fade" id="doctorReferLeadModal" tabindex="-1" aria-labelledby="doctorReferLeadModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content dr-refer-modal-content">
            <div class="modal-header dr-refer-modal-header">
                <h5 class="modal-title font-weight-bold" id="doctorReferLeadModalLabel">
                    <i class="fas fa-user-plus mr-2" style="color:#F07F28"></i>Refer Lead
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="POST" action="{{ route('doctor_portal.refer-leads.store') }}" id="doctorReferLeadForm">
                    @csrf
                    <div class="form-group mb-3">
                        <label class="dr-refer-label">Number <span class="text-danger">*</span></label>
                        <input type="text" name="number" class="form-control dr-refer-input @error('number') is-invalid @enderror" value="{{ old('number') }}" maxlength="10" placeholder="10-digit mobile" required pattern="[0-9]{10}" inputmode="numeric">
                        @error('number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group mb-3">
                        <label class="dr-refer-label">Service <span class="text-muted font-weight-normal">(optional)</span></label>
                        <select name="service" class="form-control dr-refer-input @error('service') is-invalid @enderror">
                            <option value="">Select service (optional)</option>
                            @foreach($serviceOptions as $opt)
                                <option value="{{ $opt }}" {{ old('service') === $opt ? 'selected' : '' }}>{{ $opt }}</option>
                            @endforeach
                        </select>
                        @error('service')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group mb-3">
                        <label class="dr-refer-label">Detail <span class="text-muted font-weight-normal">(optional)</span></label>
                        <textarea name="detail" class="form-control dr-refer-input dr-refer-textarea @error('detail') is-invalid @enderror" rows="4" placeholder="Additional notes — shown to sales as Query Remark">{{ old('detail') }}</textarea>
                        @error('detail')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <small class="text-muted">If filled, this appears as <strong>Query Remark</strong> for the sales team.</small>
                    </div>
                    <div class="form-group mb-2">
                        <label class="dr-refer-label">Bulk <span class="text-danger">*</span></label>
                        <input type="number" name="bulk" min="1" step="1" class="form-control dr-refer-input @error('bulk') is-invalid @enderror" value="{{ old('bulk') }}" placeholder="Numeric quantity" required>
                        @error('bulk')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <p class="text-muted small mb-3">Lead goes directly to sales and is assigned to an executive automatically.</p>
                    <button type="submit" class="btn btn-block font-weight-bold text-white dr-refer-submit w-100">Submit to sales</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .dr-refer-wrapper { background: #f5f7fb; min-height: calc(100vh - 120px); }
    .dr-refer-card {
        border: 0;
        border-radius: 14px;
        box-shadow: 0 8px 24px rgba(15, 23, 42, 0.06);
        overflow: hidden;
    }
    .dr-refer-header {
        background: linear-gradient(180deg, #fff 0%, #fff8f1 100%);
        border-bottom: 1px solid #fee2c8;
        padding: 1rem 1.15rem;
    }
    .dr-refer-table thead th {
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        color: #9a3412;
        background: #fff7ed;
        border-bottom: 2px solid #fed7aa !important;
        white-space: nowrap;
    }
    .dr-refer-table tbody td {
        vertical-align: middle;
        color: #334155;
        font-size: 0.9rem;
    }
    .dr-refer-detail {
        max-width: 280px;
        white-space: pre-wrap;
        word-break: break-word;
    }
    .dr-refer-modal-content {
        border: 0;
        border-radius: 14px;
        overflow: hidden;
        box-shadow: 0 20px 50px rgba(15, 23, 42, 0.18);
    }
    .dr-refer-modal-header {
        background: linear-gradient(180deg, #fff 0%, #fff8f1 100%);
        border-bottom: 1px solid #fee2c8;
    }
    .dr-refer-label {
        display: block;
        font-size: 0.82rem;
        font-weight: 700;
        color: #475569;
        margin-bottom: 0.35rem;
    }
    .dr-refer-input {
        height: 42px;
        border-radius: 10px;
        border: 1px solid #dbe2ea;
        font-size: 0.92rem;
    }
    .dr-refer-textarea {
        height: auto;
        min-height: 110px;
    }
    .dr-refer-input:focus {
        border-color: #F07F28;
        box-shadow: 0 0 0 0.2rem rgba(240, 127, 40, 0.15);
    }
    .dr-refer-submit {
        background: #F07F28;
        border-color: #F07F28;
        border-radius: 10px;
        padding: 0.65rem 1rem;
        font-size: 0.95rem;
    }
    .dr-refer-submit:hover {
        background: #d96d1a;
        border-color: #d96d1a;
    }
</style>
@endpush

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var modalEl = document.getElementById('doctorReferLeadModal');
        if (!modalEl || typeof bootstrap === 'undefined' || !bootstrap.Modal) return;

        var modal = bootstrap.Modal.getOrCreateInstance(modalEl);

        var openBtn = document.getElementById('openDoctorReferLeadModalBtn');
        if (openBtn) {
            openBtn.addEventListener('click', function (e) {
                e.preventDefault();
                modal.show();
            });
        }

        @if($openReferModalOnError)
            modal.show();
        @endif
    });
</script>
@endpush
