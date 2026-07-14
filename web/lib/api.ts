import { Board, BoardColumn, CardItem } from './types';

const API_URL = process.env.NEXT_PUBLIC_API_URL ?? 'http://localhost:8000/api';

async function request<T>(path: string, options: RequestInit = {}): Promise<T> {
  const res = await fetch(`${API_URL}${path}`, {
    ...options,
    headers: {
      'Content-Type': 'application/json',
      Accept: 'application/json',
      ...options.headers,
    },
  });

  if (!res.ok) {
    throw new Error(`Request to ${path} failed with status ${res.status}`);
  }

  if (res.status === 204) {
    return undefined as T;
  }

  const json = await res.json();
  return json.data ?? json;
}

export function createBoard(name: string): Promise<Board> {
  return request<Board>('/boards', { method: 'POST', body: JSON.stringify({ name }) });
}

export function getBoard(boardId: string): Promise<Board> {
  return request<Board>(`/boards/${boardId}`);
}

export function createColumn(boardId: string, name: string): Promise<BoardColumn> {
  return request<BoardColumn>(`/boards/${boardId}/columns`, {
    method: 'POST',
    body: JSON.stringify({ name }),
  });
}

export function createCard(columnId: number, title: string, description?: string): Promise<CardItem> {
  return request<CardItem>(`/columns/${columnId}/cards`, {
    method: 'POST',
    body: JSON.stringify({ title, description }),
  });
}

export function updateCard(cardId: number, data: Partial<Pick<CardItem, 'title' | 'description'>>): Promise<CardItem> {
  return request<CardItem>(`/cards/${cardId}`, { method: 'PATCH', body: JSON.stringify(data) });
}

export function moveCard(cardId: number, boardColumnId: number, position: number): Promise<CardItem> {
  return request<CardItem>(`/cards/${cardId}/move`, {
    method: 'PATCH',
    body: JSON.stringify({ board_column_id: boardColumnId, position }),
  });
}

export function deleteCard(cardId: number): Promise<void> {
  return request<void>(`/cards/${cardId}`, { method: 'DELETE' });
}
