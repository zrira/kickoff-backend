<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MatchScoreResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'type' => 'match_scores',
            'id' => (string) $this->id,
            'attributes' => [
                'match_id' => $this->match_id,
                'submitted_by' => $this->submitted_by,
                'score_a' => $this->score_a,
                'score_b' => $this->score_b,
                'status' => $this->status,
                'created_at' => $this->created_at?->toISOString(),
            ],
            'relationships' => [
                'submitter' => [
                    'data' => $this->whenLoaded('submitter', fn () => new UserResource($this->submitter)),
                ],
                'votes' => [
                    'data' => $this->whenLoaded('votes', fn () =>
                        MatchScoreVoteResource::collection($this->votes)
                    ),
                ],
            ],
        ];
    }
}
