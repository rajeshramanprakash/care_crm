<?php

namespace App\Observers;

use App\Models\CustomerChatMessage;
use App\Support\DoctorCustomerChatRealtime;

class CustomerChatMessageObserver
{
    public function created(CustomerChatMessage $message): void
    {
        DoctorCustomerChatRealtime::broadcastMessageCreated($message);
    }
}
