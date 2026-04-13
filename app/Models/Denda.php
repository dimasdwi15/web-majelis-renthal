<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Denda extends Model
{
    protected $table = 'denda';

    protected $fillable = [
        'transaksi_id',
        'jenis',
        'jumlah',
        'catatan',
        'dibuat_oleh',
        'dibayar_pada'
    ];

    public function transaksi()
    {
        return $this->belongsTo(Transaksi::class, 'transaksi_id');
    }

    public function pembuat()
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }

    public function foto()
    {
        return $this->hasMany(DendaFoto::class, 'denda_id');
    }
}
