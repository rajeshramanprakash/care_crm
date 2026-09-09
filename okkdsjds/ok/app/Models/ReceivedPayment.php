<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReceivedPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'payment_invoice_id',
        'operation_lead_id',
        'amount',
        'received_date',
        'utr_number',
        'screenshot',
        'remark',
    ];

    protected $casts = [
        'received_date' => 'datetime',
        'amount' => 'decimal:2',
    ];

    /**
     * Relationship to Payment Invoice
     */
    public function paymentInvoice()
    {
        return $this->belongsTo(PaymentInvoice::class);
    }

    /**
     * Relationship to Operation Lead
     */
    public function operationLead()
    {
        return $this->belongsTo(OperationLead::class);
    }
}
