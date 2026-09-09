<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DoctorConsultationService;
use App\Models\DoctorConsultationServiceSubService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class DoctorConsultationServiceController extends Controller
{
    public function index()
    {
        $items = DoctorConsultationService::query()
            ->withCount('subServices')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('admin.doctor_consultation_services.index', compact('items'));
    }

    public function create()
    {
        return view('admin.doctor_consultation_services.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:doctor_consultation_services,name',
            'category' => 'nullable|string|max:160',
            'icon' => 'nullable|image|mimes:jpeg,jpg,png,webp|max:2048',
            'specialization_options_text' => 'nullable|string|max:8000',
            'consultation_duration_minutes' => 'nullable|integer|min:1|max:1440',
            'sort_order' => 'nullable|integer|min:0|max:999999',
            'sub_services' => 'nullable|array|max:50',
            'sub_services.*.name' => 'nullable|string|max:255',
            'sub_services.*.consultation_duration_minutes' => 'nullable|integer|min:1|max:1440',
            'sub_services.*.sort_order' => 'nullable|integer|min:0|max:999999',
            'sub_services.*.specialization_options_text' => 'nullable|string|max:8000',
            'sub_services.*.icon' => 'nullable|image|mimes:jpeg,jpg,png,webp|max:2048',
        ]);

        $specLines = self::resolveParentSpecializationOptions($request);

        $iconPath = self::storeServiceIcon($request);

        $service = DoctorConsultationService::create([
            'name' => $validated['name'],
            'category' => $validated['category'] ?? null,
            'icon_path' => $iconPath,
            'specialization_options' => $specLines,
            'consultation_duration_minutes' => (int) ($validated['consultation_duration_minutes'] ?? 30),
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_active' => true,
        ]);

        try {
            self::syncSubServices($service, $request);
        } catch (ValidationException $e) {
            $service->delete();
            self::deleteServiceIcon($iconPath);

            throw $e;
        }

        return redirect()->route('admin.doctor_consultation_services.index')
            ->with('success', 'Consultation service added.');
    }

    public function edit(DoctorConsultationService $doctor_consultation_service)
    {
        $doctor_consultation_service->load('subServices');

        return view('admin.doctor_consultation_services.edit', ['item' => $doctor_consultation_service]);
    }

    public function update(Request $request, DoctorConsultationService $doctor_consultation_service)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:doctor_consultation_services,name,'.$doctor_consultation_service->id,
            'category' => 'nullable|string|max:160',
            'icon' => 'nullable|image|mimes:jpeg,jpg,png,webp|max:2048',
            'remove_icon' => 'sometimes|boolean',
            'specialization_options_text' => 'nullable|string|max:8000',
            'consultation_duration_minutes' => 'nullable|integer|min:1|max:1440',
            'sort_order' => 'nullable|integer|min:0|max:999999',
            'sub_services' => 'nullable|array|max:50',
            'sub_services.*.id' => 'nullable|integer',
            'sub_services.*._delete' => 'sometimes|boolean',
            'sub_services.*.name' => 'nullable|string|max:255',
            'sub_services.*.consultation_duration_minutes' => 'nullable|integer|min:1|max:1440',
            'sub_services.*.sort_order' => 'nullable|integer|min:0|max:999999',
            'sub_services.*.specialization_options_text' => 'nullable|string|max:8000',
            'sub_services.*.icon' => 'nullable|image|mimes:jpeg,jpg,png,webp|max:2048',
            'sub_services.*.remove_icon' => 'sometimes|boolean',
        ]);

        $specLines = self::resolveParentSpecializationOptions($request);

        $iconPath = $doctor_consultation_service->icon_path;
        if ($request->boolean('remove_icon')) {
            self::deleteServiceIcon($iconPath);
            $iconPath = null;
        }
        if ($request->hasFile('icon')) {
            self::deleteServiceIcon($iconPath);
            $iconPath = self::storeServiceIcon($request);
        }

        $doctor_consultation_service->update([
            'name' => $validated['name'],
            'category' => $validated['category'] ?? null,
            'icon_path' => $iconPath,
            'specialization_options' => $specLines,
            'consultation_duration_minutes' => (int) ($validated['consultation_duration_minutes'] ?? 30),
            'sort_order' => $validated['sort_order'] ?? 0,
        ]);

        self::syncSubServices($doctor_consultation_service, $request);

        return redirect()->route('admin.doctor_consultation_services.index')
            ->with('success', 'Consultation service updated.');
    }

    public function destroy(DoctorConsultationService $doctor_consultation_service)
    {
        self::deleteServiceIcon($doctor_consultation_service->icon_path);
        foreach ($doctor_consultation_service->subServices as $sub) {
            self::deleteServiceIcon($sub->icon_path);
        }
        $doctor_consultation_service->delete();

        return redirect()->route('admin.doctor_consultation_services.index')
            ->with('success', 'Consultation service deleted.');
    }

    /** Admin mobile app — list (includes inactive). */
    public function apiIndex()
    {
        $items = DoctorConsultationService::query()
            ->withCount('subServices')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(fn (DoctorConsultationService $service) => $this->formatServiceForApi($service));

        return response()->json([
            'success' => true,
            'services' => $items,
        ]);
    }

    public function apiShow(DoctorConsultationService $doctor_consultation_service)
    {
        $doctor_consultation_service->load(['subServices' => fn ($q) => $q->orderBy('sort_order')->orderBy('name')]);

        return response()->json([
            'success' => true,
            'service' => $this->formatServiceDetailForApi($doctor_consultation_service),
        ]);
    }

    public function apiStore(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:doctor_consultation_services,name',
            'category' => 'nullable|string|max:160',
            'icon' => 'nullable|image|mimes:jpeg,jpg,png,webp|max:2048',
            'specialization_options_text' => 'nullable|string|max:8000',
            'consultation_duration_minutes' => 'nullable|integer|min:1|max:1440',
            'sort_order' => 'nullable|integer|min:0|max:999999',
            'sub_services' => 'nullable|array|max:50',
            'sub_services.*.name' => 'nullable|string|max:255',
            'sub_services.*.consultation_duration_minutes' => 'nullable|integer|min:1|max:1440',
            'sub_services.*.sort_order' => 'nullable|integer|min:0|max:999999',
            'sub_services.*.specialization_options_text' => 'nullable|string|max:8000',
            'sub_services.*.icon' => 'nullable|image|mimes:jpeg,jpg,png,webp|max:2048',
        ]);

        $specLines = self::resolveParentSpecializationOptions($request);
        $iconPath = self::storeServiceIcon($request);

        $service = DoctorConsultationService::create([
            'name' => $validated['name'],
            'category' => $validated['category'] ?? null,
            'icon_path' => $iconPath,
            'specialization_options' => $specLines,
            'consultation_duration_minutes' => (int) ($validated['consultation_duration_minutes'] ?? 30),
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_active' => true,
        ]);

        try {
            self::syncSubServices($service, $request);
        } catch (ValidationException $e) {
            $service->delete();
            self::deleteServiceIcon($iconPath);

            throw $e;
        }

        $service->load(['subServices' => fn ($q) => $q->orderBy('sort_order')->orderBy('name')]);
        $service->loadCount('subServices');

        return response()->json([
            'success' => true,
            'message' => 'Consultation service added.',
            'service' => $this->formatServiceDetailForApi($service),
        ], 201);
    }

    public function apiUpdate(Request $request, DoctorConsultationService $doctor_consultation_service)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:doctor_consultation_services,name,'.$doctor_consultation_service->id,
            'category' => 'nullable|string|max:160',
            'icon' => 'nullable|image|mimes:jpeg,jpg,png,webp|max:2048',
            'remove_icon' => 'sometimes|boolean',
            'specialization_options_text' => 'nullable|string|max:8000',
            'consultation_duration_minutes' => 'nullable|integer|min:1|max:1440',
            'sort_order' => 'nullable|integer|min:0|max:999999',
            'is_active' => 'sometimes|boolean',
            'sub_services' => 'nullable|array|max:50',
            'sub_services.*.id' => 'nullable|integer',
            'sub_services.*._delete' => 'sometimes|boolean',
            'sub_services.*.name' => 'nullable|string|max:255',
            'sub_services.*.consultation_duration_minutes' => 'nullable|integer|min:1|max:1440',
            'sub_services.*.sort_order' => 'nullable|integer|min:0|max:999999',
            'sub_services.*.specialization_options_text' => 'nullable|string|max:8000',
            'sub_services.*.icon' => 'nullable|image|mimes:jpeg,jpg,png,webp|max:2048',
            'sub_services.*.remove_icon' => 'sometimes|boolean',
        ]);

        $specLines = self::resolveParentSpecializationOptions($request);

        $iconPath = $doctor_consultation_service->icon_path;
        if ($request->boolean('remove_icon')) {
            self::deleteServiceIcon($iconPath);
            $iconPath = null;
        }
        if ($request->hasFile('icon')) {
            self::deleteServiceIcon($iconPath);
            $iconPath = self::storeServiceIcon($request);
        }

        $updateData = [
            'name' => $validated['name'],
            'category' => $validated['category'] ?? null,
            'icon_path' => $iconPath,
            'specialization_options' => $specLines,
            'consultation_duration_minutes' => (int) ($validated['consultation_duration_minutes'] ?? 30),
            'sort_order' => $validated['sort_order'] ?? 0,
        ];
        if (array_key_exists('is_active', $validated)) {
            $updateData['is_active'] = (bool) $validated['is_active'];
        }

        $doctor_consultation_service->update($updateData);
        self::syncSubServices($doctor_consultation_service, $request);

        $doctor_consultation_service->load(['subServices' => fn ($q) => $q->orderBy('sort_order')->orderBy('name')]);
        $doctor_consultation_service->loadCount('subServices');

        return response()->json([
            'success' => true,
            'message' => 'Consultation service updated.',
            'service' => $this->formatServiceDetailForApi($doctor_consultation_service),
        ]);
    }

    public function apiDestroy(DoctorConsultationService $doctor_consultation_service)
    {
        self::deleteServiceIcon($doctor_consultation_service->icon_path);
        foreach ($doctor_consultation_service->subServices as $sub) {
            self::deleteServiceIcon($sub->icon_path);
        }
        $doctor_consultation_service->delete();

        return response()->json([
            'success' => true,
            'message' => 'Consultation service deleted.',
        ]);
    }

    /** @return array<string, mixed> */
    private function formatServiceForApi(DoctorConsultationService $service): array
    {
        $tags = is_array($service->specialization_options) ? $service->specialization_options : [];

        return [
            'id' => $service->id,
            'name' => $service->name,
            'category' => $service->category,
            'icon_path' => $service->icon_path,
            'icon_url' => $this->serviceIconUrl($service->icon_path),
            'consultation_duration_minutes' => (int) ($service->consultation_duration_minutes ?? 30),
            'sort_order' => (int) ($service->sort_order ?? 0),
            'specialization_options' => $tags,
            'sub_services_count' => (int) ($service->sub_services_count ?? 0),
            'is_active' => (bool) $service->is_active,
        ];
    }

    /** @return array<string, mixed> */
    private function formatServiceDetailForApi(DoctorConsultationService $service): array
    {
        $base = $this->formatServiceForApi($service);
        $base['sub_services'] = $service->subServices->map(fn (DoctorConsultationServiceSubService $sub) => [
            'id' => $sub->id,
            'name' => $sub->name,
            'icon_path' => $sub->icon_path,
            'icon_url' => $this->serviceIconUrl($sub->icon_path),
            'consultation_duration_minutes' => (int) ($sub->consultation_duration_minutes ?? 30),
            'sort_order' => (int) ($sub->sort_order ?? 0),
            'specialization_options' => is_array($sub->specialization_options) ? $sub->specialization_options : [],
            'is_active' => (bool) $sub->is_active,
        ])->values();

        return $base;
    }

    private function serviceIconUrl(?string $path): ?string
    {
        if ($path === null || trim($path) === '') {
            return null;
        }
        $clean = ltrim(str_replace('\\', '/', trim($path)), '/');

        return asset('storage/'.$clean);
    }

    /**
     * @param  array<int, array<string, mixed>>|null  $rows
     */
    private static function syncSubServices(DoctorConsultationService $service, Request $request): void
    {
        $rows = $request->input('sub_services', []);
        if (! is_array($rows)) {
            return;
        }

        $seenNames = [];
        $errors = [];

        foreach ($rows as $index => $row) {
            if (! is_array($row)) {
                continue;
            }

            $delete = filter_var($row['_delete'] ?? false, FILTER_VALIDATE_BOOLEAN);
            $id = isset($row['id']) ? (int) $row['id'] : null;
            $name = trim((string) ($row['name'] ?? ''));

            if ($delete && $id) {
                $sub = $service->subServices()->whereKey($id)->first();
                if ($sub) {
                    self::deleteServiceIcon($sub->icon_path);
                    $sub->delete();
                }
                continue;
            }

            if ($delete || $name === '') {
                continue;
            }

            $nameKey = mb_strtolower($name);
            if (isset($seenNames[$nameKey])) {
                $errors["sub_services.$index.name"] = 'Duplicate sub-service name in this form.';

                continue;
            }
            $seenNames[$nameKey] = true;

            $specLines = self::parseSpecializationLines($row['specialization_options_text'] ?? '');

            $rawDuration = $row['consultation_duration_minutes'] ?? null;
            $duration = ($rawDuration === null || $rawDuration === '') ? 30 : (int) $rawDuration;
            if ($duration < 1) {
                $duration = 30;
            }

            $sortOrder = (int) ($row['sort_order'] ?? 0);

            $sub = null;
            if ($id) {
                $sub = $service->subServices()->whereKey($id)->first();
            }

            if ($sub === null) {
                $duplicateDb = $service->subServices()->whereRaw('LOWER(TRIM(name)) = ?', [$nameKey])->exists();
                if ($duplicateDb) {
                    $errors["sub_services.$index.name"] = 'A sub-service with this name already exists.';

                    continue;
                }
                $sub = new DoctorConsultationServiceSubService([
                    'doctor_consultation_service_id' => $service->id,
                ]);
            } else {
                $duplicateDb = $service->subServices()
                    ->where('id', '!=', $sub->id)
                    ->whereRaw('LOWER(TRIM(name)) = ?', [$nameKey])
                    ->exists();
                if ($duplicateDb) {
                    $errors["sub_services.$index.name"] = 'A sub-service with this name already exists.';

                    continue;
                }
            }

            $iconPath = $sub->icon_path;
            if (filter_var($row['remove_icon'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
                self::deleteServiceIcon($iconPath);
                $iconPath = null;
            }

            $iconFile = $request->file("sub_services.$index.icon");
            if ($iconFile) {
                self::deleteServiceIcon($iconPath);
                $iconPath = $iconFile->store('consultation-service-icons', 'public') ?: null;
            }

            $sub->fill([
                'name' => $name,
                'icon_path' => $iconPath,
                'specialization_options' => $specLines,
                'consultation_duration_minutes' => $duration,
                'sort_order' => $sortOrder,
                'is_active' => true,
            ]);
            $sub->save();
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    private static function syncSubServicesFromApiPayload(DoctorConsultationService $service, array $rows): void
    {
        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }

            $delete = filter_var($row['_delete'] ?? false, FILTER_VALIDATE_BOOLEAN);
            $id = isset($row['id']) ? (int) $row['id'] : null;

            if ($delete && $id) {
                $sub = $service->subServices()->whereKey($id)->first();
                if ($sub) {
                    self::deleteServiceIcon($sub->icon_path);
                    $sub->delete();
                }
                continue;
            }

            $name = trim((string) ($row['name'] ?? ''));
            if ($name === '') {
                continue;
            }

            $tags = array_values(array_filter(array_map('trim', $row['specialization_options'] ?? [])));

            $sub = $id ? $service->subServices()->whereKey($id)->first() : null;
            if ($sub === null) {
                $sub = new DoctorConsultationServiceSubService([
                    'doctor_consultation_service_id' => $service->id,
                ]);
            }

            $sub->fill([
                'name' => $name,
                'specialization_options' => $tags,
                'consultation_duration_minutes' => (int) ($row['consultation_duration_minutes'] ?? 30),
                'sort_order' => (int) ($row['sort_order'] ?? 0),
                'is_active' => true,
            ]);
            $sub->save();
        }
    }

    /**
     * Parent service tags are optional (empty list allowed).
     *
     * @return list<string>
     */
    private static function resolveParentSpecializationOptions(Request $request): array
    {
        if (self::hasActiveSubServicesInRequest($request)) {
            return [];
        }

        return self::parseSpecializationLines($request->input('specialization_options_text'));
    }

    private static function hasActiveSubServicesInRequest(Request $request): bool
    {
        $rows = $request->input('sub_services', []);
        if (! is_array($rows)) {
            return false;
        }

        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }
            if (filter_var($row['_delete'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
                continue;
            }
            if (trim((string) ($row['name'] ?? '')) !== '') {
                return true;
            }
        }

        return false;
    }

    /** @return list<string> */
    private static function parseSpecializationLines(?string $raw): array
    {
        $raw = trim((string) $raw);
        if ($raw === '') {
            return [];
        }
        $lines = preg_split('/\r\n|\r|\n/', $raw) ?: [];
        $out = [];
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line !== '') {
                $out[] = $line;
            }
        }

        return array_slice($out, 0, 200);
    }

    private static function storeServiceIcon(Request $request): ?string
    {
        if (! $request->hasFile('icon')) {
            return null;
        }

        return $request->file('icon')->store('consultation-service-icons', 'public') ?: null;
    }

    private static function deleteServiceIcon(?string $path): void
    {
        if ($path === null || $path === '') {
            return;
        }
        $path = str_replace('\\', '/', trim($path));
        if (Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}
