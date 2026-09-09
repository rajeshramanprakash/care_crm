@php
    /** @var \App\Models\DoctorRequest $doctor */
    $fluentLangOptions = config('doctor_registration.fluent_languages', []);
    $doctorLangs = is_array($doctor->fluent_languages ?? null) ? $doctor->fluent_languages : [];
    $doctorGender = strtolower(trim((string) ($doctor->gender ?? '')));
    if ($doctorGender === '' && ! empty($doctor->age) && is_string($doctor->age) && str_contains($doctor->age, '|')) {
        $doctorGender = strtolower(trim((string) (explode('|', $doctor->age)[1] ?? '')));
    }
@endphp
<div class="dr-reg-filters-wrap border rounded p-3 mt-3 bg-white"
     data-dr-id="{{ (int) $doctor->id }}"
     data-url="{{ route('admin.doctor_requests.registration_profile_update', $doctor) }}">
    <h6 class="font-weight-bold mb-1">Gender &amp; fluent languages (admin)</h6>
    <p class="small text-muted mb-3 mb-md-2">Used on the CareWeb consultation page to filter doctor listings. Select languages the doctor is fluent in; tap a selected language again to remove it.</p>

    <div class="form-row">
        <div class="form-group col-md-4 mb-2 mb-md-3">
            <label class="small font-weight-bold d-block" for="dr-reg-gender-{{ $doctor->id }}">Gender</label>
            <select id="dr-reg-gender-{{ $doctor->id }}" class="form-control form-control-sm dr-reg-gender">
                <option value="" @selected($doctorGender === '')>— Not set —</option>
                <option value="male" @selected($doctorGender === 'male')>Male</option>
                <option value="female" @selected($doctorGender === 'female')>Female</option>
                <option value="other" @selected($doctorGender === 'other')>Other</option>
            </select>
        </div>
    </div>

    <div class="form-group mb-2">
        <label class="small font-weight-bold d-block">Fluent languages</label>
        @if(count($fluentLangOptions) === 0)
            <p class="small text-warning mb-0">Language options are not configured in CRM.</p>
        @else
            <div class="dr-reg-lang-grid" role="group" aria-label="Fluent languages">
                @foreach($fluentLangOptions as $code => $label)
                    @php $isOn = in_array($code, $doctorLangs, true); @endphp
                    <button type="button"
                            class="dr-reg-lang-pill{{ $isOn ? ' is-selected' : '' }}"
                            data-lang="{{ e($code) }}"
                            aria-pressed="{{ $isOn ? 'true' : 'false' }}">{{ e($label) }}</button>
                @endforeach
            </div>
            <p class="small text-muted mt-2 mb-0">CareWeb filters: English, Hindi, Telugu, Tamil, Marathi. Save after add or remove.</p>
        @endif
    </div>

    <button type="button" class="btn btn-primary btn-sm dr-reg-filters-save">Save gender &amp; languages</button>
    <span class="ml-2 small dr-reg-filters-msg"></span>
</div>
