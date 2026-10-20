'use client';

import { useEffect, useState } from 'react';
import { useParams } from 'next/navigation';
import { getBoard } from '@/lib/api';
import { getEcho } from '@/lib/echo';
import { applyColumnCards, upsertColumn } from '@/lib/board-state';
import { Board as BoardType, BoardColumn, CardItem, MoveResult } from '@/lib/types';
import Board from '@/components/Board';

export default function BoardPage() {
  const params = useParams<{ boardId: string }>();
  const boardId = params.boardId;
  const [board, setBoardState] = useState<BoardType | null>(null);

  // Wraps useState's setter so <Board> can work with a plain BoardType
  // updater function without worrying about the null initial state here.
  function updateBoard(updater: (prev: BoardType) => BoardType) {
    setBoardState((prev) => (prev ? updater(prev) : prev));
  }

  useEffect(() => {
    let active = true;
    getBoard(boardId)
      .then((data) => {
        if (active) setBoardState(data);
      })
      .catch((err) => console.error('Failed to load board', err));
    return () => {
      active = false;
    };
  }, [boardId]);

  useEffect(() => {
    if (!boardId) return;

    const echo = getEcho();
    const channel = echo.channel(`board.${boardId}`);

    function upsertCard(card: CardItem) {
      updateBoard((prev) => {
        const columns = prev.columns.map((col) => ({
          ...col,
          cards: col.cards.filter((c) => c.id !== card.id),
        }));
        const target = columns.find((col) => col.id === card.board_column_id);
        if (target) {
          target.cards = [...target.cards, card].sort((a, b) => a.position - b.position);
        }
        return { ...prev, columns };
      });
    }

    channel.listen('.card.created', (e: { card: CardItem }) => upsertCard(e.card));
    channel.listen('.card.updated', (e: { card: CardItem }) => upsertCard(e.card));
    channel.listen('.card.moved', (e: MoveResult) => updateBoard((prev) => applyColumnCards(prev, e.columns)));

    channel.listen('.card.deleted', (e: { cardId: number }) => {
      updateBoard((prev) => ({
        ...prev,
        columns: prev.columns.map((col) => ({
          ...col,
          cards: col.cards.filter((c) => c.id !== e.cardId),
        })),
      }));
    });

    channel.listen('.column.created', (e: { column: BoardColumn }) => {
      updateBoard((prev) => upsertColumn(prev, e.column));
    });

    return () => {
      echo.leave(`board.${boardId}`);
    };
  }, [boardId]);

  if (!board) {
    return (
      <main className="flex min-h-screen items-center justify-center text-sm text-slate-400">
        Loading board...
      </main>
    );
  }

  return (
    <main className="min-h-screen">
      <header className="border-b border-slate-200 bg-white px-6 py-4">
        <h1 className="text-lg font-semibold">{board.name}</h1>
        <p className="text-xs text-slate-400">Share this URL — updates sync live for everyone viewing it.</p>
      </header>
      <Board board={board} setBoard={updateBoard} />
    </main>
  );
}
