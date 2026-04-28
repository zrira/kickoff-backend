<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LeaderboardResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'type' => 'leaderboard_entries',
            'id' => (string) $this->id,
            'attributes' => [
                'rank' => $this->rank ?? null, // Will be overridden by index in frontend mostly
                'elo_points' => $this->elo_points,
                'total_matches' => $this->total_matches,
                'total_wins' => $this->total_wins,
                'win_rate' => $this->win_rate,
            ],
            'relationships' => [
                'user' => [
                    'data' => $this->whenLoaded('user', fn () => new UserResource($this->user)),
                ],
                'sport' => [
                    'data' => $this->whenLoaded('sport', fn () => new SportResource($this->sport)),
                ],
            ],
        ];
    }
}
