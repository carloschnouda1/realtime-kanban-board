<?php

namespace App\Events;

use App\Http\Resources\CardResource;
use App\Models\Card;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CardUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Card $card) {}

    public function broadcastOn(): array
    {
        return [new Channel('board.'.$this->card->boardColumn->board_id)];
    }

    public function broadcastAs(): string
    {
        return 'card.updated';
    }

    public function broadcastWith(): array
    {
        return ['card' => (new CardResource($this->card))->resolve()];
    }
}
