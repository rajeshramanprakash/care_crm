<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vendor extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'contact_no',
        'email',
        'account_name',
        'account_number',
        'ifsc_code',
        'upi_id',
        'service_city_shifts',
        'shift',
        'status'
    ];

    protected $casts = [
        'service_city_shifts' => 'array'
    ];

    // Relationships
    public function payments()
    {
        return $this->hasMany(VendorPayment::class);
    }

    // Accessors
    public function getTotalPaidAttribute()
    {
        return $this->payments()->completed()->sum('amount');
    }

    public function getLastPaymentDateAttribute()
    {
        return $this->payments()->latest('payment_date')->first()?->payment_date;
    }
}
