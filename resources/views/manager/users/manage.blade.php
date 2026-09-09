@extends('admin.layouts.app')

@section('title', isset($user->id) ? 'Edit User' : 'Add User')

@section('header-css')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.1/css/jquery.dataTables.min.css">
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
@endsection

@section('main')
<div class="content-wrapper pb-5">
    <section class="content-header">
        <div class="container-fluid">
            <div class="d-flex justify-content-between mb-2">
                <h1 class="m-0">{{ isset($user->id) ? 'Edit User' : 'Add User' }}</h1>
                <a href="{{ route('admin.users.index') }}" class="btn btn-secondary">Back to Users</a>
            </div>
        </div>
    </section>
    <section class="content">
        <div class="container-fluid">
            <div class="card">
                <div class="card-body">
                    <form
                        action="{{ isset($user->id) ? route('admin.users.manage_process', $user->id) : route('admin.users.manage_process') }}"
                        method="POST">
                        @csrf
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="f_name">First Name</label>
                                    <input type="text" class="form-control @error('f_name') is-invalid @enderror"
                                        id="f_name" name="f_name" value="{{ old('f_name', $user->f_name ?? '') }}" required>
                                    @error('f_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="l_name">Last Name</label>
                                    <input type="text" class="form-control @error('l_name') is-invalid @enderror"
                                        id="l_name" name="l_name" value="{{ old('l_name', $user->l_name ?? '') }}" required>
                                    @error('l_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="email">Email</label>
                                    <input type="email" class="form-control @error('email') is-invalid @enderror" id="email"
                                        name="email" value="{{ old('email', $user->email ?? '') }}" required>
                                    @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="mobile">Mobile Number</label>
                                    <input type="text" class="form-control @error('mobile') is-invalid @enderror" id="mobile" name="mobile" value="{{ old('mobile', $user->mobile ?? '') }}" required>
                                    @error('mobile')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="location_id">Location</label>
                                    <select class="form-control location-select @error('location_id') is-invalid @enderror" id="location_id" name="location_id[]" multiple required data-placeholder="Select locations">
                                        @foreach($locations as $location)
                                            <option value="{{ $location->id }}" data-state="{{ $location->state ?? '' }}" data-city-name="{{ $location->name }}" {{ in_array($location->id, old('location_id', $user->location_id ?? [])) ? 'selected' : '' }}>
                                                {{ $location->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('location_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="lead_type">Lead Type</label>
                                    <select class="form-control select2 @error('lead_type') is-invalid @enderror" id="lead_type" name="lead_type[]" multiple required>
                                        @foreach($lead_types as $lead_type)
                                            <option value="{{ $lead_type }}"
                                                @php
                                                    $selectedLeadTypes = old('lead_type', isset($user->lead_type) ? (is_array($user->lead_type) ? $user->lead_type : explode(',', $user->lead_type)) : []);
                                                @endphp
                                                {{ in_array($lead_type, $selectedLeadTypes) ? 'selected' : '' }}>
                                                {{ $lead_type }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('lead_type')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="parent_id">Parent Member</label>
                                    <select class="form-control @error('parent_id') is-invalid @enderror" id="parent_id" name="parent_id">
                                        <option value="">Select Parent Member</option>
                                        @foreach($parentUsers as $parent)
                                            <option value="{{ $parent->id }}" {{ old('parent_id', $user->parent_id) == $parent->id ? 'selected' : '' }}>
                                                {{ $parent->f_name }} {{ $parent->l_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('parent_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="role_id">Roles</label>
                            <select class="form-control select2 @error('role_id') is-invalid @enderror" id="role_id" name="role_id[]" multiple required>
                                @foreach($roles as $role)
                                    <option value="{{ $role->id }}" {{ in_array($role->id, old('role_id', $user->role_id ?? [])) ? 'selected' : '' }}>
                                        {{ $role->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('role_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="password">{{ isset($user->id) ? 'New Password (leave blank to keep current)' : 'Password' }}</label>
                            <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password" {{ isset($user->id) ? '' : 'required' }}>
                            @error('password')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">{{ isset($user->id) ? 'Update User' : 'Create User' }}</button>
                            <a href="{{ route('admin.users.index') }}" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>
</div>
@section('footer-script')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    $(document).ready(function() {
        // Initialize Select2 for both role and location fields
        $('.select2').not('#location_id').select2({
            placeholder: "Please select...",
            allowClear: true
        });

        if (typeof initLocationSelects === 'function') {
            initLocationSelects();
        }

        function toggleCommissionFields(selectedRoles) {
            $('#commissionFieldsContainer').hide();
            $('#commissionMainContainer').hide();
            $('#commissionFirstContainer').hide();
            $('#commissionSecondContainer').hide();
            $('#vendorWalletPassContainer').hide();

            // Check if any of the selected roles are 8 or 10
            if (selectedRoles && selectedRoles.length > 0) {
                if (selectedRoles.includes('10')) {
                    $('#commissionFieldsContainer').show();
                    $('#commissionMainContainer').show();
                    $('#vendorWalletPassContainer').show();
                } else if (selectedRoles.includes('8')) {
                    $('#commissionFieldsContainer').show();
                    $('#commissionMainContainer').show();
                    $('#commissionFirstContainer').show();
                    $('#commissionSecondContainer').show();
                }
            }
        }

        // Get initial selected roles
        const initialRoleIds = $('#role_id').val();
        toggleCommissionFields(initialRoleIds);

        // Handle role selection change
        $('#role_id').on('change', function() {
            const selectedRoles = $(this).val();
            toggleCommissionFields(selectedRoles);
        });
    });
</script>
@endsection
@endsection
