{{-- Multi-service block for admin vendor create/edit --}}
@php
    $formPrefix = $formPrefix ?? 'create';
    $blocks = $blocks ?? [];
@endphp

<div class="av-services-wrap mt-3" id="{{ $formPrefix }}_vendor_services_wrap">
    <h6 class="mb-2"><i class="fas fa-briefcase-medical text-warning mr-1"></i> Services, sub-services, tags &amp; pricing</h6>
    <p class="text-muted small mb-2">Pehle location select karein, phir ek se zyada services add kar sakte hain. Har service ke sub-services aur tags alag set honge.</p>
    <input type="hidden" name="vendor_services" id="{{ $formPrefix }}_vendor_services_json" value="[]">
    <div id="{{ $formPrefix }}_vendor_services_blocks"></div>
    <button type="button" class="btn btn-outline-success btn-sm mt-2" id="{{ $formPrefix }}_add_vendor_service" data-form-prefix="{{ $formPrefix }}">
        <i class="fas fa-plus mr-1"></i> Add service
    </button>
</div>

<template id="{{ $formPrefix }}_vendor_service_block_tpl">
    <div class="card border mb-3 av-service-block" data-block-index="__BLOCK_INDEX__">
        <div class="card-header py-2 px-3 d-flex justify-content-between align-items-center" style="background:#fff7f0;">
            <strong class="small text-dark"><i class="fas fa-layer-group mr-1"></i> Service __BLOCK_NUM__</strong>
            <button type="button" class="btn btn-sm btn-outline-danger av-remove-service-block" title="Remove service">
                <i class="fas fa-trash-alt"></i>
            </button>
        </div>
        <div class="card-body p-3">
            <div class="form-group mb-2">
                <label class="font-weight-bold mb-1">Job Title (Service) <span class="text-danger">*</span></label>
                <select class="form-control av-service-select" data-block-index="__BLOCK_INDEX__">
                    <option value="">Select service</option>
                </select>
            </div>
            <div class="av-sub-picker-wrap" style="display:none;">
                <label class="font-weight-bold">Sub-service</label>
                <select class="form-control av-sub-add-select">
                    <option value="">Select sub-service to add</option>
                </select>
                <small class="text-muted d-block mt-1">Ek se zyada sub-service add kar sakte hain.</small>
            </div>
            <div class="av-service-tags-wrap mt-2" style="display:none;">
                <label class="font-weight-bold">Service tags</label>
                <div class="av-tags-grid av-service-tags-grid"></div>
            </div>
            <div class="av-sub-tags-stack mt-2"></div>
            <div class="av-price-panel mt-3" style="display:none;">
                <label class="font-weight-bold d-block mb-1">Pricing (₹)</label>
                <small class="text-muted d-block mb-2">Location default grey mein dikhega. Vendor ke liye alag price ho to edit karein.</small>
                <div class="av-price-table-wrap"></div>
            </div>
        </div>
    </div>
</template>
