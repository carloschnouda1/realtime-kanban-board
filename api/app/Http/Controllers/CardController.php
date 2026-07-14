<?php

namespace App\Http\Controllers;

use App\Events\CardCreated;
use App\Events\CardDeleted;
use App\Events\CardMoved;
use App\Events\CardUpdated;
use App\Http\Resources\CardResource;
use App\Models\BoardColumn;
use App\Models\Card;
use Illuminate\Http\Request;

class CardController extends Controller
{
    public function store(Request $request, BoardColumn $column)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        $position = $column->cards()->max('position') + 1;

        $card = $column->cards()->create([...$data, 'position' => $position]);

        broadcast(new CardCreated($card))->toOthers();

        return new CardResource($card);
    }

    public function update(Request $request, Card $card)
    {
        $data = $request->validate([
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        $card->update($data);

        broadcast(new CardUpdated($card))->toOthers();

        return new CardResource($card);
    }

    public function move(Request $request, Card $card)
    {
        $data = $request->validate([
            'board_column_id' => ['required', 'exists:board_columns,id'],
            'position' => ['required', 'integer', 'min:0'],
        ]);

        $card->update($data);

        $boardId = $card->boardColumn()->first()->board_id;

        broadcast(new CardMoved($card->fresh(), $boardId))->toOthers();

        return new CardResource($card);
    }

    public function destroy(Card $card)
    {
        $cardId = $card->id;
        $boardId = $card->boardColumn->board_id;

        $card->delete();

        broadcast(new CardDeleted($cardId, $boardId))->toOthers();

        return response()->noContent();
    }
}
