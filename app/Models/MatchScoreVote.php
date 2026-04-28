<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;


class MatchScoreVote extends Model
{
    protected $fillable = ['match_score_id', 'user_id', 'vote', 'submitted_score_a', 'submitted_score_b'];
    public $timestamps = false;

    protected $casts = [
        'submitted_score_a' => 'integer',
        'submitted_score_b' => 'integer',
        'created_at' => 'datetime',
    ];

    public function matchScore(): BelongsTo
    {
        return $this->belongsTo(MatchScore::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
