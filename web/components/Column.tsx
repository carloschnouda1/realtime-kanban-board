'use client';

import { useState } from 'react';
import { useDroppable } from '@dnd-kit/core';
import { SortableContext, verticalListSortingStrategy } from '@dnd-kit/sortable';
import { BoardColumn, CardItem } from '@/lib/types';
import Card from './Card';

interface Props {
  column: BoardColumn;
  onAddCard: (columnId: number, title: string) => void;
  onDeleteCard: (cardId: number) => void;
}

export default function Column({ column, onAddCard, onDeleteCard }: Props) {
  const [draft, setDraft] = useState('');
  const { setNodeRef } = useDroppable({ id: `column-${column.id}` });

  const cardIds = column.cards.map((card: CardItem) => `card-${card.id}`);

  function handleSubmit(e: React.FormEvent) {
    e.preventDefault();
    if (!draft.trim()) return;
    onAddCard(column.id, draft.trim());
    setDraft('');
  }

  return (
    <div className="flex w-72 shrink-0 flex-col rounded-lg bg-slate-100 p-3">
      <h2 className="mb-3 px-1 text-sm font-semibold text-slate-700">
        {column.name} <span className="text-slate-400">({column.cards.length})</span>
      </h2>

      <div ref={setNodeRef} className="flex min-h-[40px] flex-col gap-2">
        <SortableContext items={cardIds} strategy={verticalListSortingStrategy}>
          {column.cards.map((card) => (
            <Card key={card.id} card={card} onDelete={onDeleteCard} />
          ))}
        </SortableContext>
      </div>

      <form onSubmit={handleSubmit} className="mt-3">
        <input
          value={draft}
          onChange={(e) => setDraft(e.target.value)}
          placeholder="Add a card..."
          className="w-full rounded-md border border-slate-200 bg-white px-2 py-1.5 text-sm outline-none focus:border-slate-400"
        />
      </form>
    </div>
  );
}
