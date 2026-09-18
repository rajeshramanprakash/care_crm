@extends('admin.layouts.app')

@section('header-css')
<style>
    .bpa-page-title { font-size: 1.35rem; font-weight: 700; color: #111827; }
    .bpa-toolbar .btn-create { font-weight: 600; }
    .bpa-table thead th { font-size: 0.8rem; color: #6b7280; white-space: nowrap; }

    .bpa-modal .modal-content { border: 0; border-radius: 12px; box-shadow: 0 20px 50px rgba(15, 23, 42, 0.15); overflow: hidden; }
    .bpa-modal .modal-header { background: linear-gradient(180deg, #fff 0%, #f8fafc 100%); border-bottom: 1px solid #e5e7eb; padding: 1rem 1.25rem; }
    .bpa-modal .modal-title { font-weight: 700; color: #111827; font-size: 1.1rem; }
    .bpa-modal .modal-body { padding: 1rem 1.25rem 0.5rem; background: #f8fafc; max-height: calc(100vh - 220px); overflow-y: auto; }
    .bpa-modal .modal-footer { background: #fff; border-top: 1px solid #e5e7eb; padding: 0.85rem 1.25rem; }

    .bpa-form-section {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 10px;
        padding: 1rem 1.1rem;
        margin-bottom: 1rem;
    }
    .bpa-form-section-title {
        font-size: 0.8rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        color: #475569;
        margin: 0 0 0.85rem;
        padding-bottom: 0.5rem;
        border-bottom: 1px solid #f1f5f9;
    }
    .bpa-form-section-chat { background: #f0f9ff; border-color: #bae6fd; }
    .bpa-label {
        display: block;
        font-size: 0.78rem;
        font-weight: 600;
        color: #374151;
        margin-bottom: 0.35rem;
    }
    .bpa-optional { font-weight: 500; color: #9ca3af; font-size: 0.72rem; text-transform: none; letter-spacing: 0; }
    .bpa-input {
        border-radius: 8px;
        border-color: #d1d5db;
        font-size: 0.9rem;
    }
    .bpa-input:focus { border-color: #0369a1; box-shadow: 0 0 0 3px rgba(3, 105, 161, 0.12); }
    .bpa-file-hint { display: block; margin-top: 0.35rem; }
    .bpa-peer-options { max-height: 180px; overflow-y: auto; background: #fff; }
    .bpa-peer-option { cursor: pointer; border-bottom: 1px solid #f1f5f9; font-size: .88rem; }
    .bpa-peer-option:hover { background: #f8fafc; }
    .bpa-peer-chips { display: flex; flex-wrap: wrap; gap: .4rem; min-height: 28px; }
    .bpa-peer-chip {
        display: inline-flex; align-items: center; gap: .35rem;
        background: #e0f2fe; color: #0369a1; border-radius: 20px;
        padding: .25rem .65rem; font-size: .78rem; font-weight: 600;
    }
    .bpa-peer-chip button { border: none; background: transparent; color: #0369a1; cursor: pointer; padding: 0; line-height: 1; }
    .bpa-form .row.g-3 { --bs-gutter-x: 1rem; --bs-gutter-y: 0.75rem; }
</style>
@endsection

@section('main')
<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid d-flex flex-wrap align-items-center justify-content-between">
            <div>
                <h1 class="bpa-page-title m-0">{{ $page_heading }}</h1>
                <p class="text-muted small mb-0">
                    <a href="{{ route('subadmin.corporate_individual.hub') }}">Corporate / Individual (B2B)</a>
                    &nbsp;/&nbsp; {{ $is_corporate ? 'B2B Corporate' : 'Individual' }}
                </p>
            </div>
            @if( ($is_corporate && auth()->user()->can('create_b2b_corporate')) || (!$is_corporate && auth()->user()->can('create_b2b_individual')) )
            <button type="button" class="btn btn-warning bpa-toolbar btn-create text-dark" data-toggle="modal" data-target="#createPartnerModal" data-bs-toggle="modal" data-bs-target="#createPartnerModal">
                <i class="fas fa-plus mr-1"></i>
                {{ $is_corporate ? 'Create B2B Corporate User' : 'Create Individual Partner User' }}
            </button>
            @endif
        </div>
    </section>
    <section class="content">
        <div class="container-fluid">
            @if(session('status'))
                <div class="alert alert-{{ session('status.alert_type', 'success') }} alert-dismissible fade show">
                    {{ session('status.message') }}
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                </div>
            @endif
            @if($errors->any())
                <div class="alert alert-danger">
                    @foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach
                </div>
            @endif

            <div class="card">
                <div class="card-body table-responsive p-0">
                    <table class="table table-hover bpa-table mb-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Name</th>
                                <th>Company</th>
                                <th>Mobile</th>
                                @if($is_corporate)<th>Chat</th>@endif
                                <th>Referral</th>
                                <th>Commission</th>
                                <th>Service</th>
                                <th>Bulk Qty</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($items as $item)
                                <tr>
                                    <td>{{ $item->id }}</td>
                                    <td>{{ $item->name }}</td>
                                    <td>{{ $item->company_name }}</td>
                                    <td>{{ $item->mobile ?: '—' }}</td>
                                    @if($is_corporate)
                                        <td>
                                            @if($item->chat_enabled)
                                                <span class="badge badge-success">On</span>
                                                <small class="d-block text-muted">{{ $item->chatPeers->count() }} peer(s)</small>
                                            @else
                                                <span class="badge badge-secondary">Off</span>
                                            @endif
                                        </td>
                                    @endif
                                    <td class="small">{{ $item->referenceUser ? $item->referenceUser->name : '—' }}</td>
                                    <td>{{ $item->commission_percent !== null ? $item->commission_percent.'%' : '—' }}</td>
                                    <td>{{ $item->service_requirement ?: '—' }}</td>
                                    <td>{{ $item->bulk_requirement_qty ?? '—' }}</td>
                                    <td class="text-nowrap small">{{ $item->created_at?->format('d M Y') }}</td>
                                    <td class="text-nowrap">
                                        <button type="button" class="btn btn-xs btn-info" data-toggle="modal" data-target="#viewPartner{{ $item->id }}" data-bs-toggle="modal" data-bs-target="#viewPartner{{ $item->id }}">View</button>
                                        @if( ($is_corporate && auth()->user()->can('edit_b2b_corporate')) || (!$is_corporate && auth()->user()->can('edit_b2b_individual')) )
                                        <button type="button" class="btn btn-xs btn-primary" data-toggle="modal" data-target="#editPartner{{ $item->id }}" data-bs-toggle="modal" data-bs-target="#editPartner{{ $item->id }}">Edit</button>
                                        @endif
                                        @if( ($is_corporate && auth()->user()->can('delete_b2b_corporate')) || (!$is_corporate && auth()->user()->can('delete_b2b_individual')) )
                                        <form method="POST" action="{{ route($is_corporate ? 'admin.b2b_corporate.destroy' : 'admin.b2b_individual.destroy', $item) }}" class="d-inline" onsubmit="return confirm('Delete this partner account?');">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn btn-xs btn-danger">Delete</button>
                                        </form>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="{{ $is_corporate ? 11 : 10 }}" class="text-center text-muted py-4">No accounts yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>
</div>

{{-- Create --}}
<div class="modal fade bpa-modal" id="createPartnerModal" tabindex="-1" aria-labelledby="createPartnerModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="{{ route($is_corporate ? 'admin.b2b_corporate.store' : 'admin.b2b_individual.store') }}" enctype="multipart/form-data">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="createPartnerModalLabel">
                        <i class="fas {{ $is_corporate ? 'fa-building text-primary' : 'fa-user-tie' }} mr-2" @if(!$is_corporate) style="color:#F07F28" @endif></i>
                        {{ $is_corporate ? 'Create B2B Corporate User' : 'Create Individual Partner User' }}
                    </h5>
                    <button type="button" class="btn-close" data-dismiss="modal" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small mb-3">Partner will log in with mobile OTP. Fields marked optional can be left blank.</p>
                    @include('admin.b2b_partner_accounts.partials.form-fields', ['is_corporate' => $is_corporate, 'prefix' => 'create_'])
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-dismiss="modal" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning text-dark rounded-pill px-4 font-weight-bold">
                        <i class="fas fa-check mr-1"></i> Create account
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@foreach($items as $item)
    <div class="modal fade" id="viewPartner{{ $item->id }}" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Partner details</h5>
                    <button type="button" class="close" data-dismiss="modal" data-bs-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-2"><strong>Name:</strong> {{ $item->name }}</div>
                        <div class="col-md-6 mb-2"><strong>Company:</strong> {{ $item->company_name }}</div>
                        <div class="col-md-6 mb-2"><strong>Mobile:</strong> {{ $item->mobile ?: '—' }}</div>
                        <div class="col-md-6 mb-2"><strong>Referral:</strong> {{ $item->referenceUser?->name ?? '—' }}</div>
                        <div class="col-md-6 mb-2"><strong>Commission:</strong> {{ $item->commission_percent !== null ? $item->commission_percent.'%' : '—' }}</div>
                        <div class="col-md-6 mb-2"><strong>Service:</strong> {{ $item->service_requirement ?: '—' }}</div>
                        <div class="col-md-6 mb-2"><strong>Bulk qty:</strong> {{ $item->bulk_requirement_qty ?? '—' }}</div>
                        @if($is_corporate)
                            <div class="col-md-12 mb-2"><strong>Chat:</strong> {{ $item->chat_enabled ? 'Enabled — peers: '.$item->chatPeers->pluck('name')->join(', ') : 'Disabled' }}</div>
                        @endif
                        <div class="col-md-12 mb-2"><strong>Bank details:</strong> {{ $item->bank_account_details ?: '—' }}</div>
                        <div class="col-md-6 mb-2"><strong>Holder:</strong> {{ $item->account_holder_name ?: '—' }}</div>
                        <div class="col-md-6 mb-2"><strong>Bank:</strong> {{ $item->bank_name ?: '—' }}</div>
                        <div class="col-md-6 mb-2"><strong>IFSC:</strong> {{ $item->ifsc_code ?: '—' }}</div>
                        <div class="col-md-6 mb-2"><strong>Account #:</strong> {{ $item->account_number ?: '—' }}</div>
                        @foreach(['company_registration_file' => 'Registration', 'company_gst_file' => 'GST', 'mou_file' => 'MOU'] as $field => $label)
                            <div class="col-md-4 mb-2">
                                <strong>{{ $label }}:</strong>
                                @if($item->{$field})
                                    <a href="{{ asset('storage/'.$item->{$field}) }}" target="_blank">View</a>
                                @else — @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade bpa-modal" id="editPartner{{ $item->id }}" tabindex="-1">
        <div class="modal-dialog modal-xl modal-dialog-scrollable modal-dialog-centered">
            <div class="modal-content">
                <form method="POST" action="{{ route($is_corporate ? 'admin.b2b_corporate.update' : 'admin.b2b_individual.update', $item) }}" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title font-weight-bold">Edit partner — {{ $item->name }}</h5>
                        <button type="button" class="btn-close" data-dismiss="modal" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        @include('admin.b2b_partner_accounts.partials.form-fields', [
                            'is_corporate' => $is_corporate,
                            'item' => $item,
                            'prefix' => 'edit_'.$item->id.'_',
                        ])
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-dismiss="modal" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary rounded-pill px-4 font-weight-bold">Save changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endforeach
@endsection

@section('footer-script')
<script>
(function () {
    function openModal(selector) {
        var modalEl = document.querySelector(selector);
        if (!modalEl) return;
        if (window.bootstrap && window.bootstrap.Modal) {
            window.bootstrap.Modal.getOrCreateInstance(modalEl).show();
            return;
        }
        if (typeof jQuery !== 'undefined' && typeof jQuery(selector).modal === 'function') {
            jQuery(selector).modal('show');
        }
    }

    $(document).on('click', '[data-target="#createPartnerModal"], [data-bs-target="#createPartnerModal"], [data-target^="#viewPartner"], [data-bs-target^="#viewPartner"], [data-target^="#editPartner"], [data-bs-target^="#editPartner"]', function () {
        var targetSelector = $(this).attr('data-bs-target') || $(this).attr('data-target');
        if (targetSelector) openModal(targetSelector);
    });

    function bindChatToggle(prefix) {
        var cb = document.getElementById(prefix + 'chat_enabled');
        var wrap = document.getElementById(prefix + 'chat_peers_wrap');
        if (!cb || !wrap) return;
        cb.addEventListener('change', function () {
            wrap.classList.toggle('d-none', !cb.checked);
        });
    }

    function initPeerPicker(prefix) {
        var chips = document.getElementById(prefix + 'peer_chips');
        var hidden = document.getElementById(prefix + 'peer_hidden_inputs');
        var search = document.getElementById(prefix + 'peer_search');
        var options = document.getElementById(prefix + 'peer_options');
        if (!chips || !hidden || !options) return;

        function syncHidden() {
            hidden.innerHTML = '';
            options.querySelectorAll('.bpa-peer-check:checked').forEach(function (cb) {
                var inp = document.createElement('input');
                inp.type = 'hidden';
                inp.name = 'chat_peer_user_ids[]';
                inp.value = cb.value;
                hidden.appendChild(inp);
            });
        }

        function renderChips() {
            chips.innerHTML = '';
            options.querySelectorAll('.bpa-peer-check:checked').forEach(function (cb) {
                var chip = document.createElement('span');
                chip.className = 'bpa-peer-chip';
                chip.innerHTML = escapeHtml(cb.getAttribute('data-label')) +
                    ' <button type="button" aria-label="Remove">&times;</button>';
                chip.querySelector('button').addEventListener('click', function () {
                    cb.checked = false;
                    renderChips();
                    syncHidden();
                });
                chips.appendChild(chip);
            });
            syncHidden();
        }

        options.querySelectorAll('.bpa-peer-check').forEach(function (cb) {
            cb.addEventListener('change', renderChips);
        });

        if (search) {
            search.addEventListener('input', function () {
                var q = this.value.toLowerCase();
                options.querySelectorAll('.bpa-peer-option').forEach(function (row) {
                    var name = row.getAttribute('data-name') || '';
                    row.style.display = name.indexOf(q) !== -1 ? '' : 'none';
                });
            });
        }

        renderChips();
    }

    function escapeHtml(t) {
        var d = document.createElement('div');
        d.textContent = t || '';
        return d.innerHTML;
    }

    bindChatToggle('create_');
    initPeerPicker('create_');
    @foreach($items as $item)
        bindChatToggle('edit_{{ $item->id }}_');
        initPeerPicker('edit_{{ $item->id }}_');
    @endforeach
    @if($errors->any())
        openModal('#createPartnerModal');
    @endif
})();
</script>
@endsection
