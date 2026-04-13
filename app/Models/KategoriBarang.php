<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KategoriBarang extends Model
{
    protected $table = 'kategori_barang';

    protected $fillable = [
        'nama',
        'slug',
        'ikon',
        'aktif',
    ];

    // Scope hanya kategori yang aktif
    public function scopeAktif($query)
    {
        return $query->where('aktif', 1);
    }

    // Relasi ke barang (semua, untuk admin)
    public function barang()
    {
        return $this->hasMany(Barang::class, 'kategori_barang_id');
    }

    // Relasi hanya ke barang aktif (untuk katalog publik)
    public function barangAktif()
    {
        return $this->hasMany(Barang::class, 'kategori_barang_id')
            ->where('status', 'aktif');
    }
}
