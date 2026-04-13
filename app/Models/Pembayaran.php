<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pembayaran extends Model
{
    protected $table = 'pembayaran';

    protected $fillable = [
        'transaksi_id',
        'jenis',
        'jumlah',
        'metode',
        'status',
        'referensi_midtrans',
        'dibayar_pada'
    ];

    public function transaksi()
    {
        return $this->belongsTo(Transaksi::class, 'transaksi_id');
    }
}
