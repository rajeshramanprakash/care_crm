@php
    /** @var \App\Models\DoctorRequest $doctor */
    $eduSummary = '';
    $edu = $doctor->education_history ?? [];
    if (is_array($edu) && isset($edu[0])) {
        $deg = trim((string) ($edu[0]['degree'] ?? ''));
        $inst = trim((string) ($edu[0]['institution'] ?? ''));
        $eduSummary = $deg && $inst ? $deg.' @ '.$inst : ($deg ?: $inst);
    }
    $expSummary = '';
    $exp = $doctor->experience_history ?? [];
    if (is_array($exp) && isset($exp[0])) {
        $title = trim((string) ($exp[0]['title'] ?? ''));
        $org = trim((string) ($exp[0]['organization'] ?? ''));
        $expSummary = $title && $org ? $title.' @ '.$org : ($title ?: $org);
    }
@endphp
<div class="dr-reg-about-wrap border rounded p-3 mt-3 bg-white"
     data-dr-id="{{ (int) $doctor->id }}"
     data-save-url="{{ route('subadmin.doctor_requests.registration_profile_update', $doctor) }}"
     data-generate-url="{{ route('subadmin.doctor_requests.generate_about', $doctor) }}"
     data-doctor-name="{{ e($doctor->name ?? $doctor->customer_name ?? '') }}"
     data-job-title="{{ e($doctor->job_title ?? '') }}"
     data-edu-summary="{{ e($eduSummary) }}"
     data-exp-summary="{{ e($expSummary) }}">
    <h6 class="font-weight-bold mb-1">About you (admin)</h6>
    <p class="small text-muted mb-2">Edit the doctor’s profile “About” on CareWeb. <strong>Generate with AI</strong> reads this doctor’s full registration — Basic details, Consultation service, Education, Experience, and Languages — then drafts the About text. You can add optional notes below; review before saving.</p>

    <div class="form-group mb-2">
        <label class="small font-weight-bold d-block" for="dr-reg-about-text-{{ $doctor->id }}">About text</label>
        <textarea id="dr-reg-about-text-{{ $doctor->id }}" class="form-control form-control-sm dr-reg-about-text" rows="6" maxlength="8000" placeholder="Optional: extra points for AI (tone, focus areas). Generate with AI also uses all registration sections on this page.">{{ e($doctor->about_text ?? '') }}</textarea>
    </div>

    <div class="d-flex flex-wrap align-items-center mb-2" style="gap: 8px;">
        <button type="button" class="btn btn-outline-secondary btn-sm dr-reg-about-generate">
            <i class="fas fa-magic mr-1"></i> Generate with AI
        </button>
        <button type="button" class="btn btn-primary btn-sm dr-reg-about-save">
            <i class="fas fa-save mr-1"></i> Save About
        </button>
        <span class="small dr-reg-about-msg"></span>
    </div>
    <p class="small text-muted mb-0">Powered by Google Gemini. Uses Basic details, Consultation service, Education, Experience, Languages from this registration, plus optional notes above. Does not invent facts beyond that data.</p>
</div>
