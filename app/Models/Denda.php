<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Model Denda
 *
 * FIX Bug C: Tambahkan relasi foto() → HasMany(DendaFoto)
 * agar with(['denda.foto']) di CheckoutController::detailLengkap()
 * bisa eager-load dengan benar dan $d['foto'] terisi.
 */
class Denda extends Model
{
    protected $table = 'denda';

    protected $fillable = [
        'transaksi_id',
        'jenis',        // 'keterlambatan' | 'kerusakan'
        'jumlah',
        'catatan',
        'dibayar_pada',
    ];

    protected $casts = [
        'jumlah'      => 'decimal:2',
        'dibayar_pada' => 'datetime',
    ];

    // ── Relationships ──────────────────────────────────────────────────────

    public function transaksi(): BelongsTo
    {
        return $this->belongsTo(Transaksi::class, 'transaksi_id');
    }

    /**
     * Foto bukti denda (bisa lebih dari satu).
     *
     * Nama relasi HARUS 'foto' agar cocok dengan:
     *   - with(['denda.foto'])         di CheckoutController::detailLengkap()
     *   - $d['foto'] setelah toArray() di loop yang sama
     */
    public function foto(): HasMany
    {
        return $this->hasMany(DendaFoto::class, 'denda_id');
    }
}
