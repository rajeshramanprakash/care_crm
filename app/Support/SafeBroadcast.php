<?php

namespace App\Support;

use Illuminate\Support\Facades\Log;
use Throwable;

class SafeBroadcast
{
    public static function toOthers(object $event): void
    {
        try {
            broadcast($event)->toOthers();
        } catch (Throwable $e) {
            Log::warning('Realtime broadcast failed', [
                'event' => get_class($event),
                'error' => $e->getMessage(),
            ]);
        }
    }
}
