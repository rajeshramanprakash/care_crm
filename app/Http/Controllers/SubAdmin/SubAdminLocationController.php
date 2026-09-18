<?php

namespace App\Http\Controllers\SubAdmin;

use App\Exports\LocationBulkTemplateExport;
use App\Http\Controllers\Controller;
use App\Imports\LocationBulkImport;
use App\Models\DoctorConsultationService;
use App\Models\Location;
use App\Models\LocationDoctorConsultationPrice;
use App\Models\Service;
use App\Models\ServiceSubService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;

class SubAdminLocationController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:view_locations');
    }

    public function index()
    {
        $locations = Location::with('services')->latest()->get();
        $subServiceNames = ServiceSubService::query()->pluck('name', 'id');

        return view('subadmin.locations.index', compact('locations', 'subServiceNames'));
    }

    public function create()
    {
        $services = $this->servicesForProviderPricing();
        $indianStates = $this->indianStates();
        $doctorConsultationServices = $this->doctorConsultationServicesForPricing();
        $doctorPricingBlocks = $this->doctorPricingBlocksFromOldInput();
        $tierOptions = $this->tierOptions();
        $vendorServiceRows = $this->providerServiceRowsFromOldInput('vendor_services');
        $freelancerServiceRows = $this->providerServiceRowsFromOldInput('freelancer_services');

        return view('subadmin.locations.create', compact(
            'services',
            'indianStates',
            'tierOptions',
            'doctorConsultationServices',
            'doctorPricingBlocks',
            'vendorServiceRows',
            'freelancerServiceRows'
        ));
    }

    public function store(Request $request)
    {
        $request->validate(array_merge([
            'name' => 'required|string|max:255|unique:locations',
            'state' => ['required', 'string', 'max:100', Rule::in($this->indianStates())],
            'tier' => ['required', 'string', 'max:50', Rule::in($this->tierOptions())],
        ], $this->providerServicesValidationRules(), $this->doctorPricingValidationRules()));

        $location = Location::create($request->only('name', 'state', 'tier'));

        $this->syncDoctorConsultationPrices($location, $request);
        $this->syncProviderServices($location, $request, 'vendor', 'vendor_services');
        $this->syncProviderServices($location, $request, 'freelancer', 'freelancer_services');

        return redirect()->route('admin.locations.index')
            ->with('success', 'Location created successfully.');
    }

    public function show(string $id)
    {
        //
    }

    public function edit(Location $location)
    {
        $services = $this->servicesForProviderPricing();
        $location->load(['services', 'doctorConsultationPrices']);
        $indianStates = $this->indianStates();
        $doctorConsultationServices = $this->doctorConsultationServicesForPricing();
        $doctorPricingBlocks = $this->doctorPricingBlocksFromOldInput()
            ?: $this->doctorPricingBlocksFromLocation($location);
        $tierOptions = $this->tierOptions();
        $vendorServiceRows = $this->providerServiceRowsFromOldInput('vendor_services')
            ?: $this->providerServiceRowsFromLocation($location, 'vendor');
        $freelancerServiceRows = $this->providerServiceRowsFromOldInput('freelancer_services')
            ?: $this->providerServiceRowsFromLocation($location, 'freelancer');

        return view('subadmin.locations.edit', compact(
            'location',
            'services',
            'indianStates',
            'tierOptions',
            'doctorConsultationServices',
            'doctorPricingBlocks',
            'vendorServiceRows',
            'freelancerServiceRows'
        ));
    }

    public function update(Request $request, Location $location)
    {
        $request->validate(array_merge([
            'name' => 'required|string|max:255|unique:locations,name,' . $location->id,
            'state' => ['required', 'string', 'max:100', Rule::in($this->indianStates())],
            'tier' => ['required', 'string', 'max:50', Rule::in($this->tierOptions())],
        ], $this->providerServicesValidationRules(), $this->doctorPricingValidationRules()));

        $location->update($request->only('name', 'state', 'tier'));

        $this->syncDoctorConsultationPrices($location, $request);
        $this->syncProviderServices($location, $request, 'vendor', 'vendor_services');
        $this->syncProviderServices($location, $request, 'freelancer', 'freelancer_services');

        return redirect()->route('admin.locations.index')
            ->with('success', 'Location updated successfully.');
    }

    public function destroy(Location $location)
    {
        $location->delete();

        return redirect()->route('admin.locations.index')
            ->with('success', 'Location deleted successfully.');
    }

    public function downloadBulkTemplate()
    {
        return Excel::download(
            new LocationBulkTemplateExport(),
            'locations_bulk_upload_template.xlsx'
        );
    }

    public function bulkImport(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls|max:10240',
        ]);

        try {
            $import = new LocationBulkImport();
            Excel::import($import, $request->file('file'));
        } catch (\Throwable $e) {
            report($e);

            $message = 'Bulk upload failed. ';
            if (str_contains($e->getMessage(), 'state') || str_contains($e->getMessage(), 'tier')) {
                $message .= 'Please run database migration on server: php artisan migrate';
            } else {
                $message .= $e->getMessage();
            }

            return redirect()->route('admin.locations.index')->with('error', $message);
        }

        $created = $import->getCreatedCount();
        $updated = $import->getUpdatedCount();
        $skippedDuplicates = $import->getSkippedDuplicateCount();
        $errors = $import->getErrors();
        $errorCount = $import->getErrorCount();

        $message = "Bulk upload complete. {$created} new, {$updated} updated (existing cities).";
        if ($skippedDuplicates > 0) {
            $message .= " {$skippedDuplicates} duplicate row(s) in file skipped.";
        }
        if ($errorCount > 0) {
            $message .= " {$errorCount} row(s) had errors.";
        }

        return redirect()->route('admin.locations.index')
            ->with('success', $message)
            ->with('import_errors', $errors);
    }

    public function getServiceRates(Request $request)
    {
        $request->validate([
            'location' => 'required|string',
            'service' => 'required|string',
        ]);

        $location = Location::where('name', $request->location)->first();
        $service = Service::where('name', $request->service)->first();

        if (!$location || !$service) {
            return response()->json([
                'success' => false,
                'message' => 'Location or service not found',
            ], 404);
        }

        $providerType = $request->input('provider_type', 'vendor');
        if (! in_array($providerType, ['vendor', 'freelancer'], true)) {
            $providerType = 'vendor';
        }

        $subServiceId = (int) $request->input('service_sub_service_id', 0);

        $locationServiceQuery = $location->services()
            ->where('service_id', $service->id)
            ->wherePivot('provider_type', $providerType);

        if ($subServiceId > 0) {
            $locationServiceQuery->wherePivot('service_sub_service_id', $subServiceId);
        } else {
            $locationServiceQuery->wherePivot('service_sub_service_id', 0);
        }

        $locationService = $locationServiceQuery->first();

        if (! $locationService && $subServiceId <= 0) {
            $locationService = $location->services()
                ->where('service_id', $service->id)
                ->wherePivot('provider_type', $providerType)
                ->first();
        }

        if (!$locationService) {
            return response()->json([
                'success' => false,
                'message' => 'Service not available for this location',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'service_name' => $service->name,
                'service_description' => $service->description,
                'location_name' => $location->name,
                'service_sub_service_id' => (int) ($locationService->pivot->service_sub_service_id ?? 0),
                'prices' => [
                    'price_12hr' => $locationService->pivot->price_12hr,
                    'price_24hr' => $locationService->pivot->price_24hr,
                    'price_onetime' => $locationService->pivot->price_onetime,
                ],
            ],
        ]);
    }

    private function servicesForProviderPricing()
    {
        return Service::query()
            ->with(['subServices' => fn ($q) => $q->where('is_active', true)->orderBy('sort_order')->orderBy('name')])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    private function doctorConsultationServicesForPricing()
    {
        return DoctorConsultationService::query()
            ->where('is_active', true)
            ->with(['subServices' => fn ($q) => $q->where('is_active', true)->orderBy('sort_order')->orderBy('name')])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function doctorPricingBlocksFromLocation(Location $location): array
    {
        $grouped = $location->doctorConsultationPrices->groupBy(function ($row) {
            return $row->doctor_consultation_service_id.'-'.($row->doctor_consultation_service_sub_service_id ?? '0');
        });

        $blocks = [];
        foreach ($grouped as $rows) {
            $first = $rows->first();
            $modes = [];
            foreach (LocationDoctorConsultationPrice::MODES as $mode) {
                $modes[$mode] = [
                    'enabled' => false,
                    'website_price' => '',
                    'doctor_max_price' => '',
                ];
            }
            foreach ($rows as $row) {
                $modes[$row->consultation_mode] = [
                    'enabled' => true,
                    'website_price' => $row->website_price,
                    'doctor_max_price' => $row->doctor_max_price,
                    'original_website_price' => $row->original_website_price,
                    'original_doctor_max_price' => $row->original_doctor_max_price,
                ];
            }
            $blocks[] = [
                'doctor_consultation_service_id' => $first->doctor_consultation_service_id,
                'doctor_consultation_service_sub_service_id' => $first->doctor_consultation_service_sub_service_id,
                'modes' => $modes,
            ];
        }

        return $blocks;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function doctorPricingBlocksFromOldInput(): array
    {
        $raw = old('doctor_pricing');
        if (! is_array($raw)) {
            return [];
        }

        $blocks = [];
        foreach ($raw as $block) {
            if (! is_array($block)) {
                continue;
            }
            $serviceId = $block['doctor_consultation_service_id'] ?? null;
            if (! $serviceId) {
                continue;
            }
            $blocks[] = [
                'doctor_consultation_service_id' => $serviceId,
                'doctor_consultation_service_sub_service_id' => $block['doctor_consultation_service_sub_service_id'] ?? null,
                'modes' => is_array($block['modes'] ?? null) ? $block['modes'] : [],
            ];
        }

        return $blocks;
    }

    private function syncDoctorConsultationPrices(Location $location, Request $request): void
    {
        $blocks = $request->input('doctor_pricing', []);
        $location->doctorConsultationPrices()->delete();

        if (! is_array($blocks)) {
            return;
        }

        $servicesById = DoctorConsultationService::query()
            ->where('is_active', true)
            ->with(['subServices' => fn ($q) => $q->where('is_active', true)])
            ->get()
            ->keyBy('id');

        foreach ($blocks as $block) {
            if (! is_array($block)) {
                continue;
            }

            $serviceId = (int) ($block['doctor_consultation_service_id'] ?? 0);
            if ($serviceId <= 0) {
                continue;
            }

            $service = $servicesById->get($serviceId);
            $activeSubs = $service ? $service->subServices->filter(fn ($s) => (bool) $s->is_active) : collect();
            $hasSubs = $activeSubs->isNotEmpty();

            $subRaw = $block['doctor_consultation_service_sub_service_id'] ?? null;
            $subId = ($subRaw !== null && $subRaw !== '') ? (int) $subRaw : null;

            // Service has sub-services → price must be saved for a specific sub-service (not service-level).
            if ($hasSubs) {
                if ($subId === null || $subId <= 0 || ! $activeSubs->contains('id', $subId)) {
                    continue;
                }
            } else {
                // No sub-services → always service-level (sub_service_id = null).
                $subId = null;
            }

            $modes = $block['modes'] ?? [];
            if (! is_array($modes)) {
                continue;
            }

            foreach (LocationDoctorConsultationPrice::MODES as $mode) {
                $modeData = $modes[$mode] ?? [];
                if (! is_array($modeData)) {
                    continue;
                }
                if (empty($modeData['enabled'])) {
                    continue;
                }

                $websitePrice = $modeData['website_price'] ?? null;
                $doctorMax = $modeData['doctor_max_price'] ?? null;

                // Skip empty mode rows (no prices set).
                if (($websitePrice === null || $websitePrice === '') && ($doctorMax === null || $doctorMax === '')) {
                    continue;
                }

                LocationDoctorConsultationPrice::create([
                    'location_id' => $location->id,
                    'doctor_consultation_service_id' => $serviceId,
                    'doctor_consultation_service_sub_service_id' => $subId,
                    'consultation_mode' => $mode,
                    'website_price' => $websitePrice !== '' && $websitePrice !== null ? $websitePrice : null,
                    'doctor_max_price' => $doctorMax !== '' && $doctorMax !== null ? $doctorMax : null,
                    'is_active' => true,
                ]);
            }
        }
    }

    private function tierOptions(): array
    {
        return ['Tier 1', 'Tier 2', 'Tier 3', 'Tier 4'];
    }

    private function providerServicesValidationRules(): array
    {
        $rules = [];
        foreach (['vendor_services', 'freelancer_services'] as $key) {
            $rules[$key] = 'nullable|array|max:100';
            $rules["{$key}.*.service_id"] = 'nullable|integer|exists:services,id';
            $rules["{$key}.*.service_sub_service_id"] = 'nullable|integer|min:0';
            $rules["{$key}.*.price_12hr"] = 'nullable|numeric|min:0';
            $rules["{$key}.*.price_24hr"] = 'nullable|numeric|min:0';
            $rules["{$key}.*.price_onetime"] = 'nullable|numeric|min:0';
        }

        return $rules;
    }

    private function doctorPricingValidationRules(): array
    {
        return [
            'doctor_pricing' => 'nullable|array|max:50',
            'doctor_pricing.*.doctor_consultation_service_id' => 'nullable|integer|exists:doctor_consultation_services,id',
            'doctor_pricing.*.doctor_consultation_service_sub_service_id' => 'nullable|integer|exists:doctor_consultation_service_sub_services,id',
            'doctor_pricing.*.modes' => 'nullable|array',
            'doctor_pricing.*.modes.*.enabled' => 'nullable|boolean',
            'doctor_pricing.*.modes.*.website_price' => 'nullable|numeric|min:0',
            'doctor_pricing.*.modes.*.doctor_max_price' => 'nullable|numeric|min:0',
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function providerServiceRowsFromLocation(Location $location, string $providerType): array
    {
        $rows = [];
        foreach ($location->services as $service) {
            $pivotType = $service->pivot->provider_type ?? 'vendor';
            if ($pivotType !== $providerType) {
                continue;
            }
            $rows[] = [
                'service_id' => $service->id,
                'service_sub_service_id' => (int) ($service->pivot->service_sub_service_id ?? 0),
                'price_12hr' => $service->pivot->price_12hr,
                'price_24hr' => $service->pivot->price_24hr,
                'price_onetime' => $service->pivot->price_onetime,
                'original_price_12hr' => $service->pivot->original_price_12hr ?? null,
                'original_price_24hr' => $service->pivot->original_price_24hr ?? null,
                'original_price_onetime' => $service->pivot->original_price_onetime ?? null,
            ];
        }

        return $rows;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function providerServiceRowsFromOldInput(string $inputKey): array
    {
        $raw = old($inputKey);
        if (! is_array($raw)) {
            return [];
        }

        $rows = [];
        foreach ($raw as $row) {
            if (! is_array($row) || empty($row['service_id'])) {
                continue;
            }
            $rows[] = [
                'service_id' => $row['service_id'],
                'service_sub_service_id' => (int) ($row['service_sub_service_id'] ?? 0),
                'price_12hr' => $row['price_12hr'] ?? null,
                'price_24hr' => $row['price_24hr'] ?? null,
                'price_onetime' => $row['price_onetime'] ?? null,
            ];
        }

        return $rows;
    }

    private function syncProviderServices(Location $location, Request $request, string $providerType, string $inputKey): void
    {
        DB::table('location_services')
            ->where('location_id', $location->id)
            ->where('provider_type', $providerType)
            ->delete();

        $rows = $request->input($inputKey, []);
        if (! is_array($rows)) {
            return;
        }

        $servicesById = Service::query()
            ->with(['subServices' => fn ($q) => $q->where('is_active', true)])
            ->get()
            ->keyBy('id');

        $seenKeys = [];

        foreach ($rows as $serviceData) {
            if (! is_array($serviceData) || empty($serviceData['service_id'])) {
                continue;
            }

            $serviceId = (int) $serviceData['service_id'];
            $service = $servicesById->get($serviceId);
            if (! $service) {
                continue;
            }

            $activeSubs = $service->subServices->filter(fn ($s) => (bool) $s->is_active);
            $hasSubs = $activeSubs->isNotEmpty();
            $subId = (int) ($serviceData['service_sub_service_id'] ?? 0);

            if ($hasSubs) {
                if ($subId <= 0 || ! $activeSubs->contains('id', $subId)) {
                    continue;
                }
            } else {
                $subId = 0;
            }

            $dedupeKey = $serviceId.'|'.$subId;
            if (isset($seenKeys[$dedupeKey])) {
                continue;
            }
            $seenKeys[$dedupeKey] = true;

            $hasPrice = ($serviceData['price_12hr'] ?? '') !== ''
                || ($serviceData['price_24hr'] ?? '') !== ''
                || ($serviceData['price_onetime'] ?? '') !== '';
            if (! $hasPrice) {
                continue;
            }

            $location->services()->attach($serviceId, [
                'service_sub_service_id' => $subId,
                'price_12hr' => $serviceData['price_12hr'] ?? null,
                'price_24hr' => $serviceData['price_24hr'] ?? null,
                'price_onetime' => $serviceData['price_onetime'] ?? null,
                'provider_type' => $providerType,
            ]);
        }
    }

    private function indianStates(): array
    {
        return [
            'Andhra Pradesh', 'Arunachal Pradesh', 'Assam', 'Bihar', 'Chhattisgarh',
            'Goa', 'Gujarat', 'Haryana', 'Himachal Pradesh', 'Jharkhand', 'Karnataka',
            'Kerala', 'Madhya Pradesh', 'Maharashtra', 'Manipur', 'Meghalaya', 'Mizoram',
            'Nagaland', 'Odisha', 'Punjab', 'Rajasthan', 'Sikkim', 'Tamil Nadu',
            'Telangana', 'Tripura', 'Uttar Pradesh', 'Uttarakhand', 'West Bengal',
            'Andaman and Nicobar Islands', 'Chandigarh', 'Dadra and Nagar Haveli and Daman and Diu',
            'Delhi', 'Jammu and Kashmir', 'Ladakh', 'Lakshadweep', 'Puducherry',
        ];
    }

    // ─── Mobile admin API (CareApp parity with web CRUD) ─────────────────

    public function apiIndex(): \Illuminate\Http\JsonResponse
    {
        $subServiceNames = ServiceSubService::query()->pluck('name', 'id');
        $items = Location::with('services')->latest()->get()
            ->map(fn (Location $location) => $this->formatLocationForApi($location, $subServiceNames));

        return response()->json(['items' => $items]);
    }

    public function apiFormOptions(): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'indian_states' => $this->indianStates(),
            'tier_options' => $this->tierOptions(),
            'services' => $this->servicesForProviderPricing()->map(fn ($s) => [
                'id' => (int) $s->id,
                'name' => (string) $s->name,
                'sub_services' => $s->subServices->map(fn ($sub) => [
                    'id' => (int) $sub->id,
                    'name' => (string) $sub->name,
                ])->values(),
            ])->values(),
            'doctor_consultation_services' => $this->doctorConsultationServicesForPricing()->map(fn ($s) => [
                'id' => (int) $s->id,
                'name' => (string) $s->name,
                'sub_services' => $s->subServices->map(fn ($sub) => [
                    'id' => (int) $sub->id,
                    'name' => (string) $sub->name,
                ])->values(),
            ])->values(),
            'doctor_modes' => LocationDoctorConsultationPrice::MODES,
            'doctor_mode_labels' => LocationDoctorConsultationPrice::MODE_LABELS,
        ]);
    }

    public function apiShow(Location $location): \Illuminate\Http\JsonResponse
    {
        $location->load(['services', 'doctorConsultationPrices']);

        return response()->json([
            'item' => [
                'id' => $location->id,
                'name' => $location->name,
                'state' => $location->state,
                'tier' => $location->tier,
            ],
            'doctor_pricing' => $this->doctorPricingBlocksFromLocation($location),
            'vendor_services' => $this->providerServiceRowsFromLocation($location, 'vendor'),
            'freelancer_services' => $this->providerServiceRowsFromLocation($location, 'freelancer'),
        ]);
    }

    public function apiStore(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate(array_merge([
            'name' => 'required|string|max:255|unique:locations',
            'state' => ['required', 'string', 'max:100', Rule::in($this->indianStates())],
            'tier' => ['required', 'string', 'max:50', Rule::in($this->tierOptions())],
        ], $this->providerServicesValidationRules(), $this->doctorPricingValidationRules()));

        $location = Location::create($request->only('name', 'state', 'tier'));
        $this->syncDoctorConsultationPrices($location, $request);
        $this->syncProviderServices($location, $request, 'vendor', 'vendor_services');
        $this->syncProviderServices($location, $request, 'freelancer', 'freelancer_services');

        return response()->json([
            'success' => true,
            'message' => 'Location created successfully.',
            'item' => $location->fresh(),
        ], 201);
    }

    public function apiUpdate(Request $request, Location $location): \Illuminate\Http\JsonResponse
    {
        $request->validate(array_merge([
            'name' => 'required|string|max:255|unique:locations,name,'.$location->id,
            'state' => ['required', 'string', 'max:100', Rule::in($this->indianStates())],
            'tier' => ['required', 'string', 'max:50', Rule::in($this->tierOptions())],
        ], $this->providerServicesValidationRules(), $this->doctorPricingValidationRules()));

        $location->update($request->only('name', 'state', 'tier'));
        $this->syncDoctorConsultationPrices($location, $request);
        $this->syncProviderServices($location, $request, 'vendor', 'vendor_services');
        $this->syncProviderServices($location, $request, 'freelancer', 'freelancer_services');

        return response()->json([
            'success' => true,
            'message' => 'Location updated successfully.',
            'item' => $location->fresh(),
        ]);
    }

    public function apiDestroy(Location $location): \Illuminate\Http\JsonResponse
    {
        $location->delete();

        return response()->json([
            'success' => true,
            'message' => 'Location deleted successfully.',
        ]);
    }

    /** @return array<string, mixed> */
    private function formatLocationForApi(Location $location, $subServiceNames): array
    {
        return [
            'id' => $location->id,
            'name' => $location->name,
            'state' => $location->state,
            'tier' => $location->tier,
            'display_label' => $location->display_label,
            'created_at' => $location->created_at?->format('Y-m-d H:i:s'),
            'services' => $location->services->map(function ($service) use ($subServiceNames) {
                $subId = (int) ($service->pivot->service_sub_service_id ?? 0);

                return [
                    'id' => $service->id,
                    'name' => $service->name,
                    'provider_type' => $service->pivot->provider_type ?? 'vendor',
                    'service_sub_service_id' => $subId,
                    'sub_service_name' => $subId > 0 ? ($subServiceNames[$subId] ?? null) : null,
                    'price_12hr' => $service->pivot->price_12hr,
                    'price_24hr' => $service->pivot->price_24hr,
                    'price_onetime' => $service->pivot->price_onetime,
                ];
            })->values(),
        ];
    }
}
