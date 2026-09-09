<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GroupMessageRead extends Model
{
    protected $fillable = [
        'user_id',
        'group_id',
        'group_message_id',
        'read_at',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function group()
    {
        return $this->belongsTo(Group::class);
    }

    public function groupMessage()
    {
        return $this->belongsTo(GroupMessage::class);
    }
}
