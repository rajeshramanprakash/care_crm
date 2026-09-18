<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\B2BReferenceUser;
use App\Models\B2BUser;
use App\Models\DoctorConsultationService;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class B2BUserController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:view_b2b_users')->only(['index', 'apiIndex']);
        $this->middleware('can:create_b2b_users')->only(['store', 'apiStore']);
        $this->middleware('can:edit_b2b_users')->only(['update', 'apiUpdate']);
        $this->middleware('can:delete_b2b_users')->only(['destroy', 'apiDestroy']);
    }

    public function index()
    {
        $page_heading = 'B2B Users';
        $items = B2BUser::legacy()->with('referenceUser:id,name,mobile')->orderByDesc('id')->get();
        $serviceOptions = $this->serviceOptions();
        $referenceUsers = $this->referenceUsersList();
        $referenceUserRecords = B2BReferenceUser::query()->orderByDesc('id')->get();

        return view('admin.b2b_users.index', compact('page_heading', 'items', 'serviceOptions', 'referenceUsers', 'referenceUserRecords'));
    }

    public function store(Request $request)
    {
        $data = $this->validatePayload($request);
        $data = $this->attachUploadedFiles($request, $data, null);

        $data['account_type'] = B2BUser::TYPE_LEGACY;
        $data['is_active'] = $data['is_active'] ?? true;

        B2BUser::create($data);

        return redirect()->route('admin.b2b_users.index')->with('status', [
            'alert_type' => 'success',
            'message' => 'B2B user created successfully.',
        ]);
    }

    public function update(Request $request, B2BUser $b2bUser)
    {
        $data = $this->validatePayload($request, $b2bUser->id);
        $data = $this->attachUploadedFiles($request, $data, $b2bUser);

        $b2bUser->update($data);

        return redirect()->route('admin.b2b_users.index')->with('status', [
            'alert_type' => 'success',
            'message' => 'B2B user updated successfully.',
        ]);
    }

    public function destroy(B2BUser $b2bUser)
    {
        $this->deleteB2BUserAndStoredFiles($b2bUser);

        return redirect()->route('admin.b2b_users.index')->with('status', [
            'alert_type' => 'success',
            'message' => 'B2B user deleted. Their B2B portal leads were removed as well.',
        ]);
    }

    public function apiIndex()
    {
        return response()->json([
            'items' => B2BUser::legacy()->with('referenceUser:id,name,mobile')->orderByDesc('id')->get(),
            'service_options' => $this->serviceOptions(),
            'referral_users' => $this->referenceUsersList(),
            'referral_user_records' => B2BReferenceUser::query()
                ->orderByDesc('id')
                ->get(['id', 'name', 'mobile', 'created_at']),
        ]);
    }

    public function apiStore(Request $request)
    {
        $data = $this->validatePayload($request);
        $data = $this->attachUploadedFiles($request, $data, null);

        $item = B2BUser::create($data);

        return response()->json([
            'success' => true,
            'message' => 'B2B user created successfully.',
            'item' => $item,
        ]);
    }

    public function apiUpdate(Request $request, B2BUser $b2bUser)
    {
        $data = $this->validatePayload($request, $b2bUser->id);
        $data = $this->attachUploadedFiles($request, $data, $b2bUser);
        $b2bUser->update($data);

        return response()->json([
            'success' => true,
            'message' => 'B2B user updated successfully.',
            'item' => $b2bUser->fresh('referenceUser:id,name,mobile'),
        ]);
    }

    public function apiDestroy(B2BUser $b2bUser)
    {
        $this->deleteB2BUserAndStoredFiles($b2bUser);

        return response()->json([
            'success' => true,
            'message' => 'B2B user deleted. Related B2B leads were removed.',
        ]);
    }

    /**
     * Remove DB row first (FK cascade clears b2b_leads), then storage files captured before delete.
     */
    private function deleteB2BUserAndStoredFiles(B2BUser $user): void
    {
        $paths = [];
        foreach (['company_registration_file', 'company_gst_file', 'mou_file'] as $field) {
            if (! empty($user->{$field})) {
                $paths[] = $user->{$field};
            }
        }

        $user->delete();

        foreach ($paths as $path) {
            if ($path && Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }
        }
    }

    private function validatePayload(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'company_name' => ['required', 'string', 'max:255'],
            'mobile' => ['required', 'string', 'regex:/^[0-9]{10}$/', Rule::unique('b2b_users', 'mobile')->ignore($ignoreId)],
            'b2b_reference_user_id' => ['required', 'integer', 'exists:b2b_reference_users,id'],
            'commission_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'service_requirement' => ['required', 'string', 'max:255'],
            'bank_account_details' => ['nullable', 'string', 'max:2000'],
            'account_holder_name' => ['nullable', 'string', 'max:255'],
            'bank_name' => ['nullable', 'string', 'max:255'],
            'ifsc_code' => ['nullable', 'string', 'max:32'],
            'account_number' => ['nullable', 'string', 'max:64'],
            'company_registration_file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:10240'],
            'company_gst_file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:10240'],
            'mou_file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:10240'],
        ]);
    }

    private function attachUploadedFiles(Request $request, array $data, ?B2BUser $existing): array
    {
        foreach (['company_registration_file', 'company_gst_file', 'mou_file'] as $fileField) {
            if ($request->hasFile($fileField)) {
                $data[$fileField] = $request->file($fileField)->store('b2b-documents', 'public');
            } elseif ($existing) {
                $data[$fileField] = $existing->{$fileField};
            }
        }

        return $data;
    }

    /** List for admin + app dropdowns (key still `referral_users` in JSON for app compatibility). */
    private function referenceUsersList(): array
    {
        return B2BReferenceUser::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->orderBy('mobile')
            ->get()
            ->map(fn (B2BReferenceUser $r) => [
                'id' => $r->id,
                'name' => trim($r->name) . ' · ' . $r->mobile,
            ])
            ->values()
            ->all();
    }

    private function serviceOptions(): array
    {
        $regular = Service::query()
            ->select('id', 'name')
            ->orderBy('name')
            ->get()
            ->map(fn (Service $s) => [
                'id' => 'service:' . $s->id,
                'name' => trim((string) $s->name),
                'source' => 'service',
            ]);

        $doctor = DoctorConsultationService::query()
            ->select('id', 'name')
            ->orderBy('name')
            ->get()
            ->map(fn (DoctorConsultationService $s) => [
                'id' => 'doctor_consultation:' . $s->id,
                'name' => trim((string) $s->name),
                'source' => 'doctor_consultation_service',
            ]);

        return $regular
            ->merge($doctor)
            ->filter(fn (array $row) => $row['name'] !== '')
            ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
            ->values()
            ->all();
    }
}
