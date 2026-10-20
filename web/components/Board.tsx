'use client';

import { DndContext, DragEndEvent, PointerSensor, closestCorners, useSensor, useSensors } from '@dnd-kit/core';
import { Board as BoardType } from '@/lib/types';
import { createCard, createColumn, deleteCard, getBoard, moveCard } from '@/lib/api';
import { applyColumnCards, upsertColumn } from '@/lib/board-state';
import Column from './Column';
import AddColumnForm from './AddColumnForm';

interface Props {
  board: BoardType;
  setBoard: (updater: (prev: BoardType) => BoardType) => void;
}

function parseId(id: string | number): { type: 'card' | 'column'; id: number } {
  const [type, rawId] = String(id).split('-');
  return { type: type as 'card' | 'column', id: Number(rawId) };
}

export default function Board({ board, setBoard }: Props) {
  const sensors = useSensors(useSensor(PointerSensor, { activationConstraint: { distance: 5 } }));

  function handleDragEnd(event: DragEndEvent) {
    const { active, over } = event;
    if (!over) return;

    const activeParsed = parseId(active.id);
    const overParsed = parseId(over.id);
    if (activeParsed.type !== 'card') return;

    const sourceColumn = board.columns.find((col) => col.cards.some((card) => card.id === activeParsed.id));
    if (!sourceColumn) return;

    const destColumnId =
      overParsed.type === 'column'
        ? overParsed.id
        : board.columns.find((col) => col.cards.some((card) => card.id === overParsed.id))?.id;
    if (destColumnId === undefined) return;

    const destColumn = board.columns.find((col) => col.id === destColumnId);
    const card = sourceColumn.cards.find((c) => c.id === activeParsed.id);
    if (!destColumn || !card) return;

    const destIndex =
      overParsed.type === 'card' ? destColumn.cards.findIndex((c) => c.id === overParsed.id) : destColumn.cards.length;

    setBoard((prev) => {
      const columns = prev.columns.map((col) => ({ ...col, cards: [...col.cards] }));
      const from = columns.find((col) => col.id === sourceColumn.id)!;
      const to = columns.find((col) => col.id === destColumn.id)!;

      const cardIndex = from.cards.findIndex((c) => c.id === card.id);
      const [moved] = from.cards.splice(cardIndex, 1);
      const insertAt = from === to && cardIndex < destIndex ? destIndex - 1 : destIndex;
      to.cards.splice(Math.max(insertAt, 0), 0, { ...moved, board_column_id: to.id });

      return { ...prev, columns };
    });

    moveCard(card.id, destColumn.id, Math.max(destIndex, 0))
      .then((result) => setBoard((prev) => applyColumnCards(prev, result.columns)))
      .catch((err) => {
        // The optimistic move didn't stick: reload the authoritative state.
        console.error('Failed to persist card move', err);
        getBoard(board.id).then((fresh) => setBoard(() => fresh));
      });
  }

  async function handleAddCard(columnId: number, title: string) {
    const card = await createCard(columnId, title);
    setBoard((prev) => ({
      ...prev,
      columns: prev.columns.map((col) => (col.id === columnId ? { ...col, cards: [...col.cards, card] } : col)),
    }));
  }

  async function handleDeleteCard(cardId: number) {
    setBoard((prev) => ({
      ...prev,
      columns: prev.columns.map((col) => ({ ...col, cards: col.cards.filter((c) => c.id !== cardId) })),
    }));
    await deleteCard(cardId).catch((err) => console.error('Failed to delete card', err));
  }

  async function handleAddColumn(name: string) {
    const column = await createColumn(board.id, name);
    setBoard((prev) => upsertColumn(prev, column));
  }

  return (
    <DndContext sensors={sensors} collisionDetection={closestCorners} onDragEnd={handleDragEnd}>
      <div className="flex gap-4 overflow-x-auto p-6">
        {board.columns.map((column) => (
          <Column key={column.id} column={column} onAddCard={handleAddCard} onDeleteCard={handleDeleteCard} />
        ))}
        <AddColumnForm onAdd={handleAddColumn} />
      </div>
    </DndContext>
  );
}
