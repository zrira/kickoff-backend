<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\JoinMatchmakingRequest;
use App\Services\MatchmakingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MatchmakingController extends Controller
{
    public function __construct(
        private readonly MatchmakingService $matchmakingService
    ) {}

    /**
     * Join the matchmaking queue.
     */
    public function join(JoinMatchmakingRequest $request): JsonResponse
    {
        try {
            $this->matchmakingService->joinQueue(
                $request->user(),
                $request->validated('sport_id'),
                $request->validated('city_id')
            );

            return response()->json([
                'message' => 'Joined matchmaking queue.',
                'data' => $this->matchmakingService->getQueueStatus($request->user()),
            ]);
        } catch (\RuntimeException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Leave the matchmaking queue.
     */
    public function leave(Request $request): JsonResponse
    {
        $this->matchmakingService->leaveQueue($request->user());

        return response()->json([
            'message' => 'Left matchmaking queue.',
        ]);
    }

    /**
     * Get current queue status.
     */
    public function status(Request $request): JsonResponse
    {
        $status = $this->matchmakingService->getQueueStatus($request->user());

        return response()->json([
            'data' => $status,
        ]);
    }
}
