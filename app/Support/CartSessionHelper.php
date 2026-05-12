<?php

namespace App\Support;

/**
 * Normalisasi struktur cart di session (kunci string id, barang_id int).
 * Penting saat session.serialization = json agar kunci tidak bentrok.
 */
final class CartSessionHelper
{
    /**
     * @param  mixed  $cart
     */
    public static function normalizeKeys(mixed $cart): array
    {
        if (! is_array($cart) || $cart === []) {
            return [];
        }

        $out = [];
        foreach ($cart as $key => $row) {
            if (! is_array($row)) {
                continue;
            }
            $id = isset($row['barang_id']) ? (string) (int) $row['barang_id'] : (string) (int) $key;
            $row['barang_id'] = (int) ($row['barang_id'] ?? $id);
            $out[$id] = $row;
        }

        return $out;
    }
}
