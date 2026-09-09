<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class B2BChatReadCursor extends Model
{
    protected $table = 'b2b_chat_read_cursors';

    protected $guarded = [];

    public function b2bUser(): BelongsTo
    {
        return $this->belongsTo(B2BUser::class);
    }
}
