<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EasebuzzPaymentLink extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_PAID = 'paid';

    public const STATUS_FAILED = 'failed';

    public const STATUS_EXPIRED = 'expired';

    protected $fillable = [
        'created_by',
        'merchant_txn',
        'customer_name',
        'email',
        'phone',
        'message',
        'amount',
        'payment_url',
        'expire_at',
        'status',
        'easebuzz_create_response',
        'verified_at',
        'easebuzz_verify_response',
        'paid_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'expire_at' => 'datetime',
        'verified_at' => 'datetime',
        'paid_at' => 'datetime',
        'easebuzz_create_response' => 'array',
        'easebuzz_verify_response' => 'array',
    ];

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('created_by', $userId);
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopePaid($query)
    {
        return $query->where('status', self::STATUS_PAID);
    }

    public function isExpired(): bool
    {
        return $this->expire_at !== null && $this->expire_at->isPast();
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_PAID => 'Paid',
            self::STATUS_FAILED => 'Failed',
            self::STATUS_EXPIRED => 'Expired',
            default => $this->isExpired() ? 'Expired' : 'Pending',
        };
    }

    public function statusBadgeClass(): string
    {
        return match ($this->status) {
            self::STATUS_PAID => 'badge-success',
            self::STATUS_FAILED => 'badge-danger',
            self::STATUS_EXPIRED => 'badge-secondary',
            default => $this->isExpired() ? 'badge-secondary' : 'badge-warning',
        };
    }
}
