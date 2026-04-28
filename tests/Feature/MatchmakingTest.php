<?php

use App\Models\City;
use App\Models\Country;
use App\Models\Sport;
use App\Models\User;
use Illuminate\Support\Facades\Redis;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    Redis::flushall();

    $this->country = Country::create(['name' => 'Test Country', 'code' => 'TC']);
    $this->city = City::create(['country_id' => $this->country->id, 'name' => 'Test City', 'lat' => 0, 'lng' => 0]);
    $this->sport = Sport::create(['name' => 'Football', 'team_size' => 5, 'match_duration_minutes' => 60]);
    $this->user = User::factory()->create(['city_id' => $this->city->id]);
    $this->token = $this->user->createToken('test')->plainTextToken;
});

it('joins the matchmaking queue', function () {
    $response = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->postJson('/api/v1/matchmaking/join', [
            'sport_id' => $this->sport->id,
            'city_id' => $this->city->id,
        ]);

    $response->assertOk()
        ->assertJsonPath('data.in_queue', true);
});

it('prevents joining queue twice', function () {
    $this->withHeader('Authorization', "Bearer {$this->token}")
        ->postJson('/api/v1/matchmaking/join', [
            'sport_id' => $this->sport->id,
            'city_id' => $this->city->id,
        ]);

    $response = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->postJson('/api/v1/matchmaking/join', [
            'sport_id' => $this->sport->id,
            'city_id' => $this->city->id,
        ]);

    $response->assertStatus(422);
});

it('leaves the matchmaking queue', function () {
    $this->withHeader('Authorization', "Bearer {$this->token}")
        ->postJson('/api/v1/matchmaking/join', [
            'sport_id' => $this->sport->id,
            'city_id' => $this->city->id,
        ]);

    $response = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->postJson('/api/v1/matchmaking/leave');

    $response->assertOk();

    $status = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->getJson('/api/v1/matchmaking/status');

    $status->assertJsonPath('data.in_queue', false);
});

it('returns queue status', function () {
    $response = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->getJson('/api/v1/matchmaking/status');

    $response->assertOk()
        ->assertJsonStructure([
            'data' => ['in_queue', 'sport_id', 'city_id', 'position', 'queue_size'],
        ]);
});
