<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FavoriteGroup extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'group_id'];

    /**
     * Get the user that favorited the group
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the favorited group
     */
    public function group()
    {
        return $this->belongsTo(Group::class);
    }
}
