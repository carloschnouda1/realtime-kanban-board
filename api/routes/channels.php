<?php

use Illuminate\Support\Facades\Broadcast;

// Boards are accessed via an unguessable UUID link (no auth), so the
// `board.{boardId}` channel is broadcast as a public channel and needs
// no explicit Broadcast::channel() authorization callback.
