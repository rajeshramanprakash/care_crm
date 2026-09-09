{{-- Shared sub-service + pricing block for vendor & freelancer registration --}}
<div id="provider_service_picker_wrap" style="display:none;">
  <div id="provider_sub_service_picker_wrap" class="mt-1" style="display:none;">
    <label for="provider_sub_service_add_select"><span data-i18n="provider.sub_services">Sub-service</span> <span class="required">*</span></label>
    <select id="provider_sub_service_add_select" class="form-control">
      <option value="" data-i18n="provider.select_service">Select sub-service to add</option>
    </select>
    <span class="provider-help" data-i18n="provider.help_sub_multi">You can add multiple sub-services. Choose tags for each sub-service separately.</span>
  </div>

  <div id="provider_service_tags_wrap" class="mt-2" style="display:none;">
    <label data-i18n="provider.tags">Service tags</label>
    <div class="provider-tags-grid" id="provider_service_tags_grid"></div>
    <span class="provider-help">Select tags for this service.</span>
  </div>

  <div id="provider_sub_service_tags_stack" class="mt-2"></div>

  <div id="provider_sub_service_summary_wrap" class="provider-sub-summary" style="display:none;">
    <h6><i class="fas fa-list-check"></i> Selected sub-services &amp; tags</h6>
    <div id="provider_sub_service_summary_list"></div>
  </div>

  <input type="hidden" name="service_sub_services" id="service_sub_services_json" value="[]">
</div>

<div id="provider_price_panel_wrap" class="provider-price-panel" style="display:none;">
  <label class="d-block font-weight-bold mb-1" data-i18n="provider.pricing_title">Location pricing (₹)</label>
  <p class="provider-help mb-2" id="provider_price_hint" data-i18n="provider.price_hint">After selecting 12/24 hr or One-time, prices will appear here. Enter how far (km) you can work for each selected shift.</p>
  <div id="provider_price_table_wrap"></div>
</div>
