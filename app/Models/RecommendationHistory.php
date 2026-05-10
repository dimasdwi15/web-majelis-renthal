<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecommendationHistory extends Model
{
    protected $table = 'recommendation_histories';

    protected $fillable = [
        'user_id',
        'image_path',
        'ai_detected_items',
        'ai_tags',
        'ai_confidence',
        'recommended_barang',
        'is_fallback',
    ];

    protected $casts = [
        'ai_detected_items'  => 'array',
        'ai_tags'            => 'array',
        'ai_confidence'      => 'float',
        'recommended_barang' => 'array',
        'is_fallback'        => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
