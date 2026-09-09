<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class B2BReferenceUser extends Model
{
    protected $table = 'b2b_reference_users';

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function b2bUsers()
    {
        return $this->hasMany(B2BUser::class, 'b2b_reference_user_id');
    }
}
