'use client';

import { useState } from 'react';

interface Props {
  onAdd: (name: string) => void;
}

export default function AddColumnForm({ onAdd }: Props) {
  const [draft, setDraft] = useState('');

  function handleSubmit(e: React.FormEvent) {
    e.preventDefault();
    if (!draft.trim()) return;
    onAdd(draft.trim());
    setDraft('');
  }

  return (
    <form onSubmit={handleSubmit} className="w-72 shrink-0">
      <input
        value={draft}
        onChange={(e) => setDraft(e.target.value)}
        placeholder="+ Add column"
        className="w-full rounded-lg border border-dashed border-slate-300 bg-transparent px-3 py-2.5 text-sm text-slate-500 outline-none focus:border-slate-400"
      />
    </form>
  );
}
