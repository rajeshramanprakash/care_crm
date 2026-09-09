<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    use HasFactory;

    protected $fillable = ['name'];

    public function services()
    {
        return $this->belongsToMany(Service::class, 'location_services')
                    ->withPivot(['price_12hr', 'price_24hr', 'price_onetime'])
                    ->withTimestamps();
    }
}
