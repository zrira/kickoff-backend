<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;


class EloHistory extends Model
{
    protected $fillable = [
    'user_id', 'match_id', 'sport_id',
    'points_before', 'points_after', 'delta',
];
    public $timestamps = false;

    protected $table = 'elo_history';

    protected $casts = [
        'points_before' => 'integer',
        'points_after' => 'integer',
        'delta' => 'integer',
        'created_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function match(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    public function sport(): BelongsTo
    {
        return $this->belongsTo(Sport::class);
    }
}
