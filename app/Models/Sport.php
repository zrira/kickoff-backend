<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;


class Sport extends Model
{
    protected $fillable = ['name', 'team_size', 'match_duration_minutes'];
    public $timestamps = false;

    protected $casts = [
        'team_size' => 'integer',
        'match_duration_minutes' => 'integer',
        'created_at' => 'datetime',
    ];

    public function playerProfiles(): HasMany
    {
        return $this->hasMany(PlayerProfile::class);
    }

    public function matches(): HasMany
    {
        return $this->hasMany(Game::class);
    }
}
