<?php

namespace App\Services;

use App\Models\EasebuzzPaymentLink;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Easebuzz EasyCollect payment links (dashboard API) + transaction retrieve.
 */
class EasebuzzEasyCollectService
{
    public function isDemoMode(): bool
    {
        return (bool) config('services.easycollect.demo', true);
    }

    public function isConfigured(): bool
    {
        $key = trim((string) config('services.easycollect.key', ''));
        $salt = trim((string) config('services.easycollect.salt', ''));

        return $key !== '' && $salt !== '';
    }

    private function credentials(): array
    {
        return [
            'key' => trim((string) config('services.easycollect.key', '')),
            'salt' => trim((string) config('services.easycollect.salt', '')),
            'base' => (string) config('services.easycollect.dashboard_base_url', 'https://testdashboard.easebuzz.in'),
        ];
    }

    /**
     * @param  array{name: string, email: string, phone: string, amount: string|float, message?: string, merchant_txn: string}  $input
     * @return array{ok: bool, payment_url?: string, expire_by?: int, error?: string, raw?: mixed}
     */
    public function createPaymentLink(array $input): array
    {
        if (! $this->isConfigured()) {
            $vars = $this->isDemoMode()
                ? 'EASEBUZZ_KEY_DEMO / EASEBUZZ_SALT_DEMO'
                : 'EASEBUZZ_KEY / EASEBUZZ_SALT';

            return ['ok' => false, 'error' => "Easebuzz is not configured ({$vars})."];
        }

        $cred = $this->credentials();
        $key = $cred['key'];
        $salt = $cred['salt'];
        $base = $cred['base'];
        $days = (int) config('services.easycollect.link_validity_days', 7);

        $merchantTxn = trim((string) ($input['merchant_txn'] ?? ''));
        $name = trim((string) ($input['name'] ?? ''));
        $email = trim((string) ($input['email'] ?? ''));
        $phone = $this->normalizePhoneTenDigits((string) ($input['phone'] ?? ''));
        $amount = $this->normalizeAmount((string) ($input['amount'] ?? '0'));
        $message = trim((string) ($input['message'] ?? ''));

        if ($merchantTxn === '' || $name === '' || $email === '' || $phone === '' || (float) $amount <= 0) {
            return ['ok' => false, 'error' => 'Missing required payment fields.'];
        }

        $udf1 = $udf2 = $udf3 = $udf4 = $udf5 = '';
        $expireBy = time() + ($days * 24 * 60 * 60);

        $hashString = implode('|', [
            $key,
            $merchantTxn,
            $name,
            $email,
            $phone,
            $amount,
            $udf1,
            $udf2,
            $udf3,
            $udf4,
            $udf5,
            $message,
            $salt,
        ]);
        $hash = hash('sha512', $hashString);

        $payload = [
            'key' => $key,
            'merchant_txn' => $merchantTxn,
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'amount' => $amount,
            'message' => $message,
            'hash' => $hash,
            'expire_by' => $expireBy,
        ];

        $url = $base.'/easycollect/v1/create';

        try {
            $response = Http::withHeaders(['Content-Type' => 'application/json'])
                ->timeout(45)
                ->post($url, $payload);
        } catch (\Throwable $e) {
            Log::warning('Easebuzz EasyCollect create HTTP error', ['error' => $e->getMessage()]);

            return ['ok' => false, 'error' => 'Could not reach payment gateway. Try again later.'];
        }

        $raw = $response->json();
        if (! is_array($raw)) {
            Log::warning('Easebuzz EasyCollect create invalid JSON', [
                'status' => $response->status(),
                'body' => $response->body(),
                'url' => $url,
            ]);

            return ['ok' => false, 'error' => 'Invalid gateway response.', 'raw' => $response->body()];
        }

        if (! $this->isApiSuccess($raw['status'] ?? null)) {
            $msg = $this->extractApiErrorMessage($raw);
            Log::warning('Easebuzz EasyCollect create rejected', [
                'error' => $msg,
                'url' => $url,
                'raw' => $raw,
            ]);

            return ['ok' => false, 'error' => $msg, 'raw' => $raw];
        }

        $paymentUrl = trim((string) ($raw['data']['payment_url'] ?? ''));
        if ($paymentUrl === '') {
            return ['ok' => false, 'error' => 'Gateway did not return a payment URL.', 'raw' => $raw];
        }

        return [
            'ok' => true,
            'payment_url' => $paymentUrl,
            'expire_by' => $expireBy,
            'raw' => $raw,
        ];
    }

    /**
     * @return array{ok: bool, status?: string, error?: string, raw?: mixed}
     */
    public function retrieveTransaction(string $merchantTxn, string $amount, string $email, string $phone): array
    {
        if (! $this->isConfigured()) {
            return ['ok' => false, 'error' => 'Easebuzz is not configured.'];
        }

        $cred = $this->credentials();
        $key = $cred['key'];
        $salt = $cred['salt'];
        $base = $cred['base'];

        $txnid = trim($merchantTxn);
        $amountNorm = $this->normalizeAmountForRetrieve($amount);
        $email = trim($email);
        $phone = $this->normalizePhoneTenDigits($phone);

        $posted = [
            'key' => $key,
            'txnid' => $txnid,
            'amount' => $amountNorm,
            'email' => $email,
            'phone' => $phone,
        ];

        $hashString = $key.'|'.$txnid.'|'.$amountNorm.'|'.$email.'|'.$phone.'|'.$salt;
        $posted['hash'] = strtolower(hash('sha512', $hashString));

        $url = $base.'/transaction/v1/retrieve';

        try {
            $response = Http::asForm()
                ->timeout(45)
                ->post($url, $posted);
        } catch (\Throwable $e) {
            Log::warning('Easebuzz transaction retrieve HTTP error', ['error' => $e->getMessage()]);

            return ['ok' => false, 'error' => 'Could not reach payment gateway.'];
        }

        $raw = $response->json();
        if (! is_array($raw)) {
            return ['ok' => false, 'error' => 'Invalid gateway response.', 'raw' => $response->body()];
        }

        if (! $this->isApiSuccess($raw['status'] ?? null)) {
            $msg = $this->extractApiErrorMessage($raw);
            if ($msg === 'Payment link could not be created.') {
                $msg = 'Transaction not found or not paid yet.';
            }

            return ['ok' => false, 'error' => $msg, 'raw' => $raw];
        }

        $mappedStatus = $this->mapTransactionStatus($raw);

        return [
            'ok' => true,
            'status' => $mappedStatus,
            'raw' => $raw,
        ];
    }

    /**
     * Easebuzz transaction/v1/retrieve returns paid details under "msg" (not "data").
     *
     * @param  array<string, mixed>  $raw
     */
    private function mapTransactionStatus(array $raw): string
    {
        $blocks = array_filter([
            $raw['msg'] ?? null,
            $raw['data'] ?? null,
        ], static fn ($b) => is_array($b));

        foreach ($blocks as $block) {
            $status = strtolower(trim((string) ($block['status'] ?? '')));
            if (in_array($status, ['success', 'paid', 'captured'], true)) {
                return EasebuzzPaymentLink::STATUS_PAID;
            }
            if (in_array($status, ['failure', 'failed', 'usercancelled', 'cancelled', 'dropped'], true)) {
                return EasebuzzPaymentLink::STATUS_FAILED;
            }

            $errorText = strtolower(trim((string) ($block['error'] ?? $block['error_Message'] ?? '')));
            if ($errorText !== '' && (str_contains($errorText, 'successful') || str_contains($errorText, 'success'))) {
                return EasebuzzPaymentLink::STATUS_PAID;
            }
        }

        $flat = is_string($raw['data'] ?? null) ? (string) $raw['data'] : '';
        if ($flat !== '' && stripos($flat, 'success') !== false) {
            return EasebuzzPaymentLink::STATUS_PAID;
        }

        return EasebuzzPaymentLink::STATUS_PENDING;
    }

    private function normalizeAmount(string $amount): string
    {
        return number_format((float) $amount, 2, '.', '');
    }

    /** Retrieve API expects amount as float string (e.g. 100.0 or 100.50). */
    private function normalizeAmountForRetrieve(string $amount): string
    {
        $n = (float) $amount;
        $s = sprintf('%.2f', $n);
        if (preg_match('/^(\d+)\.00$/', $s, $m)) {
            return $m[1].'.0';
        }

        return $s;
    }

    private function normalizePhoneTenDigits(string $raw): string
    {
        $digits = preg_replace('/\D/', '', $raw) ?? '';
        if (strlen($digits) >= 10) {
            return substr($digits, -10);
        }
        if ($digits === '') {
            return '9999999999';
        }

        return str_pad($digits, 10, '0', STR_PAD_LEFT);
    }

    private function isApiSuccess(mixed $status): bool
    {
        return $status === true || $status === 1 || $status === '1';
    }

    /**
     * @param  array<string, mixed>  $raw
     */
    private function extractApiErrorMessage(array $raw): string
    {
        $errorDesc = trim((string) ($raw['error_desc'] ?? ''));
        if ($errorDesc !== '') {
            return $errorDesc;
        }

        if (isset($raw['validation_errors']) && is_array($raw['validation_errors'])) {
            $parts = array_filter(array_map(
                static fn ($v) => trim((string) $v),
                $raw['validation_errors']
            ));
            if ($parts !== []) {
                return implode(' ', $parts);
            }
        }

        $message = trim((string) ($raw['message'] ?? $raw['error'] ?? ''));
        if ($message !== '' && ! $this->isApiSuccess($raw['status'] ?? null)) {
            return $message;
        }

        if (is_string($raw['data'] ?? null) && trim((string) $raw['data']) !== '') {
            return trim((string) $raw['data']);
        }

        $dashBase = (string) config('services.easycollect.dashboard_base_url');
        if ($this->isDemoMode() && ! str_contains($dashBase, 'testdashboard')) {
            return 'Demo mode: set EASEBUZZ_EASYCOLLECT_BASE_URL=https://testdashboard.easebuzz.in or leave it unset.';
        }
        if (! $this->isDemoMode() && str_contains($dashBase, 'testdashboard')) {
            return 'Live mode: set EASEBUZZ_EASYCOLLECT_DEMO=false and use production KEY/SALT with dashboard.easebuzz.in.';
        }

        return 'Payment link could not be created.';
    }
}
