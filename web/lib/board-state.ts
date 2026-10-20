import { Board, BoardColumn, CardItem } from './types';

/**
 * Replaces the cards of the given columns with the server's version. Used for
 * card moves, where several cards are renumbered at once.
 */
export function applyColumnCards(board: Board, columns: { id: number; cards: CardItem[] }[]): Board {
  const byId = new Map(columns.map((column) => [column.id, column.cards]));

  return {
    ...board,
    columns: board.columns.map((column) =>
      byId.has(column.id) ? { ...column, cards: byId.get(column.id)! } : column,
    ),
  };
}

/**
 * Adds a column, or replaces it if it is already on the board. The API doesn't
 * include `cards` for a new column, so default to an empty list.
 */
export function upsertColumn(board: Board, column: Omit<BoardColumn, 'cards'> & { cards?: CardItem[] }): Board {
  const next: BoardColumn = { ...column, cards: column.cards ?? [] };
  const exists = board.columns.some((existing) => existing.id === next.id);

  return {
    ...board,
    columns: exists
      ? board.columns.map((existing) => (existing.id === next.id ? { ...next, cards: existing.cards } : existing))
      : [...board.columns, next].sort((a, b) => a.position - b.position),
  };
}
