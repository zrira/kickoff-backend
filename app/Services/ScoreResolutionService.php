<?php

namespace App\Services;

use App\Events\MatchCompletedEvent;
use App\Events\MatchScoreSubmittedEvent;
use App\Jobs\ApplyEloResultJob;
use App\Models\Game;
use App\Models\MatchScore;
use App\Models\MatchScoreVote;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ScoreResolutionService
{
    /**
     * Submit a score for a match.
     */
    public function submitScore(Game $match, User $user, int $scoreA, int $scoreB): MatchScore
    {
        // Ensure match is in correct state
        if (!in_array($match->status, ['active', 'scoring'])) {
            throw new \RuntimeException('Match is not in a state to accept scores.');
        }

        // Ensure user is a player in this match
        $isPlayer = $match->players()->where('user_id', $user->id)->exists();
        if (!$isPlayer) {
            throw new \RuntimeException('You are not a player in this match.');
        }

        // Check if user already submitted
        $existing = $match->scores()->where('submitted_by', $user->id)->first();
        if ($existing) {
            throw new \RuntimeException('You have already submitted a score.');
        }

        // Update match status to scoring
        if ($match->status === 'active') {
            $match->update(['status' => 'scoring']);
        }

        $score = MatchScore::create([
            'match_id' => $match->id,
            'submitted_by' => $user->id,
            'score_a' => $scoreA,
            'score_b' => $scoreB,
            'status' => 'pending',
        ]);

        $score->load('submitter');

        event(new MatchScoreSubmittedEvent($match, $score));

        return $score;
    }

    /**
     * Cast a vote on a submitted score.
     */
    public function castVote(
        MatchScore $score,
        User $user,
        string $vote,
        ?int $scoreA = null,
        ?int $scoreB = null
    ): MatchScoreVote {
        // Prevent voting on own submission
        if ($score->submitted_by === $user->id) {
            throw new \RuntimeException('You cannot vote on your own score submission.');
        }

        // Prevent duplicate votes
        $existingVote = $score->votes()->where('user_id', $user->id)->first();
        if ($existingVote) {
            throw new \RuntimeException('You have already voted on this score.');
        }

        $scoreVote = MatchScoreVote::create([
            'match_score_id' => $score->id,
            'user_id' => $user->id,
            'vote' => $vote,
            'submitted_score_a' => $vote === 'dispute' ? $scoreA : null,
            'submitted_score_b' => $vote === 'dispute' ? $scoreB : null,
        ]);

        // Check if we can resolve
        $this->checkAndResolve($score->match);

        return $scoreVote;
    }

    /**
     * Check if enough votes are in to resolve the match.
     */
    private function checkAndResolve(Game $match): void
    {
        $totalPlayers = $match->players()->count();
        $score = $match->scores()->latest('id')->first();

        if (!$score) {
            return;
        }

        $totalVotes = $score->votes()->count();
        // +1 for the submitter
        $totalResponses = $totalVotes + 1;

        // Need majority (more than half) to resolve
        $majority = ceil($totalPlayers / 2);

        if ($totalResponses >= $majority) {
            $this->resolveMatch($match);
        }
    }

    /**
     * Resolve a match based on score votes.
     */
    public function resolveMatch(Game $match): void
    {
        $score = $match->scores()->latest('id')->first();

        if (!$score) {
            return;
        }

        $approvals = $score->approvalsCount();
        $disputes = $score->disputesCount();

        if ($approvals >= $disputes) {
            // Majority approves — use the submitted score
            $score->update(['status' => 'approved']);
            $finalScoreA = $score->score_a;
            $finalScoreB = $score->score_b;
        } else {
            // Disputed — use majority submitted alternative score
            $score->update(['status' => 'disputed']);

            // Find the most common alternative score
            $alternativeScore = $this->getMajorityAlternativeScore($score);
            $finalScoreA = $alternativeScore['score_a'] ?? $score->score_a;
            $finalScoreB = $alternativeScore['score_b'] ?? $score->score_b;
        }

        // Mark match as completed
        $match->update(['status' => 'completed']);

        // Dispatch ELO calculation job
        ApplyEloResultJob::dispatch($match, $finalScoreA, $finalScoreB);

        // Broadcast completion
        event(new MatchCompletedEvent($match, $finalScoreA, $finalScoreB));
    }

    /**
     * Get the most commonly submitted alternative score from dispute votes.
     */
    private function getMajorityAlternativeScore(MatchScore $score): array
    {
        $disputeVotes = $score->votes()
            ->where('vote', 'dispute')
            ->whereNotNull('submitted_score_a')
            ->whereNotNull('submitted_score_b')
            ->get();

        if ($disputeVotes->isEmpty()) {
            return ['score_a' => $score->score_a, 'score_b' => $score->score_b];
        }

        // Group by score combination and find the most frequent
        $grouped = $disputeVotes->groupBy(function ($vote) {
            return $vote->submitted_score_a . '-' . $vote->submitted_score_b;
        });

        $mostCommon = $grouped->sortByDesc->count()->first();

        return [
            'score_a' => $mostCommon->first()->submitted_score_a,
            'score_b' => $mostCommon->first()->submitted_score_b,
        ];
    }
}
