<?php

namespace App\Events;

use App\Models\EloHistory;
use App\Models\Game;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MatchCompletedEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Game $match,
        public readonly int $finalScoreA,
        public readonly int $finalScoreB
    ) {}

    public function broadcastOn(): array
    {
        return [
            new Channel("match.{$this->match->id}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'match.completed';
    }

    public function broadcastWith(): array
    {
        $winningTeam = $this->finalScoreA > $this->finalScoreB
            ? 'A'
            : ($this->finalScoreB > $this->finalScoreA ? 'B' : 'draw');

        $eloChanges = EloHistory::where('match_id', $this->match->id)
            ->with('user')
            ->get()
            ->map(fn (EloHistory $eh) => [
                'user_id' => $eh->user_id,
                'user_name' => $eh->user->name,
                'team' => $this->match->players->where('user_id', $eh->user_id)->first()?->team,
                'points_before' => $eh->points_before,
                'points_after' => $eh->points_after,
                'delta' => $eh->delta,
            ]);

        return [
            'match_id' => $this->match->id,
            'final_score_a' => $this->finalScoreA,
            'final_score_b' => $this->finalScoreB,
            'winning_team' => $winningTeam,
            'elo_changes' => $eloChanges,
        ];
    }
}
