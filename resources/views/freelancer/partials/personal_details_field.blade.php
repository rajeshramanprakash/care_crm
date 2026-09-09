@php
    $inputId = $inputId ?? ($fieldName . '_input');
    $inputType = $inputType ?? 'text';
    $placeholder = $placeholder ?? $label;
    $filled = $filled ?? false;
@endphp
<tr>
    <th>{{ $label }}</th>
    <td>
        @if($filled)
            <span class="fl-field-value">{!! $displayValue ?? e($value) !!}</span>
        @else
            <div class="fl-field-edit">
                @if($inputType === 'select')
                    <select class="form-control form-control-sm" id="{{ $inputId }}">
                        @foreach($options ?? [] as $optVal => $optLabel)
                            <option value="{{ $optVal }}">{{ $optLabel }}</option>
                        @endforeach
                    </select>
                @else
                    <input type="{{ $inputType }}" class="form-control form-control-sm" id="{{ $inputId }}"
                           placeholder="{{ $placeholder }}" @if(!empty($inputAttrs)) {!! $inputAttrs !!} @endif>
                @endif
                <button type="button" class="btn btn-sm fl-save-btn" onclick="saveField('{{ $fieldName }}', '{{ $inputId }}')">
                    <i class="fas fa-save"></i> Save
                </button>
            </div>
            <small class="fl-field-hint">This field needs to be filled</small>
        @endif
    </td>
</tr>
