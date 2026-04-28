<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VenueResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'type' => 'venues',
            'id' => (string) $this->id,
            'attributes' => [
                'name' => $this->name,
                'address' => $this->address,
                'city_id' => $this->city_id,
                'lat' => $this->lat,
                'lng' => $this->lng,
                'surface_type' => $this->surface_type,
                'is_active' => $this->is_active,
                'image_urls' => $this->image_urls ?? [],
                'created_at' => $this->created_at?->toISOString(),
            ],
            'relationships' => [
                'city' => [
                    'data' => $this->whenLoaded('city', fn () => new CityResource($this->city)),
                ],
            ],
        ];
    }
}
