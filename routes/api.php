<?php

use App\Http\Controllers\CuacaController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\Auth\AuthController;
use App\Http\Controllers\API\BarangController;
use App\Http\Controllers\API\ProfileController;
use App\Http\Controllers\API\ImageRecommendationController;
use App\Http\Controllers\API\ChatController;

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
// POST   /api/chat          → kirim pesan
// GET    /api/chat/history  → riwayat chat (butuh session_id atau token)
// DELETE /api/chat/clear    → hapus riwayat
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
});
