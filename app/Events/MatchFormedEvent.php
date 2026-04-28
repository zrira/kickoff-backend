<?php

namespace App\Events;

use App\Models\Game;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MatchFormedEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Game $match
    ) {}

    public function broadcastOn(): array
    {
        return [
            new Channel("matchmaking.{$this->match->sport_id}.{$this->match->city_id}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'match.formed';
    }

    public function broadcastWith(): array
    {
        $this->match->loadMissing(['sport', 'venue', 'city', 'players.user']);

        $teamA = $this->match->players->where('team', 'A')->map(fn ($p) => [
            'id' => $p->user->id,
            'name' => $p->user->name,
            'avatar' => $p->user->avatar,
        ])->values();

        $teamB = $this->match->players->where('team', 'B')->map(fn ($p) => [
            'id' => $p->user->id,
            'name' => $p->user->name,
            'avatar' => $p->user->avatar,
        ])->values();

        return [
            'match_id' => $this->match->id,
            'sport' => $this->match->sport,
            'venue' => $this->match->venue,
            'city' => $this->match->city,
            'team_a' => $teamA,
            'team_b' => $teamB,
            'scheduled_at' => $this->match->scheduled_at?->toISOString(),
            'lobby_expires_at' => $this->match->lobby_expires_at?->toISOString(),
        ];
    }
}
