<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Append-only audit trail of everything done on the Speak Up review area.
 */
class SpeakUpAccessLog extends Model
{
    public const UPDATED_AT = null;

    protected $guarded = [];

    protected $casts = [
        'success' => 'boolean',
        'client_info' => 'array',
        'created_at' => 'datetime',
    ];

    public const EVENTS = [
        'page_opened' => 'URL khola',
        'access_denied' => 'Admin ke alawa kisi ne URL khola (block)',
        'device_info' => 'Browser / device details',
        'password_failed' => 'Galat password',
        'password_locked' => 'Lock (bahut galat password)',
        'unlocked' => 'Password sahi — andar gaye',
        'list_viewed' => 'List dekhi',
        'case_viewed' => 'Case dekha',
        'status_changed' => 'Status badla',
        'reply_added' => 'Reply / note likha',
        'access_log_viewed' => 'Access log dekha',
        'locked' => 'Logout / lock kiya',
        'invalid_url' => 'Galat secret URL',
        'password_set' => 'Password set kiya',
        'password_changed' => 'Password badla',
        'password_change_failed' => 'Password badalne mein purana password galat',
        'url_regenerated' => 'Naya secret URL banaya',
        'password_reset_cli' => 'Server command se password reset',
    ];

    public function getEventLabelAttribute(): string
    {
        return self::EVENTS[$this->event] ?? $this->event;
    }

    public function save(array $options = [])
    {
        if ($this->exists) {
            return false;
        }

        return parent::save($options);
    }

    public function delete()
    {
        return false;
    }
}
