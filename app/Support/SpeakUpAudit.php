<?php

namespace App\Support;

use App\Models\SpeakUpAccessLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Writes the Speak Up access trail: who opened what, when, from which IP / browser / device.
 */
class SpeakUpAudit
{
    public const CLIENT_INFO_KEYS = ['timezone', 'tz_offset', 'screen', 'viewport', 'pixel_ratio', 'language', 'languages', 'platform', 'cores', 'memory', 'touch', 'cookies', 'local_time'];

    public static function log(Request $request, string $event, bool $success = true, ?int $submissionId = null, ?string $details = null, ?array $clientInfo = null): void
    {
        try {
            $user = $request->user();
            $ua = (string) $request->userAgent();
            $parsed = static::parseUserAgent($ua);

            SpeakUpAccessLog::create([
                'user_id' => $user?->id,
                'user_name' => $user ? (trim($user->f_name . ' ' . $user->l_name) ?: $user->email) : null,
                'event' => $event,
                'success' => $success,
                'speak_up_submission_id' => $submissionId,
                'details' => $details !== null ? Str::limit($details, 490, '') : null,
                'ip_address' => $request->ip(),
                'forwarded_for' => Str::limit((string) $request->header('X-Forwarded-For'), 250, '') ?: null,
                'user_agent' => Str::limit($ua, 2000, '') ?: null,
                'browser' => $parsed['browser'],
                'platform' => $parsed['platform'],
                'device' => $parsed['device'],
                'url' => static::maskedUrl($request),
                'method' => $request->method(),
                'referrer' => Str::limit((string) $request->headers->get('referer'), 2000, '') ?: null,
                'accept_language' => Str::limit((string) $request->header('Accept-Language'), 250, '') ?: null,
                'session_hash' => $request->hasSession() ? substr(hash('sha256', $request->session()->getId()), 0, 16) : null,
                'client_info' => $clientInfo ?: null,
            ]);
        } catch (\Throwable $e) {
            Log::warning('Speak Up access log failed: ' . $e->getMessage());
        }
    }

    public static function clientInfo(Request $request): ?array
    {
        $raw = $request->input('client_info');
        if (! is_string($raw) || $raw === '') {
            return null;
        }
        $data = json_decode($raw, true);
        if (! is_array($data)) {
            return null;
        }

        $clean = [];
        foreach (self::CLIENT_INFO_KEYS as $key) {
            if (isset($data[$key]) && is_scalar($data[$key])) {
                $clean[$key] = Str::limit((string) $data[$key], 120, '');
            }
        }

        return $clean ?: null;
    }

    /** The secret slug is replaced so the access log itself never leaks the URL. */
    private static function maskedUrl(Request $request): string
    {
        $url = $request->fullUrl();
        $slug = (string) $request->route('slug');

        return Str::limit($slug !== '' ? str_replace($slug, '{secret}', $url) : $url, 2000, '');
    }

    /**
     * @return array{browser: ?string, platform: ?string, device: ?string}
     */
    public static function parseUserAgent(string $ua): array
    {
        if ($ua === '') {
            return ['browser' => null, 'platform' => null, 'device' => null];
        }

        $browsers = [
            'Edge' => '/Edg(?:e|A|iOS)?\/([\d.]+)/',
            'Opera' => '/(?:OPR|Opera)\/([\d.]+)/',
            'Samsung Internet' => '/SamsungBrowser\/([\d.]+)/',
            'Firefox' => '/(?:Firefox|FxiOS)\/([\d.]+)/',
            'Chrome' => '/(?:Chrome|CriOS)\/([\d.]+)/',
            'Safari' => '/Version\/([\d.]+).*Safari/',
        ];
        $browser = 'Other';
        foreach ($browsers as $name => $pattern) {
            if (preg_match($pattern, $ua, $m)) {
                $browser = $name . ' ' . explode('.', $m[1])[0];
                break;
            }
        }

        $platforms = [
            'Android' => '/Android ([\d.]+)/',
            'iOS' => '/(?:iPhone|iPad|iPod).*OS ([\d_]+)/',
            'Windows' => '/Windows NT ([\d.]+)/',
            'macOS' => '/Mac OS X ([\d_.]+)/',
            'Linux' => '/Linux/',
        ];
        $platform = 'Other';
        foreach ($platforms as $name => $pattern) {
            if (preg_match($pattern, $ua, $m)) {
                $platform = trim($name . ' ' . str_replace('_', '.', $m[1] ?? ''));
                break;
            }
        }

        $device = preg_match('/iPad|Tablet/i', $ua) ? 'Tablet'
            : (preg_match('/Mobile|iPhone|Android/i', $ua) ? 'Mobile'
            : (preg_match('/bot|crawl|spider|curl|wget|python/i', $ua) ? 'Bot / Script' : 'Desktop'));

        return ['browser' => Str::limit($browser, 58, ''), 'platform' => Str::limit($platform, 58, ''), 'device' => $device];
    }
}
