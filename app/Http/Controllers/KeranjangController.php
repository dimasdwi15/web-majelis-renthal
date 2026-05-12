<?php

namespace App\Http\Controllers;

use App\Models\Barang;
use App\Support\CartSessionHelper;
use Illuminate\Http\Request;

class KeranjangController extends Controller
{
    /**
     * Cart JSON harus selalu object { "id": {...} } — bukan array [] — supaya klien
     * tidak menginterpretasikan sebagai "bukan object" dan mengosongkan keranjang.
     */
    private function cartJsonPayload(array $cart): \stdClass
    {
        if ($cart === []) {
            return new \stdClass;
        }

        $out = new \stdClass;
        foreach ($cart as $key => $value) {
            $out->{(string) $key} = $value;
        }

        return $out;
    }

    private function getCart(): array
    {
        return CartSessionHelper::normalizeKeys(session('cart', []));
    }

    private function saveCart(array $cart): void
    {
        session(['cart' => $cart]);
        session()->save();
    }

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
        $cart = $this->getCart();

        if ($cart === []) {
            return $cart;
        }

        // Ambil semua barang sekaligus — hindari N+1 query
        $barangList = Barang::with('fotoUtama')
            ->whereIn('id', array_keys($cart))
            ->get()
            ->keyBy(fn ($b) => (string) $b->id);

        foreach ($cart as $id => $item) {
            $barang = $barangList->get((string) $id);

            // Keluarkan dari cart jika barang tidak ada atau nonaktif
            if (! $barang || $barang->status !== 'aktif') {
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

        $this->saveCart($cart);

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
     * Tambah item ke keranjang via AJAX.
     */
    public function tambah(Request $request, $barangId)
    {
        $barang = Barang::with('fotoUtama')->find($barangId);

        if (! $barang || $barang->status !== 'aktif' || $barang->stok < 1) {
            return response()->json([
                'success' => false,
                'message' => 'Barang tidak tersedia.',
            ], 422);
        }

        $cart = $this->getCart();
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

        $this->saveCart($cart);

        return response()->json([
            'success' => true,
            'message' => "{$barang->nama} ditambahkan ke keranjang.",
            'cart'    => $this->cartJsonPayload($cart),
            'count'   => collect($cart)->sum('qty'),
        ]);
    }

    /**
     * Sewa langsung: tambahkan item ke keranjang, lalu redirect ke checkout.
     * Jika item sudah ada di keranjang, qty dipertahankan (tidak digandakan).
     * Digunakan oleh tombol "Sewa Sekarang" di halaman katalog.
     */
    public function sewaLangsung($barangId)
    {
        $barang = Barang::with('fotoUtama')->find($barangId);

        if (! $barang || $barang->status !== 'aktif' || $barang->stok < 1) {
            return redirect()->route('katalog')
                ->with('error', 'Barang tidak tersedia atau stok habis.');
        }

        $cart = $this->getCart();
        $id   = (string) $barang->id;

        if (! isset($cart[$id])) {
            $cart[$id] = [
                'barang_id' => $barang->id,
                'nama'      => $barang->nama,
                'harga'     => (float) $barang->harga_per_hari,
                'stok'      => $barang->stok,
                'foto'      => $barang->fotoUtama?->path_foto,
                'qty'       => 1,
            ];
            $this->saveCart($cart);
        }

        return redirect()->route('checkout.index');
    }

    /**
     * Hapus satu item dari keranjang via AJAX.
     */
    public function hapus(Request $request, $barangId)
    {
        $cart = $this->getCart();
        unset($cart[(string) $barangId]);
        $this->saveCart($cart);

        return response()->json([
            'success' => true,
            'cart'    => $this->cartJsonPayload($cart),
            'count'   => collect($cart)->sum('qty'),
        ]);
    }

    /**
     * Update qty item di keranjang via AJAX.
     */
    public function update(Request $request, $barangId)
    {
        $cart = $this->getCart();
        $id   = (string) $barangId;
        $qty  = max(1, (int) $request->qty);

        if (! isset($cart[$id])) {
            return response()->json(['success' => false, 'message' => 'Item tidak ditemukan.'], 404);
        }

        if ($qty > $cart[$id]['stok']) {
            return response()->json([
                'success' => false,
                'message' => 'Melebihi batas stok tersedia.',
            ], 422);
        }

        $cart[$id]['qty'] = $qty;
        $this->saveCart($cart);

        return response()->json([
            'success' => true,
            'cart'    => $this->cartJsonPayload($cart),
            'count'   => collect($cart)->sum('qty'),
        ]);
    }

    /**
     * Kosongkan seluruh keranjang.
     */
    public function kosongkan()
    {
        session()->forget('cart');
        session()->save();

        return response()->json([
            'success' => true,
            'cart'    => new \stdClass,
            'count'   => 0,
        ]);
    }
}
