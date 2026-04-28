<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlayerProfileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'type' => 'player_profiles',
            'id' => (string) $this->id,
            'attributes' => [
                'user_id' => $this->user_id,
                'sport_id' => $this->sport_id,
                'elo_points' => $this->elo_points,
                'rank' => $this->rank,
                'total_matches' => $this->total_matches,
                'total_wins' => $this->total_wins,
                'trust_score' => $this->trust_score,
                'win_rate' => $this->win_rate,
                'created_at' => $this->created_at?->toISOString(),
            ],
            'relationships' => [
                'sport' => [
                    'data' => $this->whenLoaded('sport', fn () => new SportResource($this->sport)),
                ],
            ],
        ];
    }
}
