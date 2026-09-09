<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorServicePriceChangeRequest extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const TYPE_12HR = '12hr';

    public const TYPE_24HR = '24hr';

    public const TYPE_ONETIME = 'onetime';

    protected $fillable = [
        'vendor_id',
        'service_id',
        'service_name',
        'service_sub_service_id',
        'sub_service_name',
        'price_type',
        'current_price',
        'requested_price',
        'status',
        'admin_note',
        'reviewed_by',
        'reviewed_at',
    ];

    protected $casts = [
        'service_sub_service_id' => 'integer',
        'current_price' => 'decimal:2',
        'requested_price' => 'decimal:2',
        'reviewed_at' => 'datetime',
    ];

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public static function priceTypes(): array
    {
        return [self::TYPE_12HR, self::TYPE_24HR, self::TYPE_ONETIME];
    }

    public function priceTypeLabel(): string
    {
        return match ($this->price_type) {
            self::TYPE_12HR => '12 Hours',
            self::TYPE_24HR => '24 Hours',
            self::TYPE_ONETIME => 'One-time',
            default => (string) $this->price_type,
        };
    }
}
