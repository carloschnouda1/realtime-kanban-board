<?php

namespace Database\Factories;

use App\Models\Board;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Board> */
class BoardFactory extends Factory
{
    public function definition(): array
    {
        return ['name' => fake()->words(2, true)];
    }
}
