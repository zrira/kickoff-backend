<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;


class Country extends Model
{
    protected $fillable = ['name', 'code'];
    public $timestamps = false;

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function cities(): HasMany
    {
        return $this->hasMany(City::class);
    }
}
