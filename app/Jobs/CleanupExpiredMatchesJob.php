<?php

namespace App\Jobs;

use App\Models\Game;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class CleanupExpiredMatchesJob implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        $expired = Game::lobby()
            ->where('lobby_expires_at', '<', now())
            ->get();

        foreach ($expired as $match) {
            $confirmedCount = $match->players()
                ->where('status', 'confirmed')
                ->count();

            if ($confirmedCount === 0) {
                $match->update(['status' => 'cancelled']);

                // Mark all players as no_show
                $match->players()->update(['status' => 'no_show']);

                Log::info("Expired match cancelled", ['match_id' => $match->id]);
            }
        }
    }
}
