<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = ['name', 'email', 'password', 'avatar', 'city_id', 'role'];
    protected $hidden = ['password', 'remember_token'];

    public function isSuperAdmin(): bool
    {
        return $this->role === 'superadmin';
    }

    public function isAdmin(): bool
    {
        return in_array($this->role, ['superadmin', 'admin']);
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'city_id' => 'integer',
        ];
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function playerProfiles(): HasMany
    {
        return $this->hasMany(PlayerProfile::class);
    }

    public function matchPlayers(): HasMany
    {
        return $this->hasMany(MatchPlayer::class);
    }

    public function chatMessages(): HasMany
    {
        return $this->hasMany(ChatMessage::class);
    }

    public function eloHistory(): HasMany
    {
        return $this->hasMany(EloHistory::class);
    }

    public function matchScores(): HasMany
    {
        return $this->hasMany(MatchScore::class, 'submitted_by');
    }

    /**
     * Get the player profile for a specific sport.
     */
    public function profileForSport(int $sportId): ?PlayerProfile
    {
        return $this->playerProfiles()->where('sport_id', $sportId)->first();
    }
}
