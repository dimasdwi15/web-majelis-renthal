<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * Tambahkan 'api/midtrans/callback' agar Midtrans bisa mengirim
     * webhook POST ke endpoint Flutter tanpa CSRF token.
     *
     * 'midtrans/callback' (tanpa prefix 'api/') adalah endpoint web lama —
     * biarkan tetap ada jika masih dipakai.
     *
     * @var array<int, string>
     */
    protected $except = [
        'midtrans/callback',       // ← endpoint web yang sudah ada, jangan dihapus
        'api/midtrans/callback',   // ← endpoint baru untuk Flutter
    ];
}
