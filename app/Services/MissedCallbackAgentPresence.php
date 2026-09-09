<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Cache;

class MissedCallbackAgentPresence
{
    private const CACHE_PREFIX = 'mcb_agent_line:';

    public function refreshFromTataWebhook(?User $user, array $data): void
    {
        if (! $user) {
            return;
        }

        $status = strtolower(trim((string) ($data['call_status'] ?? '')));

        if (in_array($status, ['ringing', 'dialing', 'in-progress', 'in_progress', 'progress'], true)) {
            Cache::put(self::CACHE_PREFIX.$user->id, 'ringing', now()->addMinutes(3));
        } elseif (in_array($status, ['answered'], true)) {
            Cache::put(self::CACHE_PREFIX.$user->id, 'on_call', now()->addMinutes(20));
        } elseif ($status !== '') {
            Cache::put(self::CACHE_PREFIX.$user->id, 'idle', now()->addMinutes(2));
        }
    }

    public function isAgentBusy(User $user): bool
    {
        $v = Cache::get(self::CACHE_PREFIX.$user->id);

        return in_array($v, ['ringing', 'on_call'], true);
    }
}
