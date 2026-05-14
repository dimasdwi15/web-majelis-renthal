<?php

namespace App\Observers;

use App\Models\Barang;
use Illuminate\Support\Facades\Cache;

/**
 * Invalidasi cache konteks katalog AI setiap kali data barang berubah.
 * Ini memastikan AI selalu menerima data terbaru dari database.
 */
class BarangObserver
{
    private const CACHE_KEY = 'chat_catalog_context';

    public function saved(Barang $barang): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    public function deleted(Barang $barang): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    public function restored(Barang $barang): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
