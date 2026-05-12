<?php

namespace App\Http\Controllers;

use App\Models\Barang;
use App\Models\KategoriBarang;
use Illuminate\Http\Request;

class KatalogController extends Controller
{
    public function index(Request $request)
    {
        $kategori = KategoriBarang::where('aktif', 1)
            ->withCount([
                'barang as barang_aktif_count' => fn ($q) => $q->where('status', 'aktif'),
            ])
            ->orderBy('nama')
            ->get();

        // Pastikan eager-load menggunakan nama relasi yang benar:
        // - fotoUtama : hasOne BarangFoto (foto pertama / utama)
        // - fotos     : hasMany BarangFoto (semua foto, untuk galeri di modal detail)
        //
        // Hanya barang status "aktif" + kategori masih aktif (konsisten dengan sidebar).
        $query = Barang::with(['kategori', 'fotoUtama', 'fotos'])
            ->aktif()
            ->whereHas('kategori', fn ($q) => $q->where('aktif', 1));

        // Filter: pencarian teks
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")
                    ->orWhere('deskripsi', 'like', "%{$search}%")
                    ->orWhere('spesifikasi', 'like', "%{$search}%");
            });
        }

        // Filter: kategori (checkbox = ID numerik; link dari home = slug, mis. ?kategori=tenda)
        $kategoriIds = $this->resolveKategoriBarangIds($request);
        if ($kategoriIds !== []) {
            $query->whereIn('kategori_barang_id', $kategoriIds);
        }

        // Filter: max harga per hari
        if ($request->filled('harga')) {
            $query->where('harga_per_hari', '<=', (int) $request->harga);
        }

        // Sorting
        match ($request->get('sort', 'terbaru')) {
            'harga_asc' => $query->orderBy('harga_per_hari', 'asc'),
            'harga_desc' => $query->orderBy('harga_per_hari', 'desc'),
            'nama_asc' => $query->orderBy('nama', 'asc'),
            default => $query->latest(),
        };

        $perPage = (int) $request->get('perPage', 6);
        $barang = $query->paginate($perPage)->withQueryString();

        return view('user.pages.katalog', compact('barang', 'kategori'));
    }

    /**
     * @return list<int>
     */
    private function resolveKategoriBarangIds(Request $request): array
    {
        if (! $request->filled('kategori')) {
            return [];
        }

        $raw = $request->kategori;
        $values = is_array($raw) ? $raw : [$raw];

        $ids = [];
        $slugs = [];

        foreach ($values as $v) {
            if ($v === null || $v === '') {
                continue;
            }
            if (is_numeric($v)) {
                $ids[] = (int) $v;
            } else {
                $slugs[] = (string) $v;
            }
        }

        if ($slugs !== []) {
            $fromSlug = KategoriBarang::query()
                ->where('aktif', 1)
                ->whereIn('slug', $slugs)
                ->pluck('id')
                ->all();
            $ids = array_merge($ids, $fromSlug);
        }

        $ids = array_values(array_unique(array_filter($ids)));

        if ($ids === []) {
            return [];
        }

        // Hanya ID yang benar-benar kategori aktif (cegah manipulasi URL)
        return KategoriBarang::query()
            ->where('aktif', 1)
            ->whereIn('id', $ids)
            ->pluck('id')
            ->all();
    }
}
