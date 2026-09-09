<?php

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DirectChatMessageCreated implements ShouldBroadcast
{
    use Dispatchable, SerializesModels;

    public function __construct(public Message $message)
    {
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('chat.user.'.$this->message->sender_id),
            new PrivateChannel('chat.user.'.$this->message->receiver_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'direct.message.created';
    }

    public function broadcastWith(): array
    {
        return [
            'message' => $this->message->loadMissing(['sender', 'repliedTo.sender']),
        ];
    }
}
