@php
    $doctorPricingBlocks = $doctorPricingBlocks ?? [];
    if ($doctorPricingBlocks === []) {
        $doctorPricingBlocks = [[]];
    }
    $vendorServiceRows = $vendorServiceRows ?? [];
    $freelancerServiceRows = $freelancerServiceRows ?? [];
    $servicesJson = ($doctorConsultationServices ?? collect())->map(function ($svc) {
        return [
            'id' => (int) $svc->id,
            'name' => (string) $svc->name,
            'sub_services' => $svc->subServices->map(fn ($sub) => [
                'id' => (int) $sub->id,
                'name' => (string) $sub->name,
            ])->values()->all(),
        ];
    })->values()->all();
    $modeLabels = \App\Models\LocationDoctorConsultationPrice::MODE_LABELS;
    $vendorServicesJson = ($services ?? collect())->map(function ($s) {
        return [
            'id' => (int) $s->id,
            'name' => (string) $s->name,
            'sub_services' => $s->subServices->map(fn ($sub) => [
                'id' => (int) $sub->id,
                'name' => (string) $sub->name,
            ])->values()->all(),
        ];
    })->values()->all();
@endphp

<div class="loc-pricing-sections">
    <div class="loc-pricing-pills" role="tablist">
        <button type="button" class="loc-pricing-pill active" data-pricing-tab="doctor">Doctor Pricing</button>
        <button type="button" class="loc-pricing-pill" data-pricing-tab="vendor">Vendor Pricing</button>
        <button type="button" class="loc-pricing-pill" data-pricing-tab="freelancer">Freelancer Pricing</button>
    </div>
    <p class="loc-pricing-hint mb-2">
        Har location ki alag prices save hoti hain. <strong>Doctor</strong>: service select karein — agar us service ke andar sub-services hain to <strong>sub-service select karke</strong> Online / Home Visit / Clinic ki price set karein (wo price us sub-service ki hogi). Sub-service na ho to service-level price save hogi.
        <strong>Vendor &amp; Freelancer</strong>: service select karein — agar sub-services hain to <strong>sub-service select karke</strong> 12hr, 24hr aur One-time price set karein; sub-service na ho to service-level price save hogi.
    </p>

    <div class="loc-pricing-panel active" id="loc_pricing_panel_doctor" data-pricing-panel="doctor">
        <div id="loc_doctor_pricing_blocks">
            @foreach($doctorPricingBlocks as $bIndex => $block)
                @include('admin.locations.partials.doctor-pricing-block', [
                    'blockIndex' => $bIndex,
                    'block' => $block,
                    'doctorConsultationServices' => $doctorConsultationServices ?? collect(),
                    'modeLabels' => $modeLabels,
                ])
            @endforeach
        </div>
        <button type="button" class="btn btn-outline-success btn-sm" id="loc_add_doctor_pricing_block">
            <i class="fas fa-plus mr-1"></i> Add doctor service pricing
        </button>
    </div>

    <div class="loc-pricing-panel" id="loc_pricing_panel_vendor" data-pricing-panel="vendor">
        @include('admin.locations.partials.provider-pricing-panel', [
            'inputPrefix' => 'vendor_services',
            'containerId' => 'vendor-services-container',
            'addBtnId' => 'add-vendor-service',
            'rows' => $vendorServiceRows,
            'panelTitle' => 'vendor service',
            'panelHint' => 'Is location ke liye vendor services — sub-service ho to uski alag 12hr, 24hr, one-time price set karein.',
        ])
    </div>

    <div class="loc-pricing-panel" id="loc_pricing_panel_freelancer" data-pricing-panel="freelancer">
        @include('admin.locations.partials.provider-pricing-panel', [
            'inputPrefix' => 'freelancer_services',
            'containerId' => 'freelancer-services-container',
            'addBtnId' => 'add-freelancer-service',
            'rows' => $freelancerServiceRows,
            'panelTitle' => 'freelancer service',
            'panelHint' => 'Is location ke liye freelancer services — sub-service ho to uski alag 12hr, 24hr, one-time price set karein.',
        ])
    </div>
</div>

<script type="application/json" id="loc_doctor_services_json">@json($servicesJson)</script>
<script type="application/json" id="loc_vendor_services_json">@json($vendorServicesJson)</script>

<template id="loc_doctor_pricing_block_tpl">
    @include('admin.locations.partials.doctor-pricing-block', [
        'blockIndex' => '__BLOCK_INDEX__',
        'block' => [],
        'doctorConsultationServices' => $doctorConsultationServices ?? collect(),
        'modeLabels' => $modeLabels,
    ])
</template>

<template id="loc_provider_service_row_tpl">
    @include('admin.locations.partials.service-row', [
        'index' => '__ROW_INDEX__',
        'inputPrefix' => '__INPUT_PREFIX__',
        'selectedServiceId' => null,
        'selectedSubServiceId' => 0,
        'price12' => null,
        'price24' => null,
        'priceOnetime' => null,
        'showRemove' => true,
    ])
</template>

@include('admin.locations.partials.doctor-pricing-script')
