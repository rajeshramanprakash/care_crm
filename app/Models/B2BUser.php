<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class B2BUser extends Model
{
    public const TYPE_LEGACY = 'legacy';

    public const TYPE_CORPORATE = 'b2b_corporate';

    public const TYPE_INDIVIDUAL = 'b2b_individual';

    protected $table = 'b2b_users';

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
        'chat_enabled' => 'boolean',
        'bulk_requirement_qty' => 'integer',
        'commission_percent' => 'float',
    ];

    public function referenceUser(): BelongsTo
    {
        return $this->belongsTo(B2BReferenceUser::class, 'b2b_reference_user_id');
    }

    public function leads(): HasMany
    {
        return $this->hasMany(B2BLead::class);
    }

    public function chatPeerLinks(): HasMany
    {
        return $this->hasMany(B2BUserChatPeer::class);
    }

    public function chatPeers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'b2b_user_chat_peers', 'b2b_user_id', 'user_id')
            ->withTimestamps();
    }

    public function scopeLegacy(Builder $query): Builder
    {
        return $query->where('account_type', self::TYPE_LEGACY);
    }

    public function scopeCorporate(Builder $query): Builder
    {
        return $query->where('account_type', self::TYPE_CORPORATE);
    }

    public function scopeIndividual(Builder $query): Builder
    {
        return $query->where('account_type', self::TYPE_INDIVIDUAL);
    }

    public function isLegacy(): bool
    {
        return $this->account_type === self::TYPE_LEGACY;
    }

    public function isCorporate(): bool
    {
        return $this->account_type === self::TYPE_CORPORATE;
    }

    public function isIndividual(): bool
    {
        return $this->account_type === self::TYPE_INDIVIDUAL;
    }

    public function portalLabel(): string
    {
        return match ($this->account_type) {
            self::TYPE_CORPORATE => 'B2B Corporate',
            self::TYPE_INDIVIDUAL => 'Individual Partner',
            default => 'B2B Partner',
        };
    }
}
