<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CardDeleted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public int $cardId, public string $boardId)
    {
    }

    public function broadcastOn(): array
    {
        return [new Channel('board.'.$this->boardId)];
    }

    public function broadcastAs(): string
    {
        return 'card.deleted';
    }

    public function broadcastWith(): array
    {
        return ['cardId' => $this->cardId];
    }
}
