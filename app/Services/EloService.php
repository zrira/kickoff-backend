<?php

namespace App\Services;

use App\Models\EloHistory;
use App\Models\Game;
use App\Models\PlayerProfile;
use Illuminate\Support\Facades\DB;

class EloService
{
    /**
     * K-factor for ELO calculation.
     */
    private const K_FACTOR = 32;

    /**
     * Maximum bonus points for score margin.
     */
    private const MAX_MARGIN_BONUS = 8;

    /**
     * Calculate ELO delta for the winner.
     *
     * @param  float  $winnerAvgElo  Average ELO of winning team
     * @param  float  $loserAvgElo   Average ELO of losing team
     * @param  int    $scoreDiff     Absolute score difference
     * @return int    Points to award to winner (and subtract from loser)
     */
    public function calculate(float $winnerAvgElo, float $loserAvgElo, int $scoreDiff): int
    {
        // Standard ELO expected score
        $expectedWinner = 1 / (1 + pow(10, ($loserAvgElo - $winnerAvgElo) / 400));

        // Base ELO change (winner always gets actual = 1)
        $baseDelta = round(self::K_FACTOR * (1 - $expectedWinner));

        // Score margin bonus (capped)
        $marginBonus = min($scoreDiff * 2, self::MAX_MARGIN_BONUS);

        return max(1, (int) ($baseDelta + $marginBonus));
    }

    /**
     * Apply match result: update ELO for all players and write history.
     */
    public function applyResult(Game $match, int $scoreA, int $scoreB): void
    {
        $match->loadMissing(['players.user']);

        $teamAPlayers = $match->players->where('team', 'A');
        $teamBPlayers = $match->players->where('team', 'B');

        // Get average ELO for each team
        $avgEloA = $this->getTeamAvgElo($teamAPlayers, $match->sport_id);
        $avgEloB = $this->getTeamAvgElo($teamBPlayers, $match->sport_id);

        $scoreDiff = abs($scoreA - $scoreB);

        if ($scoreA === $scoreB) {
            // Draw — minimal ELO change
            $delta = 0;
            $this->applyToTeam($teamAPlayers, $match, $delta);
            $this->applyToTeam($teamBPlayers, $match, $delta);
        } elseif ($scoreA > $scoreB) {
            // Team A wins
            $delta = $this->calculate($avgEloA, $avgEloB, $scoreDiff);
            $this->applyToTeam($teamAPlayers, $match, $delta);
            $this->applyToTeam($teamBPlayers, $match, -$delta);
            $this->recordWins($teamAPlayers, $match->sport_id);
        } else {
            // Team B wins
            $delta = $this->calculate($avgEloB, $avgEloA, $scoreDiff);
            $this->applyToTeam($teamBPlayers, $match, $delta);
            $this->applyToTeam($teamAPlayers, $match, -$delta);
            $this->recordWins($teamBPlayers, $match->sport_id);
        }

        // Increment total_matches for all players
        $allPlayerIds = $match->players->pluck('user_id');
        PlayerProfile::where('sport_id', $match->sport_id)
            ->whereIn('user_id', $allPlayerIds)
            ->increment('total_matches');
    }

    /**
     * Calculate average ELO for a team.
     */
    private function getTeamAvgElo($players, int $sportId): float
    {
        $elos = $players->map(function ($mp) use ($sportId) {
            $profile = PlayerProfile::where('user_id', $mp->user_id)
                ->where('sport_id', $sportId)
                ->first();

            return $profile?->elo_points ?? 1000;
        });

        return $elos->avg() ?: 1000.0;
    }

    /**
     * Apply ELO delta to all players in a team and write history.
     */
    private function applyToTeam($players, Game $match, int $delta): void
    {
        foreach ($players as $matchPlayer) {
            $profile = PlayerProfile::firstOrCreate(
                ['user_id' => $matchPlayer->user_id, 'sport_id' => $match->sport_id],
                ['elo_points' => 1000, 'total_matches' => 0, 'total_wins' => 0, 'trust_score' => 100]
            );

            $pointsBefore = $profile->elo_points;
            $pointsAfter = max(0, $pointsBefore + $delta);

            $profile->update(['elo_points' => $pointsAfter]);

            EloHistory::create([
                'user_id' => $matchPlayer->user_id,
                'match_id' => $match->id,
                'sport_id' => $match->sport_id,
                'points_before' => $pointsBefore,
                'points_after' => $pointsAfter,
                'delta' => $pointsAfter - $pointsBefore,
            ]);
        }
    }

    /**
     * Increment win count for winning team players.
     */
    private function recordWins($players, int $sportId): void
    {
        $playerIds = $players->pluck('user_id');
        PlayerProfile::where('sport_id', $sportId)
            ->whereIn('user_id', $playerIds)
            ->increment('total_wins');
    }
}
