<?php

namespace Database\Seeders;

use App\Models\Board;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Creates a sample board so the app has something to show on first run.
     * The board URL is printed to the console.
     */
    public function run(): void
    {
        $board = Board::create(['name' => 'Demo board']);

        $columns = collect(['To Do', 'In Progress', 'Done'])
            ->map(fn (string $name, int $position) => $board->columns()->create(compact('name', 'position')));

        $cards = [
            [0, ['Open this board in a second tab', 'Drag a card to another column']],
            [1, ['Watch it move in the other tab']],
            [2, ['Create a board']],
        ];

        foreach ($cards as [$column, $titles]) {
            foreach ($titles as $position => $title) {
                $columns[$column]->cards()->create(compact('title', 'position'));
            }
        }

        $this->command?->info('Demo board: /board/'.$board->id);
    }
}
