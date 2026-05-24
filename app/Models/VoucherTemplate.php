<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VoucherTemplate extends Model
{
    protected $fillable = [
        'code',
        'title',
        'description',
        'type',
        'rarity',
        'discount_amount',
        'min_checkout',
        'free_barang_id',
        'xp_cost',
        'valid_days',
        'is_active',
        'is_mystery_pool',
    ];

    protected $casts = [
        'is_active'       => 'boolean',
        'is_mystery_pool' => 'boolean',
    ];

    public function freeBarang(): BelongsTo
    {
        return $this->belongsTo(Barang::class, 'free_barang_id');
    }
}
