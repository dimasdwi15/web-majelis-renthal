<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BarangRusak extends Model
{
    public const STATUS_MENUNGGU = 'menunggu_perbaikan';

    public const STATUS_DIPERBAIKI = 'sudah_diperbaiki';

    protected $table = 'barang_rusak';

    protected $fillable = [
        'transaksi_id',
        'transaksi_detail_id',
        'barang_id',
        'denda_id',
        'jumlah',
        'status',
        'catatan_kerusakan',
        'dibuat_oleh',
        'diperbaiki_pada',
        'diperbaiki_oleh',
    ];

    protected $casts = [
        'diperbaiki_pada' => 'datetime',
    ];

    public function transaksi(): BelongsTo
    {
        return $this->belongsTo(Transaksi::class, 'transaksi_id');
    }

    public function transaksiDetail(): BelongsTo
    {
        return $this->belongsTo(TransaksiDetail::class, 'transaksi_detail_id');
    }

    public function barang(): BelongsTo
    {
        return $this->belongsTo(Barang::class, 'barang_id');
    }

    public function denda(): BelongsTo
    {
        return $this->belongsTo(Denda::class, 'denda_id');
    }

    public function pembuat(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }

    public function perbaikiOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diperbaiki_oleh');
    }

    public function scopeMenungguPerbaikan($query)
    {
        return $query->where('status', self::STATUS_MENUNGGU);
    }
}
