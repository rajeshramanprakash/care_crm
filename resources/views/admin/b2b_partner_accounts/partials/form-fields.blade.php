@php
    $item = $item ?? null;
    $isCorporate = $is_corporate ?? true;
    $prefix = $prefix ?? '';
@endphp

<div class="bpa-form">
    <div class="bpa-form-section">
        <h6 class="bpa-form-section-title"><i class="fas fa-user mr-2"></i>Basic details</h6>
        <div class="row g-3">
            <div class="col-md-4">
                <label class="bpa-label">Name <span class="text-danger">*</span></label>
                <input type="text" name="name" class="form-control bpa-input" required value="{{ old('name', $item->name ?? '') }}" placeholder="Full name">
            </div>
            <div class="col-md-4">
                <label class="bpa-label">Company name <span class="text-danger">*</span></label>
                <input type="text" name="company_name" class="form-control bpa-input" required value="{{ old('company_name', $item->company_name ?? '') }}" placeholder="Company name">
            </div>
            <div class="col-md-4">
                <label class="bpa-label">
                    Mobile number
                    @if(!$isCorporate)<span class="text-danger">*</span>@else<span class="bpa-optional">optional</span>@endif
                </label>
                <input type="text" name="mobile" class="form-control bpa-input" maxlength="10" pattern="[0-9]{10}"
                    {{ $isCorporate ? '' : 'required' }}
                    value="{{ old('mobile', $item->mobile ?? '') }}" placeholder="10-digit mobile">
            </div>
        </div>
    </div>

    <div class="bpa-form-section">
        <h6 class="bpa-form-section-title"><i class="fas fa-briefcase mr-2"></i>Service &amp; commission</h6>
        <div class="row g-3">
            <div class="col-md-4">
                <label class="bpa-label">Referral user <span class="bpa-optional">optional</span></label>
                <select name="b2b_reference_user_id" class="form-select bpa-input">
                    <option value="">— None —</option>
                    @foreach($referenceUsers as $refOption)
                        <option value="{{ $refOption['id'] }}" {{ (int) old('b2b_reference_user_id', $item->b2b_reference_user_id ?? 0) === (int) $refOption['id'] ? 'selected' : '' }}>
                            {{ $refOption['name'] }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="bpa-label">Commission (%) <span class="bpa-optional">optional</span></label>
                <input type="number" step="0.01" min="0" max="100" name="commission_percent" class="form-control bpa-input"
                    value="{{ old('commission_percent', $item->commission_percent ?? '') }}" placeholder="e.g. 10">
            </div>
            @if($isCorporate)
            <div class="col-md-4">
                <label class="bpa-label">Service requirement</label>
                <select name="service_requirement" class="form-select bpa-input">
                    <option value="">— Select service —</option>
                    @foreach($serviceOptions as $serviceOption)
                        <option value="{{ $serviceOption['name'] }}" {{ old('service_requirement', $item->service_requirement ?? '') === $serviceOption['name'] ? 'selected' : '' }}>
                            {{ $serviceOption['name'] }}
                        </option>
                    @endforeach
                </select>
            </div>
            @endif
            <div class="col-md-4">
                <label class="bpa-label">Bulk requirement (qty)</label>
                <input type="number" min="0" name="bulk_requirement_qty" class="form-control bpa-input"
                    value="{{ old('bulk_requirement_qty', $item->bulk_requirement_qty ?? '') }}" placeholder="Quantity">
            </div>
        </div>
    </div>

    <div class="bpa-form-section">
        <h6 class="bpa-form-section-title"><i class="fas fa-university mr-2"></i>Bank details <span class="bpa-optional">all optional</span></h6>
        <div class="row g-3">
            <div class="col-12">
                <label class="bpa-label">Bank account details</label>
                <textarea name="bank_account_details" class="form-control bpa-input" rows="2" placeholder="Additional bank notes">{{ old('bank_account_details', $item->bank_account_details ?? '') }}</textarea>
            </div>
            <div class="col-md-3">
                <label class="bpa-label">Account holder name</label>
                <input type="text" name="account_holder_name" class="form-control bpa-input" value="{{ old('account_holder_name', $item->account_holder_name ?? '') }}">
            </div>
            <div class="col-md-3">
                <label class="bpa-label">Bank name</label>
                <input type="text" name="bank_name" class="form-control bpa-input" value="{{ old('bank_name', $item->bank_name ?? '') }}">
            </div>
            <div class="col-md-3">
                <label class="bpa-label">IFSC code</label>
                <input type="text" name="ifsc_code" class="form-control bpa-input" value="{{ old('ifsc_code', $item->ifsc_code ?? '') }}">
            </div>
            <div class="col-md-3">
                <label class="bpa-label">Account number</label>
                <input type="text" name="account_number" class="form-control bpa-input" value="{{ old('account_number', $item->account_number ?? '') }}">
            </div>
        </div>
    </div>

    <div class="bpa-form-section">
        <h6 class="bpa-form-section-title"><i class="fas fa-file-upload mr-2"></i>Documents <span class="bpa-optional">pdf / image, optional</span></h6>
        <div class="row g-3">
            <div class="col-md-4">
                <label class="bpa-label">Company registration</label>
                <input type="file" name="company_registration_file" class="form-control bpa-input" accept=".pdf,image/*">
                @if(!empty($item->company_registration_file))
                    <small class="bpa-file-hint"><a href="{{ asset('storage/'.$item->company_registration_file) }}" target="_blank">View current file</a></small>
                @endif
            </div>
            <div class="col-md-4">
                <label class="bpa-label">Company GST</label>
                <input type="file" name="company_gst_file" class="form-control bpa-input" accept=".pdf,image/*">
                @if(!empty($item->company_gst_file))
                    <small class="bpa-file-hint"><a href="{{ asset('storage/'.$item->company_gst_file) }}" target="_blank">View current file</a></small>
                @endif
            </div>
            <div class="col-md-4">
                <label class="bpa-label">MOU</label>
                <input type="file" name="mou_file" class="form-control bpa-input" accept=".pdf,image/*">
                @if(!empty($item->mou_file))
                    <small class="bpa-file-hint"><a href="{{ asset('storage/'.$item->mou_file) }}" target="_blank">View current file</a></small>
                @endif
            </div>
        </div>
    </div>

    @if($isCorporate)
    @php
        $selectedPeers = old('chat_peer_user_ids', isset($item) ? $item->chatPeers->pluck('id')->all() : []);
    @endphp
    <div class="bpa-form-section bpa-form-section-chat">
        <h6 class="bpa-form-section-title"><i class="fas fa-comments mr-2"></i>Chat access</h6>
        <div class="form-check mb-3">
            <input type="checkbox" class="form-check-input" id="{{ $prefix }}chat_enabled" name="chat_enabled" value="1"
                {{ old('chat_enabled', $item->chat_enabled ?? false) ? 'checked' : '' }}>
            <label class="form-check-label bpa-label mb-0" for="{{ $prefix }}chat_enabled">
                Enable chat for this corporate partner
            </label>
        </div>
        <div id="{{ $prefix }}chat_peers_wrap" class="{{ old('chat_enabled', $item->chat_enabled ?? false) ? '' : 'd-none' }}">
            <label class="bpa-label">Chat group name <span class="bpa-optional">shown in chat</span></label>
            <input type="text" name="chat_group_name" class="form-control bpa-input mb-3" maxlength="120"
                placeholder="e.g. Sales team, North region"
                value="{{ old('chat_group_name', $item->chat_group_name ?? '') }}">

            <label class="bpa-label">Add CRM users to chat group</label>
            <p class="text-muted small mb-2">Selected users and the corporate partner share <strong>one group chat</strong> — everyone sees the same messages and attachments.</p>

            <div class="bpa-peer-picker" data-prefix="{{ $prefix }}">
                <div class="bpa-peer-chips mb-2" id="{{ $prefix }}peer_chips"></div>
                <div id="{{ $prefix }}peer_hidden_inputs">
                    @foreach($selectedPeers as $pid)
                        <input type="hidden" name="chat_peer_user_ids[]" value="{{ $pid }}">
                    @endforeach
                </div>
                <input type="text" class="form-control bpa-input mb-2" id="{{ $prefix }}peer_search" placeholder="Search CRM users…" autocomplete="off">
                <div class="bpa-peer-options border rounded" id="{{ $prefix }}peer_options">
                    @foreach($chatStaffUsers as $staff)
                        <label class="bpa-peer-option d-flex align-items-center px-2 py-2 mb-0" data-name="{{ strtolower($staff['name']) }}">
                            <input type="checkbox" class="mr-2 bpa-peer-check" value="{{ $staff['id'] }}" data-label="{{ $staff['name'] }}"
                                {{ in_array($staff['id'], $selectedPeers, true) ? 'checked' : '' }}>
                            <span>{{ $staff['name'] }}</span>
                        </label>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
