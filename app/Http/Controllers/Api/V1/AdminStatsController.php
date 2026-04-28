<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Venue;
use App\Models\Game;
use Illuminate\Http\Request;

class AdminStatsController extends Controller
{
    public function index()
    {
        return response()->json([
            'data' => [
                'total_users' => User::count(),
                'total_venues' => Venue::count(),
                'total_matches' => Game::count(),
                'active_matches' => Game::where('status', 'in_progress')->count(),
            ]
        ]);
    }
}
