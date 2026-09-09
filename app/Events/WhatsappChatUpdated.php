<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WhatsappChatUpdated implements ShouldBroadcast
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public string $number,
        public string $eventType,
        public array $payload = []
    ) {
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('whatsapp.number.'.$this->number),
        ];
    }

    public function broadcastAs(): string
    {
        return 'whatsapp.chat.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'number' => $this->number,
            'event_type' => $this->eventType,
            'payload' => $this->payload,
        ];
    }
}
