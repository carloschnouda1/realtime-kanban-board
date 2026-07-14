# Realtime Kanban Board

A live, multi-user Kanban board. Open the same board in two tabs, drag a card in one, and watch it move in the other — no refresh. Built with **Laravel Reverb** (first-party WebSocket broadcasting, no third-party service required) on the backend and **Next.js + dnd-kit** on the frontend.

Boards are accessed via an unguessable UUID link (no accounts, no login) — create a board, share the URL, everyone with the link can edit it live. This keeps the demo frictionless while still exercising real production concerns: WebSocket broadcasting, optimistic UI updates, and drag-and-drop state reconciliation.

## How it works

1. A board has columns (`To Do`, `In Progress`, `Done` by default), each holding cards.
2. Every card/column mutation (`create`, `update`, `move`, `delete`) fires a Laravel broadcast event on a `board.{boardId}` channel.
3. The Next.js client subscribes to that channel via `laravel-echo`, listens for events, and reconciles them into local state — so every connected browser stays in sync in real time.
4. Drag-and-drop (via `@dnd-kit`) updates the UI optimistically and persists the new position through the API in the background.

## Stack

- **API**: Laravel 11, Reverb (WebSocket broadcasting), Eloquent, API Resources
- **Web**: Next.js 14 (App Router), TypeScript, Tailwind CSS, `@dnd-kit`, `laravel-echo` + `pusher-js` (Reverb speaks the Pusher protocol)

## Project layout

```
api/    Custom Laravel application code (models, migrations, events, controllers, routes)
web/    Next.js frontend
```

`api/` is not a full Laravel installation — it's the application-specific code layered on top of one, the same way you'd review it in a real project (nobody reads the framework boilerplate). See setup below for how to bootstrap it.

## Setup

### API

```bash
composer create-project laravel/laravel realtime-kanban-api
cd realtime-kanban-api
composer require laravel/reverb
php artisan install:broadcasting   # scaffolds Reverb config and .env entries
```

Then copy the contents of this repo's `api/app`, `api/database/migrations`, and `api/routes` into the newly created project (merging `routes/api.php` and `routes/channels.php` with the defaults Laravel generates).

```bash
php artisan migrate
php artisan reverb:start
php artisan serve
```

### Web

```bash
cd web
cp .env.local.example .env.local   # fill in your Reverb app key + host
npm install
npm run dev
```

Visit `http://localhost:3000`, create a board, and open the resulting `/board/{id}` URL in a second tab to see updates sync live.

## Notes on scope

This is a focused demo, not a production app — there's intentionally no auth, no rate limiting, and no persistence beyond the three tables. The parts worth reading are the broadcasting setup (`api/app/Events`), the channel reconciliation logic (`web/app/board/[boardId]/page.tsx`), and the drag-end handler (`web/components/Board.tsx`).
