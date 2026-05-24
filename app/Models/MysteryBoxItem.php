<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MysteryBoxItem extends Model
{
    protected $fillable = [
        'title',
        'description',
        'type',
        'rarity',
        'discount_amount',
        'min_checkout',
        'barang_id',
        'barang_nama_snapshot',
        'barang_foto_url_snapshot',
        'weight',
        'valid_days',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // ── Relasi ke barang (soft reference) ────────────────────────────────
    public function barang(): BelongsTo
    {
        return $this->belongsTo(Barang::class, 'barang_id');
    }

    // ── Helper: nama barang (snapshot atau dari relasi) ───────────────────
    public function getBarangNamaAttribute(): string
    {
        return $this->barang_nama_snapshot
            ?? $this->barang?->nama
            ?? 'Item Gratis';
    }

    // ── Helper: URL foto (snapshot atau dari relasi) ──────────────────────
    public function getFotoUrlAttribute(): ?string
    {
        return $this->barang_foto_url_snapshot
            ?? $this->barang?->fotoUtama?->path_foto;
    }

    // ── Scope: hanya item aktif ───────────────────────────────────────────
    public function scopeAktif($query)
    {
        return $query->where('is_active', true);
    }

    // ── Pilih item secara weighted random ─────────────────────────────────
    /**
     * Pilih satu item secara weighted random dari koleksi.
     * Item dengan weight lebih besar lebih sering dipilih.
     *
     * @param \Illuminate\Database\Eloquent\Collection $pool
     * @return static|null
     */
    public static function weightedRandom($pool): ?self
    {
        if ($pool->isEmpty()) return null;

        $totalWeight = $pool->sum('weight');
        $rand = rand(1, $totalWeight);
        $cumulative = 0;

        foreach ($pool as $item) {
            $cumulative += $item->weight;
            if ($rand <= $cumulative) {
                return $item;
            }
        }

        return $pool->last();
    }
}
