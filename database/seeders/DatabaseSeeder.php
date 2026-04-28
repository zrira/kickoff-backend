<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\City;
use App\Models\Country;
use App\Models\Sport;
use App\Models\Venue;
use App\Models\PlayerProfile;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Core Data
        $country = Country::firstOrCreate(['name' => 'Morocco'], ['code' => 'MA']);
        $city = City::firstOrCreate(
            ['name' => 'Casablanca', 'country_id' => $country->id],
            ['lat' => 33.5731, 'lng' => -7.5898]
        );
        
        // Ensure ONLY football exists
        Sport::where('id', '>', 0)->delete();
        $football = Sport::create([
            'id' => 1,
            'name' => 'Football',
            'team_size' => 5,
            'match_duration_minutes' => 60,
        ]);

        // 2. Auth Users
        // Clean up all users first and reset IDs
        \Illuminate\Support\Facades\DB::statement("DELETE FROM match_players");
        \Illuminate\Support\Facades\DB::statement("DELETE FROM match_scores");
        \Illuminate\Support\Facades\DB::statement("DELETE FROM elo_history");
        \Illuminate\Support\Facades\DB::statement("DELETE FROM matches");
        \Illuminate\Support\Facades\DB::statement("DELETE FROM player_profiles");
        \Illuminate\Support\Facades\DB::statement("DELETE FROM users");
        \Illuminate\Support\Facades\DB::statement("DELETE FROM venues");
        
        \Illuminate\Support\Facades\DB::statement("DELETE FROM sqlite_sequence WHERE name='users'");
        \Illuminate\Support\Facades\DB::statement("DELETE FROM sqlite_sequence WHERE name='venues'");
        \Illuminate\Support\Facades\DB::statement("DELETE FROM sqlite_sequence WHERE name='matches'");
        \Illuminate\Support\Facades\DB::statement("DELETE FROM sqlite_sequence WHERE name='player_profiles'");
        \Illuminate\Support\Facades\DB::statement("DELETE FROM sqlite_sequence WHERE name='elo_history'");
        \Illuminate\Support\Facades\DB::statement("DELETE FROM sqlite_sequence WHERE name='match_players'");
        \Illuminate\Support\Facades\DB::statement("DELETE FROM sqlite_sequence WHERE name='match_scores'");

        // 2.1 Superadmin
        User::create([
            'name' => 'Super Admin',
            'email' => 'superadmin@kickoff.com',
            'password' => Hash::make('secret'),
            'city_id' => $city->id,
            'role' => 'superadmin',
        ]);

        // 2.2 Admins (3)
        for ($i = 1; $i <= 3; $i++) {
            User::create([
                'name' => "Admin $i",
                'email' => "admin$i@kickoff.com",
                'password' => Hash::make('secret'),
                'city_id' => $city->id,
                'role' => 'admin',
            ]);
        }

        // 3. Venues (10)
        // ... (venues code stays same)
        $venueNames = [
            'Stade Mohammed V',
            'City Foot Casablanca',
            'Foot District Anfa',
            'Arena 5 Casablanca',
            'Kickoff Casa Park',
            'The Pitch Maârif',
            'Seven Foot Ain Diab',
            'Elite Football Gauthier',
            'Oasis Sport Center',
            'Bouskoura Football Club'
        ];

        foreach ($venueNames as $index => $name) {
            Venue::create([
                'city_id' => $city->id,
                'name' => $name,
                'address' => "Casablanca, Morocco (Field " . ($index + 1) . ")",
                'lat' => 33.5731 + (rand(-300, 300) / 10000),
                'lng' => -7.5898 + (rand(-300, 300) / 10000),
                'surface_type' => 'Artificial Turf',
                'is_active' => true,
            ]);
        }

        // 4. Players (100)
        $players = [];
        for ($i = 1; $i <= 100; $i++) {
            $user = User::create([
                'name' => "Player $i",
                'email' => "player$i@example.com",
                'password' => Hash::make('password'),
                'city_id' => $city->id,
                'role' => 'player',
            ]);

            $profile = PlayerProfile::create([
                'user_id' => $user->id,
                'sport_id' => $football->id,
                'elo_points' => 1000,
                'total_matches' => 0,
                'total_wins' => 0,
                'trust_score' => rand(90, 100),
            ]);
            $players[] = $user;
        }

        // 5. Simulate Match History (100 Matches over 3 months)
        $venues = Venue::all();
        $eloService = new \App\Services\EloService();
        
        for ($i = 1; $i <= 100; $i++) {
            $match = \App\Models\Game::create([
                'venue_id' => $venues->random()->id,
                'sport_id' => $football->id,
                'city_id' => $city->id,
                'scheduled_at' => now()->subDays(100 - $i)->setHour(rand(18, 22))->setMinute(0),
                'status' => 'completed',
            ]);

            // Pick 10 random players
            $matchPlayers = collect($players)->random(10);
            $teamA = $matchPlayers->take(5);
            $teamB = $matchPlayers->skip(5);

            // Assign to match
            foreach ($teamA as $p) {
                \App\Models\MatchPlayer::create([
                    'match_id' => $match->id,
                    'user_id' => $p->id,
                    'team' => 'A',
                    'status' => 'confirmed'
                ]);
            }
            foreach ($teamB as $p) {
                \App\Models\MatchPlayer::create([
                    'match_id' => $match->id,
                    'user_id' => $p->id,
                    'team' => 'B',
                    'status' => 'confirmed'
                ]);
            }

            // Generate Random Score
            $scoreA = rand(0, 5);
            $scoreB = rand(0, 5);
            if ($scoreA === $scoreB) $scoreA++; // Avoid too many draws for better seeding variety

            // Apply ELO Result using the service
            $eloService->applyResult($match, $scoreA, $scoreB);

            // Create Score Record
            $match->scores()->create([
                'score_a' => $scoreA,
                'score_b' => $scoreB,
                'submitted_by' => $matchPlayers->first()->id,
                'status' => 'approved',
            ]);
        }

        // 6. Future Matches (To demonstrate Busy slots in the calendar)
        for ($i = 1; $i <= 15; $i++) {
            $date = now()->addDays(rand(0, 10))->setHour(rand(8, 22))->setMinute(0);
            \App\Models\Game::create([
                'venue_id' => $venues->random()->id,
                'sport_id' => $football->id,
                'city_id' => $city->id,
                'scheduled_at' => $date,
                'status' => 'waiting',
            ]);
        }
    }
}
