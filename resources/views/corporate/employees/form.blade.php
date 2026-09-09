@extends('corporate.layouts.app')
@section('title', $item->exists ? 'Edit Employee' : 'Add Employee')
@section('page_title', $item->exists ? 'Edit Employee' : 'Add Employee')

@section('content')
<form method="POST" action="{{ $item->exists ? route('corporate.employees.update', $item) : route('corporate.employees.store') }}" enctype="multipart/form-data">
    @csrf
    @if($item->exists) @method('PUT') @endif

    <div class="row">
        <div class="col-md-6">
            <div class="form-group">
                <label>Employee ID <span class="text-danger">*</span></label>
                <input type="text" name="employee_id" class="form-control @error('employee_id') is-invalid @enderror" value="{{ old('employee_id', $item->employee_id) }}" required maxlength="100">
                @error('employee_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="form-group">
                <label>Employee Name <span class="text-danger">*</span></label>
                <input type="text" name="employee_name" class="form-control @error('employee_name') is-invalid @enderror" value="{{ old('employee_name', $item->employee_name) }}" required>
                @error('employee_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="form-group">
                <label>Date of Birth</label>
                <input type="date" name="date_of_birth" class="form-control @error('date_of_birth') is-invalid @enderror" value="{{ old('date_of_birth', $item->date_of_birth?->format('Y-m-d')) }}">
                @error('date_of_birth')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="form-group">
                <label>Phone Number</label>
                <input type="text" name="phone_number" class="form-control @error('phone_number') is-invalid @enderror" value="{{ old('phone_number', $item->phone_number) }}" maxlength="20">
                @error('phone_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="form-group">
                <label>Gender</label>
                <select name="gender" class="form-control @error('gender') is-invalid @enderror">
                    <option value="">— Select —</option>
                    @foreach(['Male','Female','Other'] as $g)
                        <option value="{{ $g }}" @selected(old('gender', $item->gender) === $g)>{{ $g }}</option>
                    @endforeach
                </select>
                @error('gender')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $item->email) }}">
                @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="form-group">
                <label>Relationship</label>
                <input type="text" name="relationship" class="form-control @error('relationship') is-invalid @enderror" value="{{ old('relationship', $item->relationship) }}" maxlength="100">
                @error('relationship')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label>Issuance date</label>
                <input type="date" name="issuance_date" class="form-control @error('issuance_date') is-invalid @enderror" value="{{ old('issuance_date', $item->issuance_date?->format('Y-m-d')) }}">
                @error('issuance_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="form-group">
                <label>Last Working Date</label>
                <input type="date" name="last_working_date" class="form-control @error('last_working_date') is-invalid @enderror" value="{{ old('last_working_date', $item->last_working_date?->format('Y-m-d')) }}">
                @error('last_working_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="form-group">
                <label>SI Limit (numbers only)</label>
                <input type="text" name="si_limit" class="form-control @error('si_limit') is-invalid @enderror" value="{{ old('si_limit', $item->si_limit) }}" inputmode="decimal" pattern="^\d+(\.\d+)?$" title="Numbers only">
                @error('si_limit')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="form-group">
                <label>Active From</label>
                <input type="date" name="active_from" class="form-control @error('active_from') is-invalid @enderror" value="{{ old('active_from', $item->active_from?->format('Y-m-d')) }}">
                @error('active_from')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="form-group">
                <label>Active To</label>
                <input type="date" name="active_to" class="form-control @error('active_to') is-invalid @enderror" value="{{ old('active_to', $item->active_to?->format('Y-m-d')) }}">
                @error('active_to')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="form-group">
                <label>Room Limit (numbers only)</label>
                <input type="text" name="room_limit" class="form-control @error('room_limit') is-invalid @enderror" value="{{ old('room_limit', $item->room_limit) }}" inputmode="decimal" pattern="^\d+(\.\d+)?$" title="Numbers only">
                @error('room_limit')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="form-group">
                <label>Login password {{ $item->exists ? '(leave blank to keep)' : '' }} @if(!$item->exists)<span class="text-danger">*</span>@endif</label>
                <input type="text" name="password" class="form-control @error('password') is-invalid @enderror" {{ $item->exists ? '' : 'required' }} minlength="6" autocomplete="new-password">
                @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="form-group">
                <label>Policy Terms &amp; Conditions (PDF, JPG, PNG)</label>
                @if($item->policy_terms_file)
                    <p class="small mb-1"><a href="{{ $item->policyTermsUrl() }}" target="_blank" rel="noopener">Current file</a></p>
                @endif
                <input type="file" name="policy_terms_file" class="form-control-file @error('policy_terms_file') is-invalid @enderror" accept=".pdf,.jpg,.jpeg,.png">
                @error('policy_terms_file')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            </div>
            <div class="custom-control custom-checkbox mb-3">
                <input type="checkbox" class="custom-control-input" name="is_active" id="is_active" value="1" @checked(old('is_active', $item->is_active ?? true))>
                <label class="custom-control-label" for="is_active">Active (can login)</label>
            </div>
        </div>
    </div>

    <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i> Save</button>
    <a href="{{ route('corporate.employees.index') }}" class="btn btn-secondary ml-2">Cancel</a>
</form>
@endsection
