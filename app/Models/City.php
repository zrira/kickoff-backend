<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;


class City extends Model
{
    protected $fillable = ['country_id', 'name', 'lat', 'lng'];
    public $timestamps = false;

    protected $casts = [
        'lat' => 'float',
        'lng' => 'float',
        'created_at' => 'datetime',
    ];

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function venues(): HasMany
    {
        return $this->hasMany(Venue::class);
    }

    public function matches(): HasMany
    {
        return $this->hasMany(Game::class);
    }
}
