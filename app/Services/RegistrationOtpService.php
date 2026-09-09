<?php

namespace App\Services;

use App\Models\DoctorRegistrationOtpLog;
use App\Models\DoctorRequest;
use App\Models\JobRequest;
use App\Models\Vendor;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RegistrationOtpService
{
    public const TYPES = ['doctor', 'vendor', 'freelancer'];

    private const DEMO_OTP = '999999';

    private const DEMO_MOBILES = [
        '7754966128',
        '9999999999',
        '8888888888',
        '7777777777',
    ];

    public function __construct(
        private readonly string $registrationType = 'doctor'
    ) {
        if (! in_array($this->registrationType, self::TYPES, true)) {
            throw new \InvalidArgumentException('Invalid registration OTP type: '.$this->registrationType);
        }
    }

    public static function for(string $type): self
    {
        return new self($type);
    }

    public function type(): string
    {
        return $this->registrationType;
    }

    public function cachePrefix(): string
    {
        return $this->registrationType.'_reg_otp_';
    }

    public function verifiedPrefix(): string
    {
        return $this->registrationType.'_reg_mobile_verified_';
    }

    public function normalizeMobile(?string $mobile): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $mobile);
        if (! is_string($digits) || strlen($digits) < 10) {
            return null;
        }

        return substr($digits, -10);
    }

    public function isMobileAlreadyRegistered(string $mobile): bool
    {
        return match ($this->registrationType) {
            'doctor' => DoctorRequest::query()->where('mobile', $mobile)->orWhere('contact_no', $mobile)->exists()
                || JobRequest::query()->where('mobile', $mobile)->orWhere('contact_no', $mobile)->exists(),
            'vendor' => Vendor::query()->where('contact_no', $mobile)->exists(),
            'freelancer' => JobRequest::query()->where('mobile', $mobile)->orWhere('contact_no', $mobile)->exists()
                || DoctorRequest::query()->where('mobile', $mobile)->orWhere('contact_no', $mobile)->exists(),
            default => false,
        };
    }

    public function usesDemoOtp(string $mobile): bool
    {
        return in_array($mobile, self::DEMO_MOBILES, true);
    }

    /**
     * @return array{success: bool, message: string, meta?: array<string, mixed>}
     */
    public function sendOtp(string $mobile, string $ip): array
    {
        $mobile = $this->normalizeMobile($mobile) ?? '';
        if ($mobile === '') {
            return ['success' => false, 'message' => 'Please enter a valid 10-digit mobile number.'];
        }

        if ($this->isMobileAlreadyRegistered($mobile)) {
            return [
                'success' => false,
                'message' => 'This mobile number is already registered. Please login instead.',
            ];
        }

        $existing = Cache::get($this->cachePrefix().$mobile);
        if (is_array($existing) && ! empty($existing['sent_at_unix'])) {
            $secondsSince = time() - (int) $existing['sent_at_unix'];
            $wait = 60 - $secondsSince;
            if ($wait > 0) {
                return [
                    'success' => false,
                    'message' => 'Please wait '.$wait.' seconds before requesting another OTP.',
                ];
            }
        }

        $otp = $this->usesDemoOtp($mobile)
            ? self::DEMO_OTP
            : str_pad((string) random_int(100000, 999999), 6, '0', STR_PAD_LEFT);

        $sentAt = now();
        $meta = [
            'otp' => $otp,
            'mobile' => $mobile,
            'registration_type' => $this->registrationType,
            'sent_at' => $sentAt->toIso8601String(),
            'sent_at_unix' => $sentAt->timestamp,
            'ip' => $ip,
            'verified' => false,
            'verified_at' => null,
        ];

        $otpLog = DoctorRegistrationOtpLog::query()->create([
            'registration_type' => $this->registrationType,
            'mobile' => $mobile,
            'otp' => $otp,
            'sent_at' => $sentAt,
            'sent_ip' => $ip !== '' ? $ip : null,
            'sms_provider' => $this->usesDemoOtp($mobile) ? 'demo' : 'msg91_flow',
        ]);
        $meta['log_id'] = $otpLog->id;

        Cache::put($this->cachePrefix().$mobile, $meta, now()->addMinutes($this->otpExpiryMinutes()));
        Cache::forget($this->verifiedPrefix().$mobile);

        if (! $this->usesDemoOtp($mobile)) {
            $smsResult = $this->sendSmsViaMsg91($mobile, $otp, $otpLog);
            if (! $smsResult['success']) {
                Cache::forget($this->cachePrefix().$mobile);
                $otpLog->delete();

                return $smsResult;
            }
        }

        Log::info(ucfirst($this->registrationType).' registration OTP sent', [
            'registration_type' => $this->registrationType,
            'mobile' => $mobile,
            'ip' => $ip,
            'sent_at' => $meta['sent_at'],
        ]);

        $response = [
            'success' => true,
            'message' => 'OTP sent to your mobile number.',
        ];

        if (config('app.debug')) {
            $response['meta'] = [
                'otp' => $otp,
                'sent_at' => $meta['sent_at'],
                'ip' => $ip,
                'expires_in_minutes' => $this->otpExpiryMinutes(),
                'msg91_request_id' => $otpLog->msg91_request_id,
                'msg91_flow_template_id' => config('services.msg91.flow_template_id'),
            ];
        }

        return $response;
    }

    /**
     * @return array{success: bool, message: string}
     */
    public function verifyOtp(string $mobile, string $otp, string $ip): array
    {
        $mobile = $this->normalizeMobile($mobile) ?? '';
        $otp = preg_replace('/\D+/', '', (string) $otp);

        if ($mobile === '' || strlen($otp) !== 6) {
            return ['success' => false, 'message' => 'Please enter a valid mobile number and 6-digit OTP.'];
        }

        $cacheKey = $this->cachePrefix().$mobile;
        $meta = Cache::get($cacheKey);

        if (! is_array($meta) || empty($meta['otp'])) {
            return ['success' => false, 'message' => 'OTP expired or not sent. Please request a new OTP.'];
        }

        $storedOtp = (string) $meta['otp'];
        $valid = ($this->usesDemoOtp($mobile) && $otp === self::DEMO_OTP) || $storedOtp === $otp;

        if (! $valid) {
            $this->recordVerifyAttempt($meta, $ip);
            Log::warning(ucfirst($this->registrationType).' registration OTP verify failed', [
                'registration_type' => $this->registrationType,
                'mobile' => $mobile,
                'ip' => $ip,
                'sent_at' => $meta['sent_at'] ?? null,
            ]);

            return ['success' => false, 'message' => 'Invalid OTP. Please check and try again.'];
        }

        $verifiedAt = now();
        $meta['verified'] = true;
        $meta['verified_at'] = $verifiedAt->toIso8601String();
        $meta['verified_ip'] = $ip;
        Cache::put($cacheKey, $meta, now()->addMinutes($this->otpExpiryMinutes()));
        Cache::put($this->verifiedPrefix().$mobile, true, now()->addMinutes(60));

        $this->markOtpLogVerified($meta, $verifiedAt, $ip);

        Log::info(ucfirst($this->registrationType).' registration mobile verified', [
            'registration_type' => $this->registrationType,
            'mobile' => $mobile,
            'ip' => $ip,
            'sent_at' => $meta['sent_at'] ?? null,
            'verified_at' => $meta['verified_at'],
        ]);

        return ['success' => true, 'message' => 'Mobile number verified successfully.'];
    }

    public function isMobileVerified(string $mobile): bool
    {
        $mobile = $this->normalizeMobile($mobile) ?? '';

        return $mobile !== '' && Cache::get($this->verifiedPrefix().$mobile) === true;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getOtpMeta(string $mobile): ?array
    {
        if (! config('app.debug')) {
            return null;
        }

        $mobile = $this->normalizeMobile($mobile) ?? '';
        if ($mobile === '') {
            return null;
        }

        $meta = Cache::get($this->cachePrefix().$mobile);
        if (! is_array($meta)) {
            return null;
        }

        return [
            'registration_type' => $this->registrationType,
            'mobile' => $mobile,
            'otp' => $meta['otp'] ?? null,
            'sent_at' => $meta['sent_at'] ?? null,
            'ip' => $meta['ip'] ?? null,
            'verified' => (bool) ($meta['verified'] ?? false),
            'verified_at' => $meta['verified_at'] ?? null,
            'verified_ip' => $meta['verified_ip'] ?? null,
        ];
    }

    /**
     * @return array{success: bool, message: string}
     */
    private function sendSmsViaMsg91(string $mobile, string $otp, DoctorRegistrationOtpLog $otpLog): array
    {
        $authKey = trim((string) config('services.msg91.auth_key', ''));
        if ($authKey === '') {
            return ['success' => false, 'message' => 'SMS OTP is not configured. Please contact support.'];
        }

        $templateId = trim((string) config('services.msg91.flow_template_id', ''));
        if ($templateId === '') {
            return [
                'success' => false,
                'message' => 'SMS template is not configured. Admin must set MSG91_FLOW_TEMPLATE_ID in .env.',
            ];
        }

        $otpVar = trim((string) config('services.msg91.flow_otp_variable', 'otp'));
        $sender = trim((string) config('services.msg91.sender', ''));
        $recipient = [
            'mobiles' => '91'.$mobile,
            $otpVar => $otp,
        ];

        $payload = [
            'flow_id' => $templateId,
            'template_id' => $templateId,
            'short_url' => '0',
            'recipients' => [$recipient],
        ];
        if ($sender !== '') {
            $payload['sender'] = $sender;
        }

        try {
            $response = $this->postMsg91Flow($authKey, $payload);
            $data = $response['data'];
            $type = strtolower((string) ($data['type'] ?? ''));
            $requestId = (string) ($data['request_id'] ?? '');
            if ($requestId === '' && $type === 'success') {
                $requestId = (string) ($data['message'] ?? '');
            }

            if ($response['http_ok'] && $type === 'success' && $requestId !== '' && ! $this->isMsg91TemplateError($requestId)) {
                $otpLog->msg91_request_id = $requestId;
                $otpLog->msg91_delivery_status = 'flow_submitted';
                $otpLog->msg91_last_error = null;
                $otpLog->save();

                return ['success' => true, 'message' => 'OTP sent to your mobile number.'];
            }

            $apiMessage = (string) ($data['message'] ?? $response['body'] ?? '');
            $otpLog->msg91_delivery_status = 'api_failed';
            $otpLog->msg91_last_error = $apiMessage;
            $otpLog->save();

            return [
                'success' => false,
                'message' => $apiMessage !== ''
                    ? 'Could not send OTP: '.$apiMessage
                    : 'Could not send OTP. Please check your number or try again.',
            ];
        } catch (\Throwable $e) {
            $otpLog->msg91_delivery_status = 'exception';
            $otpLog->msg91_last_error = $e->getMessage();
            $otpLog->save();

            Log::error('MSG91 Flow OTP send exception', [
                'registration_type' => $this->registrationType,
                'mobile' => $mobile,
                'error' => $e->getMessage(),
            ]);

            return ['success' => false, 'message' => $this->formatSmsExceptionMessage($e)];
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{http_ok: bool, status: int, data: array<string, mixed>, body: string}
     */
    private function postMsg91Flow(string $authKey, array $payload): array
    {
        $lastException = null;
        $maxAttempts = 3;

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            try {
                return $this->postMsg91FlowOnce($authKey, $payload);
            } catch (\Throwable $e) {
                $lastException = $e;
                if (! $this->isRetryableMsg91Error($e->getMessage()) || $attempt >= $maxAttempts) {
                    throw $e;
                }
                usleep(1000000 * $attempt);
            }
        }

        throw $lastException ?? new \RuntimeException('MSG91 request failed.');
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{http_ok: bool, status: int, data: array<string, mixed>, body: string}
     */
    private function postMsg91FlowOnce(string $authKey, array $payload): array
    {
        try {
            $response = Http::timeout(45)
                ->connectTimeout(25)
                ->withOptions([
                    'curl' => [
                        \CURLOPT_IPRESOLVE => \CURL_IPRESOLVE_V4,
                        \CURLOPT_NOSIGNAL => true,
                    ],
                ])
                ->withHeaders([
                    'authkey' => $authKey,
                    'accept' => 'application/json',
                    'content-type' => 'application/json',
                ])
                ->post('https://control.msg91.com/api/v5/flow', $payload);

            $data = $response->json();

            return [
                'http_ok' => $response->successful(),
                'status' => $response->status(),
                'data' => is_array($data) ? $data : [],
                'body' => trim((string) $response->body()),
            ];
        } catch (\Throwable $httpException) {
            if (! $this->isRetryableMsg91Error($httpException->getMessage())) {
                throw $httpException;
            }

            return $this->postMsg91FlowViaCurl($authKey, $payload);
        }
    }

    /**
     * Fallback when Guzzle DNS fails (common on php artisan serve / macOS).
     *
     * @param  array<string, mixed>  $payload
     * @return array{http_ok: bool, status: int, data: array<string, mixed>, body: string}
     */
    private function postMsg91FlowViaCurl(string $authKey, array $payload): array
    {
        $json = json_encode($payload, JSON_UNESCAPED_UNICODE);
        if ($json === false) {
            throw new \RuntimeException('Could not encode MSG91 payload.');
        }

        $ch = curl_init('https://control.msg91.com/api/v5/flow');
        curl_setopt_array($ch, [
            \CURLOPT_POST => true,
            \CURLOPT_POSTFIELDS => $json,
            \CURLOPT_RETURNTRANSFER => true,
            \CURLOPT_TIMEOUT => 45,
            \CURLOPT_CONNECTTIMEOUT => 25,
            \CURLOPT_IPRESOLVE => \CURL_IPRESOLVE_V4,
            \CURLOPT_HTTPHEADER => [
                'authkey: '.$authKey,
                'accept: application/json',
                'content-type: application/json',
            ],
        ]);

        $body = curl_exec($ch);
        $errno = curl_errno($ch);
        $error = curl_error($ch);
        $status = (int) curl_getinfo($ch, \CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($errno !== 0 || $body === false) {
            throw new \RuntimeException($error !== '' ? $error : 'MSG91 curl request failed (code '.$errno.')');
        }

        $data = json_decode((string) $body, true);

        return [
            'http_ok' => $status >= 200 && $status < 300,
            'status' => $status,
            'data' => is_array($data) ? $data : [],
            'body' => trim((string) $body),
        ];
    }

    private function isRetryableMsg91Error(string $message): bool
    {
        return str_contains($message, 'timed out')
            || str_contains($message, 'Timeout')
            || str_contains($message, 'Could not resolve')
            || str_contains($message, 'name lookup')
            || str_contains($message, 'Connection')
            || str_contains($message, 'cURL error 6')
            || str_contains($message, 'cURL error 28');
    }

    private function formatSmsExceptionMessage(\Throwable $e): string
    {
        $msg = $e->getMessage();

        if (str_contains($msg, 'Could not resolve')
            || str_contains($msg, 'name lookup')
            || str_contains($msg, 'cURL error 6')) {
            return 'Could not reach SMS server (MSG91). Check internet / Wi‑Fi, then try Send OTP again.';
        }

        if (str_contains($msg, 'cURL error 28')
            || (str_contains($msg, 'timed out') && ! str_contains($msg, 'name lookup'))) {
            return 'SMS service is slow right now. Please wait 10 seconds and tap Send OTP again.';
        }

        if (str_contains($msg, 'Connection refused')) {
            return 'Could not connect to SMS server. Please try again in a moment.';
        }

        if (config('app.debug')) {
            return 'Could not send OTP: '.$msg;
        }

        return 'Could not send OTP. Please try again in a moment.';
    }

    private function isMsg91TemplateError(string $message): bool
    {
        return stripos($message, 'template') !== false
            || stripos($message, 'invalid') !== false
            || stripos($message, 'missing') !== false;
    }

    private function otpExpiryMinutes(): int
    {
        return max(5, (int) config('services.msg91.otp_expiry_minutes', 10));
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    private function recordVerifyAttempt(array $meta, string $ip): void
    {
        $log = $this->resolveOtpLog($meta);
        if (! $log) {
            return;
        }

        $log->verify_attempts = (int) $log->verify_attempts + 1;
        $log->last_verify_attempt_at = now();
        $log->last_verify_attempt_ip = $ip !== '' ? $ip : null;
        $log->save();
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    private function markOtpLogVerified(array $meta, \Illuminate\Support\Carbon $verifiedAt, string $ip): void
    {
        $log = $this->resolveOtpLog($meta);
        if (! $log) {
            return;
        }

        $log->verified_at = $verifiedAt;
        $log->verified_ip = $ip !== '' ? $ip : null;
        $log->save();
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    private function resolveOtpLog(array $meta): ?DoctorRegistrationOtpLog
    {
        if (! empty($meta['log_id'])) {
            $log = DoctorRegistrationOtpLog::query()->find((int) $meta['log_id']);
            if ($log && $log->registration_type === $this->registrationType) {
                return $log;
            }
        }

        $mobile = $this->normalizeMobile($meta['mobile'] ?? '');
        if ($mobile === '') {
            return null;
        }

        return DoctorRegistrationOtpLog::query()
            ->where('registration_type', $this->registrationType)
            ->where('mobile', $mobile)
            ->whereNull('verified_at')
            ->orderByDesc('sent_at')
            ->first();
    }
}
