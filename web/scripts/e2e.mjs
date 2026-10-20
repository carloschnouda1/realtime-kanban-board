// End-to-end check of the realtime path against a running stack
// (docker compose up). It subscribes to a board's channel on Reverb exactly
// like the browser does, makes changes through the REST API, and asserts that
// the matching WebSocket events arrive.
//
//   API_URL=http://localhost:8000/api REVERB_APP_KEY=local-reverb-key node scripts/e2e.mjs
import Pusher from 'pusher-js/node.js';

const API = process.env.API_URL ?? 'http://localhost:8000/api';
const KEY = process.env.REVERB_APP_KEY ?? 'local-reverb-key';
const WS_HOST = process.env.REVERB_HOST ?? 'localhost';
const WS_PORT = Number(process.env.REVERB_PORT ?? 8080);

async function api(path, method = 'GET', body) {
  const res = await fetch(`${API}${path}`, {
    method,
    headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
    body: body ? JSON.stringify(body) : undefined,
  });
  if (!res.ok) throw new Error(`${method} ${path} -> ${res.status} ${await res.text()}`);
  return res.status === 204 ? null : (await res.json()).data;
}

async function waitForApi() {
  for (let attempt = 0; attempt < 60; attempt++) {
    try {
      const res = await fetch(API.replace(/\/api$/, '') + '/up');
      if (res.ok) return;
    } catch {}
    await new Promise((resolve) => setTimeout(resolve, 1000));
  }
  throw new Error('API did not become healthy within 60s');
}

function nextEvent(channel, name, timeoutMs = 10000) {
  return new Promise((resolve, reject) => {
    const timer = setTimeout(() => reject(new Error(`Timed out waiting for "${name}"`)), timeoutMs);
    channel.bind(name, (payload) => {
      clearTimeout(timer);
      resolve(payload);
    });
  });
}

function assert(condition, message) {
  if (!condition) throw new Error(`Assertion failed: ${message}`);
  console.log(`ok - ${message}`);
}

await waitForApi();

const board = await api('/boards', 'POST', { name: 'e2e board' });
const [todo, , done] = board.columns;

const pusher = new Pusher(KEY, {
  cluster: 'local',
  wsHost: WS_HOST,
  wsPort: WS_PORT,
  wssPort: WS_PORT,
  forceTLS: false,
  enabledTransports: ['ws'],
  disableStats: true,
});

pusher.connection.bind('state_change', ({ current }) => console.log(`connection: ${current}`));
pusher.connection.bind('error', (error) => console.log('connection error:', JSON.stringify(error)));

try {
  const channel = pusher.subscribe(`board.${board.id}`);
  await new Promise((resolve, reject) => {
    const timer = setTimeout(() => reject(new Error('Could not subscribe to the board channel')), 10000);
    channel.bind('pusher:subscription_succeeded', () => {
      clearTimeout(timer);
      resolve();
    });
  });
  console.log('ok - subscribed to the board channel');

  let pending = nextEvent(channel, 'card.created');
  const card = await api(`/columns/${todo.id}/cards`, 'POST', { title: 'Realtime card' });
  const created = await pending;
  assert(created.card.id === card.id, 'card.created is delivered over the WebSocket');

  pending = nextEvent(channel, 'card.moved');
  await api(`/cards/${card.id}/move`, 'PATCH', { board_column_id: done.id, position: 0 });
  const moved = await pending;
  const doneColumn = moved.columns.find((column) => column.id === done.id);
  assert(doneColumn?.cards[0]?.id === card.id, 'card.moved carries the renumbered destination column');

  pending = nextEvent(channel, 'card.deleted');
  await api(`/cards/${card.id}`, 'DELETE');
  const deleted = await pending;
  assert(deleted.cardId === card.id, 'card.deleted is delivered over the WebSocket');

  const fresh = await api(`/boards/${board.id}`);
  assert(fresh.columns.every((column) => column.cards.length === 0), 'the board is empty after the delete');
} finally {
  pusher.disconnect();
}

console.log('E2E passed');
process.exit(0);
