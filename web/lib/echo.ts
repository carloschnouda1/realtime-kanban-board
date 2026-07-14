'use client';

import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

declare global {
  interface Window {
    Pusher: typeof Pusher;
  }
}

let echoInstance: Echo<'reverb'> | null = null;

// Lazily creates a single shared Echo/Reverb connection for the browser
// session. Reverb speaks the Pusher protocol, so the standard pusher-js
// client works against it directly.
export function getEcho(): Echo<'reverb'> {
  if (typeof window === 'undefined') {
    throw new Error('getEcho() can only be called in the browser');
  }

  if (!echoInstance) {
    window.Pusher = Pusher;

    echoInstance = new Echo<'reverb'>({
      broadcaster: 'reverb',
      key: process.env.NEXT_PUBLIC_REVERB_APP_KEY,
      wsHost: process.env.NEXT_PUBLIC_REVERB_HOST,
      wsPort: Number(process.env.NEXT_PUBLIC_REVERB_PORT ?? 8080),
      wssPort: Number(process.env.NEXT_PUBLIC_REVERB_PORT ?? 8080),
      forceTLS: (process.env.NEXT_PUBLIC_REVERB_SCHEME ?? 'http') === 'https',
      enabledTransports: ['ws', 'wss'],
    });
  }

  return echoInstance;
}
