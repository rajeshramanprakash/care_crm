<?php

namespace App\Http\Controllers\SpeakUp;

use App\Http\Controllers\Controller;
use App\Models\SpeakUpAccessLog;
use App\Models\SpeakUpSetting;
use App\Models\SpeakUpSubmission;
use App\Support\SpeakUpAudit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Admin-only Speak Up review area behind a secret URL + separate password.
 * Every visit (including wrong URLs and non-admin visitors) is written to speak_up_access_logs.
 */
class SpeakUpReviewController extends Controller
{
    public const UNLOCK_MINUTES = 30;

    public const MAX_ATTEMPTS = 5;

    public const LOCK_SECONDS = 900;

    private const SESSION_KEY = 'speak_up_review_unlock';

    public function gate(Request $request, string $slug)
    {
        if ($denied = $this->guard($request, $slug, 'page_opened')) {
            return $denied;
        }

        $setting = SpeakUpSetting::current();
        if ($this->isUnlocked($request, $setting)) {
            return redirect()->route('speak_up.review.cases', $slug);
        }

        return view('speak_up.review.gate', [
            'slug' => $slug,
            'hasPassword' => $setting->hasPassword(),
            'lockedFor' => RateLimiter::tooManyAttempts($this->limiterKey($request), self::MAX_ATTEMPTS)
                ? RateLimiter::availableIn($this->limiterKey($request)) : 0,
        ]);
    }

    public function deviceInfo(Request $request, string $slug)
    {
        if ($this->guard($request, $slug, null)) {
            return response()->json([], 404);
        }
        SpeakUpAudit::log($request, 'device_info', true, null, null, SpeakUpAudit::clientInfo($request));

        return response()->json(['ok' => true]);
    }

    public function unlock(Request $request, string $slug)
    {
        if ($denied = $this->guard($request, $slug, null)) {
            return $denied;
        }

        $setting = SpeakUpSetting::current();
        $clientInfo = SpeakUpAudit::clientInfo($request);
        $key = $this->limiterKey($request);

        if (! $setting->hasPassword()) {
            return redirect()->route('speak_up.review.gate', $slug);
        }

        if (RateLimiter::tooManyAttempts($key, self::MAX_ATTEMPTS)) {
            SpeakUpAudit::log($request, 'password_locked', false, null, 'Locked, try after ' . RateLimiter::availableIn($key) . 's', $clientInfo);

            return back()->withErrors(['password' => 'Too many wrong attempts. Try again after ' . ceil(RateLimiter::availableIn($key) / 60) . ' minutes.']);
        }

        if (! Hash::check((string) $request->input('password'), $setting->password_hash)) {
            RateLimiter::hit($key, self::LOCK_SECONDS);
            $left = max(0, self::MAX_ATTEMPTS - RateLimiter::attempts($key));
            SpeakUpAudit::log($request, $left === 0 ? 'password_locked' : 'password_failed', false, null, 'Attempts left: ' . $left, $clientInfo);

            return back()->withErrors(['password' => $left === 0
                ? 'Too many wrong attempts. Locked for ' . (self::LOCK_SECONDS / 60) . ' minutes.'
                : 'Wrong password. Attempts left: ' . $left]);
        }

        RateLimiter::clear($key);
        $request->session()->put(self::SESSION_KEY, [
            'user_id' => Auth::id(),
            'slug_hash' => hash('sha256', $setting->secret_slug),
            'password_hash' => hash('sha256', (string) $setting->password_hash),
            'expires_at' => now()->addMinutes(self::UNLOCK_MINUTES)->timestamp,
        ]);
        SpeakUpAudit::log($request, 'unlocked', true, null, null, $clientInfo);

        return redirect()->route('speak_up.review.cases', $slug);
    }

    public function cases(Request $request, string $slug)
    {
        if ($blocked = $this->requireUnlocked($request, $slug)) {
            return $blocked;
        }

        $filter = $request->query('filter');
        $submissions = SpeakUpSubmission::query()
            ->when(in_array($filter, array_keys(SpeakUpSubmission::STATUSES), true), fn ($q) => $q->where('status', $filter))
            ->when($filter === 'anonymous', fn ($q) => $q->where('is_anonymous', true))
            ->when($filter === 'named', fn ($q) => $q->where('is_anonymous', false))
            ->when($request->filled('category'), fn ($q) => $q->where('category', $request->query('category')))
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = '%' . $request->query('q') . '%';
                $q->where(fn ($w) => $w->where('reference_no', 'like', $term)->orWhere('subject', 'like', $term)->orWhere('message', 'like', $term));
            })
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $counts = SpeakUpSubmission::query()->selectRaw('status, count(*) c')->groupBy('status')->pluck('c', 'status');
        SpeakUpAudit::log($request, 'list_viewed', true, null, $filter ? 'Filter: ' . $filter : null);

        return view('speak_up.review.cases', compact('slug', 'submissions', 'counts', 'filter'));
    }

    public function show(Request $request, string $slug, int $id)
    {
        if ($blocked = $this->requireUnlocked($request, $slug)) {
            return $blocked;
        }

        $submission = SpeakUpSubmission::with('replies')->findOrFail($id);
        SpeakUpAudit::log($request, 'case_viewed', true, $submission->id, $submission->reference_no);

        return view('speak_up.review.show', compact('slug', 'submission'));
    }

    public function updateStatus(Request $request, string $slug, int $id)
    {
        if ($blocked = $this->requireUnlocked($request, $slug)) {
            return $blocked;
        }

        $data = $request->validate(['status' => 'required|in:' . implode(',', array_keys(SpeakUpSubmission::STATUSES))]);
        $submission = SpeakUpSubmission::findOrFail($id);
        $old = $submission->status;
        $submission->update(['status' => $data['status']]);
        SpeakUpAudit::log($request, 'status_changed', true, $submission->id, $submission->reference_no . ': ' . $old . ' → ' . $data['status']);

        return redirect()->route('speak_up.review.show', [$slug, $submission->id])
            ->with('status', ['alert_type' => 'success', 'message' => 'Status updated.']);
    }

    public function reply(Request $request, string $slug, int $id)
    {
        if ($blocked = $this->requireUnlocked($request, $slug)) {
            return $blocked;
        }

        $data = $request->validate([
            'message' => 'required|string|max:5000',
            'visibility' => 'required|in:internal,public',
        ]);
        $submission = SpeakUpSubmission::findOrFail($id);
        $user = $request->user();
        $public = $data['visibility'] === 'public';

        $submission->replies()->create([
            'author_user_id' => $user->id,
            'author_name' => trim($user->f_name . ' ' . $user->l_name),
            'message' => $data['message'],
            'is_internal' => ! $public,
        ]);
        $changes = [];
        if ($public) {
            $changes['has_unread_reply'] = true;
        }
        if ($submission->status === 'new') {
            $changes['status'] = 'under_review';
        }
        if ($changes) {
            $submission->update($changes);
        }
        SpeakUpAudit::log($request, 'reply_added', true, $submission->id, $submission->reference_no . ($public ? ' (reply to submitter)' : ' (internal note)'));

        return redirect()->route('speak_up.review.show', [$slug, $submission->id])
            ->with('status', ['alert_type' => 'success', 'message' => $public ? 'Reply sent to submitter.' : 'Internal note saved.']);
    }

    public function accessLog(Request $request, string $slug)
    {
        if ($blocked = $this->requireUnlocked($request, $slug)) {
            return $blocked;
        }

        $logs = SpeakUpAccessLog::query()
            ->when($request->filled('event'), fn ($q) => $q->where('event', $request->query('event')))
            ->when($request->filled('from'), fn ($q) => $q->whereDate('created_at', '>=', $request->query('from')))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('created_at', '<=', $request->query('to')))
            ->orderByDesc('id')
            ->paginate(30)
            ->withQueryString();
        SpeakUpAudit::log($request, 'access_log_viewed');

        return view('speak_up.review.access_log', compact('slug', 'logs'));
    }

    public function lock(Request $request, string $slug)
    {
        if ($denied = $this->guard($request, $slug, null)) {
            return $denied;
        }
        $request->session()->forget(self::SESSION_KEY);
        SpeakUpAudit::log($request, 'locked');

        return redirect()->route('speak_up.review.gate', $slug);
    }

    /**
     * Logs the visit, then hides the area (404) from anyone who is not a logged-in Admin or uses a wrong slug.
     */
    private function guard(Request $request, string $slug, ?string $openEvent)
    {
        $setting = SpeakUpSetting::current();
        $isAdmin = Auth::check() && (int) $request->session()->get('logged_role') === 1;
        $validSlug = hash_equals($setting->secret_slug, $slug);

        if (! $validSlug) {
            SpeakUpAudit::log($request, 'invalid_url', false, null, $isAdmin ? null : 'Not admin');
            abort(404);
        }
        if (! $isAdmin) {
            SpeakUpAudit::log($request, 'access_denied', false, null, Auth::check() ? 'Logged in role id: ' . $request->session()->get('logged_role') : 'Not logged in');
            abort(404);
        }
        if ($openEvent) {
            SpeakUpAudit::log($request, $openEvent);
        }

        return null;
    }

    private function requireUnlocked(Request $request, string $slug)
    {
        if ($denied = $this->guard($request, $slug, null)) {
            return $denied;
        }
        if (! $this->isUnlocked($request, SpeakUpSetting::current())) {
            return redirect()->route('speak_up.review.gate', $slug)->withErrors(['password' => 'Please enter the Speak Up password.']);
        }

        $unlock = $request->session()->get(self::SESSION_KEY);
        $unlock['expires_at'] = now()->addMinutes(self::UNLOCK_MINUTES)->timestamp;
        $request->session()->put(self::SESSION_KEY, $unlock);

        return null;
    }

    private function isUnlocked(Request $request, SpeakUpSetting $setting): bool
    {
        $unlock = $request->session()->get(self::SESSION_KEY);
        if (! is_array($unlock) || ! $setting->hasPassword()) {
            return false;
        }

        $valid = ($unlock['user_id'] ?? null) === Auth::id()
            && hash_equals(hash('sha256', $setting->secret_slug), (string) ($unlock['slug_hash'] ?? ''))
            && hash_equals(hash('sha256', (string) $setting->password_hash), (string) ($unlock['password_hash'] ?? ''))
            && ($unlock['expires_at'] ?? 0) > now()->timestamp;

        if (! $valid) {
            $request->session()->forget(self::SESSION_KEY);
        }

        return $valid;
    }

    private function limiterKey(Request $request): string
    {
        return 'speak-up-unlock:' . (Auth::id() ?? 'guest') . '|' . $request->ip();
    }
}
