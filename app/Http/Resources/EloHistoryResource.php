<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EloHistoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'type' => 'elo_history',
            'id' => (string) $this->id,
            'attributes' => [
                'user_id' => $this->user_id,
                'match_id' => $this->match_id,
                'sport_id' => $this->sport_id,
                'points_before' => $this->points_before,
                'points_after' => $this->points_after,
                'delta' => $this->delta,
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
