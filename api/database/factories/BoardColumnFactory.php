<?php

namespace Database\Factories;

use App\Models\Board;
use App\Models\BoardColumn;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<BoardColumn> */
class BoardColumnFactory extends Factory
{
    public function definition(): array
    {
        return [
            'board_id' => Board::factory(),
            'name' => fake()->word(),
            'position' => 0,
        ];
    }
}
