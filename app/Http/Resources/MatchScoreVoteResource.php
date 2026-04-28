<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MatchScoreVoteResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'type' => 'match_score_votes',
            'id' => (string) $this->id,
            'attributes' => [
                'match_score_id' => $this->match_score_id,
                'user_id' => $this->user_id,
                'vote' => $this->vote,
                'submitted_score_a' => $this->submitted_score_a,
                'submitted_score_b' => $this->submitted_score_b,
                'created_at' => $this->created_at?->toISOString(),
            ],
        ];
    }
}
