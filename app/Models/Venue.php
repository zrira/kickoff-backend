<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

use Illuminate\Database\Eloquent\Builder;

class Venue extends Model
{
    protected $fillable = ['city_id', 'name', 'address', 'lat', 'lng', 'surface_type', 'is_active', 'image_urls'];
    public $timestamps = false;

    protected $casts = [
        'lat' => 'float',
        'lng' => 'float',
        'is_active' => 'boolean',
        'image_urls' => 'array',
        'created_at' => 'datetime',
    ];

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function matches(): HasMany
    {
        return $this->hasMany(Game::class);
    }

    /**
     * Scope: only active venues.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: venues in a specific city.
     */
    public function scopeInCity(Builder $query, int $cityId): Builder
    {
        return $query->where('city_id', $cityId);
    }
}
