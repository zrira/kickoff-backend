<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MatchPlayerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'type' => 'match_players',
            'id' => (string) $this->id,
            'attributes' => [
                'match_id' => $this->match_id,
                'user_id' => $this->user_id,
                'player_name' => $this->user?->name,
                'player_email' => $this->user?->email,
                'player_elo' => $this->user?->playerProfiles()->where('sport_id', $this->match->sport_id)->first()?->elo_points ?? 1000,
                'elo_delta' => \App\Models\EloHistory::where('match_id', $this->match_id)
                    ->where('user_id', $this->user_id)
                    ->value('delta') ?? 0,
                'team' => $this->team,
                'status' => $this->status,
                'created_at' => $this->created_at?->toISOString(),
            ],
            'relationships' => [
                'user' => [
                    'data' => $this->whenLoaded('user', fn () => new UserResource($this->user)),
                ],
            ],
        ];
    }
}
