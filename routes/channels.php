<?php

use Illuminate\Support\Facades\Broadcast as BroadcastFacade;

/**
 * Authorize channel access for per-user private channel
 */
BroadcastFacade::channel('user.{id}', fn ($user, $id) => (int) $user->id === (int) $id);
