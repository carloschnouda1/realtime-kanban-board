<?php

namespace Tests\Feature;

use App\Models\Board;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BoardTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_a_board_adds_the_default_columns(): void
    {
        $response = $this->postJson('/api/boards', ['name' => 'Roadmap']);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'Roadmap')
            ->assertJsonCount(3, 'data.columns')
            ->assertJsonPath('data.columns.0.name', 'To Do')
            ->assertJsonPath('data.columns.1.name', 'In Progress')
            ->assertJsonPath('data.columns.2.name', 'Done');

        $this->assertDatabaseCount('board_columns', 3);
    }

    public function test_a_board_name_is_required(): void
    {
        $this->postJson('/api/boards', [])->assertUnprocessable()->assertJsonValidationErrors('name');
    }

    public function test_a_board_is_loaded_with_ordered_columns_and_cards(): void
    {
        $board = Board::factory()->create();
        $second = $board->columns()->create(['name' => 'Second', 'position' => 1]);
        $first = $board->columns()->create(['name' => 'First', 'position' => 0]);
        $first->cards()->create(['title' => 'B', 'position' => 1]);
        $first->cards()->create(['title' => 'A', 'position' => 0]);

        $this->getJson("/api/boards/{$board->id}")
            ->assertOk()
            ->assertJsonPath('data.columns.0.name', 'First')
            ->assertJsonPath('data.columns.1.name', 'Second')
            ->assertJsonPath('data.columns.0.cards.0.title', 'A')
            ->assertJsonPath('data.columns.0.cards.1.title', 'B');
    }

    public function test_an_unknown_board_returns_404(): void
    {
        $this->getJson('/api/boards/not-a-real-board')->assertNotFound();
    }
}
