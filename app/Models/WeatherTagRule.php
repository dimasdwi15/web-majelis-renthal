<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WeatherTagRule extends Model
{
    protected $table = 'weather_tag_rules';

    protected $fillable = [
        'weather_condition',
        'tag_id',
        'prioritas',
    ];

    /**
     * Tag yang berlaku untuk rule cuaca ini.
     */
    public function tag(): BelongsTo
    {
        return $this->belongsTo(Tag::class, 'tag_id');
    }

    /**
     * Ambil semua tag_id yang relevan untuk kondisi cuaca tertentu,
     * diurutkan berdasarkan prioritas.
     *
     * Digunakan oleh WeatherRecommendationService.
     *
     * @param  string  $condition  Nilai cuaca lowercase dari OWM (rain, clear, dll.)
     * @return \Illuminate\Support\Collection<int>  Collection of tag_id
     */
    public static function tagIdsForCondition(string $condition): \Illuminate\Support\Collection
    {
        return static::where('weather_condition', $condition)
            ->orderBy('prioritas')
            ->pluck('tag_id');
    }
}
