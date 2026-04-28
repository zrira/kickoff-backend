<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

use Illuminate\Database\Eloquent\Builder;

class Game extends Model
{
    protected $fillable = [
    'sport_id', 'venue_id', 'city_id', 'status',
    'scheduled_at', 'lobby_expires_at',
];
    public $timestamps = false;

    protected $table = 'matches';

    protected $casts = [
        'scheduled_at' => 'datetime',
        'lobby_expires_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    // --- Relationships ---

    public function sport(): BelongsTo
    {
        return $this->belongsTo(Sport::class);
    }

    public function venue(): BelongsTo
    {
        return $this->belongsTo(Venue::class);
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function players(): HasMany
    {
        return $this->hasMany(MatchPlayer::class, 'match_id');
    }

    public function scores(): HasMany
    {
        return $this->hasMany(MatchScore::class, 'match_id');
    }

    public function chatMessages(): HasMany
    {
        return $this->hasMany(ChatMessage::class, 'match_id');
    }

    // --- Scopes ---

    public function scopeWaiting(Builder $query): Builder
    {
        return $query->where('status', 'waiting');
    }

    public function scopeLobby(Builder $query): Builder
    {
        return $query->where('status', 'lobby');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function scopeScoring(Builder $query): Builder
    {
        return $query->where('status', 'scoring');
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', 'completed');
    }

    public function scopeCancelled(Builder $query): Builder
    {
        return $query->where('status', 'cancelled');
    }

    public function scopeInProgress(Builder $query): Builder
    {
        return $query->whereIn('status', ['lobby', 'active', 'scoring']);
    }

    // --- Helpers ---

    /**
     * Get players on a specific team.
     */
    public function teamPlayers(string $team): HasMany
    {
        return $this->players()->where('team', $team);
    }

    /**
     * Check if the lobby has expired.
     */
    public function isLobbyExpired(): bool
    {
        return $this->lobby_expires_at && $this->lobby_expires_at->isPast();
    }

    /**
     * Get maximum players for this match.
     */
    public function getMaxPlayersAttribute(): int
    {
        return ($this->sport->team_size ?? 5) * 2;
    }
}
