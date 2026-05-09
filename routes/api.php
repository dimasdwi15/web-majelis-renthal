<?php

use App\Http\Controllers\CuacaController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\Auth\AuthController;
use App\Http\Controllers\API\BarangController;
use App\Http\Controllers\API\ProfileController;  // ← TAMBAHAN

// ─────────────────────────────────────────────────────────────────
// Cuaca & Lokasi (public)
// ─────────────────────────────────────────────────────────────────
Route::get('/cuaca',          [CuacaController::class, 'cek']);
Route::get('/lokasi/cari',    [CuacaController::class, 'cariLokasi']);
Route::get('/lokasi/reverse', [CuacaController::class, 'reverseLokasi']);

// ─────────────────────────────────────────────────────────────────
// Katalog Barang & Kategori (public — tidak perlu token)
// ─────────────────────────────────────────────────────────────────
Route::get('/kategori',       [BarangController::class, 'kategori']);

Route::prefix('barang')->group(function () {
    Route::get('/',        [BarangController::class, 'index']);
    Route::get('/{id}',    [BarangController::class, 'show']);
});

// ─────────────────────────────────────────────────────────────────
// Auth Routes (public — tidak perlu token)
// ─────────────────────────────────────────────────────────────────
Route::prefix('auth')->group(function () {
    Route::post('register',        [AuthController::class, 'register']);
    Route::post('login',           [AuthController::class, 'login']);
    Route::post('google',          [AuthController::class, 'googleAuth']);
    Route::post('set-password',    [AuthController::class, 'setPassword']);
});

// ─────────────────────────────────────────────────────────────────
// Protected Routes — Wajib Bearer Token (Sanctum)
// ─────────────────────────────────────────────────────────────────
Route::middleware('auth:sanctum')->group(function () {

    // Auth
    Route::prefix('auth')->group(function () {
        Route::get('me',        [AuthController::class, 'me']);
        Route::delete('logout', [AuthController::class, 'logout']);
    });

    // ── Profile ──────────────────────────────────────────────────
    // GET    /api/profile                → ambil profil user login
    // PUT    /api/profile                → update nama, phone, alamat
    // POST   /api/profile/change-password → ganti kata sandi
    Route::prefix('profile')->group(function () {
        Route::get('/',                 [ProfileController::class, 'show']);
        Route::put('/',                 [ProfileController::class, 'update']);
        Route::post('change-password',  [ProfileController::class, 'changePassword']);
    });
});
