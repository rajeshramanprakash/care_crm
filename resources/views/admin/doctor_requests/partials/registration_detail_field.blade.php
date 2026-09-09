@php
    $full = $full ?? false;
    $valueHtml = $value ?? '—';
    $isEmpty = ($valueHtml === '—' || $valueHtml === '' || $valueHtml === null);
@endphp
<div @class(['dr-kv-item', 'dr-kv-item--full' => $full])>
    <dt>{{ $label }}</dt>
    <dd @class(['dr-kv-empty' => $isEmpty])>{!! $valueHtml !!}</dd>
</div>
