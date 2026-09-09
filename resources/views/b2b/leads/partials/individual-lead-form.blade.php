<div class="form-group">
    <label class="b2b-modal-label">Number <span class="text-danger">*</span></label>
    <input type="text" name="number" class="form-control b2b-modal-input @error('number') is-invalid @enderror" value="{{ old('number') }}" maxlength="10" placeholder="10-digit mobile" required>
    @error('number')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>
<div class="form-group">
    <label class="b2b-modal-label">Service <span class="text-muted font-weight-normal">(optional)</span></label>
    <select name="service" class="form-control b2b-modal-input @error('service') is-invalid @enderror">
        <option value="">Select service (optional)</option>
        @foreach($serviceOptions as $opt)
            <option value="{{ $opt }}" {{ old('service') === $opt ? 'selected' : '' }}>{{ $opt }}</option>
        @endforeach
    </select>
    @error('service')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>
<div class="form-group">
    <label class="b2b-modal-label">Detail <span class="text-muted font-weight-normal">(optional)</span></label>
    <textarea name="detail" class="form-control b2b-modal-input @error('detail') is-invalid @enderror" rows="4" placeholder="Additional notes — shown to sales as Query Remark">{{ old('detail') }}</textarea>
    @error('detail')<div class="invalid-feedback">{{ $message }}</div>@enderror
    <small class="text-muted">If filled, this appears as <strong>Query Remark</strong> for the sales team.</small>
</div>
<div class="form-group">
    <label class="b2b-modal-label">Bulk <span class="text-danger">*</span></label>
    <input type="number" name="bulk" min="1" step="1" class="form-control b2b-modal-input @error('bulk') is-invalid @enderror" value="{{ old('bulk') }}" placeholder="Numeric quantity" required>
    @error('bulk')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>
