<?php

namespace App\Services;

use App\Events\MatchFormedEvent;
use App\Models\Game;
use App\Models\MatchPlayer;
use App\Models\Sport;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class MatchmakingService
{
    /**
     * Redis key pattern for matchmaking queues.
     */
    private function queueKey(int $sportId, int $cityId): string
    {
        return "matchmaking:{$sportId}:{$cityId}";
    }

    /**
     * Redis key for tracking which queue a user is in.
     */
    private function userQueueKey(int $userId): string
    {
        return "matchmaking:user:{$userId}";
    }

    /**
     * Add user to the matchmaking queue.
     */
    public function joinQueue(User $user, int $sportId, int $cityId): void
    {
        $key = $this->queueKey($sportId, $cityId);
        $userKey = $this->userQueueKey($user->id);

        // Check if user is already in a queue
        if (Redis::exists($userKey)) {
            throw new \RuntimeException('You are already in a matchmaking queue.');
        }

        // Ensure user has a profile for this sport, create if not
        $user->playerProfiles()->firstOrCreate(
            ['sport_id' => $sportId],
            ['elo_points' => 1000, 'total_matches' => 0, 'total_wins' => 0, 'trust_score' => 100]
        );

        // Add to sorted set with timestamp as score (FIFO ordering)
        Redis::zadd($key, now()->timestamp, $user->id);

        // Track which queue the user is in
        Redis::set($userKey, json_encode([
            'sport_id' => $sportId,
            'city_id' => $cityId,
            'joined_at' => now()->toISOString(),
        ]));

        // Set TTL of 30 minutes to auto-cleanup stale entries
        Redis::expire($userKey, 1800);
    }

    /**
     * Remove user from the matchmaking queue.
     */
    public function leaveQueue(User $user): void
    {
        $userKey = $this->userQueueKey($user->id);
        $queueData = Redis::get($userKey);

        if (!$queueData) {
            return;
        }

        $data = json_decode($queueData, true);
        $key = $this->queueKey($data['sport_id'], $data['city_id']);

        Redis::zrem($key, $user->id);
        Redis::del($userKey);
    }

    /**
     * Get the current queue status for a user.
     */
    public function getQueueStatus(User $user): array
    {
        $userKey = $this->userQueueKey($user->id);
        $queueData = Redis::get($userKey);

        if (!$queueData) {
            return [
                'in_queue' => false,
                'sport_id' => null,
                'city_id' => null,
                'position' => null,
                'queue_size' => 0,
                'estimated_wait_seconds' => null,
            ];
        }

        $data = json_decode($queueData, true);
        $key = $this->queueKey($data['sport_id'], $data['city_id']);

        $position = Redis::zrank($key, $user->id);
        $queueSize = Redis::zcard($key);

        return [
            'in_queue' => true,
            'sport_id' => $data['sport_id'],
            'city_id' => $data['city_id'],
            'position' => $position !== null ? $position + 1 : null,
            'queue_size' => (int) $queueSize,
            'estimated_wait_seconds' => $position !== null ? $position * 30 : null,
        ];
    }

    /**
     * Attempt to form a match from queued players.
     */
    public function tryFormMatch(int $sportId, int $cityId): ?Game
    {
        $sport = Sport::findOrFail($sportId);
        $requiredPlayers = $sport->team_size * 2;
        $key = $this->queueKey($sportId, $cityId);

        // Check if we have enough players
        $queueSize = Redis::zcard($key);
        if ($queueSize < $requiredPlayers) {
            return null;
        }

        // Pop the required number of players (oldest first)
        $playerIds = Redis::zrange($key, 0, $requiredPlayers - 1);

        if (count($playerIds) < $requiredPlayers) {
            return null;
        }

        return DB::transaction(function () use ($playerIds, $sportId, $cityId, $sport, $key) {
            // Remove players from Redis queue
            foreach ($playerIds as $playerId) {
                Redis::zrem($key, $playerId);
                Redis::del($this->userQueueKey($playerId));
            }

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
