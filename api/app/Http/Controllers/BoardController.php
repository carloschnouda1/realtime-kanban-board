<?php

namespace App\Http\Controllers;

use App\Http\Resources\BoardResource;
use App\Models\Board;
use Illuminate\Http\Request;

class BoardController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $board = Board::create($data);

        foreach (['To Do', 'In Progress', 'Done'] as $index => $name) {
            $board->columns()->create(['name' => $name, 'position' => $index]);
        }

        return (new BoardResource($board->load('columns.cards')))->response()->setStatusCode(201);
    }

    public function show(Board $board)
    {
        return new BoardResource($board->load('columns.cards'));
    }
}
