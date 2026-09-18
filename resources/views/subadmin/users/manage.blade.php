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
                <a href="{{ route('subadmin.users.index') }}" class="btn btn-secondary">Back to Users</a>
            </div>
        </div>
    </section>
    <section class="content">
        <div class="container-fluid">
            <div class="card">
                <div class="card-body">
                    <form
                        action="{{ isset($user->id) ? route('subadmin.users.manage_process', $user->id) : route('subadmin.users.manage_process') }}"
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
                                    <select class="form-control location-select @error('location_id') is-invalid @enderror" id="location_id" name="location_id[]" multiple required data-placeholder="Select locations">
                                        <option value="all" id="select-all-location">Select All</option>
                                        @foreach($locations as $location)
                                            <option value="{{ $location->id }}" data-state="{{ $location->state ?? '' }}" data-city-name="{{ $location->name }}" {{ in_array($location->id, old('location_id', isset($user) ? ($user->location_id ?? []) : [])) ? 'selected' : '' }}>
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

                        <!-- Sub Admin Permissions -->
                        <div id="subAdminPermissionsContainer" style="display: none; background: #fff; border-radius: 12px; padding: 20px; margin-bottom: 25px; border: 1px solid #e0e0e0; box-shadow: 0 4px 15px rgba(0,0,0,0.05);">
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <label style="color: #ea8a2b; font-weight: 700; font-size: 18px; margin: 0;">
                                    <i class="fas fa-user-shield"></i> Sub Admin Permissions Matrix
                                </label>
                                <button type="button" class="btn btn-sm btn-outline-secondary" id="selectAllPermissionsBtn">
                                    <i class="fas fa-check-double"></i> Select All
                                </button>
                            </div>
                            
                            <div class="table-responsive">
                                <table class="table table-hover table-bordered text-center align-middle" style="border-collapse: separate; border-spacing: 0; border-radius: 8px; overflow: hidden;">
                                    <thead style="background-color: #f8f9fa;">
                                        <tr>
                                            <th class="text-start" style="width: 25%; color: #495057; font-weight: 600;">Module</th>
                                            <th style="width: 15%; color: #495057; font-weight: 600;">Access (Menu)</th>
                                            <th style="width: 15%; color: #495057; font-weight: 600;">View Details (Eye)</th>
                                            <th style="width: 15%; color: #495057; font-weight: 600;">Create (Add)</th>
                                            <th style="width: 15%; color: #495057; font-weight: 600;">Edit (Pencil)</th>
                                            <th style="width: 15%; color: #495057; font-weight: 600;">Delete (Trash)</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php
                                            $modules = [
                                                'Dashboard' => ['access' => 'view_dashboard'],
                                                'Payments' => [
                                                    'access' => 'view_payments',
                                                    'create' => 'create_payment'
                                                ],
                                                'Leads' => [
                                                    'access' => 'view_leads',
                                                    'view_details' => 'view_lead_details',
                                                    'create' => 'create_lead',
                                                    'edit' => 'edit_lead',
                                                    'delete' => 'delete_lead'
                                                ],
                                                'Sales & Operation Referral Leads' => ['access' => 'view_referral_leads'],
                                                'Users' => [
                                                    'access' => 'view_user',
                                                    'create' => 'create_user',
                                                    'edit' => 'edit_user',
                                                    'delete' => 'delete_user'
                                                ],
                                                'B2B Users' => [
                                                    'access' => 'view_b2b_users',
                                                    'create' => 'create_b2b_users',
                                                    'edit' => 'edit_b2b_users',
                                                    'delete' => 'delete_b2b_users'
                                                ],
                                                'B2B Corporate' => [
                                                    'access' => 'view_b2b_corporate',
                                                    'create' => 'create_b2b_corporate',
                                                    'edit' => 'edit_b2b_corporate',
                                                    'delete' => 'delete_b2b_corporate'
                                                ],
                                                'Individual' => [
                                                    'access' => 'view_b2b_individual',
                                                    'create' => 'create_b2b_individual',
                                                    'edit' => 'edit_b2b_individual',
                                                    'delete' => 'delete_b2b_individual'
                                                ],
                                                'Insurers' => [
                                                    'access' => 'view_insurers',
                                                    'create' => 'create_insurer',
                                                    'edit' => 'edit_insurer',
                                                    'delete' => 'delete_insurer'
                                                ],
                                                'Brokers' => [
                                                    'access' => 'view_brokers',
                                                    'create' => 'create_broker',
                                                    'edit' => 'edit_broker',
                                                    'delete' => 'delete_broker'
                                                ],
                                                'Break Logs' => ['access' => 'view_break_logs'],
                                                'Duty Logs' => ['access' => 'view_duty_logs'],
                                                'Operation Leads' => ['access' => 'view_operation_leads'],
                                                'Locations' => ['access' => 'view_locations'],
                                                'Services' => ['access' => 'view_services'],
                                                'Registration Languages' => ['access' => 'view_languages'],
                                                'Agreements' => ['access' => 'view_agreements'],
                                                'Doctor Requests' => ['access' => 'view_doctor_requests'],
                                                'Chats' => ['access' => 'view_chats'],
                                                'Whatsapp' => ['access' => 'view_whatsapp'],
                                                'Job Request' => ['access' => 'view_vendors'],
                                                'Bulk Registration' => ['access' => 'view_bulk_registration'],
                                                'Technical Support' => ['access' => 'view_technical_support'],
                                            ];

                                            $hasPerm = function($permName) use ($user) {
                                                return isset($user) && $user->hasDirectPermission($permName) ? 'checked' : '';
                                            };
                                        @endphp
                                        
                                        @foreach($modules as $moduleName => $perms)
                                            <tr>
                                                <td class="text-start fw-bold" style="color: #333;">{{ $moduleName }}</td>
                                                
                                                <!-- Access (Menu) -->
                                                <td>
                                                    @if(isset($perms['access']))
                                                        <div class="form-check custom-control custom-checkbox d-inline-block">
                                                            <input class="form-check-input custom-control-input perm-checkbox perm-{{ Str::slug($moduleName) }}" type="checkbox" name="permissions[]" value="{{ $perms['access'] }}" id="perm_{{ $perms['access'] }}" {{ $hasPerm($perms['access']) }}>
                                                            <label class="form-check-label custom-control-label" for="perm_{{ $perms['access'] }}" style="cursor:pointer;"></label>
                                                        </div>
                                                    @else
                                                        <span class="text-muted">-</span>
                                                    @endif
                                                </td>

                                                <!-- View Details (Eye) -->
                                                <td>
                                                    @if(isset($perms['view_details']))
                                                        <div class="form-check custom-control custom-checkbox d-inline-block">
                                                            <input class="form-check-input custom-control-input perm-checkbox perm-{{ Str::slug($moduleName) }}" type="checkbox" name="permissions[]" value="{{ $perms['view_details'] }}" id="perm_{{ $perms['view_details'] }}" {{ $hasPerm($perms['view_details']) }}>
                                                            <label class="form-check-label custom-control-label" for="perm_{{ $perms['view_details'] }}" style="cursor:pointer;"></label>
                                                        </div>
                                                    @else
                                                        <span class="text-muted">-</span>
                                                    @endif
                                                </td>
                                                
                                                <!-- Create -->
                                                <td>
                                                    @if(isset($perms['create']))
                                                        <div class="form-check custom-control custom-checkbox d-inline-block">
                                                            <input class="form-check-input custom-control-input perm-checkbox perm-{{ Str::slug($moduleName) }}" type="checkbox" name="permissions[]" value="{{ $perms['create'] }}" id="perm_{{ $perms['create'] }}" {{ $hasPerm($perms['create']) }}>
                                                            <label class="form-check-label custom-control-label" for="perm_{{ $perms['create'] }}" style="cursor:pointer;"></label>
                                                        </div>
                                                    @else
                                                        <span class="text-muted">-</span>
                                                    @endif
                                                </td>
                                                
                                                <!-- Edit -->
                                                <td>
                                                    @if(isset($perms['edit']))
                                                        <div class="form-check custom-control custom-checkbox d-inline-block">
                                                            <input class="form-check-input custom-control-input perm-checkbox perm-{{ Str::slug($moduleName) }}" type="checkbox" name="permissions[]" value="{{ $perms['edit'] }}" id="perm_{{ $perms['edit'] }}" {{ $hasPerm($perms['edit']) }}>
                                                            <label class="form-check-label custom-control-label" for="perm_{{ $perms['edit'] }}" style="cursor:pointer;"></label>
                                                        </div>
                                                    @else
                                                        <span class="text-muted">-</span>
                                                    @endif
                                                </td>
                                                
                                                <!-- Delete -->
                                                <td>
                                                    @if(isset($perms['delete']))
                                                        <div class="form-check custom-control custom-checkbox d-inline-block">
                                                            <input class="form-check-input custom-control-input perm-checkbox perm-{{ Str::slug($moduleName) }}" type="checkbox" name="permissions[]" value="{{ $perms['delete'] }}" id="perm_{{ $perms['delete'] }}" {{ $hasPerm($perms['delete']) }}>
                                                            <label class="form-check-label custom-control-label" for="perm_{{ $perms['delete'] }}" style="cursor:pointer;"></label>
                                                        </div>
                                                    @else
                                                        <span class="text-muted">-</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
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
                            <a href="{{ route('subadmin.users.index') }}" class="btn btn-secondary">Cancel</a>
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

        function toggleSubAdminPermissions() {
            const selectedRoleTexts = [];
            $('#role_id option:selected').each(function() {
                selectedRoleTexts.push($(this).text().trim().toLowerCase());
            });
            
            if (selectedRoleTexts.includes('sub admin')) {
                $('#subAdminPermissionsContainer').show();
            } else {
                $('#subAdminPermissionsContainer').hide();
            }
        }

        // Permissions Matrix - Global Select All
        $('#selectAllPermissionsBtn').on('click', function() {
            const allCheckboxes = $('#subAdminPermissionsContainer input[type="checkbox"]');
            const anyUnchecked = allCheckboxes.not(':checked').length > 0;
            allCheckboxes.prop('checked', anyUnchecked);
        });

        // Get initial selected roles
        const initialRoleIds = $('#role_id').val();
        toggleCommissionFields(initialRoleIds);
        toggleServicesField();
        toggleSubAdminPermissions();

        // Handle role selection change
        $('#role_id').on('change', function() {
            const selectedRoles = $(this).val();
            toggleCommissionFields(selectedRoles);
            toggleServicesField();
            toggleSubAdminPermissions();
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
