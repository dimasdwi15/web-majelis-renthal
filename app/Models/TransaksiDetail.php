<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransaksiDetail extends Model
{
    protected $table = 'transaksi_detail';

    protected $fillable = [
        'transaksi_id',
        'barang_id',
        'jumlah',
        'harga_per_hari',
        'durasi_hari',
        'subtotal',
    ];

    protected $casts = [
        'harga_per_hari' => 'decimal:2',
        'subtotal'       => 'decimal:2',
    ];

    // ── Relasi ──────────────────────────────────────────────────────────

    /**
     * Transaksi induk.
     */
    public function transaksi()
    {
        return $this->belongsTo(Transaksi::class, 'transaksi_id');
    }

    /**
     * Barang yang disewa.
     */
    public function barang()
    {
        return $this->belongsTo(Barang::class, 'barang_id');
    }
}
