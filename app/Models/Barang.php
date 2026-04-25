<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

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

    // ── Scopes ───────────────────────────────────────────────────────────────

    /**
     * Hanya barang yang aktif dan masih ada stok.
     */
    public function scopeAktif($query)
    {
        return $query->where('status', 'aktif');
    }

    public function scopeTersedia($query)
    {
        return $query->where('status', 'aktif')->where('stok', '>', 0);
    }

    // ── Relationships ─────────────────────────────────────────────────────────

    public function kategori(): BelongsTo
    {
        return $this->belongsTo(KategoriBarang::class, 'kategori_barang_id');
    }

    public function foto(): HasMany
    {
        return $this->hasMany(BarangFoto::class, 'barang_id');
    }

    /**
     * Foto pertama yang diupload = foto utama (konsisten).
     */
    public function fotoUtama(): HasOne
    {
        return $this->hasOne(BarangFoto::class, 'barang_id')->oldestOfMany();
    }

    public function transaksiDetail(): HasMany
    {
        return $this->hasMany(TransaksiDetail::class, 'barang_id');
    }

    /**
     * Tag fungsional barang (many-to-many via barang_tag).
     *
     * Contoh: Tenda → [shelter, waterproof]
     *         Jas hujan → [waterproof]
     *         Sleeping bag → [insulating]
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'barang_tag');
    }
}
