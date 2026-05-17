<?php

use App\Http\Controllers\CuacaController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\Auth\AuthController;
use App\Http\Controllers\API\Auth\PasswordResetController;
use App\Http\Controllers\API\BarangController;
use App\Http\Controllers\API\ProfileController;
use App\Http\Controllers\API\ImageRecommendationController;
use App\Http\Controllers\API\ChatController;
use App\Http\Controllers\API\CheckoutController;
use App\Http\Controllers\API\MidtransCallbackController; // ← TAMBAHAN

// ─────────────────────────────────────────────────────────────────
// Midtrans Callback (PUBLIC — tanpa auth, dipanggil server Midtrans)
// ─────────────────────────────────────────────────────────────────
// URL yang didaftarkan di dashboard Midtrans Sandbox:
//   https://xxxx.ngrok-free.app/api/midtrans/callback
// ─────────────────────────────────────────────────────────────────
Route::post('/midtrans/callback', [MidtransCallbackController::class, 'handle'])
    ->name('api.midtrans.callback');

// ─────────────────────────────────────────────────────────────────
// Cuaca & Lokasi (public)
// ─────────────────────────────────────────────────────────────────
Route::get('/cuaca',          [CuacaController::class, 'cek']);
Route::get('/lokasi/cari',    [CuacaController::class, 'cariLokasi']);
Route::get('/lokasi/reverse', [CuacaController::class, 'reverseLokasi']);

// ─────────────────────────────────────────────────────────────────
// Katalog Barang & Kategori (public)
// ─────────────────────────────────────────────────────────────────
Route::get('/kategori', [BarangController::class, 'kategori']);

Route::prefix('barang')->group(function () {
    Route::get('/',     [BarangController::class, 'index']);
    Route::get('/{id}', [BarangController::class, 'show']);
});

// ─────────────────────────────────────────────────────────────────
// AI Image Recommendation (public)
// ─────────────────────────────────────────────────────────────────
Route::prefix('recommendation')->group(function () {
    Route::post('/image', [ImageRecommendationController::class, 'analyze']);
});

// ─────────────────────────────────────────────────────────────────
// AI Chat Assistant (public — guest & user login bisa pakai)
// ─────────────────────────────────────────────────────────────────
Route::prefix('chat')->group(function () {
    Route::post('/',        [ChatController::class, 'send']);
    Route::get('/history',  [ChatController::class, 'history']);
    Route::delete('/clear', [ChatController::class, 'clear']);
});

// ─────────────────────────────────────────────────────────────────
// Auth Routes (public)
// ─────────────────────────────────────────────────────────────────
Route::prefix('auth')->group(function () {
    Route::post('register',     [AuthController::class, 'register']);
    Route::post('login',        [AuthController::class, 'login']);
    Route::post('google',       [AuthController::class, 'googleAuth']);
    Route::post('set-password', [AuthController::class, 'setPassword']);

    Route::post('forgot-password',  [PasswordResetController::class, 'forgotPassword']);
    Route::post('verify-reset-otp', [PasswordResetController::class, 'verifyResetOtp']);
    Route::post('reset-password',   [PasswordResetController::class, 'resetPassword']);
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

    // Profile
    Route::prefix('profile')->group(function () {
        Route::get('/',                [ProfileController::class, 'show']);
        Route::put('/',                [ProfileController::class, 'update']);
        Route::post('change-password', [ProfileController::class, 'changePassword']);
    });

    // AI Recommendation — riwayat (hanya user login)
    Route::prefix('recommendation')->group(function () {
        Route::get('/history', [ImageRecommendationController::class, 'history']);
    });

    // ── Checkout ──────────────────────────────────────────────────────────
    Route::prefix('checkout')->group(function () {
        Route::post('/validasi-identitas',   [CheckoutController::class, 'validasiIdentitas']);
        Route::post('/',                     [CheckoutController::class, 'store']);
        Route::get('/history',               [CheckoutController::class, 'history']);
        Route::get('/{id}',                  [CheckoutController::class, 'show']);
        Route::post('/{id}/reopen-payment',  [CheckoutController::class, 'reopenPayment']);
        Route::post('/{id}/bayar-denda',     [CheckoutController::class, 'bayarDenda']);
        Route::get('/{id}/detail-lengkap',   [CheckoutController::class, 'detailLengkap']);
    });

    // ── Cuaca & Lokasi ────────────────────────────────────────────────────
    Route::prefix('lokasi')->group(function () {
        Route::get('cari',    [CuacaController::class, 'cariLokasi']);
        Route::get('reverse', [CuacaController::class, 'reverseLokasi']);
    });

    Route::get('cuaca', [CuacaController::class, 'cek']);
});
