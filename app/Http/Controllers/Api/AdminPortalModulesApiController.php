<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\Concerns\HandlesPartnerUserDocuments;
use App\Http\Controllers\Controller;
use App\Models\B2BReferenceUser;
use App\Models\B2BUser;
use App\Models\BrokerUser;
use App\Models\ConsultationWebsiteBooking;
use App\Models\CorporateEmployee;
use App\Models\CorporateUser;
use App\Models\DeploymentLocationAttendance;
use App\Models\DoctorRegistrationOtpLog;
use App\Models\EasebuzzPaymentLink;
use App\Models\InsurerUser;
use App\Models\Language;
use App\Models\Location;
use App\Models\OperationDeploymentDetails;
use App\Models\Service;
use App\Models\User;
use App\Services\EasebuzzEasyCollectService;
use App\Services\RegistrationI18nService;
use App\Services\RegistrationOtpService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AdminPortalModulesApiController extends Controller
{
    use HandlesPartnerUserDocuments;

    public function __construct(
        private readonly EasebuzzEasyCollectService $easyCollect,
        private readonly RegistrationI18nService $i18n,
        private readonly AdminController $adminController,
    ) {}

    private function ensureAdmin(): void
    {
        $roleIds = array_filter(explode(',', (string) (Auth::user()->role_id ?? '')));
        if (! in_array('1', $roleIds, true)) {
            abort(403, 'Admin access required');
        }
    }

    // ─── Easebuzz Payments ───────────────────────────────────────────────

    public function easebuzzPaymentsIndex(Request $request): JsonResponse
    {
        $this->ensureAdmin();

        $query = EasebuzzPaymentLink::query()->with('createdBy:id,f_name,l_name');

        $status = trim((string) $request->query('status', ''));
        if ($status !== '' && in_array($status, ['pending', 'paid', 'failed', 'expired'], true)) {
            $query->where('status', $status);
        }

        $q = trim((string) $request->query('q', ''));
        if ($q !== '') {
            $like = '%'.$q.'%';
            $query->where(function ($w) use ($like, $q) {
                $w->where('customer_name', 'like', $like)
                    ->orWhere('email', 'like', $like)
                    ->orWhere('phone', 'like', $like)
                    ->orWhere('merchant_txn', 'like', $like);
                if (ctype_digit($q)) {
                    $w->orWhere('id', (int) $q);
                }
            });
        }

        $statsQuery = EasebuzzPaymentLink::query();
        $stats = [
            'pending' => (clone $statsQuery)->where('status', EasebuzzPaymentLink::STATUS_PENDING)->count(),
            'paid' => (clone $statsQuery)->where('status', EasebuzzPaymentLink::STATUS_PAID)->count(),
            'total' => (clone $statsQuery)->count(),
        ];

        $payments = $query->orderByDesc('created_at')->paginate(15);

        return response()->json([
            'stats' => $stats,
            'demo_mode' => $this->easyCollect->isDemoMode(),
            'configured' => $this->easyCollect->isConfigured(),
            'items' => $payments,
        ]);
    }

    public function easebuzzPaymentsShow(EasebuzzPaymentLink $payment): JsonResponse
    {
        $this->ensureAdmin();
        $payment->load('createdBy:id,f_name,l_name');

        if ($payment->status === EasebuzzPaymentLink::STATUS_PENDING && $payment->isExpired()) {
            $payment->update(['status' => EasebuzzPaymentLink::STATUS_EXPIRED]);
            $payment->refresh();
        }

        return response()->json(['payment' => $payment]);
    }

    public function easebuzzPaymentsStore(Request $request): JsonResponse
    {
        $this->ensureAdmin();

        $validated = $request->validate([
            'customer_name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'required|string|max:20',
            'message' => 'nullable|string|max:500',
            'amount' => 'required|numeric|min:0.01',
        ]);

        if (! $this->easyCollect->isConfigured()) {
            return response()->json([
                'success' => false,
                'message' => 'Easebuzz is not configured. Check EASEBUZZ_KEY / EASEBUZZ_SALT in .env.',
            ], 422);
        }

        $merchantTxn = 'TXN'.time().Str::upper(Str::random(4));
        $result = $this->easyCollect->createPaymentLink([
            'merchant_txn' => $merchantTxn,
            'name' => $validated['customer_name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'amount' => $validated['amount'],
            'message' => $validated['message'] ?? '',
        ]);

        if (! ($result['ok'] ?? false)) {
            return response()->json([
                'success' => false,
                'message' => $result['error'] ?? 'Could not create payment link.',
            ], 422);
        }

        $expireAt = isset($result['expire_by'])
            ? Carbon::createFromTimestamp((int) $result['expire_by'])
            : now()->addDays((int) config('services.easycollect.link_validity_days', 7));

        $payment = EasebuzzPaymentLink::create([
            'created_by' => Auth::id(),
            'merchant_txn' => $merchantTxn,
            'customer_name' => $validated['customer_name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'message' => $validated['message'] ?? null,
            'amount' => $validated['amount'],
            'payment_url' => $result['payment_url'],
            'expire_at' => $expireAt,
            'status' => EasebuzzPaymentLink::STATUS_PENDING,
            'easebuzz_create_response' => $result['raw'] ?? null,
        ]);

        return response()->json(['success' => true, 'payment' => $payment->load('createdBy:id,f_name,l_name')], 201);
    }

    public function easebuzzPaymentsVerify(EasebuzzPaymentLink $payment): JsonResponse
    {
        $this->ensureAdmin();

        if ($payment->status === EasebuzzPaymentLink::STATUS_PAID) {
            return response()->json([
                'success' => true,
                'message' => 'Payment is already marked as paid.',
                'payment' => $payment,
            ]);
        }

        $result = $this->easyCollect->retrieveTransaction(
            $payment->merchant_txn,
            (string) $payment->amount,
            $payment->email,
            $payment->phone
        );

        $update = [
            'verified_at' => now(),
            'easebuzz_verify_response' => $result['raw'] ?? null,
        ];

        if ($result['ok'] ?? false) {
            $newStatus = $result['status'] ?? EasebuzzPaymentLink::STATUS_PENDING;
            $update['status'] = $newStatus;
            if ($newStatus === EasebuzzPaymentLink::STATUS_PAID) {
                $update['paid_at'] = now();
            }
            $payment->update($update);
            $payment->refresh();

            return response()->json([
                'success' => $newStatus === EasebuzzPaymentLink::STATUS_PAID,
                'message' => $newStatus === EasebuzzPaymentLink::STATUS_PAID
                    ? 'Payment verified — marked as paid.'
                    : 'Payment not completed yet.',
                'payment' => $payment,
            ]);
        }

        $payment->update($update);

        return response()->json([
            'success' => false,
            'message' => $result['error'] ?? 'Payment not completed yet.',
            'payment' => $payment->refresh(),
        ]);
    }

    // ─── B2B Corporate / Individual Partners ───────────────────────────────

    public function b2bPartnersIndex(Request $request): JsonResponse
    {
        $this->ensureAdmin();

        $type = $request->query('type', B2BUser::TYPE_CORPORATE);
        if (! in_array($type, [B2BUser::TYPE_CORPORATE, B2BUser::TYPE_INDIVIDUAL], true)) {
            return response()->json(['message' => 'Invalid partner type'], 422);
        }

        $query = $type === B2BUser::TYPE_CORPORATE ? B2BUser::corporate() : B2BUser::individual();

        $items = $query
            ->with(['referenceUser:id,name,mobile', 'chatPeers:id,f_name,l_name'])
            ->orderByDesc('id')
            ->get();

        return response()->json([
            'account_type' => $type,
            'items' => $items,
            'service_options' => $this->b2bServiceOptions(),
            'reference_users' => $this->b2bReferenceUsersList(),
            'chat_staff_users' => $this->b2bChatStaffUsers(),
        ]);
    }

    public function b2bPartnersStore(Request $request): JsonResponse
    {
        $this->ensureAdmin();
        $type = $request->input('account_type', B2BUser::TYPE_CORPORATE);

        return $this->saveB2bPartner($request, $type);
    }

    public function b2bPartnersUpdate(Request $request, B2BUser $b2bUser): JsonResponse
    {
        $this->ensureAdmin();
        $type = $b2bUser->account_type;
        if (! in_array($type, [B2BUser::TYPE_CORPORATE, B2BUser::TYPE_INDIVIDUAL], true)) {
            abort(404);
        }

        return $this->saveB2bPartner($request, $type, $b2bUser);
    }

    public function b2bPartnersDestroy(B2BUser $b2bUser): JsonResponse
    {
        $this->ensureAdmin();
        if (! in_array($b2bUser->account_type, [B2BUser::TYPE_CORPORATE, B2BUser::TYPE_INDIVIDUAL], true)) {
            abort(404);
        }

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

        return response()->json(['success' => true, 'message' => 'Partner account deleted.']);
    }

    private function saveB2bPartner(Request $request, string $accountType, ?B2BUser $existing = null): JsonResponse
    {
        $isCorporate = $accountType === B2BUser::TYPE_CORPORATE;
        $ignoreId = $existing?->id;

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
            'chat_enabled' => ['nullable', 'boolean'],
            'chat_group_name' => ['nullable', 'string', 'max:120'],
            'chat_peer_user_ids' => ['nullable', 'array'],
            'chat_peer_user_ids.*' => ['integer', 'exists:users,id'],
            'company_registration_file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:10240'],
            'company_gst_file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:10240'],
            'mou_file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:10240'],
        ];

        $data = $request->validate($rules);
        $data['account_type'] = $accountType;
        $data['chat_enabled'] = $isCorporate && $request->boolean('chat_enabled');
        if (! $isCorporate) {
            $data['chat_enabled'] = false;
            $data['service_requirement'] = null;
        }
        $data['mobile'] = empty($data['mobile']) ? null : $data['mobile'];
        $data['b2b_reference_user_id'] = empty($data['b2b_reference_user_id']) ? null : $data['b2b_reference_user_id'];
        if ($isCorporate && empty($data['service_requirement'])) {
            $data['service_requirement'] = null;
        }

        $peerIds = collect($request->input('chat_peer_user_ids', []))->map(fn ($id) => (int) $id)->filter()->unique()->values()->all();
        unset($data['chat_peer_user_ids']);

        foreach (['company_registration_file', 'company_gst_file', 'mou_file'] as $fileField) {
            if ($request->hasFile($fileField)) {
                $data[$fileField] = $request->file($fileField)->store('b2b-documents', 'public');
            } elseif ($existing) {
                $data[$fileField] = $existing->{$fileField};
            } else {
                unset($data[$fileField]);
            }
        }

        if ($existing) {
            $existing->update($data);
            $user = $existing->fresh();
        } else {
            $data['is_active'] = true;
            $user = B2BUser::create($data);
        }

        if ($isCorporate && $user->chat_enabled) {
            $user->chatPeers()->sync($peerIds);
        } else {
            $user->chatPeers()->detach();
        }

        return response()->json([
            'success' => true,
            'item' => $user->load(['referenceUser:id,name,mobile', 'chatPeers:id,f_name,l_name']),
        ], $existing ? 200 : 201);
    }

    private function b2bServiceOptions(): array
    {
        $services = Service::query()->orderBy('name')->get(['id', 'name'])->map(fn ($s) => [
            'id' => 'service:'.$s->id,
            'name' => $s->name,
            'source' => 'service',
        ]);

        $doctor = \App\Models\DoctorConsultationService::query()->orderBy('name')->get(['id', 'name'])->map(fn ($s) => [
            'id' => 'doctor_consultation_service:'.$s->id,
            'name' => $s->name,
            'source' => 'doctor_consultation_service',
        ]);

        return $services->concat($doctor)->values()->all();
    }

    private function b2bReferenceUsersList(): array
    {
        return B2BReferenceUser::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'mobile'])
            ->map(fn (B2BReferenceUser $r) => [
                'id' => $r->id,
                'name' => trim($r->name).' · '.$r->mobile,
                'mobile' => $r->mobile,
            ])
            ->all();
    }

    private function b2bChatStaffUsers(): array
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
                'name' => trim($u->f_name.' '.$u->l_name).($u->role ? ' ('.$u->role->name.')' : ''),
            ])
            ->all();
    }

    // ─── Insurer / Broker / Corporate accounts ─────────────────────────────

    public function insurersIndex(): JsonResponse
    {
        $this->ensureAdmin();

        $items = InsurerUser::query()
            ->with(['corporateUsers' => fn ($q) => $q->withCount('employees')->orderByDesc('id')])
            ->withCount('corporateUsers')
            ->orderByDesc('id')
            ->get()
            ->map(fn (InsurerUser $item) => $this->formatInsurerUser($item));

        return response()->json(['items' => $items]);
    }

    public function insurersShow(InsurerUser $insurer): JsonResponse
    {
        $this->ensureAdmin();
        $insurer->load(['corporateUsers' => fn ($q) => $q->withCount('employees')->orderByDesc('id')])
            ->loadCount('corporateUsers');

        return response()->json(['item' => $this->formatInsurerUser($insurer)]);
    }

    public function insurersStore(Request $request): JsonResponse
    {
        $this->ensureAdmin();
        $data = $this->validateInsurerPayload($request);
        $item = InsurerUser::create($data);
        $this->processPartnerDocuments($request, $item, 'insurer');

        return response()->json([
            'success' => true,
            'item' => $this->formatInsurerUser($this->reloadInsurerUser($item)),
        ], 201);
    }

    public function insurersUpdate(Request $request, InsurerUser $insurer): JsonResponse
    {
        $this->ensureAdmin();
        $data = $this->validateInsurerPayload($request, $insurer->id);
        if (empty($data['password'])) {
            unset($data['password']);
        }
        $insurer->update($data);
        $this->processPartnerDocuments($request, $insurer->fresh(), 'insurer');

        return response()->json([
            'success' => true,
            'item' => $this->formatInsurerUser($this->reloadInsurerUser($insurer)),
        ]);
    }

    public function insurersDestroy(InsurerUser $insurer): JsonResponse
    {
        $this->ensureAdmin();
        $this->deletePartnerUserFiles($insurer);
        $insurer->delete();

        return response()->json(['success' => true, 'message' => 'Insurer account removed.']);
    }

    public function brokersIndex(): JsonResponse
    {
        $this->ensureAdmin();

        $items = BrokerUser::query()
            ->with(['corporateUsers' => fn ($q) => $q->withCount('employees')->orderByDesc('id')])
            ->withCount('corporateUsers')
            ->orderByDesc('id')
            ->get()
            ->map(fn (BrokerUser $item) => $this->formatBrokerUser($item));

        return response()->json(['items' => $items]);
    }

    public function brokersShow(BrokerUser $broker): JsonResponse
    {
        $this->ensureAdmin();
        $broker->load(['corporateUsers' => fn ($q) => $q->withCount('employees')->orderByDesc('id')])
            ->loadCount('corporateUsers');

        return response()->json(['item' => $this->formatBrokerUser($broker)]);
    }

    public function brokersStore(Request $request): JsonResponse
    {
        $this->ensureAdmin();
        $data = $this->validateBrokerPayload($request);
        $item = BrokerUser::create($data);
        $this->processPartnerDocuments($request, $item, 'broker');

        return response()->json([
            'success' => true,
            'item' => $this->formatBrokerUser($this->reloadBrokerUser($item)),
        ], 201);
    }

    public function brokersUpdate(Request $request, BrokerUser $broker): JsonResponse
    {
        $this->ensureAdmin();
        $data = $this->validateBrokerPayload($request, $broker->id);
        if (empty($data['password'])) {
            unset($data['password']);
        }
        $broker->update($data);
        $this->processPartnerDocuments($request, $broker->fresh(), 'broker');

        return response()->json([
            'success' => true,
            'item' => $this->formatBrokerUser($this->reloadBrokerUser($broker)),
        ]);
    }

    public function brokersDestroy(BrokerUser $broker): JsonResponse
    {
        $this->ensureAdmin();
        $this->deletePartnerUserFiles($broker);
        $broker->delete();

        return response()->json(['success' => true, 'message' => 'Broker account removed.']);
    }

    public function corporateAccountsIndex(Request $request): JsonResponse
    {
        $this->ensureAdmin();

        $query = $this->corporateAccountsQuery($request);
        $items = $query->paginate(25);
        $items->getCollection()->transform(fn (CorporateUser $item) => $this->formatCorporateAccount($item));

        $totalEmployees = (int) $this->corporateAccountsQuery($request)
            ->withCount('employees')
            ->get()
            ->sum('employees_count');

        return response()->json([
            'items' => $items,
            'total_employees' => $totalEmployees,
        ]);
    }

    public function corporateEmployeesIndex(Request $request): JsonResponse
    {
        $this->ensureAdmin();

        $query = CorporateEmployee::query()
            ->with([
                'corporate' => fn ($q) => $q->with([
                    'insurer:id,name,company_name',
                    'broker:id,name,company_name',
                ]),
            ])
            ->orderByDesc('id');

        if ($request->filled('corporate_id')) {
            $query->where('corporate_user_id', $request->integer('corporate_id'));
        }
        if ($request->filled('q')) {
            $like = '%'.$request->string('q').'%';
            $query->where(function ($sub) use ($like) {
                $sub->where('employee_id', 'like', $like)
                    ->orWhere('employee_name', 'like', $like)
                    ->orWhere('email', 'like', $like)
                    ->orWhere('phone_number', 'like', $like);
            });
        }

        $items = $query->paginate(25);
        $items->getCollection()->transform(fn (CorporateEmployee $item) => $this->formatCorporateEmployee($item));

        return response()->json(['items' => $items]);
    }

    public function corporateEmployeesShow(CorporateEmployee $corporateEmployee): JsonResponse
    {
        $this->ensureAdmin();
        $corporateEmployee->load([
            'corporate' => fn ($q) => $q->with([
                'insurer:id,name,company_name',
                'broker:id,name,company_name',
            ]),
        ]);

        return response()->json(['item' => $this->formatCorporateEmployee($corporateEmployee, true)]);
    }

    // ─── Locations / Services / Languages ────────────────────────────────

    public function locationsIndex(): JsonResponse
    {
        $this->ensureAdmin();
        $locations = Location::with('services:id,name')->latest()->get();

        return response()->json(['items' => $locations]);
    }

    public function locationsStore(Request $request): JsonResponse
    {
        $this->ensureAdmin();
        $data = $request->validate([
            'name' => 'required|string|max:255|unique:locations',
            'state' => 'required|string|max:100',
            'tier' => 'required|string|max:50',
        ]);
        $location = Location::create($data);

        return response()->json(['success' => true, 'item' => $location], 201);
    }

    public function locationsUpdate(Request $request, Location $location): JsonResponse
    {
        $this->ensureAdmin();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('locations', 'name')->ignore($location->id)],
            'state' => 'required|string|max:100',
            'tier' => 'required|string|max:50',
        ]);
        $location->update($data);

        return response()->json(['success' => true, 'item' => $location->fresh()->load('services:id,name')]);
    }

    public function servicesCatalogIndex(): JsonResponse
    {
        $this->ensureAdmin();
        $services = Service::query()
            ->withCount('subServices')
            ->with(['subServices:id,service_id,name,consultation_duration_minutes,sort_order,is_active'])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return response()->json(['items' => $services]);
    }

    public function servicesCatalogShow(Service $service): JsonResponse
    {
        $this->ensureAdmin();
        $service->load(['subServices' => fn ($q) => $q->orderBy('sort_order')]);

        return response()->json(['item' => $service]);
    }

    public function languagesIndex(): JsonResponse
    {
        $this->ensureAdmin();
        $languages = Language::query()->orderBy('sort_order')->orderBy('name')->get();

        return response()->json(['items' => $languages]);
    }

    public function languagesStore(Request $request): JsonResponse
    {
        $this->ensureAdmin();
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
            'item' => $language,
            'translate_message' => $translate['message'] ?? null,
        ], 201);
    }

    public function languagesUpdate(Request $request, Language $language): JsonResponse
    {
        $this->ensureAdmin();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'native_name' => ['nullable', 'string', 'max:100'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);
        $language->update([
            'name' => $data['name'],
            'native_name' => $data['native_name'] ?? null,
            'is_active' => $request->boolean('is_active', $language->is_active),
            'sort_order' => (int) ($data['sort_order'] ?? $language->sort_order),
        ]);

        return response()->json(['success' => true, 'item' => $language->fresh()]);
    }

    // ─── OTP verify logs ─────────────────────────────────────────────────

    public function otpLogsIndex(Request $request, string $registrationType): JsonResponse
    {
        $this->ensureAdmin();

        if (! in_array($registrationType, RegistrationOtpService::TYPES, true)) {
            return response()->json(['message' => 'Invalid registration type'], 404);
        }

        $query = DoctorRegistrationOtpLog::query()
            ->where('registration_type', $registrationType)
            ->orderByDesc('sent_at');

        $mobile = preg_replace('/\D+/', '', (string) $request->query('mobile', ''));
        if (strlen($mobile) >= 10) {
            $query->where('mobile', substr($mobile, -10));
        }

        if ($request->query('status') === 'verified') {
            $query->whereNotNull('verified_at');
        } elseif ($request->query('status') === 'pending') {
            $query->whereNull('verified_at');
        }

        $logs = $query->paginate(50);

        $labels = ['doctor' => 'Doctor', 'vendor' => 'Vendor', 'freelancer' => 'Freelancer'];

        return response()->json([
            'registration_type' => $registrationType,
            'registration_label' => $labels[$registrationType] ?? $registrationType,
            'items' => $logs,
        ]);
    }

    // ─── Website consultation payments ─────────────────────────────────────

    public function websiteConsultationPaymentsIndex(Request $request): JsonResponse
    {
        $this->ensureAdmin();

        $query = ConsultationWebsiteBooking::query()
            ->with([
                'doctorRequest:id,name',
                'consultationService:id,name',
                'consultationSubService:id,name',
            ]);

        $status = $request->query('payment_status', '');
        if ($status === 'paid') {
            $query->where('payment_status', 'paid');
        } elseif ($status === 'pending_payment') {
            $query->where('payment_status', 'pending_payment');
        } elseif ($status === 'with_fee') {
            $query->whereNotNull('booking_fee_amount')
                ->where('booking_fee_amount', '>', 0);
        }

        $q = trim((string) $request->query('q', ''));
        if ($q !== '') {
            $like = '%'.$q.'%';
            $query->where(function ($w) use ($like, $q) {
                $w->where('customer_name', 'like', $like)
                    ->orWhere('contact_no', 'like', $like)
                    ->orWhere('easebuzz_txnid', 'like', $like)
                    ->orWhere('customer_address', 'like', $like)
                    ->orWhere('customer_city', 'like', $like);
                if (ctype_digit($q)) {
                    $w->orWhere('id', (int) $q);
                }
                $w->orWhereHas('doctorRequest', function ($dr) use ($like) {
                    $dr->where('name', 'like', $like);
                });
            });
        }

        $perPage = (int) $request->query('per_page', 15);
        if (! in_array($perPage, [10, 15, 25, 50], true)) {
            $perPage = 15;
        }

        $bookings = $query->orderByDesc('created_at')->paginate($perPage);
        $bookings->getCollection()->transform(fn (ConsultationWebsiteBooking $b) => $this->formatWebsiteBookingListItem($b));

        return response()->json([
            'items' => $bookings,
            'stats' => [
                'paid_sum' => (float) ConsultationWebsiteBooking::query()->where('payment_status', 'paid')->sum('booking_fee_amount'),
                'paid_this_month' => (float) ConsultationWebsiteBooking::query()
                    ->where('payment_status', 'paid')
                    ->whereNotNull('paid_at')
                    ->where('paid_at', '>=', Carbon::now()->startOfMonth())
                    ->sum('booking_fee_amount'),
                'pending_count' => ConsultationWebsiteBooking::query()->where('payment_status', 'pending_payment')->count(),
            ],
        ]);
    }

    public function websiteConsultationPaymentsShow(ConsultationWebsiteBooking $booking): JsonResponse
    {
        $this->ensureAdmin();
        $booking->load([
            'doctorRequest:id,name,mobile,contact_no,job_title,city',
            'consultationService:id,name,consultation_duration_minutes',
            'consultationSubService:id,name',
        ]);

        return response()->json(['booking' => $this->formatWebsiteBookingDetail($booking)]);
    }

    /** @return array<string, mixed> */
    private function formatWebsiteBookingListItem(ConsultationWebsiteBooking $booking): array
    {
        $tags = is_array($booking->selected_tags)
            ? array_values(array_filter(array_map('strval', $booking->selected_tags)))
            : [];
        $subName = trim((string) ($booking->sub_service_name ?? ''));
        if ($subName === '') {
            $subName = trim((string) ($booking->consultationSubService?->name ?? ''));
        }

        return [
            'id' => $booking->id,
            'created_at' => $booking->created_at?->toIso8601String(),
            'customer_name' => $booking->customer_name,
            'contact_no' => $booking->contact_no,
            'customer_address' => $booking->customer_address,
            'customer_city' => $booking->customer_city,
            'customer_address_lat' => $booking->customer_address_lat !== null ? (float) $booking->customer_address_lat : null,
            'customer_address_lng' => $booking->customer_address_lng !== null ? (float) $booking->customer_address_lng : null,
            'map_url' => ConsultationWebsiteBooking::googleMapsOpenUrl(
                $booking->customer_address_lat !== null ? (float) $booking->customer_address_lat : null,
                $booking->customer_address_lng !== null ? (float) $booking->customer_address_lng : null
            ),
            'doctor_request' => $booking->doctorRequest ? [
                'id' => $booking->doctorRequest->id,
                'name' => $booking->doctorRequest->name,
            ] : null,
            'consultation_service' => $booking->consultationService ? [
                'id' => $booking->consultationService->id,
                'name' => $booking->consultationService->name,
            ] : null,
            'sub_service_name' => $subName !== '' ? $subName : null,
            'selected_tags' => $tags,
            'consultation_mode' => $booking->consultation_mode,
            'consultation_mode_label' => ConsultationWebsiteBooking::modeLabel((string) ($booking->consultation_mode ?? '')),
            'appointment_date' => optional($booking->appointment_date)->format('Y-m-d'),
            'appointment_date_label' => $booking->appointment_date
                ? $booking->appointment_date->format('d M Y')
                : null,
            'appointment_start_time' => $booking->appointment_start_time,
            'appointment_end_time' => $booking->appointment_end_time,
            'time_range_label' => ConsultationWebsiteBooking::timeRangeLabel(
                $booking->appointment_start_time,
                $booking->appointment_end_time
            ),
            'booking_fee_amount' => $booking->booking_fee_amount !== null ? (float) $booking->booking_fee_amount : null,
            'payment_status' => $booking->payment_status,
            'payment_status_label' => ConsultationWebsiteBooking::paymentStatusLabel($booking->payment_status),
            'paid_at' => $booking->paid_at?->toIso8601String(),
            'easebuzz_txnid' => $booking->easebuzz_txnid,
        ];
    }

    /** @return array<string, mixed> */
    private function formatWebsiteBookingDetail(ConsultationWebsiteBooking $booking): array
    {
        $item = $this->formatWebsiteBookingListItem($booking);
        $item['consultation_duration_minutes'] = $booking->consultation_duration_minutes;
        $item['duration_label'] = ConsultationWebsiteBooking::durationLabel(
            $booking->consultation_duration_minutes ?? $booking->consultationService?->consultation_duration_minutes
        );
        $item['online_meeting_link'] = $booking->online_meeting_link;

        if ($booking->consultationService) {
            $item['consultation_service'] = [
                'id' => $booking->consultationService->id,
                'name' => $booking->consultationService->name,
                'consultation_duration_minutes' => $booking->consultationService->consultation_duration_minutes,
            ];
        }

        if ($booking->doctorRequest) {
            $item['doctor_request'] = [
                'id' => $booking->doctorRequest->id,
                'name' => $booking->doctorRequest->name,
                'mobile' => $booking->doctorRequest->mobile,
                'contact_no' => $booking->doctorRequest->contact_no,
                'job_title' => $booking->doctorRequest->job_title,
                'city' => $booking->doctorRequest->city,
            ];
        }

        return $item;
    }

    // ─── Location attendance ───────────────────────────────────────────────

    public function locationAttendanceIndex(Request $request): JsonResponse
    {
        $this->ensureAdmin();

        $likeTerm = static function (string $value): string {
            $t = trim($value);

            return '%'.str_replace(['%', '_'], ['\%', '\_'], $t).'%';
        };

        $query = OperationDeploymentDetails::query()
            ->with([
                'operationLead:id,lead_id,customer_name,contact_no,executive',
                'operationLead.executive:id,f_name,l_name',
                'vendor:id,name',
                'freelanceStaff:id,name',
            ])
            ->where(function ($q) {
                $q->whereNotNull('vendor_id')->orWhereNotNull('freelance_staff_id');
            });

        if ($request->filled('operation_lead_id')) {
            $query->where('operation_lead_id', (int) $request->integer('operation_lead_id'));
        }

        if ($request->filled('customer_q')) {
            $term = $likeTerm($request->string('customer_q')->toString());
            $query->whereHas('operationLead', function ($q) use ($term) {
                $q->where(function ($q2) use ($term) {
                    $q2->where('customer_name', 'like', $term)
                        ->orWhere('contact_no', 'like', $term)
                        ->orWhere('lead_id', 'like', $term);
                });
            });
        }

        if ($request->filled('executive_q')) {
            $term = $likeTerm($request->string('executive_q')->toString());
            $query->whereHas('operationLead', function ($q) use ($term) {
                $q->whereHas('executive', function ($ex) use ($term) {
                    $ex->where('f_name', 'like', $term)
                        ->orWhere('l_name', 'like', $term)
                        ->orWhereRaw(
                            "CONCAT(COALESCE(f_name,''), ' ', COALESCE(l_name,'')) LIKE ?",
                            [$term]
                        );
                });
            });
        }

        if ($request->filled('provider_q')) {
            $term = $likeTerm($request->string('provider_q')->toString());
            $query->where(function ($q) use ($term) {
                $q->whereHas('vendor', fn ($v) => $v->where('name', 'like', $term))
                    ->orWhereHas('freelanceStaff', fn ($f) => $f->where('name', 'like', $term));
            });
        }

        $assignmentType = $request->get('assignment_type');
        if ($assignmentType === 'vendor') {
            $query->whereNotNull('vendor_id');
        } elseif ($assignmentType === 'freelancer') {
            $query->whereNotNull('freelance_staff_id');
        }

        $query->orderByDesc('deployment_from_date')->orderByDesc('id');
        $rows = $query->paginate(10);

        $deploymentItems = $rows->getCollection();
        if ($deploymentItems->isNotEmpty()) {
            $leadIds = $deploymentItems->pluck('operation_lead_id')->unique()->values()->all();
            $startDates = $deploymentItems->map(fn ($item) => optional($item->deployment_from_date ?: $item->deployment_date)->toDateString())->filter()->values();
            $endDates = $deploymentItems->map(fn ($item) => optional($item->deployment_to_date ?: $item->deployment_from_date ?: $item->deployment_date)->toDateString())->filter()->values();
            $minDate = $startDates->isNotEmpty() ? $startDates->min() : now()->toDateString();
            $maxDate = $endDates->isNotEmpty() ? $endDates->max() : now()->toDateString();

            $attendanceRows = DeploymentLocationAttendance::query()
                ->whereIn('operation_lead_id', $leadIds)
                ->whereBetween('attendance_date', [$minDate, $maxDate])
                ->get();

            $attendanceMap = [];
            foreach ($attendanceRows as $attendanceRow) {
                $type = $attendanceRow->vendor_id ? 'vendor' : 'freelancer';
                $providerId = $attendanceRow->vendor_id ?: $attendanceRow->freelancer_id;
                if (! $providerId) {
                    continue;
                }
                $attendanceDate = optional($attendanceRow->attendance_date)->toDateString();
                if (! $attendanceDate) {
                    continue;
                }
                $key = $attendanceRow->operation_lead_id.'|'.$type.'|'.$providerId;
                $attendanceMap[$key][$attendanceDate] = $attendanceRow;
            }

            $deploymentItems->transform(function ($item) use ($attendanceMap) {
                $type = $item->vendor_id ? 'vendor' : 'freelancer';
                $providerId = $item->vendor_id ?: $item->freelance_staff_id;
                $key = $item->operation_lead_id.'|'.$type.'|'.$providerId;
                $start = $item->deployment_from_date ?: $item->deployment_date;
                $end = $item->deployment_to_date ?: $item->deployment_from_date ?: $item->deployment_date;

                if (! $start || ! $end) {
                    $item->attendance_day_statuses = [];

                    return $item;
                }

                $startDate = Carbon::parse($start);
                $endDate = Carbon::parse($end);
                if ($startDate->gt($endDate)) {
                    [$startDate, $endDate] = [$endDate, $startDate];
                }

                $statuses = [];
                $cursor = $startDate->copy()->startOfDay();
                $rangeEnd = $endDate->copy()->startOfDay();
                $dotsCount = 0;

                while ($cursor->lte($rangeEnd) && $dotsCount < 180) {
                    $day = $cursor->toDateString();
                    $row = $attendanceMap[$key][$day] ?? null;
                    $isMarked = $row && $row->attendance_status === 'present' && (bool) $row->is_location_matched;
                    $statuses[] = [
                        'date' => $day,
                        'is_marked' => $isMarked,
                        'status' => $row->attendance_status ?? 'pending',
                    ];
                    $cursor->addDay();
                    $dotsCount++;
                }

                $item->attendance_day_statuses = $statuses;

                return $item;
            });
        }

        return response()->json(['items' => $rows]);
    }

    public function locationAttendanceDeploymentDetail(OperationDeploymentDetails $deployment): JsonResponse
    {
        $this->ensureAdmin();

        return $this->adminController->locationAttendanceDeploymentDetail($deployment);
    }

    private function validateInsurerPayload(Request $request, ?int $ignoreId = null): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'company_name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'mobile' => ['nullable', 'string', 'regex:/^[0-9]{10}$/'],
            'username' => ['required', 'string', 'max:100', 'alpha_dash', Rule::unique('insurer_users', 'username')->ignore($ignoreId)],
            'is_active' => ['sometimes', 'boolean'],
        ];

        if ($ignoreId) {
            $rules['password'] = ['nullable', 'string', 'min:6', 'max:100'];
        } else {
            $rules['password'] = ['required', 'string', 'min:6', 'max:100'];
        }

        $data = $request->validate(array_merge($rules, $this->partnerDocumentValidationRules()));
        $data['is_active'] = $request->boolean('is_active', true);

        unset(
            $data['mou_file'],
            $data['company_documents'],
            $data['remove_company_documents'],
            $data['remove_mou']
        );

        return $data;
    }

    private function reloadInsurerUser(InsurerUser $item): InsurerUser
    {
        return $item->fresh()
            ->load(['corporateUsers' => fn ($q) => $q->withCount('employees')->orderByDesc('id')])
            ->loadCount('corporateUsers');
    }

    /** @return array<string, mixed> */
    private function formatInsurerUser(InsurerUser $item): array
    {
        $companyDocs = $this->partnerCompanyDocumentPaths($item->company_documents);

        return [
            'id' => $item->id,
            'name' => $item->name,
            'company_name' => $item->company_name,
            'email' => $item->email,
            'mobile' => $item->mobile,
            'username' => $item->username,
            'mou_file' => $item->mou_file,
            'mou_file_url' => $item->mou_file ? url('/storage/'.$item->mou_file) : null,
            'company_documents' => $companyDocs,
            'company_document_files' => array_map(fn (string $path) => [
                'path' => $path,
                'name' => basename($path),
                'url' => url('/storage/'.$path),
            ], $companyDocs),
            'is_active' => (bool) $item->is_active,
            'corporate_users_count' => (int) ($item->corporate_users_count ?? $item->corporateUsers?->count() ?? 0),
            'employees_total' => (int) ($item->corporateUsers?->sum('employees_count') ?? 0),
            'corporate_users' => ($item->corporateUsers ?? collect())->map(fn (CorporateUser $corp) => [
                'id' => $corp->id,
                'corporate_name' => $corp->corporate_name,
                'username' => $corp->username,
                'is_active' => (bool) $corp->is_active,
                'employees_count' => (int) ($corp->employees_count ?? 0),
                'created_at' => $corp->created_at?->toIso8601String(),
                'created_at_label' => $corp->created_at?->format('d M Y, h:i A'),
            ])->values()->all(),
            'created_at' => $item->created_at?->toIso8601String(),
            'updated_at' => $item->updated_at?->toIso8601String(),
        ];
    }

    private function corporateAccountsQuery(Request $request)
    {
        $query = CorporateUser::query()
            ->with(['insurer:id,name,company_name', 'broker:id,name,company_name'])
            ->withCount('employees')
            ->orderByDesc('id');

        if ($request->filled('owner_type')) {
            $query->where('owner_type', $request->string('owner_type'));
        }
        if ($request->filled('insurer_id')) {
            $query->where('owner_type', 'insurer')->where('insurer_user_id', $request->integer('insurer_id'));
        }
        if ($request->filled('broker_id')) {
            $query->where('owner_type', 'broker')->where('broker_user_id', $request->integer('broker_id'));
        }
        if ($request->filled('q')) {
            $like = '%'.$request->string('q').'%';
            $query->where(function ($sub) use ($like) {
                $sub->where('corporate_name', 'like', $like)->orWhere('username', 'like', $like);
            });
        }

        return $query;
    }

    /** @return array<string, mixed> */
    private function formatCorporateAccount(CorporateUser $item): array
    {
        return [
            'id' => $item->id,
            'corporate_name' => $item->corporate_name,
            'username' => $item->username,
            'owner_type' => $item->owner_type,
            'is_active' => (bool) $item->is_active,
            'employees_count' => (int) ($item->employees_count ?? 0),
            'created_by_label' => $item->createdByLabel(),
            'created_at' => $item->created_at?->toIso8601String(),
            'created_at_label' => $item->created_at?->format('d M Y'),
            'insurer' => $item->insurer ? [
                'id' => $item->insurer->id,
                'name' => $item->insurer->name,
                'company_name' => $item->insurer->company_name,
            ] : null,
            'broker' => $item->broker ? [
                'id' => $item->broker->id,
                'name' => $item->broker->name,
                'company_name' => $item->broker->company_name,
            ] : null,
        ];
    }

    /** @return array<string, mixed> */
    private function formatCorporateEmployee(CorporateEmployee $employee, bool $detailed = false): array
    {
        $corp = $employee->corporate;
        $base = [
            'id' => $employee->id,
            'employee_id' => $employee->employee_id,
            'employee_name' => $employee->employee_name,
            'phone_number' => $employee->phone_number,
            'email' => $employee->email,
            'is_active' => (bool) $employee->is_active,
            'corporate_user_id' => $employee->corporate_user_id,
            'corporate_name' => $corp?->corporate_name,
            'corporate_username' => $corp?->username,
            'created_by_label' => $corp?->createdByLabel(),
            'created_at_label' => $employee->created_at?->format('d M Y, h:i A'),
            'updated_at_label' => $employee->updated_at?->format('d M Y, h:i A'),
        ];

        if (! $detailed) {
            return $base;
        }

        return array_merge($base, [
            'date_of_birth' => $employee->date_of_birth?->format('d M Y'),
            'gender' => $employee->gender,
            'relationship' => $employee->relationship,
            'issuance_date' => $employee->issuance_date?->format('d M Y'),
            'last_working_date' => $employee->last_working_date?->format('d M Y'),
            'si_limit' => $employee->si_limit,
            'active_from' => $employee->active_from?->format('d M Y'),
            'active_to' => $employee->active_to?->format('d M Y'),
            'room_limit' => $employee->room_limit,
            'policy_terms_url' => $employee->policyTermsUrl(),
            'employee_login_url' => url('/corporate/employee/login'),
            'employee_login_hint' => $employee->employeeLoginHint(),
        ]);
    }

    private function validateBrokerPayload(Request $request, ?int $ignoreId = null): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'company_name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'mobile' => ['nullable', 'string', 'regex:/^[0-9]{10}$/'],
            'username' => ['required', 'string', 'max:100', 'alpha_dash', Rule::unique('broker_users', 'username')->ignore($ignoreId)],
            'is_active' => ['sometimes', 'boolean'],
        ];

        if ($ignoreId) {
            $rules['password'] = ['nullable', 'string', 'min:6', 'max:100'];
        } else {
            $rules['password'] = ['required', 'string', 'min:6', 'max:100'];
        }

        $data = $request->validate(array_merge($rules, $this->partnerDocumentValidationRules()));
        $data['is_active'] = $request->boolean('is_active', true);

        unset(
            $data['mou_file'],
            $data['company_documents'],
            $data['remove_company_documents'],
            $data['remove_mou']
        );

        return $data;
    }

    private function reloadBrokerUser(BrokerUser $item): BrokerUser
    {
        return $item->fresh()
            ->load(['corporateUsers' => fn ($q) => $q->withCount('employees')->orderByDesc('id')])
            ->loadCount('corporateUsers');
    }

    /** @return array<string, mixed> */
    private function formatBrokerUser(BrokerUser $item): array
    {
        $companyDocs = $this->partnerCompanyDocumentPaths($item->company_documents);

        return [
            'id' => $item->id,
            'name' => $item->name,
            'company_name' => $item->company_name,
            'email' => $item->email,
            'mobile' => $item->mobile,
            'username' => $item->username,
            'mou_file' => $item->mou_file,
            'mou_file_url' => $item->mou_file ? url('/storage/'.$item->mou_file) : null,
            'company_documents' => $companyDocs,
            'company_document_files' => array_map(fn (string $path) => [
                'path' => $path,
                'name' => basename($path),
                'url' => url('/storage/'.$path),
            ], $companyDocs),
            'is_active' => (bool) $item->is_active,
            'corporate_users_count' => (int) ($item->corporate_users_count ?? $item->corporateUsers?->count() ?? 0),
            'employees_total' => (int) ($item->corporateUsers?->sum('employees_count') ?? 0),
            'corporate_users' => ($item->corporateUsers ?? collect())->map(fn (CorporateUser $corp) => [
                'id' => $corp->id,
                'corporate_name' => $corp->corporate_name,
                'username' => $corp->username,
                'is_active' => (bool) $corp->is_active,
                'employees_count' => (int) ($corp->employees_count ?? 0),
                'created_at' => $corp->created_at?->toIso8601String(),
                'created_at_label' => $corp->created_at?->format('d M Y, h:i A'),
            ])->values()->all(),
            'created_at' => $item->created_at?->toIso8601String(),
            'updated_at' => $item->updated_at?->toIso8601String(),
        ];
    }
}
