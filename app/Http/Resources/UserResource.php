<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'type' => 'users',
            'id' => (string) $this->id,
            'attributes' => [
                'name' => $this->name,
                'email' => $this->email,
                'avatar' => $this->avatar,
                'city_id' => $this->city_id,
                'role' => $this->role,
                'elo_points' => $this->playerProfiles->first()?->elo_points ?? null,
                'rank' => $this->role === 'player' ? (\App\Models\PlayerProfile::where('sport_id', 1)->where('elo_points', '>', $this->playerProfiles->first()?->elo_points ?? 0)->count() + 1) : null,
                'created_at' => $this->created_at?->toISOString(),
            ],
            'relationships' => [
                'city' => [
                    'data' => $this->whenLoaded('city', fn () => $this->city ? [
                        'type' => 'cities',
                        'id' => (string) $this->city->id,
                        'attributes' => (new CityResource($this->city))->resolve(),
                    ] : null),
                ],
                'player_profiles' => [
                    'data' => $this->whenLoaded('playerProfiles', fn () =>
                        PlayerProfileResource::collection($this->playerProfiles)
                    ),
                ],
                'elo_history' => [
                    'data' => $this->whenLoaded('eloHistory', fn () =>
                        EloHistoryResource::collection($this->eloHistory)
                    ),
                ],
            ],
        ];
    }
}
