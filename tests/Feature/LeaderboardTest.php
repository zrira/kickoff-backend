<?php

use App\Models\City;
use App\Models\Country;
use App\Models\PlayerProfile;
use App\Models\Sport;
use App\Models\User;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->country = Country::create(['name' => 'Test Country', 'code' => 'TC']);
    $this->city = City::create(['country_id' => $this->country->id, 'name' => 'Test City', 'lat' => 0, 'lng' => 0]);
    $this->sport = Sport::create(['name' => 'Football', 'team_size' => 5, 'match_duration_minutes' => 60]);
    $this->user = User::factory()->create(['city_id' => $this->city->id]);
    $this->token = $this->user->createToken('test')->plainTextToken;

    // Seed some leaderboard data
    for ($i = 0; $i < 15; $i++) {
        $u = User::factory()->create(['city_id' => $this->city->id]);
        PlayerProfile::create([
            'user_id' => $u->id,
            'sport_id' => $this->sport->id,
            'elo_points' => 1000 + ($i * 50),
            'total_matches' => $i * 3,
            'total_wins' => $i * 2,
        ]);
    }
});

it('returns paginated leaderboard', function () {
    $response = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->getJson("/api/v1/leaderboard?sport_id={$this->sport->id}&per_page=10");

    $response->assertOk()
        ->assertJsonCount(10, 'data')
        ->assertJsonStructure([
            'data',
            'meta' => ['current_page', 'last_page', 'per_page', 'total'],
        ]);
});

it('sorts leaderboard by ELO descending', function () {
    $response = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->getJson("/api/v1/leaderboard?sport_id={$this->sport->id}");

    $data = $response->json('data');
    $elos = collect($data)->pluck('attributes.elo_points');

    expect($elos->toArray())->toBe($elos->sortDesc()->values()->toArray());
});

it('filters leaderboard by city', function () {
    $otherCity = City::create(['country_id' => $this->country->id, 'name' => 'Other City', 'lat' => 1, 'lng' => 1]);
    $otherUser = User::factory()->create(['city_id' => $otherCity->id]);
    PlayerProfile::create([
        'user_id' => $otherUser->id,
        'sport_id' => $this->sport->id,
        'elo_points' => 2000,
    ]);

    $response = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->getJson("/api/v1/leaderboard?sport_id={$this->sport->id}&city_id={$this->city->id}");

    // Should not include the player from other city
    $response->assertOk();
    $userIds = collect($response->json('data'))->pluck('attributes.user_id');
    expect($userIds)->not->toContain($otherUser->id);
});

it('requires sport_id parameter', function () {
    $response = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->getJson('/api/v1/leaderboard');

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['sport_id']);
});
