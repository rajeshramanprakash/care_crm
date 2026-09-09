<?php

namespace App\Services;

use App\Models\MissedCallbackSequence;
use App\Models\MissedCallbackStepConfig;
use Carbon\Carbon;

class MissedCallbackTimeCalculator
{
    public static function timezone(): string
    {
        return config('app.timezone', 'Asia/Kolkata');
    }

    /**
     * After completing step $completedStepIndex (1..10), when should step $completedStepIndex+1 run?
     */
    public function nextRunAfterCompletedStep(
        MissedCallbackSequence $sequence,
        int $completedStepIndex,
        Carbon $fromUtc
    ): ?Carbon {
        $nextStepIndex = $completedStepIndex + 1;
        $cfg = MissedCallbackStepConfig::query()
            ->where('step_index', $nextStepIndex)
            ->where('is_active', true)
            ->first();

        if (! $cfg) {
            return null;
        }

        $from = $fromUtc->copy()->timezone(self::timezone());

        return match ($cfg->kind) {
            MissedCallbackStepConfig::KIND_IMMEDIATE => $from,
            MissedCallbackStepConfig::KIND_DELAY_MINUTES => $from->copy()->addMinutes(max(0, (int) ($cfg->delay_minutes ?? 0))),
            MissedCallbackStepConfig::KIND_CALENDAR_WINDOW => $this->nextCalendarWindowSlot($sequence, $cfg, $from),
            default => $from,
        };
    }

    /**
     * First run (step 1): config row 1 is usually "immediate".
     */
    public function nextRunForStepIndex(
        MissedCallbackSequence $sequence,
        int $stepIndex,
        Carbon $fromUtc
    ): ?Carbon {
        $cfg = MissedCallbackStepConfig::query()
            ->where('step_index', $stepIndex)
            ->where('is_active', true)
            ->first();

        if (! $cfg) {
            return null;
        }

        $from = $fromUtc->copy()->timezone(self::timezone());

        return match ($cfg->kind) {
            MissedCallbackStepConfig::KIND_IMMEDIATE => $from,
            MissedCallbackStepConfig::KIND_DELAY_MINUTES => $from->copy()->addMinutes(max(0, (int) ($cfg->delay_minutes ?? 0))),
            MissedCallbackStepConfig::KIND_CALENDAR_WINDOW => $this->nextCalendarWindowSlot($sequence, $cfg, $from),
            default => $from,
        };
    }

    private function nextCalendarWindowSlot(
        MissedCallbackSequence $sequence,
        MissedCallbackStepConfig $cfg,
        Carbon $from
    ): Carbon {
        $tz = self::timezone();
        $from = $from->copy()->timezone($tz);
        $anchor = $sequence->created_at->copy()->timezone($tz)->startOfDay();

        $ws = substr((string) $cfg->window_start, 0, 8);
        $we = substr((string) $cfg->window_end, 0, 8);

        $firstDay = $anchor->copy()->addDays((int) $cfg->day_offset);

        for ($i = 0; $i < 400; $i++) {
            $day = $firstDay->copy()->addDays($i);
            $slotStart = $day->copy()->setTimeFromTimeString($ws);
            $slotEnd = $day->copy()->setTimeFromTimeString($we);

            if ($from->greaterThan($slotEnd)) {
                continue;
            }

            if ($from->lessThan($slotStart)) {
                return $slotStart->copy()->timezone('UTC');
            }

            return $from->copy()->timezone('UTC');
        }

        return $from->copy()->addHour()->timezone('UTC');
    }
}
