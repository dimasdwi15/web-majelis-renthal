<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BarangFoto extends Model
{
    protected $table = 'barang_foto';

    protected $fillable = [
        'barang_id',
        'path_foto',
    ];

    public function barang()
    {
        return $this->belongsTo(Barang::class, 'barang_id');
    }
}
