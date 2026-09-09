<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DirectChatMessagesRead implements ShouldBroadcast
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public int $readerId,
        public int $senderId
    ) {
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('chat.user.'.$this->readerId),
            new PrivateChannel('chat.user.'.$this->senderId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'direct.messages.read';
    }

    public function broadcastWith(): array
    {
        return [
            'reader_id' => $this->readerId,
            'sender_id' => $this->senderId,
        ];
    }
}
