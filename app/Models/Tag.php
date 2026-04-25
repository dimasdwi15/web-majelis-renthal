<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tag extends Model
{
    protected $table = 'tags';

    protected $fillable = [
        'slug',
        'label',
        'deskripsi',
    ];

    /**
     * Barang-barang yang memiliki tag ini.
     */
    public function barang(): BelongsToMany
    {
        return $this->belongsToMany(Barang::class, 'barang_tag');
    }

    /**
     * Weather rules yang menggunakan tag ini.
     */
    public function weatherRules(): HasMany
    {
        return $this->hasMany(WeatherTagRule::class, 'tag_id');
    }
}
