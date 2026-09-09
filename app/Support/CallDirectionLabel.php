<?php

namespace App\Support;

class CallDirectionLabel
{
    /**
     * Human label for Tata / dialplan rows: Inbound, Outbound, or em dash if unknown.
     */
    public static function display(?string $callType, $rawData = null): string
    {
        $raw = is_array($rawData) ? $rawData : [];

        $stored = strtolower(trim((string) $callType));
        if ($stored === 'outbound') {
            return 'Outbound';
        }
        if ($stored === 'inbound') {
            return 'Inbound';
        }

        $dir = strtolower(trim((string) ($raw['direction'] ?? '')));
        if (in_array($dir, ['outbound', 'clicktocall', 'click_to_call', 'click-to-call'], true)) {
            return 'Outbound';
        }
        if ($dir === 'inbound') {
            return 'Inbound';
        }

        return '—';
    }
}
