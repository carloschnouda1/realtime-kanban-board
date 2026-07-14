'use client';

import { useState } from 'react';
import { useRouter } from 'next/navigation';
import { createBoard } from '@/lib/api';

export default function HomePage() {
  const [name, setName] = useState('');
  const [loading, setLoading] = useState(false);
  const router = useRouter();

  async function handleSubmit(e: React.FormEvent) {
    e.preventDefault();
    if (!name.trim()) return;
    setLoading(true);
    try {
      const board = await createBoard(name.trim());
      router.push(`/board/${board.id}`);
    } catch (err) {
      console.error('Failed to create board', err);
      setLoading(false);
    }
  }

  return (
    <main className="flex min-h-screen flex-col items-center justify-center px-4">
      <div className="w-full max-w-sm rounded-xl border border-slate-200 bg-white p-8 shadow-sm">
        <h1 className="mb-1 text-xl font-semibold">Realtime Kanban</h1>
        <p className="mb-6 text-sm text-slate-500">Create a board and share the link. Changes sync live for everyone viewing it.</p>
        <form onSubmit={handleSubmit} className="flex flex-col gap-3">
          <input
            value={name}
            onChange={(e) => setName(e.target.value)}
            placeholder="Board name, e.g. Sprint 12"
            className="rounded-md border border-slate-200 px-3 py-2 text-sm outline-none focus:border-slate-400"
          />
          <button
            type="submit"
            disabled={loading}
            className="rounded-md bg-slate-900 px-3 py-2 text-sm font-medium text-white hover:bg-slate-700 disabled:opacity-50"
          >
            {loading ? 'Creating...' : 'Create board'}
          </button>
        </form>
      </div>
    </main>
  );
}
