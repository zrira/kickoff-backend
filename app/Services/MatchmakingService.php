<?php

namespace App\Services;

use App\Events\MatchFormedEvent;
use App\Models\Game;
use App\Models\MatchPlayer;
use App\Models\Sport;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Support\Facades\DB;

class MatchmakingService
{
    /**
     * Add user to the matchmaking queue.
     */
    public function joinQueue(User $user, int $sportId, int $cityId): void
    {
        // Check if user is already in a queue
        $alreadyInQueue = DB::table('matchmaking_queues')
            ->where('user_id', $user->id)
            ->exists();

        if ($alreadyInQueue) {
            throw new \RuntimeException('You are already in a matchmaking queue.');
        }

        // Ensure user has a profile for this sport, create if not
        $user->playerProfiles()->firstOrCreate(
            ['sport_id' => $sportId],
            ['elo_points' => 1000, 'total_matches' => 0, 'total_wins' => 0, 'trust_score' => 100]
        );

        // Add to database queue
        DB::table('matchmaking_queues')->insert([
            'user_id' => $user->id,
            'sport_id' => $sportId,
            'city_id' => $cityId,
            'joined_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Remove user from the matchmaking queue.
     */
    public function leaveQueue(User $user): void
    {
        DB::table('matchmaking_queues')
            ->where('user_id', $user->id)
            ->delete();
    }

    /**
     * Get the current queue status for a user.
     */
    public function getQueueStatus(User $user): array
    {
        $queueEntry = DB::table('matchmaking_queues')
            ->where('user_id', $user->id)
            ->first();

        if (!$queueEntry) {
            return [
                'in_queue' => false,
                'sport_id' => null,
                'city_id' => null,
                'position' => null,
                'queue_size' => 0,
                'estimated_wait_seconds' => null,
            ];
        }

        $queueSize = DB::table('matchmaking_queues')
            ->where('sport_id', $queueEntry->sport_id)
            ->where('city_id', $queueEntry->city_id)
            ->count();

        $position = DB::table('matchmaking_queues')
            ->where('sport_id', $queueEntry->sport_id)
            ->where('city_id', $queueEntry->city_id)
            ->where('joined_at', '<', $queueEntry->joined_at)
            ->count() + 1;

        return [
            'in_queue' => true,
            'sport_id' => $queueEntry->sport_id,
            'city_id' => $queueEntry->city_id,
            'position' => $position,
            'queue_size' => (int) $queueSize,
            'estimated_wait_seconds' => ($position - 1) * 30,
        ];
    }

    /**
     * Attempt to form a match from queued players.
     */
    public function tryFormMatch(int $sportId, int $cityId): ?Game
    {
        $sport = Sport::findOrFail($sportId);
        $requiredPlayers = $sport->team_size * 2;

        // Check if we have enough players
        $playersInQueue = DB::table('matchmaking_queues')
            ->where('sport_id', $sportId)
            ->where('city_id', $cityId)
            ->orderBy('joined_at', 'asc')
            ->limit($requiredPlayers)
            ->get();

        if ($playersInQueue->count() < $requiredPlayers) {
            return null;
        }

        $playerIds = $playersInQueue->pluck('user_id')->toArray();

        return DB::transaction(function () use ($playerIds, $sportId, $cityId, $sport) {
            // Remove players from database queue
            DB::table('matchmaking_queues')
                ->whereIn('user_id', $playerIds)
                ->delete();

            // Find nearest available venue
            $venue = Venue::active()
                ->inCity($cityId)
                ->inRandomOrder()
                ->first();

            // Create the match
            $match = Game::create([
                'sport_id' => $sportId,
                'venue_id' => $venue?->id,
                'city_id' => $cityId,
                'status' => 'lobby',
                'scheduled_at' => now()->addHours(2),
                'lobby_expires_at' => now()->addMinutes(30),
            ]);

            // Shuffle and split into teams
            $shuffled = collect($playerIds)->shuffle();
            $teamSize = count($playerIds) / 2;

            $shuffled->each(function ($playerId, $index) use ($match, $teamSize) {
                MatchPlayer::create([
                    'match_id' => $match->id,
                    'user_id' => (int) $playerId,
                    'team' => $index < $teamSize ? 'A' : 'B',
                    'status' => 'invited',
                ]);
            });

            // Load relationships for broadcasting
            $match->load(['sport', 'venue', 'city', 'players.user']);

            // Fire event
            event(new MatchFormedEvent($match));

            return $match;
        });
    }
}
