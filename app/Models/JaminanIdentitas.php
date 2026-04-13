<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JaminanIdentitas extends Model
{
    protected $table = 'jaminan_identitas';

    protected $fillable = [
        'transaksi_id',
        'user_id',
        'jenis_identitas',
        'path_file',
        'status',
        'dihapus_pada'
    ];

    public function transaksi()
    {
        return $this->belongsTo(Transaksi::class, 'transaksi_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
