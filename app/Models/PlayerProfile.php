<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;


class PlayerProfile extends Model
{
    protected $fillable = [
    'user_id', 'sport_id', 'elo_points', 'rank',
    'total_matches', 'total_wins', 'trust_score',
];
    public $timestamps = false;

    protected $casts = [
        'elo_points' => 'integer',
        'rank' => 'integer',
        'total_matches' => 'integer',
        'total_wins' => 'integer',
        'trust_score' => 'integer',
        'created_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sport(): BelongsTo
    {
        return $this->belongsTo(Sport::class);
    }

    public function eloHistory(): HasMany
    {
        return $this->hasMany(EloHistory::class, 'user_id', 'user_id')
                    ->where('sport_id', $this->sport_id);
    }

    /**
     * Calculate win rate as a percentage.
     */
    public function getWinRateAttribute(): float
    {
        if ($this->total_matches === 0) {
            return 0.0;
        }

        return round(($this->total_wins / $this->total_matches) * 100, 1);
    }
}
