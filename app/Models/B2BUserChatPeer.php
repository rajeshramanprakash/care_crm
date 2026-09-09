<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class B2BUserChatPeer extends Model
{
    protected $table = 'b2b_user_chat_peers';

    protected $guarded = [];

    public function b2bUser(): BelongsTo
    {
        return $this->belongsTo(B2BUser::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
