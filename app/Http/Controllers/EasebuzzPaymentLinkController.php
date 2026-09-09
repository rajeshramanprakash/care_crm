<?php

namespace App\Http\Controllers;

use App\Models\EasebuzzPaymentLink;
use App\Services\EasebuzzEasyCollectService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class EasebuzzPaymentLinkController extends Controller
{
    public function __construct(
        private EasebuzzEasyCollectService $easyCollect
    ) {}

    public function index(Request $request)
    {
        $ctx = $this->roleContext();
        $query = EasebuzzPaymentLink::query()->with('createdBy');

        if (! $ctx['is_admin']) {
            $query->where('created_by', Auth::id());
        }

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

        $payments = $query->orderByDesc('created_at')->paginate(15)->withQueryString();

        $statsQuery = EasebuzzPaymentLink::query();
        if (! $ctx['is_admin']) {
            $statsQuery->where('created_by', Auth::id());
        }

        $stats = [
            'pending' => (clone $statsQuery)->where('status', EasebuzzPaymentLink::STATUS_PENDING)->count(),
            'paid' => (clone $statsQuery)->where('status', EasebuzzPaymentLink::STATUS_PAID)->count(),
            'total' => (clone $statsQuery)->count(),
        ];

        return view('easebuzz_payments.index', array_merge($ctx, compact('payments', 'stats', 'status', 'q'), [
            'easebuzz_demo_mode' => $this->easyCollect->isDemoMode(),
        ]));
    }

    public function create()
    {
        return view('easebuzz_payments.create', array_merge($this->roleContext(), [
            'easebuzz_demo_mode' => $this->easyCollect->isDemoMode(),
        ]));
    }

    public function store(Request $request)
    {
        $ctx = $this->roleContext();

        $validated = $request->validate([
            'customer_name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'required|string|max:20',
            'message' => 'nullable|string|max:500',
            'amount' => 'required|numeric|min:0.01',
        ]);

        if (! $this->easyCollect->isConfigured()) {
            $hint = $this->easyCollect->isDemoMode()
                ? 'Set EASEBUZZ_KEY_DEMO and EASEBUZZ_SALT_DEMO in .env.'
                : 'Set EASEBUZZ_KEY and EASEBUZZ_SALT in .env (or set EASEBUZZ_EASYCOLLECT_DEMO=true for test).';

            return back()->withInput()->with('error', 'Easebuzz is not configured. '.$hint);
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
            return back()->withInput()->with('error', $result['error'] ?? 'Could not create payment link.');
        }

        $expireAt = isset($result['expire_by'])
            ? \Carbon\Carbon::createFromTimestamp((int) $result['expire_by'])
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

        return redirect()
            ->route($ctx['route_prefix'].'.payments.show', $payment)
            ->with('success', 'Payment link created successfully.');
    }

    public function show(EasebuzzPaymentLink $payment)
    {
        $ctx = $this->roleContext();
        $this->authorizePayment($payment, $ctx);

        $payment->load('createdBy');

        if ($payment->status === EasebuzzPaymentLink::STATUS_PENDING && $payment->isExpired()) {
            $payment->update(['status' => EasebuzzPaymentLink::STATUS_EXPIRED]);
            $payment->refresh();
        }

        return view('easebuzz_payments.show', array_merge($ctx, compact('payment'), [
            'easebuzz_demo_mode' => $this->easyCollect->isDemoMode(),
        ]));
    }

    public function verify(EasebuzzPaymentLink $payment)
    {
        $ctx = $this->roleContext();
        $this->authorizePayment($payment, $ctx);

        if ($payment->status === EasebuzzPaymentLink::STATUS_PAID) {
            return redirect()
                ->route($ctx['route_prefix'].'.payments.show', $payment)
                ->with('info', 'This payment is already marked as paid.');
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

            if ($newStatus === EasebuzzPaymentLink::STATUS_PAID) {
                return redirect()
                    ->route($ctx['route_prefix'].'.payments.show', $payment)
                    ->with('success', 'Payment verified — marked as paid.');
            }

            return redirect()
                ->route($ctx['route_prefix'].'.payments.show', $payment)
                ->with('warning', 'Payment not completed yet. Customer has not paid or payment is still processing.');
        }

        $payment->update($update);

        return redirect()
            ->route($ctx['route_prefix'].'.payments.show', $payment)
            ->with('warning', $result['error'] ?? 'Payment not completed yet.');
    }

    /**
     * @return array{layout: string, route_prefix: string, is_admin: bool}
     */
    private function roleContext(): array
    {
        $user = Auth::user();
        $roleName = $user?->role?->name ?? '';

        return match ($roleName) {
            'Admin' => [
                'layout' => 'admin.layouts.app',
                'route_prefix' => 'admin',
                'is_admin' => true,
            ],
            'Sales' => [
                'layout' => 'sales.layouts.app',
                'route_prefix' => 'sales',
                'is_admin' => false,
            ],
            'Operation' => [
                'layout' => 'operation.layouts.app',
                'route_prefix' => 'operation',
                'is_admin' => false,
            ],
            default => abort(403),
        };
    }

    /**
     * @param  array{is_admin: bool}  $ctx
     */
    private function authorizePayment(EasebuzzPaymentLink $payment, array $ctx): void
    {
        if ($ctx['is_admin']) {
            return;
        }

        if ((int) $payment->created_by !== (int) Auth::id()) {
            abort(403);
        }
    }
}
