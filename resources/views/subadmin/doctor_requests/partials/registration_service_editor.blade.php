@php
    /** @var \App\Models\DoctorRequest $doctor */
    use App\Models\DoctorConsultationService;
    use App\Models\Location;

    $allServices = DoctorConsultationService::query()
        ->where('is_active', true)
        ->with(['subServices' => fn ($q) => $q->where('is_active', true)->orderBy('sort_order')->orderBy('name')])
        ->orderBy('sort_order')
        ->orderBy('name')
        ->get();

    $currentJobTitle = trim((string) ($doctor->job_title ?? ''));
    $existingSubServices = is_array($doctor->consultation_sub_services ?? null) ? $doctor->consultation_sub_services : [];
    $existingSpecs = is_array($doctor->specializations ?? null) ? $doctor->specializations : [];
    $consultationModes = is_array($doctor->consultation_modes ?? null) ? array_values($doctor->consultation_modes) : [];
    $consultationPricingExisting = is_array($doctor->consultation_pricing ?? null) ? $doctor->consultation_pricing : [];
    $doctorCity = trim((string) ($doctor->city ?? $doctor->location ?? ''));
    $doctorLocationRow = $doctorCity !== '' ? Location::query()->where('name', $doctorCity)->first() : null;
    $doctorLocationId = $doctorLocationRow ? (int) $doctorLocationRow->id : 0;

    $catalog = $allServices->map(function (DoctorConsultationService $svc) {
        $parentTags = is_array($svc->specialization_options)
            ? array_values(array_filter(array_map('strval', $svc->specialization_options)))
            : [];

        return [
            'id' => (int) $svc->id,
            'name' => (string) $svc->name,
            'tags' => $parentTags,
            'sub_services' => $svc->subServices->map(fn ($sub) => [
                'id' => (int) $sub->id,
                'name' => (string) $sub->name,
                'tags' => is_array($sub->specialization_options)
                    ? array_values(array_filter(array_map('strval', $sub->specialization_options)))
                    : [],
            ])->values()->all(),
        ];
    })->values()->all();
@endphp
<div class="dr-reg-service-wrap border rounded p-3 mt-3 bg-light"
     data-dr-id="{{ (int) $doctor->id }}"
     data-url="{{ route('subadmin.doctor_requests.registration_profile_update', $doctor) }}"
     data-catalog='@json($catalog)'
     data-job-title="{{ e($currentJobTitle) }}"
     data-existing-subs='@json($existingSubServices)'
     data-existing-tags='@json(array_values($existingSpecs))'
     data-consultation-modes='@json($consultationModes)'
     data-consultation-pricing='@json($consultationPricingExisting)'
     data-location-id="{{ $doctorLocationId }}"
     data-location-name="{{ e($doctorCity) }}">
    <h6 class="font-weight-bold mb-1">Edit consultation service &amp; tags</h6>
    <p class="small text-muted mb-3">Change the doctor’s consultation service, sub-services, tags, and charges (under each sub-service). CareWeb filters and doctor cards use these values.</p>

    <div class="form-group mb-3">
        <label class="small font-weight-bold d-block" for="dr-reg-service-select-{{ $doctor->id }}">Consultation service</label>
        <select id="dr-reg-service-select-{{ $doctor->id }}" class="form-control form-control-sm dr-reg-service-select">
            <option value="">— Select service —</option>
            @foreach($allServices as $svcOpt)
                <option value="{{ e($svcOpt->name) }}" @selected(strcasecmp($currentJobTitle, (string) $svcOpt->name) === 0)>{{ e($svcOpt->name) }}</option>
            @endforeach
        </select>
    </div>

    <div class="dr-reg-sub-picker-wrap mb-2" style="display:none;">
        <label class="small font-weight-bold d-block" for="dr-reg-sub-add-{{ $doctor->id }}">Add sub-service</label>
        <div class="form-row align-items-end">
            <div class="col-md-8 mb-2 mb-md-0">
                <select id="dr-reg-sub-add-{{ $doctor->id }}" class="form-control form-control-sm dr-reg-sub-add">
                    <option value="">Select sub-service to add</option>
                </select>
            </div>
        </div>
    </div>

    <div class="dr-reg-sub-blocks mb-3"></div>

    <div class="dr-reg-service-tags-wrap mb-3" style="display:none;">
        <label class="small font-weight-bold d-block">Service tags</label>
        <p class="small text-muted mb-2 dr-reg-service-tags-hint">Tap to add or remove tags for this service.</p>
        <div class="dr-reg-tags-grid dr-reg-service-tags-grid" role="group" aria-label="Service tags"></div>
        <div class="dr-reg-service-pricing-slot"></div>
    </div>

    <button type="button" class="btn btn-primary btn-sm dr-reg-service-save">Save service, tags &amp; charges</button>
    <span class="small dr-reg-service-msg d-block mt-2"></span>
</div>
