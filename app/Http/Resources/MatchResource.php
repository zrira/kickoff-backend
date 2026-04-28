<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MatchResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'type' => 'matches',
            'id' => (string) $this->id,
            'attributes' => [
                'sport_id' => $this->sport_id,
                'venue_id' => $this->venue_id,
                'city_id' => $this->city_id,
                'status' => $this->status,
                'scheduled_at' => $this->scheduled_at?->toISOString(),
                'lobby_expires_at' => $this->lobby_expires_at?->toISOString(),
                'final_score' => $this->status === 'completed' ? $this->scores()->where('status', 'approved')->first() ? [
                    'score_a' => $this->scores()->where('status', 'approved')->first()->score_a,
                    'score_b' => $this->scores()->where('status', 'approved')->first()->score_b,
                ] : null : null,
                'max_players' => $this->max_players,
                'created_at' => $this->created_at?->toISOString(),
            ],
            'relationships' => [
                'sport' => [
                    'data' => $this->whenLoaded('sport', fn () => new SportResource($this->sport)),
                ],
                'venue' => [
                    'data' => $this->whenLoaded('venue', fn () => $this->venue ? new VenueResource($this->venue) : null),
                ],
                'city' => [
                    'data' => $this->whenLoaded('city', fn () => new CityResource($this->city)),
                ],
                'players' => [
                    'data' => $this->whenLoaded('players', fn () =>
                        MatchPlayerResource::collection($this->players)
                    ),
                ],
                'scores' => [
                    'data' => $this->whenLoaded('scores', fn () =>
                        MatchScoreResource::collection($this->scores)
                    ),
                ],
            ],
        ];
    }
}
