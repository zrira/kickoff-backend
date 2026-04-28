<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SportResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'type' => 'sports',
            'id' => (string) $this->id,
            'attributes' => [
                'name' => $this->name,
                'team_size' => $this->team_size,
                'match_duration_minutes' => $this->match_duration_minutes,
                'created_at' => $this->created_at?->toISOString(),
            ],
        ];
    }
}
