<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class SpeakUpSetting extends Model
{
    protected $guarded = [];

    protected $hidden = ['password_hash'];

    protected $casts = [
        'password_changed_at' => 'datetime',
    ];

    public static function current(): self
    {
        return static::query()->first() ?? static::create(['secret_slug' => static::newSlug()]);
    }

    public static function newSlug(): string
    {
        return 'sp-' . Str::lower(Str::random(32));
    }

    public function hasPassword(): bool
    {
        return ! empty($this->password_hash);
    }
}
