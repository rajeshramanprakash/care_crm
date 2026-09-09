@php
    /** @var \App\Models\DoctorRequest $doctor */
    $currentModes = is_array($doctor->consultation_modes ?? null) ? array_values($doctor->consultation_modes) : [];
    $modeOptions = [
        'online' => ['label' => 'Online', 'icon' => 'fa-video'],
        'home_visit' => ['label' => 'Home visit', 'icon' => 'fa-home'],
        'clinic_visit' => ['label' => 'Clinic visit', 'icon' => 'fa-clinic-medical'],
    ];
@endphp
<div class="dr-reg-modes-wrap border rounded p-3 mb-3 bg-light"
     data-dr-id="{{ (int) $doctor->id }}"
     data-url="{{ route('admin.doctor_requests.registration_profile_update', $doctor) }}"
     data-modes='@json($currentModes)'>
    <h6 class="font-weight-bold mb-1">Edit consultation modes</h6>
    <p class="small text-muted mb-2">Select which consultation types this doctor offers. At least one mode is required.</p>
    <div class="dr-reg-modes-grid mb-2" role="group" aria-label="Consultation modes">
        @foreach($modeOptions as $modeKey => $meta)
            @php $on = in_array($modeKey, $currentModes, true); @endphp
            <button type="button"
                    class="dr-reg-mode-pill{{ $on ? ' is-selected' : '' }}"
                    data-mode="{{ $modeKey }}"
                    aria-pressed="{{ $on ? 'true' : 'false' }}">
                <i class="fas {{ $meta['icon'] }} mr-1"></i>{{ $meta['label'] }}
            </button>
        @endforeach
    </div>
    <button type="button" class="btn btn-primary btn-sm dr-reg-modes-save">Save consultation modes</button>
    <span class="small dr-reg-modes-msg d-block mt-2"></span>
</div>
