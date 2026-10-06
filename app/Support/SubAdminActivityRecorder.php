<?php

namespace App\Support;

use App\Models\SubAdminActivityLog;
use Illuminate\Database\Eloquent\Model;

/**
 * Collects Eloquent changes made while a Sub Admin request is being handled.
 */
class SubAdminActivityRecorder
{
    private const MAX_CHANGES = 200;

    private const HIDDEN_FIELDS = ['password', 'remember_token', 'api_token', 'otp', 'two_factor_secret', 'two_factor_recovery_codes'];

    /** Speak Up must stay confidential (anonymous submitters), so it never reaches the activity log. */
    private const IGNORED_MODELS = [
        SubAdminActivityLog::class,
        \App\Models\SpeakUpSubmission::class,
        \App\Models\SpeakUpReply::class,
        \App\Models\SpeakUpAccessLog::class,
        \App\Models\SpeakUpSetting::class,
    ];

    private bool $active = false;

    /** @var list<array<string, mixed>> */
    private array $changes = [];

    public function start(): void
    {
        $this->active = true;
        $this->changes = [];
    }

    public function stop(): void
    {
        $this->active = false;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function changes(): array
    {
        return $this->changes;
    }

    public function capture(string $event, Model $model): void
    {
        if (! $this->active || in_array(get_class($model), self::IGNORED_MODELS, true) || count($this->changes) >= self::MAX_CHANGES) {
            return;
        }

        $entry = [
            'event' => $event,
            'model' => class_basename($model),
            'id' => $model->getKey(),
        ];

        if ($event === 'updated') {
            $new = $this->clean($model->getChanges());
            unset($new['updated_at']);
            if ($new === []) {
                return;
            }
            $old = [];
            foreach (array_keys($new) as $field) {
                $old[$field] = $model->getOriginal($field);
            }
            $entry['old'] = $this->clean($old);
            $entry['new'] = $new;
        } elseif ($event === 'created') {
            $entry['new'] = $this->clean($model->getAttributes());
        } else {
            $entry['old'] = $this->clean($model->getOriginal());
        }

        $this->changes[] = $entry;
    }

    /**
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    private function clean(array $values): array
    {
        foreach ($values as $key => $value) {
            if (in_array($key, self::HIDDEN_FIELDS, true)) {
                $values[$key] = '***';
            } elseif (is_string($value) && mb_strlen($value) > 1000) {
                $values[$key] = mb_substr($value, 0, 1000).'…';
            } elseif ($value instanceof \DateTimeInterface) {
                $values[$key] = $value->format('Y-m-d H:i:s');
            } elseif (is_object($value)) {
                $values[$key] = method_exists($value, '__toString') ? (string) $value : get_class($value);
            }
        }

        return $values;
    }
}
