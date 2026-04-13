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
                'barang as barang_aktif_count' => fn($q) => $q->where('status', 'aktif'),
            ])
            ->orderBy('nama')
            ->get();

        $query = Barang::with(['kategori', 'fotoUtama'])
            ->where('status', 'aktif');

        // Filter: pencarian teks
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")
                    ->orWhere('deskripsi', 'like', "%{$search}%")
                    ->orWhere('spesifikasi', 'like', "%{$search}%");
            });
        }

        // Filter: kategori (multi-select)
        if ($request->filled('kategori')) {
            $query->whereIn('kategori_barang_id', (array) $request->kategori);
        }

        // Filter: max harga per hari
        if ($request->filled('harga')) {
            $query->where('harga_per_hari', '<=', (int) $request->harga);
        }

        // Sorting
        match ($request->get('sort', 'terbaru')) {
            'harga_asc'  => $query->orderBy('harga_per_hari', 'asc'),
            'harga_desc' => $query->orderBy('harga_per_hari', 'desc'),
            'nama_asc'   => $query->orderBy('nama', 'asc'),
            default      => $query->latest(),
        };

        $barang = $query->paginate(9)->withQueryString();

        $perPage = (int) request('perPage', 6); // default 6
        $barang = $query->paginate($perPage)->withQueryString();

        return view('user.pages.katalog', compact('barang', 'kategori'));
    }
}
