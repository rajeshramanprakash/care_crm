@php
    /** @var \App\Models\DoctorRequest $doctor */
    $eduList = is_array($doctor->education_history ?? null) && count($doctor->education_history) > 0
        ? $doctor->education_history
        : [['degree' => '', 'institution' => '', 'year_completed' => '']];
    $expList = is_array($doctor->experience_history ?? null) && count($doctor->experience_history) > 0
        ? $doctor->experience_history
        : [['title' => '', 'organization' => '', 'from_year' => '', 'to_year' => '', 'details' => '']];
    $svcRow = \App\Models\DoctorConsultationService::query()
        ->where('name', $doctor->job_title)
        ->where('is_active', true)
        ->first();
    $allowedTags = [];
    if ($svcRow && is_array($svcRow->specialization_options)) {
        foreach ($svcRow->specialization_options as $tag) {
            $t = trim((string) $tag);
            if ($t !== '') {
                $allowedTags[] = $t;
            }
        }
    }
    $doctorTags = [];
    if (is_array($doctor->specializations ?? null)) {
        foreach ($doctor->specializations as $tag) {
            $t = trim((string) $tag);
            if ($t !== '') {
                $doctorTags[] = $t;
            }
        }
    }
@endphp
<div class="dr-reg-profile-wrap border rounded p-3 mb-3 bg-light"
     data-dr-id="{{ (int) $doctor->id }}"
     data-url="{{ route('admin.doctor_requests.registration_profile_update', $doctor) }}">
    <h6 class="font-weight-bold mb-2">Edit profile (CareWeb doctor modal)</h6>
    <p class="small text-muted mb-3">Changes save to this registration and appear on the public consultation page (doctor profile modal) after refresh.</p>

    <div class="form-group mb-2">
        <label class="small font-weight-bold d-block">Doctor tags</label>
        <p class="small text-muted mb-2">Edit service and tags in the <strong>Consultation service</strong> section above.</p>
    </div>

    <div class="mb-2">
        <div class="d-flex justify-content-between align-items-center mb-1">
            <label class="small font-weight-bold mb-0">Education <span class="text-muted font-weight-normal">(newest first is recommended)</span></label>
            <button type="button" class="btn btn-xs btn-outline-secondary btn-sm dr-reg-edu-add"><i class="fas fa-plus"></i> Row</button>
        </div>
        <div class="table-responsive">
            <table class="table table-bordered table-sm mb-0">
                <thead class="thead-light">
                    <tr>
                        <th>Degree</th>
                        <th>Institution</th>
                        <th style="width:110px;">Year</th>
                        <th style="width:44px;"></th>
                    </tr>
                </thead>
                <tbody class="dr-reg-edu-body">
                    @foreach($eduList as $row)
                        <tr class="dr-reg-edu-row">
                            <td><input type="text" class="form-control form-control-sm dr-reg-edu-degree" value="{{ e($row['degree'] ?? '') }}" maxlength="500"></td>
                            <td><input type="text" class="form-control form-control-sm dr-reg-edu-inst" value="{{ e($row['institution'] ?? '') }}" maxlength="500"></td>
                            <td><input type="text" class="form-control form-control-sm dr-reg-edu-year" value="{{ e($row['year_completed'] ?? '') }}" maxlength="32"></td>
                            <td class="text-center align-middle">
                                <button type="button" class="btn btn-link btn-sm text-danger p-0 dr-reg-edu-rm" title="Remove">&times;</button>
                            </td>
                        </tr>
                    @endforeach
                    <tr class="dr-reg-edu-row dr-reg-edu-template d-none" aria-hidden="true">
                        <td><input type="text" class="form-control form-control-sm dr-reg-edu-degree" maxlength="500"></td>
                        <td><input type="text" class="form-control form-control-sm dr-reg-edu-inst" maxlength="500"></td>
                        <td><input type="text" class="form-control form-control-sm dr-reg-edu-year" maxlength="32"></td>
                        <td class="text-center align-middle">
                            <button type="button" class="btn btn-link btn-sm text-danger p-0 dr-reg-edu-rm" title="Remove">&times;</button>
                            </td>
                        </tr>
                </tbody>
            </table>
        </div>
        <span class="small text-muted">Leave all rows blank to clear education on the website card (modal will fall back to qualification text).</span>
    </div>

    <div class="mb-3">
        <div class="d-flex justify-content-between align-items-center mb-1">
            <label class="small font-weight-bold mb-0">Experience</label>
            <button type="button" class="btn btn-xs btn-outline-secondary btn-sm dr-reg-exp-add"><i class="fas fa-plus"></i> Row</button>
        </div>
        <div class="table-responsive">
            <table class="table table-bordered table-sm mb-0">
                <thead class="thead-light">
                    <tr>
                        <th>Title</th>
                        <th>Organization</th>
                        <th style="width:90px;">From</th>
                        <th style="width:90px;">To</th>
                        <th>Details</th>
                        <th style="width:44px;"></th>
                    </tr>
                </thead>
                <tbody class="dr-reg-exp-body">
                    @foreach($expList as $row)
                        <tr class="dr-reg-exp-row">
                            <td><input type="text" class="form-control form-control-sm dr-reg-exp-title" value="{{ e($row['title'] ?? '') }}" maxlength="500"></td>
                            <td><input type="text" class="form-control form-control-sm dr-reg-exp-org" value="{{ e($row['organization'] ?? '') }}" maxlength="500"></td>
                            <td><input type="text" class="form-control form-control-sm dr-reg-exp-from" value="{{ e($row['from_year'] ?? '') }}" maxlength="32"></td>
                            <td><input type="text" class="form-control form-control-sm dr-reg-exp-to" value="{{ e($row['to_year'] ?? '') }}" maxlength="32" placeholder="Present"></td>
                            <td><input type="text" class="form-control form-control-sm dr-reg-exp-details" value="{{ e($row['details'] ?? '') }}" maxlength="2000"></td>
                            <td class="text-center align-middle">
                                <button type="button" class="btn btn-link btn-sm text-danger p-0 dr-reg-exp-rm" title="Remove">&times;</button>
                            </td>
                        </tr>
                    @endforeach
                    <tr class="dr-reg-exp-row dr-reg-exp-template d-none" aria-hidden="true">
                        <td><input type="text" class="form-control form-control-sm dr-reg-exp-title" maxlength="500"></td>
                        <td><input type="text" class="form-control form-control-sm dr-reg-exp-org" maxlength="500"></td>
                        <td><input type="text" class="form-control form-control-sm dr-reg-exp-from" maxlength="32"></td>
                        <td><input type="text" class="form-control form-control-sm dr-reg-exp-to" maxlength="32" placeholder="Present"></td>
                        <td><input type="text" class="form-control form-control-sm dr-reg-exp-details" maxlength="2000"></td>
                        <td class="text-center align-middle">
                            <button type="button" class="btn btn-link btn-sm text-danger p-0 dr-reg-exp-rm" title="Remove">&times;</button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <span class="small text-muted">Each saved row needs title, organization and from year. Leave all blank to clear structured experience.</span>
    </div>

    <button type="button" class="btn btn-primary btn-sm dr-reg-profile-save">Save profile</button>
    <span class="ml-2 small dr-reg-profile-msg"></span>
</div>
