<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FavoriteChat extends Model
{
    protected $fillable = ['user_id', 'favorite_user_id'];
}
