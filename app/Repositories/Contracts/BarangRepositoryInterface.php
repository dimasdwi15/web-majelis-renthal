<?php

namespace App\Repositories\Contracts;

use Illuminate\Database\Eloquent\Collection;

interface BarangRepositoryInterface
{
    /**
     * Cari barang berdasarkan array slug tag, hanya yang stok > 0 dan aktif.
     * Kembalikan dengan jumlah tag match (score).
     */
    public function findByTagSlugs(array $slugs, int $limit = 12): Collection;

    /**
     * Ambil barang populer sebagai fallback (stok terbanyak, status aktif).
     */
    public function getPopularItems(int $limit = 8): Collection;
}
