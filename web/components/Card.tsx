'use client';

import { useSortable } from '@dnd-kit/sortable';
import { CSS } from '@dnd-kit/utilities';
import { CardItem } from '@/lib/types';

interface Props {
  card: CardItem;
  onDelete: (cardId: number) => void;
}

export default function Card({ card, onDelete }: Props) {
  const { attributes, listeners, setNodeRef, transform, transition, isDragging } = useSortable({
    id: `card-${card.id}`,
  });

  const style = {
    transform: CSS.Transform.toString(transform),
    transition,
    opacity: isDragging ? 0.5 : 1,
  };

  return (
    <div
      ref={setNodeRef}
      style={style}
      {...attributes}
      {...listeners}
      className="group rounded-md border border-slate-200 bg-white p-3 shadow-sm hover:shadow"
    >
      <div className="flex items-start justify-between gap-2">
        <p className="text-sm font-medium text-slate-800">{card.title}</p>
        <button
          onClick={(e) => {
            e.stopPropagation();
            onDelete(card.id);
          }}
          className="invisible text-xs text-slate-400 hover:text-red-500 group-hover:visible"
        >
          ✕
        </button>
      </div>
      {card.description && <p className="mt-1 text-xs text-slate-500">{card.description}</p>}
    </div>
  );
}
