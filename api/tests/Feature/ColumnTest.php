<?php

namespace Tests\Feature;

use App\Events\ColumnCreated;
use App\Models\Board;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class ColumnTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_column_is_appended_and_broadcast(): void
    {
        Event::fake([ColumnCreated::class]);
        $board = Board::factory()->create();
        $board->columns()->create(['name' => 'Existing', 'position' => 0]);

        $this->postJson("/api/boards/{$board->id}/columns", ['name' => 'Review'])
            ->assertCreated()
            ->assertJsonPath('data.name', 'Review')
            ->assertJsonPath('data.position', 1);

        Event::assertDispatched(
            ColumnCreated::class,
            fn (ColumnCreated $event) => $event->column->name === 'Review' && $event->column->board_id === $board->id
        );
    }

    public function test_the_first_column_of_a_board_starts_at_position_zero(): void
    {
        $board = Board::factory()->create();

        $this->postJson("/api/boards/{$board->id}/columns", ['name' => 'Only'])
            ->assertCreated()
            ->assertJsonPath('data.position', 0);
    }

    public function test_a_column_name_is_required(): void
    {
        $board = Board::factory()->create();

        $this->postJson("/api/boards/{$board->id}/columns", [])->assertUnprocessable();
    }
}
