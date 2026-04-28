<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\LeaderboardResource;
use App\Models\PlayerProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LeaderboardController extends Controller
{
    /**
     * Get leaderboard, filterable by city_id and sport_id.
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'sport_id' => 'required|integer|exists:sports,id',
            'city_id' => 'nullable|integer|exists:cities,id',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $query = PlayerProfile::with(['user.city', 'sport'])
            ->where('sport_id', $request->input('sport_id'))
            ->orderByDesc('elo_points');

        if ($request->filled('city_id')) {
            $query->whereHas('user', function ($q) use ($request) {
                $q->where('city_id', $request->input('city_id'));
            });
        }

        $profiles = $query->paginate($request->input('per_page', 25));

        return response()->json([
            'data' => LeaderboardResource::collection($profiles),
            'meta' => [
                'current_page' => $profiles->currentPage(),
                'last_page' => $profiles->lastPage(),
                'per_page' => $profiles->perPage(),
                'total' => $profiles->total(),
            ],
        ]);
    }
}
