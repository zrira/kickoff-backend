<?php

namespace App\Jobs;

use App\Models\Game;
use App\Services\EloService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ApplyEloResultJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Game $match,
        public readonly int $scoreA,
        public readonly int $scoreB
    ) {}

    public function handle(EloService $eloService): void
    {
        try {
            $eloService->applyResult($this->match, $this->scoreA, $this->scoreB);

            Log::info("ELO applied", [
                'match_id' => $this->match->id,
                'score' => "{$this->scoreA}-{$this->scoreB}",
            ]);
        } catch (\Throwable $e) {
            Log::error("Failed to apply ELO", [
                'match_id' => $this->match->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
