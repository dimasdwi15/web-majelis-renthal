<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\KatalogController;
use App\Http\Controllers\KeranjangController;
use App\Http\Controllers\CheckoutController;

Route::get('/', fn() => view('home'));

Route::get('/dashboard', fn() => view('dashboard'))
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile',    [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile',  [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Public pages
Route::get('/home',         fn() => view('home'))->name('home');
Route::get('/tentang-kami', fn() => view('user.pages.about'))->name('about');

// Katalog
Route::get('/katalog', [KatalogController::class, 'index'])->name('katalog');

// Keranjang — session-based, tidak perlu login
Route::prefix('keranjang')->name('keranjang.')->group(function () {
    Route::get('/',                           [KeranjangController::class, 'index'])->name('index');
    Route::post('/tambah/{barang}',           [KeranjangController::class, 'tambah'])->name('tambah');
    Route::patch('/update/{barangId}',        [KeranjangController::class, 'update'])->name('update');
    Route::delete('/hapus/{barangId}',        [KeranjangController::class, 'hapus'])->name('hapus');
    Route::delete('/kosongkan',               [KeranjangController::class, 'kosongkan'])->name('kosongkan');
});

Route::get('/keranjang/refresh', [KeranjangController::class, 'refresh'])->name('keranjang.refresh');

// Checkout (harus login)
Route::middleware(['auth'])->group(function () {
    Route::get ('/checkout',        [CheckoutController::class, 'index'])  ->name('checkout.index');
    Route::post('/checkout/proses', [CheckoutController::class, 'proses']) ->name('checkout.proses');
    Route::get ('/checkout/sukses/{nomorTransaksi}',
                                    [CheckoutController::class, 'sukses']) ->name('checkout.sukses');
});

require __DIR__ . '/auth.php';
