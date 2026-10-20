export interface CardItem {
  id: number;
  board_column_id: number;
  title: string;
  description: string | null;
  position: number;
}

export interface BoardColumn {
  id: number;
  name: string;
  position: number;
  cards: CardItem[];
}

export interface Board {
  id: string;
  name: string;
  columns: BoardColumn[];
}

/** Response of PATCH /cards/{id}/move: the full card list of every column that changed. */
export interface MoveResult {
  columns: { id: number; cards: CardItem[] }[];
}
