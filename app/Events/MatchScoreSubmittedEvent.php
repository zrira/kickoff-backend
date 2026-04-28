<?php

namespace App\Events;

use App\Models\Game;
use App\Models\GameScore;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MatchScoreSubmittedEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Game $match,
        public readonly MatchScore $score
    ) {}

    public function broadcastOn(): array
    {
        return [
            new Channel("match.{$this->match->id}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'score.submitted';
    }

    public function broadcastWith(): array
    {
        $this->score->loadMissing(['submitter', 'votes']);

        return [
            'match_id' => $this->match->id,
            'score_id' => $this->score->id,
            'submitted_by' => [
                'id' => $this->score->submitter->id,
                'name' => $this->score->submitter->name,
                'avatar' => $this->score->submitter->avatar,
            ],
            'score_a' => $this->score->score_a,
            'score_b' => $this->score->score_b,
            'status' => $this->score->status,
            'votes_count' => $this->score->votes->count(),
            'approvals_count' => $this->score->votes->where('vote', 'approve')->count(),
            'disputes_count' => $this->score->votes->where('vote', 'dispute')->count(),
        ];
    }
}
