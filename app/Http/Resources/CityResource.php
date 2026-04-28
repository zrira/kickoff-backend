<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CityResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'type' => 'cities',
            'id' => (string) $this->id,
            'attributes' => [
                'name' => $this->name,
                'country_id' => $this->country_id,
                'lat' => $this->lat,
                'lng' => $this->lng,
                'created_at' => $this->created_at?->toISOString(),
            ],
            'relationships' => [
                'country' => [
                    'data' => $this->whenLoaded('country', fn () => [
                        'type' => 'countries',
                        'id' => (string) $this->country->id,
                    ]),
                ],
            ],
        ];
    }
}
