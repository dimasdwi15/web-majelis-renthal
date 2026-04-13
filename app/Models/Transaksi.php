<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaksi extends Model
{
    protected $table = 'transaksi';

    protected $fillable = [
        'user_id',
        'nomor_transaksi',
        'status',
        'metode_pembayaran',
        'status_pembayaran',
        'total_sewa',
        'total_denda',
        'total_charge',
        'tanggal_ambil',
        'tanggal_kembali',
    ];

    protected $casts = [
        'tanggal_ambil'   => 'date',
        'tanggal_kembali' => 'date',
        'total_sewa'      => 'decimal:2',
        'total_denda'     => 'decimal:2',
        'total_charge'    => 'decimal:2',
    ];

    // ── Relasi ──────────────────────────────────────────────────────────

    /**
     * Penyewa (user).
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Detail item yang disewa.
     * Diakses via $transaksi->details
     */
    public function details()
    {
        return $this->hasMany(TransaksiDetail::class, 'transaksi_id');
    }

    /**
     * Jaminan identitas penyewa.
     */
    public function jaminanIdentitas()
    {
        return $this->hasOne(JaminanIdentitas::class, 'transaksi_id');
    }

    /**
     * Riwayat pembayaran.
     */
    public function pembayaran()
    {
        return $this->hasMany(Pembayaran::class, 'transaksi_id');
    }

    /**
     * Denda yang dikenakan.
     */
    public function denda()
    {
        return $this->hasMany(Denda::class, 'transaksi_id');
    }
}
