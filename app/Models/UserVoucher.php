<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserVoucher extends Model
{
    protected $fillable = [
        'user_id',
        'voucher_template_id',
        'free_barang_id',
        'mystery_box_item_id',
        'mystery_title_snapshot',
        'mystery_foto_url_snapshot',
        'unique_code',
        'expires_at',
        'used_at',
        'used_in_transaksi_id',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'used_at'    => 'datetime',
    ];

    // ── Scopes ───────────────────────────────────────────────────────────
    public function scopeActive($query)
    {
        return $query->whereNull('used_at')
            ->where('expires_at', '>', now());
    }

    public function scopeAvailable($query)
    {
        return $query->active();
    }

    // ── Accessors ─────────────────────────────────────────────────────────
    public function getIsExpiredAttribute(): bool
    {
        return $this->expires_at->isPast();
    }

    public function getIsUsedAttribute(): bool
    {
        return ! is_null($this->used_at);
    }

    public function getExpiresInAttribute(): string
    {
        if ($this->is_expired) return 'Kadaluwarsa';
        return $this->expires_at->diffForHumans(now(), true) . ' lagi';
    }

    /**
     * Judul dinamis voucher.
     * Prioritas: mystery box snapshot > free barang relasi > template title
     */
    public function getDynamicTitleAttribute(): string
    {

        if (
            $this->mystery_box_item_id === null &&
            $this->voucher_template_id === null &&
            $this->mystery_title_snapshot
        ) {
            return $this->mystery_title_snapshot; // pakai snapshot
        }
        // Mystery box reward — gunakan snapshot (permanen, tidak terpengaruh perubahan data)
        if ($this->mystery_box_item_id && $this->mystery_title_snapshot) {
            return $this->mystery_title_snapshot;
        }

        // Mystery box reward — fallback ke relasi item jika snapshot kosong
        if ($this->mystery_box_item_id && $this->mysteryBoxItem) {
            $item = $this->mysteryBoxItem;
            if ($item->type === 'free_rental') {
                return 'Gratis Sewa ' . $item->barang_nama;
            }
            return $item->title;
        }

        // Voucher redeem XP dengan item gratis
        if ($this->free_barang_id && $this->freeBarang) {
            return 'Gratis Sewa ' . $this->freeBarang->nama;
        }

        return $this->template?->title ?? 'Voucher';
    }

    /**
     * Deskripsi dinamis voucher.
     */
    public function getDynamicDescriptionAttribute(): string
    {
        // Mystery box reward
        if ($this->mystery_box_item_id && $this->mysteryBoxItem) {
            return $this->mysteryBoxItem->description ?? 'Hadiah dari Mystery Box!';
        }

        // Voucher redeem XP dengan item gratis
        if ($this->free_barang_id && $this->freeBarang) {
            return 'Dapatkan gratis sewa ' . $this->freeBarang->nama . ' untuk transaksi Anda!';
        }

        return $this->template?->description ?? '';
    }

    /**
     * URL foto untuk mystery box reward (dari snapshot).
     */
    public function getMysteryFotoUrlAttribute(): ?string
    {
        return $this->mystery_foto_url_snapshot;
    }

    // ── Relations ────────────────────────────────────────────────────────
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(VoucherTemplate::class, 'voucher_template_id');
    }

    public function transaksi(): BelongsTo
    {
        return $this->belongsTo(Transaksi::class, 'used_in_transaksi_id');
    }

    public function freeBarang(): BelongsTo
    {
        return $this->belongsTo(Barang::class, 'free_barang_id');
    }

    public function mysteryBoxItem(): BelongsTo
    {
        return $this->belongsTo(MysteryBoxItem::class, 'mystery_box_item_id');
    }
}
