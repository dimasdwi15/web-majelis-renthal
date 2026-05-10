<?php

namespace App\Repositories;

use App\Models\Barang;
use App\Repositories\Contracts\BarangRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class BarangRepository implements BarangRepositoryInterface
{
    /**
     * Cari barang berdasarkan tag slugs.
     * Gunakan subquery COUNT untuk hitung match score per barang.
     */
    public function findByTagSlugs(array $slugs, int $limit = 12): Collection
    {
        if (empty($slugs)) {
            return $this->getPopularItems($limit);
        }

        return Barang::query()
            ->with(['fotos', 'kategori', 'tags'])
            ->where('status', 'aktif')
            ->where('stok', '>', 0)
            ->whereHas('tags', function ($query) use ($slugs) {
                $query->whereIn('slug', $slugs);
            })
            ->select('barang.*')
            ->selectSub(
                DB::table('barang_tag')
                    ->join('tags', 'tags.id', '=', 'barang_tag.tag_id')
                    ->whereColumn('barang_tag.barang_id', 'barang.id')
                    ->whereIn('tags.slug', $slugs)
                    ->selectRaw('COUNT(*)'),
                'match_score'
            )
            ->orderByDesc('match_score')
            ->orderByDesc('stok')
            ->limit($limit)
            ->get();
    }

    /**
     * Fallback: barang aktif, stok terbanyak.
     */
    public function getPopularItems(int $limit = 8): Collection
    {
        return Barang::query()
            ->with(['fotos', 'kategori', 'tags'])
            ->where('status', 'aktif')
            ->where('stok', '>', 0)
            ->orderByDesc('stok')
            ->limit($limit)
            ->get()
            ->each(fn ($item) => $item->match_score = 0);
    }
}
