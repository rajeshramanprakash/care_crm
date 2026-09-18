<div class="service-row loc-service-row border p-3 mb-3 rounded"
     data-saved-sub-id="{{ (int) ($selectedSubServiceId ?? 0) }}">
    <div class="row align-items-end">
        <div class="col-lg-3 col-md-6">
            <label class="loc-label">Service</label>
            <select class="form-control service-select loc-provider-service-select" name="{{ $inputPrefix ?? 'services' }}[{{ $index }}][service_id]">
                <option value="">Select service</option>
                @foreach($services as $s)
                    <option value="{{ $s->id }}" {{ (string) ($selectedServiceId ?? '') === (string) $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-lg-3 col-md-6">
            <label class="loc-label">Sub-service</label>
            <select class="form-control loc-provider-sub-select" name="{{ $inputPrefix ?? 'services' }}[{{ $index }}][service_sub_service_id]">
                <option value="0">— Service-level (no sub-service) —</option>
                @if(($selectedServiceId ?? null) && ($selectedSubServiceId ?? 0) > 0)
                    @php
                        $svcForSub = ($services ?? collect())->firstWhere('id', (int) $selectedServiceId);
                        $subRow = $svcForSub ? $svcForSub->subServices->firstWhere('id', (int) $selectedSubServiceId) : null;
                    @endphp
                    @if($subRow)
                        <option value="{{ $subRow->id }}" selected>{{ $subRow->name }}</option>
                    @endif
                @endif
            </select>
            <small class="text-muted d-block loc-provider-sub-hint">Agar service ke andar sub-services hain to sub-service select karke uski alag price set karein.</small>
        </div>
        <div class="col-lg-2 col-md-4 col-6">
            <label class="loc-label">12hr price</label>
            <input type="number" step="0.01" min="0" class="form-control" name="{{ $inputPrefix ?? 'services' }}[{{ $index }}][price_12hr]" value="{{ $price12 }}" placeholder="0.00">
            @if(($origPrice12 ?? null) !== null)
                <small class="text-warning d-block mt-1" style="line-height: 1.1;">
                    <i class="fas fa-exclamation-triangle"></i> Temp Active<br>
                    <span class="text-muted">Orig: ₹{{ $origPrice12 }}</span>
                </small>
            @endif
        </div>
        <div class="col-lg-2 col-md-4 col-6">
            <label class="loc-label">24hr price</label>
            <input type="number" step="0.01" min="0" class="form-control" name="{{ $inputPrefix ?? 'services' }}[{{ $index }}][price_24hr]" value="{{ $price24 }}" placeholder="0.00">
            @if(($origPrice24 ?? null) !== null)
                <small class="text-warning d-block mt-1" style="line-height: 1.1;">
                    <i class="fas fa-exclamation-triangle"></i> Temp Active<br>
                    <span class="text-muted">Orig: ₹{{ $origPrice24 }}</span>
                </small>
            @endif
        </div>
        <div class="col-lg-1 col-md-4 col-6">
            <label class="loc-label">One-time</label>
            <input type="number" step="0.01" min="0" class="form-control" name="{{ $inputPrefix ?? 'services' }}[{{ $index }}][price_onetime]" value="{{ $priceOnetime }}" placeholder="0.00">
            @if(($origPriceOnetime ?? null) !== null)
                <small class="text-warning d-block mt-1" style="line-height: 1.1;">
                    <i class="fas fa-exclamation-triangle"></i> Temp Active<br>
                    <span class="text-muted">Orig: ₹{{ $origPriceOnetime }}</span>
                </small>
            @endif
        </div>
        <div class="col-lg-1 col-md-4 col-6">
            <label class="loc-label d-none d-lg-block">&nbsp;</label>
            <button type="button" class="btn btn-outline-danger btn-sm btn-block remove-service" {{ empty($showRemove) ? 'style=display:none;' : '' }}>
                <i class="fas fa-trash-alt"></i>
            </button>
        </div>
    </div>
</div>
