<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DoctorConsultationService;
use App\Models\DoctorRequest;
use App\Models\User;
use App\Services\ConsultationAddressSearchService;
use App\Services\ConsultationDoctorProximityService;
use App\Services\ConsultationLocationPricingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class PublicConsultationWebsiteController extends Controller
{
    /**
     * Approved doctors for website booking, matched by job_title === consultation service name (same rules as legacy careweb SQL).
     */
    public function googleMapsConfig(): JsonResponse
    {
        $search = app(ConsultationAddressSearchService::class);

        $health = Cache::remember('consult_address_search_health_v2', 600, static function () use ($search) {
            return $search->healthCheck();
        });

        return response()->json([
            'success' => true,
            'address_search_enabled' => $health['ok'],
            'provider' => $health['provider'],
            'message' => $health['message'] !== '' ? $health['message'] : null,
        ]);
    }

    public function addressAutocomplete(Request $request, ConsultationAddressSearchService $search): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));
        $city = trim((string) $request->query('city', ''));

        $predictions = $search->autocomplete($q, $city !== '' ? $city : null);

        return response()->json([
            'success' => true,
            'predictions' => $predictions,
        ]);
    }

    public function addressResolve(Request $request, ConsultationAddressSearchService $search): JsonResponse
    {
        $placeId = trim((string) $request->query('place_id', ''));
        $address = trim((string) $request->query('address', ''));
        $city = trim((string) $request->query('city', ''));

        $resolved = null;
        if ($placeId !== '') {
            $resolved = $search->resolvePlace($placeId, $city !== '' ? $city : null);
        } elseif ($address !== '') {
            $resolved = $search->geocode($address, $city !== '' ? $city : null);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'place_id or address is required.',
            ], 422);
        }

        if ($resolved === null || ($resolved['address'] ?? '') === '') {
            $err = $search->lastError();
            $message = $err !== null && $err !== ''
                ? $err
                : 'Address not found.';

            return response()->json([
                'success' => false,
                'message' => $message,
            ], 404);
        }

        return response()->json([
            'success' => true,
            'address' => $resolved['address'],
            'lat' => $resolved['lat'],
            'lng' => $resolved['lng'],
        ]);
    }

    public function reverseGeocode(Request $request, ConsultationAddressSearchService $search): JsonResponse
    {
        $lat = $request->query('lat');
        $lng = $request->query('lng');
        $city = trim((string) $request->query('city', ''));

        if (! is_numeric($lat) || ! is_numeric($lng)) {
            return response()->json([
                'success' => false,
                'message' => 'lat and lng are required.',
            ], 422);
        }

        $resolved = $search->reverseGeocode((float) $lat, (float) $lng, $city !== '' ? $city : null);
        if ($resolved === null || ($resolved['address'] ?? '') === '') {
            $err = $search->lastError();
            $message = $err !== null && $err !== ''
                ? $err
                : ($city !== ''
                    ? 'GPS location is outside '.$city.'. Please type an address in '.$city.'.'
                    : 'Could not resolve address from GPS.');

            return response()->json([
                'success' => false,
                'message' => $message,
            ], 404);
        }

        return response()->json([
            'success' => true,
            'address' => $resolved['address'],
            'lat' => $resolved['lat'],
            'lng' => $resolved['lng'],
        ]);
    }

    public function doctors(Request $request, int $serviceId, ConsultationLocationPricingService $locationPricing): JsonResponse
    {
        $service = DoctorConsultationService::query()
            ->where('id', $serviceId)
            ->where('is_active', true)
            ->with(['subServices' => fn ($q) => $q->where('is_active', true)])
            ->first(['id', 'name', 'consultation_duration_minutes', 'sort_order', 'specialization_options']);

        if (! $service || trim((string) $service->name) === '') {
            return response()->json([
                'success' => false,
                'message' => 'Consultation service not found.',
            ], 404);
        }

        $svcName = trim((string) $service->name);
        $activeSubIds = $service->subServices->pluck('id')->map(fn ($id) => (int) $id)->all();
        $hasSubServices = count($activeSubIds) > 0;

        $subServiceId = (int) $request->query('sub_service_id', 0);
        if ($hasSubServices) {
            if ($subServiceId <= 0 || ! in_array($subServiceId, $activeSubIds, true)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Please select a valid sub-service.',
                ], 422);
            }
        } else {
            $subServiceId = 0;
        }

        $doctorLocation = trim((string) $request->query('doctor_location', ''));
        $consultationMode = strtolower(trim((string) $request->query('mode', '')));
        $requiredTags = $this->parseDoctorTagsQuery($request->query('doctor_tags'));

        $doctors = DoctorRequest::query()
            ->whereRaw('LOWER(TRIM(approval_status)) = ?', ['approved'])
            ->whereRaw('LOWER(TRIM(COALESCE(job_title, \'\'))) = ?', [mb_strtolower($svcName)])
            ->with(['websiteProfileReviews' => function ($q) {
                $q->orderBy('sort_order')->orderByDesc('id');
            }])
            ->orderByDesc('reviewed_at')
            ->orderByDesc('id')
            ->get([
                'id', 'lead_id', 'name', 'mobile', 'contact_no', 'job_title', 'city', 'location',
                'profile_image', 'profile_image_status', 'reviewed_at', 'total_experience',                 'consultation_modes',
                'consultation_sub_services',
                'consultation_pricing',
                'online_charges', 'home_visit_charges', 'clinic_consultation_charges',
                'website_customer_fee_online', 'website_customer_fee_home_visit', 'website_customer_fee_clinic',
                'website_card_rating', 'website_card_attend', 'website_card_qualification',
                'website_card_experience', 'website_card_modal_description', 'website_card_book_url',
                'about_text', 'education_history', 'experience_history', 'specializations',
                'gender', 'fluent_languages', 'age',
                'base_location_address', 'base_location_lat', 'base_location_lng', 'coverage_radius_km',
                'clinic_name', 'clinic_address', 'clinic_lat', 'clinic_lng',
            ]);

        $doctors = $doctors->filter(function (DoctorRequest $doctor) use (
            $hasSubServices,
            $subServiceId,
            $doctorLocation,
            $consultationMode,
            $requiredTags
        ) {
            if ($hasSubServices && ! $this->doctorHasSubService($doctor, $subServiceId)) {
                return false;
            }
            if ($doctorLocation !== '' && ! $this->doctorMatchesCity($doctor, $doctorLocation)) {
                return false;
            }
            if ($consultationMode !== '' && ! $this->doctorSupportsMode($doctor, $consultationMode)) {
                return false;
            }
            if ($requiredTags !== [] && ! $this->doctorMatchesTags($doctor, $requiredTags, $hasSubServices ? $subServiceId : null)) {
                return false;
            }

            return true;
        })->values();

        $locationFees = $doctorLocation !== ''
            ? $locationPricing->websitePricesFor($doctorLocation, $serviceId, $subServiceId > 0 ? $subServiceId : null)
            : [
                'online' => null,
                'home_visit' => null,
                'clinic_visit' => null,
            ];

        $patientLat = $request->query('lat');
        $patientLng = $request->query('lng');
        $radiusKm = (float) $request->query('radius_km', ConsultationDoctorProximityService::DEFAULT_RADIUS_KM);
        if ($radiusKm <= 0 || $radiusKm > 50) {
            $radiusKm = ConsultationDoctorProximityService::DEFAULT_RADIUS_KM;
        }

        $proximityApplied = false;
        $doctorPayloads = [];

        if (in_array($consultationMode, ['home_visit', 'clinic_visit'], true)
            && is_numeric($patientLat) && is_numeric($patientLng)) {
            $proximityApplied = true;
            $matches = ConsultationDoctorProximityService::filterWithinRadius(
                $doctors,
                $consultationMode,
                (float) $patientLat,
                (float) $patientLng,
                $radiusKm
            );
            foreach ($matches as $match) {
                $doctorPayloads[] = $this->formatDoctorForWebsite($match['doctor'], $match['distance_km'], $locationFees, $subServiceId > 0 ? $subServiceId : null);
            }
        } else {
            foreach ($doctors as $doctor) {
                $doctorPayloads[] = $this->formatDoctorForWebsite($doctor, null, $locationFees, $subServiceId > 0 ? $subServiceId : null);
            }
        }

        return response()->json([
            'success' => true,
            'service' => [
                'id' => $service->id,
                'name' => $service->name,
                'consultation_duration_minutes' => $service->consultation_duration_minutes,
                'sort_order' => $service->sort_order,
                'has_sub_services' => $hasSubServices,
            ],
            'filters' => [
                'sub_service_id' => $subServiceId > 0 ? $subServiceId : null,
                'doctor_location' => $doctorLocation !== '' ? $doctorLocation : null,
                'mode' => $consultationMode !== '' ? $consultationMode : null,
                'doctor_tags' => $requiredTags,
            ],
            'location_pricing' => [
                'location' => $doctorLocation !== '' ? $doctorLocation : null,
                'sub_service_id' => $subServiceId > 0 ? $subServiceId : null,
                'fees' => $locationFees,
            ],
            'doctors' => $doctorPayloads,
            'proximity_filter' => [
                'applied' => $proximityApplied,
                'mode' => $proximityApplied ? $consultationMode : null,
                'radius_km' => $proximityApplied ? $radiusKm : null,
            ],
        ]);
    }

    /**
     * @return list<string>
     */
    private function parseDoctorTagsQuery(mixed $raw): array
    {
        if ($raw === null || $raw === '') {
            return [];
        }
        $parts = is_array($raw)
            ? $raw
            : (preg_split('/[|,]/', (string) $raw) ?: []);
        $out = [];
        foreach ($parts as $part) {
            $tag = trim((string) $part);
            if ($tag !== '') {
                $out[] = $tag;
            }
        }

        return array_values(array_unique($out));
    }

    private function doctorHasSubService(DoctorRequest $doctor, int $subServiceId): bool
    {
        $entries = is_array($doctor->consultation_sub_services) ? $doctor->consultation_sub_services : [];
        foreach ($entries as $entry) {
            if (! is_array($entry)) {
                continue;
            }
            if ((int) ($entry['sub_service_id'] ?? 0) === $subServiceId) {
                return true;
            }
        }

        return false;
    }

    private function doctorMatchesCity(DoctorRequest $doctor, string $locationName): bool
    {
        $needle = mb_strtolower(trim($locationName));
        if ($needle === '') {
            return true;
        }
        foreach (['city', 'location'] as $field) {
            $val = mb_strtolower(trim((string) ($doctor->{$field} ?? '')));
            if ($val === '') {
                continue;
            }
            if ($val === $needle || str_contains($val, $needle) || str_contains($needle, $val)) {
                return true;
            }
        }

        return false;
    }

    private function doctorSupportsMode(DoctorRequest $doctor, string $mode): bool
    {
        $mode = strtolower(trim($mode));
        if (! in_array($mode, ['online', 'home_visit', 'clinic_visit'], true)) {
            return true;
        }
        $modes = $doctor->consultation_modes;
        if (is_string($modes) && $modes !== '') {
            $modes = json_decode($modes, true);
        }
        if (! is_array($modes)) {
            return false;
        }
        foreach ($modes as $m) {
            if (strtolower(trim((string) $m)) === $mode) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  list<string>  $requiredTags
     */
    private function doctorMatchesTags(DoctorRequest $doctor, array $requiredTags, ?int $subServiceId): bool
    {
        if ($requiredTags === []) {
            return true;
        }

        $haystack = $this->doctorTagHaystack($doctor, $subServiceId);
        $hayJoin = ' '.implode(' ', $haystack).' ';

        foreach ($requiredTags as $tag) {
            $needle = $this->normalizeTag($tag);
            if ($needle === '') {
                continue;
            }
            $found = false;
            foreach ($haystack as $h) {
                if ($h === $needle || str_contains($h, $needle) || str_contains($needle, $h)) {
                    $found = true;
                    break;
                }
            }
            if (! $found && ! str_contains($hayJoin, ' '.$needle.' ')) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return list<string>
     */
    private function doctorTagHaystack(DoctorRequest $doctor, ?int $subServiceId): array
    {
        $hay = [];

        if ($subServiceId !== null && $subServiceId > 0) {
            $entries = is_array($doctor->consultation_sub_services) ? $doctor->consultation_sub_services : [];
            foreach ($entries as $entry) {
                if (! is_array($entry) || (int) ($entry['sub_service_id'] ?? 0) !== $subServiceId) {
                    continue;
                }
                $tags = $entry['tags'] ?? [];
                if (is_array($tags)) {
                    foreach ($tags as $tag) {
                        $n = $this->normalizeTag((string) $tag);
                        if ($n !== '') {
                            $hay[] = $n;
                        }
                    }
                }
            }
        }

        $specs = $doctor->specializations;
        if (is_string($specs) && $specs !== '') {
            $specs = json_decode($specs, true);
        }
        if (is_array($specs)) {
            foreach ($specs as $spec) {
                $n = $this->normalizeTag(is_array($spec) ? (string) ($spec['name'] ?? $spec['label'] ?? '') : (string) $spec);
                if ($n !== '') {
                    $hay[] = $n;
                }
            }
        }

        $about = $this->normalizeTag((string) ($doctor->about_text ?? ''));
        if ($about !== '') {
            $hay[] = $about;
        }

        return array_values(array_unique($hay));
    }

    private function normalizeTag(string $value): string
    {
        $value = mb_strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9\s-]/', '', $value) ?? '';

        return trim(preg_replace('/\s+/', ' ', $value) ?? '');
    }

    /**
     * @param  array{online: ?float, home_visit: ?float, clinic_visit: ?float}  $locationFees
     * @return array<string, mixed>
     */
    private function formatDoctorForWebsite(DoctorRequest $d, ?float $distanceKm, array $locationFees = [], ?int $subServiceId = null): array
    {
        $pricing = app(ConsultationLocationPricingService::class);
        $feeOnline = $pricing->patientBookingFeeFromLocationFees($locationFees, $d, 'online', $subServiceId);
        $feeHome = $pricing->patientBookingFeeFromLocationFees($locationFees, $d, 'home_visit', $subServiceId);
        $feeClinic = $pricing->patientBookingFeeFromLocationFees($locationFees, $d, 'clinic_visit', $subServiceId);

        return [
            'id' => $d->id,
            'lead_id' => $d->lead_id,
            'name' => $d->name,
            'mobile' => $d->mobile,
            'contact_no' => $d->contact_no,
            'job_title' => $d->job_title,
            'city' => $d->city,
            'location' => $d->location,
            'profile_image' => $d->profile_image_status === 'approved' ? $d->profile_image : null,
            'reviewed_at' => $d->reviewed_at?->toIso8601String(),
            'total_experience' => $d->total_experience,
            'consultation_modes' => $d->consultation_modes,
            'consultation_sub_services' => $d->consultation_sub_services,
            'online_charges' => $d->online_charges,
            'home_visit_charges' => $d->home_visit_charges,
            'clinic_consultation_charges' => $d->clinic_consultation_charges,
            'website_customer_fee_online' => $d->website_customer_fee_online,
            'website_customer_fee_home_visit' => $d->website_customer_fee_home_visit,
            'website_customer_fee_clinic' => $d->website_customer_fee_clinic,
            'website_booking_fee_online' => $feeOnline,
            'website_booking_fee_home_visit' => $feeHome,
            'website_booking_fee_clinic_visit' => $feeClinic,
            'website_card_rating' => $d->website_card_rating,
            'website_card_attend' => $d->website_card_attend,
            'website_card_qualification' => $d->website_card_qualification,
            'website_card_experience' => $d->website_card_experience,
            'website_card_modal_description' => $d->website_card_modal_description,
            'website_card_book_url' => $d->website_card_book_url,
            'about_text' => $d->about_text,
            'education_history' => $d->education_history,
            'experience_history' => $d->experience_history,
            'specializations' => $d->specializations,
            'gender' => $d->gender,
            'fluent_languages' => $d->fluent_languages,
            'age' => $d->age,
            'clinic_name' => $d->clinic_name,
            'clinic_address' => $d->clinic_address,
            'distance_km' => $distanceKm,
            'website_profile_reviews' => $d->websiteProfileReviews->map(function ($r) {
                return [
                    'reviewer_name' => $r->reviewer_display_name,
                    'rating' => (float) $r->rating,
                    'body' => $r->body,
                    'consultation_mode' => $r->consultation_mode,
                    'photo_url' => $r->reviewerPhotoPublicUrl(),
                    'reviewed_on' => $r->reviewed_on?->format('Y-m-d'),
                ];
            })->values()->all(),
        ];
    }

    /**
     * CareCRM admin locations for CareWeb consultation city filters (legacy endpoint).
     */
    public function doctorCities(): JsonResponse
    {
        $list = \App\Models\Location::query()
            ->orderBy('name')
            ->pluck('name')
            ->map(fn ($name) => trim((string) $name))
            ->filter(fn ($name) => $name !== '')
            ->values()
            ->all();

        return response()->json([
            'success' => true,
            'cities' => $list,
        ]);
    }

    /**
     * Lightweight index for CareWeb consultation hero search (services, tags, doctor names).
     */
    public function searchIndex(): JsonResponse
    {
        $services = DoctorConsultationService::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'specialization_options']);

        $serviceIdByJobTitle = [];
        foreach ($services as $service) {
            $key = mb_strtolower(trim((string) $service->name));
            if ($key !== '') {
                $serviceIdByJobTitle[$key] = (int) $service->id;
            }
        }

        $doctors = DoctorRequest::query()
            ->whereRaw('LOWER(TRIM(approval_status)) = ?', ['approved'])
            ->whereNotNull('job_title')
            ->whereRaw('TRIM(job_title) <> ?', [''])
            ->orderByDesc('reviewed_at')
            ->orderByDesc('id')
            ->get(['id', 'name', 'job_title'])
            ->map(function (DoctorRequest $d) use ($serviceIdByJobTitle) {
                $jobKey = mb_strtolower(trim((string) $d->job_title));
                $serviceId = $serviceIdByJobTitle[$jobKey] ?? null;
                if ($serviceId === null) {
                    return null;
                }

                return [
                    'id' => (int) $d->id,
                    'name' => (string) ($d->name ?? ''),
                    'service_id' => $serviceId,
                    'service_name' => (string) $d->job_title,
                ];
            })
            ->filter()
            ->values();

        return response()->json([
            'success' => true,
            'services' => $services->map(function (DoctorConsultationService $s) {
                $tags = $s->specialization_options;
                if (! is_array($tags)) {
                    $tags = [];
                }

                return [
                    'id' => (int) $s->id,
                    'name' => (string) $s->name,
                    'tags' => array_values(array_filter(array_map('trim', $tags))),
                ];
            })->values(),
            'doctors' => $doctors,
        ]);
    }

    /**
     * Operation executive display name + IVR call link for CareWeb “Call RM” (role id 4).
     * Personal mobile is never returned — calls go through CONSULTATION_WEBSITE_IVR_PHONE.
     */
    public function operationExecContact(): JsonResponse
    {
        $telHref = $this->consultationWebsiteIvrTelHref();
        $name = '';

        $phoneColumn = null;
        foreach (['contact_no', 'mobile', 'phone', 'phone_number'] as $candidate) {
            if (Schema::hasColumn('users', $candidate)) {
                $phoneColumn = $candidate;
                break;
            }
        }

        if ($phoneColumn !== null) {
            $user = User::query()
                ->where(function ($q) {
                    $q->where('role_id', '4')
                        ->orWhereRaw('FIND_IN_SET(?, role_id)', ['4']);
                })
                ->whereNotNull($phoneColumn)
                ->whereRaw('TRIM(`'.$phoneColumn.'`) <> \'\'')
                ->inRandomOrder()
                ->first(['f_name', 'l_name']);

            if ($user) {
                $name = trim((string) (($user->f_name ?? '').' '.($user->l_name ?? '')));
            }
        }

        return response()->json([
            'success' => true,
            'name' => $name,
            'phone' => '',
            'tel_href' => $telHref,
        ]);
    }

    private function consultationWebsiteIvrTelHref(): string
    {
        $raw = trim((string) config('services.consultation.website_ivr_phone', '7666426664'));
        $digits = preg_replace('/\D+/', '', $raw);
        if ($digits === '') {
            return '';
        }
        if (strlen($digits) === 10) {
            $digits = '91'.$digits;
        }

        return 'tel:+'.$digits;
    }
}
