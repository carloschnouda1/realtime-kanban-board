<?php

namespace Tests\Feature;

use App\Events\CardCreated;
use App\Events\CardDeleted;
use App\Events\CardMoved;
use App\Events\CardUpdated;
use App\Models\Board;
use App\Models\BoardColumn;
use App\Models\Card;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class CardTest extends TestCase
{
    use RefreshDatabase;

    private Board $board;

    private BoardColumn $todo;

    private BoardColumn $done;

    protected function setUp(): void
    {
        parent::setUp();

        $this->board = Board::factory()->create();
        $this->todo = $this->board->columns()->create(['name' => 'To Do', 'position' => 0]);
        $this->done = $this->board->columns()->create(['name' => 'Done', 'position' => 1]);
    }

    /** @return array<int, string> card titles in order */
    private function titles(BoardColumn $column): array
    {
        return $column->cards()->pluck('title')->all();
    }

    private function cardsIn(BoardColumn $column, string ...$titles): array
    {
        return collect($titles)
            ->map(fn (string $title, int $position) => $column->cards()->create(compact('title', 'position')))
            ->all();
    }

    public function test_a_card_is_appended_to_a_column_and_broadcast(): void
    {
        Event::fake([CardCreated::class]);
        $this->cardsIn($this->todo, 'First');

        $this->postJson("/api/columns/{$this->todo->id}/cards", ['title' => 'Second', 'description' => 'Details'])
            ->assertCreated()
            ->assertJsonPath('data.title', 'Second')
            ->assertJsonPath('data.description', 'Details')
            ->assertJsonPath('data.position', 1);

        Event::assertDispatched(CardCreated::class);
    }

    public function test_the_first_card_in_an_empty_column_starts_at_position_zero(): void
    {
        $this->postJson("/api/columns/{$this->todo->id}/cards", ['title' => 'First'])
            ->assertCreated()
            ->assertJsonPath('data.position', 0);
    }

    public function test_a_card_needs_a_title(): void
    {
        $this->postJson("/api/columns/{$this->todo->id}/cards", ['title' => ''])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('title');
    }

    public function test_a_card_can_be_edited_and_the_change_is_broadcast(): void
    {
        Event::fake([CardUpdated::class]);
        [$card] = $this->cardsIn($this->todo, 'Old title');

        $this->patchJson("/api/cards/{$card->id}", ['title' => 'New title'])
            ->assertOk()
            ->assertJsonPath('data.title', 'New title');

        $this->assertSame('New title', $card->fresh()->title);
        Event::assertDispatched(CardUpdated::class);
    }

    public function test_moving_within_a_column_reorders_the_cards(): void
    {
        Event::fake([CardMoved::class]);
        [$a, $b, $c] = $this->cardsIn($this->todo, 'A', 'B', 'C');

        $this->patchJson("/api/cards/{$c->id}/move", ['board_column_id' => $this->todo->id, 'position' => 0])
            ->assertOk();

        $this->assertSame(['C', 'A', 'B'], $this->titles($this->todo));
        Event::assertDispatched(CardMoved::class);
    }

    public function test_moving_to_another_column_renumbers_both_columns(): void
    {
        Event::fake([CardMoved::class]);
        [$a, $b, $c] = $this->cardsIn($this->todo, 'A', 'B', 'C');
        $this->cardsIn($this->done, 'X', 'Y');

        $this->patchJson("/api/cards/{$b->id}/move", ['board_column_id' => $this->done->id, 'position' => 1])
            ->assertOk()
            ->assertJsonCount(2, 'data.columns');

        $this->assertSame(['A', 'C'], $this->titles($this->todo));
        $this->assertSame(['X', 'B', 'Y'], $this->titles($this->done));
        $this->assertSame([0, 1], $this->todo->cards()->pluck('position')->all());
        $this->assertSame([0, 1, 2], $this->done->cards()->pluck('position')->all());

        Event::assertDispatched(
            CardMoved::class,
            fn (CardMoved $event) => $event->boardId === $this->board->id && count($event->columns) === 2
        );
    }

    public function test_moving_into_an_empty_column_works(): void
    {
        [$a] = $this->cardsIn($this->todo, 'A');

        $this->patchJson("/api/cards/{$a->id}/move", ['board_column_id' => $this->done->id, 'position' => 0])
            ->assertOk();

        $this->assertSame([], $this->titles($this->todo));
        $this->assertSame(['A'], $this->titles($this->done));
    }

    public function test_a_position_past_the_end_is_clamped(): void
    {
        [$a, $b] = $this->cardsIn($this->todo, 'A', 'B');

        $this->patchJson("/api/cards/{$a->id}/move", ['board_column_id' => $this->todo->id, 'position' => 99])
            ->assertOk();

        $this->assertSame(['B', 'A'], $this->titles($this->todo));
    }

    public function test_a_card_cannot_move_to_a_column_of_another_board(): void
    {
        [$a] = $this->cardsIn($this->todo, 'A');
        $foreign = BoardColumn::factory()->create();

        $this->patchJson("/api/cards/{$a->id}/move", ['board_column_id' => $foreign->id, 'position' => 0])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('board_column_id');

        $this->assertSame($this->todo->id, $a->fresh()->board_column_id);
    }

    public function test_moving_needs_a_valid_column_and_position(): void
    {
        [$a] = $this->cardsIn($this->todo, 'A');

        $this->patchJson("/api/cards/{$a->id}/move", ['board_column_id' => 9999, 'position' => -1])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['board_column_id', 'position']);
    }

    public function test_a_card_can_be_deleted_and_the_deletion_is_broadcast(): void
    {
        Event::fake([CardDeleted::class]);
        [$a] = $this->cardsIn($this->todo, 'A');

        $this->deleteJson("/api/cards/{$a->id}")->assertNoContent();

        $this->assertModelMissing($a);
        Event::assertDispatched(
            CardDeleted::class,
            fn (CardDeleted $event) => $event->cardId === $a->id && $event->boardId === $this->board->id
        );
    }
}
