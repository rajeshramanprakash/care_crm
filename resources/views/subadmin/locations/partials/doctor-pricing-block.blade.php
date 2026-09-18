@php
    $block = $block ?? [];
    $serviceId = old("doctor_pricing.$blockIndex.doctor_consultation_service_id", $block['doctor_consultation_service_id'] ?? '');
    $subId = old("doctor_pricing.$blockIndex.doctor_consultation_service_sub_service_id", $block['doctor_consultation_service_sub_service_id'] ?? '');
    $modes = $block['modes'] ?? [];
    $prefix = "doctor_pricing[$blockIndex]";
@endphp

<div class="loc-doc-block" data-block-index="{{ $blockIndex }}"
     data-saved-sub-id="{{ $subId !== '' && $subId !== null ? (int) $subId : '' }}">
    <div class="loc-doc-block-head">
        <span class="loc-doc-block-title">Doctor service pricing <span class="loc-block-num">{{ is_numeric($blockIndex) ? (int) $blockIndex + 1 : '' }}</span></span>
        <button type="button" class="btn btn-sm btn-outline-danger loc-remove-doc-block" title="Remove block">
            <i class="fas fa-trash-alt"></i>
        </button>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="form-group mb-3">
                <label class="font-weight-bold">Doctor Service</label>
                <select class="form-control loc-doc-service-select" name="{{ $prefix }}[doctor_consultation_service_id]">
                    <option value="">Select service</option>
                    @foreach($doctorConsultationServices as $svc)
                        <option value="{{ $svc->id }}" {{ (string) $serviceId === (string) $svc->id ? 'selected' : '' }}>{{ $svc->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group mb-3">
                <label class="font-weight-bold">Sub-Service</label>
                <select class="form-control loc-doc-sub-select" name="{{ $prefix }}[doctor_consultation_service_sub_service_id]">
                    <option value="">— Service-level (no sub-service) —</option>
                    @if($serviceId && $subId)
                        @php
                            $svcForSub = ($doctorConsultationServices ?? collect())->firstWhere('id', (int) $serviceId);
                            $subRow = $svcForSub ? $svcForSub->subServices->firstWhere('id', (int) $subId) : null;
                        @endphp
                        @if($subRow)
                            <option value="{{ $subRow->id }}" selected>{{ $subRow->name }}</option>
                        @endif
                    @endif
                </select>
                <small class="text-muted loc-doc-sub-hint">Agar service ke andar sub-services hain to sub-service select karke uski alag price set karein. Sub-service na ho to service-level price set hoti hai.</small>
            </div>
        </div>
    </div>

    <label class="font-weight-bold d-block mb-1">Doctor Consultation Modes</label>
    <div class="loc-mode-chips">
        @foreach($modeLabels as $modeKey => $modeLabel)
            @php
                $modeOld = old("doctor_pricing.$blockIndex.modes.$modeKey", $modes[$modeKey] ?? []);
                $enabled = ! empty($modeOld['enabled']);
            @endphp
            <label class="loc-mode-chip {{ $enabled ? 'active' : '' }}" data-mode="{{ $modeKey }}">
                <input type="checkbox"
                       class="loc-mode-chip-cb"
                       name="{{ $prefix }}[modes][{{ $modeKey }}][enabled]"
                       value="1"
                       {{ $enabled ? 'checked' : '' }}>
                <i class="fas fa-check-circle"></i> {{ $modeLabel }}
            </label>
        @endforeach
    </div>

    <div class="table-responsive loc-doc-modes-table-wrap">
        <table class="table table-bordered table-sm loc-doc-price-table mb-0">
            <thead class="bg-light">
                <tr>
                    <th>Mode</th>
                    <th>Website Price</th>
                    <th>Doctor Max Price</th>
                    <th>Final Allowed</th>
                    <th>Status</th>
                    <th class="text-center">Action</th>
                </tr>
            </thead>
            <tbody class="loc-doc-modes-tbody">
                @foreach($modeLabels as $modeKey => $modeLabel)
                    @php
                        $modeOld = old("doctor_pricing.$blockIndex.modes.$modeKey", $modes[$modeKey] ?? []);
                        $enabled = ! empty($modeOld['enabled']);
                        $webPrice = $modeOld['website_price'] ?? '';
                        $docMax = $modeOld['doctor_max_price'] ?? '';
                        $origWebPrice = $modeOld['original_website_price'] ?? null;
                        $origDocMax = $modeOld['original_doctor_max_price'] ?? null;
                    @endphp
                    <tr class="loc-mode-row {{ $enabled ? '' : 'd-none' }}" data-mode="{{ $modeKey }}">
                        <td class="font-weight-bold">{{ $modeLabel }}</td>
                        <td>
                            <input type="number"
                                   step="0.01"
                                   min="0"
                                   class="form-control form-control-sm loc-website-price"
                                   name="{{ $prefix }}[modes][{{ $modeKey }}][website_price]"
                                   value="{{ $webPrice }}"
                                   placeholder="0.00">
                            @if($origWebPrice !== null)
                                <small class="text-warning d-block mt-1" style="line-height: 1.1;">
                                    <i class="fas fa-exclamation-triangle"></i> Temp Active<br>
                                    <span class="text-muted">Orig: ₹{{ $origWebPrice }}</span>
                                </small>
                            @endif
                        </td>
                        <td>
                            <input type="number"
                                   step="0.01"
                                   min="0"
                                   class="form-control form-control-sm loc-doctor-max-price"
                                   name="{{ $prefix }}[modes][{{ $modeKey }}][doctor_max_price]"
                                   value="{{ $docMax }}"
                                   placeholder="0.00">
                            @if($origDocMax !== null)
                                <small class="text-warning d-block mt-1" style="line-height: 1.1;">
                                    <i class="fas fa-exclamation-triangle"></i> Temp Active<br>
                                    <span class="text-muted">Orig: ₹{{ $origDocMax }}</span>
                                </small>
                            @endif
                        </td>
                        <td class="loc-final-allowed">₹<span class="loc-final-val">{{ $docMax !== '' && $docMax !== null ? number_format((float) $docMax, 0) : '0' }}</span></td>
                        <td><span class="badge badge-success">Active</span></td>
                        <td class="text-center">
                            <button type="button" class="btn btn-sm btn-outline-danger loc-mode-row-remove">Remove</button>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="loc-doc-rule">
        <strong>Doctor rule:</strong> If doctor enters an amount higher than Doctor Max Price, the system will block submission and show an error.
    </div>
</div>
