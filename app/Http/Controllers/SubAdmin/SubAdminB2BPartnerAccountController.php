<?php

namespace App\Http\Controllers\SubAdmin;

use App\Http\Controllers\Controller;
use App\Models\B2BReferenceUser;
use App\Models\B2BUser;
use App\Models\DoctorConsultationService;
use App\Models\Service;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class SubAdminB2BPartnerAccountController extends Controller
{
    public function __construct()
    {
        // Corporate
        $this->middleware('can:view_b2b_corporate')->only(['indexCorporate']);
        $this->middleware('can:create_b2b_corporate')->only(['storeCorporate']);
        $this->middleware('can:edit_b2b_corporate')->only(['updateCorporate']);
        $this->middleware('can:delete_b2b_corporate')->only(['destroyCorporate']);
        
        // Individual
        $this->middleware('can:view_b2b_individual')->only(['indexIndividual']);
        $this->middleware('can:create_b2b_individual')->only(['storeIndividual']);
        $this->middleware('can:edit_b2b_individual')->only(['updateIndividual']);
        $this->middleware('can:delete_b2b_individual')->only(['destroyIndividual']);
    }

    public function indexCorporate()
    {
        return $this->indexPage(B2BUser::TYPE_CORPORATE);
    }

    public function indexIndividual()
    {
        return $this->indexPage(B2BUser::TYPE_INDIVIDUAL);
    }

    public function storeCorporate(Request $request)
    {
        return $this->storeAccount($request, B2BUser::TYPE_CORPORATE);
    }

    public function storeIndividual(Request $request)
    {
        return $this->storeAccount($request, B2BUser::TYPE_INDIVIDUAL);
    }

    public function updateCorporate(Request $request, B2BUser $b2bUser)
    {
        $this->ensureAccountType($b2bUser, B2BUser::TYPE_CORPORATE);

        return $this->updateAccount($request, $b2bUser, B2BUser::TYPE_CORPORATE);
    }

    public function updateIndividual(Request $request, B2BUser $b2bUser)
    {
        $this->ensureAccountType($b2bUser, B2BUser::TYPE_INDIVIDUAL);

        return $this->updateAccount($request, $b2bUser, B2BUser::TYPE_INDIVIDUAL);
    }

    public function destroyCorporate(B2BUser $b2bUser)
    {
        $this->ensureAccountType($b2bUser, B2BUser::TYPE_CORPORATE);

        return $this->destroyAccount($b2bUser, B2BUser::TYPE_CORPORATE);
    }

    public function destroyIndividual(B2BUser $b2bUser)
    {
        $this->ensureAccountType($b2bUser, B2BUser::TYPE_INDIVIDUAL);

        return $this->destroyAccount($b2bUser, B2BUser::TYPE_INDIVIDUAL);
    }

    protected function indexPage(string $accountType): \Illuminate\View\View
    {
        $isCorporate = $accountType === B2BUser::TYPE_CORPORATE;
        $query = $isCorporate ? B2BUser::corporate() : B2BUser::individual();

        return view('subadmin.b2b_partner_accounts.index', [
            'page_heading' => $isCorporate ? 'B2B Corporate Partners' : 'Individual Partners',
            'account_type' => $accountType,
            'is_corporate' => $isCorporate,
            'items' => $query->with(['referenceUser:id,name,mobile', 'chatPeers:id,f_name,l_name'])->orderByDesc('id')->get(),
            'serviceOptions' => $this->serviceOptions(),
            'referenceUsers' => $this->referenceUsersList(),
            'chatStaffUsers' => $this->chatStaffUsers(),
        ]);
    }

    protected function storeAccount(Request $request, string $accountType): \Illuminate\Http\RedirectResponse
    {
        $data = $this->validatePayload($request, $accountType);
        $data['account_type'] = $accountType;
        $data['is_active'] = true;
        $data = $this->attachUploadedFiles($request, $data, null);

        $user = B2BUser::create($data);
        $this->syncChatPeers($request, $user, $accountType);

        return $this->redirectWithSuccess($accountType, 'Partner account created successfully.');
    }

    protected function updateAccount(Request $request, B2BUser $b2bUser, string $accountType): \Illuminate\Http\RedirectResponse
    {
        $data = $this->validatePayload($request, $accountType, $b2bUser->id);
        $data = $this->attachUploadedFiles($request, $data, $b2bUser);

        $b2bUser->update($data);
        $this->syncChatPeers($request, $b2bUser->fresh(), $accountType);

        return $this->redirectWithSuccess($accountType, 'Partner account updated successfully.');
    }

    protected function destroyAccount(B2BUser $b2bUser, string $accountType): \Illuminate\Http\RedirectResponse
    {
        $paths = [];
        foreach (['company_registration_file', 'company_gst_file', 'mou_file'] as $field) {
            if (! empty($b2bUser->{$field})) {
                $paths[] = $b2bUser->{$field};
            }
        }

        $b2bUser->chatPeers()->detach();
        $b2bUser->delete();

        foreach ($paths as $path) {
            if ($path && Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }
        }

        return $this->redirectWithSuccess($accountType, 'Partner account deleted.');
    }

    protected function ensureAccountType(B2BUser $user, string $expected): void
    {
        if ($user->account_type !== $expected) {
            abort(404);
        }
    }

    protected function validatePayload(Request $request, string $accountType, ?int $ignoreId = null): array
    {
        $isCorporate = $accountType === B2BUser::TYPE_CORPORATE;

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'company_name' => ['required', 'string', 'max:255'],
            'mobile' => [
                $isCorporate ? 'nullable' : 'required',
                'string',
                'regex:/^[0-9]{10}$/',
                Rule::unique('b2b_users', 'mobile')->ignore($ignoreId),
            ],
            'b2b_reference_user_id' => ['nullable', 'integer', 'exists:b2b_reference_users,id'],
            'commission_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'service_requirement' => ['nullable', 'string', 'max:255'],
            'bulk_requirement_qty' => ['nullable', 'integer', 'min:0'],
            'bank_account_details' => ['nullable', 'string', 'max:2000'],
            'account_holder_name' => ['nullable', 'string', 'max:255'],
            'bank_name' => ['nullable', 'string', 'max:255'],
            'ifsc_code' => ['nullable', 'string', 'max:32'],
            'account_number' => ['nullable', 'string', 'max:64'],
            'company_registration_file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:10240'],
            'company_gst_file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:10240'],
            'mou_file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:10240'],
            'chat_enabled' => ['nullable', 'boolean'],
            'chat_group_name' => ['nullable', 'string', 'max:120'],
            'chat_peer_user_ids' => ['nullable', 'array'],
            'chat_peer_user_ids.*' => ['integer', 'exists:users,id'],
        ];

        $data = $request->validate($rules);

        $data['chat_enabled'] = $isCorporate && $request->boolean('chat_enabled');
        if (! $isCorporate) {
            $data['chat_enabled'] = false;
        }

        if (empty($data['mobile'])) {
            $data['mobile'] = null;
        }

        if (empty($data['b2b_reference_user_id'])) {
            $data['b2b_reference_user_id'] = null;
        }

        if (! $isCorporate) {
            $data['service_requirement'] = null;
        } elseif (empty($data['service_requirement'])) {
            $data['service_requirement'] = null;
        }

        // Stored via b2b_user_chat_peers pivot — not a column on b2b_users.
        unset($data['chat_peer_user_ids']);

        return $data;
    }

    protected function syncChatPeers(Request $request, B2BUser $user, string $accountType): void
    {
        if ($accountType !== B2BUser::TYPE_CORPORATE) {
            $user->chatPeers()->detach();
            $user->update(['chat_enabled' => false]);

            return;
        }

        if (! $user->chat_enabled) {
            $user->chatPeers()->detach();

            return;
        }

        $ids = collect($request->input('chat_peer_user_ids', []))
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values()
            ->all();

        $user->chatPeers()->sync($ids);
    }

    protected function attachUploadedFiles(Request $request, array $data, ?B2BUser $existing): array
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

    protected function redirectWithSuccess(string $accountType, string $message): \Illuminate\Http\RedirectResponse
    {
        $route = $accountType === B2BUser::TYPE_CORPORATE
            ? 'admin.b2b_corporate.index'
            : 'admin.b2b_individual.index';

        return redirect()->route($route)->with('status', [
            'alert_type' => 'success',
            'message' => $message,
        ]);
    }

    protected function referenceUsersList(): array
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

    protected function chatStaffUsers(): array
    {
        return User::query()
            ->with('role:id,name')
            ->whereHas('role', function ($q) {
                $q->whereIn('name', [
                    'Admin',
                    'Sales',
                    'Sales Manager',
                    'Operation',
                    'Operation Manager',
                    'Manager',
                ]);
            })
            ->orderBy('f_name')
            ->orderBy('l_name')
            ->get(['id', 'f_name', 'l_name', 'role_id'])
            ->map(fn (User $u) => [
                'id' => $u->id,
                'name' => trim($u->name) . ($u->role ? ' (' . $u->role->name . ')' : ''),
            ])
            ->values()
            ->all();
    }

    protected function serviceOptions(): array
    {
        $regular = Service::query()
            ->select('id', 'name')
            ->orderBy('name')
            ->get()
            ->map(fn (Service $s) => [
                'id' => 'service:' . $s->id,
                'name' => trim((string) $s->name),
            ]);

        $doctor = DoctorConsultationService::query()
            ->select('id', 'name')
            ->orderBy('name')
            ->get()
            ->map(fn (DoctorConsultationService $s) => [
                'id' => 'doctor_consultation:' . $s->id,
                'name' => trim((string) $s->name),
            ]);

        return $regular
            ->merge($doctor)
            ->filter(fn (array $row) => $row['name'] !== '')
            ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
            ->values()
            ->all();
    }
}
