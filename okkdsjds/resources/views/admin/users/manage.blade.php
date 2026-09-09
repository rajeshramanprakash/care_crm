@extends('admin.layouts.app')

@section('title', isset($user->id) ? 'Edit User' : 'Add User')

@section('header-css')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.1/css/jquery.dataTables.min.css">
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
@endsection

@section('main')
<div class="content-wrapper pb-5" style="height: 55vh; overflow-y: auto;">
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
                                        id="f_name" name="f_name" value="{{ old('f_name', isset($user) ? ($user->f_name ?? '') : '') }}" required>
                                    @error('f_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="l_name">Last Name</label>
                                    <input type="text" class="form-control @error('l_name') is-invalid @enderror"
                                        id="l_name" name="l_name" value="{{ old('l_name', isset($user) ? ($user->l_name ?? '') : '') }}" required>
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
                                        name="email" value="{{ old('email', isset($user) ? ($user->email ?? '') : '') }}" required>
                                    @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="mobile">Mobile Number</label>
                                    <input type="text" class="form-control @error('mobile') is-invalid @enderror" id="mobile" name="mobile" value="{{ old('mobile', isset($user) ? ($user->mobile ?? '') : '') }}" required>
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
                                    <select class="form-control select2 @error('location_id') is-invalid @enderror" id="location_id" name="location_id[]" multiple required>
                                        <option value="all" id="select-all-location">Select All</option>
                                        @foreach($locations as $location)
                                            <option value="{{ $location->id }}" {{ in_array($location->id, old('location_id', isset($user) ? ($user->location_id ?? []) : [])) ? 'selected' : '' }}>
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
                                                    $selectedLeadTypes = old('lead_type', isset($user) && isset($user->lead_type) ? (is_array($user->lead_type) ? $user->lead_type : explode(',', $user->lead_type)) : []);
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
                                            <option value="{{ $parent->id }}" {{ old('parent_id', isset($user) ? $user->parent_id : '') == $parent->id ? 'selected' : '' }}>
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
                                    <option value="{{ $role->id }}" {{ in_array($role->id, old('role_id', isset($user) ? ($user->role_id ?? []) : [])) ? 'selected' : '' }}>
                                        {{ $role->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('role_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
<!-- Services Field (Only for Operation Role) -->
                        <div id="servicesFieldContainer" style="display: none;">
                            <div class="form-group">
                                <label for="services" style="color: #ea8a2b; font-weight: 700; font-size: 15px; margin-bottom: 8px;">
                                    <i class="fas fa-filter"></i> Services Filter
                                </label>
                                <small class="text-muted d-block mb-3" style="font-style: italic;">
                                    <i class="fas fa-info-circle"></i> Only selected services will be visible to this user. If no service is selected, all services will be visible.
                                </small>
                                <select class="form-control select2 @error('services') is-invalid @enderror" id="services" name="services[]" multiple style="width: 100%;">
                                    <option value="all" id="select-all-services">Select All</option>
                                    @foreach($services as $service)
                                        <option value="{{ $service->name }}"
                                            @php
                                                $selectedServices = old('services', isset($user) && isset($user->services) ? (is_array($user->services) ? $user->services : explode(',', $user->services)) : []);
                                            @endphp
                                            {{ in_array($service->name, $selectedServices) ? 'selected' : '' }}>
                                            {{ $service->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('services')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
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
        $('.select2').select2({
            placeholder: "Please select...",
            allowClear: true
        });

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

        function toggleServicesField() {
            const selectedRoles = $('#role_id').val();
            const selectedRoleTexts = [];
            
            // Get selected role names
            $('#role_id option:selected').each(function() {
                selectedRoleTexts.push($(this).text().trim());
            });
            
            // Check if "Operation" role is selected
            const hasOperationRole = selectedRoleTexts.some(role => role.toLowerCase().includes('operation'));
            
            if (hasOperationRole) {
                $('#servicesFieldContainer').show();
            } else {
                $('#servicesFieldContainer').hide();
                // Clear services selection if Operation role is not selected
                $('#services').val(null).trigger('change');
            }
        }

        // Get initial selected roles
        const initialRoleIds = $('#role_id').val();
        toggleCommissionFields(initialRoleIds);
        toggleServicesField();

        // Handle role selection change
        $('#role_id').on('change', function() {
            const selectedRoles = $(this).val();
            toggleCommissionFields(selectedRoles);
            toggleServicesField();
        });

        // Handle Select All for locations
        $('#location_id').on('change', function() {
            const selectedValues = $(this).val();

            if (selectedValues && selectedValues.includes('all')) {
                // If "Select All" is selected, select all location options except "Select All"
                const allLocationIds = [];
                $(this).find('option').each(function() {
                    if ($(this).val() !== 'all' && $(this).val() !== '') {
                        allLocationIds.push($(this).val());
                    }
                });

                // Set all location IDs (excluding 'all') and update Select2
                $(this).val(allLocationIds);
                $(this).trigger('change');
            }
        });

        // Handle Select All for services
        $('#services').on('change', function() {
            const selectedValues = $(this).val();

            if (selectedValues && selectedValues.includes('all')) {
                // If "Select All" is selected, select all service options except "Select All"
                const allServices = [];
                $(this).find('option').each(function() {
                    if ($(this).val() !== 'all' && $(this).val() !== '') {
                        allServices.push($(this).val());
                    }
                });

                // Set all services (excluding 'all') and update Select2
                $(this).val(allServices);
                $(this).trigger('change');
            }
        });
    });
</script>
@endsection
@endsection
