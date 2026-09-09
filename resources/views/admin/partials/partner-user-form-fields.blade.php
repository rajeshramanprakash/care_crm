@php
    $partnerLabel = $partner_type === 'broker' ? 'broker' : 'insurer';
    $companyDocs = is_array($item->company_documents ?? null) ? $item->company_documents : [];
@endphp

<div class="row">
    <div class="col-md-6 form-group">
        <label for="name">Full name <span class="text-danger">*</span></label>
        <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $item->name) }}" required maxlength="255">
        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6 form-group">
        <label for="company_name">Company name</label>
        <input type="text" name="company_name" id="company_name" class="form-control @error('company_name') is-invalid @enderror" value="{{ old('company_name', $item->company_name) }}" maxlength="255">
        @error('company_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6 form-group">
        <label for="username">Username <span class="text-danger">*</span></label>
        <input type="text" name="username" id="username" class="form-control @error('username') is-invalid @enderror" value="{{ old('username', $item->username) }}" required maxlength="100" autocomplete="off">
        @error('username')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6 form-group">
        <label for="password">Password {{ $item->exists ? '(leave blank to keep)' : '' }} @if(!$item->exists)<span class="text-danger">*</span>@endif</label>
        <input type="text" name="password" id="password" class="form-control @error('password') is-invalid @enderror" {{ $item->exists ? '' : 'required' }} minlength="6" maxlength="100" autocomplete="new-password">
        <small class="text-muted">Share with the {{ $partnerLabel }} for first login.</small>
        @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6 form-group">
        <label for="email">Email</label>
        <input type="email" name="email" id="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $item->email) }}" maxlength="255">
        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6 form-group">
        <label for="mobile">Mobile no. <span class="text-muted">(optional)</span></label>
        <input type="text" name="mobile" id="mobile" class="form-control @error('mobile') is-invalid @enderror" value="{{ old('mobile', $item->mobile) }}" maxlength="10" pattern="[0-9]{10}" placeholder="10-digit number">
        @error('mobile')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
</div>

<hr class="my-3">
<h5 class="mb-3 text-secondary"><i class="fas fa-file-alt mr-1"></i> Documents</h5>

<div class="row">
    <div class="col-md-6 form-group">
        <label for="mou_file">Attach MOU <span class="text-muted">(PDF only)</span></label>
        <input type="file" name="mou_file" id="mou_file" class="form-control-file @error('mou_file') is-invalid @enderror" accept=".pdf,application/pdf">
        @error('mou_file')<div class="text-danger small">{{ $message }}</div>@enderror
        @if($item->mou_file)
            <div class="mt-2 p-2 border rounded bg-light">
                <a href="{{ asset('storage/'.$item->mou_file) }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-primary">
                    <i class="fas fa-file-pdf mr-1"></i> View current MOU
                </a>
                <div class="custom-control custom-checkbox mt-2">
                    <input type="checkbox" class="custom-control-input" name="remove_mou" id="remove_mou" value="1">
                    <label class="custom-control-label text-danger" for="remove_mou">Remove current MOU on save</label>
                </div>
            </div>
        @endif
    </div>
    <div class="col-md-6 form-group">
        <label for="company_documents">Documents of company <span class="text-muted">(PDF, JPEG, PNG — multiple)</span></label>
        <input type="file" name="company_documents[]" id="company_documents" class="form-control-file @error('company_documents') is-invalid @enderror @error('company_documents.*') is-invalid @enderror" accept=".pdf,.jpg,.jpeg,.png,image/jpeg,image/png,application/pdf" multiple>
        <small class="text-muted d-block mt-1">You can select multiple files at once. New files are added to existing ones on edit.</small>
        @error('company_documents')<div class="text-danger small">{{ $message }}</div>@enderror
        @error('company_documents.*')<div class="text-danger small">{{ $message }}</div>@enderror
    </div>
</div>

@if(count($companyDocs) > 0)
    <div class="form-group">
        <label class="d-block">Uploaded company documents</label>
        <ul class="list-group list-group-flush border rounded">
            @foreach($companyDocs as $docPath)
                @if($docPath)
                    @php
                        $ext = strtolower(pathinfo($docPath, PATHINFO_EXTENSION));
                        $icon = $ext === 'pdf' ? 'fa-file-pdf text-danger' : 'fa-file-image text-primary';
                    @endphp
                    <li class="list-group-item d-flex align-items-center justify-content-between py-2">
                        <a href="{{ asset('storage/'.$docPath) }}" target="_blank" rel="noopener">
                            <i class="fas {{ $icon }} mr-2"></i>{{ basename($docPath) }}
                        </a>
                        <div class="custom-control custom-checkbox mb-0">
                            <input type="checkbox" class="custom-control-input" name="remove_company_documents[]" id="remove_doc_{{ $loop->index }}" value="{{ $docPath }}">
                            <label class="custom-control-label text-danger small" for="remove_doc_{{ $loop->index }}">Remove</label>
                        </div>
                    </li>
                @endif
            @endforeach
        </ul>
    </div>
@endif

<div class="form-group">
    <div class="custom-control custom-checkbox">
        <input type="checkbox" class="custom-control-input" name="is_active" id="is_active" value="1" @checked(old('is_active', $item->is_active ?? true))>
        <label class="custom-control-label" for="is_active">Account active (can login)</label>
    </div>
</div>
