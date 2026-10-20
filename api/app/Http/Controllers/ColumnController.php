<?php

namespace App\Http\Controllers;

use App\Events\ColumnCreated;
use App\Http\Resources\BoardColumnResource;
use App\Models\Board;
use Illuminate\Http\Request;

class ColumnController extends Controller
{
    public function store(Request $request, Board $board)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $position = ($board->columns()->max('position') ?? -1) + 1;

        $column = $board->columns()->create([
            'name' => $data['name'],
            'position' => $position,
        ]);

        broadcast(new ColumnCreated($column))->toOthers();

        return (new BoardColumnResource($column))->response()->setStatusCode(201);
    }
}
