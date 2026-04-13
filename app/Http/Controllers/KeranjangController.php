<?php

namespace App\Http\Controllers;

use App\Models\Barang;
use Illuminate\Http\Request;

class KeranjangController extends Controller
{
    /**
     * Sinkronisasi data cart di session dengan data terbaru dari database.
     * Memperbarui nama, harga, stok, dan foto setiap item.
     * Item yang nonaktif / dihapus admin dikeluarkan dari cart.
     * Qty yang melebihi stok terbaru di-clamp ke nilai stok tersedia.
     *
     * @return array  Cart yang sudah di-refresh (sudah disimpan ke session)
     */
    private function refreshCartSession(): array
    {
        $cart = session('cart', []);

        if (empty($cart)) {
            return $cart;
        }

        // Ambil semua barang sekaligus — hindari N+1 query
        $barangList = Barang::with('fotoUtama')
            ->whereIn('id', array_keys($cart))
            ->get()
            ->keyBy('id');

        foreach ($cart as $id => $item) {
            $barang = $barangList->get($id);

            // Keluarkan dari cart jika barang tidak ada atau nonaktif
            if (!$barang || $barang->status !== 'aktif') {
                unset($cart[$id]);
                continue;
            }

            // Update semua field yang bisa berubah dari admin panel
            $cart[$id]['nama']  = $barang->nama;
            $cart[$id]['harga'] = (float) $barang->harga_per_hari;
            $cart[$id]['stok']  = $barang->stok;
            $cart[$id]['foto']  = $barang->fotoUtama?->path_foto;

            // Pastikan qty tidak melebihi stok terbaru
            if ($cart[$id]['qty'] > $barang->stok) {
                $cart[$id]['qty'] = max(1, $barang->stok);
            }
        }

        session(['cart' => $cart]);

        return $cart;
    }

    /**
     * Tampilkan halaman keranjang.
     * Refresh data dari DB agar selalu up-to-date.
     */
    public function index()
    {
        $cart = $this->refreshCartSession();

        return view('user.pages.keranjang', [
            'cart' => $cart,
        ]);
    }

    /**
     * Endpoint AJAX untuk menyegarkan data cart dari DB.
     * Dipanggil oleh Alpine store setiap kali cart panel dibuka,
     * dan checkout component saat halaman dimuat.
     */
    public function refresh()
    {
        $cart = $this->refreshCartSession();

        return response()->json([
            'success' => true,
            'cart'    => $cart,
            'count'   => collect($cart)->sum('qty'),
        ]);
    }

    /**
     * Tambah item ke keranjang via AJAX.
     */
    public function tambah(Request $request, Barang $barang)
    {
        if ($barang->status !== 'aktif' || $barang->stok < 1) {
            return response()->json([
                'success' => false,
                'message' => 'Barang tidak tersedia.',
            ], 422);
        }

        $cart = session('cart', []);
        $id   = (string) $barang->id;

        if (isset($cart[$id])) {
            if ($cart[$id]['qty'] >= $barang->stok) {
                return response()->json([
                    'success' => false,
                    'message' => 'Stok maksimal sudah tercapai.',
                ], 422);
            }
            $cart[$id]['qty']++;
        } else {
            $cart[$id] = [
                'barang_id' => $barang->id,
                'nama'      => $barang->nama,
                'harga'     => (float) $barang->harga_per_hari,
                'stok'      => $barang->stok,
                'foto'      => $barang->fotoUtama?->path_foto,
                'qty'       => 1,
            ];
        }

        session(['cart' => $cart]);

        return response()->json([
            'success' => true,
            'message' => "{$barang->nama} ditambahkan ke keranjang.",
            'cart'    => $cart,
            'count'   => collect($cart)->sum('qty'),
        ]);
    }

    /**
     * Hapus satu item dari keranjang via AJAX.
     */
    public function hapus(Request $request, $barangId)
    {
        $cart = session('cart', []);
        unset($cart[(string) $barangId]);
        session(['cart' => $cart]);

        return response()->json([
            'success' => true,
            'cart'    => $cart,
            'count'   => collect($cart)->sum('qty'),
        ]);
    }

    /**
     * Update qty item di keranjang via AJAX.
     */
    public function update(Request $request, $barangId)
    {
        $cart = session('cart', []);
        $id   = (string) $barangId;
        $qty  = max(1, (int) $request->qty);

        if (!isset($cart[$id])) {
            return response()->json(['success' => false, 'message' => 'Item tidak ditemukan.'], 404);
        }

        if ($qty > $cart[$id]['stok']) {
            return response()->json([
                'success' => false,
                'message' => 'Melebihi batas stok tersedia.',
            ], 422);
        }

        $cart[$id]['qty'] = $qty;
        session(['cart' => $cart]);

        return response()->json([
            'success' => true,
            'cart'    => $cart,
            'count'   => collect($cart)->sum('qty'),
        ]);
    }

    /**
     * Kosongkan seluruh keranjang.
     */
    public function kosongkan()
    {
        session()->forget('cart');

        return response()->json([
            'success' => true,
            'cart'    => [],
            'count'   => 0,
        ]);
    }
}
