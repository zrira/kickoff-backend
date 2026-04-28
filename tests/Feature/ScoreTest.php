<?php

use App\Models\City;
use App\Models\Country;
use App\Models\Game;
use App\Models\GamePlayer;
use App\Models\GameScore;
use App\Models\Sport;
use App\Models\User;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->country = Country::create(['name' => 'Test Country', 'code' => 'TC']);
    $this->city = City::create(['country_id' => $this->country->id, 'name' => 'Test City', 'lat' => 0, 'lng' => 0]);
    $this->sport = Sport::create(['name' => 'Football', 'team_size' => 5, 'match_duration_minutes' => 60]);

    $this->match = Game::create([
        'sport_id' => $this->sport->id,
        'city_id' => $this->city->id,
        'status' => 'active',
        'scheduled_at' => now()->addHour(),
        'lobby_expires_at' => now()->addMinutes(30),
    ]);

    // Create players
    $this->playerA = User::factory()->create();
    $this->playerB = User::factory()->create();

    MatchPlayer::create(['match_id' => $this->match->id, 'user_id' => $this->playerA->id, 'team' => 'A']);
    MatchPlayer::create(['match_id' => $this->match->id, 'user_id' => $this->playerB->id, 'team' => 'B']);

    $this->tokenA = $this->playerA->createToken('test')->plainTextToken;
    $this->tokenB = $this->playerB->createToken('test')->plainTextToken;
});

it('submits a score', function () {
    $response = $this->withHeader('Authorization', "Bearer {$this->tokenA}")
        ->postJson("/api/v1/matches/{$this->match->id}/score", [
            'score_a' => 3,
            'score_b' => 1,
        ]);

    $response->assertStatus(201)
        ->assertJsonPath('data.attributes.score_a', 3)
        ->assertJsonPath('data.attributes.score_b', 1);
});

it('prevents duplicate score submission', function () {
    $this->withHeader('Authorization', "Bearer {$this->tokenA}")
        ->postJson("/api/v1/matches/{$this->match->id}/score", [
            'score_a' => 3,
            'score_b' => 1,
        ]);

    $response = $this->withHeader('Authorization', "Bearer {$this->tokenA}")
        ->postJson("/api/v1/matches/{$this->match->id}/score", [
            'score_a' => 3,
            'score_b' => 1,
        ]);

    $response->assertStatus(422);
});

it('allows voting on a submitted score', function () {
    // Player A submits score
    $this->withHeader('Authorization', "Bearer {$this->tokenA}")
        ->postJson("/api/v1/matches/{$this->match->id}/score", [
            'score_a' => 3,
            'score_b' => 1,
        ]);

    // Player B approves
    $response = $this->withHeader('Authorization', "Bearer {$this->tokenB}")
        ->postJson("/api/v1/matches/{$this->match->id}/score/vote", [
            'vote' => 'approve',
        ]);

    $response->assertOk()
        ->assertJsonPath('data.vote', 'approve');
});

it('handles score dispute with alternative score', function () {
    $this->withHeader('Authorization', "Bearer {$this->tokenA}")
        ->postJson("/api/v1/matches/{$this->match->id}/score", [
            'score_a' => 3,
            'score_b' => 1,
        ]);

    $response = $this->withHeader('Authorization', "Bearer {$this->tokenB}")
        ->postJson("/api/v1/matches/{$this->match->id}/score/vote", [
            'vote' => 'dispute',
            'submitted_score_a' => 2,
            'submitted_score_b' => 2,
        ]);

    $response->assertOk()
        ->assertJsonPath('data.vote', 'dispute');
});
