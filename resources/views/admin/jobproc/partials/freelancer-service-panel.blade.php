{{-- Freelancer service / sub-service / per-freelancer price panel for JobProc add & edit --}}
@php
    $pfx = $fieldPrefix ?? '';
@endphp
<div class="jp-service-section mt-2 mb-3" id="{{ $pfx }}jp_service_section" style="display:none;">
    <div class="card border-0 shadow-sm">
        <div class="card-header py-2 px-3" style="background:#fff7f0;border-bottom:1px solid #f0e0d0;">
            <strong style="color:#ea8a2b;"><i class="fas fa-briefcase-medical mr-1"></i> Service, sub-services &amp; pricing</strong>
        </div>
        <div class="card-body p-3">
            <p class="small text-muted mb-2">Location ke freelancer services se select karein. Price change sirf is freelancer par apply hogi — baaki freelancers location wali default price use karenge.</p>

            <div id="{{ $pfx }}jp_sub_service_picker_wrap" style="display:none;">
                <label class="font-weight-bold">Sub-service <span class="text-danger">*</span></label>
                <select class="form-control" id="{{ $pfx }}jp_sub_service_add_select">
                    <option value="">Select sub-service to add</option>
                </select>
                <small class="text-muted d-block mt-1">Ek se zyada sub-service add kar sakte hain.</small>
            </div>

            <div id="{{ $pfx }}jp_service_tags_wrap" class="mt-2" style="display:none;">
                <label class="font-weight-bold">Service tags</label>
                <div class="jp-tags-grid" id="{{ $pfx }}jp_service_tags_grid"></div>
            </div>

            <div id="{{ $pfx }}jp_sub_service_tags_stack" class="mt-2"></div>

            <div id="{{ $pfx }}jp_price_panel_wrap" class="mt-3" style="display:none;">
                <label class="font-weight-bold d-block mb-1">Pricing (₹) — is freelancer ke liye</label>
                <small class="text-muted d-block mb-2">Location default niche grey mein dikhega. Sirf change karna ho toh input edit karein.</small>
                <div id="{{ $pfx }}jp_price_table_wrap"></div>
            </div>

            <input type="hidden" name="service_sub_services" id="{{ $pfx }}service_sub_services_json" value="[]">
            <input type="hidden" name="service_price_overrides" id="{{ $pfx }}service_price_overrides_json" value="[]">
        </div>
    </div>
</div>
