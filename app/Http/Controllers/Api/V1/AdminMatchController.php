<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Game;
use App\Models\Sport;
use App\Models\Venue;
use App\Http\Resources\MatchResource;
use Illuminate\Http\Request;

class AdminMatchController extends Controller
{
    public function index(Request $request)
    {
        // Using 'Game' model as the underlying table is 'matches'
        $query = Game::with(['sport', 'venue', 'players.user'])
            ->orderBy('created_at', 'desc');

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $matches = $query->paginate($request->input('per_page', 50));
            
        return MatchResource::collection($matches);
    }
    
    public function show(Game $match)
    {
        $match->load(['sport', 'venue', 'players.user', 'scores']);
        return new MatchResource($match);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'sport_id' => 'required|exists:sports,id',
            'venue_id' => 'required|exists:venues,id',
            'scheduled_at' => 'required|date|after:now',
        ]);

        $sport = Sport::findOrFail($validated['sport_id']);

        $match = Game::create([
            'sport_id' => $validated['sport_id'],
            'venue_id' => $validated['venue_id'],
            'status' => 'waiting',
            'scheduled_at' => $validated['scheduled_at'],
        ]);

        $match->load(['sport', 'venue', 'players.user', 'scores']);
        return new MatchResource($match);
    }

    public function addPlayer(Request $request, Game $match)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'team' => 'nullable|in:A,B',
        ]);

        // Check if already in match
        if ($match->players()->where('user_id', $validated['user_id'])->exists()) {
            return response()->json(['message' => 'Player already in match'], 422);
        }

        $match->players()->create([
            'user_id' => $validated['user_id'],
            'team' => $validated['team'] ?? null,
        ]);

        return new MatchResource($match->load(['players.user']));
    }

    public function removePlayer(Game $match, $userId)
    {
        $match->players()->where('user_id', $userId)->delete();
        return new MatchResource($match->load(['players.user']));
    }

    public function destroy(Game $match)
    {
        $match->delete();
        return response()->json(null, 204);
    }

    public function resolve(Request $request, Game $match)
    {
        $validated = $request->validate([
            'score_a' => 'required|integer|min:0',
            'score_b' => 'required|integer|min:0',
        ]);

        if ($match->status === 'completed') {
            return response()->json(['message' => 'Match is already completed'], 422);
        }

        // Use EloService directly for immediate update
        $eloService = app(\App\Services\EloService::class);
        $eloService->applyResult($match, $validated['score_a'], $validated['score_b']);

        // Update match status
        $match->update(['status' => 'completed']);
        
        $match->scores()->create([
            'score_a' => $validated['score_a'],
            'score_b' => $validated['score_b'],
            'submitted_by' => auth()->id(),
            'status' => 'approved',
        ]);

        return new MatchResource($match->load(['sport', 'venue', 'players.user', 'scores']));
    }
}
