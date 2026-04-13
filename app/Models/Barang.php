<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Barang extends Model
{
    protected $table = 'barang';

    protected $fillable = [
        'kategori_barang_id',
        'nama',
        'deskripsi',
        'spesifikasi',
        'harga_per_hari',
        'stok',
        'status',
    ];

    // Scope untuk filter barang aktif
    public function scopeAktif($query)
    {
        return $query->where('status', 'aktif');
    }

    public function kategori()
    {
        return $this->belongsTo(KategoriBarang::class, 'kategori_barang_id');
    }

    public function foto()
    {
        return $this->hasMany(BarangFoto::class, 'barang_id');
    }

    public function transaksiDetail()
    {
        return $this->hasMany(TransaksiDetail::class, 'barang_id');
    }

    // FIX: oldestOfMany() = foto pertama diupload = foto utama
    // latestOfMany() = foto terbaru = tidak konsisten sebagai "foto utama"
    public function fotoUtama()
    {
        return $this->hasOne(BarangFoto::class, 'barang_id')->oldestOfMany();
    }
}
