@php
    $isEdit = isset($item);
@endphp

<form action="{{ $formAction }}" method="POST" class="dcs-form" enctype="multipart/form-data" novalidate>
    @csrf
    @if($formMethod ?? false)
        @method($formMethod)
    @endif

    <div class="dcs-form-wrap">
        <div class="dcs-panel">
            <div class="dcs-panel-head">
                <h2><i class="fas fa-stethoscope"></i> Service details</h2>
            </div>
            <div class="dcs-panel-body">
                <div class="form-group mb-4">
                    <label class="dcs-label" for="name">Service name <span class="req">*</span></label>
                    <input type="text"
                           class="form-control @error('name') is-invalid @enderror"
                           id="name"
                           name="name"
                           value="{{ old('name', $isEdit ? $item->name : '') }}"
                           required
                           maxlength="255"
                           placeholder="e.g. General Physician Consultation"
                           autocomplete="off">
                    @error('name')
                        <span class="invalid-feedback d-block"><strong>{{ $message }}</strong></span>
                    @enderror
                    <p class="dcs-hint mb-0">Website consultation page aur doctor registration me dikhega.</p>
                </div>

                <div class="form-group mb-4">
                    <label class="dcs-label" for="icon">Service icon</label>
                    @if($isEdit && $item->icon_path)
                        <div class="mb-2">
                            <img src="{{ asset('storage/' . ltrim(str_replace('\\', '/', $item->icon_path), '/')) }}" alt="" width="72" height="72" style="object-fit:contain;border-radius:12px;border:1px solid #e9ecef;padding:8px;background:#fff;">
                        </div>
                        <div class="custom-control custom-checkbox mb-2">
                            <input type="checkbox" class="custom-control-input" id="remove_icon" name="remove_icon" value="1" {{ old('remove_icon') ? 'checked' : '' }}>
                            <label class="custom-control-label" for="remove_icon">Remove current icon</label>
                        </div>
                    @endif
                    <input type="file"
                           class="form-control-file @error('icon') is-invalid @enderror"
                           id="icon"
                           name="icon"
                           accept="image/jpeg,image/png,image/webp">
                    @error('icon')
                        <span class="invalid-feedback d-block"><strong>{{ $message }}</strong></span>
                    @enderror
                    <p class="dcs-hint mb-0">PNG, JPG or WebP. Consultation page par service card me dikhega (recommended ~120×120).</p>
                </div>

                <div class="row">
                    <div class="col-sm-6">
                        <div class="form-group mb-4 mb-sm-0">
                            <label class="dcs-label" for="consultation_duration_minutes">Session duration <span class="text-muted font-weight-normal">(optional)</span></label>
                            <div class="dcs-duration-wrap">
                                <input type="number"
                                       class="form-control @error('consultation_duration_minutes') is-invalid @enderror"
                                       id="consultation_duration_minutes"
                                       name="consultation_duration_minutes"
                                       value="{{ old('consultation_duration_minutes', $isEdit ? ($item->consultation_duration_minutes ?? '') : '') }}"
                                       min="1"
                                       max="1440"
                                       placeholder="30">
                                <span class="dcs-duration-suffix">min</span>
                            </div>
                            @error('consultation_duration_minutes')
                                <span class="invalid-feedback d-block"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="form-group mb-0">
                            <label class="dcs-label" for="sort_order">Display order</label>
                            <input type="number"
                                   class="form-control @error('sort_order') is-invalid @enderror"
                                   id="sort_order"
                                   name="sort_order"
                                   value="{{ old('sort_order', $isEdit ? $item->sort_order : 0) }}"
                                   min="0"
                                   placeholder="0">
                            @error('sort_order')
                                <span class="invalid-feedback d-block"><strong>{{ $message }}</strong></span>
                            @enderror
                            <p class="dcs-hint mb-0">Chhota number pehle dikhega.</p>
                        </div>
                    </div>
                </div>

                @include('admin.doctor_consultation_services.partials.tags-field', ['item' => $item ?? null])

            </div>
        </div>

        @include('admin.doctor_consultation_services.partials.sub-services-field', ['item' => $item ?? null])

        <div class="dcs-actions mt-3">
            <button type="submit" class="btn btn-dcs-primary">
                <i class="fas fa-check mr-1"></i> {{ $submitLabel }}
            </button>
            <a href="{{ route('subadmin.doctor_consultation_services.index') }}" class="btn btn-dcs-ghost">Cancel</a>
        </div>
    </div>
</form>
