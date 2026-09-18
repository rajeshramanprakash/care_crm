@php
    /** @var \App\Models\DoctorRequest $doctor */
    /** @var string $inputSize sm|default */
    $inputSize = $inputSize ?? 'sm';
    $colClass = $colClass ?? 'col-md-4';
    $inputCls = $inputSize === 'sm' ? 'form-control form-control-sm' : 'form-control';
    $selectCls = $inputSize === 'sm' ? 'custom-select custom-select-sm' : 'custom-select';
    $fields = [
        [
            'label' => 'Online',
            'value' => $doctor->referral_commission_online,
            'type' => $doctor->referral_commission_online_type ?? 'fixed',
            'valClass' => $valueClassOnline ?? 'dr-ref-comm-val-online',
            'typeClass' => $typeClassOnline ?? 'dr-ref-comm-type-online',
            'valId' => $valueIdOnline ?? null,
            'typeId' => $typeIdOnline ?? null,
        ],
        [
            'label' => 'Home visit',
            'value' => $doctor->referral_commission_home_visit,
            'type' => $doctor->referral_commission_home_visit_type ?? 'fixed',
            'valClass' => $valueClassHome ?? 'dr-ref-comm-val-home',
            'typeClass' => $typeClassHome ?? 'dr-ref-comm-type-home',
            'valId' => $valueIdHome ?? null,
            'typeId' => $typeIdHome ?? null,
        ],
        [
            'label' => 'Clinic',
            'value' => $doctor->referral_commission_clinic,
            'type' => $doctor->referral_commission_clinic_type ?? 'fixed',
            'valClass' => $valueClassClinic ?? 'dr-ref-comm-val-clinic',
            'typeClass' => $typeClassClinic ?? 'dr-ref-comm-type-clinic',
            'valId' => $valueIdClinic ?? null,
            'typeId' => $typeIdClinic ?? null,
        ],
    ];
@endphp
<div class="form-row dr-ref-comm-fields">
    @foreach($fields as $field)
        @php
            $typeVal = strtolower((string) ($field['type'] ?? 'fixed')) === 'percent' ? 'percent' : 'fixed';
        @endphp
        <div class="form-group {{ $colClass }} mb-2">
            <label class="small d-block mb-1">{{ $field['label'] }}</label>
            <div class="input-group dr-ref-comm-input-group" data-ref-mode="{{ strtolower($field['label']) }}">
                <div class="input-group-prepend">
                    <select @if(!empty($field['typeId'])) id="{{ $field['typeId'] }}" @endif class="{{ $selectCls }} dr-ref-comm-type {{ $field['typeClass'] }}" aria-label="{{ $field['label'] }} commission type">
                        <option value="fixed" @selected($typeVal === 'fixed')>₹ Fix</option>
                        <option value="percent" @selected($typeVal === 'percent')>%</option>
                    </select>
                </div>
                <input type="number"
                       @if(!empty($field['valId'])) id="{{ $field['valId'] }}" @endif
                       class="{{ $inputCls }} dr-ref-comm-val {{ $field['valClass'] }}"
                       min="0"
                       step="0.01"
                       data-comm-type="{{ $typeVal }}"
                       value="{{ $field['value'] !== null ? $field['value'] : '' }}"
                       placeholder="{{ $typeVal === 'percent' ? 'e.g. 10' : 'e.g. 500' }}">
            </div>
            <small class="text-muted dr-ref-comm-hint">{{ $typeVal === 'percent' ? '% of paid booking amount' : 'Fixed ₹ per paid booking' }}</small>
        </div>
    @endforeach
</div>
