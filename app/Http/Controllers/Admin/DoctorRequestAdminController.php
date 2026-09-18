<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ConsultationWebsiteBooking;
use App\Models\DoctorConsultationService;
use App\Models\DoctorReferralUser;
use App\Models\DoctorConsultationPriceChangeRequest;
use App\Models\DoctorRequest;
use App\Models\DoctorRequestPriceLog;
use App\Models\DoctorWebsiteProfileReview;
use App\Services\DoctorConsultationPriceChangeService;
use App\Services\DoctorConsultationPricingService;
use App\Services\DoctorConsultationRegistrationTagsService;
use App\Services\DoctorProfileCoatService;
use App\Services\DoctorRegistrationAboutAiService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class DoctorRequestAdminController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:view_doctor_requests');
    }

    public function index()
    {
        $doctorReferralUsers = DoctorReferralUser::query()->withCount('doctorRequests')->orderByDesc('id')->get();
        $pendingPriceChangeCount = DoctorConsultationPriceChangeRequest::query()
            ->where('status', DoctorConsultationPriceChangeRequest::STATUS_PENDING)
            ->count();
        $portalCommissionDoctors = DoctorRequest::query()
            ->where('approval_status', 'approved')
            ->orderBy('name')
            ->get(['id', 'name', 'mobile', 'contact_no', 'portal_lead_commission_percent']);

        return view('admin.doctor_requests.index', compact(
            'doctorReferralUsers',
            'pendingPriceChangeCount',
            'portalCommissionDoctors'
        ));
    }

    public function show(DoctorRequest $doctor_request)
    {
        $doctor_request->load($this->doctorDetailRelations());
        $doctorReferralUsers = DoctorReferralUser::query()->where('is_active', true)->orderBy('name')->get();

        return view('admin.doctor_requests.show', [
            'doctor' => $doctor_request,
            'doctorReferralUsers' => $doctorReferralUsers,
            'showCareWebReviewsAdminTools' => true,
            'showRegistrationProfileEditor' => true,
        ]);
    }

    /**
     * HTML fragment for the View modal on the doctor requests list.
     */
    public function viewModal(DoctorRequest $doctor_request)
    {
        $doctor_request->load($this->doctorDetailRelations());
        $doctorReferralUsers = DoctorReferralUser::query()->where('is_active', true)->orderBy('name')->get();

        return view('admin.doctor_requests.partials.registration_detail_card', [
            'doctor' => $doctor_request,
            'doctorReferralUsers' => $doctorReferralUsers,
            'showCareWebReviewsAdminTools' => true,
            'showRegistrationProfileEditor' => true,
        ]);
    }

    private function doctorDetailRelations(): array
    {
        return [
            'doctorReferralUser:id,name,mobile,commission_percent',
            'reviewer:id,f_name,l_name',
            'priceLogs' => function ($q) {
                $q->with('updatedByUser:id,f_name,l_name')->orderByDesc('created_at')->limit(50);
            },
            'consultationWebsiteBookings' => function ($q) {
                $q->with('consultationService:id,name,consultation_duration_minutes')->orderByDesc('created_at')->limit(100);
            },
            'calendarAvailabilitySlots' => function ($q) {
                $q->where('slot_date', '>=', now()->subDays(1)->toDateString())->orderBy('slot_date')->limit(200);
            },
            'websiteProfileReviews' => function ($q) {
                $q->orderBy('sort_order')->orderByDesc('id');
            },
            'latestLeegalitySignature',
        ];
    }

    public function data(Request $request)
    {
        $q = DoctorRequest::query()->orderBy('created_at', 'desc');

        if ($request->filled('approval_status')) {
            $q->where('approval_status', $request->approval_status);
        }

        $data = $q->withCount([
            'pendingPriceChangeRequests as pending_price_change_count',
        ])->with('latestLeegalitySignature')->get()->map(function (DoctorRequest $row) {
            $sig = $row->latestLeegalitySignature;

            return [
                'id' => $row->id,
                'lead_id' => $row->lead_id,
                'name' => $row->name,
                'mobile' => $row->mobile ?? $row->contact_no,
                'job_title' => $row->job_title,
                'approval_status' => $row->approval_status,
                'agreement_status' => $sig?->signature_status,
                'agreement_status_label' => $sig ? ($sig->statusEmoji().' '.$sig->statusLabel()) : '—',
                'agreement_number' => $row->agreement_number ?? '—',
                'partner_id' => $row->partner_id ?? '—',
                'created_at' => optional($row->created_at)->format('Y-m-d H:i'),
                'pending_price_change_count' => (int) ($row->pending_price_change_count ?? 0),
                'actions' => view('admin.doctor_requests._actions', ['row' => $row])->render(),
            ];
        });

        return response()->json(['data' => $data]);
    }

    /**
     * Mobile admin: list doctor requests (no HTML actions).
     */
    public function apiIndex(Request $request)
    {
        $q = DoctorRequest::query()->orderBy('created_at', 'desc');

        if ($request->filled('approval_status')) {
            $q->where('approval_status', $request->approval_status);
        }

        $data = $q->get()->map(function (DoctorRequest $row) {
            return [
                'id' => $row->id,
                'lead_id' => $row->lead_id,
                'name' => $row->name,
                'mobile' => $row->mobile ?? $row->contact_no,
                'job_title' => $row->job_title,
                'approval_status' => $row->approval_status,
                'created_at' => optional($row->created_at)->format('Y-m-d H:i'),
            ];
        });

        return response()->json(['data' => $data]);
    }

    /**
     * Mobile admin: full doctor request record for detail screen.
     */
    public function apiShow(Request $request, DoctorRequest $doctor_request)
    {
        $doctor_request->load($this->doctorDetailRelations());

        $doctor = $doctor_request->toArray();
        $publicPath = $doctor_request->profile_image_status === 'approved'
            ? $doctor_request->profile_image
            : null;
        $doctor['profile_image_url'] = $this->absolutePublicFileUrl($publicPath);
        $doctor['profile_image_upload_url'] = $this->absolutePublicFileUrl($doctor_request->profile_image_upload);
        $doctor['profile_image_pending_url'] = $this->absolutePublicFileUrl($doctor_request->profile_image_pending);
        foreach (['aadhar_card', 'pan_card', 'qualification_certificate', 'bank_document'] as $field) {
            $doctor[$field.'_url'] = $this->absolutePublicFileUrl($doctor_request->{$field});
        }

        return response()->json(['doctor' => $doctor]);
    }

    /**
     * Absolute URL for a path on the public disk. Uses the incoming request host
     * so LAN / device access matches where the API was called (avoids APP_URL mismatches).
     */
    private function absolutePublicFileUrl(?string $path): ?string
    {
        if ($path === null || $path === '') {
            return null;
        }
        $path = str_replace('\\', '/', trim((string) $path));
        if (preg_match('#^https?://#i', $path)) {
            return $path;
        }
        $path = ltrim($path, '/');
        $relative = str_starts_with($path, 'storage/') ? '/'.$path : '/storage/'.$path;
        $host = rtrim((string) request()->getSchemeAndHttpHost(), '/');
        if ($host === '') {
            $host = rtrim((string) config('app.url', ''), '/');
        }

        return $host.$relative;
    }

    public function generateProfileImageCoat(DoctorRequest $doctor_request, DoctorProfileCoatService $coatService): JsonResponse
    {
        $result = $coatService->generateCoatPreview($doctor_request);
        if (! ($result['success'] ?? false)) {
            return response()->json([
                'success' => false,
                'message' => $result['message'] ?? 'Generation failed.',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => $result['message'] ?? 'Preview ready.',
            'preview_url' => $result['preview_url'] ?? $this->absolutePublicFileUrl($doctor_request->fresh()->profile_image_pending),
        ]);
    }

    public function approveProfileImage(DoctorRequest $doctor_request, DoctorProfileCoatService $coatService): JsonResponse
    {
        try {
            $coatService->approvePending($doctor_request, Auth::id());
        } catch (\InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Profile photo approved. It is now live on the CareWeb consultation page.',
            'profile_image_url' => $this->absolutePublicFileUrl($doctor_request->fresh()->profile_image),
        ]);
    }

    public function updateStatus(Request $request, DoctorRequest $doctor_request)
    {
        $request->validate([
            'approval_status' => 'required|in:pending,approved,rejected',
            'admin_remark' => 'nullable|string|max:2000',
        ]);
        $doctor_request->approval_status = $request->approval_status;
        $doctor_request->admin_remark = $request->admin_remark;
        $doctor_request->reviewed_at = now();
        $doctor_request->reviewed_by = Auth::id();
        $doctor_request->save();

        return response()->json([
            'success' => true,
            'message' => 'Status updated.',
        ]);
    }

    public function pricingEdit(DoctorRequest $doctor_request)
    {
        $modes = $doctor_request->consultation_modes ?? [];
        $jobTitle = trim((string) ($doctor_request->job_title ?? ''));
        $serviceRow = $jobTitle !== ''
            ? DoctorConsultationService::query()->where('is_active', true)->where('name', $jobTitle)->first()
            : null;
        $city = trim((string) ($doctor_request->city ?? $doctor_request->location ?? ''));
        $locationId = 0;
        if ($city !== '') {
            $locationId = (int) (\App\Models\Location::query()->where('name', $city)->value('id') ?? 0);
        }

        return response()->json([
            'id' => $doctor_request->id,
            'name' => $doctor_request->name,
            'job_title' => $jobTitle,
            'service_id' => $serviceRow ? (int) $serviceRow->id : 0,
            'location_id' => $locationId,
            'modes' => $modes,
            'consultation_sub_services' => is_array($doctor_request->consultation_sub_services ?? null)
                ? $doctor_request->consultation_sub_services
                : [],
            'consultation_pricing' => is_array($doctor_request->consultation_pricing ?? null)
                ? $doctor_request->consultation_pricing
                : [],
            'online_charges' => $doctor_request->online_charges,
            'home_visit_charges' => $doctor_request->home_visit_charges,
            'clinic_consultation_charges' => $doctor_request->clinic_consultation_charges,
            'website_customer_fee_online' => $doctor_request->website_customer_fee_online,
            'website_customer_fee_home_visit' => $doctor_request->website_customer_fee_home_visit,
            'website_customer_fee_clinic' => $doctor_request->website_customer_fee_clinic,
            'doctor_referral_user_id' => $doctor_request->doctor_referral_user_id,
            'referral_commission_online' => $doctor_request->referral_commission_online,
            'referral_commission_online_type' => $doctor_request->referral_commission_online_type ?? 'fixed',
            'referral_commission_home_visit' => $doctor_request->referral_commission_home_visit,
            'referral_commission_home_visit_type' => $doctor_request->referral_commission_home_visit_type ?? 'fixed',
            'referral_commission_clinic' => $doctor_request->referral_commission_clinic,
            'referral_commission_clinic_type' => $doctor_request->referral_commission_clinic_type ?? 'fixed',
            'referral_users' => $this->referralUsersForPricingSelect(),
        ]);
    }

    /** @return list<array{id:int,name:string,mobile:string,label:string}> */
    private function referralUsersForPricingSelect(): array
    {
        return DoctorReferralUser::query()->where('is_active', true)->orderBy('name')->get()->map(static function (DoctorReferralUser $r) {
            return [
                'id' => $r->id,
                'name' => $r->name,
                'mobile' => $r->mobile,
                'label' => $r->name.' ('.$r->mobile.')',
            ];
        })->values()->all();
    }

    private function clearDoctorReferralCommissions(DoctorRequest $doctor_request): void
    {
        $doctor_request->referral_commission_online = null;
        $doctor_request->referral_commission_home_visit = null;
        $doctor_request->referral_commission_clinic = null;
        $doctor_request->referral_commission_online_type = 'fixed';
        $doctor_request->referral_commission_home_visit_type = 'fixed';
        $doctor_request->referral_commission_clinic_type = 'fixed';
    }

    private function applyDoctorReferralCommissionsFromRequest(Request $request, DoctorRequest $doctor_request): void
    {
        $pairs = [
            ['online', 'referral_commission_online', 'referral_commission_online_type'],
            ['home_visit', 'referral_commission_home_visit', 'referral_commission_home_visit_type'],
            ['clinic_visit', 'referral_commission_clinic', 'referral_commission_clinic_type'],
        ];

        foreach ($pairs as [$mode, $valueAttr, $typeAttr]) {
            if ($request->has($typeAttr)) {
                $type = strtolower(trim((string) $request->input($typeAttr, 'fixed')));
                $doctor_request->{$typeAttr} = $type === 'percent' ? 'percent' : 'fixed';
            }

            if (! $request->has($valueAttr)) {
                continue;
            }

            $raw = $request->input($valueAttr);
            if ($raw === null || $raw === '') {
                $doctor_request->{$valueAttr} = null;

                continue;
            }

            $num = (float) $raw;
            $type = $doctor_request->{$typeAttr} ?? 'fixed';
            if ($type === 'percent' && $num > 100) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    $valueAttr => ['Percentage cannot exceed 100.'],
                ]);
            }

            $doctor_request->{$valueAttr} = $raw;
        }
    }

    public function pricingLogs(DoctorRequest $doctor_request)
    {
        $logs = $doctor_request->priceLogs()
            ->with('updatedByUser:id,f_name,l_name')
            ->limit(100)
            ->get()
            ->map(function (DoctorRequestPriceLog $log) {
                return [
                    'mode' => $log->mode,
                    'old_amount' => $log->old_amount,
                    'new_amount' => $log->new_amount,
                    'note' => $log->note,
                    'updated_by' => $log->updatedByUser
                        ? trim(($log->updatedByUser->f_name ?? '') . ' ' . ($log->updatedByUser->l_name ?? ''))
                        : null,
                    'created_at' => $log->created_at?->format('Y-m-d H:i'),
                ];
            });

        return response()->json(['data' => $logs]);
    }

    public function updatePricing(Request $request, DoctorRequest $doctor_request)
    {
        $request->validate([
            'online_charges' => 'nullable|numeric|min:0',
            'home_visit_charges' => 'nullable|numeric|min:0',
            'clinic_consultation_charges' => 'nullable|numeric|min:0',
            'website_customer_fee_online' => 'nullable|numeric|min:0',
            'website_customer_fee_home_visit' => 'nullable|numeric|min:0',
            'website_customer_fee_clinic' => 'nullable|numeric|min:0',
            'doctor_referral_user_id' => 'nullable|integer|exists:doctor_referral_users,id',
            'referral_commission_online' => 'nullable|numeric|min:0',
            'referral_commission_home_visit' => 'nullable|numeric|min:0',
            'referral_commission_clinic' => 'nullable|numeric|min:0',
            'referral_commission_online_type' => 'nullable|in:fixed,percent',
            'referral_commission_home_visit_type' => 'nullable|in:fixed,percent',
            'referral_commission_clinic_type' => 'nullable|in:fixed,percent',
            'note' => 'nullable|string|max:2000',
        ]);

        $note = $request->input('note');
        $userId = Auth::id();

        $mapDoctor = [
            'online' => 'online_charges',
            'home_visit' => 'home_visit_charges',
            'clinic_visit' => 'clinic_consultation_charges',
        ];
        $mapWebsiteCustomer = [
            'website_customer_online' => 'website_customer_fee_online',
            'website_customer_home_visit' => 'website_customer_fee_home_visit',
            'website_customer_clinic' => 'website_customer_fee_clinic',
        ];

        DB::transaction(function () use ($request, $doctor_request, $mapDoctor, $mapWebsiteCustomer, $note, $userId) {
            if ($request->has('doctor_referral_user_id')) {
                $refId = $request->input('doctor_referral_user_id');
                $doctor_request->doctor_referral_user_id = ($refId === null || $refId === '') ? null : (int) $refId;
                if (! $doctor_request->doctor_referral_user_id) {
                    $this->clearDoctorReferralCommissions($doctor_request);
                }
            }
            if ($doctor_request->doctor_referral_user_id) {
                $this->applyDoctorReferralCommissionsFromRequest($request, $doctor_request);
            }
            foreach ([$mapDoctor, $mapWebsiteCustomer] as $map) {
                foreach ($map as $mode => $attr) {
                    if (! $request->has($attr)) {
                        continue;
                    }
                    $newRaw = $request->input($attr);
                    $oldVal = $doctor_request->{$attr};
                    if ($newRaw === null || $newRaw === '') {
                        if ($oldVal !== null) {
                            DoctorRequestPriceLog::create([
                                'doctor_request_id' => $doctor_request->id,
                                'mode' => $mode,
                                'old_amount' => $oldVal,
                                'new_amount' => null,
                                'note' => $note,
                                'updated_by' => $userId,
                            ]);
                            $doctor_request->{$attr} = null;
                        }

                        continue;
                    }
                    $new = (string) $newRaw;
                    $old = $oldVal === null ? null : (string) $oldVal;
                    if ($old !== null && (string) round((float) $old, 2) === (string) round((float) $new, 2)) {
                        continue;
                    }
                    DoctorRequestPriceLog::create([
                        'doctor_request_id' => $doctor_request->id,
                        'mode' => $mode,
                        'old_amount' => $oldVal,
                        'new_amount' => $new,
                        'note' => $note,
                        'updated_by' => $userId,
                    ]);
                    $doctor_request->{$attr} = $new;
                }
            }

            if ($request->has('consultation_pricing')) {
                $jobTitle = trim((string) ($doctor_request->job_title ?? ''));
                $serviceRow = $jobTitle !== ''
                    ? DoctorConsultationService::query()->where('is_active', true)->where('name', $jobTitle)->with(['subServices' => fn ($q) => $q->where('is_active', true)])->first()
                    : null;
                if ($serviceRow) {
                    $normalizedSubServices = is_array($doctor_request->consultation_sub_services ?? null)
                        ? $doctor_request->consultation_sub_services
                        : [];
                    $pricingRaw = $this->decodeJsonArrayProfile($request->input('consultation_pricing', []));
                    $pricingService = app(DoctorConsultationPricingService::class);
                    $pricingNormalized = $pricingService->normalizeAdminPricing(
                        $doctor_request,
                        $serviceRow,
                        $normalizedSubServices,
                        $pricingRaw
                    );
                    if (isset($pricingNormalized['error'])) {
                        throw \Illuminate\Validation\ValidationException::withMessages([
                            'consultation_pricing' => [(string) $pricingNormalized['error']],
                        ]);
                    }
                    $doctor_request->consultation_pricing = $pricingNormalized['pricing'];
                }
            }

            $doctor_request->save();
        });

        return response()->json(['success' => true, 'message' => 'Pricing updated and logged.']);
    }

    public function updateRegistrationProfile(Request $request, DoctorRequest $doctor_request): JsonResponse
    {
        $payload = $request->all();
        $filtersOnly = filter_var($payload['filters_only'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $aboutOnly = filter_var($payload['about_only'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $servicesOnly = filter_var($payload['services_only'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $modesOnly = filter_var($payload['modes_only'] ?? false, FILTER_VALIDATE_BOOLEAN);

        if ($modesOnly) {
            return $this->updateRegistrationConsultationModes($request, $doctor_request);
        }

        if ($servicesOnly) {
            return $this->updateRegistrationServices($request, $doctor_request);
        }

        if ($filtersOnly) {
            return $this->updateRegistrationFilters($request, $doctor_request);
        }

        if ($aboutOnly) {
            return $this->updateRegistrationAboutOnly($request, $doctor_request);
        }

        $educationHistory = $this->normalizeProfileEducationRows($this->decodeJsonArrayProfile($payload['education_history'] ?? []));
        if (count($educationHistory) > 20) {
            return response()->json(['success' => false, 'message' => 'Too many education entries (max 20).'], 422);
        }
        foreach ($educationHistory as $i => $row) {
            if (mb_strlen($row['degree']) > 500 || mb_strlen($row['institution']) > 500) {
                return response()->json(['success' => false, 'message' => 'Education row '.($i + 1).': degree and institution may not exceed 500 characters each.'], 422);
            }
            $yc = $row['year_completed'];
            if ($yc !== null && mb_strlen($yc) > 32) {
                return response()->json(['success' => false, 'message' => 'Education row '.($i + 1).': year is too long.'], 422);
            }
            if ($row['degree'] === '' || $row['institution'] === '') {
                return response()->json(['success' => false, 'message' => 'Each education entry needs both degree and institution.'], 422);
            }
        }

        $experienceHistory = $this->normalizeProfileExperienceRows($this->decodeJsonArrayProfile($payload['experience_history'] ?? []));
        if (count($experienceHistory) > 30) {
            return response()->json(['success' => false, 'message' => 'Too many experience entries (max 30).'], 422);
        }
        foreach ($experienceHistory as $i => $row) {
            if (mb_strlen($row['title']) > 500 || mb_strlen($row['organization']) > 500) {
                return response()->json(['success' => false, 'message' => 'Experience row '.($i + 1).': title and organization may not exceed 500 characters each.'], 422);
            }
            if (mb_strlen($row['from_year']) > 32 || ($row['to_year'] !== null && mb_strlen($row['to_year']) > 32)) {
                return response()->json(['success' => false, 'message' => 'Experience row '.($i + 1).': year fields are too long.'], 422);
            }
            $details = $row['details'];
            if ($details !== null && mb_strlen($details) > 2000) {
                return response()->json(['success' => false, 'message' => 'Experience row '.($i + 1).': details may not exceed 2000 characters.'], 422);
            }
            if ($row['title'] === '' || $row['organization'] === '' || $row['from_year'] === '') {
                return response()->json(['success' => false, 'message' => 'Each experience entry needs title, organization and from year.'], 422);
            }
        }

        $specsRaw = $payload['specializations'] ?? [];
        if (! is_array($specsRaw)) {
            $specsRaw = [];
        }
        $specializations = array_values(array_unique(array_filter(array_map(static function ($s) {
            return trim((string) $s);
        }, $specsRaw), static fn ($s) => $s !== '')));
        if (count($specializations) > 50) {
            return response()->json(['success' => false, 'message' => 'Too many specializations (max 50).'], 422);
        }
        foreach ($specializations as $spec) {
            if (mb_strlen($spec) > 255) {
                return response()->json(['success' => false, 'message' => 'Each specialization may not exceed 255 characters.'], 422);
            }
        }

        $allowedSpecs = DoctorConsultationService::query()
            ->where('is_active', true)
            ->where('name', $doctor_request->job_title)
            ->value('specialization_options');
        $allowedSpecs = is_array($allowedSpecs) ? array_values(array_filter(array_map('strval', $allowedSpecs))) : [];
        if ($allowedSpecs !== []) {
            foreach ($specializations as $spec) {
                if (! in_array($spec, $allowedSpecs, true)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'One or more specializations are not allowed for this doctor’s consultation service.',
                        'errors' => ['specializations' => ['Pick values from the configured list for '.$doctor_request->job_title.'.']],
                    ], 422);
                }
            }
        }

        $this->sortEducationHistoryNewestFirstForProfile($educationHistory);
        $this->sortExperienceHistoryNewestFirstForProfile($experienceHistory);

        if (array_key_exists('about_text', $payload)) {
            $aboutRaw = $payload['about_text'];
            if (! is_string($aboutRaw)) {
                $aboutRaw = '';
            }
            $aboutText = trim($aboutRaw);
            if (mb_strlen($aboutText) > 8000) {
                return response()->json(['success' => false, 'message' => 'About may not exceed 8000 characters.'], 422);
            }
            $doctor_request->about_text = $aboutText !== '' ? $aboutText : null;
        }

        $doctor_request->education_history = $educationHistory;
        $doctor_request->experience_history = $experienceHistory;
        $doctor_request->specializations = $specializations;

        if ($request->has('gender') || $request->has('fluent_languages')) {
            $this->applyRegistrationGenderAndLanguages($request, $doctor_request);
        }

        if ($request->has('city')) {
            $this->applyRegistrationCity($request, $doctor_request);
        }

        $this->syncWebsiteCardSummaryFromStructuredProfile($doctor_request);

        $doctor_request->save();

        return response()->json([
            'success' => true,
            'message' => 'Profile updated. CareWeb consultation page will show these details after refresh.',
        ]);
    }

    public function generateRegistrationAbout(Request $request, DoctorRequest $doctor_request, DoctorRegistrationAboutAiService $aboutAi): JsonResponse
    {
        $request->validate([
            'about_details' => 'nullable|string|max:4000',
        ]);

        $result = $aboutAi->generateForAdminDoctor(
            $doctor_request,
            (string) $request->input('about_details', ''),
        );

        $status = $result['success'] ? 200 : (isset($result['ai_disabled']) ? 503 : 502);

        return response()->json($result, $status);
    }

    private function updateRegistrationAboutOnly(Request $request, DoctorRequest $doctor_request): JsonResponse
    {
        $aboutRaw = $request->input('about_text', '');
        if (! is_string($aboutRaw)) {
            $aboutRaw = '';
        }
        $aboutText = trim($aboutRaw);
        if (mb_strlen($aboutText) > 8000) {
            return response()->json(['success' => false, 'message' => 'About may not exceed 8000 characters.'], 422);
        }

        $doctor_request->about_text = $aboutText !== '' ? $aboutText : null;
        $this->syncWebsiteCardSummaryFromStructuredProfile($doctor_request);
        $doctor_request->save();

        return response()->json([
            'success' => true,
            'message' => 'About updated. CareWeb consultation page will show this text after refresh.',
            'about_text' => $doctor_request->about_text,
        ]);
    }

    public function updateRegistrationFilters(Request $request, DoctorRequest $doctor_request): JsonResponse
    {
        $this->applyRegistrationGenderAndLanguages($request, $doctor_request);
        $this->applyRegistrationCity($request, $doctor_request);
        $doctor_request->save();

        $message = 'Saved.';
        if ($request->has('gender') || $request->has('fluent_languages')) {
            $message = 'Gender and fluent languages saved. CareWeb doctor listing filters will use these values.';
        }
        if ($request->has('city')) {
            $message = 'City saved. CareWeb consultation page will show this city on the doctor card.';
        }
        if (($request->has('gender') || $request->has('fluent_languages')) && $request->has('city')) {
            $message = 'City, gender and fluent languages saved.';
        }

        return response()->json([
            'success' => true,
            'message' => $message,
        ]);
    }

    public function updateRegistrationConsultationModes(Request $request, DoctorRequest $doctor_request): JsonResponse
    {
        $allowedModes = ['online', 'home_visit', 'clinic_visit'];
        $request->validate([
            'consultation_modes' => ['required', 'array', 'min:1'],
            'consultation_modes.*' => [Rule::in($allowedModes)],
        ]);

        $modesRaw = $request->input('consultation_modes', []);
        if (! is_array($modesRaw)) {
            $modesRaw = [];
        }
        $modes = array_values(array_unique(array_filter(array_map(static fn ($m) => strtolower(trim((string) $m)), $modesRaw), static fn ($m) => in_array($m, $allowedModes, true))));

        if ($modes === []) {
            return response()->json([
                'success' => false,
                'message' => 'Select at least one consultation mode.',
                'errors' => ['consultation_modes' => ['Select at least one consultation mode.']],
            ], 422);
        }

        $doctor_request->consultation_modes = $modes;
        $doctor_request->save();

        return response()->json([
            'success' => true,
            'message' => 'Consultation modes updated.',
        ]);
    }

    public function updateRegistrationServices(Request $request, DoctorRequest $doctor_request): JsonResponse
    {
        $request->validate([
            'job_title' => ['required', 'string', 'max:255', Rule::exists('doctor_consultation_services', 'name')->where('is_active', true)],
        ]);

        $jobTitle = trim((string) $request->input('job_title'));
        $consultationSubServices = $this->decodeJsonArrayProfile($request->input('consultation_sub_services', []));
        $specializationsRaw = $request->input('specializations', []);
        if (! is_array($specializationsRaw)) {
            $specializationsRaw = [];
        }
        $specializations = array_values(array_unique(array_filter(array_map(static function ($s) {
            return trim((string) $s);
        }, $specializationsRaw), static fn ($s) => $s !== '')));

        $serviceRow = DoctorConsultationService::query()
            ->where('is_active', true)
            ->where('name', $jobTitle)
            ->with(['subServices' => fn ($q) => $q->where('is_active', true)])
            ->first();

        $tagsService = app(DoctorConsultationRegistrationTagsService::class);
        $tagValidation = $tagsService->validate($serviceRow, $specializations, $consultationSubServices, false);
        if ($tagValidation !== null) {
            return response()->json($tagValidation, 422);
        }

        $normalizedSubServices = $tagsService->normalizeSubServices($consultationSubServices);
        $specializations = $tagsService->flattenTags($normalizedSubServices, $specializations);

        $consultationPricingRaw = $this->decodeJsonArrayProfile($request->input('consultation_pricing', []));
        $pricingService = app(DoctorConsultationPricingService::class);
        $pricingNormalized = $pricingService->normalizeAdminPricing(
            $doctor_request,
            $serviceRow,
            $normalizedSubServices,
            $consultationPricingRaw
        );
        if (isset($pricingNormalized['error'])) {
            return response()->json([
                'success' => false,
                'message' => (string) $pricingNormalized['error'],
                'errors' => ['consultation_pricing' => [(string) $pricingNormalized['error']]],
            ], 422);
        }

        $doctor_request->job_title = $jobTitle;
        $doctor_request->consultation_sub_services = $normalizedSubServices !== [] ? $normalizedSubServices : null;
        $doctor_request->specializations = $specializations !== [] ? $specializations : null;
        if ($request->has('consultation_pricing')) {
            $doctor_request->consultation_pricing = $pricingNormalized['pricing'];
        }
        $doctor_request->save();

        return response()->json([
            'success' => true,
            'message' => 'Consultation service, sub-services, tags and doctor-specific charges saved. CareWeb will reflect these after refresh.',
        ]);
    }

    private function applyRegistrationCity(Request $request, DoctorRequest $doctor_request): void
    {
        if (! $request->has('city')) {
            return;
        }

        $request->validate([
            'city' => ['required', 'string', 'max:255', Rule::exists('locations', 'name')],
        ]);

        $city = trim((string) $request->input('city'));
        $doctor_request->city = $city;
        $doctor_request->location = $city;
    }

    private function applyRegistrationGenderAndLanguages(Request $request, DoctorRequest $doctor_request): void
    {
        if ($request->has('gender')) {
            $gender = strtolower(trim((string) $request->input('gender', '')));
            if ($gender === '') {
                $doctor_request->gender = null;
            } else {
                $request->validate([
                    'gender' => ['required', Rule::in(['male', 'female', 'other'])],
                ]);
                $doctor_request->gender = $gender;
            }
        }

        if ($request->has('fluent_languages')) {
            $allowed = array_keys(config('doctor_registration.fluent_languages', []));
            $request->validate([
                'fluent_languages' => ['nullable', 'array'],
                'fluent_languages.*' => [Rule::in($allowed)],
            ]);
            $raw = $request->input('fluent_languages', []);
            if (! is_array($raw)) {
                $raw = [];
            }
            $langs = array_values(array_unique(array_intersect(
                $allowed,
                array_map(static fn ($k) => strtolower(trim((string) $k)), $raw)
            )));
            $doctor_request->fluent_languages = $langs;
        }
    }

    /**
     * Admin bulk action: regenerate Zoom links for all online website bookings.
     */
    public function regenerateOnlineMeetingLinks(Request $request)
    {
        $request->validate([
            'scope' => 'nullable|in:all,missing',
        ]);
        $scope = (string) ($request->input('scope', 'all') ?: 'all');

        $query = ConsultationWebsiteBooking::query()
            ->where('consultation_mode', 'online')
            ->with(['doctorRequest:id,name,approval_status', 'consultationService:id,name']);
        if ($scope === 'missing') {
            $query->where(function ($q) {
                $q->whereNull('online_meeting_link')->orWhere('online_meeting_link', '');
            });
        }

        $processed = 0;
        $updated = 0;
        $failed = 0;
        $skipped = 0;

        $query->orderBy('id')->chunkById(100, function ($rows) use (&$processed, &$updated, &$failed, &$skipped) {
            foreach ($rows as $booking) {
                $processed++;
                $doctor = $booking->doctorRequest;
                if (! $doctor || strtolower((string) $doctor->approval_status) !== 'approved') {
                    $skipped++;
                    continue;
                }

                $startAt = $this->resolveMeetingStartAt($booking);
                $durationMinutes = max(1, (int) ($booking->consultation_duration_minutes ?? 30));
                if (! $startAt) {
                    $skipped++;
                    continue;
                }

                $meeting = $this->createZoomMeetingLinkForBooking($doctor, $booking, $startAt, $durationMinutes);
                if (! empty($meeting['link'])) {
                    $booking->online_meeting_provider = $meeting['provider'] ?? 'zoom';
                    $booking->online_meeting_link = $meeting['link'];
                    $booking->online_meeting_starts_at = $startAt;
                    $booking->online_meeting_ends_at = $startAt->copy()->addMinutes($durationMinutes);
                    $booking->save();
                    $updated++;
                } else {
                    $failed++;
                }
            }
        });

        return response()->json([
            'success' => true,
            'message' => "Processed {$processed} online booking(s): {$updated} updated, {$failed} failed, {$skipped} skipped.",
            'stats' => compact('processed', 'updated', 'failed', 'skipped'),
        ]);
    }

    private function resolveMeetingStartAt(ConsultationWebsiteBooking $booking): ?Carbon
    {
        $timezone = (string) config('app.timezone', 'Asia/Kolkata');
        if ($booking->online_meeting_starts_at) {
            return $booking->online_meeting_starts_at->copy()->timezone($timezone);
        }
        if (! $booking->appointment_date || ! $booking->appointment_start_time) {
            return null;
        }
        $date = $booking->appointment_date instanceof \DateTimeInterface
            ? $booking->appointment_date->format('Y-m-d')
            : (string) $booking->appointment_date;
        $time = trim((string) $booking->appointment_start_time);
        if (! preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $time)) {
            return null;
        }

        return Carbon::createFromFormat('Y-m-d H:i', $date.' '.$time, $timezone);
    }

    private function createZoomMeetingLinkForBooking(
        DoctorRequest $doctor,
        ConsultationWebsiteBooking $booking,
        Carbon $meetingStartsAt,
        int $durationMinutes
    ): array {
        $zoomClientId = trim((string) env('ZOOM_CLIENT_ID', ''));
        $zoomClientSecret = trim((string) env('ZOOM_CLIENT_SECRET', ''));
        $zoomAccountId = trim((string) env('ZOOM_ACCOUNT_ID', ''));
        $zoomUserId = trim((string) env('ZOOM_USER_ID', 'me'));
        if ($zoomClientId === '' || $zoomClientSecret === '' || $zoomAccountId === '') {
            Log::warning('Zoom env missing while regenerating meeting links');
            return ['provider' => null, 'link' => null];
        }

        try {
            $tokenRes = Http::asForm()
                ->withBasicAuth($zoomClientId, $zoomClientSecret)
                ->post('https://zoom.us/oauth/token', [
                    'grant_type' => 'account_credentials',
                    'account_id' => $zoomAccountId,
                ]);
            if (! $tokenRes->successful()) {
                Log::warning('Zoom token create failed during admin regenerate', [
                    'status' => $tokenRes->status(),
                    'body' => $tokenRes->body(),
                ]);
                return ['provider' => null, 'link' => null];
            }

            $token = (string) ($tokenRes->json('access_token') ?? '');
            if ($token === '') {
                return ['provider' => null, 'link' => null];
            }

            $topic = 'Carelix Consultation - '.trim((string) ($doctor->name ?? 'Doctor'));
            if ($booking->consultationService?->name) {
                $topic .= ' ('.$booking->consultationService->name.')';
            }
            $meetingRes = Http::withToken($token)
                ->post('https://api.zoom.us/v2/users/'.urlencode($zoomUserId).'/meetings', [
                    'topic' => $topic,
                    'type' => 2,
                    'start_time' => $meetingStartsAt->copy()->utc()->format('Y-m-d\TH:i:s\Z'),
                    'duration' => max(1, $durationMinutes),
                    'timezone' => (string) config('app.timezone', 'Asia/Kolkata'),
                    'settings' => [
                        'waiting_room' => true,
                        'join_before_host' => false,
                    ],
                ]);
            if (! $meetingRes->successful()) {
                Log::warning('Zoom meeting create failed during admin regenerate', [
                    'booking_id' => $booking->id,
                    'status' => $meetingRes->status(),
                    'body' => $meetingRes->body(),
                ]);
                return ['provider' => null, 'link' => null];
            }

            $joinUrl = trim((string) ($meetingRes->json('join_url') ?? ''));
            if ($joinUrl === '') {
                return ['provider' => null, 'link' => null];
            }

            return ['provider' => 'zoom', 'link' => $joinUrl];
        } catch (\Throwable $e) {
            Log::warning('Zoom regenerate exception', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
            ]);
            return ['provider' => null, 'link' => null];
        }
    }

    public function websiteReviewsPanel(DoctorRequest $doctor_request): \Illuminate\Contracts\View\View
    {
        $doctor_request->load(['websiteProfileReviews']);

        return view('admin.doctor_requests.partials.website_reviews_manage', [
            'doctor' => $doctor_request,
        ]);
    }

    public function websiteReviewsStore(Request $request, DoctorRequest $doctor_request): JsonResponse
    {
        $validated = $request->validate([
            'reviewer_display_name' => ['required', 'string', 'max:255'],
            'rating' => ['required', 'numeric', 'min:1', 'max:5'],
            'body' => ['required', 'string', 'min:5', 'max:8000'],
            'consultation_mode' => ['required', Rule::in(['online', 'home_visit', 'clinic_visit'])],
            'reviewed_on' => ['nullable', 'date'],
            'reviewer_photo' => ['nullable', 'file', 'image', 'max:4096'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:99999'],
        ]);

        $path = null;
        if ($request->hasFile('reviewer_photo')) {
            $path = $request->file('reviewer_photo')->store('documents/website-profile-reviews/'.$doctor_request->id, 'public');
        }

        DoctorWebsiteProfileReview::create([
            'doctor_request_id' => $doctor_request->id,
            'reviewer_display_name' => $validated['reviewer_display_name'],
            'reviewer_photo_path' => $path,
            'rating' => round((float) $validated['rating'], 1),
            'body' => $validated['body'],
            'consultation_mode' => $validated['consultation_mode'],
            'reviewed_on' => $validated['reviewed_on'] ?? null,
            'sort_order' => (int) ($validated['sort_order'] ?? 0),
        ]);

        $doctor_request->load(['websiteProfileReviews']);

        return response()->json([
            'success' => true,
            'message' => 'Review saved.',
            'html' => view('admin.doctor_requests.partials.website_reviews_manage', [
                'doctor' => $doctor_request,
            ])->render(),
        ]);
    }

    public function websiteReviewsDestroy(DoctorRequest $doctor_request, DoctorWebsiteProfileReview $review): JsonResponse
    {
        if ((int) $review->doctor_request_id !== (int) $doctor_request->id) {
            abort(404);
        }
        $review->deleteReviewerPhotoIfPresent();
        $review->delete();

        $doctor_request->load(['websiteProfileReviews']);

        return response()->json([
            'success' => true,
            'message' => 'Review removed.',
            'html' => view('admin.doctor_requests.partials.website_reviews_manage', [
                'doctor' => $doctor_request,
            ])->render(),
        ]);
    }

    /** @return array<int, mixed> */
    private function decodeJsonArrayProfile(mixed $raw): array
    {
        if (is_array($raw)) {
            return $raw;
        }
        if (is_string($raw)) {
            $decoded = json_decode($raw, true);

            return is_array($decoded) ? $decoded : [];
        }

        return [];
    }

    /**
     * @param  array<int, mixed>  $rows
     * @return array<int, array{degree: string, institution: string, year_completed: ?string}>
     */
    private function normalizeProfileEducationRows(array $rows): array
    {
        $out = [];
        foreach ($rows as $r) {
            if (! is_array($r)) {
                continue;
            }
            $degree = trim((string) ($r['degree'] ?? ''));
            $inst = trim((string) ($r['institution'] ?? ''));
            $year = trim((string) ($r['year_completed'] ?? ''));
            if ($degree === '' && $inst === '' && $year === '') {
                continue;
            }
            $out[] = [
                'degree' => $degree,
                'institution' => $inst,
                'year_completed' => $year !== '' ? $year : null,
            ];
        }

        return $out;
    }

    /**
     * @param  array<int, mixed>  $rows
     * @return array<int, array{title: string, organization: string, from_year: string, to_year: ?string, details: ?string}>
     */
    private function normalizeProfileExperienceRows(array $rows): array
    {
        $out = [];
        foreach ($rows as $r) {
            if (! is_array($r)) {
                continue;
            }
            $title = trim((string) ($r['title'] ?? ''));
            $org = trim((string) ($r['organization'] ?? ''));
            $from = trim((string) ($r['from_year'] ?? ''));
            $to = trim((string) ($r['to_year'] ?? ''));
            $details = trim((string) ($r['details'] ?? ''));
            if ($title === '' && $org === '' && $from === '' && $to === '' && $details === '') {
                continue;
            }
            $out[] = [
                'title' => $title,
                'organization' => $org,
                'from_year' => $from,
                'to_year' => $to !== '' ? $to : null,
                'details' => $details !== '' ? $details : null,
            ];
        }

        return $out;
    }

    /** @param  array<int, array{degree: string, institution: string, year_completed: ?string}>  $rows */
    private function sortEducationHistoryNewestFirstForProfile(array &$rows): void
    {
        usort($rows, function ($a, $b) {
            $ya = (int) preg_replace('/\D/', '', (string) ($a['year_completed'] ?? ''));
            $yb = (int) preg_replace('/\D/', '', (string) ($b['year_completed'] ?? ''));

            return $yb <=> $ya;
        });
    }

    /** @param  array<int, array{title: string, organization: string, from_year: string, to_year: ?string, details: ?string}>  $rows */
    private function sortExperienceHistoryNewestFirstForProfile(array &$rows): void
    {
        usort($rows, function ($a, $b) {
            $ya = (int) preg_replace('/\D/', '', (string) ($a['from_year'] ?? ''));
            $yb = (int) preg_replace('/\D/', '', (string) ($b['from_year'] ?? ''));

            return $yb <=> $ya;
        });
    }

    private function syncWebsiteCardSummaryFromStructuredProfile(DoctorRequest $doctor): void
    {
        $edu = $doctor->education_history ?? [];
        $quals = [];
        if (is_array($edu)) {
            foreach ($edu as $row) {
                $d = trim((string) ($row['degree'] ?? ''));
                if ($d !== '') {
                    $quals[] = $d;
                }
            }
        }
        if ($quals !== []) {
            $doctor->website_card_qualification = implode(' · ', array_slice($quals, 0, 8));
        } else {
            $doctor->website_card_qualification = null;
        }

        $exp = $doctor->experience_history ?? [];
        if (is_array($exp) && count($exp) > 0) {
            $first = $exp[0];
            $title = trim((string) ($first['title'] ?? ''));
            $org = trim((string) ($first['organization'] ?? ''));
            $from = trim((string) ($first['from_year'] ?? ''));
            $toRaw = $first['to_year'] ?? null;
            $to = is_string($toRaw) ? trim($toRaw) : ($toRaw !== null && $toRaw !== '' ? (string) $toRaw : '');
            $toLabel = $to !== '' ? $to : 'Present';
            $head = $title !== '' && $org !== '' ? $title.' at '.$org : ($title !== '' ? $title : $org);
            $yr = $from !== '' ? '('.$from.' – '.$toLabel.')' : '';
            $line = trim($head.' '.$yr);
            $doctor->website_card_experience = $line !== '' ? $line : null;
        } else {
            $doctor->website_card_experience = null;
        }
    }

    public function priceChangeRequests(DoctorRequest $doctor_request): JsonResponse
    {
        $requests = $doctor_request->priceChangeRequests()
            ->where('status', DoctorConsultationPriceChangeRequest::STATUS_PENDING)
            ->orderByDesc('created_at')
            ->get()
            ->map(function (DoctorConsultationPriceChangeRequest $req) {
                return [
                    'id' => $req->id,
                    'service_name' => $req->service_name,
                    'sub_service_id' => (int) $req->sub_service_id,
                    'sub_service_name' => $req->sub_service_name,
                    'consultation_mode' => $req->consultation_mode,
                    'mode_label' => $req->modeLabel(),
                    'current_price' => $req->current_price,
                    'requested_price' => $req->requested_price,
                    'created_at' => $req->created_at?->format('d M Y, H:i'),
                ];
            });

        return response()->json([
            'success' => true,
            'doctor' => [
                'id' => $doctor_request->id,
                'name' => $doctor_request->name,
                'job_title' => $doctor_request->job_title,
            ],
            'requests' => $requests,
        ]);
    }

    public function approvePriceChangeRequest(DoctorConsultationPriceChangeRequest $price_change_request): JsonResponse
    {
        if ((int) $price_change_request->doctor_request_id <= 0) {
            return response()->json(['success' => false, 'message' => 'Invalid request.'], 404);
        }

        app(DoctorConsultationPriceChangeService::class)->approve($price_change_request, Auth::id());

        return response()->json([
            'success' => true,
            'message' => 'Price change approved. Doctor profile and CareWeb listing updated.',
        ]);
    }

    public function rejectPriceChangeRequest(Request $request, DoctorConsultationPriceChangeRequest $price_change_request): JsonResponse
    {
        $request->validate([
            'admin_note' => ['required', 'string', 'max:2000'],
        ]);

        app(DoctorConsultationPriceChangeService::class)->reject(
            $price_change_request,
            (string) $request->input('admin_note'),
            Auth::id()
        );

        return response()->json([
            'success' => true,
            'message' => 'Price change request rejected. Doctor will see your note.',
        ]);
    }

    /**
     * Bulk-set doctor-portal "Refer Lead" commission % for selected doctors.
     */
    public function updatePortalLeadCommission(Request $request)
    {
        $data = $request->validate([
            'doctor_ids' => ['required', 'array', 'min:1'],
            'doctor_ids.*' => ['integer', 'exists:doctor_requests,id'],
            'commission_percent' => ['required', 'numeric', 'min:0', 'max:100'],
        ]);

        $percent = round((float) $data['commission_percent'], 2);
        $ids = array_values(array_unique(array_map('intval', $data['doctor_ids'])));

        $updated = DoctorRequest::query()
            ->whereIn('id', $ids)
            ->update(['portal_lead_commission_percent' => $percent]);

        return redirect()
            ->route('admin.doctor_requests.index')
            ->with('status', [
                'alert_type' => 'success',
                'message' => "Portal lead commission set to {$percent}% for {$updated} doctor(s).",
            ]);
    }
}
