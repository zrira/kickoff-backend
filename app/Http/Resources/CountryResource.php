<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CountryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'type' => 'countries',
            'id' => (string) $this->id,
            'attributes' => [
                'name' => $this->name,
                'code' => $this->code,
                'created_at' => $this->created_at?->toISOString(),
            ],
        ];
    }
}
