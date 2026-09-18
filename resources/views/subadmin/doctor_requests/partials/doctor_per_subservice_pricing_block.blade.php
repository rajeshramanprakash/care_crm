@php
    /** @var \App\Models\DoctorRequest $doctor */
    /** @var int $subServiceId */
    /** @var string|null $subServiceName */
    /** @var string $scopePrefix input id prefix (reg, inline, modal) */

    $subServiceId = (int) ($subServiceId ?? 0);
    $subServiceName = $subServiceName ?? null;
    $scopePrefix = (string) ($scopePrefix ?? 'inline');
    $drId = (int) $doctor->id;
    $enabledModes = is_array($doctor->consultation_modes ?? null) ? array_values($doctor->consultation_modes) : [];
    $modeLabels = \App\Models\LocationDoctorConsultationPrice::MODE_LABELS;
    $pricingRows = is_array($doctor->consultation_pricing ?? null) ? $doctor->consultation_pricing : [];
    $pricingRow = null;
    foreach ($pricingRows as $pr) {
        if (! is_array($pr)) {
            continue;
        }
        if ((int) ($pr['sub_service_id'] ?? 0) === $subServiceId) {
            $pricingRow = $pr;
            break;
        }
    }
    $rowModes = is_array($pricingRow['modes'] ?? null) ? $pricingRow['modes'] : [];
    $inputSize = (string) ($inputSize ?? 'sm');
    $sizeClass = $inputSize === 'default' ? '' : 'form-control-sm';
@endphp
<div class="dr-sub-pricing-block mt-2 pt-2 border-top" data-sub-id="{{ $subServiceId }}">
    <div class="small font-weight-bold mb-1">Consultation charges</div>
    <p class="small text-muted mb-2 mb-md-1">Doctor charge = doctor rate. CareWeb charge = price on this doctor’s card. Blank = use location default.</p>
    @if($enabledModes === [])
        <p class="small text-muted mb-0">No consultation modes enabled for this doctor.</p>
    @else
        @foreach($enabledModes as $modeKey)
            @php
                $modeKey = (string) $modeKey;
                if (! array_key_exists($modeKey, $modeLabels)) {
                    continue;
                }
                $modeRow = is_array($rowModes[$modeKey] ?? null) ? $rowModes[$modeKey] : [];
                $docVal = $modeRow['doctor_price'] ?? null;
                $webVal = $modeRow['website_price'] ?? null;
                $docId = $scopePrefix . '_price_' . $drId . '_doc_' . $modeKey . '_' . $subServiceId;
                $webId = $scopePrefix . '_price_' . $drId . '_web_' . $modeKey . '_' . $subServiceId;
            @endphp
            <div class="form-row dr-sub-pricing-mode-row align-items-end mb-2">
                <div class="col-12 col-md-3 mb-1 mb-md-0">
                    <span class="small font-weight-bold d-block">{{ $modeLabels[$modeKey] }}</span>
                    <span class="small text-muted dr-sub-pricing-loc-hint" data-mode="{{ $modeKey }}" data-sub-id="{{ $subServiceId }}">Location: —</span>
                </div>
                <div class="col-6 col-md-4 mb-1 mb-md-0">
                    <label class="small mb-0" for="{{ $docId }}">Doctor (₹)</label>
                    <input type="number" class="form-control {{ $sizeClass }} dr-consult-price-doc"
                           id="{{ $docId }}"
                           data-mode="{{ $modeKey }}"
                           data-sub-id="{{ $subServiceId }}"
                           min="0" step="0.01" placeholder="Optional"
                           value="{{ $docVal !== null && $docVal !== '' ? $docVal : '' }}">
                </div>
                <div class="col-6 col-md-5 mb-1 mb-md-0">
                    <label class="small mb-0" for="{{ $webId }}">CareWeb card (₹)</label>
                    <input type="number" class="form-control {{ $sizeClass }} dr-consult-price-web"
                           id="{{ $webId }}"
                           data-mode="{{ $modeKey }}"
                           data-sub-id="{{ $subServiceId }}"
                           min="0" step="0.01" placeholder="Optional"
                           value="{{ $webVal !== null && $webVal !== '' ? $webVal : '' }}">
                </div>
            </div>
        @endforeach
    @endif
</div>
