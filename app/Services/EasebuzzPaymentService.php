<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Easebuzz hosted checkout (initiateLink). Hash rules match easebuzz/paywitheasebuzz-php-lib.
 *
 * @see https://github.com/easebuzz/paywitheasebuzz-php-lib
 */
class EasebuzzPaymentService
{
    public function isConfigured(): bool
    {
        $key = trim((string) config('services.easebuzz.key', ''));
        $salt = trim((string) config('services.easebuzz.salt', ''));

        return $key !== '' && $salt !== '';
    }

    /**
     * @param  array<string, string>  $fields  txnid, amount, productinfo, firstname, email, phone, surl, furl, udf1..udf10, optional address1..zipcode
     * @return array{ok: bool, payment_url?: string, error?: string, raw?: mixed}
     */
    public function initiatePaymentLink(array $fields): array
    {
        if (! $this->isConfigured()) {
            return ['ok' => false, 'error' => 'Easebuzz is not configured (EASEBUZZ_KEY / EASEBUZZ_SALT).'];
        }

        $key = trim((string) config('services.easebuzz.key'));
        $salt = trim((string) config('services.easebuzz.salt'));
        $base = rtrim((string) config('services.easebuzz.base_url', 'https://testpay.easebuzz.in/'), '/').'/';

        $posted = array_merge([
            'key' => $key,
            'txnid' => '',
            'amount' => '',
            'productinfo' => '',
            'firstname' => '',
            'email' => '',
            'phone' => '',
            'surl' => '',
            'furl' => '',
            'udf1' => '',
            'udf2' => '',
            'udf3' => '',
            'udf4' => '',
            'udf5' => '',
            'udf6' => '',
            'udf7' => '',
            'udf8' => '',
            'udf9' => '',
            'udf10' => '',
            'address1' => '',
            'address2' => '',
            'city' => '',
            'state' => '',
            'country' => '',
            'zipcode' => '',
        ], $fields);

        foreach ($posted as $k => $v) {
            if (is_string($v)) {
                $posted[$k] = trim($v);
            }
        }
        $posted['key'] = $key;

        foreach (['txnid', 'amount', 'productinfo', 'firstname', 'email', 'phone', 'surl', 'furl'] as $req) {
            if (($posted[$req] ?? '') === '') {
                return ['ok' => false, 'error' => 'Missing Easebuzz field: '.$req];
            }
        }

        $posted['amount'] = $this->normalizeAmountForEasebuzz((string) $posted['amount']);
        $posted['phone'] = $this->normalizePhoneTenDigits((string) $posted['phone']);

        $posted['hash'] = $this->buildRequestHash($posted, $salt);

        $url = $base.'payment/initiateLink';
        $body = http_build_query($posted, '', '&', PHP_QUERY_RFC1738);

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/x-www-form-urlencoded',
                'Accept' => 'application/json',
            ])
                ->timeout(45)
                ->withBody($body, 'application/x-www-form-urlencoded')
                ->post($url);
        } catch (\Throwable $e) {
            Log::warning('Easebuzz initiateLink HTTP error', ['error' => $e->getMessage()]);

            return ['ok' => false, 'error' => 'Could not reach payment gateway. Try again later.'];
        }

        if (! $response->successful()) {
            Log::warning('Easebuzz initiateLink non-200', ['status' => $response->status(), 'body' => $response->body()]);

            return ['ok' => false, 'error' => 'Payment gateway error.', 'raw' => $response->json()];
        }

        $json = $response->json();
        if (! is_array($json)) {
            return ['ok' => false, 'error' => 'Invalid gateway response.', 'raw' => $response->body()];
        }

        $status = $json['status'] ?? null;
        $accessKey = isset($json['data']) ? trim((string) $json['data']) : '';
        if ((int) $status !== 1 || $accessKey === '') {
            $msg = $this->formatGatewayErrorMessage($json);

            Log::warning('Easebuzz initiateLink rejected', [
                'status' => $status,
                'message' => $msg,
                'error_desc' => $json['error_desc'] ?? null,
                'base_url' => $base,
            ]);

            return ['ok' => false, 'error' => $msg, 'raw' => $json];
        }

        $paymentUrl = $base.'pay/'.$accessKey;

        return ['ok' => true, 'payment_url' => $paymentUrl, 'raw' => $json];
    }

    /**
     * @param  array<string, mixed>  $response  decoded callback / redirect body
     */
    public function verifyResponseHash(array $response, string $salt): bool
    {
        $received = strtolower(trim((string) ($response['hash'] ?? '')));
        if ($received === '') {
            return false;
        }

        $sequence = ['udf10', 'udf9', 'udf8', 'udf7', 'udf6', 'udf5', 'udf4', 'udf3', 'udf2', 'udf1', 'email', 'firstname', 'productinfo', 'amount', 'txnid', 'key'];
        $reverse = $salt.'|'.($response['status'] ?? '');
        foreach ($sequence as $k) {
            $reverse .= '|';
            $reverse .= isset($response[$k]) ? (string) $response[$k] : '';
        }

        $computed = strtolower(hash('sha512', $reverse));

        return hash_equals($computed, $received);
    }

    /**
     * @param  array<string, mixed>  $json
     */
    private function formatGatewayErrorMessage(array $json): string
    {
        $errorDesc = trim((string) ($json['error_desc'] ?? ''));
        $dataMsg = '';
        if (isset($json['data'])) {
            if (is_string($json['data'])) {
                $dataMsg = trim($json['data']);
            } elseif (is_array($json['data'])) {
                $dataMsg = trim(json_encode($json['data']));
            }
        }

        if ($errorDesc !== '' && stripos($errorDesc, 'invalid merchant key') !== false) {
            $isProdHost = str_contains(
                strtolower(rtrim((string) config('services.easebuzz.base_url', ''), '/')),
                'pay.easebuzz.in'
            );

            return $isProdHost
                ? 'Invalid merchant key for production. Use live KEY and SALT from the Easebuzz dashboard with EASEBUZZ_BASE_URL=https://pay.easebuzz.in/ (test credentials only work on testpay.easebuzz.in).'
                : 'Invalid merchant key. Check EASEBUZZ_KEY and EASEBUZZ_SALT match the environment (test vs live).';
        }

        if ($errorDesc !== '') {
            return $errorDesc;
        }

        if ($dataMsg !== '') {
            return $dataMsg;
        }

        return 'Payment could not be started.';
    }

    /**
     * @param  array<string, string>  $posted
     */
    private function buildRequestHash(array $posted, string $salt): string
    {
        $sequence = ['key', 'txnid', 'amount', 'productinfo', 'firstname', 'email', 'udf1', 'udf2', 'udf3', 'udf4', 'udf5', 'udf6', 'udf7', 'udf8', 'udf9', 'udf10'];
        $hash = '';
        foreach ($sequence as $k) {
            $hash .= isset($posted[$k]) ? (string) $posted[$k] : '';
            $hash .= '|';
        }
        $hash .= $salt;

        return strtolower(hash('sha512', $hash));
    }

    /**
     * Amount must match ^\d+\.\d{1,2}$ for Easebuzz client validation; hash uses same string.
     */
    private function normalizeAmountForEasebuzz(string $amount): string
    {
        $n = (float) $amount;
        if ($n < 0) {
            $n = 0;
        }
        $s = sprintf('%.2f', $n);
        if (preg_match('/^(\d+)\.00$/', $s, $m)) {
            return $m[1].'.0';
        }

        return $s;
    }

    /** Easebuzz expects exactly 10 digit phone (India-style). */
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
}
