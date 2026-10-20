import { describe, expect, it } from 'vitest';
import { applyColumnCards, upsertColumn } from '../board-state';
import { Board, CardItem } from '../types';

const card = (id: number, columnId: number, position: number): CardItem => ({
  id,
  board_column_id: columnId,
  title: `Card ${id}`,
  description: null,
  position,
});

const board = (): Board => ({
  id: 'board-1',
  name: 'Test',
  columns: [
    { id: 1, name: 'To Do', position: 0, cards: [card(1, 1, 0), card(2, 1, 1)] },
    { id: 2, name: 'Done', position: 1, cards: [] },
  ],
});

describe('applyColumnCards', () => {
  it('replaces the cards of the listed columns and leaves the others alone', () => {
    const result = applyColumnCards(board(), [
      { id: 1, cards: [card(2, 1, 0)] },
      { id: 2, cards: [card(1, 2, 0)] },
    ]);

    expect(result.columns[0].cards.map((c) => c.id)).toEqual([2]);
    expect(result.columns[1].cards.map((c) => c.id)).toEqual([1]);
  });

  it('ignores columns that are not on the board', () => {
    const result = applyColumnCards(board(), [{ id: 99, cards: [card(1, 99, 0)] }]);

    expect(result).toEqual(board());
  });
});

describe('upsertColumn', () => {
  it('adds a new column with an empty card list when the API sends none', () => {
    const result = upsertColumn(board(), { id: 3, name: 'Review', position: 2 });

    expect(result.columns.map((c) => c.id)).toEqual([1, 2, 3]);
    expect(result.columns[2].cards).toEqual([]);
  });

  it('does not duplicate a column that is already on the board', () => {
    const once = upsertColumn(board(), { id: 3, name: 'Review', position: 2 });
    const twice = upsertColumn(once, { id: 3, name: 'Review', position: 2 });

    expect(twice.columns).toHaveLength(3);
  });

  it('keeps the cards of an existing column when it is updated', () => {
    const result = upsertColumn(board(), { id: 1, name: 'Backlog', position: 0 });

    expect(result.columns[0].name).toBe('Backlog');
    expect(result.columns[0].cards).toHaveLength(2);
  });
});
