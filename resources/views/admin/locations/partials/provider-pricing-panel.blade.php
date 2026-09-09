@php
    $inputPrefix = $inputPrefix ?? 'vendor_services';
    $containerId = $containerId ?? 'vendor-services-container';
    $addBtnId = $addBtnId ?? 'add-vendor-service';
    $rows = $rows ?? [];
    if ($rows === []) {
        $rows = [['service_id' => null, 'service_sub_service_id' => 0, 'price_12hr' => null, 'price_24hr' => null, 'price_onetime' => null]];
    }
    $panelTitle = $panelTitle ?? 'Services';
    $panelHint = $panelHint ?? 'Service select karein — agar sub-services hain to sub-service select karke 12hr, 24hr aur one-time price set karein. Sub-service na ho to service-level price save hogi.';
@endphp

<div class="loc-provider-pricing-panel">
    <p class="text-muted small mb-3">{{ $panelHint }}</p>
    <div class="loc-services-scroll" id="{{ $containerId }}">
        @foreach($rows as $index => $row)
            @include('admin.locations.partials.service-row', [
                'index' => $index,
                'inputPrefix' => $inputPrefix,
                'selectedServiceId' => $row['service_id'] ?? null,
                'selectedSubServiceId' => (int) ($row['service_sub_service_id'] ?? 0),
                'price12' => $row['price_12hr'] ?? null,
                'price24' => $row['price_24hr'] ?? null,
                'priceOnetime' => $row['price_onetime'] ?? null,
                'showRemove' => count($rows) > 1,
            ])
        @endforeach
    </div>
    <button type="button" class="btn btn-outline-success btn-sm mt-2 loc-add-provider-service" id="{{ $addBtnId }}" data-container="{{ $containerId }}" data-prefix="{{ $inputPrefix }}">
        <i class="fas fa-plus mr-1"></i> Add {{ strtolower($panelTitle) }}
    </button>
</div>
