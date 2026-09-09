<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'description'];

    public function locations()
    {
        return $this->belongsToMany(Location::class, 'location_services')
                    ->withPivot(['price_12hr', 'price_24hr', 'price_onetime'])
                    ->withTimestamps();
    }
}
