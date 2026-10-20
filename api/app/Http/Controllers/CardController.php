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
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CardController extends Controller
{
    public function store(Request $request, BoardColumn $column)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
        ]);

        $position = ($column->cards()->max('position') ?? -1) + 1;

        $card = $column->cards()->create([...$data, 'position' => $position]);

        broadcast(new CardCreated($card))->toOthers();

        return (new CardResource($card))->response()->setStatusCode(201);
    }

    public function update(Request $request, Card $card)
    {
        $data = $request->validate([
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
        ]);

        $card->update($data);

        broadcast(new CardUpdated($card))->toOthers();

        return new CardResource($card);
    }

    public function move(Request $request, Card $card)
    {
        $data = $request->validate([
            'board_column_id' => ['required', 'integer', 'exists:board_columns,id'],
            'position' => ['required', 'integer', 'min:0'],
        ]);

        $source = $card->boardColumn;
        $destination = BoardColumn::findOrFail($data['board_column_id']);

        // A card may only move between columns of its own board.
        if ($destination->board_id !== $source->board_id) {
            throw ValidationException::withMessages([
                'board_column_id' => 'The column belongs to a different board.',
            ]);
        }

        $changed = DB::transaction(function () use ($card, $source, $destination, $data) {
            // Take the card out of its current ordering, then insert it at the
            // requested index (clamped to the end of the list).
            $sourceIds = $source->cards()->pluck('id')->reject(fn ($id) => $id === $card->id)->values();
            $destinationIds = $source->is($destination)
                ? $sourceIds
                : $destination->cards()->pluck('id');

            $index = min($data['position'], $destinationIds->count());
            $destinationIds = $destinationIds->splice(0, $index)
                ->push($card->id)
                ->concat($destinationIds)
                ->values();

            $card->update(['board_column_id' => $destination->id]);

            $affected = $source->is($destination)
                ? [$destination->id => $destinationIds]
                : [$source->id => $sourceIds, $destination->id => $destinationIds];

            foreach ($affected as $ids) {
                foreach ($ids as $position => $id) {
                    Card::whereKey($id)->update(['position' => $position]);
                }
            }

            return array_keys($affected);
        });

        $columns = BoardColumn::with('cards')
            ->whereIn('id', $changed)
            ->get()
            ->map(fn (BoardColumn $column) => [
                'id' => $column->id,
                'cards' => CardResource::collection($column->cards)->resolve(),
            ])
            ->all();

        broadcast(new CardMoved($destination->board_id, $columns))->toOthers();

        return response()->json(['data' => ['columns' => $columns]]);
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
