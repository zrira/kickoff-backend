<?php

use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
*/

// Public channel for matchmaking updates per sport/city
Broadcast::channel('matchmaking.{sportId}.{cityId}', function () {
    return true; // Public channel — anyone can listen
});

// Match-specific channel — only match participants
Broadcast::channel('match.{matchId}', function ($user, $matchId) {
    return \App\Models\GamePlayer::where('match_id', $matchId)
        ->where('user_id', $user->id)
        ->exists();
});
