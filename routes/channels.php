<?php

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Support\Facades\Broadcast as BroadcastFacade;

/**
 * Authorize channel access for per-user private channel
 */
BroadcastFacade::channel('user.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});
