import type { Metadata } from 'next';
import './globals.css';

export const metadata: Metadata = {
  title: 'Realtime Kanban',
  description: 'A live, multi-user Kanban board built with Laravel Reverb and Next.js',
};

export default function RootLayout({ children }: { children: React.ReactNode }) {
  return (
    <html lang="en">
      <body className="bg-slate-50 text-slate-900">{children}</body>
    </html>
  );
}
