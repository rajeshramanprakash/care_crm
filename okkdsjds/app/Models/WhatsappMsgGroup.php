<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WhatsappMsgGroup extends Model
{
    use HasFactory;

    protected $table = 'whatsapp_msg_group';

    protected $fillable = [
        'whatsapp_number',
        'executive_ids',
    ];
}