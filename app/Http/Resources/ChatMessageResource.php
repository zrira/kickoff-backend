<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ChatMessageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'type' => 'chat_messages',
            'id' => (string) $this->id,
            'attributes' => [
                'match_id' => $this->match_id,
                'user_id' => $this->user_id,
                'message' => $this->message,
                'created_at' => $this->created_at?->toISOString(),
            ],
            'relationships' => [
                'user' => [
                    'data' => $this->whenLoaded('user', fn () => [
                        'id' => $this->user->id,
                        'name' => $this->user->name,
                        'avatar' => $this->user->avatar,
                    ]),
                ],
            ],
        ];
    }
}
