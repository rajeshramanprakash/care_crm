<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentInvoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'operation_lead_id',
        'invoice_id',
        'from_date',
        'to_date',
        'payment_amount',
        'is_received',
        'work_days',
        'remark',
    ];

    protected $casts = [
        'from_date' => 'datetime',
        'to_date' => 'datetime',
        'is_received' => 'boolean',
        'payment_amount' => 'decimal:2',
    ];

    /**
     * Relationship to Operation Lead
     */
    public function operationLead()
    {
        return $this->belongsTo(OperationLead::class);
    }

    /**
     * Relationship to Received Payments
     */
    public function receivedPayments()
    {
        return $this->hasMany(ReceivedPayment::class);
    }

    /**
     * Generate a unique invoice ID
     */
    public static function generateInvoiceId()
    {
        $date = date('Ymd');
        $lastInvoice = self::whereDate('created_at', today())
            ->orderBy('id', 'desc')
            ->first();
        
        $sequence = $lastInvoice ? (int)substr($lastInvoice->invoice_id, -4) + 1 : 1;
        
        return 'INV-' . $date . '-' . str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Calculate total received amount for this invoice
     */
    public function getTotalReceivedAttribute()
    {
        return $this->receivedPayments()->sum('amount');
    }

    /**
     * Calculate outstanding amount for this invoice
     */
    public function getOutstandingAmountAttribute()
    {
        return $this->payment_amount - $this->total_received;
    }
}
