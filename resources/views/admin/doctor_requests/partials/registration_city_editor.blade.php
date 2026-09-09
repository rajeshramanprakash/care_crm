@php
    /** @var \App\Models\DoctorRequest $doctor */
    $currentCity = trim((string) ($doctor->city ?? ''));
    if ($currentCity === '') {
        $currentCity = trim((string) ($doctor->location ?? ''));
    }
    $locationOptions = isset($adminLocations)
        ? $adminLocations
        : \App\Models\Location::query()->orderBy('name')->get(['id', 'name', 'state']);
@endphp
<div class="dr-reg-city-wrap border rounded p-3 mt-3 bg-light"
     data-dr-id="{{ (int) $doctor->id }}"
     data-url="{{ route('admin.doctor_requests.registration_profile_update', $doctor) }}">
    <h6 class="font-weight-bold mb-1">Edit city</h6>
    <p class="small text-muted mb-2">Shown on the CareWeb consultation page (doctor card and city filters). Must match a city from Admin → Locations.</p>
    <div class="form-row align-items-end">
        <div class="form-group col-md-8 mb-2 mb-md-0">
            <label class="small font-weight-bold d-block" for="dr-reg-city-{{ $doctor->id }}">City</label>
            <select id="dr-reg-city-{{ $doctor->id }}" class="form-control form-control-sm dr-reg-city location-select" data-placeholder="Select city">
                <option value="" @selected($currentCity === '')>— Select city —</option>
                @foreach($locationOptions as $loc)
                    @php $locName = trim((string) ($loc->name ?? '')); @endphp
                    @if($locName !== '')
                        <option value="{{ e($locName) }}" data-state="{{ e($loc->state ?? '') }}" data-city-name="{{ e($locName) }}" @selected(strcasecmp($currentCity, $locName) === 0)>{{ e($locName) }}</option>
                    @endif
                @endforeach
            </select>
        </div>
        <div class="form-group col-md-4 mb-0">
            <button type="button" class="btn btn-primary btn-sm btn-block dr-reg-city-save">Save city</button>
        </div>
    </div>
    <span class="small dr-reg-city-msg d-block mt-2"></span>
</div>
