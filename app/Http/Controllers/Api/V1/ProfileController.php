<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateProfileRequest;
use App\Http\Resources\EloHistoryResource;
use App\Http\Resources\MatchResource;
use App\Http\Resources\UserResource;
use App\Models\EloHistory;
use App\Models\Game;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    /**
     * Show a user's profile.
     */
    public function show(User $user): JsonResponse
    {
        $user->load(['city', 'playerProfiles.sport']);

        return response()->json([
            'data' => new UserResource($user),
        ]);
    }

    /**
     * Get a user's match history.
     */
    public function history(User $user, Request $request): JsonResponse
    {
        $matchIds = $user->matchPlayers()->pluck('match_id');

        $matches = Game::whereIn('id', $matchIds)
            ->with(['sport', 'venue', 'city', 'players.user'])
            ->completed()
            ->orderByDesc('created_at')
            ->paginate($request->input('per_page', 15));

        return response()->json([
            'data' => MatchResource::collection($matches),
            'meta' => [
                'current_page' => $matches->currentPage(),
                'last_page' => $matches->lastPage(),
                'per_page' => $matches->perPage(),
                'total' => $matches->total(),
            ],
        ]);
    }

    /**
     * Get a user's ELO history.
     */
    public function eloHistory(User $user, Request $request): JsonResponse
    {
        $request->validate([
            'sport_id' => 'nullable|integer|exists:sports,id',
        ]);

        $query = EloHistory::where('user_id', $user->id)
            ->with(['sport', 'match'])
            ->orderBy('created_at', 'asc');

        if ($request->filled('sport_id')) {
            $query->where('sport_id', $request->input('sport_id'));
        }

        $history = $query->get();

        return response()->json([
            'data' => EloHistoryResource::collection($history),
        ]);
    }

    /**
     * Update authenticated user's profile.
     */
    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $user = $request->user();
        $data = $request->validated();
        
        if ($request->has('password')) {
            $data['password'] = \Illuminate\Support\Facades\Hash::make($request->password);
        }
        
        $user->update($data);
        $user->load(['city', 'playerProfiles.sport']);

        return response()->json([
            'data' => new UserResource($user),
            'message' => 'Profile updated.',
        ]);
    }
}
