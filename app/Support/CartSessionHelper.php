<?php

namespace App\Support;

use App\Models\Barang;

class CartSessionHelper
{
    /**
     * Normalisasi struktur keranjang di session (kunci string, qty minimal 1).
     *
     * @param  array<string, mixed>  $cart
     * @return array<string, array{nama: string, harga: float, stok: int, qty: int, foto: ?string}>
     */
    public static function normalizeKeys(array $cart): array
    {
        $out = [];
        foreach ($cart as $id => $row) {
            if (! is_array($row)) {
                continue;
            }
            $key = (string) $id;
            $out[$key] = [
                'qty' => max(1, (int) ($row['qty'] ?? 1)),
                'nama' => (string) ($row['nama'] ?? ''),
                'harga' => isset($row['harga']) ? (float) $row['harga'] : 0.0,
                'stok' => isset($row['stok']) ? (int) $row['stok'] : 0,
                'foto' => $row['foto'] ?? null,
            ];
        }

        return $out;
    }

    /**
     * Samakan data keranjang dengan DB (nama, harga, stok, foto) dan buang barang tidak aktif.
     *
     * @return array<string, array{nama: string, harga: float, stok: int, qty: int, foto: ?string}>
     */
    public static function getRefreshedCart(): array
    {
        $cart = self::normalizeKeys(session('cart', []));

        if ($cart === []) {
            session(['cart' => []]);
            session()->save();

            return [];
        }

        $ids = array_map(intval(...), array_keys($cart));

        $barangList = Barang::with('fotoUtama')
            ->whereIn('id', $ids)
            ->get()
            ->keyBy(fn ($b) => (string) $b->id);

        foreach ($cart as $id => $item) {
            $barang = $barangList->get((string) $id);

            if (! $barang || $barang->status !== 'aktif') {
                unset($cart[$id]);
                continue;
            }

            $cart[$id]['nama'] = $barang->nama;
            $cart[$id]['harga'] = (float) $barang->harga_per_hari;
            $cart[$id]['stok'] = $barang->stok;
            $cart[$id]['foto'] = $barang->fotoUtama?->path_foto;

            if ($cart[$id]['qty'] > $barang->stok) {
                $cart[$id]['qty'] = max(1, $barang->stok);
            }
        }

        session(['cart' => $cart]);
        session()->save();

        return $cart;
    }
}
