@php
    $part = $part ?? 'all';
    $clsrCssRes = resource_path('assets/crm-lead-status-remarks.css');
    $clsrJsRes = resource_path('assets/lead-status-remarks.js');
    $clsrCssPublic = public_path('css/crm-lead-status-remarks.css');
    $clsrJsPublic = public_path('js/lead-status-remarks.js');
    $clsrCssPath = file_exists($clsrCssRes) ? $clsrCssRes : (file_exists($clsrCssPublic) ? $clsrCssPublic : null);
    $clsrJsPath = file_exists($clsrJsRes) ? $clsrJsRes : (file_exists($clsrJsPublic) ? $clsrJsPublic : null);
@endphp

@if (in_array($part, ['css', 'all'], true) && $clsrCssPath)
    @once('lead-status-remarks-css')
        <style id="clsr-remarks-css">{!! file_get_contents($clsrCssPath) !!}</style>
    @endonce
@endif

@if (in_array($part, ['js', 'all'], true) && $clsrJsPath)
    @once('lead-status-remarks-js')
        <script>window.__leadStatusRemarksAssets = { cssUrl: '' };</script>
        <script>{!! file_get_contents($clsrJsPath) !!}</script>
    @endonce
@endif
