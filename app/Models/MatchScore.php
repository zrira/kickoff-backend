<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;


class MatchScore extends Model
{
    protected $fillable = ['match_id', 'submitted_by', 'score_a', 'score_b', 'status'];
    public $timestamps = false;

    protected $casts = [
        'score_a' => 'integer',
        'score_b' => 'integer',
        'created_at' => 'datetime',
    ];

    public function match(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function votes(): HasMany
    {
        return $this->hasMany(MatchScoreVote::class);
    }

    /**
     * Count of approval votes.
     */
    public function approvalsCount(): int
    {
        return $this->votes()->where('vote', 'approve')->count();
    }

    /**
     * Count of dispute votes.
     */
    public function disputesCount(): int
    {
        return $this->votes()->where('vote', 'dispute')->count();
    }
}
