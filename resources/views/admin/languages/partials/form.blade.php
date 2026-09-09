<div class="row">
    <div class="col-md-{{ !empty($simple_create) ? '12' : '6' }}">
        <div class="form-group">
            <label for="name">Language name <span class="text-danger">*</span></label>
            <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror"
                   value="{{ old('name', $language->name ?? '') }}" required placeholder="e.g. Hindi, Tamil, French">
            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            @if(!empty($simple_create))
                <small class="text-muted">Code auto-generate hoga (Hindi → hi). Translation fields manually bharna nahi padega.</small>
            @endif
        </div>
    </div>
    @empty($simple_create)
    <div class="col-md-6">
        <div class="form-group">
            <label for="code">Code <span class="text-danger">*</span></label>
            <input type="text" name="code" id="code" class="form-control @error('code') is-invalid @enderror"
                   value="{{ old('code', $language->code ?? '') }}" required placeholder="e.g. hi" maxlength="10">
            <small class="text-muted">Short code (en, hi, ta). Used by registration form.</small>
            @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
    @endempty
    <div class="col-md-6">
        <div class="form-group">
            <label for="native_name">Native name (optional)</label>
            <input type="text" name="native_name" id="native_name" class="form-control"
                   value="{{ old('native_name', $language->native_name ?? '') }}" placeholder="e.g. हिन्दी">
        </div>
    </div>
    <div class="col-md-3">
        <div class="form-group">
            <label for="sort_order">Sort order</label>
            <input type="number" name="sort_order" id="sort_order" class="form-control" min="0"
                   value="{{ old('sort_order', $language->sort_order ?? 0) }}">
        </div>
    </div>
    <div class="col-md-3">
        <div class="form-group mt-4 pt-2">
            <div class="custom-control custom-checkbox">
                <input type="checkbox" class="custom-control-input" id="is_active" name="is_active" value="1"
                       {{ old('is_active', $language->is_active ?? true) ? 'checked' : '' }}>
                <label class="custom-control-label" for="is_active">Active (show on registration)</label>
            </div>
        </div>
    </div>
</div>
