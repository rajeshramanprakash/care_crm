<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Models\ServiceSubService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ServiceController extends Controller
{
    public function index()
    {
        $services = Service::query()
            ->withCount('subServices')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('admin.services.index', compact('services'));
    }

    public function create()
    {
        return view('admin.services.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:services,name',
            'description' => 'nullable|string|max:5000',
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

        $service = Service::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
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

        return redirect()->route('admin.services.index')
            ->with('success', 'Service created successfully.');
    }

    public function show(string $id)
    {
        //
    }

    public function edit(Service $service)
    {
        $service->load('subServices');

        return view('admin.services.edit', ['item' => $service]);
    }

    public function update(Request $request, Service $service)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:services,name,'.$service->id,
            'description' => 'nullable|string|max:5000',
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

        $iconPath = $service->icon_path;
        if ($request->boolean('remove_icon')) {
            self::deleteServiceIcon($iconPath);
            $iconPath = null;
        }
        if ($request->hasFile('icon')) {
            self::deleteServiceIcon($iconPath);
            $iconPath = self::storeServiceIcon($request);
        }

        $service->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'icon_path' => $iconPath,
            'specialization_options' => $specLines,
            'consultation_duration_minutes' => (int) ($validated['consultation_duration_minutes'] ?? 30),
            'sort_order' => $validated['sort_order'] ?? 0,
        ]);

        self::syncSubServices($service, $request);

        return redirect()->route('admin.services.index')
            ->with('success', 'Service updated successfully.');
    }

    public function destroy(Service $service)
    {
        self::deleteServiceIcon($service->icon_path);
        foreach ($service->subServices as $sub) {
            self::deleteServiceIcon($sub->icon_path);
        }
        $service->delete();

        return redirect()->route('admin.services.index')
            ->with('success', 'Service deleted successfully.');
    }

    private static function syncSubServices(Service $service, Request $request): void
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
                $sub = new ServiceSubService([
                    'service_id' => $service->id,
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
                $iconPath = $iconFile->store('service-icons', 'public') ?: null;
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

    /** @return list<string> */
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

        return $request->file('icon')->store('service-icons', 'public') ?: null;
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

    // ─── Mobile admin API (CareApp parity with web CRUD) ─────────────────

    public function apiIndex(): \Illuminate\Http\JsonResponse
    {
        $items = Service::query()
            ->withCount('subServices')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(fn (Service $service) => $this->formatServiceForApi($service));

        return response()->json(['items' => $items]);
    }

    public function apiShow(Service $service): \Illuminate\Http\JsonResponse
    {
        $service->load(['subServices' => fn ($q) => $q->orderBy('sort_order')->orderBy('name')]);

        return response()->json(['item' => $this->formatServiceDetailForApi($service)]);
    }

    public function apiStore(Request $request): \Illuminate\Http\JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:services,name',
            'description' => 'nullable|string|max:5000',
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

        $service = Service::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
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

        $service->loadCount('subServices');

        return response()->json([
            'success' => true,
            'message' => 'Service created successfully.',
            'item' => $this->formatServiceDetailForApi($service->load('subServices')),
        ], 201);
    }

    public function apiUpdate(Request $request, Service $service): \Illuminate\Http\JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:services,name,'.$service->id,
            'description' => 'nullable|string|max:5000',
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

        $iconPath = $service->icon_path;
        if ($request->boolean('remove_icon')) {
            self::deleteServiceIcon($iconPath);
            $iconPath = null;
        }
        if ($request->hasFile('icon')) {
            self::deleteServiceIcon($iconPath);
            $iconPath = self::storeServiceIcon($request);
        }

        $service->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'icon_path' => $iconPath,
            'specialization_options' => $specLines,
            'consultation_duration_minutes' => (int) ($validated['consultation_duration_minutes'] ?? 30),
            'sort_order' => $validated['sort_order'] ?? 0,
        ]);

        self::syncSubServices($service, $request);

        $service->load(['subServices' => fn ($q) => $q->orderBy('sort_order')->orderBy('name')]);
        $service->loadCount('subServices');

        return response()->json([
            'success' => true,
            'message' => 'Service updated successfully.',
            'item' => $this->formatServiceDetailForApi($service),
        ]);
    }

    public function apiDestroy(Service $service): \Illuminate\Http\JsonResponse
    {
        self::deleteServiceIcon($service->icon_path);
        foreach ($service->subServices as $sub) {
            self::deleteServiceIcon($sub->icon_path);
        }
        $service->delete();

        return response()->json([
            'success' => true,
            'message' => 'Service deleted successfully.',
        ]);
    }

    /** @return array<string, mixed> */
    private function formatServiceForApi(Service $service): array
    {
        $tags = is_array($service->specialization_options) ? $service->specialization_options : [];

        return [
            'id' => $service->id,
            'name' => $service->name,
            'description' => $service->description,
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
    private function formatServiceDetailForApi(Service $service): array
    {
        $base = $this->formatServiceForApi($service);
        $base['sub_services'] = $service->subServices->map(fn (ServiceSubService $sub) => [
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
}
