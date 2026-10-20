<?php

namespace Database\Factories;

use App\Models\BoardColumn;
use App\Models\Card;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Card> */
class CardFactory extends Factory
{
    public function definition(): array
    {
        return [
            'board_column_id' => BoardColumn::factory(),
            'title' => fake()->sentence(3),
            'description' => null,
            'position' => 0,
        ];
    }
}
