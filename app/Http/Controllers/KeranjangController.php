<?php

namespace App\Http\Controllers;

use App\Models\Barang;
use App\Support\CartSessionHelper;
use Illuminate\Http\Request;

class KeranjangController extends Controller
{
    private function jsonCart(): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'cart' => CartSessionHelper::getRefreshedCart(),
        ]);
    }

    /**
     * Sinkronisasi state keranjang untuk Alpine (GET JSON).
     */
    public function sync()
    {
        return $this->jsonCart();
    }

    /**
     * Halaman keranjang tidak dipisah — arahkan ke katalog (panel keranjang di navbar).
     */
    public function index()
    {
        return redirect()->route('katalog');
    }

    public function tambah(Request $request, Barang $barang)
    {
        $barang->loadMissing('fotoUtama');

        if ($barang->status !== 'aktif' || $barang->stok < 1) {
            return response()->json(['message' => 'Barang tidak tersedia.'], 422);
        }

        $cart = CartSessionHelper::normalizeKeys(session('cart', []));
        $id = (string) $barang->id;

        if (isset($cart[$id])) {
            $cart[$id]['qty'] = min($cart[$id]['qty'] + 1, $barang->stok);
        } else {
            $cart[$id] = [
                'qty' => 1,
                'nama' => $barang->nama,
                'harga' => (float) $barang->harga_per_hari,
                'stok' => $barang->stok,
                'foto' => $barang->fotoUtama?->path_foto,
            ];
        }

        session(['cart' => $cart]);
        session()->save();

        return $this->jsonCart();
    }

    public function update(Request $request, string $barangId)
    {
        $validated = $request->validate([
            'qty' => ['required', 'integer', 'min:1'],
        ]);

        $qty = (int) $validated['qty'];
        $cart = CartSessionHelper::normalizeKeys(session('cart', []));
        $id = (string) $barangId;

        if (! isset($cart[$id])) {
            return response()->json(['message' => 'Item tidak ada di keranjang.'], 404);
        }

        $barang = Barang::query()->where('status', 'aktif')->find((int) $barangId);

        if (! $barang) {
            unset($cart[$id]);
            session(['cart' => $cart]);
            session()->save();

            return $this->jsonCart();
        }

        $cart[$id]['qty'] = min($qty, $barang->stok);
        if ($cart[$id]['qty'] < 1) {
            $cart[$id]['qty'] = 1;
        }

        session(['cart' => $cart]);
        session()->save();

        return $this->jsonCart();
    }

    public function hapus(string $barangId)
    {
        $cart = CartSessionHelper::normalizeKeys(session('cart', []));
        unset($cart[(string) $barangId]);
        session(['cart' => $cart]);
        session()->save();

        return $this->jsonCart();
    }

    public function kosongkan()
    {
        session()->forget('cart');
        session()->save();

        return $this->jsonCart();
    }

    public function sewaLangsung(Barang $barang)
    {
        $barang->loadMissing('fotoUtama');

        if ($barang->status !== 'aktif' || $barang->stok < 1) {
            return redirect()->route('katalog')->with('error', 'Barang tidak tersedia.');
        }

        $cart = CartSessionHelper::normalizeKeys(session('cart', []));
        $id = (string) $barang->id;

        if (isset($cart[$id])) {
            $cart[$id]['qty'] = min($cart[$id]['qty'] + 1, $barang->stok);
        } else {
            $cart[$id] = [
                'qty' => 1,
                'nama' => $barang->nama,
                'harga' => (float) $barang->harga_per_hari,
                'stok' => $barang->stok,
                'foto' => $barang->fotoUtama?->path_foto,
            ];
        }

        session(['cart' => $cart]);
        session()->save();

        return redirect()->route('checkout.index');
    }
}
