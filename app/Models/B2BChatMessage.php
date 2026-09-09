<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class B2BChatMessage extends Model
{
    protected $table = 'b2b_chat_messages';

    protected $guarded = [];

    protected $casts = [
        'is_read' => 'boolean',
    ];

    public function b2bUser(): BelongsTo
    {
        return $this->belongsTo(B2BUser::class);
    }

    public function staffUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
