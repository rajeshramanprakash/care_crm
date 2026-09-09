<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EasebuzzPaymentLink;
use App\Services\EasebuzzEasyCollectService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class OperationEasebuzzPaymentsApiController extends Controller
{
    public function __construct(
        private readonly EasebuzzEasyCollectService $easyCollect
    ) {}

    private function ensureOperation(): void
    {
        $roleIds = array_filter(explode(',', (string) (Auth::user()->role_id ?? '')));
        if (! in_array('4', $roleIds, true)) {
            abort(403, 'Operation access required');
        }
    }

    private function authorizeOwnPayment(EasebuzzPaymentLink $payment): void
    {
        if ((int) $payment->created_by !== (int) Auth::id()) {
            abort(403, 'You can only access your own payment links.');
        }
    }

    public function index(Request $request): JsonResponse
    {
        $this->ensureOperation();

        $query = EasebuzzPaymentLink::query()
            ->where('created_by', Auth::id());

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

        $statsQuery = EasebuzzPaymentLink::query()->where('created_by', Auth::id());
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

    public function show(EasebuzzPaymentLink $payment): JsonResponse
    {
        $this->ensureOperation();
        $this->authorizeOwnPayment($payment);

        if ($payment->status === EasebuzzPaymentLink::STATUS_PENDING && $payment->isExpired()) {
            $payment->update(['status' => EasebuzzPaymentLink::STATUS_EXPIRED]);
            $payment->refresh();
        }

        return response()->json(['payment' => $payment]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->ensureOperation();

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

        return response()->json(['success' => true, 'payment' => $payment], 201);
    }

    public function verify(EasebuzzPaymentLink $payment): JsonResponse
    {
        $this->ensureOperation();
        $this->authorizeOwnPayment($payment);

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
}
