<?php

namespace App\Http\Controllers\Api\V1;

use App\Events\MatchChatMessageEvent;
use App\Http\Controllers\Controller;
use App\Http\Requests\CastScoreVoteRequest;
use App\Http\Requests\SendChatMessageRequest;
use App\Http\Requests\SubmitScoreRequest;
use App\Http\Resources\ChatMessageResource;
use App\Http\Resources\MatchPlayerResource;
use App\Http\Resources\MatchResource;
use App\Http\Resources\MatchScoreResource;
use App\Models\ChatMessage;
use App\Models\Game;
use App\Services\ScoreResolutionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MatchController extends Controller
{
    public function __construct(
        private readonly ScoreResolutionService $scoreService
    ) {}

    /**
     * Show match details.
     */
    public function show(Game $match): JsonResponse
    {
        $match->load(['sport', 'venue.city', 'city', 'players.user', 'scores.votes']);

        return response()->json([
            'data' => new MatchResource($match),
        ]);
    }

    /**
     * Get match players.
     */
    public function players(Game $match): JsonResponse
    {
        $players = $match->players()->with('user')->get();

        return response()->json([
            'data' => MatchPlayerResource::collection($players),
        ]);
    }

    /**
     * Get chat messages for a match.
     */
    public function chatIndex(Game $match): JsonResponse
    {
        $messages = $match->chatMessages()
            ->with('user')
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json([
            'data' => ChatMessageResource::collection($messages),
        ]);
    }

    /**
     * Send a chat message.
     */
    public function chatStore(SendChatMessageRequest $request, Game $match): JsonResponse
    {
        $message = ChatMessage::create([
            'match_id' => $match->id,
            'user_id' => $request->user()->id,
            'message' => $request->validated('message'),
        ]);

        $message->load('user');

        event(new MatchChatMessageEvent($match, $message));

        return response()->json([
            'data' => new ChatMessageResource($message),
        ], 201);
    }

    /**
     * Submit a match score.
     */
    public function submitScore(SubmitScoreRequest $request, Game $match): JsonResponse
    {
        try {
            $score = $this->scoreService->submitScore(
                $match,
                $request->user(),
                $request->validated('score_a'),
                $request->validated('score_b')
            );

            return response()->json([
                'data' => new MatchScoreResource($score),
                'message' => 'Score submitted. Waiting for votes.',
            ], 201);
        } catch (\RuntimeException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Vote on a submitted score.
     */
    public function voteScore(CastScoreVoteRequest $request, Game $match): JsonResponse
    {
        $score = $match->scores()->latest('id')->firstOrFail();

        try {
            $vote = $this->scoreService->castVote(
                $score,
                $request->user(),
                $request->validated('vote'),
                $request->validated('submitted_score_a'),
                $request->validated('submitted_score_b')
            );

            return response()->json([
                'message' => 'Vote recorded.',
                'data' => [
                    'vote' => $vote->vote,
                    'approvals' => $score->approvalsCount(),
                    'disputes' => $score->disputesCount(),
                ],
            ]);
        } catch (\RuntimeException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
