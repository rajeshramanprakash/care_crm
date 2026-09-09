@php
    $rowId = $row['id'] ?? null;
    $rowName = $row['name'] ?? '';
    $rowDuration = $row['consultation_duration_minutes'] ?? '';
    $rowSort = $row['sort_order'] ?? 0;
    $rowIconPath = $row['icon_path'] ?? null;
    $tagsText = $row['specialization_options_text'] ?? '';
    $prefix = 'sub_services[' . $index . ']';
@endphp

<div class="dcs-sub-service-card" data-sub-index="{{ $index }}">
    <div class="dcs-sub-service-head">
        <span class="dcs-sub-service-title"><i class="fas fa-layer-group mr-1"></i> Sub-service <span class="dcs-sub-num">{{ (int) $index + 1 }}</span></span>
        <button type="button" class="btn btn-sm btn-outline-danger dcs-sub-remove" title="Remove sub-service">
            <i class="fas fa-times mr-1"></i> Remove
        </button>
    </div>
    <div class="dcs-sub-service-body">
        @if($rowId)
            <input type="hidden" name="{{ $prefix }}[id]" value="{{ $rowId }}">
        @endif
        <input type="hidden" name="{{ $prefix }}[_delete]" value="0" class="dcs-sub-delete-flag">

        <div class="form-group mb-3">
            <label class="dcs-label">Sub-service name <span class="req">*</span></label>
            <input type="text"
                   class="form-control dcs-sub-name @error('sub_services.'.$index.'.name') is-invalid @enderror"
                   name="{{ $prefix }}[name]"
                   value="{{ $rowName }}"
                   maxlength="255"
                   placeholder="e.g. Video consultation"
                   autocomplete="off">
            @error('sub_services.'.$index.'.name')
                <span class="invalid-feedback d-block"><strong>{{ $message }}</strong></span>
            @enderror
        </div>

        <div class="form-group mb-3">
            <label class="dcs-label">Sub-service icon</label>
            @if($rowIconPath)
                <div class="mb-2">
                    <img src="{{ asset('storage/' . ltrim(str_replace('\\', '/', $rowIconPath), '/')) }}" alt="" width="64" height="64" style="object-fit:contain;border-radius:10px;border:1px solid #e9ecef;padding:6px;background:#fff;">
                </div>
                <div class="custom-control custom-checkbox mb-2">
                    <input type="checkbox" class="custom-control-input" id="sub_remove_icon_{{ $index }}" name="{{ $prefix }}[remove_icon]" value="1">
                    <label class="custom-control-label" for="sub_remove_icon_{{ $index }}">Remove current icon</label>
                </div>
            @endif
            <input type="file"
                   class="form-control-file @error('sub_services.'.$index.'.icon') is-invalid @enderror"
                   name="{{ $prefix }}[icon]"
                   accept="image/jpeg,image/png,image/webp">
            @error('sub_services.'.$index.'.icon')
                <span class="invalid-feedback d-block"><strong>{{ $message }}</strong></span>
            @enderror
        </div>

        <div class="row">
            <div class="col-sm-6">
                <div class="form-group mb-3 mb-sm-0">
                    <label class="dcs-label">Sub-service session duration <span class="text-muted font-weight-normal">(optional)</span></label>
                    <div class="dcs-duration-wrap">
                        <input type="number"
                               class="form-control @error('sub_services.'.$index.'.consultation_duration_minutes') is-invalid @enderror"
                               name="{{ $prefix }}[consultation_duration_minutes]"
                               value="{{ $rowDuration }}"
                               min="1"
                               max="1440"
                               placeholder="30">
                        <span class="dcs-duration-suffix">min</span>
                    </div>
                    @error('sub_services.'.$index.'.consultation_duration_minutes')
                        <span class="invalid-feedback d-block"><strong>{{ $message }}</strong></span>
                    @enderror
                </div>
            </div>
            <div class="col-sm-6">
                <div class="form-group mb-0">
                    <label class="dcs-label">Sub-service display order</label>
                    <input type="number"
                           class="form-control @error('sub_services.'.$index.'.sort_order') is-invalid @enderror"
                           name="{{ $prefix }}[sort_order]"
                           value="{{ $rowSort }}"
                           min="0"
                           placeholder="0">
                    @error('sub_services.'.$index.'.sort_order')
                        <span class="invalid-feedback d-block"><strong>{{ $message }}</strong></span>
                    @enderror
                </div>
            </div>
        </div>

        <div class="form-group mb-0 mt-3">
            <label class="dcs-label">Sub-service tags for doctors <span class="text-muted font-weight-normal">(optional)</span></label>
            <p class="dcs-hint mb-2">Doctor registration me is sub-service ke liye tags select ho sakte hain.</p>
            <div class="dcs-tags-box dcs-sub-tags-box">
                <div class="dcs-tags-list" aria-live="polite"></div>
                <input type="text"
                       class="dcs-tags-input dcs-sub-tags-input"
                       maxlength="255"
                       placeholder="Type tag name, press Enter"
                       autocomplete="off">
            </div>
            <textarea class="d-none dcs-sub-tags-hidden @error('sub_services.'.$index.'.specialization_options_text') is-invalid @enderror"
                      name="{{ $prefix }}[specialization_options_text]"
                      rows="1"
                      aria-hidden="true">{{ $tagsText }}</textarea>
            @error('sub_services.'.$index.'.specialization_options_text')
                <span class="invalid-feedback d-block"><strong>{{ $message }}</strong></span>
            @enderror
        </div>
    </div>
</div>
