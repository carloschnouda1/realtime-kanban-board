<?php

namespace App\Events;

use App\Http\Resources\BoardColumnResource;
use App\Models\BoardColumn;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ColumnCreated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public BoardColumn $column)
    {
    }

    public function broadcastOn(): array
    {
        return [new Channel('board.'.$this->column->board_id)];
    }

    public function broadcastAs(): string
    {
        return 'column.created';
    }

    public function broadcastWith(): array
    {
        return ['column' => (new BoardColumnResource($this->column))->resolve()];
    }
}
