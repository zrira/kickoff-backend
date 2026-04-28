<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\LeaderboardController;
use App\Http\Controllers\Api\V1\MatchController;
use App\Http\Controllers\Api\V1\MatchmakingController;
use App\Http\Controllers\Api\V1\ProfileController;
use App\Http\Controllers\Api\V1\VenueController;
use App\Http\Controllers\Api\V1\AdminUserController;
use App\Http\Controllers\Api\V1\AdminVenueController;
use App\Http\Controllers\Api\V1\AdminMatchController;
use App\Http\Controllers\Api\V1\AdminStatsController;
use App\Http\Controllers\Api\V1\CityController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — v1
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {

    // --- Auth (public) ---
    Route::prefix('auth')->group(function () {
        Route::post('/register', [AuthController::class, 'register']);
        Route::post('/login', [AuthController::class, 'login']);
    });

    // --- Auth (protected) ---
    Route::middleware('auth:sanctum')->group(function () {

        // Auth
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/auth/me', [AuthController::class, 'me']);

        // Matchmaking
        Route::prefix('matchmaking')->group(function () {
            Route::post('/join', [MatchmakingController::class, 'join']);
            Route::post('/leave', [MatchmakingController::class, 'leave']);
            Route::get('/status', [MatchmakingController::class, 'status']);
        });

        // Matches
        Route::prefix('matches/{match}')->group(function () {
            Route::get('/', [MatchController::class, 'show']);
            Route::get('/players', [MatchController::class, 'players']);
            Route::get('/chat', [MatchController::class, 'chatIndex']);
            Route::post('/chat', [MatchController::class, 'chatStore']);
            Route::post('/score', [MatchController::class, 'submitScore']);
            Route::post('/score/vote', [MatchController::class, 'voteScore']);
        });

        // Venues
        Route::get('/venues', [VenueController::class, 'index']);

        // Leaderboard
        Route::get('/leaderboard', [LeaderboardController::class, 'index']);

        // Profile
        Route::get('/profile/{user}', [ProfileController::class, 'show']);
        Route::get('/profile/{user}/history', [ProfileController::class, 'history']);
        Route::get('/profile/{user}/elo-history', [ProfileController::class, 'eloHistory']);
        Route::put('/profile/update', [ProfileController::class, 'update']);

        // Cities
        Route::get('/cities', [CityController::class, 'index']);

        // --- Admin Backoffice ---
        Route::prefix('admin')->group(function () {
            // Dashboard Stats
            Route::get('stats', [AdminStatsController::class, 'index']);
            
            // Users CRUD
            Route::apiResource('users', AdminUserController::class)->only(['index', 'show', 'update', 'destroy']);
            
            // Venues CRUD
            Route::apiResource('venues', AdminVenueController::class)->only(['index', 'store', 'show', 'update', 'destroy']);
            Route::get('venues/{venue}/schedule', [AdminVenueController::class, 'schedule']);
            
            // Matches
            Route::get('matches', [AdminMatchController::class, 'index']);
            Route::post('matches', [AdminMatchController::class, 'store']);
            Route::get('matches/{match}', [AdminMatchController::class, 'show']);
            Route::delete('matches/{match}', [AdminMatchController::class, 'destroy']);
            Route::post('matches/{match}/resolve', [AdminMatchController::class, 'resolve']);
            Route::post('matches/{match}/players', [AdminMatchController::class, 'addPlayer']);
            Route::delete('matches/{match}/players/{user}', [AdminMatchController::class, 'removePlayer']);
        });
    });
});
