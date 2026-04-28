<?php

use App\Jobs\CleanupExpiredMatchesJob;
use App\Jobs\TryFormMatchJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Matchmaking: try to form matches every 30 seconds
Schedule::job(new TryFormMatchJob)->everyThirtySeconds();

// Cleanup: cancel expired lobby matches every 5 minutes
Schedule::job(new CleanupExpiredMatchesJob)->everyFiveMinutes();
