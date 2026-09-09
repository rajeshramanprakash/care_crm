@extends('broker.layouts.app')
@section('title', 'Reset Password')
@section('page_title', 'Reset Password')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card">
            <div class="card-body">
                <p class="text-muted">Change your login password. You will need your current password.</p>
                <form method="POST" action="{{ route('broker.password.update') }}">
                    @csrf
                    @method('PUT')
                    <div class="form-group">
                        <label for="current_password">Current password</label>
                        <input type="password" name="current_password" id="current_password" class="form-control @error('current_password') is-invalid @enderror" required>
                        @error('current_password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group">
                        <label for="password">New password</label>
                        <input type="password" name="password" id="password" class="form-control @error('password') is-invalid @enderror" required minlength="6">
                        @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group">
                        <label for="password_confirmation">Confirm new password</label>
                        <input type="password" name="password_confirmation" id="password_confirmation" class="form-control" required minlength="6">
                    </div>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i> Update password</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
