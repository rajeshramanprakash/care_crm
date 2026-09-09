@php
    $routePrefix = $partner_type === 'broker' ? 'broker' : 'insurer';
@endphp

<div class="alert alert-info py-2 small mb-3">
    <strong>Login link for this corporate:</strong>
    <a href="{{ $login_url }}" target="_blank" rel="noopener">{{ $login_url }}</a>
    <span class="text-muted"> — username &amp; password below are what they will use there.</span>
</div>

<form method="POST" action="{{ $item->exists ? route($routePrefix.'.corporates.update', $item) : route($routePrefix.'.corporates.store') }}">
    @csrf
    @if($item->exists)
        @method('PUT')
    @endif

    <div class="form-group">
        <label for="corporate_name">Corporate name <span class="text-danger">*</span></label>
        <input type="text" name="corporate_name" id="corporate_name" class="form-control @error('corporate_name') is-invalid @enderror" value="{{ old('corporate_name', $item->corporate_name) }}" required maxlength="255">
        @error('corporate_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="form-group">
        <label for="username">Username <span class="text-danger">*</span></label>
        <input type="text" name="username" id="username" class="form-control @error('username') is-invalid @enderror" value="{{ old('username', $item->username) }}" required maxlength="100" autocomplete="off">
        @error('username')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="form-group">
        <label for="password">Password {{ $item->exists ? '(leave blank to keep)' : '' }} @if(!$item->exists)<span class="text-danger">*</span>@endif</label>
        <input type="text" name="password" id="password" class="form-control @error('password') is-invalid @enderror" {{ $item->exists ? '' : 'required' }} minlength="6" maxlength="100" autocomplete="new-password">
        @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="custom-control custom-checkbox mb-3">
        <input type="checkbox" class="custom-control-input" name="is_active" id="is_active" value="1" @checked(old('is_active', $item->is_active ?? true))>
        <label class="custom-control-label" for="is_active">Account active (can login)</label>
    </div>

    <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i> {{ $item->exists ? 'Update' : 'Create' }} corporate</button>
    <a href="{{ route($routePrefix.'.corporates.index') }}" class="btn btn-secondary ml-2">Cancel</a>
</form>
