<?php

namespace App\Jobs;

use App\Models\City;
use App\Models\Sport;
use App\Services\MatchmakingService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class TryFormMatchJob implements ShouldQueue
{
    use Queueable;

    public function handle(MatchmakingService $service): void
    {
        $sports = Sport::all();
        $cities = City::all();

        foreach ($sports as $sport) {
            foreach ($cities as $city) {
                try {
                    $match = $service->tryFormMatch($sport->id, $city->id);

                    if ($match) {
                        Log::info("Match formed", [
                            'match_id' => $match->id,
                            'sport' => $sport->name,
                            'city' => $city->name,
                        ]);
                    }
                } catch (\Throwable $e) {
                    Log::error("Failed to form match", [
                        'sport_id' => $sport->id,
                        'city_id' => $city->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }
    }
}
