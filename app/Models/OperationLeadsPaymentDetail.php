<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OperationLeadsPaymentDetail extends Model
{
    use HasFactory;

    protected $guarded = [];
    protected $table = 'operation_leads_payment_details';

    protected $fillable = [
        'operation_lead_id',
        'payment_received',
        'refund_amount',
        'utr_number',
        'received_date',
        'from_date_time',
        'to_date_time',
        'screenshot',
        'outstanding_payment',
        'remark'
    ];

    protected $casts = [
        'payment_received' => 'decimal:2',
        'refund_amount' => 'decimal:2',
        'outstanding_payment' => 'decimal:2',
        'received_date' => 'datetime',
        'from_date_time' => 'datetime',
        'to_date_time' => 'datetime',
    ];

    /**
     * Get the operation lead that owns this payment detail
     */
    public function operationLead()
    {
        return $this->belongsTo(OperationLead::class, 'operation_lead_id');
    }

    /**
     * Check if UTR number already exists
     */
    public static function utrNumberExists($utrNumber, $excludeId = null)
    {
        $query = self::where('utr_number', $utrNumber);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }
}
