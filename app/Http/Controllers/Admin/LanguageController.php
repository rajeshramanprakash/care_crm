<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Language;
use App\Services\RegistrationI18nService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LanguageController extends Controller
{
    public function __construct(
        private readonly RegistrationI18nService $i18n
    ) {
        $this->middleware('can:view_languages');
    }

    public function index(): View
    {
        $languages = Language::query()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('admin.languages.index', compact('languages'));
    }

    public function create(): View
    {
        return view('admin.languages.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'code' => ['nullable', 'string', 'max:10', 'alpha_dash'],
            'native_name' => ['nullable', 'string', 'max:100'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $code = trim((string) ($data['code'] ?? ''));
        if ($code === '') {
            $code = $this->i18n->resolveUniqueCode($data['name']);
        } else {
            $code = strtolower($code);
            if (Language::query()->where('code', $code)->exists()) {
                $code = $this->i18n->resolveUniqueCode($data['name']);
            }
        }

        $language = Language::create([
            'name' => $data['name'],
            'code' => $code,
            'native_name' => $data['native_name'] ?? null,
            'is_active' => $request->boolean('is_active', true),
            'sort_order' => (int) ($data['sort_order'] ?? 0),
        ]);

        $result = $this->i18n->autoTranslateLanguage($language);

        $flash = $result['success']
            ? 'success'
            : 'warning';

        return redirect()
            ->route('admin.languages.index')
            ->with($flash, $result['message'].' (Code: '.$language->code.')');
    }

    public function edit(Language $language): View
    {
        return view('admin.languages.edit', compact('language'));
    }

    public function update(Request $request, Language $language): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'code' => ['required', 'string', 'max:10', 'alpha_dash', 'unique:languages,code,'.$language->id],
            'native_name' => ['nullable', 'string', 'max:100'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $language->update([
            'name' => $data['name'],
            'code' => strtolower($data['code']),
            'native_name' => $data['native_name'] ?? null,
            'is_active' => $request->boolean('is_active', true),
            'sort_order' => (int) ($data['sort_order'] ?? 0),
        ]);

        return redirect()
            ->route('admin.languages.index')
            ->with('success', 'Language updated successfully.');
    }

    public function destroy(Language $language): RedirectResponse
    {
        $language->delete();

        return redirect()
            ->route('admin.languages.index')
            ->with('success', 'Language deleted.');
    }

    public function autoTranslate(Language $language): RedirectResponse
    {
        $result = $this->i18n->autoTranslateLanguage($language);

        $flash = $result['success'] ? 'success' : 'warning';

        return redirect()
            ->route('admin.languages.index')
            ->with($flash, $result['message']);
    }

    public function translations(Language $language): View
    {
        $definitions = $this->i18n->keyDefinitions();
        $groups = $this->i18n->groupedLabels();
        $saved = $language->registrationTranslations()->pluck('value', 'translation_key')->all();

        $grouped = [];
        foreach ($definitions as $key => $meta) {
            $groupKey = $meta['group'] ?? 'common';
            $grouped[$groupKey][] = [
                'key' => $key,
                'label' => $meta['label'] ?? $key,
                'default' => $meta['default'] ?? '',
                'value' => $saved[$key] ?? ($meta['default'] ?? ''),
            ];
        }

        return view('admin.languages.translations', compact('language', 'grouped', 'groups'));
    }

    public function updateTranslations(Request $request, Language $language): RedirectResponse
    {
        $this->i18n->saveTranslations($language, $request->input('translations', []));

        return redirect()
            ->route('admin.languages.translations', $language)
            ->with('success', 'Registration form translations saved.');
    }

    public function resetTranslations(Language $language): RedirectResponse
    {
        $result = $this->i18n->autoTranslateLanguage($language);
        $flash = $result['success'] ? 'success' : 'warning';

        return redirect()
            ->route('admin.languages.translations', $language)
            ->with($flash, $result['message']);
    }

    // ─── Mobile admin API (CareApp parity with web CRUD) ─────────────────

    public function apiIndex(): JsonResponse
    {
        $items = Language::query()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(fn (Language $lang) => $this->formatLanguageForApi($lang));

        return response()->json(['items' => $items]);
    }

    public function apiShow(Language $language): JsonResponse
    {
        return response()->json(['item' => $this->formatLanguageForApi($language)]);
    }

    public function apiStore(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'code' => ['nullable', 'string', 'max:10', 'alpha_dash'],
            'native_name' => ['nullable', 'string', 'max:100'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $code = trim((string) ($data['code'] ?? ''));
        if ($code === '') {
            $code = $this->i18n->resolveUniqueCode($data['name']);
        } else {
            $code = strtolower($code);
            if (Language::query()->where('code', $code)->exists()) {
                $code = $this->i18n->resolveUniqueCode($data['name']);
            }
        }

        $language = Language::create([
            'name' => $data['name'],
            'code' => $code,
            'native_name' => $data['native_name'] ?? null,
            'is_active' => $request->boolean('is_active', true),
            'sort_order' => (int) ($data['sort_order'] ?? 0),
        ]);

        $translate = $this->i18n->autoTranslateLanguage($language);

        return response()->json([
            'success' => true,
            'message' => ($translate['message'] ?? 'Language created.').' (Code: '.$language->code.')',
            'item' => $this->formatLanguageForApi($language),
            'translate_success' => (bool) ($translate['success'] ?? false),
        ], 201);
    }

    public function apiUpdate(Request $request, Language $language): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'code' => ['required', 'string', 'max:10', 'alpha_dash', 'unique:languages,code,'.$language->id],
            'native_name' => ['nullable', 'string', 'max:100'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $language->update([
            'name' => $data['name'],
            'code' => strtolower($data['code']),
            'native_name' => $data['native_name'] ?? null,
            'is_active' => $request->boolean('is_active', true),
            'sort_order' => (int) ($data['sort_order'] ?? 0),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Language updated successfully.',
            'item' => $this->formatLanguageForApi($language->fresh()),
        ]);
    }

    public function apiDestroy(Language $language): JsonResponse
    {
        $language->delete();

        return response()->json([
            'success' => true,
            'message' => 'Language deleted.',
        ]);
    }

    public function apiAutoTranslate(Language $language): JsonResponse
    {
        $result = $this->i18n->autoTranslateLanguage($language);

        return response()->json([
            'success' => (bool) ($result['success'] ?? false),
            'message' => $result['message'] ?? 'Auto-translate completed.',
        ], ($result['success'] ?? false) ? 200 : 422);
    }

    public function apiTranslations(Language $language): JsonResponse
    {
        $definitions = $this->i18n->keyDefinitions();
        $groups = $this->i18n->groupedLabels();
        $saved = $language->registrationTranslations()->pluck('value', 'translation_key')->all();

        $grouped = [];
        foreach ($definitions as $key => $meta) {
            $groupKey = $meta['group'] ?? 'common';
            $grouped[$groupKey][] = [
                'key' => $key,
                'label' => $meta['label'] ?? $key,
                'default' => $meta['default'] ?? '',
                'value' => $saved[$key] ?? ($meta['default'] ?? ''),
            ];
        }

        return response()->json([
            'language' => $this->formatLanguageForApi($language),
            'groups' => $groups,
            'grouped' => $grouped,
        ]);
    }

    public function apiUpdateTranslations(Request $request, Language $language): JsonResponse
    {
        $request->validate([
            'translations' => ['nullable', 'array'],
        ]);

        $this->i18n->saveTranslations($language, $request->input('translations', []));

        return response()->json([
            'success' => true,
            'message' => 'Registration form translations saved.',
        ]);
    }

    public function apiResetTranslations(Language $language): JsonResponse
    {
        $result = $this->i18n->autoTranslateLanguage($language);

        return response()->json([
            'success' => (bool) ($result['success'] ?? false),
            'message' => $result['message'] ?? 'Translations reset.',
        ], ($result['success'] ?? false) ? 200 : 422);
    }

    /** @return array<string, mixed> */
    private function formatLanguageForApi(Language $language): array
    {
        return [
            'id' => $language->id,
            'name' => $language->name,
            'code' => $language->code,
            'native_name' => $language->native_name,
            'display_label' => $language->displayLabel(),
            'is_active' => (bool) $language->is_active,
            'sort_order' => (int) ($language->sort_order ?? 0),
        ];
    }
}
