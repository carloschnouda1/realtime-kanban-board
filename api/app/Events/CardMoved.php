<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Broadcast after a card changes column and/or position.
 *
 * Moving a card renumbers its neighbours, so the payload carries the complete
 * card list of every column that changed. Clients replace those columns
 * wholesale instead of trying to replay the reordering themselves.
 */
class CardMoved implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets;

    /**
     * @param  array<int, array{id: int, cards: array<int, array<string, mixed>>}>  $columns
     */
    public function __construct(public string $boardId, public array $columns)
    {
    }

    public function broadcastOn(): array
    {
        return [new Channel('board.'.$this->boardId)];
    }

    public function broadcastAs(): string
    {
        return 'card.moved';
    }

    public function broadcastWith(): array
    {
        return ['columns' => $this->columns];
    }
}
