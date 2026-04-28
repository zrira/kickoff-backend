<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MatchPlayer extends Model
{
    protected $fillable = ['match_id', 'user_id', 'team', 'status'];
    public $timestamps = false;

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function match(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
