{{-- Shared: full registration detail (list modal + full page + doctor portal) --}}
@include('admin.doctor_requests.partials.registration_detail_styles')

@php
    /** @var \App\Models\DoctorRequest $doctor */
    $modes = $doctor->consultation_modes ?? [];
    $modeLabels = ['online' => 'Online', 'home_visit' => 'Home visit', 'clinic_visit' => 'Clinic visit'];
    $modeIcons = ['online' => 'fa-video', 'home_visit' => 'fa-home', 'clinic_visit' => 'fa-clinic-medical'];

    $docUrl = function (?string $path) {
        if (!$path) {
            return null;
        }
        $path = str_replace('\\', '/', trim($path));
        if (preg_match('#^https?://#i', $path)) {
            return $path;
        }
        $path = ltrim($path, '/');
        if (str_starts_with($path, 'storage/')) {
            return '/'.$path;
        }

        return '/storage/'.$path;
    };

    $fmtDate = function ($dt, string $format = 'd M Y, H:i') {
        if ($dt === null || $dt === '') {
            return '—';
        }
        if ($dt instanceof \DateTimeInterface) {
            return $dt->format($format);
        }
        try {
            return \Carbon\Carbon::parse($dt)->format($format);
        } catch (\Throwable $e) {
            return (string) $dt;
        }
    };

    $hideProfilePhotoRow = $hideProfilePhotoRow ?? false;
    $hideDocumentsSection = $hideDocumentsSection ?? false;
    $hideWebsiteConsultationBookingsSection = $hideWebsiteConsultationBookingsSection ?? false;
    $hideCalendarAvailabilitySection = $hideCalendarAvailabilitySection ?? false;
    $hideSetConsultationChargesSection = $hideSetConsultationChargesSection ?? false;
    $useTwoColumnDetailsLayout = $useTwoColumnDetailsLayout ?? false;
    $hideLeadRegisteredStatusHeader = $hideLeadRegisteredStatusHeader ?? false;
    $doctorPortalReadOnly = $doctorPortalReadOnly ?? false;
    $deferPortalPricingSection = $deferPortalPricingSection ?? false;

    $docDownloadName = function (?string $path, string $fallback): string {
        if (!$path) {
            return $fallback;
        }
        $base = basename(str_replace('\\', '/', $path));

        return ($base !== '' && $base !== '.') ? $base : $fallback;
    };
    $isDisplayableImage = function (?string $path): bool {
        if (!$path) {
            return false;
        }
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        return in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg'], true);
    };
    $isPdf = function (?string $path): bool {
        if (!$path) {
            return false;
        }

        return strtolower(pathinfo($path, PATHINFO_EXTENSION)) === 'pdf';
    };

    $statusClass = match ($doctor->approval_status) {
        'approved' => 'dr-status-pill--approved',
        'rejected' => 'dr-status-pill--rejected',
        default => 'dr-status-pill--pending',
    };
    $statusLabel = match ($doctor->approval_status) {
        'approved' => 'Approved',
        'rejected' => 'Rejected',
        default => 'Pending',
    };

    $profileUploadPath = $doctor->profile_image_upload ?: null;
    $profileApprovedPath = ($doctor->profile_image_status ?? '') === 'approved' ? $doctor->profile_image : null;
    $profilePendingPath = $doctor->profile_image_pending ?: null;
    $uploadUrl = $docUrl($profileUploadPath);
    $approvedUrl = $docUrl($profileApprovedPath);
    $pendingUrl = $docUrl($profilePendingPath);
    $profileUrl = $approvedUrl ?: $uploadUrl;
    $svcRow = \App\Models\DoctorConsultationService::where('name', $doctor->job_title)->first();
    $langs = $doctor->fluent_languages ?? [];
    $specs = $doctor->specializations ?? [];
    $consultationSubServices = is_array($doctor->consultation_sub_services ?? null) ? $doctor->consultation_sub_services : [];
    $consultationPricing = is_array($doctor->consultation_pricing ?? null) ? $doctor->consultation_pricing : [];
    $edu = $doctor->education_history ?? [];
    $exp = $doctor->experience_history ?? [];

    $docPreviewBlock = function (?string $path, string $downloadFallback) use ($docUrl, $docDownloadName, $isDisplayableImage, $isPdf) {
        if (!$path) {
            echo '<p class="dr-empty mb-0">Not uploaded</p>';

            return;
        }
        $url = $docUrl($path);
        $dl = $docDownloadName($path, $downloadFallback);
        if (!$url) {
            echo '<p class="dr-empty mb-0">—</p>';

            return;
        }
        echo '<div class="dr-doc-preview mb-2">';
        if ($isDisplayableImage($path)) {
            echo '<img src="'.e($url).'" alt="" class="dr-doc-img">';
        } elseif ($isPdf($path)) {
            echo '<iframe src="'.e($url).'" title="Document preview"></iframe>';
        } else {
            echo '<p class="small text-muted mb-0">Preview not available. Download to open.</p>';
        }
        echo '</div>';
        echo '<a href="'.e($url).'" download="'.e($dl).'" class="btn btn-sm btn-outline-secondary"><i class="fas fa-download"></i> Download</a>';
    };
@endphp

<div class="dr-detail">
    <div class="card card-outline card-primary dr-detail-card mb-0">
        @unless($hideLeadRegisteredStatusHeader)
            <div class="dr-detail-hero">
                <div class="dr-detail-hero-inner">
                    <div class="dr-detail-avatar-wrap">
                        @if($profileUrl && !$hideProfilePhotoRow)
                            <img src="{{ $profileUrl }}" alt="" class="dr-detail-avatar">
                        @else
                            <div class="dr-detail-avatar dr-detail-avatar--placeholder">
                                <i class="fas fa-user-md"></i>
                            </div>
                        @endif
                    </div>
                    <div class="dr-detail-hero-main">
                        <h2 class="dr-detail-hero-title">{{ $doctor->name ?? 'Doctor' }}</h2>
                        <p class="dr-detail-hero-sub mb-2">{{ $doctor->job_title ?? 'Consultation service not set' }}</p>
                        <div class="dr-detail-hero-badges">
                            <span class="dr-status-pill {{ $statusClass }}">{{ $statusLabel }}</span>
                            @if($doctor->lead_id)
                                <span class="dr-meta-chip"><i class="fas fa-hashtag"></i> {{ $doctor->lead_id }}</span>
                            @endif
                            <span class="dr-meta-chip"><i class="fas fa-phone"></i> {{ $doctor->mobile ?? $doctor->contact_no ?? '—' }}</span>
                            <span class="dr-meta-chip"><i class="far fa-clock"></i> {{ $fmtDate($doctor->created_at) }}</span>
                        </div>
                    </div>
                </div>
            </div>
        @endunless

        <div class="card-body dr-detail-body {{ $useTwoColumnDetailsLayout ? 'dr-two-col-layout' : '' }}">

            {{-- Basic details --}}
            <section class="dr-panel">
                <div class="dr-panel-head">
                    <i class="fas fa-id-card"></i>
                    <h6>Basic details</h6>
                </div>
                <div class="dr-panel-body">
                    <dl class="dr-kv-grid">
                        @include('admin.doctor_requests.partials.registration_detail_field', [
                            'label' => 'Full name',
                            'value' => e($doctor->name ?? '—'),
                        ])
                        @include('admin.doctor_requests.partials.registration_detail_field', [
                            'label' => 'Mobile',
                            'value' => e($doctor->mobile ?? $doctor->contact_no ?? '—'),
                        ])
                        @if(!empty($allowPortalEmailEdit))
                            @include('admin.doctor_requests.partials.registration_portal_email_editor', ['doctor' => $doctor])
                        @else
                            @include('admin.doctor_requests.partials.registration_detail_field', [
                                'label' => 'Email',
                                'value' => e($doctor->email ?: '—'),
                            ])
                        @endif
                        @php
                            $displayGender = strtolower(trim((string) ($doctor->gender ?? '')));
                            if ($displayGender === '' && ! empty($doctor->age) && is_string($doctor->age) && str_contains($doctor->age, '|')) {
                                $displayGender = strtolower(trim((string) (explode('|', $doctor->age)[1] ?? '')));
                            }
                            $genderLabel = match ($displayGender) {
                                'male' => 'Male',
                                'female' => 'Female',
                                'other' => 'Other',
                                default => '—',
                            };
                        @endphp
                        @include('admin.doctor_requests.partials.registration_detail_field', [
                            'label' => 'Gender',
                            'value' => e($genderLabel),
                        ])
                        @include('admin.doctor_requests.partials.registration_detail_field', [
                            'label' => 'City',
                            'value' => e($doctor->city ?: $doctor->location ?: '—'),
                        ])
                        @if($doctor->customer_name && $doctor->customer_name !== $doctor->name)
                            @include('admin.doctor_requests.partials.registration_detail_field', [
                                'label' => 'Customer name (legacy)',
                                'value' => e($doctor->customer_name),
                            ])
                        @endif
                        @unless($hideProfilePhotoRow)
                            @include('admin.doctor_requests.partials.registration_detail_field', [
                                'label' => 'Profile photo (website)',
                                'full' => true,
                                'value' => $approvedUrl
                                    ? '<a href="'.e($approvedUrl).'" target="_blank" rel="noopener"><img src="'.e($approvedUrl).'" alt="Approved profile" style="max-height:140px;border-radius:10px;border:1px solid #e8ecf1;"></a>'
                                    : '<span class="text-muted small">Not approved for website yet</span>',
                            ])
                            @include('admin.doctor_requests.partials.registration_detail_field', [
                                'label' => 'Photo status',
                                'value' => e(match ($doctor->profile_image_status ?? 'pending_review') {
                                    'approved' => 'Approved — live on CareWeb',
                                    'rejected' => 'Rejected',
                                    default => 'Pending admin review',
                                }),
                            ])
                        @endunless
                    </dl>
                    @if($showRegistrationProfileEditor ?? false)
                        @include('admin.doctor_requests.partials.registration_profile_image_admin', [
                            'doctor' => $doctor,
                            'uploadUrl' => $uploadUrl,
                            'pendingUrl' => $pendingUrl,
                            'approvedUrl' => $approvedUrl,
                        ])
                        @include('admin.doctor_requests.partials.registration_city_editor', ['doctor' => $doctor])
                    @endif
                </div>
            </section>

            {{-- Consultation service --}}
            @unless($doctorPortalReadOnly)
            <section class="dr-panel">
                <div class="dr-panel-head">
                    <i class="fas fa-stethoscope"></i>
                    <h6>Consultation service</h6>
                </div>
                <div class="dr-panel-body">
                    <dl class="dr-kv-grid">
                        @include('admin.doctor_requests.partials.registration_detail_field', [
                            'label' => 'Service',
                            'value' => e($doctor->job_title ?? '—'),
                        ])
                        @if($svcRow && $svcRow->category)
                            @include('admin.doctor_requests.partials.registration_detail_field', [
                                'label' => 'Category (CRM)',
                                'value' => e($svcRow->category ?: '—'),
                            ])
                        @endif
                        @if(count($consultationSubServices) > 0)
                            @include('admin.doctor_requests.partials.registration_detail_field', [
                                'label' => 'Sub-services & tags',
                                'full' => true,
                                'value' => collect($consultationSubServices)->map(function ($row) {
                                    $name = e($row['sub_service_name'] ?? 'Sub-service');
                                    $tags = $row['tags'] ?? [];
                                    $tagHtml = is_array($tags) && count($tags)
                                        ? collect($tags)->map(fn ($t) => '<span class="dr-chip">'.e($t).'</span>')->implode('')
                                        : '<span class="text-muted small">No tags</span>';

                                    return '<div class="mb-2"><strong class="d-block text-dark" style="font-size:0.88rem;">'.$name.'</strong><div class="mt-1">'.$tagHtml.'</div></div>';
                                })->implode(''),
                            ])
                        @elseif($svcRow && is_array($svcRow->specialization_options) && count($svcRow->specialization_options))
                            @include('admin.doctor_requests.partials.registration_detail_field', [
                                'label' => 'Service tags (CRM list)',
                                'full' => true,
                                'value' => collect($svcRow->specialization_options)->map(fn ($o) => '<span class="dr-chip dr-chip--info">'.e($o).'</span>')->implode(''),
                            ])
                        @endif
                    </dl>
                    @if($showRegistrationProfileEditor ?? false)
                        @include('admin.doctor_requests.partials.registration_service_editor', ['doctor' => $doctor])
                    @endif
                </div>
            </section>
            @endunless
            <section class="dr-panel">
                <div class="dr-panel-head">
                    <i class="fas fa-language"></i>
                    <h6>Languages &amp; profile</h6>
                </div>
                <div class="dr-panel-body">
                    <dl class="dr-kv-grid">
                        @include('admin.doctor_requests.partials.registration_detail_field', [
                            'label' => 'Fluent languages',
                            'full' => true,
                            'value' => is_array($langs) && count($langs)
                                ? collect($langs)->map(fn ($code) => '<span class="dr-chip dr-chip--lang">'.e(\Illuminate\Support\Arr::get(config('doctor_registration.fluent_languages', []), $code, $code)).'</span>')->implode('')
                                : '—',
                        ])
                        @if(!($showRegistrationProfileEditor ?? false))
                            @include('admin.doctor_requests.partials.registration_detail_field', [
                                'label' => 'About',
                                'full' => true,
                                'value' => $doctor->about_text ? nl2br(e($doctor->about_text)) : '—',
                            ])
                        @endif
                        @include('admin.doctor_requests.partials.registration_detail_field', [
                            'label' => count($consultationSubServices) > 0 ? 'All tags (combined)' : 'Tags on doctor profile',
                            'full' => true,
                            'value' => is_array($specs) && count($specs)
                                ? collect($specs)->map(fn ($sp) => '<span class="dr-chip">'.e($sp).'</span>')->implode('')
                                : '<span class="text-muted small">No tags selected</span>',
                        ])
                    </dl>
                    @if($showRegistrationProfileEditor ?? false)
                        @include('admin.doctor_requests.partials.registration_gender_languages_editor', ['doctor' => $doctor])
                        @include('admin.doctor_requests.partials.registration_about_editor', ['doctor' => $doctor])
                    @endif
                </div>
            </section>

            @if($showRegistrationProfileEditor ?? false)
                <section class="dr-panel dr-panel--editor">
                    <div class="dr-panel-head">
                        <i class="fas fa-edit"></i>
                        <h6>Edit profile (admin)</h6>
                    </div>
                    <div class="dr-panel-body p-0">
                        @include('admin.doctor_requests.partials.registration_profile_editor')
                    </div>
                </section>
            @endif

            @if ($showCareWebReviewsAdminTools ?? false)
                <section class="dr-panel">
                    <div class="dr-panel-head">
                        <i class="fas fa-star"></i>
                        <h6>CareWeb profile reviews</h6>
                    </div>
                    <div class="dr-panel-body">
                        <p class="small text-muted mb-3">Reviews shown on the public consultation page (doctor profile modal).</p>
                        <button type="button" class="btn btn-sm btn-primary" onclick="if (typeof window.openDoctorWebsiteReviewsModal === 'function') { window.openDoctorWebsiteReviewsModal({{ (int) $doctor->id }}); } else { alert('Reload the doctor requests list page to use the reviews manager.'); }">
                            <i class="fas fa-star mr-1"></i> Manage website reviews
                        </button>
                    </div>
                </section>
            @endif

            {{-- Education --}}
            <section class="dr-panel">
                <div class="dr-panel-head">
                    <i class="fas fa-graduation-cap"></i>
                    <h6>Education</h6>
                    <span class="dr-panel-hint">Newest first</span>
                </div>
                <div class="dr-panel-body">
                    @if(is_array($edu) && count($edu))
                        <ol class="dr-timeline">
                            @foreach($edu as $row)
                                <li>
                                    <div class="dr-timeline-title">{{ $row['degree'] ?? '—' }}</div>
                                    <div class="dr-timeline-meta">
                                        {{ $row['institution'] ?? '—' }}
                                        @if(!empty($row['year_completed']))
                                            · {{ $row['year_completed'] }}
                                        @endif
                                    </div>
                                </li>
                            @endforeach
                        </ol>
                    @else
                        <p class="dr-empty">No education entries.</p>
                    @endif
                </div>
            </section>

            {{-- Experience --}}
            <section class="dr-panel">
                <div class="dr-panel-head">
                    <i class="fas fa-briefcase-medical"></i>
                    <h6>Experience</h6>
                    <span class="dr-panel-hint">Newest first</span>
                </div>
                <div class="dr-panel-body">
                    @if(is_array($exp) && count($exp))
                        <ol class="dr-timeline">
                            @foreach($exp as $row)
                                <li>
                                    <div class="dr-timeline-title">{{ $row['title'] ?? '—' }}</div>
                                    <div class="dr-timeline-meta">
                                        {{ $row['organization'] ?? '—' }}
                                        ({{ $row['from_year'] ?? '?' }} — {{ !empty($row['to_year']) ? $row['to_year'] : 'Present' }})
                                    </div>
                                    @if(!empty($row['details']))
                                        <div class="dr-timeline-detail">{!! nl2br(e($row['details'])) !!}</div>
                                    @endif
                                </li>
                            @endforeach
                        </ol>
                    @else
                        <p class="dr-empty">No experience entries.</p>
                    @endif
                </div>
            </section>

            {{-- Consultation modes --}}
            @unless($doctorPortalReadOnly)
            <section class="dr-panel dr-panel--wide">
                <div class="dr-panel-head">
                    <i class="fas fa-hand-holding-medical"></i>
                    <h6>Consultation modes &amp; charges</h6>
                </div>
                <div class="dr-panel-body">
                    @if($showRegistrationProfileEditor ?? false)
                        @include('admin.doctor_requests.partials.registration_consultation_modes_editor', ['doctor' => $doctor])
                    @endif
                    @if(count($modes))
                        <div class="dr-mode-grid mb-3">
                            @foreach(['online', 'home_visit', 'clinic_visit'] as $modeKey)
                                @if(in_array($modeKey, $modes, true))
                                    <div class="dr-mode-card is-active">
                                        <div class="dr-mode-card-title">
                                            <i class="fas {{ $modeIcons[$modeKey] ?? 'fa-check' }}"></i>
                                            {{ $modeLabels[$modeKey] ?? $modeKey }}
                                        </div>
                                        <dl>
                                            @if($modeKey === 'online')
                                                <dt>Doctor rate (₹)</dt>
                                                <dd>{{ $doctor->online_charges !== null ? number_format((float) $doctor->online_charges, 2) : '—' }}</dd>
                                                <dt>CareWeb fee (₹)</dt>
                                                <dd>{{ $doctor->website_customer_fee_online !== null ? number_format((float) $doctor->website_customer_fee_online, 2) : 'Uses doctor rate' }}</dd>
                                            @elseif($modeKey === 'home_visit')
                                                <dt>Doctor rate (₹)</dt>
                                                <dd>{{ $doctor->home_visit_charges !== null ? number_format((float) $doctor->home_visit_charges, 2) : '—' }}</dd>
                                                <dt>CareWeb fee (₹)</dt>
                                                <dd>{{ $doctor->website_customer_fee_home_visit !== null ? number_format((float) $doctor->website_customer_fee_home_visit, 2) : 'Uses doctor rate' }}</dd>
                                                <dt>Radius (km)</dt>
                                                <dd>{{ $doctor->coverage_radius_km !== null ? $doctor->coverage_radius_km : '—' }}</dd>
                                                <dt>Base location</dt>
                                                <dd>{!! nl2br(e($doctor->base_location_address ?? '—')) !!}</dd>
                                                @if($doctor->base_location_lat !== null && $doctor->base_location_lng !== null)
                                                    <dt>GPS</dt>
                                                    <dd>{{ $doctor->base_location_lat }}, {{ $doctor->base_location_lng }}</dd>
                                                @endif
                                            @else
                                                <dt>Doctor rate (₹)</dt>
                                                <dd>{{ $doctor->clinic_consultation_charges !== null ? number_format((float) $doctor->clinic_consultation_charges, 2) : '—' }}</dd>
                                                <dt>CareWeb fee (₹)</dt>
                                                <dd>{{ $doctor->website_customer_fee_clinic !== null ? number_format((float) $doctor->website_customer_fee_clinic, 2) : 'Uses doctor rate' }}</dd>
                                                <dt>Clinic</dt>
                                                <dd>{{ $doctor->clinic_name ?? '—' }}</dd>
                                                <dt>Address</dt>
                                                <dd>{!! nl2br(e($doctor->clinic_address ?? '—')) !!}</dd>
                                                @if($doctor->clinic_lat !== null && $doctor->clinic_lng !== null)
                                                    <dt>GPS</dt>
                                                    <dd>{{ $doctor->clinic_lat }}, {{ $doctor->clinic_lng }}</dd>
                                                @endif
                                            @endif
                                        </dl>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    @else
                        <p class="dr-empty">No consultation modes selected.</p>
                    @endif

                    @if(count($consultationPricing) > 0)
                        <div class="mt-3">
                            <div class="small text-muted mb-2"><strong>Service / sub-service wise pricing</strong></div>
                            @foreach($consultationPricing as $row)
                                @php
                                    $subName = $row['sub_service_name'] ?? null;
                                    $title = $subName ? ('Sub-service: '.$subName) : 'Service pricing';
                                    $m = is_array($row['modes'] ?? null) ? $row['modes'] : [];
                                @endphp
                                <div class="dr-panel dr-panel--soft mb-2">
                                    <div class="dr-panel-body py-2">
                                        <div class="font-weight-bold mb-2" style="font-size:0.88rem;">{{ $title }}</div>
                                        <div class="d-flex flex-wrap" style="gap:8px;">
                                            @foreach($m as $modeKey => $modeRow)
                                                @php
                                                    $modeRow = is_array($modeRow) ? $modeRow : [];
                                                    $label = $modeLabels[$modeKey] ?? $modeKey;
                                                    $docP = $modeRow['doctor_price'] ?? null;
                                                    $webP = $modeRow['website_price'] ?? null;
                                                    $locked = !empty($modeRow['locked']);
                                                @endphp
                                                <div class="border rounded px-2 py-1 bg-white">
                                                    <div class="small text-muted">{{ $label }} {!! $locked ? '<i class="fas fa-lock text-success"></i>' : '<i class="fas fa-pen text-warning"></i>' !!}</div>
                                                    <div class="small">
                                                        <strong>Doctor:</strong> {{ $docP !== null && $docP !== '' ? '₹'.number_format((float) $docP, 2) : '—' }}
                                                        <span class="mx-1">|</span>
                                                        <strong>Website:</strong> {{ $webP !== null && $webP !== '' ? '₹'.number_format((float) $webP, 2) : '—' }}
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    <dl class="dr-kv-grid">
                        @include('admin.doctor_requests.partials.registration_detail_field', [
                            'label' => 'Referral partner',
                            'full' => true,
                            'value' => $doctor->doctorReferralUser
                                ? e($doctor->doctorReferralUser->name).' ('.e($doctor->doctorReferralUser->mobile).')'
                                    .($doctor->doctor_referral_user_id
                                        ? ' · Commission: Online '.$doctor->referralCommissionAdminLabelForMode('online')
                                            .', Home '.$doctor->referralCommissionAdminLabelForMode('home_visit')
                                            .', Clinic '.$doctor->referralCommissionAdminLabelForMode('clinic_visit')
                                        : '')
                                : '—',
                        ])
                    </dl>
                </div>
            </section>
            @endunless

            @unless($hideWebsiteConsultationBookingsSection)
                @php
                    $webBookings = $doctor->relationLoaded('consultationWebsiteBookings')
                        ? $doctor->consultationWebsiteBookings
                        : $doctor->consultationWebsiteBookings()->with('consultationService:id,name')->orderByDesc('created_at')->limit(100)->get();
                @endphp
                <section class="dr-panel dr-panel--wide">
                    <div class="dr-panel-head">
                        <i class="fas fa-calendar-check"></i>
                        <h6>Website consultation bookings</h6>
                    </div>
                    <div class="dr-panel-body">
                        @if($webBookings->isEmpty())
                            <p class="dr-empty">No website bookings yet.</p>
                        @else
                            <div class="dr-table-wrap">
                                <table class="table table-sm dr-table-pro mb-0">
                                    <thead>
                                        <tr>
                                            <th>Submitted</th>
                                            <th>Appointment</th>
                                            <th>Slot</th>
                                            <th>Customer</th>
                                            <th>Contact</th>
                                            <th>Location</th>
                                            <th>Mode</th>
                                            <th>Fee (₹)</th>
                                            <th>Service</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($webBookings as $wb)
                                            <tr>
                                                <td>{{ $fmtDate($wb->created_at) }}</td>
                                                <td>{{ $fmtDate($wb->appointment_date, 'd M Y') }}</td>
                                                <td>{{ \App\Models\ConsultationWebsiteBooking::timeRangeLabel($wb->appointment_start_time, $wb->appointment_end_time) }}</td>
                                                <td>{{ $wb->customer_name }}</td>
                                                <td>{{ $wb->contact_no }}</td>
                                                <td class="small" style="max-width:200px;">
                                                    @if($wb->customer_address || $wb->customer_city)
                                                        <div>{{ $wb->customer_address ?: '—' }}</div>
                                                        <div class="text-muted">{{ $wb->customer_city }}</div>
                                                        @php
                                                            $wMap = \App\Models\ConsultationWebsiteBooking::googleMapsOpenUrl(
                                                                $wb->customer_address_lat !== null ? (float) $wb->customer_address_lat : null,
                                                                $wb->customer_address_lng !== null ? (float) $wb->customer_address_lng : null
                                                            );
                                                        @endphp
                                                        @if($wMap)
                                                            <a href="{{ $wMap }}" target="_blank" rel="noopener">Map</a>
                                                        @endif
                                                    @else
                                                        —
                                                    @endif
                                                </td>
                                                <td>{{ \App\Models\ConsultationWebsiteBooking::modeLabel($wb->consultation_mode) }}</td>
                                                <td>{{ $wb->booking_fee_amount !== null ? number_format((float) $wb->booking_fee_amount, 2) : '—' }}</td>
                                                <td>{{ $wb->consultationService?->name ?? '—' }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                </section>
            @endunless

            @unless($hideCalendarAvailabilitySection)
                @php
                    $calSlots = $doctor->relationLoaded('calendarAvailabilitySlots')
                        ? $doctor->calendarAvailabilitySlots
                        : $doctor->calendarAvailabilitySlots()->where('slot_date', '>=', now()->subDays(1)->toDateString())->orderBy('slot_date')->limit(200)->get();
                @endphp
                <section class="dr-panel">
                    <div class="dr-panel-head">
                        <i class="fas fa-calendar-alt"></i>
                        <h6>Calendar availability</h6>
                    </div>
                    <div class="dr-panel-body">
                        @if($calSlots->isEmpty())
                            <p class="dr-empty">No calendar slots recorded.</p>
                        @else
                            <div class="dr-table-wrap">
                                <table class="table table-sm dr-table-pro mb-0">
                                    <thead>
                                        <tr><th>Date</th><th>Hours</th></tr>
                                    </thead>
                                    <tbody>
                                        @foreach($calSlots as $cs)
                                            <tr>
                                                <td>{{ $fmtDate($cs->slot_date, 'd M Y (D)') }}</td>
                                                <td>{{ $cs->fullLabel() }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                </section>
            @endunless

            @unless($hideSetConsultationChargesSection)
                <section class="dr-panel dr-panel--wide">
                    <div class="dr-panel-body p-0">
                        <div class="dr-inline-pricing-wrap m-0 border-0 rounded-0" data-dr-id="{{ $doctor->id }}">
                            <h6 class="font-weight-bold mb-2"><i class="fas fa-rupee-sign text-warning mr-1"></i> Set consultation charges (admin)</h6>
                            <p class="small text-muted mb-3">Set per sub-service charges below. Assign a <strong>referral user</strong> and set commission per mode — <strong>₹ fixed</strong> or <strong>% of paid booking</strong>.</p>
                            @php
                                $drRefSelectList = isset($doctorReferralUsers) ? $doctorReferralUsers : collect();
                            @endphp
                            @if($drRefSelectList->isNotEmpty())
                                <div class="form-group mb-2">
                                    <label class="small font-weight-bold d-block">Doctor referral user</label>
                                    <select class="form-control form-control-sm dr-inline-p-referral">
                                        <option value="">— None —</option>
                                        @foreach($drRefSelectList as $drOpt)
                                            <option value="{{ $drOpt->id }}" {{ (int) ($doctor->doctor_referral_user_id ?? 0) === (int) $drOpt->id ? 'selected' : '' }}>
                                                {{ $drOpt->name }} ({{ $drOpt->mobile }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="dr-referral-commission-panel border rounded px-2 py-2 mb-3 bg-light" style="{{ $doctor->doctor_referral_user_id ? '' : 'display:none;' }}">
                                    <div class="small font-weight-bold text-uppercase text-muted mb-2">Referral commission (per paid booking)</div>
                                    @include('admin.doctor_requests.partials.referral_commission_inputs', [
                                        'doctor' => $doctor,
                                        'inputSize' => 'sm',
                                        'valueClassOnline' => 'dr-inline-p-ref-comm-online dr-ref-comm-val-online',
                                        'typeClassOnline' => 'dr-inline-p-ref-type-online dr-ref-comm-type-online',
                                        'valueClassHome' => 'dr-inline-p-ref-comm-home dr-ref-comm-val-home',
                                        'typeClassHome' => 'dr-inline-p-ref-type-home dr-ref-comm-type-home',
                                        'valueClassClinic' => 'dr-inline-p-ref-comm-clinic dr-ref-comm-val-clinic',
                                        'typeClassClinic' => 'dr-inline-p-ref-type-clinic dr-ref-comm-type-clinic',
                                    ])
                                </div>
                            @endif
                            @php
                                $inlineSubServices = is_array($doctor->consultation_sub_services ?? null) ? $doctor->consultation_sub_services : [];
                                $inlineHasSubs = count($inlineSubServices) > 0;
                                $inlineServiceId = 0;
                                if (! empty($doctor->job_title)) {
                                    $inlineServiceId = (int) (\App\Models\DoctorConsultationService::query()
                                        ->where('is_active', true)
                                        ->where('name', $doctor->job_title)
                                        ->value('id') ?? 0);
                                }
                                $inlineLocationId = 0;
                                $inlineCity = trim((string) ($doctor->city ?? $doctor->location ?? ''));
                                if ($inlineCity !== '') {
                                    $inlineLocationId = (int) (\App\Models\Location::query()->where('name', $inlineCity)->value('id') ?? 0);
                                }
                            @endphp
                            <div class="dr-inline-pricing-subs mb-3"
                                 data-service-id="{{ $inlineServiceId }}"
                                 data-location-id="{{ $inlineLocationId }}"
                                 data-dr-id="{{ $doctor->id }}">
                                @if($inlineHasSubs)
                                    @foreach($inlineSubServices as $subRow)
                                        @php
                                            $subId = (int) ($subRow['sub_service_id'] ?? 0);
                                            $subName = (string) ($subRow['sub_service_name'] ?? 'Sub-service');
                                        @endphp
                                        @if($subId > 0)
                                            <div class="border rounded px-2 py-2 mb-2 bg-white">
                                                <div class="small font-weight-bold mb-1">{{ $subName }}</div>
                                                @include('admin.doctor_requests.partials.doctor_per_subservice_pricing_block', [
                                                    'doctor' => $doctor,
                                                    'subServiceId' => $subId,
                                                    'subServiceName' => $subName,
                                                    'scopePrefix' => 'inline',
                                                    'inputSize' => 'sm',
                                                ])
                                            </div>
                                        @endif
                                    @endforeach
                                @elseif(! empty($doctor->job_title))
                                    <div class="border rounded px-2 py-2 mb-2 bg-white">
                                        <div class="small font-weight-bold mb-1">{{ $doctor->job_title }}</div>
                                        @include('admin.doctor_requests.partials.doctor_per_subservice_pricing_block', [
                                            'doctor' => $doctor,
                                            'subServiceId' => 0,
                                            'subServiceName' => $doctor->job_title,
                                            'scopePrefix' => 'inline',
                                            'inputSize' => 'sm',
                                        ])
                                    </div>
                                @else
                                    <p class="small text-muted mb-2">Set consultation service in the editor above to configure per sub-service charges.</p>
                                @endif
                            </div>
                            <div class="form-group mb-2">
                                <label class="small font-weight-bold d-block">Note (audit log)</label>
                                <input type="text" class="form-control form-control-sm dr-inline-p-note" maxlength="2000" placeholder="Reason for change">
                            </div>
                            <button type="button" class="btn btn-primary btn-sm dr-inline-pricing-save">Save charges</button>
                            <span class="ml-2 small text-muted dr-inline-pricing-msg"></span>
                        </div>
                    </div>
                </section>
            @endunless

            {{-- Address --}}
            <section class="dr-panel">
                <div class="dr-panel-head">
                    <i class="fas fa-map-marker-alt"></i>
                    <h6>Address</h6>
                </div>
                <div class="dr-panel-body">
                    <dl class="dr-kv-grid">
                        @include('admin.doctor_requests.partials.registration_detail_field', [
                            'label' => 'Permanent address',
                            'full' => true,
                            'value' => $doctor->permanent_address ? nl2br(e($doctor->permanent_address)) : '—',
                        ])
                        @include('admin.doctor_requests.partials.registration_detail_field', [
                            'label' => 'Current address',
                            'full' => true,
                            'value' => $doctor->current_address ? nl2br(e($doctor->current_address)) : '—',
                        ])
                        @include('admin.doctor_requests.partials.registration_detail_field', [
                            'label' => 'Same as permanent',
                            'value' => $doctor->current_same_as_permanent ? 'Yes' : 'No',
                        ])
                    </dl>
                </div>
            </section>

            @if($doctor->age || ($doctor->location && $doctor->location !== $doctor->city))
                <section class="dr-panel">
                    <div class="dr-panel-head">
                        <i class="fas fa-info-circle"></i>
                        <h6>Other (legacy)</h6>
                    </div>
                    <div class="dr-panel-body">
                        <dl class="dr-kv-grid">
                            @if($doctor->age)
                                @include('admin.doctor_requests.partials.registration_detail_field', ['label' => 'Age', 'value' => e($doctor->age)])
                            @endif
                            @if($doctor->location && $doctor->location !== $doctor->city)
                                @include('admin.doctor_requests.partials.registration_detail_field', ['label' => 'Location', 'value' => e($doctor->location)])
                            @endif
                        </dl>
                    </div>
                </section>
            @endif

            {{-- Bank --}}
            <section class="dr-panel">
                <div class="dr-panel-head">
                    <i class="fas fa-university"></i>
                    <h6>Bank account</h6>
                </div>
                <div class="dr-panel-body">
                    <dl class="dr-kv-grid">
                        @include('admin.doctor_requests.partials.registration_detail_field', ['label' => 'Account holder', 'value' => e($doctor->account_name ?? '—')])
                        @include('admin.doctor_requests.partials.registration_detail_field', ['label' => 'Bank name', 'value' => e($doctor->bank_name ?? '—')])
                        @include('admin.doctor_requests.partials.registration_detail_field', ['label' => 'IFSC', 'value' => e($doctor->ifsc_code ?? '—')])
                        @include('admin.doctor_requests.partials.registration_detail_field', ['label' => 'Account number', 'value' => e($doctor->account_number ?? '—')])
                        @if($doctor->upi_id)
                            @include('admin.doctor_requests.partials.registration_detail_field', ['label' => 'UPI ID', 'value' => e($doctor->upi_id)])
                        @endif
                    </dl>
                    @if($doctor->bank_document)
                        <div class="mt-3">
                            <div class="dr-doc-card-title mb-2">Cancelled cheque / statement</div>
                            @php $docPreviewBlock($doctor->bank_document, 'bank-document'); @endphp
                        </div>
                    @endif
                </div>
            </section>

            @if(!$hideDocumentsSection)
                <section class="dr-panel dr-panel--wide">
                    <div class="dr-panel-head">
                        <i class="fas fa-file-medical"></i>
                        <h6>Documents</h6>
                    </div>
                    <div class="dr-panel-body">
                        <div class="dr-doc-grid">
                            <div class="dr-doc-card">
                                <div class="dr-doc-card-title">Aadhaar</div>
                                @php $docPreviewBlock($doctor->aadhar_card, 'aadhaar'); @endphp
                            </div>
                            <div class="dr-doc-card">
                                <div class="dr-doc-card-title">PAN</div>
                                @php $docPreviewBlock($doctor->pan_card, 'pan'); @endphp
                            </div>
                            <div class="dr-doc-card">
                                <div class="dr-doc-card-title">Education certificate</div>
                                @php $docPreviewBlock($doctor->qualification_certificate, 'qualification-certificate'); @endphp
                            </div>
                        </div>

                        @unless($doctorPortalReadOnly ?? false)
                            <div class="mt-3" id="drLeegalityPanelWrap">
                                @include('admin.doctor_requests.partials.leegality_agreement_panel', [
                                    'doctor' => $doctor,
                                    'leegalitySignature' => $doctor->latestLeegalitySignature,
                                ])
                            </div>
                        @endunless
                    </div>
                </section>
            @endif

            @if($doctorPortalReadOnly && ! $deferPortalPricingSection)
                <section class="dr-panel dr-panel--wide">
                    <div class="dr-panel-head">
                        <i class="fas fa-stethoscope"></i>
                        <h6>My consultation services &amp; pricing</h6>
                    </div>
                    <div class="dr-panel-body">
                        @include('doctor_carelix.partials.services_pricing_summary', ['doctor' => $doctor])
                    </div>
                </section>
            @endif

            @if($doctor->admin_remark || $doctor->reviewed_at)
                <section class="dr-panel">
                    <div class="dr-panel-head">
                        <i class="fas fa-user-shield"></i>
                        <h6>Admin review</h6>
                    </div>
                    <div class="dr-panel-body">
                        <dl class="dr-kv-grid">
                            @if($doctor->reviewed_at)
                                @include('admin.doctor_requests.partials.registration_detail_field', [
                                    'label' => 'Reviewed at',
                                    'value' => e($fmtDate($doctor->reviewed_at)),
                                ])
                            @endif
                            @if($doctor->reviewer)
                                @include('admin.doctor_requests.partials.registration_detail_field', [
                                    'label' => 'Reviewed by',
                                    'value' => e(trim(($doctor->reviewer->f_name ?? '').' '.($doctor->reviewer->l_name ?? ''))),
                                ])
                            @endif
                            @if($doctor->admin_remark)
                                @include('admin.doctor_requests.partials.registration_detail_field', [
                                    'label' => 'Remark',
                                    'full' => true,
                                    'value' => nl2br(e($doctor->admin_remark)),
                                ])
                            @endif
                        </dl>
                    </div>
                </section>
            @endif

            @if($doctor->priceLogs && $doctor->priceLogs->count())
                <section class="dr-panel dr-panel--wide">
                    <div class="dr-panel-head">
                        <i class="fas fa-history"></i>
                        <h6>Price change history</h6>
                    </div>
                    <div class="dr-panel-body">
                        <div class="dr-table-wrap">
                            <table class="table table-sm dr-table-pro mb-0">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Mode</th>
                                        <th>Old (₹)</th>
                                        <th>New (₹)</th>
                                        <th>By</th>
                                        <th>Note</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($doctor->priceLogs as $log)
                                        <tr>
                                            <td>{{ $fmtDate($log->created_at, 'Y-m-d H:i') }}</td>
                                            <td>{{ $log->mode }}</td>
                                            <td>{{ $log->old_amount !== null ? $log->old_amount : '—' }}</td>
                                            <td>{{ $log->new_amount }}</td>
                                            <td>{{ $log->updatedByUser ? trim(($log->updatedByUser->f_name ?? '').' '.($log->updatedByUser->l_name ?? '')) : '—' }}</td>
                                            <td><small>{{ $log->note }}</small></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </section>
            @endif

        </div>
    </div>
</div>
