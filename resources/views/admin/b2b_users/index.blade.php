@extends('admin.layouts.app')

@section('header-css')
<style>
    .b2b-admin-page .b2b-page-title {
        font-size: 1.35rem;
        font-weight: 700;
        color: #111827;
        letter-spacing: -0.02em;
    }
    .b2b-admin-page .b2b-page-sub {
        font-size: 0.8125rem;
        color: #6b7280;
        margin-top: 0.25rem;
    }
    .b2b-admin-page .b2b-toolbar {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        padding: 0.85rem 1rem;
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 0.6rem;
        box-shadow: 0 1px 3px rgba(0,0,0,.04);
    }
    .b2b-admin-page .b2b-toolbar .btn {
        border-radius: 8px;
    }
    .b2b-admin-page .b2b-data-card {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04);
        overflow: hidden;
        height: 100%;
        display: flex;
        flex-direction: column;
    }
    .b2b-admin-page .b2b-data-card .card-header {
        background: linear-gradient(180deg, #fafbfc 0%, #ffffff 100%);
        border-bottom: 1px solid #eef2f7;
        padding: 0.85rem 1rem;
        flex-shrink: 0;
    }
    .b2b-admin-page .b2b-data-card .card-title {
        font-size: 0.95rem;
        font-weight: 700;
        color: #1f2937;
        margin: 0;
    }
    .b2b-admin-page .b2b-data-card .card-body {
        flex: 1;
        padding: 0;
        display: flex;
        flex-direction: column;
        min-height: 0;
    }
    .b2b-admin-page .b2b-table-wrap {
        overflow-x: auto;
        overflow-y: auto;
        max-height: min(72vh, 680px);
        -webkit-overflow-scrolling: touch;
        padding: 0.875rem 1rem 1.125rem;
    }
    .b2b-admin-page .b2b-table {
        margin-bottom: 0;
        font-size: 0.8125rem;
        line-height: 1.55;
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
    }
    .b2b-admin-page .b2b-table.b2b-table-main {
        min-width: 980px;
    }
    .b2b-admin-page .b2b-table.b2b-table-ref {
        min-width: 100%;
    }
    .b2b-admin-page .b2b-table thead th {
        position: sticky;
        top: 0;
        z-index: 2;
        background: #eef2f6 !important;
        color: #475569;
        font-weight: 700;
        font-size: 0.6875rem;
        text-transform: uppercase;
        letter-spacing: 0.055em;
        border-bottom: 1px solid #dbe2ea !important;
        border-top: none;
        padding: 0.8rem 0.875rem;
        vertical-align: middle;
        white-space: nowrap;
        box-shadow: 0 1px 0 rgba(255, 255, 255, 0.6) inset;
    }
    .b2b-admin-page .b2b-table thead th:first-child {
        padding-left: 1rem;
    }
    .b2b-admin-page .b2b-table thead th:last-child {
        padding-right: 1rem;
    }
    .b2b-admin-page .b2b-table tbody td {
        padding: 0.8rem 0.875rem;
        vertical-align: middle;
        border-bottom: 1px solid #eef2f6;
        color: #334155;
        transition: background-color 0.12s ease;
    }
    .b2b-admin-page .b2b-table tbody td:first-child {
        padding-left: 1rem;
    }
    .b2b-admin-page .b2b-table tbody td:last-child {
        padding-right: 1rem;
    }
    .b2b-admin-page .b2b-table tbody tr:nth-child(even) td {
        background-color: #fafbfd;
    }
    .b2b-admin-page .b2b-table tbody tr:hover td {
        background-color: #eff6ff !important;
    }
    .b2b-admin-page .b2b-table tbody tr:last-child td {
        border-bottom: none;
    }
    .b2b-admin-page .b2b-table .b2b-cell-id {
        font-variant-numeric: tabular-nums;
        font-size: 0.8125rem;
        color: #64748b;
        width: 1%;
    }
    .b2b-admin-page .b2b-table .b2b-cell-strong {
        font-weight: 600;
        color: #1e293b;
    }
    .b2b-admin-page .b2b-table .b2b-cell-truncate {
        max-width: 10rem;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .b2b-admin-page .b2b-table .b2b-cell-service {
        max-width: 9.5rem;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        color: #475569;
        font-size: 0.8rem;
    }
    .b2b-admin-page .b2b-actions {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 0.4rem;
    }
    .b2b-admin-page .b2b-table .btn-sm {
        padding: 0.28rem 0.65rem;
        font-size: 0.75rem;
        font-weight: 600;
        border-radius: 8px;
        letter-spacing: 0.01em;
        line-height: 1.3;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.06);
    }
    .b2b-admin-page .b2b-table tbody td.b2b-actions-cell {
        white-space: normal;
        min-width: 9.5rem;
    }
    .b2b-admin-page .b2b-table-wrap::-webkit-scrollbar {
        width: 9px;
        height: 9px;
    }
    .b2b-admin-page .b2b-table-wrap::-webkit-scrollbar-track {
        background: #f8fafc;
        border-radius: 6px;
    }
    .b2b-admin-page .b2b-table-wrap::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 6px;
    }
    .b2b-admin-page .b2b-table-wrap::-webkit-scrollbar-thumb:hover {
        background: #94a3b8;
    }
    .b2b-admin-page .b2b-chip-count {
        font-size: 0.7rem;
        font-weight: 600;
        background: #f1f5f9;
        color: #475569;
        border-radius: 999px;
        padding: 0.2rem 0.55rem;
        margin-left: 0.35rem;
    }
    .b2b-admin-page .b2b-table .cell-muted {
        color: #94a3b8;
        font-size: 0.76rem;
    }
    .b2b-admin-page .b2b-table .commission-cell {
        font-weight: 600;
        color: #0f766e;
    }
</style>
@endsection

@section('main')
<div class="content-wrapper b2b-admin-page">
    <section class="content pt-3">
        <div class="container-fluid">
            <div class="mb-3">
                <h1 class="b2b-page-title mb-0">B2B &amp; reference users</h1>
                <p class="b2b-page-sub mb-0">Manage partner companies and referrers. Referrers appear in the company “Referral user” dropdown and sign in via OTP.</p>
            </div>

            <div class="b2b-toolbar mb-3">
                <button type="button" class="btn btn-sm btn-outline-primary font-weight-semibold px-3" data-toggle="modal" data-target="#createB2BReferenceUserModal" data-bs-toggle="modal" data-bs-target="#createB2BReferenceUserModal">
                    <i class="fas fa-user-tag mr-1"></i>Add B2B Reference User
                </button>
                <button
                    type="button"
                    id="openCreateB2BUserModalBtn"
                    class="btn btn-sm btn-warning font-weight-semibold px-3 text-dark"
                    data-toggle="modal"
                    data-target="#createB2BUserModal"
                    data-bs-toggle="modal"
                    data-bs-target="#createB2BUserModal"
                >
                    <i class="fas fa-building mr-1"></i>Create B2B User
                </button>
            </div>

            <div class="row align-items-start">
                {{-- Column 1: B2B Users (wider) --}}
                <div class="col-xl-7 col-lg-12 mb-3 mb-xl-0">
                    <div class="b2b-data-card">
                        <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
                            <h3 class="card-title mb-0 d-flex align-items-center">
                                <i class="fas fa-users mr-2 text-warning" style="opacity:.9;"></i>
                                B2B Users
                                <span class="b2b-chip-count">{{ $items->count() }}</span>
                            </h3>
                        </div>
                        <div class="card-body">
                            <div class="b2b-table-wrap">
                                <table class="table b2b-table b2b-table-main">
                                    <thead>
                                        <tr>
                                            <th>Id</th>
                                            <th>Name</th>
                                            <th>Company</th>
                                            <th>Mobile</th>
                                            <th>Referral User</th>
                                            <th>Commission %</th>
                                            <th>Service Requirement</th>
                                            <th>Created</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($items as $item)
                                            <tr>
                                                <td class="b2b-cell-id font-weight-semibold text-muted text-nowrap">#{{ $item->id }}</td>
                                                <td class="b2b-cell-strong">{{ $item->name }}</td>
                                                <td class="b2b-cell-truncate" title="{{ $item->company_name }}">{{ $item->company_name ?: '—' }}</td>
                                                <td class="text-nowrap">{{ $item->mobile }}</td>
                                                <td class="small text-secondary b2b-cell-truncate" title="{{ $item->referenceUser ? ($item->referenceUser->name.' · '.$item->referenceUser->mobile) : '' }}">{{ $item->referenceUser ? ($item->referenceUser->name.' · '.$item->referenceUser->mobile) : '—' }}</td>
                                                <td class="commission-cell text-nowrap">{{ $item->commission_percent !== null ? $item->commission_percent.'%' : '—' }}</td>
                                                <td class="b2b-cell-service" title="{{ $item->service_requirement }}">{{ \Illuminate\Support\Str::limit($item->service_requirement, 32) }}</td>
                                                <td class="cell-muted text-nowrap">{{ $item->created_at?->format('d M Y, h:i A') }}</td>
                                                <td class="b2b-actions-cell align-middle">
                                                    <div class="b2b-actions">
                                                        <button type="button" class="btn btn-sm btn-info py-1" data-toggle="modal" data-target="#viewB2BUserModal{{ $item->id }}" data-bs-toggle="modal" data-bs-target="#viewB2BUserModal{{ $item->id }}">View</button>
                                                        <button type="button" class="btn btn-sm btn-primary py-1" data-toggle="modal" data-target="#editB2BUserModal{{ $item->id }}" data-bs-toggle="modal" data-bs-target="#editB2BUserModal{{ $item->id }}">Edit</button>
                                                        <form method="POST" action="{{ route('admin.b2b_users.destroy', $item) }}" class="d-inline m-0" onsubmit="return confirm('Delete this B2B user? All leads they created in the B2B portal will be permanently removed.');">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="btn btn-sm btn-danger py-1">Delete</button>
                                                        </form>
                                                    </div>
                                                </td>
                                            </tr>

                                            <div class="modal fade" id="viewB2BUserModal{{ $item->id }}" tabindex="-1" role="dialog" aria-hidden="true">
                                                <div class="modal-dialog modal-lg" role="document">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">B2B User Details</h5>
                                                            <button type="button" class="close" data-dismiss="modal" data-bs-dismiss="modal" aria-label="Close">
                                                                <span aria-hidden="true">&times;</span>
                                                            </button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <div class="row">
                                                                <div class="col-md-6 mb-2"><strong>Name:</strong> {{ $item->name ?: '—' }}</div>
                                                                <div class="col-md-6 mb-2"><strong>Company:</strong> {{ $item->company_name ?: '—' }}</div>
                                                                <div class="col-md-6 mb-2"><strong>Mobile:</strong> {{ $item->mobile ?: '—' }}</div>
                                                                <div class="col-md-6 mb-2"><strong>Referral user:</strong> {{ $item->referenceUser ? ($item->referenceUser->name.' ('.$item->referenceUser->mobile.')') : '—' }}</div>
                                                                <div class="col-md-6 mb-2"><strong>Commission %:</strong> {{ $item->commission_percent !== null ? $item->commission_percent : '—' }}</div>
                                                                <div class="col-md-6 mb-2"><strong>Service Requirement:</strong> {{ $item->service_requirement ?: '—' }}</div>
                                                                <div class="col-md-12 mb-2"><strong>Bank Account Details:</strong> {{ $item->bank_account_details ?: '—' }}</div>
                                                                <div class="col-md-6 mb-2"><strong>Account Holder Name:</strong> {{ $item->account_holder_name ?: '—' }}</div>
                                                                <div class="col-md-6 mb-2"><strong>Bank Name:</strong> {{ $item->bank_name ?: '—' }}</div>
                                                                <div class="col-md-6 mb-2"><strong>IFSC Code:</strong> {{ $item->ifsc_code ?: '—' }}</div>
                                                                <div class="col-md-6 mb-2"><strong>Account Number:</strong> {{ $item->account_number ?: '—' }}</div>
                                                                <div class="col-md-12 mb-2">
                                                                    <strong>Company Registration:</strong>
                                                                    @if($item->company_registration_file)
                                                                        <a href="{{ asset('storage/'.$item->company_registration_file) }}" target="_blank">View file</a>
                                                                    @else
                                                                        —
                                                                    @endif
                                                                </div>
                                                                <div class="col-md-12 mb-2">
                                                                    <strong>Company GST:</strong>
                                                                    @if($item->company_gst_file)
                                                                        <a href="{{ asset('storage/'.$item->company_gst_file) }}" target="_blank">View file</a>
                                                                    @else
                                                                        —
                                                                    @endif
                                                                </div>
                                                                <div class="col-md-12 mb-2">
                                                                    <strong>MOU:</strong>
                                                                    @if($item->mou_file)
                                                                        <a href="{{ asset('storage/'.$item->mou_file) }}" target="_blank">View file</a>
                                                                    @else
                                                                        —
                                                                    @endif
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="modal fade" id="editB2BUserModal{{ $item->id }}" tabindex="-1" role="dialog" aria-hidden="true">
                                                <div class="modal-dialog modal-xl" role="document">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">Edit B2B User</h5>
                                                            <button type="button" class="close" data-dismiss="modal" data-bs-dismiss="modal" aria-label="Close">
                                                                <span aria-hidden="true">&times;</span>
                                                            </button>
                                                        </div>
                                                        <form method="POST" action="{{ route('admin.b2b_users.update', $item->id) }}" enctype="multipart/form-data">
                                                            @csrf
                                                            <div class="modal-body">
                                                                <div class="form-row">
                                                                    <div class="form-group col-md-4">
                                                                        <label>Name</label>
                                                                        <input type="text" name="name" class="form-control" required value="{{ $item->name }}">
                                                                    </div>
                                                                    <div class="form-group col-md-4">
                                                                        <label>Company Name</label>
                                                                        <input type="text" name="company_name" class="form-control" required value="{{ $item->company_name }}">
                                                                    </div>
                                                                    <div class="form-group col-md-4">
                                                                        <label>Mobile Number</label>
                                                                        <input type="text" name="mobile" class="form-control" required maxlength="10" value="{{ $item->mobile }}">
                                                                    </div>
                                                                    <div class="form-group col-md-4">
                                                                        <label>Referral user (reference)</label>
                                                                        <select name="b2b_reference_user_id" class="form-control" required>
                                                                            <option value="">Select reference user</option>
                                                                            @foreach($referenceUsers as $refOption)
                                                                                <option value="{{ $refOption['id'] }}" {{ (int) $item->b2b_reference_user_id === (int) $refOption['id'] ? 'selected' : '' }}>
                                                                                    {{ $refOption['name'] }}
                                                                                </option>
                                                                            @endforeach
                                                                        </select>
                                                                    </div>
                                                                    <div class="form-group col-md-4">
                                                                        <label>Commission in percent</label>
                                                                        <input type="number" step="0.01" min="0" max="100" name="commission_percent" class="form-control" required value="{{ $item->commission_percent }}">
                                                                    </div>
                                                                    <div class="form-group col-md-4">
                                                                        <label>Service Requirement</label>
                                                                        <select name="service_requirement" class="form-control" required>
                                                                            @foreach($serviceOptions as $serviceOption)
                                                                                <option value="{{ $serviceOption['name'] }}" {{ $item->service_requirement === $serviceOption['name'] ? 'selected' : '' }}>
                                                                                    {{ $serviceOption['name'] }} ({{ $serviceOption['source'] === 'service' ? 'Service' : 'Doctor consultation service' }})
                                                                                </option>
                                                                            @endforeach
                                                                        </select>
                                                                    </div>
                                                                    <div class="form-group col-md-12">
                                                                        <label>Bank Account Details (Optional)</label>
                                                                        <textarea name="bank_account_details" class="form-control" rows="2">{{ $item->bank_account_details }}</textarea>
                                                                    </div>
                                                                    <div class="form-group col-md-4">
                                                                        <label>Account Holder Name (Optional)</label>
                                                                        <input type="text" name="account_holder_name" class="form-control" value="{{ $item->account_holder_name }}">
                                                                    </div>
                                                                    <div class="form-group col-md-4">
                                                                        <label>Bank Name (Optional)</label>
                                                                        <input type="text" name="bank_name" class="form-control" value="{{ $item->bank_name }}">
                                                                    </div>
                                                                    <div class="form-group col-md-4">
                                                                        <label>IFSC Code (Optional)</label>
                                                                        <input type="text" name="ifsc_code" class="form-control" value="{{ $item->ifsc_code }}">
                                                                    </div>
                                                                    <div class="form-group col-md-4">
                                                                        <label>Account Number (Optional)</label>
                                                                        <input type="text" name="account_number" class="form-control" value="{{ $item->account_number }}">
                                                                    </div>
                                                                    <div class="form-group col-md-4">
                                                                        <label>Company Registration (pdf/image, Optional)</label>
                                                                        <input type="file" name="company_registration_file" class="form-control-file" accept=".pdf,image/*">
                                                                    </div>
                                                                    <div class="form-group col-md-4">
                                                                        <label>Company GST (pdf/image, Optional)</label>
                                                                        <input type="file" name="company_gst_file" class="form-control-file" accept=".pdf,image/*">
                                                                    </div>
                                                                    <div class="form-group col-md-4">
                                                                        <label>MOU (pdf/image, Optional)</label>
                                                                        <input type="file" name="mou_file" class="form-control-file" accept=".pdf,image/*">
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-dismiss="modal" data-bs-dismiss="modal">Close</button>
                                                                <button type="submit" class="btn btn-primary">Update</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        @empty
                                            <tr>
                                                <td colspan="9" class="text-center text-muted py-4">No B2B users yet.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Column 2: B2B Reference Users --}}
                <div class="col-xl-5 col-lg-12">
                    <div class="b2b-data-card">
                        <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
                            <h3 class="card-title mb-0 d-flex align-items-center">
                                <i class="fas fa-handshake mr-2 text-primary" style="opacity:.85;"></i>
                                B2B Reference Users
                                <span class="b2b-chip-count">{{ $referenceUserRecords->count() }}</span>
                            </h3>
                        </div>
                        <div class="card-body">
                            <div class="b2b-table-wrap">
                                <table class="table b2b-table b2b-table-ref">
                                    <thead>
                                        <tr>
                                            <th>Id</th>
                                            <th>Name</th>
                                            <th>Mobile</th>
                                            <th>Created</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($referenceUserRecords as $ref)
                                            <tr>
                                                <td class="b2b-cell-id font-weight-semibold text-muted text-nowrap">#{{ $ref->id }}</td>
                                                <td class="b2b-cell-strong">{{ $ref->name }}</td>
                                                <td class="text-nowrap">{{ $ref->mobile }}</td>
                                                <td class="cell-muted text-nowrap">{{ $ref->created_at?->format('d M Y, h:i A') }}</td>
                                                <td class="b2b-actions-cell align-middle">
                                                    <div class="b2b-actions">
                                                        <form method="POST" action="{{ route('admin.b2b_reference_users.destroy', $ref) }}" class="d-inline m-0" onsubmit="return confirm('Delete this reference user? Linked B2B companies will lose their referral assignment.');">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="btn btn-sm btn-outline-danger py-1">Delete</button>
                                                        </form>
                                                    </div>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="5" class="text-center text-muted py-4">No reference users yet.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Modals --}}
            <div class="modal fade" id="createB2BReferenceUserModal" tabindex="-1" role="dialog" aria-labelledby="createB2BReferenceUserModalLabel" aria-hidden="true">
                <div class="modal-dialog" role="document">
                    <div class="modal-content rounded-lg overflow-hidden border-0 shadow">
                        <div class="modal-header border-bottom-0 pb-0">
                            <h5 class="modal-title font-weight-bold" id="createB2BReferenceUserModalLabel">Create B2B Reference User</h5>
                            <button type="button" class="close" data-dismiss="modal" data-bs-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <form method="POST" action="{{ route('admin.b2b_reference_users.store') }}">
                            @csrf
                            <div class="modal-body pt-2">
                                <p class="text-muted small mb-3">OTP login same as portal home. They see leads from B2B partners assigned to them.</p>
                                <div class="form-group">
                                    <label class="font-weight-semibold text-secondary small">Name</label>
                                    <input type="text" name="name" class="form-control rounded-lg" required maxlength="255" value="{{ old('name') }}" placeholder="Full name">
                                </div>
                                <div class="form-group mb-0">
                                    <label class="font-weight-semibold text-secondary small">Mobile (10 digits)</label>
                                    <input type="text" name="mobile" class="form-control rounded-lg" required maxlength="10" value="{{ old('mobile') }}" placeholder="9876543210">
                                </div>
                            </div>
                            <div class="modal-footer border-top bg-light">
                                <button type="button" class="btn btn-outline-secondary rounded-lg" data-dismiss="modal" data-bs-dismiss="modal">Close</button>
                                <button type="submit" class="btn btn-primary rounded-lg px-4">Save reference user</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="modal fade" id="createB2BUserModal" tabindex="-1" role="dialog" aria-labelledby="createB2BUserModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-xl" role="document">
                    <div class="modal-content rounded-lg border-0 shadow">
                        <div class="modal-header border-bottom">
                            <h5 class="modal-title font-weight-bold" id="createB2BUserModalLabel">Create B2B User</h5>
                            <button type="button" class="close" data-dismiss="modal" data-bs-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <form method="POST" action="{{ route('admin.b2b_users.store') }}" enctype="multipart/form-data">
                            @csrf
                            <div class="modal-body">
                                <div class="form-row">
                                    <div class="form-group col-md-4">
                                        <label>Name</label>
                                        <input type="text" name="name" class="form-control" required value="{{ old('name') }}">
                                    </div>
                                    <div class="form-group col-md-4">
                                        <label>Company Name</label>
                                        <input type="text" name="company_name" class="form-control" required value="{{ old('company_name') }}">
                                    </div>
                                    <div class="form-group col-md-4">
                                        <label>Mobile Number</label>
                                        <input type="text" name="mobile" class="form-control" required maxlength="10" value="{{ old('mobile') }}">
                                    </div>
                                    <div class="form-group col-md-4">
                                        <label>Referral user (reference)</label>
                                        <select name="b2b_reference_user_id" class="form-control" required>
                                            <option value="">Select reference user</option>
                                            @foreach($referenceUsers as $refOption)
                                                <option value="{{ $refOption['id'] }}" {{ (string) old('b2b_reference_user_id') === (string) $refOption['id'] ? 'selected' : '' }}>
                                                    {{ $refOption['name'] }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="form-group col-md-4">
                                        <label>Commission in percent</label>
                                        <input type="number" step="0.01" min="0" max="100" name="commission_percent" class="form-control" required value="{{ old('commission_percent') }}">
                                    </div>
                                    <div class="form-group col-md-4">
                                        <label>Service Requirement</label>
                                        <select name="service_requirement" class="form-control" required>
                                            <option value="">Select service</option>
                                            @foreach($serviceOptions as $serviceOption)
                                                <option value="{{ $serviceOption['name'] }}" {{ old('service_requirement') === $serviceOption['name'] ? 'selected' : '' }}>
                                                    {{ $serviceOption['name'] }} ({{ $serviceOption['source'] === 'service' ? 'Service' : 'Doctor consultation service' }})
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="form-group col-md-12">
                                        <label>Bank Account Details (Optional)</label>
                                        <textarea name="bank_account_details" class="form-control" rows="2">{{ old('bank_account_details') }}</textarea>
                                    </div>
                                    <div class="form-group col-md-4">
                                        <label>Account Holder Name (Optional)</label>
                                        <input type="text" name="account_holder_name" class="form-control" value="{{ old('account_holder_name') }}">
                                    </div>
                                    <div class="form-group col-md-4">
                                        <label>Bank Name (Optional)</label>
                                        <input type="text" name="bank_name" class="form-control" value="{{ old('bank_name') }}">
                                    </div>
                                    <div class="form-group col-md-4">
                                        <label>IFSC Code (Optional)</label>
                                        <input type="text" name="ifsc_code" class="form-control" value="{{ old('ifsc_code') }}">
                                    </div>
                                    <div class="form-group col-md-4">
                                        <label>Account Number (Optional)</label>
                                        <input type="text" name="account_number" class="form-control" value="{{ old('account_number') }}">
                                    </div>
                                    <div class="form-group col-md-4">
                                        <label>Company Registration (pdf/image, Optional)</label>
                                        <input type="file" name="company_registration_file" class="form-control-file" accept=".pdf,image/*">
                                    </div>
                                    <div class="form-group col-md-4">
                                        <label>Company GST (pdf/image, Optional)</label>
                                        <input type="file" name="company_gst_file" class="form-control-file" accept=".pdf,image/*">
                                    </div>
                                    <div class="form-group col-md-4">
                                        <label>MOU (pdf/image, Optional)</label>
                                        <input type="file" name="mou_file" class="form-control-file" accept=".pdf,image/*">
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer border-top bg-light">
                                <button type="button" class="btn btn-secondary rounded-lg" data-dismiss="modal" data-bs-dismiss="modal">Close</button>
                                <button type="submit" class="btn btn-warning text-dark rounded-lg px-4 font-weight-bold">Create B2B User</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
@endsection

@section('footer-script')
<script>
    // Fallback open handler for mixed Bootstrap/AdminLTE setups.
    $(document).on('click', '[data-target], [data-bs-target]', function () {
        const targetSelector = $(this).attr('data-bs-target') || $(this).attr('data-target');
        if (!targetSelector || targetSelector.charAt(0) !== '#') return;
        const modalEl = document.querySelector(targetSelector);
        if (!modalEl) return;

        if (window.bootstrap && window.bootstrap.Modal) {
            const instance = window.bootstrap.Modal.getOrCreateInstance(modalEl);
            instance.show();
            return;
        }

        if (typeof $(targetSelector).modal === 'function') {
            $(targetSelector).modal('show');
        }
    });
</script>
@endsection
