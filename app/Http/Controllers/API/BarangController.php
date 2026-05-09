<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Barang;
use App\Models\KategoriBarang;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class BarangController extends Controller
{
    /**
     * GET /api/barang
     * Ambil semua barang aktif, dengan filter opsional:
     *   ?kategori=Tenda
     *   ?search=eiger
     *   ?kategori=Carrier&search=osprey
     */
    public function index(Request $request): JsonResponse
    {
        $query = Barang::tersedia()
            ->with(['kategori', 'fotoUtama', 'tags']);

        if ($request->filled('kategori') && $request->kategori !== 'Semua') {
            $query->whereHas('kategori', function ($q) use ($request) {
                $q->where('nama', 'like', '%' . $request->kategori . '%');
            });
        }

        if ($request->filled('search')) {
            $query->where('nama', 'like', '%' . $request->search . '%');
        }

        $barang = $query->orderBy('created_at', 'desc')->get();

        $data = $barang->map(fn($item) => $this->formatBarang($item));

        return response()->json([
            'success' => true,
            'data'    => $data,
        ]);
    }

    /**
     * GET /api/barang/{id}
     * Detail satu barang beserta semua foto-nya.
     */
    public function show(int $id): JsonResponse
    {
        $barang = Barang::with(['kategori', 'foto', 'tags'])
            ->tersedia()
            ->find($id);

        if (! $barang) {
            return response()->json([
                'success' => false,
                'message' => 'Barang tidak ditemukan atau tidak tersedia.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => $this->formatBarangDetail($barang),
        ]);
    }

    /**
     * GET /api/kategori
     * Ambil semua kategori aktif (untuk pill filter di Flutter).
     */
    public function kategori(): JsonResponse
    {
        $kategori = KategoriBarang::aktif()
            ->withCount(['barangAktif'])
            ->orderBy('nama')
            ->get()
            ->map(fn($k) => [
                'id'          => $k->id,
                'nama'        => $k->nama,
                'slug'        => $k->slug,
                'ikon'        => $k->ikon,
                'jumlah_item' => $k->barang_aktif_count,
            ]);

        return response()->json([
            'success' => true,
            'data'    => $kategori,
        ]);
    }

    // ── Private Helpers ────────────────────────────────────────────────────

    /**
     * Gunakan helper url() bawaan Laravel agar ikut APP_URL di .env
     * dan menghormati URL::forceRootUrl() di AppServiceProvider.
     * JANGAN pakai env('APP_URL') langsung — nilainya tidak diproses.
     */
    private function imageUrl(string $path): string
    {
        return url('storage/' . ltrim($path, '/'));
    }

    private function formatBarang(Barang $item): array
    {
        return [
            'id'             => $item->id,
            'nama'           => $item->nama,
            'kategori'       => $item->kategori?->nama ?? '-',
            'harga_per_hari' => (float) $item->harga_per_hari,
            'stok'           => $item->stok,
            'status'         => $item->status,
            'foto_utama'     => $item->fotoUtama
                                    ? $this->imageUrl($item->fotoUtama->path_foto)
                                    : null,
            'rating'         => '4.8',
        ];
    }

    private function formatBarangDetail(Barang $item): array
    {
        return [
            'id'             => $item->id,
            'nama'           => $item->nama,
            'kategori'       => $item->kategori?->nama ?? '-',
            'deskripsi'      => $item->deskripsi,
            'spesifikasi'    => $item->spesifikasi,
            'harga_per_hari' => (float) $item->harga_per_hari,
            'stok'           => $item->stok,
            'status'         => $item->status,
            'tags'           => $item->tags->pluck('label'),
            'foto'           => $item->foto->map(
                                    fn($f) => $this->imageUrl($f->path_foto)
                                ),
            'rating'         => '4.8',
        ];
    }
}
