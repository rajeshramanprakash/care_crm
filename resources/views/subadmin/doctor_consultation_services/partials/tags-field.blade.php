@php
    $isEdit = isset($item);
    $tagsTextOld = old('specialization_options_text');
    if ($isEdit) {
        $tagsText = $tagsTextOld !== null ? $tagsTextOld : (is_array($item->specialization_options) ? implode("\n", $item->specialization_options) : '');
    } else {
        $tagsText = $tagsTextOld ?? '';
    }
@endphp

<div class="form-group mb-0 mt-4" id="dcs_main_tags_group">
    <label class="dcs-label" for="dcs_tag_input">
        Tags for doctors
        <span class="text-muted font-weight-normal">(optional)</span>
    </label>
    <p class="dcs-hint mb-2 dcs-main-tags-hint-active">Jab doctor registration me yeh service choose karega, in tags me se multiple select kar sakta hai.</p>
    <p class="dcs-hint mb-2 dcs-main-tags-hint-disabled text-muted d-none">
        Sub-services add ki hain — tags ab har sub-service par set karein (yahan ki zaroorat nahi).
    </p>
    <div class="dcs-tags-box" id="dcs_tags_box">
        <div class="dcs-tags-list" id="dcs_tags_list" aria-live="polite"></div>
        <input type="text"
               class="dcs-tags-input"
               id="dcs_tag_input"
               maxlength="255"
               placeholder="Type tag name, press Enter"
               autocomplete="off">
    </div>
    <textarea class="d-none @error('specialization_options_text') is-invalid @enderror"
              id="specialization_options_text"
              name="specialization_options_text"
              rows="1"
              aria-hidden="true">{{ $tagsText }}</textarea>
    @error('specialization_options_text')
        <span class="invalid-feedback d-block"><strong>{{ $message }}</strong></span>
    @enderror
</div>
