@php
    /** @var \App\Models\DoctorRequest $doctor */
    $currentEmail = trim((string) ($doctor->email ?? ''));
@endphp
<div class="dr-kv-item dr-kv-item--full">
    <dt>Email</dt>
    <dd>
        <div class="d-flex flex-wrap align-items-center" style="gap: 8px;">
            <input
                type="email"
                id="doctor_portal_email_input"
                class="form-control form-control-sm"
                value="{{ e($currentEmail) }}"
                placeholder="yourname@gmail.com"
                autocomplete="email"
                maxlength="255"
                style="max-width: 320px; flex: 1; min-width: 200px;"
            >
            <button type="button" class="btn btn-primary btn-sm" onclick="saveDoctorPortalField('email', 'doctor_portal_email_input')">
                <i class="fas fa-save"></i> Save
            </button>
        </div>
        <small class="text-muted d-block mt-1">You can update your email anytime from here.</small>
    </dd>
</div>
